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

namespace BeesBlogModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogCategory
 */
class BeesBlogCategory extends \ObjectModel
{
    const TABLE = 'bees_blog_category';
    const PRIMARY = 'id_bees_blog_category';
    const LANG_TABLE = 'bees_blog_category_lang';
    const SHOP_TABLE = 'bees_blog_category_shop';
    const IMAGE_TYPE = 'beesblog_category';

    // @codingStandardsIgnoreStart
    public static $definition = [
        'table'          => self::TABLE,
        'primary'        => self::PRIMARY,
        'multilang'      => true,
        'fields'         => [
            'id_parent'        => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true, 'default' => '0',                   'db_type' => 'INT(11) UNSIGNED'],
            'position'         => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true, 'default' => '1',                   'db_type' => 'INT(11) UNSIGNED'],
            'active'           => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool',        'required' => true, 'default' => '1',                   'db_type' => 'TINYINT(1)'],
            'date_add'         => ['type' => self::TYPE_DATE,                   'validate' => 'isString',      'required' => true, 'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'date_upd'         => ['type' => self::TYPE_DATE,                   'validate' => 'isString',      'required' => true, 'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                     'db_type' => 'VARCHAR(255)'],
            'meta_keyword'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => false,                                    'db_type' => 'VARCHAR(255)'],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => false,                                    'db_type' => 'VARCHAR(512)'],
            'description'      => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isCleanHtml',   'required' => false,                                    'db_type' => 'TEXT'],
            'link_rewrite'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                     'db_type' => 'VARCHAR(256)'],
        ],
    ];
    /** @var int $id_bees_blog_category */
    public $id_bees_blog_category;
    /** @var int $id_parent */
    public $id_parent;
    /** @var int $position */
    public $position;
    /** @var bool $active */
    public $active = true;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    /** @var array $meta_title */
    public $meta_title;
    /** @var array $meta_keyword */
    public $meta_keyword;
    /** @var array $meta_description */
    public $meta_description;
    /** @var array $description */
    public $description;
    /** @var array $link_rewrite */
    public $link_rewrite;
    // @codingStandardsIgnoreEnd

    /**
     * BeesBlogPost constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        $this->image_dir = _PS_IMG_DIR_.'/beesblog/'.static::IMAGE_TYPE;
    }

    /**
     * @param int|null $idLang Language ID
     *
     * @return BeesBlogCategory|false
     */
    public static function getRootCategory($idLang = null)
    {
        if ($idLang == null) {
            $idLang = (int) \Context::getContext()->language->id;
        }
        $idShop = (int) \Context::getContext()->shop->id;

        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(
            static::LANG_TABLE,
            'sbcl',
            'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`'
        );
        $sql->innerJoin(
            static::SHOP_TABLE,
            'sbcs',
            'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`'
        );
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`id_shop` = '.(int) $idShop);
        $sql->where('sbc.`active` = 1');
        $sql->where('sbc.`id_parent` = 0');
        $rootCategory = new self();
        $rootCategory->hydrate(\Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql));

        if (\Validate::isLoadedObject($rootCategory)) {
            return $rootCategory;
        }

        return false;
    }

    /**
     * Get BeesBlogCategory
     *
     * @param bool     $active Active
     * @param int|null $idLang Language ID
     * @param bool     $count  Count
     *
     * @return array|int|false|null|\PDOStatement
     */
    public static function getAllCategories($active = true, $idLang = null, $count = false)
    {
        if (empty($idLang)) {
            $idLang = (int) \Context::getContext()->language->id;
        }

        $idShop = (int) \Context::getContext()->shop->id;

        $sql = new \DbQuery();
        if ($count) {
            $sql->select('COUNT(*)');
        } else {
            $sql->select('*');
        }
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`id_shop` = '.(int) $idShop);
        $sql->where('sbc.`active` = '.(int) $active);

        if ($count) {
            return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        return \ObjectModel::hydrateCollection(__CLASS__, \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql), $idLang);
    }

    /**
     * Get BeesBlogCategory name by BeesBlogPost ID
     *
     * @param int $idPost BeesBlogPost ID
     *
     * @return false|null|string
     */
    public static function getCategoryNameByPost($idPost)
    {
        $sql = new \DbQuery();
        $sql->select('sbp.`id_category`');
        $sql->from('bees_blog_post', 'sbp');
        $sql->where('sbp.`'.BeesBlogPost::PRIMARY.'` = '.(int) $idPost);

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get BeesBlogPost count in BeesBlogCategory
     *
     * @param int $idBeesBlogCategory BeesBlogCategory ID
     *
     * @return bool
     */
    public static function getPostCountInCategory($idBeesBlogCategory)
    {
        $sql = new \DbQuery();
        $sql->select('count(`id_bees_blog_post` as count');
        $sql->from('bees_blog_post');
        $sql->where('`id_category` = '.(int) $idBeesBlogCategory);
        if (!$result = \Db::getInstance()->executeS($sql)) {
            return false;
        }

        return $result[0]['count'];
    }

    /**
     * Get category meta data
     *
     * @param int      $idBeesBlogCategory BeesBlogCategory ID
     * @param int|null $idLang             Language ID
     *
     * @return mixed
     */
    public static function getCategoryMeta($idBeesBlogCategory, $idLang = null)
    {
        if ($idLang == null) {
            $idLang = (int) \Context::getContext()->language->id;
        }
        $idShop = (int) \Context::getContext()->shop->id;

        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::LANG_TABLE, 'smbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`id_shop` = '.(int) $idShop);
        $sql->where('sbc.`active` = 1');
        $sql->where('sbc.`'.static::PRIMARY.'` = '.(int) $idBeesBlogCategory);
        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if ($result['meta_title'] == '' && $result['meta_title'] == null) {
            $meta['meta_title'] = \Configuration::get('beesblogmetatitle');
        } else {
            $meta['meta_title'] = $result[0]['meta_title'];
        }

        if ($result['meta_description'] == '' && $result['meta_description'] == null) {
            $meta['meta_description'] = \Configuration::get('beesblogmetadescrip');
        } else {
            $meta['meta_description'] = $result['meta_description'];
        }

        if ($result['meta_keyword'] == '' && $result['meta_keyword'] == null) {
            $meta['meta_keywords'] = \Configuration::get('beesblogmetakeyword');
        } else {
            $meta['meta_keywords'] = $result['meta_keyword'];
        }

        return $meta;
    }

    /**
     * @param string   $rewrite Rewrite
     * @param bool     $active  Active
     * @param int|null $idLang  Language ID
     * @param int|null $idShop  Shop ID
     *
     * @return bool|false|null|string
     */
    public static function getIdByRewrite($rewrite, $active = true, $idLang = null, $idShop = null)
    {
        if (empty($rewrite)) {
            return false;
        }
        if (empty($idLang)) {
            $idLang = (int) \Context::getContext()->language->id;
        }
        if (empty($idShop)) {
            $idShop = (int) \Context::getContext()->shop->id;
        }
        $sql = new \DbQuery();
        $sql->select('sbc.`'.static::PRIMARY.'`');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`id_shop` = '.(int) $idShop);
        $sql->where('sbc.`active` = '.(int) $active);
        $sql->where('sbcl.`link_rewrite` = \''.pSQL($rewrite).'\'');

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}
