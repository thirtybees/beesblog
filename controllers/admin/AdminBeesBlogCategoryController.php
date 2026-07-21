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
use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogMultistore;
use BeesBlogModule\BeesBlogPost;

/**
 * Class AdminBeesBlogCategoryController
 */
class AdminBeesBlogCategoryController extends ModuleAdminController
{
    /**
     * @var BeesBlog
     */
    public $module;

    /**
     * AdminBeesBlogCategoryController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        // This is the main table we are going to use for this controller
        $this->table = BeesBlogCategory::TABLE;

        // This is the main class we are going to use for this AdminController
        $this->className = 'BeesBlogModule\\BeesBlogCategory';

        // Shop bootstrap elements, not the old crappy interface
        $this->bootstrap = true;

        // Retrieve the context from a static context, just because
        $this->context = Context::getContext();

        $this->multishop_context = Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP;
        BeesBlogMultistore::registerAssociations();

        // We are going to use multilang ObjectModels but there is just one language to display
        $this->lang = false;
        $this->explicitSelect = true;

        // Allow bulk delete
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];

        // Set the fields_list to display in fields mode
        $this->fields_list = [
            BeesBlogCategory::PRIMARY => [
                'title' => $this->l('ID'),
                'width' => 100,
                'type'  => 'text',
            ],
            'title'                   => [
                'title' => $this->l('Title'),
                'width' => 440,
                'type'  => 'text',
                'filter_key' => 'scl!title',
            ],
            'id_parent'              => [
                'title'   => $this->l('Parent'),
                'width'   => 200,
                'type'    => 'text',
                'callback' => 'getParentTitleById',
                'filter_key' => 'scs!id_parent',
            ],
            'active'                  => [
                'title'   => $this->l('Status'),
                'width'   => '70',
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
                'filter_key' => 'scs!active',
            ],
        ];

        $contextShopIds = BeesBlogMultistore::getContextShopIds();
        $shopList = $contextShopIds ? implode(', ', array_map('intval', $contextShopIds)) : '0';
        $this->_join = 'INNER JOIN `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'` scs'.
            ' ON scs.`'.BeesBlogCategory::PRIMARY.'` = a.`'.BeesBlogCategory::PRIMARY.'`'.
            ' AND scs.`id_shop` = (SELECT MIN(scs_scope.`id_shop`)'.
            ' FROM `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'` scs_scope'.
            ' WHERE scs_scope.`'.BeesBlogCategory::PRIMARY.'` = a.`'.BeesBlogCategory::PRIMARY.'`'.
            ' AND scs_scope.`id_shop` IN ('.$shopList.'))'.
            ' INNER JOIN `'._DB_PREFIX_.BeesBlogCategory::LANG_TABLE.'` scl'.
            ' ON scl.`'.BeesBlogCategory::PRIMARY.'` = a.`'.BeesBlogCategory::PRIMARY.'`'.
            ' AND scl.`id_shop` = scs.`id_shop`'.
            ' AND scl.`id_lang` = '.(int) $this->context->language->id;
        $this->_select = 'scs.`id_shop` AS `list_shop_id`, scs.`id_parent`, scs.`active`, scl.`title`';
        $this->_defaultOrderBy = 'a.'.BeesBlogCategory::PRIMARY;
        $this->_defaultOrderWay = 'DESC';

        // With all this info set, it's about time to call the parent constructor
        parent::__construct();
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return '';
        }
        $id = (int) Tools::getValue(BeesBlogCategory::PRIMARY);
        $idShop = BeesBlogMultistore::getObjectRepresentativeShopId(
            BeesBlogCategory::TABLE,
            BeesBlogCategory::PRIMARY,
            $id
        );

        $imagePath = BeesBlogCategory::getImagePath($id, 'category_default', $idShop, 0);
        $imageUrl = $imagePath
            ? ImageManager::thumbnail($imagePath, $this->table."_{$id}_s{$idShop}_default.jpg", 200, 'jpg', true, true)
            : false;
        $imageSize = $imagePath && file_exists($imagePath) ? filesize($imagePath) / 1000 : false;

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Blog category'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'title',
                    'size'     => 60,
                    'required' => true,
                    'desc'     => $this->l('Enter your category name'),
                    'lang'     => true,
                    'id'       => 'name',
                    'class'    => 'copyMeta2friendlyURL',
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'lang'         => true,
                    'rows'         => 10,
                    'cols'         => 62,
                    'class'        => 'rte',
                    'autoload_rte' => true,
                    'required'     => false,
                    'desc'         => $this->l('Enter a description'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Default category image'),
                    'name'          => 'category_image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'delete_url'    => self::$currentIndex.'&'.$this->identifier.'='.Tools::getValue(BeesBlogCategory::PRIMARY).'&token='.$this->token.'&deleteImage=1&image_scope=shop',
                    'hint'          => $this->l('Fallback image for every language. It is applied to all shops in the current shop context.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('URL rewrite'),
                    'name'     => 'link_rewrite',
                    'size'     => 60,
                    'lang'     => true,
                    'required' => true,
                    'desc'     => $this->l('Enter your category URL key. Used for SEO Friendly URLs'),
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
                    'type'    => 'select',
                    'label'   => $this->l('Parent Category'),
                    'name'    => 'id_parent',
                    'options' => [
                        'query' => self::getCategoriesName(),
                        'id'    => 'id_bees_blog_category',
                        'name'  => 'title',
                    ],
                    'desc'    => $this->l('Select your parent category'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => false,
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

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            ];
        }

        foreach (Language::getLanguages(true, $idShop) as $language) {
            $idLang = (int) $language['id_lang'];
            $languageImagePath = BeesBlogImage::getScopedImagePath(
                BeesBlogImage::ENTITY_CATEGORY,
                $id,
                'category_default',
                $idShop,
                $idLang
            );
            $languageImageUrl = $languageImagePath
                ? ImageManager::thumbnail(
                    $languageImagePath,
                    $this->table."_{$id}_s{$idShop}_l{$idLang}.jpg",
                    200,
                    'jpg',
                    true,
                    true
                )
                : false;
            $this->fields_form['input'][] = [
                'type' => 'file',
                'label' => sprintf($this->l('%s image override'), $language['name']),
                'name' => 'category_image_lang_'.$idLang,
                'display_image' => true,
                'image' => $languageImageUrl,
                'size' => $languageImagePath && file_exists($languageImagePath)
                    ? filesize($languageImagePath) / 1000
                    : false,
                'delete_url' => self::$currentIndex.'&'.$this->identifier.'='.Tools::getValue(BeesBlogCategory::PRIMARY).
                    '&token='.$this->token.'&deleteImage=1&image_scope=language&id_lang='.$idLang,
                'hint' => $this->l('Optional. When empty, this language uses the default image above.'),
            ];
        }

        $this->fields_value = [
            'category_image' => $imageUrl
        ];

        Media::addJsDef(['PS_ALLOW_ACCENTED_CHARS_URL' => (int) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')]);

        return parent::renderForm();
    }

    /**
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        // Add row actions for the list
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        // Call the parent renderList function afterwards, because here
        // we are actually going to render
        return parent::renderList();
    }

    /**
     * @return void
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
     * Process category image
     *
     * @param array $files
     * @param int $id
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processImage($files, $id, array $shopIds = [])
    {
        $shopIds = $shopIds ?: BeesBlogMultistore::getSubmittedShopIds($this->table);
        $uploads = ['category_image' => 0];
        foreach (Language::getLanguages(false, false, true) as $idLang) {
            $uploads['category_image_lang_'.(int) $idLang] = (int) $idLang;
        }

        foreach ($uploads as $input => $idLang) {
            if (empty($files[$input]['tmp_name']) || (int) $files[$input]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $error = null;
            if (!BeesBlogImage::saveUploadedImage(
                $files[$input],
                BeesBlogImage::ENTITY_CATEGORY,
                (int) $id,
                $shopIds,
                $idLang,
                $error
            )) {
                $this->errors[] = $error ?: $this->l('An error occurred while attempting to upload the image.');
                return false;
            }
        }

        return true;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processForceDeleteImage()
    {
        $blogCategory = $this->loadObject(true);

        if (Validate::isLoadedObject($blogCategory)) {
            $shopIds = BeesBlogMultistore::getSubmittedShopIds($this->table);
            $idLang = Tools::getValue('image_scope') === 'language'
                ? max(1, (int) Tools::getValue('id_lang'))
                : 0;
            if (BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_CATEGORY, $blogCategory->id, $shopIds, $idLang)) {
                $this->confirmations[] = $this->l('Successfully deleted image');
            }
        }
    }

    /**
     * @param int $idBeesBlogCategory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function deleteImage($idBeesBlogCategory, array $shopIds = [], $deleteLegacy = false)
    {
        $shopIds = $shopIds ?: BeesBlogMultistore::getSubmittedShopIds($this->table);
        $result = BeesBlogImage::deleteForShops(
            BeesBlogImage::ENTITY_CATEGORY,
            (int) $idBeesBlogCategory,
            $shopIds
        );
        if ($deleteLegacy) {
            BeesBlogImage::deleteLegacyImages(BeesBlogImage::ENTITY_CATEGORY, (int) $idBeesBlogCategory);
        }

        return $result;
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
        if (Tools::isSubmit(BeesBlogCategory::PRIMARY)) {
            return false;
        }

        $this->normalizeTranslatedCategoryRequest();
        $this->validateRules();
        if ($this->errors) {
            $this->display = 'add';
            return false;
        }

        $blogCategory = new BeesBlogCategory();
        $this->copyFromPost($blogCategory, $this->table);
        $this->normalizeTranslatedCategoryFields($blogCategory);

        if (!$blogCategory->id_parent) {
            $blogCategory->id_parent = 0;
        }
        if (!$blogCategory->position) {
            $blogCategory->position = 0;
        }
        $shopIds = BeesBlogMultistore::getSubmittedShopIds($this->table);
        if (!$shopIds) {
            $this->errors[] = $this->l('No authorized shop is available in the selected context.');
            return false;
        }
        $blogCategory->id_shop_list = $shopIds;
        $blogCategory->id_shop = (int) reset($shopIds);
        if (!$this->validateParentAssociations($blogCategory, $shopIds) || !$this->validateShopSlugs($blogCategory, $shopIds)) {
            $this->display = 'add';
            return false;
        }
        if ($blogCategory->add()) {
            if (!$this->processImage($_FILES, $blogCategory->id, $shopIds)) {
                return false;
            }
            $this->confirmations[] = $this->l('Successfully added a new category');

            if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$blogCategory->id.'&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&token='.$this->token;
            }

            return true;
        }

        $this->errors[] = $this->l('Unable to add new category');

        return false;
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
            $this->page_header_toolbar_btn['new_category'] = [
                'href' => static::$currentIndex.'&add'.BeesBlogCategory::TABLE.'&token='.$this->token,
                'desc' => $this->l('Add new category', null, null, false),
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
        if (!Tools::isSubmit(BeesBlogCategory::PRIMARY)) {
            return false;
        }

        $this->normalizeTranslatedCategoryRequest();
        $this->validateRules();
        if ($this->errors) {
            $this->display = 'edit';
            return false;
        }

        $idCategory = (int) Tools::getValue(BeesBlogCategory::PRIMARY);
        $idShop = BeesBlogMultistore::getObjectRepresentativeShopId(BeesBlogCategory::TABLE, BeesBlogCategory::PRIMARY, $idCategory);
        $blogCategory = new BeesBlogCategory($idCategory, null, $idShop);
        if (!Validate::isLoadedObject($blogCategory)) {
            $this->errors[] = $this->l('The category cannot be loaded in the selected shop context.');
            return false;
        }
        $this->copyFromPost($blogCategory, $this->table);
        $this->normalizeTranslatedCategoryFields($blogCategory);
        if (!$blogCategory->id_parent) {
            $blogCategory->id_parent = 0;
        }

        if (!$blogCategory->position) {
            $blogCategory->position = 0;
        }
        $shopIds = BeesBlogMultistore::getSubmittedShopIds($this->table);
        if (!$shopIds) {
            $this->errors[] = $this->l('No authorized shop is available in the selected context.');
            return false;
        }
        $blogCategory->id_shop_list = $shopIds;
        $blogCategory->id_shop = $idShop;
        if (!$this->validateParentAssociations($blogCategory, $shopIds) || !$this->validateShopSlugs($blogCategory, $shopIds)) {
            $this->display = 'edit';
            return false;
        }

        if ($blogCategory->update()) {
            if (!$this->processImage($_FILES, $blogCategory->id, $shopIds)) {
                return false;
            }
            $this->confirmations[] = $this->l('Successfully updated the category');

            if (Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
                $this->redirect_after = static::$currentIndex.'&'.$this->identifier.'='.$blogCategory->id.'&update'.$this->table.'&token='.$this->token;
            } else {
                $this->redirect_after = static::$currentIndex.'&token='.$this->token;
            }
            return true;
        }
        $this->errors[] = $this->l('Unable to update category');

        return false;
    }

    /**
     * Process delete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function processDelete()
    {
        $idCategory = (int) Tools::getValue(BeesBlogCategory::PRIMARY);
        $idShop = BeesBlogMultistore::getObjectRepresentativeShopId(BeesBlogCategory::TABLE, BeesBlogCategory::PRIMARY, $idCategory);
        $blogCategory = new BeesBlogCategory($idCategory, null, $idShop);
        $shopIds = BeesBlogMultistore::getSubmittedShopIds($this->table);
        $blogCategory->id_shop_list = $shopIds;

        $postCount = BeesBlogPost::countByCategoryInShops($idCategory, $shopIds);
        if ((int) $postCount != 0) {
            $this->errors[] = $this->l('You need to delete all posts associate with this category .');

            return false;
        } else {
            if (!$blogCategory->delete()) {
                $this->errors[] = $this->l('An error occurred while deleting the object.').' <strong>'.$this->table.' ('. Db::getInstance()->getMsgError().')</strong>';

                return false;
            } else {
                BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_CATEGORY, $blogCategory->id, $shopIds);
                if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    'SELECT 1 FROM `'._DB_PREFIX_.BeesBlogCategory::TABLE.'` WHERE `'.BeesBlogCategory::PRIMARY.'` = '.(int) $blogCategory->id
                )) {
                    BeesBlogImage::deleteLegacyImages(BeesBlogImage::ENTITY_CATEGORY, $blogCategory->id);
                }

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));

                return true;
            }
        }
    }

    /**
     * @param bool $status
     * @return bool
     * @throws PrestaShopException
     */
    protected function processBulkStatusSelection($status)
    {
        $result = true;
        $shopIds = BeesBlogMultistore::getContextShopIds();
        foreach ((array) $this->boxes as $idCategory) {
            $idCategory = (int) $idCategory;
            $idShop = BeesBlogMultistore::getObjectRepresentativeShopId($this->table, $this->identifier, $idCategory);
            $category = new BeesBlogCategory($idCategory, null, $idShop);
            if (!Validate::isLoadedObject($category)) {
                $result = false;
                continue;
            }
            $category->id_shop_list = $shopIds;
            $category->setFieldsToUpdate(['active' => true]);
            $category->active = (int) $status;
            $result = $category->update() && $result;
        }

        return $result;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    protected function processBulkDelete()
    {
        $result = true;
        $shopIds = BeesBlogMultistore::getContextShopIds();
        foreach ((array) $this->boxes as $idCategory) {
            $idCategory = (int) $idCategory;
            if (BeesBlogPost::countByCategoryInShops($idCategory, $shopIds)) {
                $result = false;
                $this->errors[] = sprintf($this->l('Category #%d still contains posts in this shop context.'), $idCategory);
                continue;
            }
            $idShop = BeesBlogMultistore::getObjectRepresentativeShopId($this->table, $this->identifier, $idCategory);
            $category = new BeesBlogCategory($idCategory, null, $idShop);
            $category->id_shop_list = $shopIds;
            if (!Validate::isLoadedObject($category) || !$category->delete()) {
                $result = false;
                $this->errors[] = sprintf($this->l('Cannot delete category #%d.'), $idCategory);
                continue;
            }
            BeesBlogImage::deleteForShops(BeesBlogImage::ENTITY_CATEGORY, $idCategory, $shopIds);
            if (!Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT 1 FROM `'._DB_PREFIX_.BeesBlogCategory::TABLE.'` WHERE `'.BeesBlogCategory::PRIMARY.'` = '.$idCategory
            )) {
                BeesBlogImage::deleteLegacyImages(BeesBlogImage::ENTITY_CATEGORY, $idCategory);
            }
        }

        if ($result) {
            $this->redirect_after = static::$currentIndex.'&conf=2&token='.$this->token;
        }

        return $result;
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
    static public function getParentTitleById($id, $row = []) {

        return BeesBlogCategory::getNameById($id, null, isset($row['list_shop_id']) ? (int) $row['list_shop_id'] : null);
    }

    /**
     * @return array[]
     * @throws PrestaShopException
     */
    public static function getCategoriesName() {

      $result = [
          0 => [
              'id_bees_blog_category' => '0',
              'title' =>  'Root'
          ]
      ];

      $langId = Context::getContext()->language->id;
      $categories = BeesBlogCategory::getCategories($langId, 0, 0, false, true, [BeesBlogCategory::PRIMARY, 'title']);

      foreach ($categories as $value) {
          $result[] = $value;
      }

      return $result;
    }

    /**
     * @return string | null
     * @throws PrestaShopException
     */
    protected function getPreviewUrl()
    {
        $id = (int)Tools::getValue(BeesBlogCategory::PRIMARY);
        if ($id) {
            $idShop = BeesBlogMultistore::getObjectRepresentativeShopId(BeesBlogCategory::TABLE, BeesBlogCategory::PRIMARY, $id);
            $category = new BeesBlogCategory($id, $this->context->language->id, $idShop);
            if (Validate::isLoadedObject($category)) {
                return $category->link;
            }
        }
        return null;
    }

    /**
     * @param bool $opt
     * @return BeesBlogCategory|bool
     * @throws PrestaShopException
     */
    protected function loadObject($opt = false)
    {
        if ($this->object) {
            return $this->object;
        }

        $id = Tools::getIntValue($this->identifier);
        if ($id && Validate::isUnsignedId($id)) {
            $idShop = BeesBlogMultistore::getObjectRepresentativeShopId($this->table, $this->identifier, $id);
            $this->object = new BeesBlogCategory($id, null, $idShop);
            if (Validate::isLoadedObject($this->object)) {
                return $this->object;
            }
            $this->errors[] = Tools::displayError('The object cannot be loaded in the selected shop context.');
            return false;
        }

        if ($opt) {
            $this->object = new BeesBlogCategory(null, null, BeesBlogMultistore::getRepresentativeShopId());
            return $this->object;
        }

        $this->errors[] = Tools::displayError('The object identifier is missing or invalid.');
        return false;
    }

    /** @return void */
    protected function normalizeTranslatedCategoryRequest()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultTitle = trim((string) Tools::getValue('title_'.$defaultLang, ''));
        foreach (Language::getLanguages(false, false, true) as $idLang) {
            $idLang = (int) $idLang;
            $titleKey = 'title_'.$idLang;
            $rewriteKey = 'link_rewrite_'.$idLang;
            $title = trim((string) Tools::getValue($titleKey, $defaultTitle));
            if ($title === '') {
                $title = $defaultTitle;
            }
            if (!isset($_POST[$titleKey]) || trim((string) $_POST[$titleKey]) === '') {
                $_POST[$titleKey] = $title;
            }
            $rewrite = trim((string) Tools::getValue($rewriteKey, ''));
            $_POST[$rewriteKey] = Tools::link_rewrite($rewrite !== '' ? $rewrite : $title);
        }
    }

