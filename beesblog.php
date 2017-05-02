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
 * @author    Thirty Bees <modules@thirtybees.com>
 * @copyright 2017 Thirty Bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;
use BeesBlogModule\BeesBlogTag;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/autoload.php';

/**
 * Class BeesBlog
 */
class BeesBlog extends Module
{
    const POST_IMG_DIR = 'beesblog/p/';
    const CATEGORY_IMG_DIR = 'beesblog/c/';

    const POSTS_PER_PAGE = 'BEESBLOG_POSTS_PER_PAGE';
    const AUTHOR_STYLE = 'BEESBLOG_SHOW_AUTHOR_STYLE';
    const MAIN_URL_KEY = 'BEESBLOG_MAIN_URL_KEY';
    const USE_HTML = 'BEESBLOG_USE_HTML';
    const ENABLE_COMMENT = 'BEESBLOG_ENABLE_COMMENT';
    const SHOW_AUTHOR = 'BEESBLOG_SHOW_AUTHOR';
    const SHOW_POST_COUNT = 'BEESBLOG_SHOW_VIEWED';
    const SHOW_NO_IMAGE = 'BEESBLOG_SHOW_NO_IMAGE';
    const SHOW_COLUMN = 'BEESBLOG_SHOW_COLUMN';
    const CUSTOM_CSS = 'BEESBLOG_CUSTOM_CSS';
    const DISABLE_CATEGORY_IMAGE = 'BEESBLOG_DISABLE_CATEGORY_IMAGE';
    const META_TITLE = 'BEESBLOG_META_TITLE';
    const META_KEYWORDS = 'BEESBLOG_META_KEYWORDS';
    const META_DESCRIPTION = 'BEESBLOG_META_DESCRIPTION';
    const DISQUS_USERNAME = 'BEESBLOG_DISQUS_USERNAME';
    const BLOG_REWRITE = 'blog_rewrite';
    const MAX_POSTS_PER_PAGE = 20;
    const MAX_CATEGORIES_PER_PAGE = 20;

    public $blogHooks = [
        [
            'name'        => 'displayBeesBlogBeforePost',
            'title'       => 'displayBeesBlogBeforePost',
            'description' => 'Display before a blog post on the Bees blog',
            'position'    => 1,
            'live_edit'   => 0,
        ],
        [
            'name'        => 'displayBeesBlogAfterPost',
            'title'       => 'displayBeesBlogAfterPost',
            'description' => 'Display after a blog post on the Bees blog',
            'position'    => 1,
            'live_edit'   => 0,
        ],
    ];
    protected $beesShopId;
    protected $secureKey;
    protected $fieldsForm;

    /**
     * BeesBlog constructor.
     */
    public function __construct()
    {
        $this->name = 'beesblog';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';

        $this->controllers = ['archive', 'category', 'details', 'search', 'tagpost'];
        $this->secureKey = Tools::encrypt($this->name);
        $this->beesShopId = Context::getContext()->shop->id;
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('Bees Blog');
        $this->description = $this->l('Thirty Bees blog module');
    }

    /**
     * Install this module
     *
     * @return bool Whether the module has been successfully installed
     */
    public function install()
    {
        Configuration::updateGlobalValue(self::POSTS_PER_PAGE, '5');
        Configuration::updateGlobalValue(self::SHOW_AUTHOR, '1');
        Configuration::updateGlobalValue(self::AUTHOR_STYLE, '1');
        Configuration::updateGlobalValue(self::MAIN_URL_KEY, 'blog');
        Configuration::updateGlobalValue(self::USE_HTML, '1');
        Configuration::updateGlobalValue(self::SHOW_POST_COUNT, '1');

        Configuration::updateGlobalValue(self::SHOW_NO_IMAGE, '1');
        Configuration::updateGlobalValue(self::SHOW_COLUMN, '3');
        Configuration::updateGlobalValue(self::CUSTOM_CSS, '');
        Configuration::updateGlobalValue(self::DISABLE_CATEGORY_IMAGE, '1');
        Configuration::updateGlobalValue(self::META_TITLE, 'Bees blog title');
        Configuration::updateGlobalValue(self::META_KEYWORDS, 'bees,blog,thirty bees blog,thirty bees,blog');
        Configuration::updateGlobalValue(self::META_DESCRIPTION, 'The beesiest blog for thirty bees');

        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('moduleRoutes')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->insertBlogHooks()
        ) {
            return false;
        }

