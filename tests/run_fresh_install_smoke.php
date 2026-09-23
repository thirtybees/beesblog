<?php
/**
 * Destructive clean-install regression for a disposable thirty bees fixture.
 * Leaves Bees Blog freshly installed when successful.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_fresh_install_smoke.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once __DIR__.'/integration_helpers.php';

use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogImageType;
use BeesBlogModule\BeesBlogResponsiveImage;
use BeesBlogModule\BeesBlogResponsiveImageJob;

$exitCode = 0;

try {
    Module::updateTranslationsAfterInstall(false);
    Context::getContext()->employee = new Employee((int) Db::getInstance()->getValue(
        'SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` ORDER BY `id_employee` ASC'
    ));

    $module = Module::getInstanceByName('beesblog');
    if (Module::isInstalled('beesblog')) {
        assertTest($module->uninstall(), 'existing Bees Blog installation can be removed');
    }

    $module = Module::getInstanceByName('beesblog');
    assertTest($module->install(), 'Bees Blog installs directly on an empty module schema');
    assertTest(Module::isInstalled('beesblog'), 'fresh installation is registered');
    $shopCount = count(Shop::getShops(false, null, true));

    foreach ([
        'bees_blog_post',
        'bees_blog_post_shop',
        'bees_blog_post_lang',
        'bees_blog_category',
        'bees_blog_category_shop',
        'bees_blog_category_lang',
        'bees_blog_post_product',
        BeesBlogImage::TABLE,
        BeesBlogImageType::TABLE,
        BeesBlogImageType::SHOP_TABLE,
        BeesBlogResponsiveImage::TABLE,
        BeesBlogResponsiveImageJob::TABLE,
    ] as $table) {
        assertTest(tableExistsForTest($table), 'fresh installation creates '.$table);
    }

    assertTest(
        !columnExistsForTest('bees_blog_post', 'image'),
        'fresh base post table does not require the legacy image column'
    );
    assertTest(
        columnExistsForTest('bees_blog_post_shop', 'image'),
        'fresh post shop table contains the shop-scoped image field'
    );
    assertTest(
        primaryColumnsForTest('bees_blog_post_lang') === ['id_bees_blog_post', 'id_shop', 'id_lang'],
        'fresh post translations include the 1.9 shop-aware primary key'
    );
    assertTest(
        primaryColumnsForTest('bees_blog_category_lang') === ['id_bees_blog_category', 'id_shop', 'id_lang'],
        'fresh category translations include the 1.9 shop-aware primary key'
    );
    assertTest(
        primaryColumnsForTest('bees_blog_post_product') === ['id_product', 'id_bees_blog_post', 'id_shop'],
        'fresh related products include the 1.9 shop scope'
    );
    foreach (Language::getLanguages(false, false, true) as $idLang) {
        assertTest(
            BeesBlog::getBlogUrlKey((int) $idLang, (int) Configuration::get('PS_SHOP_DEFAULT')) !== '',
            'fresh installation seeds the translated 1.9 blog URL for language '.(int) $idLang
        );
    }
    $duplicationHookId = (int) Hook::getIdByName('actionShopDataDuplication');
    assertTest(
        $duplicationHookId > 0 && (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `'._DB_PREFIX_.'hook_module` hm'.
            ' INNER JOIN `'._DB_PREFIX_.'module` m ON m.`id_module` = hm.`id_module`'.
            ' WHERE m.`name` = \'beesblog\' AND hm.`id_hook` = '.$duplicationHookId
        ),
        'fresh installation registers the 1.9 shop-duplication hook'
    );
    assertTest(
        Configuration::get(BeesBlogResponsiveImage::CONFIG_WIDTHS) !== false,
        'fresh installation seeds the 1.10 responsive widths'
    );
    assertTest(
        (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'bees_blog_image_type`'
        ) === 3,
        'fresh installation seeds each legacy compatibility image type once'
    );
    assertTest(
        (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'bees_blog_image_type_shop`'
        ) === 3 * $shopCount,
        'fresh installation associates each compatibility image type with every shop'
    );
    assertTest(
        (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'bees_blog_post`'
        ) === 3,
        'fresh installation creates three fixture posts'
    );
    assertTest(
        (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'` WHERE `entity_type` = \'posts\''
        ) === 3 * $shopCount,
        'fresh installation stores all fixture source images'
    );
    assertTest(
        (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogResponsiveImageJob::TABLE.'`'.
            ' WHERE `entity_type` = \'posts\' AND `status` = \'completed\''
        ) === 3 * $shopCount,
        'fresh installation completes responsive fixture generation'
    );
    assertTest(
        Db::getInstance()->getValue(
            'SELECT `version` FROM `'._DB_PREFIX_.'module` WHERE `name` = \'beesblog\''
        ) === '1.10.0',
        'fresh installation records module version 1.10.0'
    );

    echo "RESULT: fresh Bees Blog installation checks passed\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: '.$error->getMessage()."\n".$error->getTraceAsString()."\n");
    $exitCode = 1;
}

exit($exitCode);