    /**
     * @param BeesBlogCategory $category
     * @param int[] $shopIds
     * @return bool
     * @throws PrestaShopException
     */
    protected function validateShopSlugs(BeesBlogCategory $category, array $shopIds)
    {
        $conflicts = BeesBlogMultistore::findSlugConflicts(
            BeesBlogCategory::TABLE,
            BeesBlogCategory::PRIMARY,
            (int) $category->id,
            (array) $category->link_rewrite,
            $shopIds
        );
        foreach ($conflicts as $conflict) {
            $shop = Shop::getShop((int) $conflict['id_shop']);
            $this->errors[] = sprintf(
                $this->l('URL rewrite "%s" is already used in shop "%s" for this language.'),
                $conflict['slug'],
                isset($shop['name']) ? $shop['name'] : (int) $conflict['id_shop']
            );
        }

        return !$conflicts;
    }

    /**
     * @param BeesBlogCategory $category
     * @param int[] $shopIds
     * @return bool
     * @throws PrestaShopException
     */
    protected function validateParentAssociations(BeesBlogCategory $category, array $shopIds)
    {
        if (!(int) $category->id_parent) {
            return true;
        }
        if ((int) $category->id_parent === (int) $category->id) {
            $this->errors[] = $this->l('A category cannot be its own parent.');
            return false;
        }

        $missing = BeesBlogMultistore::getMissingAssociationShopIds(
            BeesBlogCategory::TABLE,
            BeesBlogCategory::PRIMARY,
            (int) $category->id_parent,
            $shopIds
        );
        foreach ($missing as $idShop) {
            $shop = Shop::getShop($idShop);
            $this->errors[] = sprintf(
                $this->l('The selected parent category is not associated with shop "%s".'),
                isset($shop['name']) ? $shop['name'] : $idShop
            );
        }

        return !$missing;
    }

