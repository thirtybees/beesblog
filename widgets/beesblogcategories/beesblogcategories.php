<?php

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
