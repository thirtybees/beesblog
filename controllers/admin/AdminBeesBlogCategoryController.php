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

if (!defined('_TB_VERSION_')) {
    exit;
}

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

/**
 * Class AdminBeesBlogCategoryController
 */
class AdminBeesBlogCategoryController extends \ModuleAdminController
{
    public $module;

    /**
     * AdminBeesBlogCategoryController constructor.
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
        $this->context = \Context::getContext();

        // Only display this page in single store context
        $this->multishop_context = Shop::CONTEXT_SHOP;

        // Make sure that when we save the `BeesBlogCategory` ObjectModel, the `_shop` table is set, too (primary => id_shop relation)
        Shop::addTableAssociation(BeesBlogCategory::TABLE, ['type' => 'shop']);

        // We are going to use multilang ObjectModels but there is just one language to display
        $this->lang = true;

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
                'lang'  => true,
            ],
            'active'                  => [
                'title'   => $this->l('Status'),
                'width'   => '70',
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        // With all this info set, it's about time to call the parent constructor
        parent::__construct();
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Blog category'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Title'),
                    'name'     => 'title',
                    'size'     => 60,
                    'required' => true,
                    'desc'     => $this->l('Enter Your Category Name'),
                    'lang'     => true,
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Summary'),
                    'name'         => 'summary',
                    'lang'         => true,
                    'rows'         => 10,
                    'cols'         => 62,
                    'class'        => 'rte',
                    'autoload_rte' => true,
                    'required'     => false,
                    'desc'         => $this->l('Enter a summary'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category image'),
                    'name'          => 'category_image',
                    'display_image' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Keywords'),
                    'name'     => 'keywords',
                    'lang'     => true,
                    'size'     => 60,
                    'required' => false,
                    'desc'     => $this->l('Enter your category`s keywords. Separated by commas (,)'),
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
                    'type'    => 'select',
                    'label'   => $this->l('Parent Category'),
                    'name'    => 'id_parent',
                    'options' => [
                        'query' => BeesBlogCategory::getCategories($this->context->language->id, 0, 0, false, true, [BeesBlogCategory::PRIMARY, 'title']),
                        'id'    => BeesBlogCategory::PRIMARY,
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
        ];

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        return parent::renderForm();
    }

    /**
     * @return false|string
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
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (\Tools::isSubmit('status'.BeesBlogCategory::TABLE) && \Tools::getValue(BeesBlogCategory::PRIMARY)) {
            if ($this->tabAccess['edit'] === '1') {
                if (\Validate::isLoadedObject($object = $this->loadObject())) {
                    if ($object->toggleStatus()) {
                        \Hook::exec('actionsbtogglecat', ['BeesBlogCat' => $this->object]);
                        $identifier = ((int) $object->id_parent ? '&id_bees_blog_category='.(int) $object->id_parent : '');
                        \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));
                    } else {
                        $this->errors[] = \Tools::displayError('An error occurred while updating the status.');
                    }
                } else {
                    $this->errors[] = \Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.\Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = \Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (\Tools::isSubmit(BeesBlogCategory::TABLE.'Orderby') && \Tools::isSubmit(BeesBlogCategory::TABLE.'Orderway')) {
            $this->_defaultOrderBy = \Tools::getValue(BeesBlogCategory::TABLE.'Orderby');
            $this->_defaultOrderWay = \Tools::getValue(BeesBlogCategory::TABLE.'Orderway');
        } else {
            parent::postProcess();
        }
    }

    /**
     * Process category image
     *
     * @param array $files
     * @param int   $id
     *
     * @return string
     */
    public function processImageCategory($files, $id)
    {
        return true;
        if (isset($files['category_image']) && isset($files['category_image']['tmp_name']) && !empty($files['category_image']['tmp_name'])) {
            if ($error = \ImageManager::validateUpload($files['category_image'], 4000000)) {
                return $this->errors[] = $this->l('Invalid image');
            } else {
                $ext = substr($files['category_image']['name'], strrpos($files['category_image']['name'], '.') + 1);
                $fileName = $id.'.'.$ext;
                $path = _PS_MODULE_DIR_.'beesblog/images/category/'.$fileName;
                if (!move_uploaded_file($files['category_image']['tmp_name'], $path)) {
                    return $this->errors[] = $this->l('An error occurred while attempting to upload the file.');
                } else {
                    if (\Configuration::hasContext('category_image', null, Shop::getContext())
                        && \Configuration::get('BLOCKBANNER_IMG') != $fileName
                    ) {
                        @unlink(__DIR__.'/'.\Configuration::get('BLOCKBANNER_IMG'));
                    }

                    $imageTypes = ImageType::getAllImagesFromType('category');
                    foreach ($imageTypes as $imageType) {
                        $dir = _PS_MODULE_DIR_.'beesblog/images/category/'.$id.'-'.stripslashes($imageType['type_name']).'.jpg';
                        if (file_exists($dir)) {
                            unlink($dir);
                        }
                    }
                    foreach ($imageTypes as $imageType) {
                        \ImageManager::resize(
                            $path,
                            _PS_MODULE_DIR_.'beesblog/images/category/'.$id.'-'.stripslashes($imageType['type_name']).'.jpg',
                            (int) $imageType['width'],
                            (int) $imageType['height']
                        );
                    }
                }
            }
        }

        return '';
    }

