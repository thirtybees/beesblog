<?php

use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogRecentPosts
 */
class BeesBlogRecentPosts extends Module
{
    /**
     * BeesBlogRecentPosts constructor.
     */
    public function __construct()
    {
        $this->name = 'beesblogrecentposts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';

        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Bees Blog Recent Posts');
        $this->description = $this->l('thirty bees blog recent posts widget');
        $this->dependencies  = ['beesblog'];
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayLeftColumn');
        $this->registerHook('displayHome');

        return true;
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

        $recentPosts = BeesBlogPost::getPosts($this->context->language->id, 0, 5);
        if (is_array($recentPosts)) {
            foreach ($recentPosts as &$recentPost) {
                $recentPost->link = BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $recentPost->link_rewrite]);
            }
        }

        $this->context->smarty->assign([
            'beesblogRecentPostsPosts' => $recentPosts,
            'beesblogRecentPostsBlogUrl' => BeesBlog::getBeesBlogLink(),
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
    /**
     * Display in home page
     *
     * @return string
     *
     * @since 1.0.3
     */
    public function hookDisplayHome()
    {
        if (!Module::isEnabled('beesblog')) {
            return '';
        }

        $recentPosts = BeesBlogPost::getPosts($this->context->language->id, 0, 5);
        if (is_array($recentPosts)) {
            foreach ($recentPosts as &$recentPost) {
                $recentPost->link = BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $recentPost->link_rewrite]);
            }
        }
        $this->context->smarty->assign([
            'beesblogRecentPostsPosts' => $recentPosts,
            'beesblogRecentPostsBlogUrl' => BeesBlog::getBeesBlogLink(),
        ]);

        return $this->display(__FILE__, 'views/templates/hooks/home.tpl');
    }
    /**
     * Display in product page
     *
     * @return string
     *
     * @since 1.0.3
     */
    public function hookProductFooter($params)
    {
        if (!Module::isEnabled('beesblog')) {
            return '';
        }

        $recentPosts = BeesBlogPost::getPosts($this->context->language->id, 0, 5);
        if (is_array($recentPosts)) {
            foreach ($recentPosts as &$recentPost) {
                $recentPost->link = BeesBlog::GetBeesBlogLink('beesblog_post', ['blog_rewrite' => $recentPost->link_rewrite]);
            }
        }
        $this->context->smarty->assign([
            'beesblogRecentPostsPosts' => $recentPosts,
            'beesblogRecentPostsBlogUrl' => BeesBlog::getBeesBlogLink(),
        ]);

        return $this->display(__FILE__, 'views/templates/hooks/product.tpl');
    }
}