        if (!(BeesBlogPost::createDatabase()
            && BeesBlogCategory::createDatabase()
            && BeesBlogTag::createDatabase())
        ) {
            return false;
        }

        $this->createBeesBlogTabs();

        return true;
    }

    /**
     * @return bool
     */
    public function insertBlogHooks()
    {
        foreach ($this->blogHooks as $hook) {
            $hookId = Hook::getIdByName($hook['name']);
            if (!$hookId) {
                $addHook = new Hook();
                $addHook->name = pSQL($hook['name']);
                $addHook->title = pSQL($hook['title']);
                $addHook->description = pSQL($hook['description']);
                $addHook->position = pSQL($hook['position']);
                $addHook->live_edit = $hook['live_edit'];
                $addHook->add();
                $hookId = $addHook->id;
                if (!$hookId) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create Bees blog tabs
     *
     * @return bool Whether the tabs have been successfully added
     */
    protected function createBeesBlogTabs()
    {
        $langs = Language::getLanguages();
        $beestab = new Tab();
        $beestab->class_name = 'AdminBeesBlog';
        $beestab->module = '';
        $beestab->id_parent = 0;
        foreach ($langs as $l) {
            $beestab->name[$l['id_lang']] = $this->l('Blog');
        }
        $beestab->save();

        $tabs = [
            [
                'class_name' => 'AdminBeesBlogPost',
                'id_parent'  => $beestab->id,
                'module'     => $this->name,
                'name'       => 'Posts',
            ],
            [
                'class_name' => 'AdminBeesBlogCategory',
                'id_parent'  => $beestab->id,
                'module'     => $this->name,
                'name'       => 'Categories',
            ],
            [
                'class_name' => 'AdminBeesBlogImageType',
                'id_parent'  => $beestab->id,
                'module'     => $this->name,
                'name'       => 'Image Types',
            ],
        ];

        foreach ($tabs as $tab) {
            $newtab = new Tab();
            $newtab->class_name = $tab['class_name'];
            $newtab->id_parent = $tab['id_parent'];
            $newtab->module = $tab['module'];
            foreach ($langs as $l) {
                $newtab->name[$l['id_lang']] = $this->l($tab['name']);
            }
            $newtab->save();
        }

        return true;
    }

    /**
     * Uninstall this module
     *
     * @return bool Whether the module has been successfully uninstalled
     */
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName(self::META_TITLE) ||
            !Configuration::deleteByName(self::META_KEYWORDS) ||
            !Configuration::deleteByName(self::META_DESCRIPTION) ||
            !Configuration::deleteByName(self::POSTS_PER_PAGE) ||
            !Configuration::deleteByName(self::USE_HTML) ||
            !Configuration::deleteByName(self::SHOW_POST_COUNT) ||
            !Configuration::deleteByName(self::DISABLE_CATEGORY_IMAGE) ||
            !Configuration::deleteByName(self::MAIN_URL_KEY) ||
            !Configuration::deleteByName(self::SHOW_COLUMN) ||
            !Configuration::deleteByName(self::AUTHOR_STYLE) ||
            !Configuration::deleteByName(self::CUSTOM_CSS) ||
            !Configuration::deleteByName(self::SHOW_NO_IMAGE) ||
            !Configuration::deleteByName(self::SHOW_AUTHOR)
        ) {
            return false;
        }

        $idtabs = [
            Tab::getIdFromClassName('AdminBeesBlog'),
            Tab::getIdFromClassName('AdminBlogPost'),
            Tab::getIdFromClassName('AdminBlogCategory'),
            Tab::getIdFromClassName('AdminImageType'),
        ];
        foreach ($idtabs as $tabid) {
            if ($tabid) {
                $tab = new Tab($tabid);
                $tab->delete();
            }
        }

        if (!(BeesBlogPost::dropDatabase()
            && BeesBlogCategory::dropDatabase()
            && BeesBlogTag::dropDatabase())
        ) {
            return false;
        }

        $this->deleteBlogHooks();

        return true;
    }

    /**
     * Delete blog hooks
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function deleteBlogHooks()
    {
        foreach ($this->blogHooks as $hkv) {
            $hookid = Hook::getIdByName($hkv['name']);
            if ($hookid) {
                $dltHook = new Hook($hookid);
                $dltHook->delete();
            }
        }
    }

    /**
     * Register the module routes
     *
     * @return array Array with routes
     */
    public function hookModuleRoutes()
    {
        $alias = Configuration::get(self::MAIN_URL_KEY);

        return [
            'beesblog'                     => [
                'controller' => 'category',
                'rule'       => $alias,
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_list'                => [
                'controller' => 'category',
                'rule'       => $alias.'/cat',
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_list_module'         => [
                'controller' => 'category',
                'rule'       => 'module/'.$alias.'/category',
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_list_pagination'     => [
                'controller' => 'category',
                'rule'       => $alias.'/cat/page/{page}',
                'keywords'   => [
                    'page' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_pagination'          => [
                'controller' => 'category',
                'rule'       => $alias.'/page/{page}',
                'keywords'   => [
                    'page' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_category'            => [
                'controller' => 'category',
                'rule'       => $alias.'/cat/{cat_rewrite}',
                'keywords'   => [
                    'cat_rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'cat_rewrite'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_category_pagination' => [
                'controller' => 'category',
                'rule'       => $alias.'/cat/{cat_rewrite}/page/{page}',
                'keywords'   => [
                    'page'        => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                    'cat_rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'cat_rewrite'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_cat_page_mod'        => [
                'controller' => 'category',
                'rule'       => 'module/'.$alias.'/cat/{blog_rewrite}/page/{page}',
                'keywords'   => [
                    'page'         => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                    'blog_rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]*'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_post'                => [
                'controller' => 'details',
                'rule'       => $alias.'/{blog_rewrite}',
                'keywords'   => [
                    'blog_rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]+', 'param' => 'blog_rewrite'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_tag'                 => [
                'controller' => 'tagpost',
                'rule'       => $alias.'/tag/{tag}',
                'keywords'   => [
                    'tag' => ['regexp' => '[_a-zA-Z0-9-\pL\+\s\-]*', 'param' => 'tag'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_search_pagination'   => [
                'controller' => 'search',
                'rule'       => $alias.'/search/page/{page}',
                'keywords'   => [
                    'page' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_archive'             => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive',
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_archive_pagination'  => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive/page/{page}',
                'keywords'   => [
                    'page' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_month'               => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive/{year}/{month}',
                'keywords'   => [
                    'year'  => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'year'],
                    'month' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'month'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_month_pagination'    => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive/{year}/{month}/page/{page}',
                'keywords'   => [
                    'year'  => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'year'],
                    'month' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'month'],
                    'page'  => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_year'                => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive/{year}',
                'keywords'   => [
                    'year' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'year'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_year_pagination'     => [
                'controller' => 'archive',
                'rule'       => $alias.'/archive/{year}/page/{page}',
                'keywords'   => [
                    'year' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'year'],
                    'page' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'page'],
                ],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
        ];
    }

    /**
     * Add links to Google Sitemap
     * Hook provided by gsitemap module
     *
     * @param array $params Hook parameters
     *
     * @return array Sitemap links
     */
    public function hookGSitemapAppendUrls()
    {
        // Blog posts
        $results = BeesBlogPost::getAllPosts();

        $links = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $link = [];
                $link['link'] = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $result['link_rewrite']]);
                $link['lastmod'] = $result['modified'];
                $link['type'] = 'module';

                if (file_exists(self::POST_IMG_DIR.(int) $result[BeesBlogPost::PRIMARY].'.jpg')) {
                    $link['image'] = ['link' => Media::getMediaPath(self::POST_IMG_DIR.(int) $result[BeesBlogPost::PRIMARY].'.jpg')];
                } elseif (file_exists(self::POST_IMG_DIR.(int) $result[BeesBlogPost::PRIMARY].'.png')) {
                    $link['image'] = ['link' => Media::getMediaPath(self::POST_IMG_DIR.(int) $result[BeesBlogPost::PRIMARY].'.png')];
                }

                $links[] = $link;
            }
        }

        // Categories
        $results = BeesBlogCategory::getAllCategories();

        if (!empty($results)) {
            foreach ($results as $result) {
                $link = [];
                $link['link'] = BeesBlog::getBeesBlogLink('beesblog_category', ['cat_rewrite' => $result['link_rewrite']]);
                $link['lastmod'] = $result['modified'];
                $link['type'] = 'module';

                if (file_exists(self::CATEGORY_IMG_DIR.(int) $result[BeesBlogCategory::PRIMARY].'.jpg')) {
                    $link['image'] = ['link' => Media::getMediaPath(self::CATEGORY_IMG_DIR.(int) $result[BeesBlogCategory::PRIMARY].'.jpg')];
                } elseif (file_exists(self::CATEGORY_IMG_DIR.(int) $result[BeesBlogCategory::PRIMARY].'.png')) {
                    $link['image'] = ['link' => Media::getMediaPath(self::CATEGORY_IMG_DIR.(int) $result[BeesBlogCategory::PRIMARY].'.png')];
                }

                $links[] = $link;
            }
        }

        return $links;
    }

    /**
     * Get link to BeesBlog item
     *
     * @param string $rewrite Rewrite
     * @param array  $params  Parameters
     * @param int    $idShop  Shop ID
     * @param int    $idLang  Language ID
     *
     * @return string URL to item
     * @throws PrestaShopException
     */
    public static function getBeesBlogLink($rewrite = null, $params = [], $idShop = null, $idLang = null)
    {
        if (!$rewrite) {
            $rewrite = Configuration::get(self::MAIN_URL_KEY);
        }

        $url = BeesBlog::getBeesBlogUrl();
        $dispatcher = Dispatcher::getInstance();

        return $url.$dispatcher->createUrl($rewrite, $idLang, $params, false, '', $idShop);
    }

    /**
     * @return string
     */
    public static function getBeesBlogUrl()
    {
        $sslEnabled = Configuration::get('PS_SSL_ENABLED');
        $idLang = (int) Context::getContext()->language->id;
        $idShop = (int) Context::getContext()->shop->id;
        $rewriteSet = (int) Configuration::get('PS_REWRITING_SETTINGS');
        $ssl = null;
        static $forceSsl = null;
        if ($ssl === null) {
            if ($forceSsl === null) {
                $forceSsl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }
            $ssl = $forceSsl;
        }
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $idShop !== null) {
            $shop = new Shop($idShop);
        } else {
            $shop = Context::getContext()->shop;
        }
        $base = (($ssl && $sslEnabled) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);
        $langUrl = Language::getIsoById($idLang).'/';
        if ((!$rewriteSet && in_array($idShop, [(int) Context::getContext()->shop->id, null]))
            || !Language::isMultiLanguageActivated($idShop)
            || !(int) Configuration::get('PS_REWRITING_SETTINGS', null, null, $idShop)
        ) {
            $langUrl = '';
        }

        return $base.$shop->getBaseURI().$langUrl;
    }

    /**
     * Hook display header
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/beesblogstyle.css', 'all');
    }

    /**
     * Get module configuration page
     *
     * @return string HTML
     */
    public function getContent()
    {
        $this->postProcess();
        $html = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue(self::META_TITLE, Tools::getValue(self::META_TITLE));
            Configuration::updateValue(self::META_KEYWORDS, Tools::getValue(self::META_KEYWORDS));
            Configuration::updateValue(self::META_DESCRIPTION, Tools::getValue(self::META_DESCRIPTION));
            Configuration::updateValue(self::POSTS_PER_PAGE, Tools::getValue(self::POSTS_PER_PAGE));
            Configuration::updateValue(self::SHOW_POST_COUNT, Tools::getValue(self::SHOW_POST_COUNT));
            Configuration::updateValue(self::DISABLE_CATEGORY_IMAGE, Tools::getValue(self::DISABLE_CATEGORY_IMAGE));
            Configuration::updateValue(self::SHOW_AUTHOR, Tools::getValue(self::SHOW_AUTHOR));
            Configuration::updateValue(self::AUTHOR_STYLE, Tools::getValue(self::AUTHOR_STYLE));
            Configuration::updateValue(self::SHOW_COLUMN, Tools::getValue(self::SHOW_COLUMN));
            Configuration::updateValue(self::MAIN_URL_KEY, Tools::getValue(self::MAIN_URL_KEY));
            Configuration::updateValue(self::USE_HTML, Tools::getValue(self::USE_HTML));
            Configuration::updateValue(self::SHOW_NO_IMAGE, Tools::getValue(self::SHOW_NO_IMAGE));
            Configuration::updateValue(self::CUSTOM_CSS, Tools::getValue(self::CUSTOM_CSS), true);
            $html = $this->displayConfirmation($this->l('The settings have been updated successfully.'));
            $helper = $this->getSettingsFormHelper();
            $html .= $helper->generateForm($this->fieldsForm);

            return $html;
        } else {
            $helper = $this->getSettingsFormHelper();
            $html .= $helper->generateForm($this->fieldsForm);
            $html .= $this->renderDisqusOptions();

            return $html;
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';
        if (Tools::isSubmit('submitOptionsconfiguration') || Tools::isSubmit('submitOptions')) {
            $output .= $this->postProcessDisqusOptions();
        }

        return $output;
    }

    /**
     * Process General Options
     */
    protected function postProcessDisqusOptions()
    {
        $idShop = (int) $this->context->shop->id;

        $username = Tools::getValue(self::DISQUS_USERNAME);

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            if (Shop::getContext() == Shop::CONTEXT_ALL) {
                $this->updateAllValue(self::DISQUS_USERNAME, $username);
            } elseif (is_array(Tools::getValue('multishopOverrideOption'))) {
                $idShopGroup = (int) Shop::getGroupFromShop($idShop, true);
                $multishopOverride = Tools::getValue('multishopOverrideOption');
                if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                    foreach (Shop::getShops(false, $idShop) as $idShop) {
                        if ($multishopOverride[self::DISQUS_USERNAME]) {
                            Configuration::updateValue(self::DISQUS_USERNAME, $username, false, $idShopGroup, $idShop);
                        }
                    }
                } else {
                    $idShop = (int) $idShop;
                    if ($multishopOverride[self::DISQUS_USERNAME]) {
                        Configuration::updateValue(self::DISQUS_USERNAME, $username, false, $idShopGroup, $idShop);
                    }
                }
            }
        } else {
            Configuration::updateValue(self::DISQUS_USERNAME, $username);
        }
    }

    /**
     * Update configuration value in ALL contexts
     *
     * @param string $key    Configuration key
     * @param mixed  $values Configuration values, can be string or array with id_lang as key
     * @param bool   $html   Contains HTML
     */
    public function updateAllValue($key, $values, $html = false)
    {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue($key, $values, $html, $shop['id_shop_group'], $shop['id_shop']);
        }
        Configuration::updateGlobalValue($key, $values, $html);
    }

    /**
     * @return HelperForm
     */
    public function getSettingsFormHelper()
    {
        $postsPerPage = [];
        for ($i = 1; $i < self::MAX_POSTS_PER_PAGE; $i++) {
            $postsPerPage[] = [
                'id'    => $i,
                'value' => $i,
                'name'  => $i,
            ];
        }
        $categoriesPerPage = [];
        for ($i = 1; $i < self::MAX_CATEGORIES_PER_PAGE; $i++) {
            $categoriesPerPage[] = [
                'id'    => $i,
                'value' => $i,
                'name'  => $i,
            ];
        }


        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Setting'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Meta title'),
                    'name'     => self::META_TITLE,
                    'size'     => 70,
                    'required' => true,
                ],
                [
                    'type'     => 'tags',
                    'label'    => $this->l('Meta keywords'),
                    'name'     => self::META_KEYWORDS,
                    'size'     => 70,
                    'required' => true,
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Meta Description'),
                    'name'     => self::META_DESCRIPTION,
                    'rows'     => 7,
                    'cols'     => 66,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Blog url key'),
                    'name'     => self::MAIN_URL_KEY,
                    'size'     => 15,
                    'required' => true,
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Number of posts per page'),
                    'name'     => self::POSTS_PER_PAGE,
                    'required' => true,
                    'options' => [
                        'query' => $categoriesPerPage,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Show author name'),
                    'name'     => self::SHOW_AUTHOR,
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Show post count'),
                    'name'     => self::SHOW_POST_COUNT,
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Author name style'),
                    'name'     => self::AUTHOR_STYLE,
                    'required' => false,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('First Name, Last Name'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Last Name, First Name'),
                        ],
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Show category image'),
                    'name'     => self::DISABLE_CATEGORY_IMAGE,
                    'required' => false,
                    'class'    => 't',
                    'desc'     => 'Show category image and description on every page',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = [
                'id_lang'    => $lang['id_lang'],
                'iso_code'   => $lang['iso_code'],
                'name'       => $lang['name'],
                'is_default' => ($defaultLang == $lang['id_lang'] ? 1 : 0),
            ];
        }

        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'token='.Tools::getAdminTokenLite('AdminModules'),
            ],
        ];

        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;

        $helper->fields_value = $this->getFormValues();

        return $helper;
    }

    /**
     * Get form values
     *
     * @return array Form values
     */
    protected function getFormValues()
    {
        $configuration = Configuration::getMultiple(
            [
                self::POSTS_PER_PAGE,
                self::SHOW_AUTHOR,
                self::AUTHOR_STYLE,
                self::MAIN_URL_KEY,
                self::USE_HTML,
                self::SHOW_COLUMN,
                self::META_TITLE,
                self::META_KEYWORDS,
                self::META_DESCRIPTION,
                self::SHOW_POST_COUNT,
                self::SHOW_POST_COUNT,
                self::DISABLE_CATEGORY_IMAGE,
                self::CUSTOM_CSS,
                self::SHOW_NO_IMAGE,
            ]
        );

        return $configuration;
    }

    /**
     * Render the General options form
     *
     * @return string HTML
     */
    protected function renderDisqusOptions()
    {
        $helper = new HelperOptions();
        $helper->id = 1;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;

        return $helper->generateOptions(array_merge($this->getDisqusOptions()));
    }

    /**
     * Get available general options
     *
     * @return array General options
     */
    protected function getDisqusOptions()
    {
        return [
            'locales' => [
                'title'  => $this->l('Comment section'),
                'icon'   => 'icon-server',
                'fields' => [
                    self::DISQUS_USERNAME => [
                        'title'      => $this->l('Disqus username'),
                        'type'       => 'text',
                        'name'       => self::DISQUS_USERNAME,
                        'value'      => Configuration::get(self::DISQUS_USERNAME),
                        'validation' => 'isString',
                        'cast'       => 'strval',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'button',
                ],
            ],
        ];
    }

    /**
     * Generate images
     */
    public static function generateBlogImage()
    {
        $getBlogImage = BeesBlogPost::getBlogImage();
        $getCategoryImage = BeesBlogCategory::getCatImage();
        $categoryTypes = static::getImagesTypes('blog_category');
        $postTypes = BeesBlogImageType::getImagesTypes('blog_post');

        foreach ($categoryTypes as $imageType) {
            foreach ($getCategoryImage as $categoryImage) {
                $path = _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'.jpg';
                \ImageManager::resize(
                    $path,
                    _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'-'.stripslashes($imageType['type_name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
            }
        }
        foreach ($postTypes as $imageType) {
            foreach ($getBlogImage as $blogImage) {
                $path = _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'.jpg';
                \ImageManager::resize(
                    $path,
                    _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'-'.stripslashes($imageType['type_name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                );
            }
        }
    }

    /**
     * Delete images
     */
    public static function deleteBlogImage()
    {
        $getBlogImage = BeesBlogPost::getBlogImage();
        $getCategoryImage = BeesBlogCategory::getCatImage();
        $categoryTypes = ImageType::getImagesTypes('beesblog_category');
        $postTypes = ImageType::getImagesTypes('beesblog_post');
        foreach ($categoryTypes as $imageType) {
            foreach ($getCategoryImage as $categoryImage) {
                $dir = _PS_IMG_DIR_.\BeesBlog::CATEGORY_IMG_DIR.$categoryImage['id_bees_blog_category'].'-'.stripslashes($imageType['type_name']).'.jpg';
                if (file_exists($dir)) {
                    unlink($dir);
                }
            }
        }
        foreach ($postTypes as $imageType) {
            foreach ($getBlogImage as $blogImage) {
                $dir = _PS_IMG_DIR_.\BeesBlog::POST_IMG_DIR.$blogImage['id_bees_blog_post'].'-'.stripslashes($imageType['type_name']).'.jpg';
                if (file_exists($dir)) {
                    unlink($dir);
                }
            }
        }
    }
}