    public function processAdd()
    {
        if (Tools::isSubmit(BeesBlogCategory::PRIMARY)) {
            return false;
        }

        $blogCategory = new BeesBlogCategory();
        $this->copyFromPost($blogCategory, $this->table);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach (BeesBlogCategory::$definition['fields'] as $name => $field) {
            if (isset($field['lang']) && $field['lang']) {
                foreach (Language::getLanguages() as $language) {
                    if ((int) $language['id_lang'] !== $idLangDefault) {
                        $blogCategory->{$name}[$language['id_lang']] = $blogCategory->{$name}[$idLangDefault];
                    }
                }
            }
        }

        if (!$blogCategory->id_parent) {
            $blogCategory->id_parent = 0;
        }
        if (!$blogCategory->position) {
            $blogCategory->position = 0;
        }
        $blogCategory->id_shop = (int) Context::getContext()->shop->id;

        // TODO: check if link_rewrite is unique

        return $blogCategory->add();
    }

    public function processUpdate()
    {
        if (Tools::isSubmit(BeesBlogCategory::PRIMARY)) {
            return false;
        }

        $blogCategory = new BeesBlogCategory((int) Tools::getValue(BeesBlogCategory::PRIMARY));
        $isRoot = (int) $blogCategory->id_parent === 0;
        $this->copyFromPost($blogCategory, $this->table);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach (BeesBlogCategory::$definition['fields'] as $name => $field) {
            if (isset($field['lang']) && $field['lang']) {
                foreach (Language::getLanguages() as $language) {
                    if ((int) $language['id_lang'] !== $idLangDefault) {
                        $blogCategory->{$name}[$language['id_lang']] = $blogCategory->{$name}[$idLangDefault];
                    }
                }
            }
        }

        if ($isRoot) {
            $blogCategory->id_parent = 0;
        }
        if (!$blogCategory->position) {
            $blogCategory->position = 0;
        }
        $blogCategory->id_shop = (int) Context::getContext()->shop->id;

        // TODO: check if link_rewrite is unique

        return $blogCategory->add();
    }

    public function processDelete()
    {
        $idLang = (int) \Context::getContext()->language->id;
        $postCount = (int) BeesBlogPost::getPostCountByCategory($idLang, \Tools::getValue(BeesBlogCategory::PRIMARY));
        if ((int) $postCount != 0) {
            $this->errors[] = $this->l('You need to delete all posts associate with this category .');
        } else {
            $blogCategory = new BeesBlogCategory((int) \Tools::getValue(BeesBlogCategory::PRIMARY));
            if (!$blogCategory->delete()) {
                $this->errors[] = $this->l('An error occurred while deleting the object.').' <strong>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</strong>';
            } else {
                \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));
            }
        }
    }
}
