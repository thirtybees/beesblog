<?php
/**
 * Runtime smoke checks for shop-scoped front-office loading and back-office
 * list SQL. This script is read-only.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_runtime_smoke.php <thirty-bees-root>\n");
    exit(1);
}

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', $root.'/admin-dev');
}
require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

function assertSmoke($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $context = Context::getContext();
    $idShop = (int) $context->shop->id;
    $idLang = (int) $context->language->id;

    $postRow = Db::getInstance()->getRow(
        'SELECT pl.`'.BeesBlogPost::PRIMARY.'`, pl.`link_rewrite`'.
        ' FROM `'._DB_PREFIX_.BeesBlogPost::LANG_TABLE.'` pl'.
        ' INNER JOIN `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` ps'.
        ' ON ps.`'.BeesBlogPost::PRIMARY.'` = pl.`'.BeesBlogPost::PRIMARY.'` AND ps.`id_shop` = pl.`id_shop`'.
        ' WHERE pl.`id_shop` = '.$idShop.' AND pl.`id_lang` = '.$idLang
    );
    assertSmoke((bool) $postRow, 'a migrated post translation is available');
    $post = new BeesBlogPost((int) $postRow[BeesBlogPost::PRIMARY], $idLang, $idShop);
    assertSmoke(Validate::isLoadedObject($post), 'front-office post ObjectModel loads for an explicit shop');
    assertSmoke(
        (int) BeesBlogPost::getIdByRewrite($postRow['link_rewrite'], true, $idLang, $idShop) === (int) $post->id,
        'front-office post slug lookup is shop-scoped'
    );

    $categoryRow = Db::getInstance()->getRow(
        'SELECT cl.`'.BeesBlogCategory::PRIMARY.'`, cl.`link_rewrite`'.
        ' FROM `'._DB_PREFIX_.BeesBlogCategory::LANG_TABLE.'` cl'.
        ' INNER JOIN `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'` cs'.
        ' ON cs.`'.BeesBlogCategory::PRIMARY.'` = cl.`'.BeesBlogCategory::PRIMARY.'` AND cs.`id_shop` = cl.`id_shop`'.
        ' WHERE cl.`id_shop` = '.$idShop.' AND cl.`id_lang` = '.$idLang
    );
    assertSmoke((bool) $categoryRow, 'a migrated category translation is available');
    assertSmoke(
        (int) BeesBlogCategory::getIdByRewrite($categoryRow['link_rewrite'], true, $idLang, $idShop) === (int) $categoryRow[BeesBlogCategory::PRIMARY],
        'front-office category slug lookup is shop-scoped'
    );

    $employeeId = (int) Db::getInstance()->getValue('SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` ORDER BY `id_employee` ASC');
    $context->employee = new Employee($employeeId);
    Shop::setContext(Shop::CONTEXT_ALL);

    $controllers = [
        'AdminBeesBlogPostController' => 'AdminBeesBlogPostController.php',
        'AdminBeesBlogCategoryController' => 'AdminBeesBlogCategoryController.php',
        'AdminBeesBlogImagesController' => 'AdminBeesBlogImagesController.php',
    ];
    foreach ($controllers as $class => $file) {
        require_once $root.'/modules/beesblog/controllers/admin/'.$file;
        $_GET['controller'] = $class;
        $controller = new $class();
        $controller->getList($idLang, null, null, 0, false);
        $property = new ReflectionProperty(AdminController::class, '_list_error');
        $property->setAccessible(true);
        assertSmoke(!$property->getValue($controller), $class.' list SQL executes');
    }

    echo "RESULT: runtime smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
