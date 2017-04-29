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
use BeesBlogModule\BeesBlogPostCategory;

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

        $configuration = \Configuration::getMultiple([
            \BeesBlog::POSTS_PER_PAGE,
            \BeesBlog::SHOW_AUTHOR,
            \BeesBlog::AUTHOR_STYLE,
            \BeesBlog::CUSTOM_CSS,
            \BeesBlog::SHOW_NO_IMAGE,
            \BeesBlog::DISABLE_CATEGORY_IMAGE,
            \BeesBlog::SHOW_POST_COUNT,
        ]);

        $categoryStatus = '';
        $totalPages = 0;
        $categoryImage = 'no';
        $categoryinfo = '';
        $titleCategory = '';
        $categoryLinkRewrite = '';
        $blogPost = new BeesBlogPost();
        $blogCategory = new BeesBlogCategory();
        $postsPerPage = $configuration[\BeesBlog::POSTS_PER_PAGE];
        $limitStart = 0;
        $limit = $postsPerPage;

        if (!$this->idCategory = BeesBlogCategory::getIdByRewrite(\Tools::getValue('cat_rewrite'))) {
            $total = $blogPost->getPostCount($this->context->language->id);
        } else {
            $total = $blogPost->getPostCountByCategory($this->context->language->id, $this->idCategory);
            \Hook::exec('actionsbcat', ['id_category' => (int) $this->idCategory]);
        }

        if ($total != 0) {
            $totalPages = ceil($total / $postsPerPage);
        }

        if ((bool) \Tools::getValue('page')) {
            $c = \Tools::getValue('page');
            $limitStart = $postsPerPage * ($c - 1);
        }
        if (!$this->idCategory) {
            $allNews = $blogPost->getAllPosts($this->context->language->id, $limitStart, $limit);
        } else {
            if (file_exists(_PS_MODULE_DIR_.'beesblog/images/category/'.$this->idCategory.'.jpg')
                || file_exists(_PS_MODULE_DIR_.'beesblog/images/category/'.$this->idCategory.'.png')) {
                $categoryImage = $this->idCategory;
            } else {
                $categoryImage = 'no';
            }
            $categoryinfo = $blogCategory->getNameCategory($this->idCategory);
            $titleCategory = $categoryinfo[0]['meta_title'];
            $categoryStatus = $categoryinfo[0]['active'];
            $categoryLinkRewrite = $categoryinfo[0]['link_rewrite'];
            $allNews = '';
        }

        // FIXME: $allNews might not have been defined
        // TODO: Change title separator
        $this->context->smarty->assign(
            [
                'postcategory'        => isset($allNews) ? $allNews : '',
                'category_status'     => $categoryStatus,
                'title_category'      => $titleCategory,
                'meta_title'          => (empty($titleCategory) ? 'Blog' : $titleCategory).' • '.\Configuration::get('PS_SHOP_NAME'),
                'cat_link_rewrite'    => $categoryLinkRewrite,
                'id_category'         => $this->idCategory,
                'cat_image'           => $categoryImage,
                'categoryinfo'        => $categoryinfo,
                'beesshowauthorstyle' => $configuration[\BeesBlog::AUTHOR_STYLE],
                'beesshowauthor'      => $configuration[\BeesBlog::SHOW_AUTHOR],
                'limit'               => isset($limit) ? $limit : 0,
                'limit_start'         => isset($limitStart) ? $limitStart : 0,
                'c'                   => isset($c) ? $c : 1,
                'total'               => $total,
                'beesblogliststyle'   => \Configuration::get('beesblogliststyle'),
                'beescustomcss'       => $configuration[\BeesBlog::CUSTOM_CSS],
                'beesshownoimg'       => $configuration[\BeesBlog::SHOW_NO_IMAGE],
                'beesdisablecatimg'   => $configuration[\BeesBlog::DISABLE_CATEGORY_IMAGE],
                'beesshowviewed'      => $configuration[\BeesBlog::SHOW_POST_COUNT],
                'post_per_page'       => $postsPerPage,
                'pagenums'            => $totalPages - 1,
                'totalpages'          => $totalPages,
            ]
        );

        $templateName = 'postcategory.tpl';

        $this->setTemplate($templateName);
    }
}
