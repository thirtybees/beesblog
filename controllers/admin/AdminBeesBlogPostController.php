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
use BeesBlogModule\BeesBlogImageType;
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
        $this->table = BeesBlogPost::TABLE;
        $this->className = 'BeesBlogModule\\BeesBlogPost';

        $this->lang = true;
        $this->context = \Context::getContext();
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->bootstrap = true;

        parent::__construct();
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
                'image' => BeesBlog::POST_IMG_DIR,
                'orderby' => false,
                'search' => false,
                'width' => 200,
                'align' => 'center',
                'filter' => false,
            ],
            'meta_title' => [
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
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'bees_blog_post_shop sbs ON a.id_bees_blog_post = sbs.id_bees_blog_post AND sbs.id_shop IN('.implode(',', \Shop::getContextListShopID()).')';
        $this->_defaultOrderBy = 'a.id_bees_blog_post';
        $this->_defaultOrderWay = 'DESC';

        if (\Shop::isFeatureActive() && \Shop::getContext() != \Shop::CONTEXT_SHOP) {
            $this->_group = 'GROUP BY a.bees_blog_post';
        }

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
        } elseif (\Tools::isSubmit('submitAddbees_blog_post')) {
            if (!$idBeesBlogPost = (int) \Tools::getValue(BeesBlogPost::PRIMARY)) {
                $beesBlogPost = new BeesBlogPost();
                $idLangDefault = \Configuration::get('PS_LANG_DEFAULT');
                $languages = \Language::getLanguages(false);
                foreach ($languages as $language) {
                    $title = \Tools::getValue('meta_title_'.$language['id_lang']);
                    $beesBlogPost->meta_title[$language['id_lang']] = $title;
                    $beesBlogPost->meta_keyword[$language['id_lang']] = (string) \Tools::getValue('meta_keyword_'.$language['id_lang']);
                    $beesBlogPost->meta_description[$language['id_lang']] = \Tools::getValue('meta_description_'.$language['id_lang']);
                    $beesBlogPost->short_description[$language['id_lang']] = (string) \Tools::getValue('short_description_'.$language['id_lang']);
                    $beesBlogPost->content[$language['id_lang']] = \Tools::getValue('content_'.$language['id_lang']);
                    if (\Tools::getValue('link_rewrite_'.$language['id_lang']) == '' && \Tools::getValue('link_rewrite_'.$language['id_lang']) == null) {
                        $beesBlogPost->link_rewrite[$language['id_lang']] = \Tools::link_rewrite(\Tools::getValue('meta_title_'.$idLangDefault));
                    } else {
                        $beesBlogPost->link_rewrite[$language['id_lang']] = \Tools::link_rewrite(\Tools::getValue('link_rewrite_'.$language['id_lang']));
                    }
                    $beesBlogPost->lang_active[$language['id_lang']] = \Tools::getValue('lang_active_'.(int) $language['id_lang']) == 'on';
                }

                $beesBlogPost->position = 0;
                $beesBlogPost->active = (bool) \Tools::getValue('active');

                $beesBlogPost->id_category = \Tools::getValue('id_category');
                $beesBlogPost->id_employee = (int) $this->context->employee->id;

                $beesBlogPost->available = 1;
                $beesBlogPost->is_featured = \Tools::getValue('is_featured');
                $beesBlogPost->viewed = 1;

                $beesBlogPost->post_type = \Tools::getValue('post_type');

                if (!$beesBlogPost->save()) {
                    $this->errors[] = \Tools::displayError('An error has occurred: Can\'t save the current object');
                } else {
                    \Hook::exec('actionsbnewpost', ['BeesBlogPost' => $beesBlogPost]);
                    $this->updateTags($languages, $beesBlogPost);
                    $this->processImage($_FILES, $beesBlogPost->id);
                    \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogPost'));
                }
            } elseif ($idBeesBlogPost = \Tools::getValue(BeesBlogPost::PRIMARY)) {
                $beesBlogPost = new BeesBlogPost($idBeesBlogPost);
                $languages = \Language::getLanguages(false);
                foreach ($languages as $language) {
                    $title = \Tools::getValue('meta_title_'.$language['id_lang']);
                    $beesBlogPost->meta_title[$language['id_lang']] = $title;
                    $beesBlogPost->meta_keyword[$language['id_lang']] = \Tools::getValue('meta_keyword_'.$language['id_lang']);
                    $beesBlogPost->meta_description[$language['id_lang']] = \Tools::getValue('meta_description_'.$language['id_lang']);
                    $beesBlogPost->short_description[$language['id_lang']] = \Tools::getValue('short_description_'.$language['id_lang']);
                    $beesBlogPost->content[$language['id_lang']] = \Tools::getValue('content_'.$language['id_lang']);
                    $beesBlogPost->link_rewrite[$language['id_lang']] = \Tools::link_rewrite(\Tools::getValue('link_rewrite_'.$language['id_lang']));
                    $beesBlogPost->lang_active[$language['id_lang']] = \Tools::getValue('lang_active_'.(int) $language['id_lang']) == 'on';
                }
                $beesBlogPost->is_featured = \Tools::getValue('is_featured');
                $beesBlogPost->active = \Tools::getValue('active');
                $beesBlogPost->id_category = \Tools::getValue('id_category');
                $beesBlogPost->comments_allowed = \Tools::getValue('comment_status');
                $beesBlogPost->id_employee = $this->context->employee->id;
                if (\Tools::getValue('date_add')) {
                    $beesBlogPost->date_add = date('y-m-d H:i:s', strtotime(\Tools::getValue('date_add')));
                }
                $beesBlogPost->date_upd = date('y-m-d H:i:s');
                if (!$beesBlogPost->update()) {
                    $this->errors[] = \Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.' ('.\Db::getInstance()->getMsgError().')</b>';
                } else {
                    \Hook::exec('actionsbupdatepost', ['BeesBlogPost' => $beesBlogPost]);
                }
               // $this->updateTags($languages, $beesBlogPost);
                $this->processImage($_FILES, $beesBlogPost->id);

                \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogPost'));
            }
        } elseif (\Tools::isSubmit('statusbees_blog_post') && \Tools::getValue($this->identifier)) {
            if ($this->tabAccess['edit'] === '1') {
                if (\Validate::isLoadedObject($object = $this->loadObject())) {
                    if ($object->toggleStatus()) {
                        \Hook::exec('actionsbtogglepost', ['BeesBlogPost' => $this->object]);
                        \Tools::redirectAdmin($this->context->link->getAdminLink('AdminBeesBlogPost'));
                    } else {
                        $this->errors[] = \Tools::displayError('An error occurred while updating the status.');
                    }
                } else {
                    $this->errors[] = \Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.\Tools::displayError('(cannot load object)');
                }
            } else {
                $this->errors[] = \Tools::displayError('You do not have permission to edit this.');
            }
        } elseif (\Tools::isSubmit('bees_blog_postOrderby') && \Tools::isSubmit('bees_blog_postOrderway')) {
            $this->_defaultOrderBy = \Tools::getValue('bees_blog_postOrderby');
            $this->_defaultOrderWay = \Tools::getValue('bees_blog_postOrderway');
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
        // Delete base image
        if (file_exists(BeesBlog::POST_IMG_DIR.$idBeesBlogPost.'.jpg')) {
            unlink(BeesBlog::POST_IMG_DIR.$idBeesBlogPost.'.jpg');
        } else {
            return false;
        }

        // now we need to delete the image type of post

        $filesToDelete = [];

        // Delete auto-generated images
        $imageTypes = BeesBlogImageType::getAllImagesFromType('post');
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
     * @param $files
     * @param $id
     *
     * @return bool|string
     */
    public function processImage($files, $id)
    {
        if (isset($files['image']) && isset($files['image']['tmp_name']) && !empty($files['image']['tmp_name'])) {
            if ($error = \ImageManager::validateUpload($files['image'], 4000000)) {
                return $this->errors[] = $this->l('Invalid image');
            } else {
                $path = BeesBlog::POST_IMG_DIR.$id.'.'.$this->imageType;

                $tempName = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                if (!$tempName) {
                    return false;
                }

                if (!move_uploaded_file($files['image']['tmp_name'], $tempName)) {
                    return false;
                }

                // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
                if (!\ImageManager::checkImageMemoryLimit($tempName)) {
                    $this->errors[] = \Tools::displayError('Due to memory limit restrictions, this image cannot be loaded. Please increase your memory_limit value via your server\'s configuration settings. ');
                }


                // Copy new image
                if (empty($this->errors) && !ImageManager::resize($tempName, $path)
                ) {
                    $this->errors[] = Tools::displayError('An error occurred while uploading the image.');
                }

                if (count($this->errors)) {
                    return false;
                }
                if ($this->afterImageUpload()) {
                    unlink($tempName);
                    //  return true;
                }

                $postTypes = BeesBlogImageType::getAllImagesFromType('post');
                foreach ($postTypes as $imageType) {
                    $dir = BeesBlog::POST_IMG_DIR.$id.'-'.stripslashes($imageType['type_name']).'.jpg';
                    if (file_exists($dir)) {
                        unlink($dir);
                    }
                }
                foreach ($postTypes as $imageType) {
                    \ImageManager::resize(
                        $path,
                        BeesBlog::POST_IMG_DIR.$id.'-'.stripslashes($imageType['type_name']).'.jpg',
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    );
                }

                @unlink(_PS_TMP_IMG_DIR_.'bees_blog_post_'.$id.'.jpg');
                @unlink(_PS_TMP_IMG_DIR_.'bees_blog_post_mini_'.$id.'_'.$this->context->shop->id.'.jpg');
            }
        }
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $image = BeesBlog::POST_IMG_DIR.$obj->id.'.jpg';

        $imageUrl = \ImageManager::thumbnail($image, $this->table.'_'.\Tools::getValue(BeesBlogPost::PRIMARY).'.jpg', 200, 'jpg', true, true);
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

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
                    'name' => 'meta_title',
                    'id' => 'name',
                    'class' => 'copyMeta2friendlyURL',
                    'size' => 60,
                    'required' => true,
                    'desc' => $this->l('Enter Your Blog Post Title'),
                    'lang' => true,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'content',
                    'lang' => true,
                    'rows' => 10,
                    'cols' => 62,
                    'class' => 'rte',
                    'autoload_rte' => true,
                    'required' => true,
                    'hint' => [
                        $this->l('Enter Your Post Description'),
                        $this->l('Invalid characters:').' <>;=#{}',
                    ],
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Feature Image'),
                    'name' => 'image',
                    'display_image' => true,
                    'image' => $imageUrl ? $imageUrl : false,
                    'size' => $imageSize,
                    'delete_url' => self::$currentIndex.'&'.$this->identifier.'='.\Tools::getValue(BeesBlogPost::PRIMARY).'&token='.$this->token.'&deleteImage=1',
                    'hint' => $this->l('Upload a feature image from your computer.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Blog Category'),
                    'name' => 'id_category',
                    'options' => [
                        'query' => BeesBlogCategory::getAllCategories(),
                        'id' => BeesBlogCategory::PRIMARY,
                        'name' => 'meta_title',
                    ],
                    'desc' => $this->l('Select Your Parent Category'),
                ],
                [
                    'type' => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name' => 'meta_keywords',
                    'lang' => true,
                    'hint' => [
                        $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Short Description'),
                    'name' => 'short_description',
                    'rows' => 10,
                    'cols' => 62,
                    'lang' => true,
                    'required' => true,
                    'hint' => [
                        $this->l('Enter Your Post Short Description'),
                    ],
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Meta Description'),
                    'name' => 'meta_description',
                    'rows' => 10,
                    'cols' => 62,
                    'lang' => true,
                    'required' => false,
                    'desc' => $this->l('Enter Your Post Meta Description'),
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
            BeesBlog::POST_IMG_DIR.$obj->id_bees_blog_post.'.jpg',
            $this->table.'_'.(int) $obj->id_bees_blog_post.'.'.$this->imageType,
            350,
            $this->imageType,
            true
        );

        $this->fields_value = [
            'image' => $image ? $image : false,
            'size' => $image ? filesize(BeesBlog::POST_IMG_DIR.$obj->id_bees_blog_post.'.jpg') / 1000 : false,
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
}
