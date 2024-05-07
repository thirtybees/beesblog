<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImageType;
use BeesBlogModule\BeesBlogPost;

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/autoload.php';

/**
 * Class BeesBlog
 */
class BeesBlog extends Module
{
    const SHORTCODE_HOOK = 'actionConvertShortcodes';
    const POSTS_PER_PAGE = 'BEESBLOG_POSTS_PER_PAGE';
    const AUTHOR_STYLE = 'BEESBLOG_SHOW_AUTHOR_STYLE';
    const MAIN_URL_KEY = 'BEESBLOG_MAIN_URL_KEY';
    const USE_HTML = 'BEESBLOG_USE_HTML';
    const ENABLE_COMMENT = 'BEESBLOG_ENABLE_COMMENT';
    const SHOW_AUTHOR = 'BEESBLOG_SHOW_AUTHOR';
    const SHOW_DATE = 'BEESBLOG_SHOW_DATE';
    const SOCIAL_SHARING = 'BEESBLOG_SOCIAL_SHARING';
    const SHOW_POST_COUNT = 'BEESBLOG_SHOW_VIEWED';
    const SHOW_NO_IMAGE = 'BEESBLOG_SHOW_NO_IMAGE';
    const CUSTOM_CSS = 'BEESBLOG_CUSTOM_CSS';
    const SHOW_CATEGORY_IMAGE = 'BEESBLOG_DISABLE_CATEGORY_IMAGE';
    const HOME_TITLE = 'BEESBLOG_META_TITLE';
    const HOME_KEYWORDS = 'BEESBLOG_META_KEYWORDS';
    const HOME_DESCRIPTION = 'BEESBLOG_META_DESCRIPTION';
    const DISQUS_USERNAME = 'BEESBLOG_DISQUS_USERNAME';
    const MAX_POSTS_PER_PAGE = 20;
    const MAX_CATEGORIES_PER_PAGE = 20;

    /**
     * @var array[]
     */
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

    /**
     * @var array
     */
    protected $fieldsForm;

