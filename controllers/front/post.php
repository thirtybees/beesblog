<?php
/**
 * 2017 thirty bees
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
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogDetailsModuleFrontController
 */
class BeesBlogPostModuleFrontController extends \ModuleFrontController
{
    public $report = '';

    /** @var int $idPost */
    protected $idPost;

    /** @var \BeesBlog $module */
    public $module;

    /**
     * Initialize content
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();
        $this->idPost = (int) BeesBlogPost::getIdByRewrite(\Tools::getValue('blog_rewrite'));

        if (empty($this->idPost)) {
            return;
        }

        $post = new BeesBlogPost($this->idPost, $this->context->language->id);
        $category = new BeesBlogCategory($post->id_category, $this->context->language->id);
        $post->category = $category;

//        if (file_exists(_PS_MODULE_DIR_.'beesblog/images/'.(int) $this->idPost.'.jpg')
//            || file_exists(_PS_MODULE_DIR_.'beesblog/images/'.(int) $this->idPost.'.jpg')) {
//            $postImage = $this->idPost;
//        } else {
//            $postImage = 'no';
//        }

        $post->employee = new Employee($post->id_employee);

        BeesBlogPost::viewed($this->idPost);

        \Media::addJsDef([
            'sharing_name' => addcslashes($post->title, "'"),
            'sharing_url' => addcslashes(\Tools::getHttpHost(true).$_SERVER['REQUEST_URI'], "'"),
            'sharing_img' => addcslashes(\Tools::getHttpHost(true).'/modules/beesblog/images/'.(int) $post->id.'.jpg', "'"),
        ]);

        if (Configuration::get(BeesBlog::SOCIAL_SHARING)) {
            if (file_exists(_PS_ROOT_DIR_._THEME_CSS_DIR_.'modules/socialsharing/socialsharing.css')) {
                $this->context->controller->addCSS(_PS_ROOT_DIR_._THEME_CSS_DIR_.'modules/socialsharing/socialsharing.css', 'all');
            } else {
                $this->context->controller->addCSS(_PS_MODULE_DIR_.'socialsharing/views/css/socialsharing.css', 'all');
            }
        }

        $postProperties = [
            'blogHome'             => \BeesBlog::getBeesBlogLink(),
            'post'                 => $post,
            'authorStyle'          => Configuration::get(BeesBlog::AUTHOR_STYLE),
            'showAuthor'           => (bool) Configuration::get(BeesBlog::SHOW_AUTHOR),
            'showDate'             => (bool) Configuration::get(BeesBlog::SHOW_DATE),
            'socialSharing'        => (bool) Configuration::get(BeesBlog::SOCIAL_SHARING) && Module::isEnabled('socialsharing'),
            'customCss'            => (bool) Configuration::get(BeesBlog::CUSTOM_CSS),
            'disableCategoryImage' => (bool) Configuration::get(BeesBlog::SHOW_CATEGORY_IMAGE),
            'showViewed'           => (bool) Configuration::get(BeesBlog::SHOW_POST_COUNT),
            'showNoImage'          => (bool) Configuration::get(BeesBlog::SHOW_NO_IMAGE),
            'showComments'         => (bool) Configuration::get(\BeesBlog::DISQUS_USERNAME),
            'disqusUsername'       => Configuration::get(BeesBlog::DISQUS_USERNAME),
            'PS_SC_TWITTER'        => Configuration::get('PS_SC_TWITTER'),
            'PS_SC_GOOGLE'         => Configuration::get('PS_SC_GOOGLE'),
            'PS_SC_FACEBOOK'       => Configuration::get('PS_SC_FACEBOOK'),
            'PS_SC_PINTEREST'      => Configuration::get('PS_SC_PINTEREST'),
        ];
        $postProperties = array_merge($postProperties, [
            'displayBeesBlogBeforePost' => \Hook::exec('displayBeesBlogBeforePost', $postProperties),
            'displayBeesBlogAfterPost' => \Hook::exec('displayBeesBlogAfterPost', $postProperties),
        ]);

        $this->context->smarty->assign($postProperties);

        $this->setTemplate('post.tpl');
    }
}
