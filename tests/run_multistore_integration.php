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
 * Destructive-to-test-data integration test for an existing thirty bees test
 * installation. The schema upgrade is intentionally retained; all temporary
 * shops and blog entities are removed in a finally block.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_multistore_integration.php <thirty-bees-root>\n");
    exit(1);
}

require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once $root.'/modules/beesblog/upgrade/upgrade-1.9.0.php';
require_once __DIR__.'/integration_helpers.php';

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogLanguageLink;
use BeesBlogModule\BeesBlogMultistore;
use BeesBlogModule\BeesBlogPost;

$db = Db::getInstance();
$module = Module::getInstanceByName('beesblog');
$createdPosts = [];
$createdCategories = [];
$testConfigurationKeys = [];
$testShopId = 0;
$originalContext = Shop::getContext();
$originalContextShopId = (int) Shop::getContextShopID();
$originalContextGroupId = (int) Shop::getContextShopGroupID();
$originalShop = Context::getContext()->shop;
$token = 'codex-ms-'.strtolower(substr(sha1(uniqid('', true)), 0, 10));

try {
    assertTest($module instanceof BeesBlog, 'installed module instance loads');

    $postCountBefore = (int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::TABLE.'`');
    $categoryCountBefore = (int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::TABLE.'`');
    $wasLegacy = !columnExistsForTest(BeesBlogPost::LANG_TABLE, 'id_shop');

    assertTest(upgrade_module_1_9_0($module), '1.9.0 upgrade succeeds');
    assertTest(columnExistsForTest(BeesBlogPost::LANG_TABLE, 'id_shop'), 'post translations contain id_shop');
    assertTest(columnExistsForTest(BeesBlogCategory::LANG_TABLE, 'id_shop'), 'category translations contain id_shop');
    assertTest(primaryColumnsForTest(BeesBlogPost::LANG_TABLE) === [BeesBlogPost::PRIMARY, 'id_shop', 'id_lang'], 'post translation primary key is shop-aware');
    assertTest(primaryColumnsForTest(BeesBlogCategory::LANG_TABLE) === [BeesBlogCategory::PRIMARY, 'id_shop', 'id_lang'], 'category translation primary key is shop-aware');
    assertTest(columnExistsForTest('bees_blog_post_product', 'id_shop'), 'related products contain id_shop');
    assertTest((int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::TABLE.'`') === $postCountBefore, 'migration preserves post base rows');
    assertTest((int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::TABLE.'`') === $categoryCountBefore, 'migration preserves category base rows');
    if ($wasLegacy) {
        assertTest((int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`') > 0, 'legacy post translations were copied');
        assertTest((int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::LANG_TABLE.'`') > 0, 'legacy category translations were copied');
    }
    foreach (Language::getLanguages(false, false, true) as $idLang) {
        assertTest(
            BeesBlog::getBlogUrlKey((int) $idLang, (int) Configuration::get('PS_SHOP_DEFAULT')) !== '',
            'legacy blog URL key is available for language '.(int) $idLang
        );
    }

    $sourceShop = (array) $db->getRow(
        'SELECT * FROM `'._DB_PREFIX_.'shop` WHERE `active` = 1 AND `deleted` = 0 ORDER BY `id_shop` ASC'
    );
    assertTest(!empty($sourceShop['id_shop']), 'source shop is available');
    $sourceShopId = (int) $sourceShop['id_shop'];
    $sourceGroupId = (int) $sourceShop['id_shop_group'];

    assertTest($db->execute(
        'INSERT INTO `'._DB_PREFIX_.'shop` (`id_shop_group`, `name`, `id_category`, `id_theme`, `active`, `deleted`)'.
        ' SELECT `id_shop_group`, \''.pSQL('BeesBlog '.$token).'\', `id_category`, `id_theme`, 1, 0'.
        ' FROM `'._DB_PREFIX_.'shop` WHERE `id_shop` = '.$sourceShopId
    ), 'temporary shop row is created');
    $testShopId = (int) $db->Insert_ID();
    assertTest($testShopId > 0, 'temporary shop has an id');

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
    Shop::cacheShops(true);
    Language::loadLanguages();

    if (Shop::isFeatureActive()) {
        $configurationKey = 'BEESBLOG_TEST_'.strtoupper(substr(sha1($token), 0, 12));
        $testConfigurationKeys[] = $configurationKey;
        $configurationLanguageId = (int) Configuration::get('PS_LANG_DEFAULT');
        $configurationUpdater = new ReflectionMethod(BeesBlog::class, 'updateConfigurationValuesForContext');
        $configurationUpdater->setAccessible(true);

        assertTest(Configuration::updateGlobalValue(
            $configurationKey,
            [$configurationLanguageId => 'global-old']
        ), 'test global translated configuration can be created');
        assertTest(Configuration::updateValue(
            $configurationKey,
            [$configurationLanguageId => 'group-old'],
            false,
            $sourceGroupId,
            0
        ), 'test group translated configuration can be created');
        foreach ([$sourceShopId, $testShopId] as $idShop) {
            assertTest(Configuration::updateValue(
                $configurationKey,
                [$configurationLanguageId => 'shop-old-'.$idShop],
                false,
                $sourceGroupId,
                $idShop
            ), 'test shop translated configuration can be created for shop '.$idShop);
        }

        Shop::setContext(Shop::CONTEXT_ALL);
        assertTest($configurationUpdater->invoke(
            $module,
            [$configurationKey => [$configurationLanguageId => 'global-new']]
        ), 'all-shops configuration save succeeds');
        assertTest((int) $db->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'configuration`'.
            ' WHERE `name` = \''.pSQL($configurationKey).'\''.
            ' AND (`id_shop_group` IS NOT NULL AND `id_shop_group` != 0'.
            ' OR `id_shop` IS NOT NULL AND `id_shop` != 0)'
        ) === 0, 'all-shops configuration save removes existing group and shop overrides');
        foreach ([$sourceShopId, $testShopId] as $idShop) {
            assertTest(
                Configuration::get($configurationKey, $configurationLanguageId, $sourceGroupId, $idShop) === 'global-new',
                'all-shops configuration value is effective in shop '.$idShop
            );
        }

        foreach ([$sourceShopId, $testShopId] as $idShop) {
            assertTest(Configuration::updateValue(
                $configurationKey,
                [$configurationLanguageId => 'shop-stale-'.$idShop],
                false,
                $sourceGroupId,
                $idShop
            ), 'stale shop override can be recreated for shop '.$idShop);
        }
        Shop::setContext(Shop::CONTEXT_GROUP, $sourceGroupId);
        assertTest($configurationUpdater->invoke(
            $module,
            [$configurationKey => [$configurationLanguageId => 'group-new']]
        ), 'shop-group configuration save succeeds');
        assertTest((int) $db->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'configuration`'.
            ' WHERE `name` = \''.pSQL($configurationKey).'\''.
            ' AND `id_shop` IN ('.$sourceShopId.', '.$testShopId.')'
        ) === 0, 'shop-group configuration save removes existing overrides for shops in the group');
        foreach ([$sourceShopId, $testShopId] as $idShop) {
            assertTest(
                Configuration::get($configurationKey, $configurationLanguageId, $sourceGroupId, $idShop) === 'group-new',
                'shop-group configuration value is effective in shop '.$idShop
            );
        }

        Shop::setContext(Shop::CONTEXT_SHOP, $sourceShopId);
        Context::getContext()->shop = new Shop($sourceShopId);
        assertTest($configurationUpdater->invoke(
            $module,
            [$configurationKey => [$configurationLanguageId => 'source-shop-new']]
        ), 'dedicated-shop configuration save succeeds');
        assertTest(
            Configuration::get($configurationKey, $configurationLanguageId, $sourceGroupId, $sourceShopId) === 'source-shop-new',
            'dedicated-shop configuration save updates the selected shop'
        );
        assertTest(
            Configuration::get($configurationKey, $configurationLanguageId, $sourceGroupId, $testShopId) === 'group-new',
            'dedicated-shop configuration save does not change another shop'
        );
    } else {
        echo "SKIP: configuration hierarchy propagation requires multistore to be active before bootstrap\n";
    }

    if (Shop::isFeatureActive()) {
        $routeLanguageIds = Language::getLanguages(true, $testShopId, true);
        assertTest(count($routeLanguageIds) >= 2, 'temporary shop has at least two active languages for route testing');
        $firstRouteLanguageId = (int) $routeLanguageIds[0];
        $secondRouteLanguageId = (int) $routeLanguageIds[1];
        $firstBlogUrlKey = $token.'-blog-one';
        $secondBlogUrlKey = $token.'-blog-two';
        Shop::setContext(Shop::CONTEXT_SHOP, $testShopId);
        Context::getContext()->shop = new Shop($testShopId);
        assertTest(Configuration::updateValue(
            BeesBlog::MAIN_URL_KEY,
            [
                $firstRouteLanguageId => $firstBlogUrlKey,
                $secondRouteLanguageId => $secondBlogUrlKey,
            ],
            false,
            $sourceGroupId,
            $testShopId
        ), 'shop-specific translated blog URL keys can be saved');
        assertTest(
            BeesBlog::getBlogUrlKey($firstRouteLanguageId, $testShopId) === $firstBlogUrlKey,
            'first-language blog URL key is shop-scoped'
        );
        assertTest(
            BeesBlog::getBlogUrlKey($secondRouteLanguageId, $testShopId) === $secondBlogUrlKey,
            'second-language blog URL key is shop-scoped'
        );
        Shop::setContext(Shop::CONTEXT_SHOP, $sourceShopId);
        Context::getContext()->shop = new Shop($sourceShopId);
        $sourceShopBlogUrlKey = BeesBlog::getBlogUrlKey($firstRouteLanguageId, $sourceShopId);
        assertTest(
            $sourceShopBlogUrlKey !== $firstBlogUrlKey,
            'shop-specific blog URL key does not leak into another shop (source: '.$sourceShopBlogUrlKey.')'
        );
        Shop::setContext(Shop::CONTEXT_SHOP, $testShopId);
        Context::getContext()->shop = new Shop($testShopId);

        $routeDefinitions = $module->hookModuleRoutes(['id_shop' => $testShopId]);
        assertTest(
            $routeDefinitions['beesblog']['rule'] === '{'.BeesBlog::MAIN_URL_ROUTE_PARAM.'}',
            'blog route uses the translated-prefix placeholder'
        );
        $routeRegexp = $routeDefinitions['beesblog']['keywords'][BeesBlog::MAIN_URL_ROUTE_PARAM]['regexp'];
        assertTest(
            preg_match('#^(?:'.$routeRegexp.')$#u', $firstBlogUrlKey) === 1
            && preg_match('#^(?:'.$routeRegexp.')$#u', $secondBlogUrlKey) === 1,
            'shop route matcher accepts both configured language prefixes'
        );
        assertTest(
            preg_match('#^(?:'.$routeRegexp.')$#u', $token.'-unconfigured') === 0,
            'shop route matcher rejects unconfigured prefixes'
        );

        $firstBlogUrl = BeesBlog::getBeesBlogLink('beesblog', [], $testShopId, $firstRouteLanguageId);
        $secondBlogUrl = BeesBlog::getBeesBlogLink('beesblog', [], $testShopId, $secondRouteLanguageId);
        parse_str((string) parse_url($firstBlogUrl, PHP_URL_QUERY), $firstBlogQuery);
        parse_str((string) parse_url($secondBlogUrl, PHP_URL_QUERY), $secondBlogQuery);
        assertTest(
            strpos((string) parse_url($firstBlogUrl, PHP_URL_PATH), '/'.$firstBlogUrlKey) !== false
            || ($firstBlogQuery[BeesBlog::MAIN_URL_ROUTE_PARAM] ?? null) === $firstBlogUrlKey,
            'first-language generated URL contains its translated blog prefix in friendly or query form'
        );
        assertTest(
            strpos((string) parse_url($secondBlogUrl, PHP_URL_PATH), '/'.$secondBlogUrlKey) !== false
            || ($secondBlogQuery[BeesBlog::MAIN_URL_ROUTE_PARAM] ?? null) === $secondBlogUrlKey,
            'second-language generated URL contains its translated blog prefix in friendly or query form'
        );
        assertTest($firstBlogUrl !== $secondBlogUrl, 'translated blog prefixes generate different URLs');
        assertTest(BeesBlog::migrateMainUrlKeyTranslations(), 'blog URL key migration can be rerun');
        assertTest(
            BeesBlog::getBlogUrlKey($secondRouteLanguageId, $testShopId) === $secondBlogUrlKey,
            'rerunning URL-key migration preserves an existing translation'
        );
    } else {
        echo "SKIP: native configuration isolation requires multistore to be active before bootstrap\n";
    }
    Shop::setContext(Shop::CONTEXT_SHOP, $testShopId);
    Context::getContext()->shop = new Shop($testShopId);

    assertTest($module->hookActionShopDataDuplication([
        'old_id_shop' => $sourceShopId,
        'new_id_shop' => $testShopId,
    ]), 'shop duplication hook succeeds');
    assertTest(
        (int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` WHERE `id_shop` = '.$testShopId)
        === (int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` WHERE `id_shop` = '.$sourceShopId),
        'shop duplication copies post associations'
    );

    assertTest(BeesBlogMultistore::getSubmittedShopIds(BeesBlogPost::TABLE) === [$testShopId], 'shop context resolves only the selected shop');
    $testOnlyCategory = addCategoryForTest($token.'-test-category', [$testShopId]);
    $createdCategories[] = (int) $testOnlyCategory->id;
    assertTest((int) $db->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'` WHERE `'.BeesBlogCategory::PRIMARY.'` = '.(int) $testOnlyCategory->id
    ) === 1, 'dedicated-shop category has one association');

    Shop::setContext(Shop::CONTEXT_GROUP, $sourceGroupId);
    $groupShopIds = BeesBlogMultistore::getSubmittedShopIds(BeesBlogCategory::TABLE);
    sort($groupShopIds);
    assertTest(in_array($sourceShopId, $groupShopIds, true) && in_array($testShopId, $groupShopIds, true), 'group context resolves all shops in the group');
    $sharedCategory = addCategoryForTest($token.'-shared-category', $groupShopIds);
    $createdCategories[] = (int) $sharedCategory->id;

    $languageId = (int) Configuration::get('PS_LANG_DEFAULT');
    $shopCategory = new BeesBlogCategory((int) $sharedCategory->id, null, $testShopId);
    $shopCategory->active = false;
    $shopCategory->title[$languageId] = 'Stale shop category title';
    $shopCategory->link_rewrite[$languageId] = $token.'-stale-shop-category';
    $shopCategory->id_shop_list = [$testShopId];
    assertTest($shopCategory->update(), 'dedicated-shop category override can be created');

    $groupCategoryTitle = 'Group category '.$token;
    $groupCategorySlug = $token.'-group-category';
    $groupCategory = new BeesBlogCategory((int) $sharedCategory->id, null, $sourceShopId);
    $groupCategory->active = true;
    $groupCategory->title[$languageId] = $groupCategoryTitle;
    $groupCategory->link_rewrite[$languageId] = $groupCategorySlug;
    $groupCategory->id_shop_list = $groupShopIds;
    assertTest($groupCategory->update(), 'shop-group category update succeeds');
    assertTest((int) $db->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'`'.
        ' WHERE `'.BeesBlogCategory::PRIMARY.'` = '.(int) $sharedCategory->id.
        ' AND `id_shop` IN ('.implode(', ', $groupShopIds).') AND `active` = 1'
    ) === count($groupShopIds), 'shop-group category update replaces existing shop field values in the group');
    assertTest((int) $db->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogCategory::LANG_TABLE.'`'.
        ' WHERE `'.BeesBlogCategory::PRIMARY.'` = '.(int) $sharedCategory->id.
        ' AND `id_shop` IN ('.implode(', ', $groupShopIds).') AND `id_lang` = '.$languageId.
        ' AND `title` = \''.pSQL($groupCategoryTitle).'\''.
        ' AND `link_rewrite` = \''.pSQL($groupCategorySlug).'\''
    ) === count($groupShopIds), 'shop-group category update replaces existing translated values in the group');

    Shop::setContext(Shop::CONTEXT_ALL);
    $allShopIds = BeesBlogMultistore::getSubmittedShopIds(BeesBlogPost::TABLE);
    assertTest(in_array($sourceShopId, $allShopIds, true) && in_array($testShopId, $allShopIds, true), 'all-shops context resolves every authorized shop');

    $allShopsCategory = addCategoryForTest($token.'-all-shops-category', $allShopIds);
    $createdCategories[] = (int) $allShopsCategory->id;
    $allShopsPost = addPostForTest($token.'-all-shops-post', $allShopsCategory->id, $allShopIds);
    $createdPosts[] = (int) $allShopsPost->id;
    $shopPost = new BeesBlogPost((int) $allShopsPost->id, null, $testShopId);
    $shopPost->active = false;
    $shopPost->title[$languageId] = 'Stale shop post title';
    $shopPost->link_rewrite[$languageId] = $token.'-stale-shop-post';
    $shopPost->id_shop_list = [$testShopId];
    assertTest($shopPost->update(), 'dedicated-shop post override can be created');

    $allShopsPostTitle = 'All shops post '.$token;
    $allShopsPostSlug = $token.'-all-shops-post-new';
    $globalPost = new BeesBlogPost((int) $allShopsPost->id, null, $sourceShopId);
    $globalPost->active = true;
    $globalPost->title[$languageId] = $allShopsPostTitle;
    $globalPost->link_rewrite[$languageId] = $allShopsPostSlug;
    $globalPost->id_shop_list = $allShopIds;
    assertTest($globalPost->update(), 'all-shops post update succeeds');
    assertTest((int) $db->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'`'.
        ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $allShopsPost->id.
        ' AND `id_shop` IN ('.implode(', ', $allShopIds).') AND `active` = 1'
    ) === count($allShopIds), 'all-shops post update replaces existing shop field values');
    assertTest((int) $db->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`'.
        ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $allShopsPost->id.
        ' AND `id_shop` IN ('.implode(', ', $allShopIds).') AND `id_lang` = '.$languageId.
        ' AND `title` = \''.pSQL($allShopsPostTitle).'\''.
        ' AND `link_rewrite` = \''.pSQL($allShopsPostSlug).'\''
    ) === count($allShopIds), 'all-shops post update replaces existing translated values');

    $statusPost = addPostForTest($token.'-status-seed', $sharedCategory->id, [$sourceShopId]);
    $createdPosts[] = (int) $statusPost->id;
    $statusPost = new BeesBlogPost((int) $statusPost->id, null, $sourceShopId);
    $statusPost->id_shop_list = $allShopIds;
    $statusPost->setFieldsToUpdate(['active' => true]);
    $statusPost->active = false;
    assertTest($statusPost->update(), 'restricted all-shops update creates complete missing associations');
    assertTest(
        (int) $db->getValue(
            'SELECT `id_category` FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'`'.
            ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $statusPost->id.' AND `id_shop` = '.$testShopId
        ) === (int) $sharedCategory->id,
        'restricted update seeds non-updated shop fields from the representative shop'
    );
    assertTest(
        (string) $db->getValue(
            'SELECT `title` FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`'.
            ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $statusPost->id.
            ' AND `id_shop` = '.$testShopId.' AND `id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT')
        ) !== '',
        'restricted update seeds complete translation rows'
    );

    $sharedPost = addPostForTest($token.'-shared-post', $sharedCategory->id, $allShopIds);
    $createdPosts[] = (int) $sharedPost->id;

    $switchLanguageIds = Language::getLanguages(true, $testShopId, true);
    if (count($switchLanguageIds) >= 2) {
        $switchFirstLanguageId = (int) $switchLanguageIds[0];
        $switchSecondLanguageId = (int) $switchLanguageIds[1];
        $firstCategorySlug = $token.'-category-first';
        $secondCategorySlug = $token.'-category-second';
        $firstPostSlug = $token.'-post-first';
        $secondPostSlug = $token.'-post-second';

        $switchCategory = new BeesBlogCategory((int) $sharedCategory->id, null, $testShopId);
        $switchCategory->link_rewrite[$switchFirstLanguageId] = $firstCategorySlug;
        $switchCategory->link_rewrite[$switchSecondLanguageId] = $secondCategorySlug;
        $switchCategory->id_shop_list = [$testShopId];
        assertTest($switchCategory->update(), 'language-switch category rewrites can differ by language');

        $switchPost = new BeesBlogPost((int) $sharedPost->id, null, $testShopId);
        $switchPost->link_rewrite[$switchFirstLanguageId] = $firstPostSlug;
        $switchPost->link_rewrite[$switchSecondLanguageId] = $secondPostSlug;
        $switchPost->id_shop_list = [$testShopId];
        assertTest($switchPost->update(), 'language-switch post rewrites can differ by language');

        $categoryLanguageLink = new BeesBlogLanguageLink(
            Context::getContext()->link,
            BeesBlogLanguageLink::ENTITY_CATEGORY,
            (int) $sharedCategory->id,
            $testShopId
        );
        $postLanguageLink = new BeesBlogLanguageLink(
            Context::getContext()->link,
            BeesBlogLanguageLink::ENTITY_POST,
            (int) $sharedPost->id,
            $testShopId
        );
        $firstCategoryUrl = $categoryLanguageLink->getLanguageLink($switchFirstLanguageId);
        $secondCategoryUrl = $categoryLanguageLink->getLanguageLink($switchSecondLanguageId);
        $firstPostUrl = $postLanguageLink->getLanguageLink($switchFirstLanguageId);
        $secondPostUrl = $postLanguageLink->getLanguageLink($switchSecondLanguageId);
        assertTest(
            strpos($firstCategoryUrl, $firstCategorySlug) !== false
            && strpos($secondCategoryUrl, $secondCategorySlug) !== false
            && $firstCategoryUrl !== $secondCategoryUrl,
            'language switcher links directly to each translated category rewrite'
        );
        assertTest(
            strpos($firstPostUrl, $firstPostSlug) !== false
            && strpos($secondPostUrl, $secondPostSlug) !== false
            && $firstPostUrl !== $secondPostUrl,
            'language switcher links directly to each translated post rewrite'
        );
    } else {
        echo "SKIP: language-switch regression requires two active languages\n";
    }

    $shopOneSlug = (string) $db->getValue(
        'SELECT `link_rewrite` FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`'.
        ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id.
        ' AND `id_shop` = '.$sourceShopId.' AND `id_lang` = '.$languageId
    );
    $shopTwoPost = new BeesBlogPost((int) $sharedPost->id, null, $testShopId);
    $shopTwoPost->link_rewrite[$languageId] = $token.'-shop-two-slug';
    $shopTwoPost->title[$languageId] = 'Shop two title';
    $shopTwoPost->id_shop_list = [$testShopId];
    assertTest($shopTwoPost->update(), 'dedicated-shop update succeeds');
    assertTest(
        (string) $db->getValue(
            'SELECT `link_rewrite` FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`'.
            ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id.
            ' AND `id_shop` = '.$sourceShopId.' AND `id_lang` = '.$languageId
        ) === $shopOneSlug,
        'updating one shop does not change another shop slug'
    );
    assertTest(
        (int) BeesBlogPost::getIdByRewrite($shopOneSlug, true, $languageId, $sourceShopId) === (int) $sharedPost->id,
        'shop-one slug resolves in shop one'
    );
    assertTest(
        (int) BeesBlogPost::getIdByRewrite($token.'-shop-two-slug', true, $languageId, $testShopId) === (int) $sharedPost->id,
        'shop-two slug resolves in shop two'
    );

    $sameSlugShopOne = addPostForTest($token.'-same-slug', $sharedCategory->id, [$sourceShopId]);
    $createdPosts[] = (int) $sameSlugShopOne->id;
    $sameSlugShopTwo = addPostForTest($token.'-same-slug', $sharedCategory->id, [$testShopId]);
    $createdPosts[] = (int) $sameSlugShopTwo->id;
    assertTest(
        !BeesBlogMultistore::findSlugConflicts(
            BeesBlogPost::TABLE,
            BeesBlogPost::PRIMARY,
            (int) $sameSlugShopTwo->id,
            [$languageId => $token.'-same-slug-'.$languageId],
            [$testShopId]
        ),
        'identical slugs in different shops are accepted'
    );
    assertTest(
        (bool) BeesBlogMultistore::findSlugConflicts(
            BeesBlogPost::TABLE,
            BeesBlogPost::PRIMARY,
            (int) $sameSlugShopTwo->id,
            [$languageId => $token.'-same-slug-'.$languageId],
            [$sourceShopId]
        ),
        'the same slug is still detected as a conflict inside one shop'
    );

    assertTest(BeesBlogMultistore::migrateSchema(), 'schema migration can be rerun');
    assertTest(
        (string) $db->getValue(
            'SELECT `link_rewrite` FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'`'.
            ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id.
            ' AND `id_shop` = '.$testShopId.' AND `id_lang` = '.$languageId
        ) === $token.'-shop-two-slug',
        'rerunning migration preserves a shop-specific slug'
    );

    $shopTwoPost = new BeesBlogPost((int) $sharedPost->id, null, $testShopId);
    $shopTwoPost->id_shop_list = [$testShopId];
    assertTest($shopTwoPost->delete(), 'deleting in one shop context succeeds');
    assertTest((bool) $db->getValue(
        'SELECT 1 FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'`'.
        ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id.' AND `id_shop` = '.$sourceShopId
    ), 'shop-context delete preserves other shop association');
    assertTest((bool) $db->getValue(
        'SELECT 1 FROM `'._DB_PREFIX_.BeesBlogPost::TABLE.'` WHERE `'.BeesBlogPost::PRIMARY.'` = '.(int) $sharedPost->id
    ), 'shop-context delete preserves the shared base entity');

    Module::upgradeModuleVersion('beesblog', '1.9.0');
    echo "RESULT: all multistore integration tests passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    $exitCode = 1;
} finally {
    Shop::setContext(Shop::CONTEXT_ALL);

    foreach ($testConfigurationKeys as $configurationKey) {
        Configuration::deleteByName($configurationKey);
    }

    foreach (array_unique(array_map('intval', $createdPosts)) as $idPost) {
        $db->delete('bees_blog_post_product', '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::LANG_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::SHOP_TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
        $db->delete(BeesBlogPost::TABLE, '`'.BeesBlogPost::PRIMARY.'` = '.$idPost);
    }
    foreach (array_unique(array_map('intval', $createdCategories)) as $idCategory) {
        $db->delete(BeesBlogCategory::LANG_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
        $db->delete(BeesBlogCategory::SHOP_TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
        $db->delete(BeesBlogCategory::TABLE, '`'.BeesBlogCategory::PRIMARY.'` = '.$idCategory);
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
        $configurationIds = array_map('intval', array_column((array) $db->executeS(
            'SELECT `id_configuration` FROM `'._DB_PREFIX_.'configuration` WHERE `id_shop` = '.(int) $testShopId
        ), 'id_configuration'));
        if ($configurationIds) {
            $db->delete('configuration_lang', '`id_configuration` IN ('.implode(',', $configurationIds).')');
            $db->delete('configuration', '`id_configuration` IN ('.implode(',', $configurationIds).')');
        }
        foreach ([
            'bees_blog_post_product',
            BeesBlogPost::LANG_TABLE,
            BeesBlogPost::SHOP_TABLE,
            BeesBlogCategory::LANG_TABLE,
            BeesBlogCategory::SHOP_TABLE,
            'bees_blog_image_type_shop',
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
