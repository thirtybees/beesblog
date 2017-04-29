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

if (!defined('_TB_VERSION_')) {
    exit;
}

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogImageType;
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
        $this->table = BeesBlogCategory::TABLE;
        $this->className = 'BeesBlogModule\\BeesBlogCategory';

        $this->bootstrap = true;

        $this->context = \Context::getContext();

        $this->multishop_context = Shop::CONTEXT_SHOP;
        $this->lang = true;

        parent::__construct();
        $this->fields_list = [
            BeesBlogCategory::PRIMARY => [
                'title' => $this->l('Id'),
                'width' => 100,
                'type'  => 'text',
            ],
            'meta_title'              => [
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
                    'label'    => $this->l('Meta title'),
                    'name'     => 'meta_title',
                    'size'     => 60,
                    'required' => true,
                    'desc'     => $this->l('Enter Your Category Name'),
                    'lang'     => true,
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
                    'desc'         => $this->l('Enter Your Category Description'),
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Category Image'),
                    'name'          => 'category_image',
                    'display_image' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Meta keywords'),
                    'name'     => 'meta_keyword',
                    'lang'     => true,
                    'size'     => 60,
                    'required' => false,
                    'desc'     => $this->l('Enter Your Category Meta Keyword. Separated by comma(,)'),
                ],
                [
                    'type'     => 'textarea',
                    'label'    => $this->l('Meta Description'),
                    'name'     => 'meta_description',
                    'rows'     => 10,
                    'cols'     => 62,
                    'lang'     => true,
                    'required' => false,
                    'desc'     => $this->l('Enter Your Category Meta Description'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Link Rewrite'),
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
                        'query' => BeesBlogCategory::getAllCategories(),
                        'id'    => BeesBlogCategory::PRIMARY,
                        'name'  => 'meta_title',
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
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (\Tools::isSubmit('delete'.BeesBlogCategory::TABLE) && \Tools::getValue(BeesBlogCategory::PRIMARY)) {
            $idLang = (int) \Context::getContext()->language->id;
            $catpost = (int) BeesBlogPost::getPostCountByCategory($idLang, \Tools::getValue(BeesBlogCategory::PRIMARY));
            if ((int) $catpost != 0) {
                $this->errors[] = \Tools::displayError('You need to delete all posts associate with this category .');
            } else {
                $blogCategory = new BeesBlogCategory((int) \Tools::getValue(BeesBlogCategory::PRIMARY));
                if (!$blogCategory->delete()) {
                    $this->errors[] = \Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</b>';
                } else {
                    \Hook::exec('actionsbdeletecat', ['BlogCategory' => $blogCategory]);
                    \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));
                }
            }
        } elseif (\Tools::isSubmit('submitAdd'.BeesBlogCategory::TABLE)) {
            parent::validateRules();
            if (count($this->errors)) {
                return;
            }
            if (!$idBeesBlogCategory = (int) \Tools::getValue(BeesBlogCategory::PRIMARY)) {
                $blogCategory = new BeesBlogCategory();

                foreach (\Language::getLanguages(false) as $language) {
                    $title = str_replace('"', '', htmlspecialchars_decode(html_entity_decode(\Tools::getValue('meta_title_'.$language['id_lang']))));
                    $blogCategory->meta_title[$language['id_lang']] = $title;
                    $blogCategory->meta_keyword[$language['id_lang']] = \Tools::getValue('meta_keyword_'.$language['id_lang']);
                    $blogCategory->meta_description[$language['id_lang']] = \Tools::getValue('meta_description_'.$language['id_lang']);
                    $blogCategory->description[$language['id_lang']] = \Tools::getValue('description_'.$language['id_lang']);
                    if (\Tools::getValue('link_rewrite_'.$language['id_lang']) == '' && \Tools::getValue('link_rewrite_'.$language['id_lang']) == null) {
                        $blogCategory->link_rewrite[$language['id_lang']] = str_replace(
                            [' ', ':', '\\', '/', '#', '!', '*', '.', '?'],
                            '-',
                            \Tools::getValue('meta_title_'.$language['id_lang'])
                        );
                    } else {
                        $blogCategory->link_rewrite[$language['id_lang']] = str_replace(
                            [' ', ':', '\\', '/', '#', '!', '*', '.', '?'],
                            '-',
                            \Tools::getValue('link_rewrite_'.$language['id_lang'])
                        );
                    }
                }

                $blogCategory->id_parent = \Tools::getValue('id_parent');
                $blogCategory->position = \Tools::getValue('position');
                $blogCategory->desc_limit = \Tools::getValue('desc_limit');
                $blogCategory->active = \Tools::getValue('active');
                $blogCategory->date_add = date('Y-m-d H:i:s');
                $blogCategory->date_upd = date('Y-m-d H:i:s');

                if (!$blogCategory->save()) {
                    $this->errors[] = \Db::getInstance()->getMsgError();
                    $this->errors[] = \Tools::displayError('An error has occurred: Can\'t save the current object');
                } else {
                    \Hook::exec('actionsbnewcat', ['BlogCategory' => $blogCategory]);
                    $this->processImageCategory($_FILES, $blogCategory->id);
                    \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));
                }
            } elseif ($idBeesBlogCategory = \Tools::getValue(BeesBlogCategory::PRIMARY)) {
                $blogCategory = new BeesBlogCategory($idBeesBlogCategory);
                $languages = \Language::getLanguages(false);
                foreach ($languages as $language) {
                    $title = str_replace('"', '', htmlspecialchars_decode(html_entity_decode(\Tools::getValue('meta_title_'.$language['id_lang']))));
                    $blogCategory->meta_title[$language['id_lang']] = $title;
                    $blogCategory->meta_keyword[$language['id_lang']] = \Tools::getValue('meta_keyword_'.$language['id_lang']);
                    $blogCategory->meta_description[$language['id_lang']] = \Tools::getValue('meta_description_'.$language['id_lang']);
                    $blogCategory->description[$language['id_lang']] = \Tools::getValue('description_'.$language['id_lang']);
                    $blogCategory->link_rewrite[$language['id_lang']] = str_replace(
                        [' ', ':', '\\', '/', '#', '!', '*', '.', '?'],
                        '-',
                        \Tools::getValue('link_rewrite_'.$language['id_lang'])
                    );
                }

                $blogCategory->id_parent = \Tools::getValue('id_parent');
                $blogCategory->position = \Tools::getValue('position');
                $blogCategory->desc_limit = \Tools::getValue('desc_limit');
                $blogCategory->active = \Tools::getValue('active');
                $blogCategory->date_upd = date('y-m-d H:i:s');

                if (!$blogCategory->update()) {
                    $this->errors[] = \Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</b>';
                } else {
                    \Hook::exec('actionsbupdatecat', ['BlogCategory' => $blogCategory]);
                }
                $this->processImageCategory($_FILES, $blogCategory->id_bees_blog_category);
            }
        } elseif (\Tools::isSubmit('status'.BeesBlogCategory::TABLE) && \Tools::getValue(BeesBlogCategory::PRIMARY)) {
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

                    $imageTypes = BeesBlogImageType::getAllImagesFromType('category');
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
}
