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

use BeesBlogModule\BeesBlogCategory;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogCategories
 */
class BeesBlogCategories extends Module
{
    /**
     * BeesBlogCategories constructor.
     */
    public function __construct()
    {
        $this->name = 'beesblogcategories';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'thirty bees';

        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Bees Blog Categories');
        $this->description = $this->l('thirty bees blog categories widget');
        $this->dependencies  = ['beesblog'];
    }

    /**
     * Display in left column
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayLeftColumn()
    {
        if (!Module::isEnabled('beesblog')) {
            return '';
        }

        $categories = BeesBlogCategory::getCategories($this->context->language->id);
        if (is_array($categories)) {
            foreach ($categories as &$category) {
                $category->link = BeesBlog::GetBeesBlogLink('beesblog_category', ['cat_rewrite' => $category->link_rewrite]);
            }
        }

        $this->context->smarty->assign([
            'beesblogCategoriesCategories' => $categories,
            'beesblogCategoriesBlogUrl' => BeesBlog::getBeesBlogLink(),
        ]);

        return $this->display(__FILE__, 'views/templates/hooks/column.tpl');
    }

    /**
     * Display in right column
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayRightColumn()
    {
        return $this->hookDisplayLeftColumn();
    }
}
