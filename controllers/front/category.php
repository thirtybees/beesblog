<?php
/**
 * 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'beesblog/classes/autoload.php';

if (!class_exists('BeesBlog')) {
    require_once _PS_MODULE_DIR_.'beesblog/beesblog.php';
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
            $category->summary = Configuration::get(BeesBlog::HOME_DESCRIPTION);
            $category->keywords = Configuration::get(BeesBlog::HOME_KEYWORDS);
        }

        $page = (int) Tools::getValue('page');

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
            $employee = new Employee($post->id_employee);
            if (Validate::isLoadedObject($employee)) {
                $post->firstname = $employee->firstname;
                $post->lastname = $employee->lastname;
            } else {
                $post->firstname = '';
                $post->lastname = '';
            }
            $post->category = new BeesBlogCategory($post->id_category, $this->context->language->id);
        }

        $this->context->smarty->assign([
            'blogHome'             => BeesBlog::getBeesBlogLink(),
            'posts'                => $posts,
            'category'             => $category,
            'authorStyle'          => Configuration::get(\BeesBlog::AUTHOR_STYLE),
            'showAuthor'           => Configuration::get(\BeesBlog::SHOW_AUTHOR),
            'customCss'            => Configuration::get(\BeesBlog::CUSTOM_CSS),
            'disableCategoryImage' => Configuration::get(\BeesBlog::DISABLE_CATEGORY_IMAGE),
            'showViewed'           => Configuration::get(\BeesBlog::SHOW_POST_COUNT),
            'showImage'            => Configuration::get(\BeesBlog::SHOW_NO_IMAGE),
            'postsPerPage'         => $limit,
            'totalPosts'           => $totalPosts,
            'totalPostsOnThisPage' => $totalPostsOnThisPage,
            'totalPages'           => $totalPages,
            'pageNumber'           => $page,
        ]);

        $templateName = 'category.tpl';

        $this->setTemplate($templateName);
    }
}
