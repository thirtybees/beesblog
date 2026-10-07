<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Front office lists are now restricted to the current shop, like the
 * post and category pages already were. Posts and categories without any
 * shop association used to appear in every list (with a broken detail
 * page); link them to all shops so they stay visible after this upgrade.
 *
 * Records that already have at least one shop association are left alone,
 * so deliberate per-shop selections are preserved.
 *
 * @param BeesBlog $module
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_1_8_1($module)
{
    $entities = [
        BeesBlogPost::class     => [BeesBlogPost::TABLE, BeesBlogPost::SHOP_TABLE, BeesBlogPost::PRIMARY],
        BeesBlogCategory::class => [BeesBlogCategory::TABLE, BeesBlogCategory::SHOP_TABLE, BeesBlogCategory::PRIMARY],
    ];

    foreach ($entities as $className => list($table, $shopTable, $primary)) {
        $sql = 'INSERT INTO `'._DB_PREFIX_.$shopTable.'` (`'.$primary.'`, `id_shop`)
                SELECT e.`'.$primary.'`, s.`id_shop`
                FROM `'._DB_PREFIX_.$table.'` e
                CROSS JOIN `'._DB_PREFIX_.'shop` s
                WHERE s.`deleted` = 0
                AND NOT EXISTS (
                    SELECT 1 FROM `'._DB_PREFIX_.$shopTable.'` es
                    WHERE es.`'.$primary.'` = e.`'.$primary.'`
                )';

        if (!Db::getInstance()->execute($sql)) {
            throw new PrestaShopException(
                'beesblog upgrade 1.8.1: could not link '.$className.' records without shop association: '.Db::getInstance()->getMsgError()
            );
        }
    }

    // cached blog lists may still contain posts of other shops
    if (class_exists('PageCache')) {
        PageCache::flush();
    }

    return true;
}
