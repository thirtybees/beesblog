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
        $id = (int) \Tools::getValue(BeesBlogCategory::PRIMARY);

        $imageUrl = \ImageManager::thumbnail(BeesBlogCategory::getImageLink($id), $this->table."_{$id}.jpg", 200, 'jpg', true, true);
        $imageSize = file_exists(BeesBlogCategory::getImageLink($id)) ? filesize(BeesBlogCategory::getImageLink($id)) / 1000 : false;

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
                    'label'         => $this->l('Category image'),
                    'name'          => 'category_image',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'delete_url'    => self::$currentIndex.'&'.$this->identifier.'='.\Tools::getValue(BeesBlogCategory::PRIMARY).'&token='.$this->token.'&deleteImage=1',
                    'hint'          => $this->l('Upload an image from your computer.'),
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
        if (\Tools::isSubmit('deleteImage')) {
               $this->processForceDeleteImage();
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
     * @return bool
     */
    public function processImage($files, $id)
    {
        $categoryImageInput = 'category_image';

        if (isset($files[$categoryImageInput]) && isset($files[$categoryImageInput]['tmp_name']) && !empty($files[$categoryImageInput]['tmp_name'])) {
            if ($error = \ImageManager::validateUpload($files[$categoryImageInput], 4000000)) {
                $this->errors[] = $this->l('Invalid image');

                return false;
            } else {
                $ext = substr($files[$categoryImageInput]['name'], strrpos($files[$categoryImageInput]['name'], '.') + 1);
                $path = _PS_IMG_DIR_."beesblog/categories/";
                if (!file_exists($path)) {
                    if (!mkdir($path, 0777, true)) {
                        $this->errors[] = sprintf($this->l('Unable to create image directory: `%s`'), $path);
                    }
                }
                $path .= "$id.$ext";
                if (!move_uploaded_file($files[$categoryImageInput]['tmp_name'], $path)) {
                    $this->errors[] = $this->l('An error occurred while attempting to upload the file.');

                    return false;
                } else {
                    $imageTypes = BeesBlogImageType::getImagesTypes('categories');
                    foreach ($imageTypes as $imageType) {
                        $dir = _PS_IMG_DIR_."beesblog/categories/{$id}-{$imageType['name']}.{$ext}";
                        if (file_exists($dir)) {
                            unlink($dir);
                        }
                        \ImageManager::resize(
                            $path,
                            _PS_IMG_DIR_."beesblog/categories/{$id}-{$imageType['name']}.{$ext}",
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
     * @since 1.0.0
     */
    public function processForceDeleteImage()
    {
        $blogPost = $this->loadObject(true);

        if (\Validate::isLoadedObject($blogPost)) {
            $this->deleteImage($blogPost->id);
        }
    }

    /**
     * @param int $idBeesBlogCategory
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function deleteImage($idBeesBlogCategory)
    {
        $deleted = false;
        // Delete base image
        foreach (['png', 'jpg'] as $extension) {
            if (file_exists(_PS_IMG_DIR_."beesblog/categories/{$idBeesBlogCategory}.{$extension}")) {
                unlink(_PS_IMG_DIR_."beesblog/categories/{$idBeesBlogCategory}.{$extension}");
            }

            // now we need to delete the image type of post

            $filesToDelete = [];

            // Delete auto-generated images
            $imageTypes = BeesBlogImageType::getImagesTypes('categories');
            foreach ($imageTypes as $imageType) {
                $filesToDelete[] = _PS_IMG_DIR_."beesblog/categories/{$idBeesBlogCategory}-{$imageType['name']}.{$extension}";
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
     * Process add
     *
     * @return bool
     *
     * @since 1.0.0
     */
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

        $this->processImage($_FILES, $blogCategory->id);

        // TODO: check if link_rewrite is unique

        if ($blogCategory->add()) {
            $this->confirmations[] = $this->l('Successfully added a new category');

            return true;
        }

        $this->errors[] = $this->l('Unable to add new category');

        return false;
    }

    /**
     * Process update
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processUpdate()
    {
        if (!Tools::isSubmit(BeesBlogCategory::PRIMARY)) {
            return false;
        }

        $blogCategory = new BeesBlogCategory((int) Tools::getValue(BeesBlogCategory::PRIMARY));
        $this->copyFromPost($blogCategory, $this->table);
        if (!$blogCategory->id_parent) {
            $blogCategory->id_parent = 0;
        }

        if (!$blogCategory->position) {
            $blogCategory->position = 0;
        }
        $blogCategory->id_shop = (int) Context::getContext()->shop->id;

        $this->processImage($_FILES, $blogCategory->id);

        // TODO: check if link_rewrite is unique

        if ($blogCategory->add()) {
            $this->confirmations[] = $this->l('Successfully updated the category');

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
     * @since 1.0.0
     */
    public function processDelete()
    {
        $idLang = (int) \Context::getContext()->language->id;
        $blogCategory = new BeesBlogCategory((int) \Tools::getValue(BeesBlogCategory::PRIMARY));

        $postCount = (int) $blogCategory->getPostsInCategory($idLang, 0, 0, true);
        if ((int) $postCount != 0) {
            $this->errors[] = $this->l('You need to delete all posts associate with this category .');

            return false;
        } else {
            if (!$blogCategory->delete()) {
                $this->errors[] = $this->l('An error occurred while deleting the object.').' <strong>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</strong>';

                return false;
            } else {
                \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogCategory'));

                return true;
            }
        }
    }
}
