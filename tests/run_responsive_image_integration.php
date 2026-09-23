<?php
/**
 * Focused integration checks for Bees Blog responsive generation and legacy
 * fallback behavior. Temporary entities, files, and configuration are cleaned.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_responsive_image_integration.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once __DIR__.'/integration_helpers.php';

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogMultistore;
use BeesBlogModule\BeesBlogPost;
use BeesBlogModule\BeesBlogResponsiveImage;
use BeesBlogModule\BeesBlogResponsiveImageJob;

$db = Db::getInstance();
$idShop = (int) Context::getContext()->shop->id;
$idLang = (int) Context::getContext()->language->id;
$originalContext = Shop::getContext();
$originalContextShop = (int) Shop::getContextShopID();
$originalWidths = Configuration::get(BeesBlogResponsiveImage::CONFIG_WIDTHS);
$originalFormat = Configuration::get('TB_IMAGE_EXTENSION');
$category = null;
$post = null;
$exitCode = 0;

try {
    Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
    assertTest(BeesBlogMultistore::migrateSchema(), 'responsive image schema migration is idempotent');
    assertTest(tableExistsForTest(BeesBlogResponsiveImage::TABLE), 'responsive image manifest table exists');
    assertTest(tableExistsForTest(BeesBlogResponsiveImageJob::TABLE), 'responsive image job table exists');
    assertTest(
        Configuration::get(BeesBlogResponsiveImage::CONFIG_WIDTHS) !== false,
        'responsive widths are seeded during migration'
    );

    $normalizationError = null;
    assertTest(
        BeesBlogResponsiveImage::normalizeWidths('768, 320,480,768', $normalizationError) === '320,480,768',
        'candidate widths are sorted and deduplicated'
    );
    assertTest(
        BeesBlogResponsiveImage::normalizeWidths('320, 20, nope', $normalizationError) === false,
        'invalid or unsafe candidate widths are rejected'
    );

    $targetFormat = ImageManager::serverSupportsAvif()
        ? 'avif'
        : (ImageManager::serverSupportsWebp() ? 'webp' : (string) $originalFormat);
    assertTest(Configuration::updateValue('TB_IMAGE_EXTENSION', $targetFormat), 'test image format can be configured');
    assertTest(
        Configuration::updateValue(BeesBlogResponsiveImage::CONFIG_WIDTHS, '320,480,768,1920'),
        'test candidate widths can be configured'
    );

    $token = 'responsive-'.substr(sha1(uniqid('', true)), 0, 10);
    $category = addCategoryForTest($token.'-category', [$idShop]);
    $post = addPostForTest($token.'-post', (int) $category->id, [$idShop]);
    $source = $root.'/modules/beesblog/fixtures/post1.jpg';
    $sourceInfo = getimagesize($source);
    $imageError = null;
    assertTest(BeesBlogImage::saveImageFile(
        $source,
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        [$idShop],
        0,
        $imageError
    ), 'one uploaded image generates legacy and responsive files');

    $jobWhere = '`entity_type` = \'posts\' AND `id_object` = '.(int) $post->id.
        ' AND `id_shop` = '.$idShop.' AND `id_lang` = 0';
    assertTest(
        $db->getValue(
            'SELECT `status` FROM `'._DB_PREFIX_.BeesBlogResponsiveImageJob::TABLE.'` WHERE '.$jobWhere
        ) === BeesBlogResponsiveImageJob::STATUS_COMPLETED,
        'a successful upload records completed responsive progress'
    );

    $manifest = BeesBlogResponsiveImage::getManifest(
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        $idShop,
        0
    );
    assertTest($manifest && $manifest['status'] === 'ready', 'a ready manifest is activated after generation');
    assertTest($manifest['modern_extension'] === $targetFormat, 'candidates use the thirty bees configured image format');
    assertTest($manifest['fallback_extension'] === 'jpg', 'a JPEG legacy fallback is generated for a JPEG upload');
    assertTest($manifest['widths'] === '320,480,768', 'configured widths larger than the source are omitted');
    assertTest((int) $manifest['fallback_width'] === 768, 'the largest generated candidate is the single fallback width');

    $scope = rtrim(_PS_IMG_DIR_, '/\\').DIRECTORY_SEPARATOR.'beesblog'.DIRECTORY_SEPARATOR.'posts'.
        DIRECTORY_SEPARATOR.'responsive'.DIRECTORY_SEPARATOR.(int) $post->id.'-s'.$idShop.DIRECTORY_SEPARATOR.
        $manifest['generation'].DIRECTORY_SEPARATOR;
    $fallbacks = glob($scope.'fallback-*.*');
    assertTest(count($fallbacks) === 1 && file_exists($fallbacks[0]), 'exactly one legacy fallback file exists');
    foreach ([320, 480, 768] as $width) {
        $candidate = $scope.$width.'.'.$targetFormat;
        $info = getimagesize($candidate);
        assertTest($info && (int) $info[0] === $width, $width.'px candidate exists at the declared width');
        assertTest(
            abs(((int) $info[0] / (int) $info[1]) - ((int) $sourceInfo[0] / (int) $sourceInfo[1])) < 0.01,
            $width.'px candidate preserves the complete source aspect ratio'
        );
    }
    assertTest(!file_exists($scope.'1920.'.$targetFormat), 'generation never upscales a source image');

    $imageData = BeesBlogResponsiveImage::getImageData(
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        'post_default',
        $idShop,
        $idLang
    );
    assertTest($imageData && strpos($imageData['srcset'], '768w') !== false, 'storefront data exposes width descriptors');
    $targetInformation = Media::getFileInformations('images', $targetFormat);
    assertTest(
        $imageData['modern_type'] === $targetInformation['mimeType'],
        'storefront source has the configured MIME type'
    );
    assertTest((int) $imageData['width'] === 768 && (int) $imageData['height'] > 0, 'fallback has intrinsic dimensions');

    assertTest($db->update(
        BeesBlogResponsiveImage::TABLE,
        ['status' => 'failed'],
        '`entity_type` = \'posts\' AND `id_object` = '.(int) $post->id.
        ' AND `id_shop` = '.$idShop.' AND `id_lang` = 0'
    ), 'a deferred-migration state can be simulated');
    $legacyData = BeesBlogResponsiveImage::getImageData(
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        'post_default',
        $idShop,
        $idLang
    );
    assertTest($legacyData && $legacyData['srcset'] === '', 'old images render through the legacy file before regeneration');

    assertTest(
        Configuration::updateValue(BeesBlogResponsiveImage::CONFIG_WIDTHS, '320,640'),
        'merchant breakpoint changes can be saved'
    );
    assertTest(
        BeesBlogResponsiveImageJob::synchronize(BeesBlogImage::ENTITY_POST, [$idShop]),
        'responsive progress synchronizes after a breakpoint change'
    );
    assertTest(
        $db->getValue(
            'SELECT `status` FROM `'._DB_PREFIX_.BeesBlogResponsiveImageJob::TABLE.'` WHERE '.$jobWhere
        ) === BeesBlogResponsiveImageJob::STATUS_PENDING,
        'an outdated responsive set is reported as missing'
    );
    $currentOriginal = BeesBlogImage::getImagePath(
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        'original',
        $idShop,
        0
    );
    $regenerationError = null;
    assertTest(BeesBlogResponsiveImage::generate(
        $currentOriginal,
        BeesBlogImage::ENTITY_POST,
        (int) $post->id,
        $idShop,
        0,
        true,
        $regenerationError
    ), 'responsive regeneration completes without errors');
    $regenerated = BeesBlogResponsiveImage::getManifest(BeesBlogImage::ENTITY_POST, (int) $post->id, $idShop, 0);
    assertTest($regenerated['widths'] === '320,640', 'regeneration applies the merchant’s current widths');
    assertTest(
        $db->getValue(
            'SELECT `status` FROM `'._DB_PREFIX_.BeesBlogResponsiveImageJob::TABLE.'` WHERE '.$jobWhere
        ) === BeesBlogResponsiveImageJob::STATUS_COMPLETED,
        'successful regeneration updates progress without a page reload'
    );
    assertTest($regenerated['generation'] !== $manifest['generation'], 'regeneration atomically activates a new generation');
    assertTest(!is_dir($scope), 'the superseded generation directory is removed after activation');

    $temporarilyMissing = $currentOriginal.'.missing-source-test';
    assertTest(rename($currentOriginal, $temporarilyMissing), 'a missing stored source can be simulated');
    try {
        assertTest(
            BeesBlogResponsiveImageJob::synchronize(BeesBlogImage::ENTITY_POST, [$idShop], true),
            'responsive progress verifies stored source files'
        );
        assertTest(
            $db->getValue(
                'SELECT `status` FROM `'._DB_PREFIX_.BeesBlogResponsiveImageJob::TABLE.'` WHERE '.$jobWhere
            ) === BeesBlogResponsiveImageJob::STATUS_FAILED,
            'an unreadable stored source is reported as failed'
        );
    } finally {
        rename($temporarilyMissing, $currentOriginal);
        BeesBlogResponsiveImageJob::markCompleted(
            BeesBlogImage::ENTITY_POST,
            (int) $post->id,
            $idShop,
            0,
            BeesBlogResponsiveImage::getConfigurationHash($idShop)
        );
    }

    echo "RESULT: responsive image integration tests passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    $exitCode = 1;
} finally {
    Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
    Configuration::updateValue('TB_IMAGE_EXTENSION', $originalFormat);
    Configuration::updateValue(BeesBlogResponsiveImage::CONFIG_WIDTHS, $originalWidths);
    if ($post && (int) $post->id) {
        BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_POST, (int) $post->id, [$idShop]);
        $db->delete('bees_blog_post_product', '`'.BeesBlogPost::PRIMARY.'` = '.(int) $post->id);
        $db->delete(BeesBlogPost::LANG_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.(int) $post->id);
        $db->delete(BeesBlogPost::SHOP_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.(int) $post->id);
        $db->delete(BeesBlogPost::TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.(int) $post->id);
    }
    if ($category && (int) $category->id) {
        $db->delete(BeesBlogCategory::LANG_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.(int) $category->id);
        $db->delete(BeesBlogCategory::SHOP_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.(int) $category->id);
        $db->delete(BeesBlogCategory::TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.(int) $category->id);
    }
    Shop::setContext($originalContext, $originalContextShop ?: null);
}

exit($exitCode);
