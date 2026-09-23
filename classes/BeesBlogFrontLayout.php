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

namespace BeesBlogModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Resolves the column state used by front-office theme layouts.
 */
final class BeesBlogFrontLayout
{
    /**
     * The rendered count controls the Bootstrap column classes. Warehouse
     * additionally uses enabled-column body classes for its wide layout.
     *
     * @param \Context $context
     *
     * @return array{rendered_sidebar_count: int, enabled_sidebar_count: int}
     */
    public static function getImageLayout(\Context $context)
    {
        $smarty = $context->smarty;
        $leftColumn = trim((string) $smarty->getTemplateVars('HOOK_LEFT_COLUMN')) !== '';
        $rightColumn = trim((string) $smarty->getTemplateVars('HOOK_RIGHT_COLUMN')) !== '';
        $leftColumnEnabled = !$smarty->getTemplateVars('hide_left_column');
        $rightColumnEnabled = !$smarty->getTemplateVars('hide_right_column');

        return [
            'rendered_sidebar_count' => (int) $leftColumn + (int) $rightColumn,
            'enabled_sidebar_count' => (int) $leftColumnEnabled + (int) $rightColumnEnabled,
        ];
    }
}
