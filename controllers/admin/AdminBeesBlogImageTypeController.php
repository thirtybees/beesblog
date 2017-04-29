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

/**
 * Class AdminBeesBlogImageTypeController
 *
 * @since 1.0.0
 */
class AdminBeesBlogImageTypeController extends ModuleAdminController
{
    /**
     * AdminBeesBlogImageTypeController constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->table = 'bees_blog_imagetype';
        $this->className = 'BeesBlogModule\\BeesBlogImageType';
        $this->module = 'beesblog';
        $this->lang = false;
        $this->context = Context::getContext();
        $this->bootstrap = true;
        $this->fields_list = [
            'id_bees_blog_imagetype' => [
                'title' => $this->l('Id'),
                'width' => 100,
                'type'  => 'text',
            ],
            'type_name'              => [
                'title' => $this->l('Type Name'),
                'width' => 350,
                'type'  => 'text',
            ],
            'width'                  => [
                'title' => $this->l('Width'),
                'width' => 60,
                'type'  => 'text',
            ],
            'height'                 => [
                'title' => $this->l('Height'),
                'width' => 60,
                'type'  => 'text',
            ],
            'type'                   => [
                'title' => $this->l('Type'),
                'width' => 220,
                'type'  => 'text',
            ],
            'active'                 => [
                'title'   => $this->l('Status'),
                'width'   => 60,
                'align'   => 'center',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];
        parent::__construct();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Blog Category'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Image Type Name'),
                    'name'     => 'type_name',
                    'size'     => 60,
                    'required' => true,
                    'desc'     => $this->l('Enter Your Image Type Name Here'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('width'),
                    'name'     => 'width',
                    'size'     => 15,
                    'required' => true,
                    'desc'     => $this->l('Image height in px'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Height'),
                    'name'     => 'height',
                    'size'     => 15,
                    'required' => true,
                    'desc'     => $this->l('Image height in px'),
                ],
                [
                    'type'     => 'select',
                    'label'    => $this->l('Type'),
                    'name'     => 'type',
                    'required' => true,
                    'options'  => [
                        'query' => [
                            [
                                'id_option' => 'post',
                                'name'      => 'Post',
                            ],
                            [
                                'id_option' => 'Category',
                                'name'      => 'category',
                            ],
                            [
                                'id_option' => 'Author',
                                'name'      => 'author',
                            ],
                        ],
                        'id'    => 'id_option',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'     => 'radio',
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
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        if (!($blogImageType = $this->loadObject(true))) {
            return null;
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
            'class' => 'button',
        ];

        return parent::renderForm();
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
}
