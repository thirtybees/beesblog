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

if (!defined('_TB_VERSION_')) {
    exit;
}

use BeesBlogModule\BeesBlogCategory;
use BeesBlogModule\BeesBlogPost;

/**
 * Class AdminBeesBlogPostController
 *
 * @since 1.0.0
 */
class AdminBeesBlogPostController extends \ModuleAdminController
{
    protected $blogPost = null;

    /**
     * AdminBeesBlogPostController constructor.
     *
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
        $this->context = \Context::getContext();

        // Only display this page in single store context
        $this->multishop_context = Shop::CONTEXT_SHOP;

        // Make sure that when we save the `BeesBlogCategory` ObjectModel, the `_shop` table is set, too (primary => id_shop relation)
        Shop::addTableAssociation(BeesBlogPost::TABLE, ['type' => 'shop']);

        // We are going to use multilang ObjectModels, but there is just one language to display
        $this->lang = true;

        $this->fields_list = [
            BeesBlogPost::PRIMARY => [
                'title' => $this->l('ID'),
                'width' => 50,
                'type' => 'text',
                'orderby' => true,
                'filter' => true,
                'search' => true,
            ],
            'viewed' => [
                'title' => $this->l('View'),
                'width' => 50,
                'type' => 'text',
                'lang' => true,
                'orderby' => true,
                'filter' => false,
                'search' => false,
            ],
            'image' => [
                'title' => $this->l('Image'),
                'image' => _PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE,
                'orderby' => false,
                'search' => false,
                'width' => 200,
                'align' => 'center',
                'filter' => false,
            ],
            'title' => [
                'title' => $this->l('Title'),
                'width' => 440,
                'type' => 'text',
                'lang' => true,
                'orderby' => true,
                'filter' => true,
                'search' => true,
            ],
            'date_add' => [
                'title' => $this->l('Posted Date'),
                'width' => 100,
                'type' => 'date',
                'lang' => true,
                'orderby' => true,
                'filter' => true,
                'search' => true,
            ],
            'active' => [
                'title' => $this->l('Status'),
                'width' => '70',
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => true,
                'filter' => true,
                'search' => true,
            ],
        ];

        // Set some default HelperList sortings
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'bees_blog_post_shop sbs ON a.id_bees_blog_post = sbs.id_bees_blog_post AND sbs.id_shop IN('.implode(',', \Shop::getContextListShopID()).')';
        $this->_defaultOrderBy = 'a.id_bees_blog_post';
        $this->_defaultOrderWay = 'DESC';

        if (\Shop::isFeatureActive() && \Shop::getContext() != \Shop::CONTEXT_SHOP) {
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

        parent::__construct();
    }

    /**
     * Render list
     *
     * @return false|string
     *
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
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (\Tools::isSubmit('deletebees_blog_post') && \Tools::getValue(BeesBlogPost::PRIMARY) != '') {
            $beesBlogPost = new BeesBlogPost((int) \Tools::getValue(BeesBlogPost::PRIMARY));

            if (!$beesBlogPost->delete()) {
                $this->errors[] = \Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</b>';
            } else {
                \Hook::exec('actionsbdeletepost', ['BeesBlogPost' => $beesBlogPost]);
                \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogPost'));
            }
        } elseif (\Tools::getValue('deleteImage')) {
            $this->processForceDeleteImage();
            if (\Tools::isSubmit('forcedeleteImage')) {
                \Tools::redirectAdmin(self::$currentIndex.'&token='.\Tools::getAdminTokenLite('AdminBeesBlogPost').'&conf=7');
            }
        } else {
            parent::postProcess();
        }
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
     * @param int $idBeesBlogPost
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function deleteImage($idBeesBlogPost)
    {
        return true;
        // Delete base image
        if (file_exists(BeesBlog::POST_IMG_DIR.$idBeesBlogPost.'.jpg')) {
            unlink(BeesBlog::POST_IMG_DIR.$idBeesBlogPost.'.jpg');
        } else {
            return false;
        }

        // now we need to delete the image type of post

        $filesToDelete = [];

        // Delete auto-generated images
        $imageTypes = ImageType::getAllImagesFromType('post');
        foreach ($imageTypes as $imageType) {
            $filesToDelete[] = BeesBlog::POST_IMG_DIR.$idBeesBlogPost.'-'.$imageType['type_name'].'.jpg';
        }

        // Delete tmp images
        $filesToDelete[] = _PS_TMP_IMG_DIR_.'bees_blog_post_'.$idBeesBlogPost.'.jpg';
        $filesToDelete[] = _PS_TMP_IMG_DIR_.'bees_blog_post_mini_'.$idBeesBlogPost.'.jpg';

        foreach ($filesToDelete as $file) {
            if (file_exists($file) && !@unlink($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $files $_FILES array
     * @param int   $id    Object ID
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function processImage($files, $id)
    {
        if (isset($files['image']) && isset($files['image']['tmp_name']) && !empty($files['image']['tmp_name'])) {
            if ($error = ImageManager::validateUpload($files['image'], 4000000)) {
                return $this->errors[] = $this->l('Invalid image');
            } else {
                $path = _PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE.'/'.$id.'.'.$this->imageType;

                $tempName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                if (!$tempName) {
                    return false;
                }

                if (!move_uploaded_file($files['image']['tmp_name'], $tempName)) {
                    return false;
                }

                // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
                if (!ImageManager::checkImageMemoryLimit($tempName)) {
                    $this->errors[] = $this->l('Due to memory limit restrictions, the image could not be processed. Please increase your memory_limit value via your server\'s configuration settings. ');
                }


                // Copy new image
                if (empty($this->errors) && !ImageManager::resize($tempName, $path)
                ) {
                    $this->errors[] = $this->l('An error occurred while uploading the image.');
                }

                if (count($this->errors)) {
                    return false;
                }
                if ($this->afterImageUpload()) {
                    unlink($tempName);
                    //  return true;
                }

                $postTypes = ImageType::getImagesTypes('post');
                foreach ($postTypes as $imageType) {
                    $destination = _PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE.'/'.$id.'-'.stripslashes($imageType['type_name']).'.jpg';
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                    \ImageManager::resize(
                        $tempName,
                        $destination,
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    );
                }

                @unlink(_PS_TMP_IMG_DIR_.'bees_blog_post_'.$id.'.jpg');
                @unlink(_PS_TMP_IMG_DIR_.'bees_blog_post_mini_'.$id.'_'.$this->context->shop->id.'.jpg');

                return true;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        if (!($obj = $this->loadObject(true)) || !empty($this->errors)) {
            return '';
        }

        $id = (int) \Tools::getValue(BeesBlogPost::PRIMARY);
        $filePath = _PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE."{$id}.jpg";

        $imageUrl = \ImageManager::thumbnail($filePath, $this->table."_{$id}.jpg", 200, 'jpg', true, true);
        $imageSize = file_exists(_PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE."{$id}.jpg") ? filesize(_PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE."{$id}.jpg") / 1000 : false;

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Blog Post'),
            ],
            'input' => [
                [
                    'type' => 'hidden',
                    'name' => 'post_type',
                    'default_value' => 0,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Blog title'),
                    'name' => 'title',
                    'id' => 'name',
                    'class' => 'copyMeta2friendlyURL',
                    'size' => 60,
                    'required' => true,
                    'desc' => $this->l('Enter the title of your blog post'),
                    'lang' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Content'),
                    'name' => 'content',
                    'lang' => true,
                    'rows' => 10,
                    'cols' => 62,
                    'class' => 'rte',
                    'autoload_rte' => true,
                    'required' => true,
                    'hint' => [
                        $this->l('Enter the content of your post'),
                        $this->l('Invalid characters:').' <>;=#{}',
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Summary'),
                    'name' => 'summary',
                    'rows' => 10,
                    'cols' => 62,
                    'lang' => true,
                    'required' => true,
                    'hint' => [
                        $this->l('Enter a short description of your post'),
                    ],
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Image'),
                    'name' => 'image',
                    'display_image' => true,
                    'image' => $imageUrl ? $imageUrl : false,
                    'size' => $imageSize,
                    'delete_url' => self::$currentIndex.'&'.$this->identifier.'='.\Tools::getValue(BeesBlogPost::PRIMARY).'&token='.$this->token.'&deleteImage=1',
                    'hint' => $this->l('Upload an image from your computer.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Blog Category'),
                    'name' => 'id_category',
                    'options' => [
                        'query' => BeesBlogCategory::getCategories($this->context->language->id, 0, 0, false, true, ['id', 'title']),
                        'id' => 'id',
                        'name' => 'title',
                    ],
                    'desc' => $this->l('Select Your Parent Category'),
                ],
                [
                    'type' => 'tags',
                    'label' => $this->l('Keywords'),
                    'name' => 'keywords',
                    'lang' => true,
                    'hint' => [
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Link Rewrite'),
                    'name' => 'link_rewrite',
                    'size' => 60,
                    'lang' => true,
                    'required' => false,
                    'hint' => $this->l('Only letters and the hyphen (-) character are allowed.'),
                ],
                [
                    'type' => 'tags',
                    'label' => $this->l('Tag'),
                    'name' => 'tags',
                    'size' => 60,
                    'lang' => true,
                    'required' => false,
                    'hint' => [
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Comment Status'),
                    'name' => 'comment_status',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'active',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'desc' => $this->l('You can enable or disable comments'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'active',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'label' => $this->l('Available for these languages'),
                    'name' => 'lang_active',
                    'multiple' => true,
                    'values' => [
                        'query' => \Language::getLanguages(false),
                        'id' => 'id_lang',
                        'name' => 'name',
                    ],
                    'expand' => (count(\Language::getLanguages(false)) > 10) ? [
                        'print_total' => count(\Language::getLanguages(false)),
                        'default' => 'show',
                        'show' => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                        'hide' => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
                    ] : null,
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Publish date'),
                    'name' => 'date_add',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Featured'),
                    'name' => 'is_featured',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'is_featured',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => 'is_featured',
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

        $image = \ImageManager::thumbnail(
            _PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE.'/'.$obj->id_bees_blog_post.'.jpg',
            $this->table.'_'.(int) $obj->id_bees_blog_post.'.'.$this->imageType,
            350,
            $this->imageType,
            true
        );

        $this->fields_value = [
            'image' => $image ? $image : false,
            'size' => $image ? filesize(_PS_IMG_DIR_.'/'.BeesBlogPost::IMAGE_TYPE.'/'.$obj->id_bees_blog_post.'.jpg') / 1000 : false,
        ];

        if (\Tools::getValue(BeesBlogPost::PRIMARY) != '' && \Tools::getValue(BeesBlogPost::PRIMARY) != null) {
            foreach (\Language::getLanguages(false) as $lang) {
                $this->fields_value['tags'][(int) $lang['id_lang']] = BeesBlogPost::getTagsByLang((int) \Tools::getValue(BeesBlogPost::PRIMARY), (int) $lang['id_lang']);
            }
        }

        foreach (\Language::getLanguages(true) as $language) {
            $this->fields_value['lang_active_'.(int) $language['id_lang']] = (bool) BeesBlogPost::getLangActive(\Tools::getValue(BeesBlogPost::PRIMARY), $language['id_lang']);
        }

        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int) \Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');

        return parent::renderForm();
    }

    /**
     *
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUI('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * Update tags
     *
     * @param array        $languages
     * @param BeesBlogPost $post
     *
     * @return bool
     */
    public function updateTags($languages, $post)
    {
        $tagSuccess = true;

        if (!BeesBlogPost::deleteTags((int) $post->id)) {
            $this->errors[] = \Tools::displayError('An error occurred while attempting to delete previous tags.');
        }
        foreach ($languages as $language) {
            if ($value = \Tools::getValue('tags_'.$language['id_lang'])) {
                $tagSuccess &= BeesBlogPost::addTags((int) $post->id, $value, $language['id_lang']);
            }
        }

        if (!$tagSuccess) {
            $this->errors[] = \Tools::displayError('An error occurred while adding tags.');
        }

        return $tagSuccess;
    }

