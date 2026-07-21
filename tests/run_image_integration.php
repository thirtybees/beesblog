<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2026 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

/**
 * Destructive-to-test-data integration test for Bees Blog image storage,
 * migration, shop/language resolution, replacement, and cleanup. Schema
 * upgrades are retained; temporary shops, entities, and files are removed.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_image_integration.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once __DIR__.'/integration_helpers.php';

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogImageType;
use BeesBlogModule\BeesBlogMultistore;
use BeesBlogModule\BeesBlogPost;

$db = Db::getInstance();
$module = Module::getInstanceByName('beesblog');
$createdPosts = [];
$createdCategories = [];
$imageShopIds = [];
$legacyFiles = [];
$testShopId = 0;
$originalContext = Shop::getContext();
$originalContextShopId = (int) Shop::getContextShopID();
$originalContextGroupId = (int) Shop::getContextShopGroupID();
$originalShop = Context::getContext()->shop;
$token = 'codex-image-'.strtolower(substr(sha1(uniqid('', true)), 0, 10));

try {
    assertTest($module instanceof BeesBlog, 'installed module instance loads');
    assertTest(BeesBlogMultistore::migrateSchema(), 'image schema and migration are available');
    assertTest(tableExistsForTest(BeesBlogImage::TABLE), 'shop/language image association table exists');
    assertTest(
        columnExistsForTest(BeesBlogImage::TABLE, 'thumbnail_extension'),
        'image associations store the generated thumbnail extension'
    );

    $sourceShop = (array) $db->getRow(
        'SELECT * FROM `'._DB_PREFIX_.'shop` WHERE `active` = 1 AND `deleted` = 0 ORDER BY `id_shop` ASC'
    );
    assertTest(!empty($sourceShop['id_shop']), 'source shop is available');
    $sourceShopId = (int) $sourceShop['id_shop'];

    assertTest($db->execute(
        'INSERT INTO `'._DB_PREFIX_.'shop` (`id_shop_group`, `name`, `id_category`, `id_theme`, `active`, `deleted`)'.
        ' SELECT `id_shop_group`, \''.pSQL('BeesBlog '.$token).'\', `id_category`, `id_theme`, 1, 0'.
        ' FROM `'._DB_PREFIX_.'shop` WHERE `id_shop` = '.$sourceShopId
    ), 'temporary image-test shop row is created');
    $testShopId = (int) $db->Insert_ID();
    assertTest($testShopId > 0, 'temporary image-test shop has an id');

    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'lang_shop` (`id_lang`, `id_shop`)'.
        ' SELECT `id_lang`, '.$testShopId.' FROM `'._DB_PREFIX_.'lang_shop` WHERE `id_shop` = '.$sourceShopId
    );
    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'employee_shop` (`id_employee`, `id_shop`)'.
        ' SELECT `id_employee`, '.$testShopId.' FROM `'._DB_PREFIX_.'employee_shop` WHERE `id_shop` = '.$sourceShopId
    );
    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'module_shop` (`id_module`, `id_shop`)'.
        ' SELECT `id_module`, '.$testShopId.' FROM `'._DB_PREFIX_.'module_shop` WHERE `id_shop` = '.$sourceShopId
    );
    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.'hook_module` (`id_module`, `id_shop`, `id_hook`, `position`)'.
        ' SELECT `id_module`, '.$testShopId.', `id_hook`, `position`'.
        ' FROM `'._DB_PREFIX_.'hook_module` WHERE `id_shop` = '.$sourceShopId
    );
    $db->execute(
        'INSERT IGNORE INTO `'._DB_PREFIX_.BeesBlogImageType::SHOP_TABLE.'`'.
        ' (`'.BeesBlogImageType::PRIMARY.'`, `id_shop`)'.
        ' SELECT `'.BeesBlogImageType::PRIMARY.'`, '.$testShopId.
        ' FROM `'._DB_PREFIX_.BeesBlogImageType::SHOP_TABLE.'` WHERE `id_shop` = '.$sourceShopId
    );
    Shop::cacheShops(true);
    Language::loadLanguages();

    Shop::setContext(Shop::CONTEXT_ALL);
    $imageShopIds = BeesBlogMultistore::getContextShopIds();
    assertTest(
        in_array($sourceShopId, $imageShopIds, true) && in_array($testShopId, $imageShopIds, true),
        'all-shops image scope contains the source and temporary shops'
    );

    $sharedCategory = addCategoryForTest($token.'-category', $imageShopIds);
    $createdCategories[] = (int) $sharedCategory->id;
    $sharedPost = addPostForTest($token.'-post', $sharedCategory->id, $imageShopIds);
    $createdPosts[] = (int) $sharedPost->id;

    $firstImageLanguage = (int) Configuration::get('PS_LANG_DEFAULT');
    $imageLanguages = Language::getLanguages(true, $testShopId, true);
    $secondImageLanguage = isset($imageLanguages[1]) ? (int) $imageLanguages[1] : $firstImageLanguage;
    $fixtureOne = $root.'/modules/beesblog/fixtures/post1.jpg';
    $fixtureTwo = $root.'/modules/beesblog/fixtures/post2.jpg';
    $fixtureThree = $root.'/modules/beesblog/fixtures/post3.jpg';
    $imageError = null;
    $legacyImageDirectory = rtrim(_PS_IMG_DIR_, '/\\').'/beesblog/posts/';
    if (!is_dir($legacyImageDirectory)) {
        assertTest(mkdir($legacyImageDirectory, 0777, true), 'legacy image test directory can be created');
    }
    $legacyJpg = $legacyImageDirectory.(int) $sharedPost->id.'.jpg';
    $legacyJpeg = $legacyImageDirectory.(int) $sharedPost->id.'.jpeg';
    $legacyFiles[] = $legacyJpg;
    $legacyFiles[] = $legacyJpeg;
    assertTest(copy($fixtureOne, $legacyJpg) && copy($fixtureTwo, $legacyJpeg), 'legacy image variants can be prepared');
    assertTest(touch($legacyJpg, time() - 10) && touch($legacyJpeg, time()), 'legacy image timestamps can be prepared');
    assertTest(
        BeesBlogPost::getImagePath(
            (int) $sharedPost->id,
            'original',
            $sourceShopId,
            $firstImageLanguage
        ) === false,
        'runtime loading ignores unassociated legacy files'
    );
    assertTest(BeesBlogMultistore::migrateSchema(), '1.9 migration converts legacy images once');
    $sourceMigratedImage = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'original',
        $sourceShopId,
        $firstImageLanguage
    );
    $targetMigratedImage = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'original',
        $testShopId,
        $firstImageLanguage
    );
    assertTest(
        $sourceMigratedImage && $targetMigratedImage
        && file_exists($sourceMigratedImage) && file_exists($targetMigratedImage)
        && $sourceMigratedImage !== $targetMigratedImage,
        'migration creates independent shop-default files for every association'
    );
    assertTest(
        pathinfo($sourceMigratedImage, PATHINFO_EXTENSION) === 'jpeg'
        && pathinfo($targetMigratedImage, PATHINFO_EXTENSION) === 'jpeg',
        'migration preserves the selected legacy original extension'
    );
    assertTest(
        hash_file('sha256', $sourceMigratedImage) === hash_file('sha256', $fixtureTwo)
        && hash_file('sha256', $targetMigratedImage) === hash_file('sha256', $fixtureTwo)
        && filesize($sourceMigratedImage) === filesize($fixtureTwo)
        && filesize($targetMigratedImage) === filesize($fixtureTwo),
        'migration selects the newest legacy original and preserves its bytes and size'
    );
    $configuredThumbnailExtension = ImageManager::getDefaultImageExtension();
    $migratedThumbnail = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'post_default',
        $sourceShopId,
        $firstImageLanguage
    );
    assertTest(
        $migratedThumbnail && file_exists($migratedThumbnail)
        && pathinfo($migratedThumbnail, PATHINFO_EXTENSION) === $configuredThumbnailExtension,
        'migration generates thumbnails in the configured thirty bees format'
    );
    assertTest(
        !file_exists($legacyJpg) && !file_exists($legacyJpeg),
        'migration removes legacy originals after successful conversion'
    );
    assertTest(
        (string) $db->getValue(
            'SELECT `image` FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'`'.
            ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id.' AND `id_shop` = '.$sourceShopId
        ) === '',
        'migration clears the obsolete post_shop image value'
    );

    assertTest(BeesBlogImage::deleteForShops(
        BeesBlogImage::ENTITY_POST,
        (int) $sharedPost->id,
        [$sourceShopId],
        0
    ), 'a shop-default image can be deleted independently');
    assertTest(
        BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $sourceShopId, $firstImageLanguage) === false,
        'deleting one shop default leaves no runtime fallback'
    );
    assertTest(
        (int) $db->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'`'.
            ' WHERE `entity_type` = \'posts\' AND `id_object` = '.(int) $sharedPost->id.
            ' AND `id_shop` = '.$sourceShopId.' AND `id_lang` = 0'
        ) === 0,
        'shop-default deletion removes the association instead of creating a tombstone'
    );
    assertTest(
        BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $testShopId, $firstImageLanguage)
        === $targetMigratedImage,
        'deleting one shop image preserves another shop image'
    );

    assertTest(BeesBlogImage::saveImageFile(
        $fixtureOne,
        BeesBlogImage::ENTITY_POST,
        (int) $sharedPost->id,
        [$sourceShopId],
        0,
        $imageError
    ), 'a default post image can be stored in one shop');
    assertTest(BeesBlogImage::duplicateShop($sourceShopId, $testShopId), 'shop image associations and files can be duplicated');
    $duplicatedImage = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'original',
        $testShopId,
        $firstImageLanguage
    );
    assertTest($duplicatedImage && file_exists($duplicatedImage), 'duplicated shop resolves an independent image file');
    assertTest(
        strpos(basename($duplicatedImage), '-s'.$testShopId.'.') !== false,
        'duplicated image filename is target-shop scoped'
    );

    $jpegNamedUpload = [
        'name' => 'merchant-upload.jpeg',
        'type' => 'image/jpeg',
        'tmp_name' => $fixtureTwo,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($fixtureTwo),
    ];
    assertTest(BeesBlogImage::saveUploadedImage(
        $jpegNamedUpload,
        BeesBlogImage::ENTITY_POST,
        (int) $sharedPost->id,
        $imageShopIds,
        0,
        $imageError
    ), 'a .jpeg-named upload is accepted in all-shops context');
    $sourceDefaultImage = BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $sourceShopId, $firstImageLanguage);
    $targetDefaultImage = BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $testShopId, $firstImageLanguage);
    assertTest(
        $sourceDefaultImage && $targetDefaultImage
        && file_exists($sourceDefaultImage) && file_exists($targetDefaultImage),
        'all-shops upload creates both physical images'
    );
    assertTest($sourceDefaultImage !== $targetDefaultImage, 'all-shops upload keeps independent shop files');
    assertTest(
        pathinfo($sourceDefaultImage, PATHINFO_EXTENSION) === 'jpeg'
        && pathinfo($targetDefaultImage, PATHINFO_EXTENSION) === 'jpeg'
        && hash_file('sha256', $sourceDefaultImage) === hash_file('sha256', $fixtureTwo)
        && hash_file('sha256', $targetDefaultImage) === hash_file('sha256', $fixtureTwo)
        && filesize($sourceDefaultImage) === filesize($fixtureTwo)
        && filesize($targetDefaultImage) === filesize($fixtureTwo),
        'uploaded originals preserve their extension, exact bytes, and size in every shop'
    );
    $targetDefaultThumbnail = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'post_default',
        $testShopId,
        $firstImageLanguage
    );
    assertTest(
        $targetDefaultThumbnail && file_exists($targetDefaultThumbnail)
        && strpos(basename($targetDefaultThumbnail), '-s'.$testShopId.'-post_default.') !== false
        && pathinfo($targetDefaultThumbnail, PATHINFO_EXTENSION) === $configuredThumbnailExtension,
        'front-office post_default resolves the configured-format target-shop thumbnail'
    );

    $previousTargetImage = $targetDefaultImage;
    assertTest(BeesBlogImage::saveImageFile(
        $fixtureThree,
        BeesBlogImage::ENTITY_POST,
        (int) $sharedPost->id,
        [$testShopId],
        0,
        $imageError,
        'jpeg'
    ), 'a single-shop upload can replace an all-shops image with the same original extension');
    $targetDefaultImage = BeesBlogPost::getImagePath(
        (int) $sharedPost->id,
        'original',
        $testShopId,
        $firstImageLanguage
    );
    assertTest(
        $targetDefaultImage === $previousTargetImage
        && file_exists($targetDefaultImage)
        && hash_file('sha256', $targetDefaultImage) === hash_file('sha256', $fixtureThree)
        && hash_file('sha256', $sourceDefaultImage) === hash_file('sha256', $fixtureTwo),
        'single-shop replacement updates only that shop image bytes'
    );

    if ($secondImageLanguage !== $firstImageLanguage) {
        assertTest(BeesBlogImage::saveImageFile(
            $fixtureThree,
            BeesBlogImage::ENTITY_POST,
            (int) $sharedPost->id,
            $imageShopIds,
            $secondImageLanguage,
            $imageError
        ), 'a language-specific post image can be stored in all-shops context');
        $sourceLanguageOverride = BeesBlogPost::getImagePath(
            (int) $sharedPost->id,
            'original',
            $sourceShopId,
            $secondImageLanguage
        );
        $previousLanguageOverride = BeesBlogPost::getImagePath(
            (int) $sharedPost->id,
            'original',
            $testShopId,
            $secondImageLanguage
        );
        assertTest(BeesBlogImage::saveImageFile(
            $fixtureOne,
            BeesBlogImage::ENTITY_POST,
            (int) $sharedPost->id,
            [$testShopId],
            $secondImageLanguage,
            $imageError
        ), 'a dedicated-shop upload can replace its all-shops language override');
        $languageOverride = BeesBlogPost::getImagePath(
            (int) $sharedPost->id,
            'original',
            $testShopId,
            $secondImageLanguage
        );
        assertTest(
            $languageOverride !== $targetDefaultImage
            && strpos(basename($languageOverride), '-l'.$secondImageLanguage.'.') !== false,
            'language override wins over the shop default'
        );
        assertTest(
            $languageOverride === $previousLanguageOverride
            && file_exists($languageOverride)
            && file_exists($sourceLanguageOverride)
            && hash_file('sha256', $languageOverride) === hash_file('sha256', $fixtureOne)
            && hash_file('sha256', $sourceLanguageOverride) === hash_file('sha256', $fixtureThree),
            'shop-specific language replacement changes only that shop bytes'
        );
        $languageOverrideThumbnail = BeesBlogPost::getImagePath(
            (int) $sharedPost->id,
            'post_default',
            $testShopId,
            $secondImageLanguage
        );
        assertTest(
            $languageOverrideThumbnail && file_exists($languageOverrideThumbnail)
            && strpos(
                basename($languageOverrideThumbnail),
                '-s'.$testShopId.'-l'.$secondImageLanguage.'-post_default.'
            ) !== false
            && pathinfo($languageOverrideThumbnail, PATHINFO_EXTENSION) === $configuredThumbnailExtension,
            'front-office post_default resolves the configured-format language override thumbnail'
        );
        assertTest(
            BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $testShopId, $firstImageLanguage)
            === $targetDefaultImage,
            'another language continues to use the shop default'
        );
        assertTest(BeesBlogImage::deleteForShops(
            BeesBlogImage::ENTITY_POST,
            (int) $sharedPost->id,
            [$testShopId],
            $secondImageLanguage
        ), 'language override can be removed');
        assertTest(
            BeesBlogPost::getImagePath((int) $sharedPost->id, 'original', $testShopId, $secondImageLanguage)
            === $targetDefaultImage,
            'removing a language override restores shop-default fallback'
        );
    }

    assertTest(BeesBlogImage::saveImageFile(
        $fixtureOne,
        BeesBlogImage::ENTITY_CATEGORY,
        (int) $sharedCategory->id,
        $imageShopIds,
        0,
        $imageError
    ), 'category images use the same all-shops association model');
    assertTest(
        BeesBlogCategory::getImagePath((int) $sharedCategory->id, 'original', $testShopId, $firstImageLanguage)
        !== BeesBlogCategory::getImagePath((int) $sharedCategory->id, 'original', $sourceShopId, $firstImageLanguage),
        'category image files are shop-specific'
    );
    $categoryThumbnail = BeesBlogCategory::getImagePath(
        (int) $sharedCategory->id,
        'category_default',
        $testShopId,
        $firstImageLanguage
    );
    assertTest(
        $categoryThumbnail && file_exists($categoryThumbnail)
        && strpos(basename($categoryThumbnail), '-s'.$testShopId.'-category_default.') !== false
        && pathinfo($categoryThumbnail, PATHINFO_EXTENSION) === $configuredThumbnailExtension,
        'front-office category_default resolves the configured-format target-shop thumbnail'
    );

    $sourceCategoryImage = BeesBlogCategory::getImagePath(
        (int) $sharedCategory->id,
        'original',
        $sourceShopId,
        $firstImageLanguage
    );
    assertTest(BeesBlogMultistore::migrateSchema(), 'image migration can be rerun');
    assertTest(
        BeesBlogCategory::getImagePath(
            (int) $sharedCategory->id,
            'original',
            $sourceShopId,
            $firstImageLanguage
        ) === $sourceCategoryImage,
        'rerunning image migration preserves an existing scoped image'
    );

    echo "RESULT: all image integration tests passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    $exitCode = 1;
} finally {
    Shop::setContext(Shop::CONTEXT_ALL);

    foreach (array_unique(array_map('intval', $createdPosts)) as $idPost) {
        if ($imageShopIds && tableExistsForTest(BeesBlogImage::TABLE)) {
            BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_POST, $idPost, $imageShopIds);
        }
        $db->delete('bees_blog_post_product', '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::LANG_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::SHOP_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
    }
    foreach (array_unique(array_map('intval', $createdCategories)) as $idCategory) {
        if ($imageShopIds && tableExistsForTest(BeesBlogImage::TABLE)) {
            BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_CATEGORY, $idCategory, $imageShopIds);
        }
        $db->delete(BeesBlogCategory::LANG_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
        $db->delete(BeesBlogCategory::SHOP_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
        $db->delete(BeesBlogCategory::TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
    }
    foreach ($legacyFiles as $legacyFile) {
        if (is_string($legacyFile) && file_exists($legacyFile)) {
            @unlink($legacyFile);
        }
    }

    if ($testShopId) {
        if (tableExistsForTest(BeesBlogImage::TABLE)) {
            foreach ((array) $db->executeS(
                'SELECT DISTINCT `entity_type`, `id_object` FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'`'.
                ' WHERE `id_shop` = '.(int) $testShopId
            ) as $image) {
                BeesBlogImage::deleteForShops(
                    $image['entity_type'],
                    (int) $image['id_object'],
                    [$testShopId]
                );
            }
        }
        foreach ([
            'bees_blog_post_product',
            BeesBlogPost::LANG_TABLE,
            BeesBlogPost::SHOP_TABLE,
            BeesBlogCategory::LANG_TABLE,
            BeesBlogCategory::SHOP_TABLE,
            BeesBlogImageType::SHOP_TABLE,
            BeesBlogImage::TABLE,
            'module_shop',
            'hook_module',
            'employee_shop',
            'lang_shop',
            'shop_url',
        ] as $table) {
            $db->delete($table, '`id_shop` = '.(int) $testShopId);
        }
        $db->delete('shop', '`id_shop` = '.(int) $testShopId.' AND `name` = \''.pSQL('BeesBlog '.$token).'\'');
        Shop::cacheShops(true);
    }

    if ($originalContext === Shop::CONTEXT_SHOP && $originalContextShopId) {
        Shop::setContext(Shop::CONTEXT_SHOP, $originalContextShopId);
    } elseif ($originalContext === Shop::CONTEXT_GROUP && $originalContextGroupId) {
        Shop::setContext(Shop::CONTEXT_GROUP, $originalContextGroupId);
    } else {
        Shop::setContext(Shop::CONTEXT_ALL);
    }
    Context::getContext()->shop = $originalShop;
}

exit(isset($exitCode) ? $exitCode : 0);