    /**
     * @param BeesBlogCategory $blogCategory
     *
     * @return void
     * @throws PrestaShopException
     */
    protected function normalizeTranslatedCategoryFields(BeesBlogCategory $blogCategory)
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultTitle = trim((string) $this->getTranslatedFieldValue($blogCategory->title, $defaultLang));

        if (!is_array($blogCategory->title)) {
            $blogCategory->title = [];
        }
        if (!is_array($blogCategory->link_rewrite)) {
            $blogCategory->link_rewrite = [];
        }

        foreach (Language::getLanguages(false, false, true) as $idLang) {
            $idLang = (int) $idLang;
            $title = trim((string) $this->getTranslatedFieldValue($blogCategory->title, $idLang));
            if ($title === '') {
                $title = $defaultTitle;
            }
            $blogCategory->title[$idLang] = $title;

            $linkRewrite = trim((string) $this->getTranslatedFieldValue($blogCategory->link_rewrite, $idLang));
            if ($linkRewrite === '') {
                $linkRewrite = Tools::link_rewrite($title ?: $defaultTitle);
            }
            $blogCategory->link_rewrite[$idLang] = $linkRewrite;
        }
    }

    /**
     * @param mixed $value
     * @param int $idLang
     *
     * @return mixed
     */
    protected function getTranslatedFieldValue($value, $idLang)
    {
        if (is_array($value)) {
            return $value[$idLang] ?? null;
        }

        return $value;
    }
}
