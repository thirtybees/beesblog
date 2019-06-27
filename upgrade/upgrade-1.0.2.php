<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

function upgrade_module_1_0_2()
{
    $widgetDir = __DIR__.'/../widgets';

    foreach (scandir($widgetDir) as $module) {
        if (in_array($module, ['.', '..']) || !is_dir($widgetDir.'/'.$module)) {
            continue;
        }

        Tools::deleteDirectory(_PS_MODULE_DIR_.$module, true);
        Tools::recurseCopy($widgetDir."/$module/", _PS_MODULE_DIR_.$module, false);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_PS_MODULE_DIR_."$module/$module.php");
        }
    }

    return true;
}
