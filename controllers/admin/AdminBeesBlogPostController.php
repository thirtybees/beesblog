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

if (!defined('_TB_VERSION_')) {
    exit;
}

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImageType;
use BeesBlogModule\BeesBlogPost;

/**
 * Class AdminBeesBlogPostController
 *
 * @since 1.0.0
 */
class AdminBeesBlogPostController extends ModuleAdminController
{
    /**
     * @var BeesBlog
     */
    public $module;

    /**
     * AdminBeesBlogPostController constructor.
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function __construct()
    {
        // This is the main table we are going to use for this controller
        $this->table = BeesBlogPost::TABLE;

        // This is the main class we are going to use for this AdminController
        $this->className = 'BeesBlogModule\\BeesBlogPost';

        // Shop bootstrap elements, not the old crappy interface
        $this->bootstrap = true;

        // Retrieve the context from a static context, just because
        $this->context = Context::getContext();

        // Only display this page in single store context
        $this->multishop_context = Shop::CONTEXT_SHOP;

        // Make sure that when we save the `BeesBlogCategory` ObjectModel, the `_shop` table is set, too (primary => id_shop relation)
        Shop::addTableAssociation(BeesBlogPost::TABLE, ['type' => 'shop']);

        // We are going to use multilang ObjectModels, but there is just one language to display
        $this->lang = true;

        $this->fields_list = [
            BeesBlogPost::PRIMARY => [
                'title'   => $this->l('ID'),
                'width'   => 50,
                'type'    => 'text',
                'orderby' => true,
                'filter'  => true,
                'search'  => true,
            ],
            'viewed'              => [
                'title'   => $this->l('View'),
                'width'   => 50,
                'type'    => 'text',
                'lang'    => true,
                'orderby' => true,
                'filter'  => false,
                'search'  => false,
            ],
            'id_category'              => [
                'title'   => $this->l('Category'),
                'width'   => 50,
                'type'    => 'text',
                'lang'    => true,
                'orderby' => true,
                'filter'  => true,
                'search'  => true,
                'callback' => 'getCategoryTitleById',
            ],
            'title'               => [
                'title'   => $this->l('Title'),
                'width'   => 440,
                'type'    => 'text',
                'lang'    => true,
                'orderby' => true,
                'filter'  => true,
                'search'  => true,
            ],
            'published'            => [
                'title'   => $this->l('Posted Date'),
                'width'   => 100,
                'type'    => 'date',
                'lang'    => true,
                'orderby' => true,
                'filter'  => true,
                'search'  => true,
                'callback' => 'colorDateIssue',
            ],
            'active'              => [
                'title'   => $this->l('Status'),
                'width'   => '70',
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => true,
                'filter'  => true,
                'search'  => true,
            ],
        ];

        // Set some default HelperList sortings
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'bees_blog_post_shop sbs ON a.id_bees_blog_post = sbs.id_bees_blog_post AND sbs.id_shop IN('.implode(',', Shop::getContextListShopID()).')';
        $this->_defaultOrderBy = 'a.id_bees_blog_post';
        $this->_defaultOrderWay = 'DESC';

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->_group = 'GROUP BY a.bees_blog_post';
        }

        // Check if there are any categories available
        if (BeesBlogCategory::getCategories($this->context->language->id, 0, 10, true) < 1) {
            $this->errors[] = $this->l('No categories found. Please add a category before making a new post.');
        }

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        $this->addJquery();
        $this->addJqueryPlugin('autocomplete');
        $this->addJS(_MODULE_DIR_ . 'beesblog/views/js/admin-post.js');

        parent::__construct();
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Post process
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('deleteImage')) {
            $this->processForceDeleteImage();
        } else {
            parent::postProcess();
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processForceDeleteImage()
    {
        $blogPost = $this->loadObject(true);

        if (Validate::isLoadedObject($blogPost)) {
            $this->deleteImage($blogPost->id);
        }
    }

    /**
     * @param int $idBeesBlogPost
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function deleteImage($idBeesBlogPost)
    {
        $deleted = false;
        // Delete base image
        foreach (['png', 'jpg'] as $extension) {
            if (file_exists(_PS_IMG_DIR_."beesblog/posts/{$idBeesBlogPost}.{$extension}")) {
                unlink(_PS_IMG_DIR_."beesblog/posts/{$idBeesBlogPost}.{$extension}");
            }

            // now we need to delete the image type of post

            $filesToDelete = [];

            // Delete auto-generated images
            $imageTypes = BeesBlogImageType::getImagesTypes('posts');
            foreach ($imageTypes as $imageType) {
                $filesToDelete[] = _PS_IMG_DIR_."beesblog/posts/{$idBeesBlogPost}-{$imageType['name']}.{$extension}";
            }

            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                    $deleted = true;
                }
            }
        }

        if ($deleted) {
            $this->confirmations[] = $this->l('Successfully deleted image');
        }

        return true;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        if (! $this->loadObject(true)) {
            return '';
        }

        $id = (int) Tools::getValue(BeesBlogPost::PRIMARY);
        $lang = (int)$this->context->language->id;

        $imageUrl = ImageManager::thumbnail(BeesBlogPost::getImagePath($id), $this->table."_{$id}.jpg", 200, 'jpg', true, true);
        $imageSize = file_exists(BeesBlogPost::getImagePath($id)) ? filesize(BeesBlogPost::getImagePath($id)) / 1000 : false;

        $products = $id ? Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((new DbQuery())
            ->select('p.id_product, pl.name, p.reference')
            ->from('bees_blog_post_product', 'pp')
            ->innerJoin('product', 'p', 'p.id_product = pp.id_product')
            ->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.$lang.Shop::addSqlRestrictionOnLang('pl'))
            ->where('pp.id_bees_blog_post = '.$id)
        ) : [];

        $employees = array();
        foreach (Employee::getEmployees() as $employee)
        {
            $employees[] = array(
                'id' => $employee['id_employee'],
                'name' => sprintf('%s %s', $employee['firstname'], $employee['lastname'])
            );
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Blog Post'),
            ],
            'input'  => [
                [
                    'type'          => 'hidden',
                    'name'          => 'post_type',
                    'default_value' => 0,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Blog title'),
                    'name'     => 'title',
                    'id'       => 'name',
                    'class'    => 'copyMeta2friendlyURL',
                    'size'     => 60,
                    'required' => true,
                    'desc'     => $this->l('Enter the title of your blog post'),
                    'lang'     => true,
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Post author'),
                    'name'    => 'id_employee',
                    'options' => [
                        'query' => $employees,
                        'id'    => 'id',
                        'name'  => 'name',
                    ],
                    'desc'    => $this->l('Select blog post author'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Content'),
                    'name'         => 'content',
                    'lang'         => true,
                    'rows'         => 10,
                    'cols'         => 62,
                    'class'        => 'rte',
                    'autoload_rte' => true,
                    'required'     => true,
                    'hint'         => [
                        $this->l('Enter the content of your post'),
                        $this->l('Invalid characters:').' <>;=#{}',
                    ],
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Image'),
                    'name'          => 'post_image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'delete_url'    => self::$currentIndex.'&'.$this->identifier.'='. Tools::getValue(BeesBlogPost::PRIMARY).'&token='.$this->token.'&deleteImage=1',
                    'hint'          => $this->l('Upload an image from your computer.'),
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('Blog Category'),
                    'name'    => 'id_category',
                    'options' => [
                        'query' => BeesBlogCategory::getCategories($this->context->language->id, 0, 0, false, true, ['id', 'title']),
                        'id'    => 'id',
                        'name'  => 'title',
                    ],
                    'desc'    => $this->l('Select Your Parent Category'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Link Rewrite'),
                    'name'     => 'link_rewrite',
                    'size'     => 60,
                    'lang'     => true,
                    'required' => false,
                    'hint'     => $this->l('Only letters and the hyphen (-) character are allowed.'),
                ],
                [
                    'type'    => 'text',
                    'label'   => $this->l('Meta title'),
                    'name'    => 'meta_title',
                    'maxchar' => 70,
                    'lang'    => true,
                    'hint'    => $this->l('Forbidden characters:').' <>;=#{}',
                ],
                [
                    'type'    => 'text',
                    'label'   => $this->l('Meta description'),
                    'name'    => 'meta_description',
                    'maxchar' => 160,
                    'lang'    => true,
                    'hint'    => $this->l('Forbidden characters:').' <>;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'hint'  => $this->l('Forbidden characters:').' <>;=#{}',
                    'desc'  => $this->l('To add tags, click in the field, write something, then press "Enter".'),
                ],
                [
                    'type'     => 'product-selector',
                    'label'    => $this->l('Related products'),
                    'name'     => 'products',
                    'id'       => 'products',
                    'size'     => 60,
                    'required' => false,
                    'products' => $products,
                    'hint'     => [
                        $this->l('Associate this blog post with products'),
                    ]
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Comment Status'),
                    'name'     => 'comments_enabled',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'desc'     => $this->l('You can enable or disable comments'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'checkbox',
                    'label'    => $this->l('Available for these languages'),
                    'name'     => 'lang_active',
                    'multiple' => true,
                    'values'   => [
                        'query' => Language::getLanguages(false),
                        'id'    => 'id_lang',
                        'name'  => 'name',
                    ],
                    'expand'   => (count(Language::getLanguages(false)) > 10) ? [
                        'print_total' => count(Language::getLanguages(false)),
                        'default'     => 'show',
                        'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ] : null,
                ],
                [
                    'type'  => 'datetime',
                    'label' => $this->l('Publish date'),
                    'name'  => 'published',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save and Stay'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ],
            ],
        ];

        $this->fields_value = [
            'post_image' => $imageUrl,
            'products' => $products,
        ];

        foreach (Language::getLanguages(true) as $language) {
            $this->fields_value['lang_active_'.(int) $language['id_lang']] = (bool) BeesBlogPost::getLangActive(Tools::getValue(BeesBlogPost::PRIMARY), $language['id_lang']);
        }

        Media::addJsDef(['PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')]);

        return parent::renderForm();
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUI('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * Process category image
     *
     * @param array $files
     * @param int $id
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processImage($files, $id)
    {
        $postImageInput = 'post_image';

        if (isset($files[$postImageInput]) && isset($files[$postImageInput]['tmp_name']) && !empty($files[$postImageInput]['tmp_name'])) {
            if ($error = ImageManager::validateUpload($files[$postImageInput], 4000000)) {
                $this->errors[] = $error;

                return false;
            } else {
                $ext = substr($files[$postImageInput]['name'], strrpos($files[$postImageInput]['name'], '.') + 1);
                $path = _PS_IMG_DIR_."beesblog/posts/";
                if (!file_exists($path)) {
                    if (!mkdir($path, 0777, true)) {
                        $this->errors[] = sprintf($this->l('Unable to create image directory: `%s`'), $path);
                    }
                }
                $path .= "$id.$ext";
                if (!move_uploaded_file($files[$postImageInput]['tmp_name'], $path)) {
                    $this->errors[] = $this->l('An error occurred while attempting to upload the file.');

                    return false;
                } else {
                    $imageTypes = BeesBlogImageType::getImagesTypes('posts');
                    foreach ($imageTypes as $imageType) {
                        $dir = _PS_IMG_DIR_."beesblog/posts/$id-{$imageType['name']}.$ext";
                        if (file_exists($dir)) {
                            @unlink($dir);
                        }
                        ImageManager::resize(
                            $path,
                            _PS_IMG_DIR_."beesblog/posts/$id-{$imageType['name']}.$ext",
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            $ext,
                            true
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param int $id Blog post id
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processProducts($id)
    {
        $id = (int)$id;
        Db::getInstance()->delete('bees_blog_post_product', 'id_bees_blog_post = '.$id);
        $products = Tools::getValue('products');
        if ($products) {
            $insert = [];
            foreach (explode('|', $products) as $productId) {
                $insert[] = [
                    'id_product' => (int)$productId,
                    'id_bees_blog_post' => (int)$id,
                ];
            }
            Db::getInstance()->insert('bees_blog_post_product', $insert);
        }
    }

    /**
     * Process add
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processAdd()
    {
        if (Tools::isSubmit(BeesBlogPost::PRIMARY)) {
            return false;
        }

        // validate data
        if (! Tools::getValue('published')) {
            $_POST['published'] = date('Y-m-d H:i:s');
        }
        foreach (Language::getLanguages(false, false, true) as $idLang) {
            $key = 'lang_active_' . (int)$idLang;
            if (!isset($_POST[$key])) {
                $_POST[$key] = 0;
            } else {
                $_POST[$key] = $_POST[$key] === 'on' ? 1 : 0;
            }
        }
        $this->validateRules();
        if ($this->errors) {
            $this->display = 'add';
            return false;
        }

        $blogPost = new BeesBlogPost();
        $this->copyFromPost($blogPost, $this->table);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach (BeesBlogPost::$definition['fields'] as $name => $field) {
            if (isset($field['lang']) && $field['lang']) {
                foreach (Language::getLanguages(false, false, true) as $idLang) {
                    if ((int) $idLang !== $idLangDefault) {
                        switch (BeesBlogPost::$definition['fields'][$name]['type']) {
                            case ObjectModel::TYPE_INT:
                            case ObjectModel::TYPE_FLOAT:
                                $defaultValue = 0;
                                break;
                            case ObjectModel::TYPE_BOOL:
                                $defaultValue = false;
                                break;
                            case ObjectModel::TYPE_STRING:
                            case ObjectModel::TYPE_HTML:
                            case ObjectModel::TYPE_SQL:
                                $defaultValue = '';
                                break;
                            case ObjectModel::TYPE_DATE:
                                $defaultValue = '1970-01-01 00:00:00';
                                break;
                            case ObjectModel::TYPE_NOTHING:
                                $defaultValue = null;
                                break;
                            default:
                                $defaultValue = '';
                                break;
                        }
                        if (!is_array($blogPost->{$name})) {
                            $blogPost->$name = [
                                $idLangDefault => $defaultValue,
                            ];
                        } elseif (!isset($blogPost->$name[$idLangDefault])) {
                            $blogPost->$name[$idLangDefault] = $defaultValue;
                        }

                        $blogPost->$name[$idLang] = $blogPost->$name[$idLangDefault];
                    }
                }
            }
        }

        $blogPost->id_employee = $this->context->employee->id;
        $blogPost->viewed = 0;
        $blogPost->id_shop = (int) Context::getContext()->shop->id;

        if ($blogPost->add()) {
            $this->processImage($_FILES, $blogPost->id);
            $this->processProducts($blogPost->id);
            $this->confirmations[] = $this->l('Successfully added post');
            if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$blogPost->id.'&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&token='.$this->token;
            }
            return true;
        }
        $this->errors[] = $this->l('Unable to add new post');

        return false;
    }

    /**
     * Process update
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processUpdate()
    {
        if (!Tools::isSubmit(BeesBlogPost::PRIMARY)) {
            return false;
        }

        // validate data
        if (! Tools::getValue('published')) {
            $_POST['published'] = date('Y-m-d H:i:s');
        }
        foreach (Language::getLanguages(false, false, true) as $idLang) {
            $key = 'lang_active_' . (int)$idLang;
            if (!isset($_POST[$key])) {
                $_POST[$key] = 0;
            } else {
                $_POST[$key] = $_POST[$key] === 'on' ? 1 : 0;
            }
        }
        $this->validateRules();
        if ($this->errors) {
            $this->display = 'edit';
            return false;
        }

        $blogPost = new BeesBlogPost((int) Tools::getValue(BeesBlogPost::PRIMARY));
        $blogPost->lang_active = [];
        $this->copyFromPost($blogPost, $this->table);

        $blogPost->id_shop = (int) Context::getContext()->shop->id;
        $this->processImage($_FILES, $blogPost->id);
        $this->processProducts($blogPost->id);
        if ($blogPost->update()) {
            $this->confirmations[] = $this->l('Successfully updated post');
            if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$blogPost->id.'&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&token='.$this->token;
            }
            return true;
        }

        $this->errors[] = $this->l('Unable to update post');

        return false;
    }

    /**
     * Color the date in admin controller view
     *
     * @return string HTML string
     */
    static public function colorDateIssue($dateIssue) {

        $today = strtotime(date('Y-m-d H:i:s'));
        $dateIssueStr = strtotime($dateIssue);

        if ($today - $dateIssueStr < 0) {
            $color = '#eab3b7';
        } else {
            $color = '#92d097';
        }
        return "<span style='background-color:".$color."; color:white; border-radius:3px 3px 3px 3px; font-size:11px; padding: 2px 5px'>".$dateIssue."</span>";
    }

