<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogImageType;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class AdminBeesBlogImagesController
 *
 * @since 1.0.0
 */
class AdminBeesBlogImagesController extends ModuleAdminController
{
    // @codingStandardsIgnoreStart
    /** @var int $start_time */
    protected $start_time = 0;
    /** @var int $max_execution_time */
    protected $max_execution_time = 7200;
    /** @var bool $display_move */
    protected $display_move;
    // @codingStandardsIgnoreEnd

    /**
     * AdminImagesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = BeesBlogImageType::TABLE;
        $this->className = 'BeesBlogModule\\BeesBlogImageType';
        $this->lang = false;

        // Retrieve the context from a static context, just because
        $this->context = \Context::getContext();

        // Only display this page in single store context
        $this->multishop_context = Shop::CONTEXT_SHOP;

        // Make sure that when we save the `BeesBlogCategory` ObjectModel, the `_shop` table is set, too (primary => id_shop relation)
        Shop::addTableAssociation(BeesBlogImageType::TABLE, ['type' => 'shop']);

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        // Refresh/restore basic image types
        BeesBlogImageType::installBasics();

        // Disable delete button for mandatory types
        $this->list_skip_actions['delete'] = BeesBlogImageType::getBasicTypeIds();

        $this->bulk_actions = [
            'delete' =>
                [
                    'text'    => $this->l('Delete selected'),
                    'confirm' => $this->l('Delete selected items?'),
                    'icon'    => 'icon-trash',
                ],
        ];

        $this->fields_list = [
            BeesBlogImageType::PRIMARY => ['title' => $this->l('ID'),         'align' => 'center', 'class' => 'fixed-width-xs'],
            'name'                     => ['title' => $this->l('Name')],
            'width'                    => ['title' => $this->l('Width'),      'suffix' => ' px'],
            'height'                   => ['title' => $this->l('Height'),     'suffix' => ' px'],
            'posts'                    => ['title' => $this->l('Posts'),   'align' => 'center', 'active' => 'posts', 'type' => 'bool', 'orderby' => false],
            'categories'               => ['title' => $this->l('Categories'), 'align' => 'center', 'active' => 'categories', 'type' => 'bool', 'orderby' => false],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Image type'),
                'icon'  => 'icon-picture',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name for the image type'),
                    'name'     => 'name',
                    'required' => true,
                    'hint'     => $this->l('Letters, underscores and hyphens only (e.g. "small_custom", "cart_medium", "large", "thickbox_extra-large").'),
                    'disabled' => in_array(Tools::getValue(BeesBlogImageType::PRIMARY), BeesBlogImageType::getBasicTypeIds()),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Width'),
                    'name'      => 'width',
                    'required'  => true,
                    'maxlength' => 5,
                    'suffix'    => $this->l('pixels'),
                    'hint'      => $this->l('Maximum image width in pixels.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Height'),
                    'name'      => 'height',
                    'required'  => true,
                    'maxlength' => 5,
                    'suffix'    => $this->l('pixels'),
                    'hint'      => $this->l('Maximum image height in pixels.'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Posts'),
                    'name'     => 'posts',
                    'required' => false,
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Post images.'),
                    'values'   => [
                        [
                            'id'    => 'post_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'post_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'disabled' => in_array(Tools::getValue(BeesBlogImageType::PRIMARY), BeesBlogImageType::getBasicTypeIds()),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Categories'),
                    'name'     => 'categories',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'hint'     => $this->l('This type will be used for Category images.'),
                    'values'   => [
                        [
                            'id'    => 'categories_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'categories_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'disabled' => in_array(Tools::getValue(BeesBlogImageType::PRIMARY), BeesBlogImageType::getBasicTypeIds()),
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        parent::__construct();
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitRegenerate'.$this->table)) {
            if ($this->tabAccess['edit'] === '1') {
                if ($this->regenerateThumbnails(Tools::getValue('type'), Tools::getValue('erase'))) {
                    Tools::redirectAdmin(static::$currentIndex.'&conf=9'.'&token='.$this->token);
                }
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to edit this.');
            }

            return false;
        } elseif (Tools::isSubmit(BeesBlogImageType::PRIMARY) && Tools::isSubmit('categories'.BeesBlogImageType::TABLE)) {
            $imageType = new BeesBlogImageType((int) Tools::getValue(BeesBlogImageType::PRIMARY), null, $this->context->shop->id);
            if (Validate::isLoadedObject($imageType)) {
                if (in_array($imageType->id, BeesBlogImageType::getBasicTypeIds())) {
                    $this->errors[] = $this->l('Cannot toggle the status for mandatory image types');

                    return false;
                }

                $imageType->categories = !$imageType->categories;

                if ($imageType->update()) {
                    $this->confirmations[] = sprintf($this->l('Successfully toggled `%s` status'), 'categories');

                    return true;
                } else {
                    $this->errors[] = sprintf($this->l('Unable to toggle `%s` status'), 'categories');
                }
            } else {
                $this->errors[] = sprintf($this->l('Unable to toggle `%s` status'), 'categories');
            }

            return false;
        } elseif (Tools::isSubmit(BeesBlogImageType::PRIMARY) && Tools::isSubmit('posts'.BeesBlogImageType::TABLE)) {
            $imageType = new BeesBlogImageType((int) Tools::getValue(BeesBlogImageType::PRIMARY), null, $this->context->shop->id);
            if (Validate::isLoadedObject($imageType)) {
                if (in_array($imageType->id, BeesBlogImageType::getBasicTypeIds())) {
                    $this->errors[] = $this->l('Cannot toggle the status for mandatory image types');

                    return false;
                }

                $imageType->posts = !$imageType->posts;

                if ($imageType->update()) {
                    $this->confirmations[] = sprintf($this->l('Successfully toggled `%s` status'), 'posts');

                    return true;
                } else {
                    $this->errors[] = sprintf($this->l('Unable to toggle `%s` status'), 'posts');

                    return false;
                }
            } else {
                $this->errors[] = sprintf($this->l('Unable to toggle `%s` status'), 'posts');

                return false;
            }
        } else {
            return parent::postProcess();
        }
    }

    /**
     * Regenerate thumbnails
     *
     * @param string $type
     * @param bool   $deleteOldImages
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function regenerateThumbnails($type = 'all', $deleteOldImages = false)
    {
        $this->start_time = time();
        @ini_set('max_execution_time', $this->max_execution_time); // ini_set may be disabled, we need the real value
        $this->max_execution_time = (int) ini_get('max_execution_time');
        $languages = Language::getLanguages(false);

        $process = [
            ['type' => 'posts',      'dir' => _PS_IMG_DIR_.'/beesblog/posts/'],
            ['type' => 'categories', 'dir' => _PS_IMG_DIR_.'/beesblog/categories/'],
        ];

        // Launching generation process
        foreach ($process as $proc) {
            if ($type != 'all' && $type != $proc['type']) {
                continue;
            }

            // Getting format generation
            $formats = BeesBlogImageType::getImagesTypes($proc['type']);
            if ($type != 'all') {
                $format = strval(Tools::getValue('format_'.$type));
                if ($format != 'all') {
                    foreach ($formats as $k => $form) {
                        if ($form['id_image_type'] != $format) {
                            unset($formats[$k]);
                        }
                    }
                }
            }

            if ($deleteOldImages) {
                $this->deleteOldImages($proc['dir'], $formats);
            }
            if (($return = $this->regenerateNewImages($proc['dir'], $formats)) === true) {
                if (!count($this->errors)) {
                    $this->errors[] = sprintf(Tools::displayError('Cannot write images for this type: %s. Please check the %s folder\'s writing permissions.'), $proc['type'], $proc['dir']);
                }
            } elseif ($return == 'timeout') {
                $this->errors[] = Tools::displayError('Only part of the images have been regenerated. The server timed out before finishing.');
            } else {
                if (!count($this->errors)) {
                    if ($this->regenerateNoPictureImages($proc['dir'], $formats, $languages)) {
                        $this->errors[] = sprintf(Tools::displayError('Cannot write "No picture" image to (%s) images folder. Please check the folder\'s writing permissions.'), $proc['type']);
                    }
                }
            }
        }

        return (count($this->errors) > 0 ? false : true);
    }

    /**
     * Delete resized image then regenerate new one with updated settings
     *
     * @param string $dir
     * @param array  $type
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function deleteOldImages($dir, $type)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $toDel = scandir($dir);

        foreach ($toDel as $d) {
            foreach ($type as $imageType) {
                if (preg_match('/^[0-9]+\-'.$imageType['name'].'\.jpg$/', $d)
                    || (count($type) > 1 && preg_match('/^[0-9]+\-[_a-zA-Z0-9-]*\.jpg$/', $d))
                    || preg_match('/^([[:lower:]]{2})\-default\-'.$imageType['name'].'\.jpg$/', $d)
                ) {
                    if (file_exists($dir.$d)) {
                        unlink($dir.$d);
                    }
                }
            }
        }
    }

    /**
     * Regenerate images
     *
     * @param string $dir
     * @param array $formats
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    protected function regenerateNewImages($dir, $formats)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $generateHighDpiImages = (bool) Configuration::get('PS_HIGHT_DPI');

        $formattedThumbScene = ImageType::getFormatedName('thumb_scene');
        $formattedMedium = ImageType::getFormatedName('medium');
        foreach (scandir($dir) as $image) {
            if (preg_match('/^[0-9]*\.jpg$/', $image)) {
                foreach ($formats as $imageType) {
                    // Customizable writing dir
                    $newDir = $dir;
                    if ($imageType['name'] == $formattedThumbScene) {
                        $newDir .= 'thumbs/';
                    }
                    if (!file_exists($newDir)) {
                        continue;
                    }

                    if (($dir == _PS_CAT_IMG_DIR_) && ($imageType['name'] == $formattedMedium) && is_file(_PS_CAT_IMG_DIR_.str_replace('.', '_thumb.', $image))) {
                        $image = str_replace('.', '_thumb.', $image);
                    }

                    if (!file_exists($newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'.jpg')) {
                        if (!file_exists($dir.$image) || !filesize($dir.$image)) {
                            $this->errors[] = sprintf(Tools::displayError('Source file does not exist or is empty (%s)'), $dir.$image);
                        } elseif (!ImageManager::resize($dir.$image, $newDir.substr(str_replace('_thumb.', '.', $image), 0, -4).'-'.stripslashes($imageType['name']).'.jpg', (int) $imageType['width'], (int) $imageType['height'])) {
                            $this->errors[] = sprintf(Tools::displayError('Failed to resize image file (%s)'), $dir.$image);
                        }

                        if ($generateHighDpiImages) {
                            if (!ImageManager::resize($dir.$image, $newDir.substr($image, 0, -4).'-'.stripslashes($imageType['name']).'2x.jpg', (int) $imageType['width'] * 2, (int) $imageType['height'] * 2)) {
                                $this->errors[] = sprintf(Tools::displayError('Failed to resize image file to high resolution (%s)'), $dir.$image);
                            }
                        }
                    }
                    // stop 4 seconds before the timeout, just enough time to process the end of the page on a slow server
                    if (time() - $this->start_time > $this->max_execution_time - 4) {
                        return 'timeout';
                    }
                }
            }
        }

        return (bool) count($this->errors);
    }

    /**
     * Regenerate no-pictures images
     *
     * @param $dir
     * @param $type
     * @param $languages
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function regenerateNoPictureImages($dir, $type, $languages)
    {
        $errors = false;
        $generateHighDpiImages = (bool) Configuration::get('PS_HIGHT_DPI');

        foreach ($type as $imageType) {
            foreach ($languages as $language) {
                $file = $dir.$language['iso_code'].'.jpg';
                if (!file_exists($file)) {
                    $file = _PS_PROD_IMG_DIR_.Language::getIsoById((int) Configuration::get('PS_LANG_DEFAULT')).'.jpg';
                }
                if (!file_exists($dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'.jpg')) {
                    if (!ImageManager::resize($file, $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'.jpg', (int) $imageType['width'], (int) $imageType['height'])) {
                        $errors = true;
                    }

                    if ($generateHighDpiImages) {
                        if (!ImageManager::resize($file, $dir.$language['iso_code'].'-default-'.stripslashes($imageType['name']).'2x.jpg', (int) $imageType['width'] * 2, (int) $imageType['height'] * 2)) {
                            $errors = true;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Initialize page header toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_image_type'] = [
                'href' => static::$currentIndex.'&add'.BeesBlogImageType::TABLE.'&token='.$this->token,
                'desc' => $this->l('Add new image type', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Initialize content
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        if ($this->display != 'edit' && $this->display != 'add') {
            $this->initRegenerate();

            $this->context->smarty->assign(
                [
                    'display_regenerate' => true,
                    'display_move'       => $this->display_move,
                ]
            );
        }

        if ($this->display == 'edit') {
            $this->warnings[] = $this->l('After modification, do not forget to regenerate thumbnails');
        }

        parent::initContent();
    }

    /**
     * Init display for the thumbnails regeneration block
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initRegenerate()
    {
        $types = [
            'posts'      => $this->l('Posts'),
            'categories' => $this->l('Categories'),
        ];

        $formats = [];
        foreach ($types as $i => $type) {
            $formats[$i] = BeesBlogImageType::getImagesTypes($i);
        }

        $this->context->smarty->assign(
            [
                'types'   => $types,
                'formats' => $formats,
            ]
        );
    }

    /**
     * Child validation
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function childValidation()
    {
        if (!Tools::getValue(BeesBlogImageType::PRIMARY) && Validate::isImageTypeName($typeName = Tools::getValue('name')) && BeesBlogImageType::typeAlreadyExists($typeName)) {
            $this->errors[] = Tools::displayError('This name already exists.');
        }
    }
}
