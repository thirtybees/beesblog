<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogResponsiveImage;
use BeesBlogModule\BeesBlogResponsiveImageJob;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Add responsive-image manifests and seed widths without touching old files.
 * Existing images keep rendering through their named legacy image type until
 * the merchant explicitly runs image regeneration in the Back Office.
 *
 * @param BeesBlog $module
 * @return bool
 */
function upgrade_module_1_10_0($module)
{
    return BeesBlogResponsiveImage::createDatabase()
        && BeesBlogResponsiveImageJob::createDatabase()
        && BeesBlogResponsiveImage::installConfiguration()
        && $module->registerHooks();
}