    /**
     * Initialize page header toolbar with a new add button
     *
     * @return void
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_image_type'] = [
                'href' => static::$currentIndex.'&add'.BeesBlogPost::TABLE.'&token='.$this->token,
                'desc' => $this->l('Add new post', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        if ($previewUrl = $this->getPreviewUrl()) {
            $this->page_header_toolbar_btn['preview'] = [
                'short'  => $this->l('Preview', null, null, false),
                'href'   => $previewUrl,
                'desc'   => $this->l('Preview', null, null, false),
                'target' => true,
                'class'  => 'previewUrl',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Process delete
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processDelete()
    {
        $blogPost = new BeesBlogPost((int) Tools::getValue(BeesBlogPost::PRIMARY));

        if (!$blogPost->delete()) {
            $this->errors[] = $this->l('An error occurred while deleting the object.').' <strong>'.$this->table.' ('. Db::getInstance()->getMsgError().')</strong>';
            return false;
        } else {
            $this->deleteImage($blogPost->id);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogPost'));
            return true;
        }
    }

    /**
     * Return category title by Id (list admin controller)
     *
     * @param int $id
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    static public function getCategoryTitleById($id) {

        return BeesBlogCategory::getNameById($id);
    }

    /**
     * Search products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessSearchProducts()
    {
        $query = Tools::getValue('q');
        $limit = (int)Tools::getValue('limit');
        $products = [];
        if ($query && $limit) {
            $lang = (int)$this->context->language->id;
            $query = pSQL($query);
            $sql = (new DbQuery())
                ->select('p.id_product, pl.name, p.reference')
                ->from('product', 'p')
                ->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.$lang.Shop::addSqlRestrictionOnLang('pl'))
                ->where('pl.name LIKE "%'.$query.'%" OR p.reference like "%'.$query.'%"')
                ->limit($limit);
            $excludeIds = Tools::getValue('excludeIds');
            if ($excludeIds && is_array($excludeIds)) {
                $excludeIds = implode(',', array_map('intval', $excludeIds));
                $sql->where('p.id_product NOT IN ('.$excludeIds.')');
            }
            $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        }
        die(json_encode($products));
    }

    /**
     * @return string | null
     * @throws PrestaShopException
     */
    protected function getPreviewUrl()
    {
        $id = (int)Tools::getValue(BeesBlogPost::PRIMARY);
        if ($id) {
            $post = new BeesBlogPost($id, $this->context->language->id);
            return $post->link;
        }
        return null;
    }
}
