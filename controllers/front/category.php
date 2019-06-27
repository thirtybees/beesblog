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
 * Class BeesBlogCategoryModuleFrontController
 */
class BeesBlogCategoryModuleFrontController extends ModuleFrontController
{
    /** @var BeesBlogCategory $beesblogCategory */
    public $beesblogCategory;

    /** @var int $idCategory */
    public $idCategory;

    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        $totalPages = 0;
        $postsPerPage = Configuration::get(\BeesBlog::POSTS_PER_PAGE);
        $limit = $postsPerPage;

        $this->idCategory = BeesBlogCategory::getIdByRewrite(\Tools::getValue('cat_rewrite'));
        if ($this->idCategory) {
            $category = new BeesBlogCategory($this->idCategory, $this->context->language->id);
        } else {
            // Make a fake category if the category ID has not been given
            $category = new BeesBlogCategory();
            $category->active = true;
            $category->title = Configuration::get(BeesBlog::HOME_TITLE);
            $category->meta_title = Configuration::get(BeesBlog::HOME_TITLE);
            $category->description = Configuration::get(BeesBlog::HOME_DESCRIPTION);
            $category->meta_description = Configuration::get(BeesBlog::HOME_DESCRIPTION);
        }

        $page = (int) Tools::getValue('page');
        if ($page <= 0) {
            $page = 1;
        }

        // Check if we are not using our fake category (happens at blog homepage)
        if (Validate::isLoadedObject($category)) {
            $posts = $category->getPostsInCategory($this->context->language->id, $page, $limit);
            $totalPosts = $category->getPostsInCategory($this->context->language->id, 0, 0, true);
            $totalPostsOnThisPage = count($posts);
        } else {
            $posts = BeesBlogPost::getPosts($this->context->language->id, $page, $limit);
            $totalPosts = BeesBlogPost::getPosts($this->context->language->id, 0, 0, true);
            $totalPostsOnThisPage = count($posts);
        }
        if ($totalPosts !== 0) {
            $totalPages = ceil($totalPosts / $postsPerPage);
        }
        foreach ($posts as &$post) {
            /** @var BeesBlogModule\BeesBlogPost $post */
            $post->employee = new Employee($post->id_employee);
            $post->category = new BeesBlogCategory($post->id_category, $this->context->language->id);
        }

        $this->context->smarty->assign([
            'meta_title'           => $category->meta_title.' - '.Configuration::get('PS_SHOP_NAME'),
            'meta_description'     => $category->meta_description,
            'meta_keywords'        => $category->meta_keywords,
            'blogHome'             => BeesBlog::getBeesBlogLink(),
            'posts'                => $posts,
            'category'             => $category,
            'categoryImageUrl'     => Media::getMediaPath(BeesBlogCategory::getImagePath($category->id)),
            'authorStyle'          => (bool) Configuration::get(\BeesBlog::AUTHOR_STYLE),
            'showAuthor'           => (bool) Configuration::get(\BeesBlog::SHOW_AUTHOR),
            'showDate'             => (bool) Configuration::get(\BeesBlog::SHOW_DATE),
            'showCategoryImage'    => (bool) Configuration::get(\BeesBlog::SHOW_CATEGORY_IMAGE),
            'showViewed'           => (bool) Configuration::get(\BeesBlog::SHOW_POST_COUNT),
            'showNoImage'          => (bool) Configuration::get(\BeesBlog::SHOW_NO_IMAGE),
            'showComments'         => (bool) Configuration::get(\BeesBlog::DISQUS_USERNAME),
            'disqusUsername'       => Configuration::get(\BeesBlog::DISQUS_USERNAME),
            'start'                => (int) $start = (($page - 1) * $limit) + 1,
            'postsPerPage'         => (int) $limit,
            'totalPosts'           => (int) $totalPosts,
            'totalPostsOnThisPage' => (int) $totalPostsOnThisPage,
            'totalPages'           => (int) $totalPages,
            'pageNumber'           => (int) $page,
        ]);

        $templateName = 'category.tpl';

        $this->setTemplate($templateName);
    }
}
