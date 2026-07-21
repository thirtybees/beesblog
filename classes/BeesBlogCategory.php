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

namespace BeesBlogModule;

use BeesBlog;
use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use Shop;
use Tools;
use Validate;
use WebserviceRequest;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogCategory
 */
class BeesBlogCategory extends ObjectModel
{
    use BeesBlogMultistoreObjectModelTrait;

    const TABLE = 'bees_blog_category';
    const PRIMARY = 'id_bees_blog_category';
    const LANG_TABLE = 'bees_blog_category_lang';
    const SHOP_TABLE = 'bees_blog_category_shop';
    const IMAGE_TYPE = 'beesblog_category';

    /**
     * @var array Contains object definition
     */
    public static $definition = [
        'table'          => self::TABLE,
        'primary'        => self::PRIMARY,
        'multilang'      => true,
        'multilang_shop' => true,
        'multishop'      => true,
        'fields' => [
            'id_parent'         => ['type' => self::TYPE_INT,  'shop' => true,   'validate' => 'isUnsignedInt', 'required' => true,  'default' => '0',                   'db_type' => 'INT(11) UNSIGNED'],
            'position'          => ['type' => self::TYPE_INT,  'shop' => true,   'validate' => 'isUnsignedInt', 'required' => true,  'default' => '1',                   'db_type' => 'INT(11) UNSIGNED'],
            'active'            => ['type' => self::TYPE_BOOL, 'shop' => true,   'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1)'],
            'date_add'          => ['type' => self::TYPE_DATE,                   'validate' => 'isString',      'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'date_upd'          => ['type' => self::TYPE_DATE, 'shop' => true,   'validate' => 'isString',      'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'title'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(255)'],
            'description'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => false,                                     'db_type' => 'VARCHAR(512)'],
            'link_rewrite'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true,                                      'db_type' => 'VARCHAR(256)', 'ws_modifier' => ['http_method' => WebserviceRequest::HTTP_POST, 'modifier' => 'modifierWsLinkRewrite']],
            'meta_title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(128)'],
            'meta_description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'meta_keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
        ],
        'keys' => [
            self::SHOP_TABLE => [
                'primary' => ['type' => self::PRIMARY_KEY, 'columns' => [self::PRIMARY, 'id_shop']],
            ],
            self::LANG_TABLE => [
                'primary' => ['type' => self::PRIMARY_KEY, 'columns' => [self::PRIMARY, 'id_shop', 'id_lang']],
                'slug_shop_lang' => ['type' => self::UNIQUE_KEY, 'columns' => ['id_shop', 'id_lang', 'link_rewrite']],
            ],
        ],
    ];

    /**
     * @var array Webservice parameters
     */
    protected $webserviceParameters = [
        'objectNodeName'  => 'bees_blog_category',
        'objectsNodeName' => 'bees_blog_categories',
        'fields'          => [
            'id_parent' => ['xlink_resource' => 'bees_blog_categories'],
        ],
    ];

    /**
     * @var int $id_bees_blog_category
     */
    public $id_bees_blog_category;

    /**
     * @var int $id_parent
     */
    public $id_parent;

    /**
     * @var int $position
     */
    public $position;

    /**
     * @var bool $active
     */
    public $active = true;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var string $date_upd
     */

    public $date_upd;

    /**
     * @var string|string[] $title
     */
    public $title;

    /**
     * @var string|string[] $description
     */
    public $description;

    /**
     * @var string|string[] $link_rewrite
     */
    public $link_rewrite;

    /**
     * @var string|string[] $meta_title
     */
    public $meta_title;

    /**
     * @var string|string[] $meta_description
     */
    public $meta_description;

    /**
     * @var string|string[] $meta_keywords
     */
    public $meta_keywords;

    /**
     * @var string|string[]
     */
    public $link;

    /**
     * BeesBlogPost constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        BeesBlogMultistore::registerAssociations();
        parent::__construct($id, $idLang, $idShop);
        $this->resolveAssociations($idLang, $this->id_shop);
    }

    /**
     * @param array $row
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hydrate(array $row, $idLang = null)
    {
        parent::hydrate($row, $idLang);
        $this->resolveAssociations($idLang, $this->id_shop);
    }

    /**
     * @param int|null $idLang
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function resolveAssociations($idLang, $idShop)
    {
        if ($idLang) {
            // single language context
            if (is_string($this->link_rewrite)) {
                $this->link = BeesBlog::getBeesBlogLink('beesblog_category', ['cat_rewrite' => $this->link_rewrite], $idShop, $idLang);
            } else {
                $this->link = '';
            }
        } else {
            // multiple language context
            $this->link = [];
            if (is_array($this->link_rewrite)) {
                foreach ($this->link_rewrite as $lang => $rewrite) {
                    $this->link[$lang] = BeesBlog::getBeesBlogLink('beesblog_category', ['cat_rewrite' => $rewrite], $idShop, $lang);
                }
            }
        }
    }

    /**
     * Get posts in category
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return int|BeesBlogPost[]
     * @throws PrestaShopException
     */
    public function getPostsInCategory($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        return BeesBlogPost::getPostsByCategory(
            (int) $this->id,
            $idLang,
            $page,
            $limit,
            $count,
            $raw,
            $propertyFilter
        );
    }

    /**
     * Get categories
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return BeesBlogCategory[]|int
     * @throws PrestaShopException
     */
    public static function getCategories($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;
        $shopIds = Shop::getContext() === Shop::CONTEXT_SHOP
            ? [(int) Context::getContext()->shop->id]
            : BeesBlogMultistore::getContextShopIds();
        if (!$shopIds) {
            return $count ? 0 : [];
        }
        $shopList = implode(', ', $shopIds);
        $query = new DbQuery();
        $query->from(static::TABLE, 'c');
        $query->innerJoin(
            static::SHOP_TABLE,
            'cs',
            'cs.`'.static::PRIMARY.'` = c.`'.static::PRIMARY.'`'.
            ' AND cs.`id_shop` = (SELECT MIN(cs_scope.`id_shop`)'.
            ' FROM `'._DB_PREFIX_.static::SHOP_TABLE.'` cs_scope'.
            ' WHERE cs_scope.`'.static::PRIMARY.'` = c.`'.static::PRIMARY.'`'.
            ' AND cs_scope.`id_shop` IN ('.$shopList.'))'
        );
        $query->innerJoin(static::LANG_TABLE, 'cl', 'cl.`'.static::PRIMARY.'` = c.`'.static::PRIMARY.'` AND cl.`id_shop` = cs.`id_shop` AND cl.`id_lang` = '.$idLang);

        if ($count) {
            $query->select('COUNT(*)');
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        }

        $query->select('c.`'.static::PRIMARY.'`, cs.`id_shop`');
        $query->orderBy('cs.`position` ASC, cl.`title` ASC');
        if ($limit > 0) {
            $query->limit((int) $limit, max(0, (int) $page - 1) * (int) $limit);
        }

        $results = [];
        foreach ((array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query) as $row) {
            $results[] = new static((int) $row[static::PRIMARY], $idLang, (int) $row['id_shop']);
        }
        static::filterCollectionResults($results, $raw, $propertyFilter);

        return $results;
    }

    /**
     * @param int|null $idLang
     *
     * @return false|BeesBlogCategory
     * @throws PrestaShopException
     */
    public static function getRootCategory($idLang = null)
    {
        if (!$idLang) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $idShop = (int) Context::getContext()->shop->id;
        $id = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('c.`'.static::PRIMARY.'`')
                ->from(static::TABLE, 'c')
                ->innerJoin(static::SHOP_TABLE, 'cs', 'cs.`'.static::PRIMARY.'` = c.`'.static::PRIMARY.'` AND cs.`id_shop` = '.$idShop)
                ->where('cs.`id_parent` = 0')
                ->orderBy('cs.`position` ASC')
        );

        return $id ? new static($id, $idLang, $idShop) : false;
    }

    /**
     * @param string $rewrite Rewrite
     * @param bool $active Active
     * @param int|null $idLang Language ID
     * @param int|null $idShop Shop ID
     *
     * @return bool|false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getIdByRewrite($rewrite, $active = true, $idLang = null, $idShop = null)
    {
        if (empty($rewrite)) {
            return false;
        }
        if (empty($idLang)) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (empty($idShop)) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $sql = new DbQuery();
        $sql->select('sbc.`'.static::PRIMARY.'`');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'` AND sbcs.`id_shop` = '.(int) $idShop);
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'` AND sbcl.`id_shop` = sbcs.`id_shop`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`active` = '.(int) $active);
        $sql->where('sbcl.`link_rewrite` = \''.pSQL($rewrite).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get local image path
     *
     * @param int    $id
     * @param string $type
     *
     * @param int|null $idShop
     * @param int|null $idLang
     * @return string|false
     *
     * @since 1.0.0
     */
    public static function getImagePath($id, $type = 'category_default', $idShop = null, $idLang = null)
    {
        return BeesBlogImage::getImagePath(
            BeesBlogImage::ENTITY_CATEGORY,
            $id,
            $type,
            $idShop,
            $idLang
        );
    }

    /**
     * Filter collection results
     *
     * @param array $results
     * @param bool  $raw
     * @param array $propertyFilter
     */
    protected static function filterCollectionResults(&$results, $raw, $propertyFilter)
    {
        if ($raw) {
            $newResults = [];
            foreach ($results as $result) {
                if (!empty($propertyFilter)) {
                    $newPost = [];
                    foreach ($propertyFilter as $filter) {
                        $newPost[$filter] = $result->{$filter};
                    }
                    $newResults[] = $newPost;
                } else {
                    $newResults[] = (array) $result;
                }
            }
            $results = $newResults;
        }
    }

    /**
     * Return the category title by id
     *
     * @param int $id
     * @param int|null $idLang
     * @return string single array string (title of category)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getNameById($id, $idLang = null, $idShop = null)
    {
        if (empty($idLang)) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if (empty($idShop)) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $sql = new DbQuery();
        $sql->select('sbcl.`title`');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'` AND sbcs.`id_shop` = '.(int) $idShop);
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'` AND sbcl.`id_shop` = sbcs.`id_shop`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcl.`'.static::PRIMARY.'` = '.(int) $id);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Derives or sanitizes link_rewrite values on webservice creation, same
     * pattern as Product::modifierWsLinkRewrite()
     *
     * @return bool
     */
    public function modifierWsLinkRewrite()
    {
        if (!is_array($this->link_rewrite)) {
            $this->link_rewrite = [];
        }
        foreach ((array) $this->title as $idLang => $title) {
            if (empty($this->link_rewrite[$idLang])) {
                $this->link_rewrite[$idLang] = Tools::link_rewrite($title);
            } elseif (!Validate::isLinkRewrite($this->link_rewrite[$idLang])) {
                $this->link_rewrite[$idLang] = Tools::link_rewrite($this->link_rewrite[$idLang]);
            }
        }

        return true;
    }
}
