<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImageType;
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/../classes/autoload.php';

function upgrade_module_2_0_0()
{
    $queries = [];
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` MODIFY content TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` MODIFY link_rewrite VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` MODIFY title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` MODIFY description VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` MODIFY link_rewrite VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` ADD meta_title VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` ADD meta_description VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogPost::LANG_TABLE).'` ADD meta_keywords VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` ADD meta_title VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` ADD meta_description VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    $queries[] = 'ALTER TABLE `'._DB_PREFIX_.bqSQL(BeesBlogCategory::LANG_TABLE).'` ADD meta_keywords VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

    foreach ($queries as $sql) {
        try {
            Db::getInstance()->execute($sql);
        } catch (Exception $e) {
        }
    }

    return true;
}