    public function processAdd()
    {
        if (Tools::isSubmit(BeesBlogPost::PRIMARY)) {
            return false;
        }

        $blogPost = new BeesBlogPost();
        $this->copyFromPost($blogPost, $this->table);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach (BeesBlogPost::$definition['fields'] as $name => $field) {
            if (isset($field['lang']) && $field['lang']) {
                foreach (Language::getLanguages() as $language) {
                    if ((int) $language['id_lang'] !== $idLangDefault) {
                        $defaultValue = '';
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
                                break;
                        }
                        if (!is_array($blogPost->{$name})) {
                            $blogPost->$name = [
                                $idLangDefault => $defaultValue,
                            ];
                        } elseif (!isset($blogPost->$name[$idLangDefault])) {
                            $blogPost->$name[$idLangDefault] = $defaultValue;
                        }

                        $blogPost->$name[$language['id_lang']] = $blogPost->$name[$idLangDefault];
                    }
                }
            }
        }

        if (!$blogPost->published) {
            $blogPost->published = date('Y-m-d H:i:s');
        }
        $blogPost->id_employee = $this->context->employee->id;
        $blogPost->viewed = 0;
        foreach ($blogPost->lang_active as &$active) {
            $active = ($active === 'on' ? true : false);
        }
        $blogPost->id_shop = (int) Context::getContext()->shop->id;

        return $blogPost->add();
    }

    public function processUpdate()
    {
        if (Tools::isSubmit(BeesBlogPost::PRIMARY)) {
            return false;
        }

        $blogPost = new BeesBlogPost((int) Tools::getValue(BeesBlogPost::PRIMARY));
        $this->copyFromPost($blogPost, $this->table);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach (BeesBlogPost::$definition['fields'] as $name => $field) {
            if (isset($field['lang']) && $field['lang']) {
                foreach (Language::getLanguages() as $language) {
                    if ((int) $language['id_lang'] !== $idLangDefault) {
                        if (!is_array($blogPost->{$name})) {
                            $blogPost->{$name} = [
                                $idLangDefault => '',
                            ];
                        }
                        $blogPost->{$name}[$language['id_lang']] = $blogPost->{$name}[$idLangDefault];
                    }
                }
            }
        }

        // TODO: check if link_rewrite is unique

        if (!$blogPost->published) {
            $blogPost->published = date('Y-m-d H:i:s');
        }
        $blogPost->id_employee = $this->context->employee->id;
        $blogPost->id_shop = (int) Context::getContext()->shop->id;

        return $blogPost->update();
    }
}