    /**
     * BeesBlog constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'beesblog';
        $this->tab = 'front_office_features';
        $this->version = '1.7.0';
        $this->author = 'thirty bees';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;

        $this->controllers = ['category', 'post'];
        $this->bootstrap = true;
        $this->badges = ['beta'];

        parent::__construct();
        $this->displayName = $this->l('Bees Blog');
        $this->description = $this->l('thirty bees blog module');
    }

    /**
     * Install this module
     *
     * @param bool $createTables indicates if database table should be created or not
     *
     * @return bool Whether the module has been successfully installed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function install($createTables = true)
    {
        if (!parent::install()) {
            return false;
        }

        Configuration::updateGlobalValue(static::POSTS_PER_PAGE, 5);
        Configuration::updateGlobalValue(static::SHOW_AUTHOR, true);
        Configuration::updateGlobalValue(static::SHOW_DATE, true);
        Configuration::updateGlobalValue(static::SOCIAL_SHARING, true);
        Configuration::updateGlobalValue(static::AUTHOR_STYLE, 1);
        Configuration::updateGlobalValue(static::MAIN_URL_KEY, 'blog');
        Configuration::updateGlobalValue(static::USE_HTML, true);
        Configuration::updateGlobalValue(static::SHOW_POST_COUNT, true);

        Configuration::updateGlobalValue(static::SHOW_NO_IMAGE, false);
        Configuration::updateGlobalValue(static::SHOW_CATEGORY_IMAGE, false);
        Configuration::updateGlobalValue(static::HOME_TITLE, 'Bees blog title');
        Configuration::updateGlobalValue(static::HOME_KEYWORDS, 'thirty bees blog,thirty bees');
        Configuration::updateGlobalValue(static::HOME_DESCRIPTION, 'The beesiest blog for thirty bees');

        if ($createTables) {
            if (!(BeesBlogPost::createDatabase()
                && BeesBlogCategory::createDatabase()
                && BeesBlogImageType::createDatabase())
            ) {
                return false;
            }
        }

        if (! $this->registerHooks()) {
            return false;
        }

        $this->createBeesBlogTabs();
        BeesBlogImageType::installBasics();

        if ($createTables) {
            $this->installFixtures();
        }

        return true;
    }

    /**
     * Registers all hooks this module depends on
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function registerHooks()
    {
        return (
            $this->registerHook('displayHeader') &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('GSitemapAppendUrls') &&
            $this->registerHook('actionRegisterShortcodes') &&
            $this->registerHook('actionRegisterShortcodeEntityTypes') &&
            $this->insertBlogHooks()
        );
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
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
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function createBeesBlogTabs()
    {
        $langs = Language::getLanguages();
        $beesTab = new Tab((int) Tab::getIdFromClassName('AdminBeesBlog'));
        $beesTab->class_name = 'AdminBeesBlog';
        $beesTab->module = '';
        $beesTab->id_parent = 0;
        foreach ($langs as $l) {
            $beesTab->name[$l['id_lang']] = $this->l('Blog');
        }

        $beesTab->save();

        $tabs = [
            [
                'class_name' => 'AdminBeesBlogPost',
                'id_parent'  => $beesTab->id,
                'module'     => $this->name,
                'name'       => 'Posts',
            ],
            [
                'class_name' => 'AdminBeesBlogCategory',
                'id_parent'  => $beesTab->id,
                'module'     => $this->name,
                'name'       => 'Categories',
            ],
            [
                'class_name' => 'AdminBeesBlogImages',
                'id_parent'  => $beesTab->id,
                'module'     => $this->name,
                'name'       => 'Images',
            ],
        ];

        foreach ($tabs as $tab) {
            $newTab = new Tab((int) Tab::getIdFromClassName($tab['class_name']));
            $newTab->class_name = $tab['class_name'];
            $newTab->id_parent = $tab['id_parent'];
            $newTab->module = $tab['module'];
            foreach ($langs as $l) {
                $newTab->name[$l['id_lang']] = $this->l($tab['name']);
            }

            $newTab->save();
        }

        return true;
    }

    /**
     * Uninstall this module
     *
     * @param bool $removeTables indicates if database tables should be dropped
     * @return bool Whether the module has been successfully uninstalled
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function uninstall($removeTables = true)
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName(static::HOME_TITLE) ||
            !Configuration::deleteByName(static::HOME_KEYWORDS) ||
            !Configuration::deleteByName(static::HOME_DESCRIPTION) ||
            !Configuration::deleteByName(static::POSTS_PER_PAGE) ||
            !Configuration::deleteByName(static::USE_HTML) ||
            !Configuration::deleteByName(static::SHOW_POST_COUNT) ||
            !Configuration::deleteByName(static::SHOW_CATEGORY_IMAGE) ||
            !Configuration::deleteByName(static::MAIN_URL_KEY) ||
            !Configuration::deleteByName(static::AUTHOR_STYLE) ||
            !Configuration::deleteByName(static::SHOW_NO_IMAGE) ||
            !Configuration::deleteByName(static::SHOW_AUTHOR) ||
            !Configuration::deleteByName(static::SHOW_DATE) ||
            !Configuration::deleteByName(static::SOCIAL_SHARING)
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

        if ($removeTables) {
            if (!(BeesBlogPost::dropDatabase()
                && BeesBlogCategory::dropDatabase()
                && BeesBlogImageType::dropDatabase())
            ) {
                return false;
            }
        }

        $this->deleteBlogHooks();

        if ($removeTables) {
            // delete images when module is fully uninstalled
            $this->deleteAllImages(_PS_IMG_DIR_.'beesblog/');
        }

        return true;
    }

    /**
     * Delete blog hooks
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * Resets module settings without removing blog post data from database
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function reset()
    {
        return (
            $this->uninstall(false) &&
            $this->install(false)
        );
    }

    /**
     * Register the module routes
     *
     * @return array Array with routes
     * @throws PrestaShopException
     */
    public function hookModuleRoutes()
    {
        $alias = Configuration::get(static::MAIN_URL_KEY);

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
                'rule'       => "{$alias}/category",
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_list_module'         => [
                'controller' => 'category',
                'rule'       => "module/{$alias}/category",
                'keywords'   => [],
                'params'     => [
                    'fc'     => 'module',
                    'module' => $this->name,
                ],
            ],
            'beesblog_list_pagination'     => [
                'controller' => 'category',
                'rule'       => "{$alias}/category/page/{page}",
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
                'rule'       => "{$alias}/page/{page}",
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
                'rule'       => "{$alias}/category/{cat_rewrite}",
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
                'rule'       => "{$alias}/category/{cat_rewrite}/page/{page}",
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
                'rule'       => "module/{$alias}/category/{blog_rewrite}/page/{page}",
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
                'controller' => 'post',
                'rule'       => "{$alias}/{blog_rewrite}",
                'keywords'   => [
                    'blog_rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]+', 'param' => 'blog_rewrite'],
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
     * @return array Sitemap links
     *
     * @since 11.0.0
     * @throws PrestaShopException
     */
    public function hookGSitemapAppendUrls()
    {
        $links = [];
        $langId = (int)Context::getContext()->language->id;

        // Blog posts
        $results = (new PrestaShopCollection('BeesBlogModule\\BeesBlogPost', $langId))
            ->where('active', '=', 1)
            ->getResults();
        if ($results) {
            /** @var BeesBlogPost $result */
            foreach ($results as $result) {
                if ($result->lang_active) {
                    $link = [];
                    $link['link'] = $result->link;
                    $link['lastmod'] = $result->date_upd;
                    $link['type'] = 'module';
                    $this->addImageLink($link, BeesBlogPost::getImagePath($result->id, 'post_list_item'));
                    $links[] = $link;
                }
            }
        }

        // Categories
        $results = (new PrestaShopCollection('BeesBlogModule\\BeesBlogCategory', $langId))
            ->where('active', '=', 1)
            ->getResults();

        if ($results) {
            /** @var BeesBlogCategory $result */
            foreach ($results as $result) {
                $link = [];
                $link['link'] = $result->link;
                $link['lastmod'] = $result->date_upd;
                $link['type'] = 'module';
                $this->addImageLink($link, BeesBlogCategory::getImagePath($result->id));
                $links[] = $link;
            }
        }

        return $links;
    }

    /**
     * Add $image to sitemapLink record, if image exists
     *
     * @param array $link
     * @param string $imagePath
     * @return void
     * @throws PrestaShopException
     */
    protected function addImageLink(&$link, $imagePath)
    {
        if ($imagePath && @file_exists($imagePath)) {
            $link['image'] = [
                'link' => $this->context->link->getMediaLink(Media::getMediaPath($imagePath))
            ];
        }
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
     *
     * @since 1.0.0
     */
    public static function getBeesBlogLink($rewrite = null, $params = [], $idShop = null, $idLang = null)
    {
        if (!$rewrite) {
            $rewrite = 'beesblog';
        }
        $dispatcher = Dispatcher::getInstance();
        $context = Context::getContext();
        $link = $context->link;
        return $link->getBaseLink($idShop) . $link->getLangLink($idLang, $context, $idShop) . $dispatcher->createUrl($rewrite, $idLang, $params, false, '', $idShop);
    }

    /**
     * Hook display header
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookDisplayHeader()
    {
        $controller = $this->context->controller;
        $controller->addCSS($this->_path.'views/css/beesblogstyle.css', 'all');

        if ($controller instanceof BeesBlogPostModuleFrontController) {
            $post = $controller->getBeesBlogPost();
            if ($post) {
                Context::getContext()->smarty->assign([
                    'bb_og_link' => $post->link,
                    'bb_og_title' => $post->meta_title . ' - ' . Configuration::get('PS_SHOP_NAME'),
                    'bb_og_description' => $post->meta_description,
                    'bb_og_image' => Tools::getHttpHost(true) . '/img/beesblog/posts/' . (int)$post->id . '.jpg',
                ]);
                return $this->display(__FILE__, 'views/templates/hooks/post-header.tpl');
            }
        }
        return null;
    }

    /**
     * @since 1.0.0
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/back.css', 'all');
    }

    /**
     * @return Shortcodes\Shortcode\Shortcode[]
     */
    public function hookActionRegisterShortcodes($params)
    {
        require_once(__DIR__ . '/classes/shortcode/include.php');
        return [
            new \BeesBlogModule\BlogPostLinkShortcode($params['contextFactory'])
        ];
    }

    /**
     * @return array
     */
    public function hookActionRegisterShortcodeEntityTypes()
    {
        require_once(__DIR__ . '/classes/shortcode/include.php');
        return [
            new \BeesBlogModule\BlogPostEntityType()
        ];
    }


        /**
     * Get module configuration page
     *
     * @return string HTML
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function getContent()
    {
        $this->postProcess();

        $html = '';
        $helper = $this->getSettingsFormHelper();
        $html .= $helper->generateForm($this->fieldsForm);
        $html .= $this->renderDisqusOptions();

        return $html;
    }


    /**
     * Save form data.
     *
     * @return void
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue(static::HOME_TITLE, Tools::getValue(static::HOME_TITLE));
            Configuration::updateValue(static::HOME_KEYWORDS, Tools::getValue(static::HOME_KEYWORDS));
            Configuration::updateValue(static::HOME_DESCRIPTION, Tools::getValue(static::HOME_DESCRIPTION));
            Configuration::updateValue(static::POSTS_PER_PAGE, Tools::getValue(static::POSTS_PER_PAGE));
            Configuration::updateValue(static::SHOW_POST_COUNT, Tools::getValue(static::SHOW_POST_COUNT));
            Configuration::updateValue(static::SHOW_CATEGORY_IMAGE, Tools::getValue(static::SHOW_CATEGORY_IMAGE));
            Configuration::updateValue(static::SHOW_AUTHOR, Tools::getValue(static::SHOW_AUTHOR));
            Configuration::updateValue(static::SHOW_DATE, Tools::getValue(static::SHOW_DATE));
            Configuration::updateValue(static::SOCIAL_SHARING, Tools::getValue(static::SOCIAL_SHARING));
            Configuration::updateValue(static::AUTHOR_STYLE, Tools::getValue(static::AUTHOR_STYLE));
            Configuration::updateValue(static::MAIN_URL_KEY, Tools::getValue(static::MAIN_URL_KEY));
            Configuration::updateValue(static::USE_HTML, Tools::getValue(static::USE_HTML));
            Configuration::updateValue(static::SHOW_NO_IMAGE, Tools::getValue(static::SHOW_NO_IMAGE));
        }

        if (Tools::isSubmit('submitOptionsconfiguration') || Tools::isSubmit('submitOptions')) {
            $this->postProcessDisqusOptions();
        }
    }

    /**
     * Process General Options
     *
     * @throws PrestaShopException
     */
    protected function postProcessDisqusOptions()
    {
        $username = Tools::getValue(static::DISQUS_USERNAME);
        Configuration::updateValue(static::DISQUS_USERNAME, $username);
    }

    /**
     * @return HelperForm
     * @throws PrestaShopException
     */
    public function getSettingsFormHelper()
    {
        $categoriesPerPage = [];
        for ($i = 1; $i < static::MAX_CATEGORIES_PER_PAGE; $i++) {
            $categoriesPerPage[] = [
                'id'    => $i,
                'value' => $i,
                'name'  => $i,
            ];
        }


        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
                'icon'  => 'icon-cogs',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Meta title'),
                    'name'     => static::HOME_TITLE,
                    'size'     => 70,
                    'required' => true,
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Meta Description'),
                    'name'     => static::HOME_DESCRIPTION,
                    'rows'     => 7,
                    'cols'     => 66,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Blog url key'),
                    'name'     => static::MAIN_URL_KEY,
                    'size'     => 15,
                    'required' => true,
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Number of posts per page'),
                    'name'     => static::POSTS_PER_PAGE,
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
                    'name'     => static::SHOW_AUTHOR,
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
                    'label'    => $this->l('Show dates'),
                    'name'     => static::SHOW_DATE,
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
                    'name'     => static::SHOW_POST_COUNT,
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
                    'label'    => $this->l('Social media buttons'),
                    'name'     => static::SOCIAL_SHARING,
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
                    'type'     => 'radio',
                    'label'    => $this->l('Author name style'),
                    'name'     => static::AUTHOR_STYLE,
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
                    'name'     => static::SHOW_CATEGORY_IMAGE,
                    'required' => false,
                    'class'    => 't',
                    'desc'     => 'Show the category image and description on every category page',
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
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function getFormValues()
    {
        return Configuration::getMultiple(
            [
                static::POSTS_PER_PAGE,
                static::SHOW_AUTHOR,
                static::SHOW_DATE,
                static::SOCIAL_SHARING,
                static::AUTHOR_STYLE,
                static::MAIN_URL_KEY,
                static::USE_HTML,
                static::HOME_TITLE,
                static::HOME_KEYWORDS,
                static::HOME_DESCRIPTION,
                static::SHOW_POST_COUNT,
                static::SHOW_POST_COUNT,
                static::SHOW_CATEGORY_IMAGE,
                static::SHOW_NO_IMAGE,
            ]
        );
    }

    /**
     * Render the General options form
     *
     * @return string HTML
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
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
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function getDisqusOptions()
    {
        return [
            'locales' => [
                'title'  => $this->l('Comment section'),
                'icon'   => 'icon-server',
                'fields' => [
                    static::DISQUS_USERNAME => [
                        'title'      => $this->l('Disqus username'),
                        'type'       => 'text',
                        'name'       => static::DISQUS_USERNAME,
                        'value'      => Configuration::get(static::DISQUS_USERNAME),
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
     * Get post image path
     * Proxy for smarty
     *
     * @param int    $id
     * @param string $type
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getPostImagePath($id, $type = 'post_default')
    {
        return BeesBlogPost::getImagePath($id, $type);
    }

    /**
     * Get category image path
     * Proxy for smarty
     *
     * @param int    $id
     * @param string $type
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getCategoryImagePath($id, $type = 'category_default')
    {
        return BeesBlogCategory::getImagePath($id, $type);
    }

    /**
     * Installs data fixtures
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installFixtures()
    {
        Shop::addTableAssociation(BeesBlogCategory::TABLE, ['type' => 'shop']);
        Shop::addTableAssociation(BeesBlogPost::TABLE, ['type' => 'shop']);

        $category = new BeesBlogCategory();
        $category->active = true;
        $category->id_parent = 0;
        $category->position = 1;
        $this->setLangValues($category, [
            'title' => 'News',
            'description' => 'thirty bees news',
            'link_rewrite' => 'news',
            'meta_title' => 'thirty bees news',
            'meta_description' => 'news about thirty bees',
            'meta_keywords' => '',
        ]);
        $category->add();

        $this->installPostFixture('post1', $category->id, [
            'title' => 'Organic Roasted Coffee',
            'link_rewrite' => 'organic-coffee',
            'meta_title' => 'Organic Roasted Coffee',
            'meta_description' => 'thirty bees organic roasted coffee',
        ]);
        $this->installPostFixture('post2', $category->id, [
            'title' => 'Hand Picked Teas',
            'link_rewrite' => 'hand-picked-teas',
            'meta_title' => 'Hand Picked Teas',
            'meta_description' => 'Hand picked teas from thirty bees',
        ]);
        $this->installPostFixture('post3', $category->id, [
            'title' => 'Organic Gifts',
            'link_rewrite' => 'organic-gifts',
            'meta_title' => 'Organic Gifts',
            'meta_description' => 'Organic gifts from thirty bees',
        ]);
    }

    /**
     * Installs fixture for single post
     *
     * @param string $id
     * @param int $categoryId
     * @param array $texts
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installPostFixture($id, $categoryId, $texts)
    {
        $post = new BeesBlogPost();
        $post->id_category = $categoryId;
        $post->id_employee = Context::getContext()->employee->id;
        $post->post_type = '0';
        $post->viewed = 0;
        $post->published = date('Y-m-d H:i:s');
        $this->setLangValues($post, array_merge($texts, [
            'content' => $this->getPostFixtureContent($id . '.html'),
            'meta_keywords' => '',
            'lang_active' => true,
        ]));
        $post->add();
        $this->installFixtureImage($id . '.jpg', $post->id);
    }

    /**
     * Helper method to set language texts to object model
     *
     * @param ObjectModel $obj
     * @param array $values
     * @throws PrestaShopException
     */
    protected function setLangValues($obj, $values)
    {
        foreach ($values as $key => $_) {
            $obj->{$key} = [];
        }
        foreach (Language::getLanguages(true, false, true) as $lang) {
            foreach ($values as $key => $value) {
                $obj->{$key}[$lang] = $value;
            }
        }
    }

    /**
     * Installs image for post post fixture
     *
     * @param string $imageName
     * @param int $postId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function installFixtureImage($imageName, $postId)
    {
        $sourceFile = _PS_MODULE_DIR_ . $this->name . '/fixtures/' . $imageName;
        if (! file_exists($sourceFile)) {
            throw new PrestaShopException(sprintf($this->l('File with fixture image not found: `%s`'), $sourceFile));
        }
        $dir = _PS_IMG_DIR_ . "beesblog/posts/";
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new PrestaShopException(sprintf($this->l('Unable to create image directory: `%s`'), $dir));
            }
        }
        $targetFile = $dir . $postId . ".jpg";
        copy($sourceFile, $targetFile);

        $imageTypes = BeesBlogImageType::getImagesTypes('posts');
        foreach ($imageTypes as $imageType) {
            $targetFile = $dir . $postId . '-' . $imageType['name'] . '.jpg';
            if (file_exists($targetFile)) {
                @unlink($targetFile);
            }
            ImageManager::resize(
                $sourceFile,
                $targetFile,
                (int) $imageType['width'],
                (int) $imageType['height'],
                'jpg',
                true
            );
        }
    }

    /**
     * Returns html content for post fixture
     *
     * @param string $file
     * @return string
     */
    protected function getPostFixtureContent($file)
    {
        return file_get_contents(_PS_MODULE_DIR_ . $this->name . '/fixtures/' . $file);
    }

    /**
     * Helper method to delete all images in directory $dir
     *
     * @param string $dir
     */
    protected function deleteAllImages($dir)
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                $path = $dir . $file;
                if (is_dir($path)) {
                    $this->deleteAllImages($path . '/');
                } else {
                    if (preg_match('/.*\.jpg$/', $file)) {
                        unlink($path);
                    }
                }
            }
        }
    }

    /**
     * @param string $text
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function processText(string $text)
    {
        $text = trim((string)$text);
        if ($text && static::shouldRunShortcodeHook()) {
            return (string)Hook::getFirstResponse(static::SHORTCODE_HOOK, ['input' => $text]);
        }
        return $text;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected static function shouldRunShortcodeHook()
    {
        static $shortcodeHookExists = null;
        if ($shortcodeHookExists === null) {
            $shortcodeHookExists = (bool)Hook::getHookModuleExecList(static::SHORTCODE_HOOK);
        }
        return (bool)$shortcodeHookExists;
    }
}
