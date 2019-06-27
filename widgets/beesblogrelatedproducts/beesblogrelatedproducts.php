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

/**
 * Class BeesBlogRelatedProducts
 */
class BeesBlogRelatedProducts extends Module
{
    const PRODUCT_CACHE_KEY = 'BeesBlogRelatedProducts_PRODUCT_';
    const BLOG_POST_CACHE_KEY = 'BeesBlogRelatedProducts_POST_';

    /**
     * BeesBlogRelatedProducts constructor.
     */
    public function __construct()
    {
        $this->name = 'beesblogrelatedproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';

        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Bees Blog Related Products');
        $this->description = $this->l('thirty bees blog related products widget');
        $this->dependencies = ['beesblog'];
    }

    /**
     * Installs module
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        return (
            parent::install() &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayBeesBlogAfterPost')
        );
    }

    /**
     * Hook to display related blog post on product page
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayFooterProduct()
    {
        if (!Module::isEnabled('beesblog')) {
            return null;
        }
        $posts = $this->getBlogPostsForProduct((int)Tools::getValue('id_product'));
        if ($posts) {
            $this->context->smarty->assign('blog_posts', $posts);
            return $this->display(__FILE__, 'views/templates/hooks/product.tpl');
        }
    }

    /**
     * Hook to display related products on blog post page
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayBeesBlogAfterPost($data)
    {
        if (!Module::isEnabled('beesblog')) {
            return null;
        }
        $products = $this->getProductsForPost((int)$data['post']->id);
        if ($products) {
            $this->context->smarty->assign('related_products', $products);
            return $this->display(__FILE__, 'views/templates/hooks/blog_post.tpl');
        }
    }

    /**
     * Returns related blog posts for product id
     *
     * @param int $productId
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBlogPostsForProduct($productId)
    {
        $productId = (int)$productId;
        $lang = (int)Context::getContext()->language->id;
        $key = static::PRODUCT_CACHE_KEY . $productId . '_' . $lang;
        if (!Cache::isStored($key)) {
            $blogPosts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((new DbQuery())
                ->select('*')
                ->from('bees_blog_post_product', 'pp')
                ->innerJoin('bees_blog_post', 'bbp', 'bbp.id_bees_blog_post = pp.id_bees_blog_post')
                ->leftJoin('bees_blog_post_lang', 'bbpl', 'bbpl.lang_active AND bbpl.id_bees_blog_post = bbp.id_bees_blog_post AND bbpl.id_lang = '.$lang)
                ->where('pp.id_product = '.$productId)
                ->where('bbp.active')
                ->orderBy('bbp.date_add desc')
                ->limit(3)
            );
            if ($blogPosts) {
                foreach ($blogPosts as &$post) {
                    $post['link'] = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $post['link_rewrite']]);
                }
            }
            Cache::store($key, $blogPosts);
        }
        return Cache::retrieve($key);
    }

    /**
     * Returns related products for blog post id
     *
     * @param $postId
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProductsForPost($postId)
    {
        $postId = (int)$postId;
        $lang = (int)Context::getContext()->language->id;
        $key = static::BLOG_POST_CACHE_KEY . $postId . '_' . $lang;
        if (!Cache::isStored($key)) {
            $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((new DbQuery())
                ->select('pl.*, is.id_image')
                ->from('bees_blog_post_product', 'pp')
                ->innerJoin('product', 'p', 'p.id_product = pp.id_product')
                ->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.$lang.Shop::addSqlRestrictionOnLang('pl'))
                ->leftJoin('image_shop', 'is', 'is.id_product = p.`id_product` AND is.cover=1 AND is.id_shop='.(int) $this->context->shop->id)
                ->where('pp.id_bees_blog_post = '.$postId)
                ->limit(3)
            );
            if ($products) {
                foreach ($products as &$product) {
                    $product['link'] = $this->context->link->getProductLink($product['id_product']);
                    $product['image'] = $this->context->link->getImageLink($product['id_product'], $product['id_image'], 'home_default');
                }
            }
            Cache::store($key, $products);
        }
        return Cache::retrieve($key);
    }
}
