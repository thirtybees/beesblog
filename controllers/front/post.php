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
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogDetailsModuleFrontController
 */
class BeesBlogPostModuleFrontController extends ModuleFrontController
{
    /**
     * @var string
     */
    public $report = '';

    /**
     * @var int $idPost
     */
    protected $idPost;

    /**
     * @var BeesBlogPost $blogPost;
     */
    protected $blogPost;

    /**
     * @var BeesBlog $module
     */
    public $module;

    /**
     * Initialize content
     *
     * @return void
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $post = $this->getBeesBlogPost();
        if (! $post) {
            Tools::redirect(BeesBlog::getBeesBlogLink());
            return;
        }

        // mark post as viewed
        BeesBlogPost::viewed($post->id);

        if (Configuration::get(BeesBlog::SOCIAL_SHARING)) {
            $this->context->controller->addCSS(_PS_MODULE_DIR_.'beesblog/views/css/socialmedia.css', 'all');
            $this->context->controller->addJS(_PS_MODULE_DIR_.'beesblog/views/js/socialmedia.js');
        }
        Media::addJsDef([
            'sharing_name' => addcslashes($post->title, "'"),
            'sharing_url' => addcslashes(Tools::getHttpHost(true).$_SERVER['REQUEST_URI'], "'"),
            'sharing_img' => addcslashes(Tools::getHttpHost(true).'/img/beesblog/posts/'.(int) $post->id.'.jpg', "'"),
        ]);

        $postProperties = [
            'meta_title'           => $post->meta_title.' - '.Configuration::get('PS_SHOP_NAME'),
            'meta_description'     => $post->meta_description,
            'meta_keywords'        => $post->meta_keywords,
            'blogHome'             => BeesBlog::getBeesBlogLink(),
            'post'                 => $post,
            'authorStyle'          => Configuration::get(BeesBlog::AUTHOR_STYLE),
            'showAuthor'           => (bool) Configuration::get(BeesBlog::SHOW_AUTHOR),
            'showDate'             => (bool) Configuration::get(BeesBlog::SHOW_DATE),
            'socialSharing'        => (bool) Configuration::get(BeesBlog::SOCIAL_SHARING) && Module::isEnabled('socialsharing'),
            'disableCategoryImage' => (bool) Configuration::get(BeesBlog::SHOW_CATEGORY_IMAGE),
            'showViewed'           => (bool) Configuration::get(BeesBlog::SHOW_POST_COUNT),
            'showNoImage'          => (bool) Configuration::get(BeesBlog::SHOW_NO_IMAGE),
            'showComments'         => (bool) Configuration::get(BeesBlog::DISQUS_USERNAME),
            'disqusUsername'       => Configuration::get(BeesBlog::DISQUS_USERNAME),
            'PS_SC_TWITTER'        => Configuration::get('PS_SC_TWITTER'),
            'PS_SC_FACEBOOK'       => Configuration::get('PS_SC_FACEBOOK'),
            'PS_SC_PINTEREST'      => Configuration::get('PS_SC_PINTEREST'),
        ];
        $postProperties = array_merge($postProperties, [
            'displayBeesBlogBeforePost' => Hook::exec('displayBeesBlogBeforePost', $postProperties),
            'displayBeesBlogAfterPost' => Hook::exec('displayBeesBlogAfterPost', $postProperties),
        ]);

        $this->context->smarty->assign($postProperties);

        $this->setTemplate('post.tpl');
    }

    /**
     * Returns blog post ID
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBeesBlogPostId()
    {
        if (is_null($this->idPost)) {
            $this->idPost = (int)BeesBlogPost::getIdByRewrite(Tools::getValue('blog_rewrite'));
        }
        return $this->idPost;
    }

    /**
     * Returns associated blog post
     *
     * @return BeesBlogPost
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getBeesBlogPost()
    {
        if (is_null($this->blogPost)) {
            $postId = $this->getBeesBlogPostId();
            if ($postId) {
                $blogPost = new BeesBlogPost($postId, $this->context->language->id);
                if (Validate::isLoadedObject($blogPost) && $blogPost->active && $blogPost->lang_active) {
                    $this->blogPost = $blogPost;
                    $this->blogPost->category = new BeesBlogCategory($this->blogPost->id_category, $this->context->language->id);
                    $this->blogPost->employee = new Employee($this->blogPost->id_employee);
                }
            }
        }
        return $this->blogPost;
    }
}
