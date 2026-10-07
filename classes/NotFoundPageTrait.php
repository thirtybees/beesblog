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

use Controller;
use FrontController;
use Meta;
use PrestaShopDatabaseException;
use PrestaShopException;
use SmartyException;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Renders the theme's "page not found" page for blog URLs that do not
 * resolve in the current shop and language.
 *
 * Mirrors core PageNotFoundController: a real 404 status lets search
 * engines drop dead URLs, where the previous redirect to the blog home
 * page looked like a soft 404.
 */
trait NotFoundPageTrait
{
    /**
     * @var bool
     */
    protected $notFound = false;

    /**
     * Must be called from initContent(), after parent::initContent(), so the
     * meta tags and page name assigned here are not overwritten
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function displayNotFound()
    {
        $this->notFound = true;

        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');

        $this->context->smarty->assign(Meta::getHomeMetas((int) $this->context->language->id, 'pagenotfound'));
        $this->context->smarty->assign('page_name', 'pagenotfound');

        // ModuleFrontController::setTemplate() only resolves module
        // templates; the FrontController variant also applies theme
        // overrides and restores a missing template, like core's 404 page
        FrontController::setTemplate(_PS_THEME_DIR_.'404.tpl');
    }

    /**
     * Keeps 404 responses out of the full page cache. Blog category routes
     * are cacheable by default, and a cached 404 would be served later with
     * status 200 while every random URL added a cache entry.
     *
     * @param array|string $content
     *
     * @return void
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function smartyOutputContent($content)
    {
        if ($this->notFound) {
            Controller::smartyOutputContent($content);
            return;
        }
        parent::smartyOutputContent($content);
    }
}
