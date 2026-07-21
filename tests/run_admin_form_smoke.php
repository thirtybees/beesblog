<?php
/**
 * Back Office form smoke test for context-aware image inputs.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = isset($argv[1]) ? rtrim($argv[1], '/\\') : '';
if (!$root || !is_file($root.'/config/config.inc.php')) {
    fwrite(STDERR, "Usage: php run_admin_form_smoke.php <thirty-bees-root>\n");
    exit(1);
}

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', $root.'/admin-dev');
}
require $root.'/config/config.inc.php';
require_once $root.'/modules/beesblog/beesblog.php';
require_once $root.'/modules/beesblog/controllers/admin/AdminBeesBlogPostController.php';
require_once $root.'/modules/beesblog/controllers/admin/AdminBeesBlogCategoryController.php';

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

function assertAdminForm($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

try {
    $context = Context::getContext();
    $employeeId = (int) Db::getInstance()->getValue(
        'SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` ORDER BY `id_employee` ASC'
    );
    $context->employee = new Employee($employeeId);
    Shop::setContext(Shop::CONTEXT_ALL);

    $entities = [
        [
            'class' => 'AdminBeesBlogPostController',
            'table' => BeesBlogPost::TABLE,
            'primary' => BeesBlogPost::PRIMARY,
            'default_input' => 'post_image',
            'language_prefix' => 'post_image_lang_',
            'following_input' => 'name="id_category"',
        ],
        [
            'class' => 'AdminBeesBlogCategoryController',
            'table' => BeesBlogCategory::TABLE,
            'primary' => BeesBlogCategory::PRIMARY,
            'default_input' => 'category_image',
            'language_prefix' => 'category_image_lang_',
            'following_input' => 'name="link_rewrite_',
        ],
    ];

    foreach ($entities as $entity) {
        $idObject = (int) Db::getInstance()->getValue(
            'SELECT `'.bqSQL($entity['primary']).'` FROM `'._DB_PREFIX_.bqSQL($entity['table']).'`'.
            ' ORDER BY `'.bqSQL($entity['primary']).'` ASC'
        );
        assertAdminForm($idObject > 0, $entity['class'].' has an object available for rendering');

        $_GET = [
            'controller' => $entity['class'],
            $entity['primary'] => $idObject,
        ];
        $_POST = [];
        $controller = new $entity['class']();
        $context->controller = $controller;
        $html = $controller->renderForm();
        assertAdminForm(
            strpos($html, 'name="'.$entity['default_input'].'"') !== false,
            $entity['class'].' renders the shop-context default image input'
        );
        $previousImagePosition = strpos($html, 'name="'.$entity['default_input'].'"');
        foreach (Language::getLanguages(true, false, true) as $idLang) {
            $languageInput = $entity['language_prefix'].(int) $idLang;
            $languageImagePosition = strpos($html, 'name="'.$languageInput.'"');
            assertAdminForm(
                $languageImagePosition !== false,
                $entity['class'].' renders the language '.(int) $idLang.' image override input'
            );
            assertAdminForm(
                $languageImagePosition > $previousImagePosition,
                $entity['class'].' renders language '.(int) $idLang.' after the preceding image input'
            );
            $previousImagePosition = $languageImagePosition;
        }
        $followingInputPosition = strpos($html, $entity['following_input']);
        assertAdminForm(
            $followingInputPosition !== false && $followingInputPosition > $previousImagePosition,
            $entity['class'].' keeps all language image overrides directly below the default image'
        );
    }

    echo "RESULT: Back Office image form smoke checks passed\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
    exit(1);
}
