<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogMultistore;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * @param BeesBlog $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_9_0($module)
{
    return BeesBlogMultistore::migrateSchema() && $module->registerHooks();
}
