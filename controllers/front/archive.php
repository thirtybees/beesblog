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

use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogarchiveModuleFrontController
 */
class BeesBlogarchiveModuleFrontController extends ModuleFrontController
{
    /**
     * Initialize content
     */
    public function initContent()
    {
        parent::initContent();

        $configuration = \Configuration::getMultiple([
            \BeesBlog::AUTHOR_STYLE,
            \BeesBlog::SHOW_POST_COUNT,
            \BeesBlog::SHOW_NO_IMAGE,
            \BeesBlog::SHOW_AUTHOR,
            \BeesBlog::POSTS_PER_PAGE,
        ]);

        $year = \Tools::getValue('year');
        $month = \Tools::getValue('month');
        $titleCategory = '';
        $postsPerPage = $configuration[\BeesBlog::POSTS_PER_PAGE];
        $limitStart = 0;
        $limit = $postsPerPage;
        if ((bool) \Tools::getValue('page')) {
            $c = (int) \Tools::getValue('page');
            $limitStart = $postsPerPage * ($c - 1);
        }
        $result = BeesBlogPost::getArchiveResult($month, $year, $limitStart, $limit);
        $total = count($result);
        $totalpages = ceil($total / $postsPerPage);

        $this->context->smarty->assign(
            [
                'postcategory'        => $result,
                'title_category'      => $titleCategory,
                'beesshowauthorstyle' => $configuration[\BeesBlog::AUTHOR_STYLE],
                'limit'               => isset($limit) ? $limit : 0,
                'limit_start'         => isset($limitStart) ? $limitStart : 0,
                'c'                   => isset($c) ? $c : 1,
                'total'               => $total,
                'beesshowviewed'      => $configuration[\BeesBlog::SHOW_POST_COUNT],
                'beescustomcss'       => $configuration[\BeesBlog::CUSTOM_CSS],
                'beesshownoimg'       => $configuration[\BeesBlog::SHOW_NO_IMAGE],
                'beesshowauthor'      => $configuration[\BeesBlog::SHOW_AUTHOR],
                'post_per_page'       => $postsPerPage,
                'pagenums'            => $totalpages - 1,
                'beesblogliststyle'   => \Configuration::get('beesblogliststyle'),
                'totalpages'          => $totalpages,
            ]
        );

        $templateName = 'archivecategory.tpl';
        $this->setTemplate($templateName);
    }
}
