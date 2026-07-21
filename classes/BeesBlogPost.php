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
use Employee;
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
 * Class BeesBlogPost
 */
class BeesBlogPost extends ObjectModel
{
    use BeesBlogMultistoreObjectModelTrait;

    const PRIMARY    = 'id_bees_blog_post';
    const TABLE      = 'bees_blog_post';
    const LANG_TABLE = 'bees_blog_post_lang';
    const SHOP_TABLE = 'bees_blog_post_shop';
    const IMAGE_TYPE = 'beesblog_post';

    /**
     * @var array Contains object definition
     */
    public static $definition = [
        'table'          => self::TABLE,
        'primary'        => self::PRIMARY,
        'multilang'      => true,
        'multilang_shop' => true,
        'multishop'      => true,
        'fields'         => [
            'active'            => ['type' => self::TYPE_BOOL,   'shop' => true, 'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1) UNSIGNED'],
            'comments_enabled'  => ['type' => self::TYPE_BOOL,   'shop' => true, 'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1) UNSIGNED'],
            'date_add'          => ['type' => self::TYPE_DATE,                   'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'date_upd'          => ['type' => self::TYPE_DATE,   'shop' => true, 'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'published'         => ['type' => self::TYPE_DATE,   'shop' => true, 'validate' => 'isDate',        'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'id_category'       => ['type' => self::TYPE_INT,    'shop' => true, 'validate' => 'isUnsignedInt', 'required' => true,                                      'db_type' => 'INT(11) UNSIGNED'],
            'id_employee'       => ['type' => self::TYPE_INT,    'shop' => true, 'validate' => 'isUnsignedInt', 'required' => true,                                      'db_type' => 'INT(11) UNSIGNED'],
            'image'             => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString',      'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'position'          => ['type' => self::TYPE_INT,    'shop' => true, 'validate' => 'isUnsignedInt', 'required' => true,  'default' => '1',                   'db_type' => 'INT(11) UNSIGNED'],
            'post_type'         => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(45)',  'size' => 45],
            'viewed'            => ['type' => self::TYPE_INT,    'shop' => true, 'validate' => 'isUnsignedInt', 'required' => true,  'default' => '0',                   'db_type' => 'INT(20) UNSIGNED'],
            'title'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(255)'],
            'content'           => ['type' => self::TYPE_HTML,   'lang' => true, 'validate' => 'isCleanHtml',   'required' => false,                                     'db_type' => 'TEXT'],
            'link_rewrite'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true,                                      'db_type' => 'VARCHAR(255)', 'ws_modifier' => ['http_method' => WebserviceRequest::HTTP_POST, 'modifier' => 'modifierWsLinkRewrite']],
            'meta_title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(128)'],
            'meta_description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'meta_keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'lang_active'       => ['type' => self::TYPE_BOOL,   'lang' => true, 'validate' => 'isBool', 'db_type' => 'TINYINT(1) UNSIGNED', 'ws_modifier' => ['http_method' => WebserviceRequest::HTTP_POST | WebserviceRequest::HTTP_PUT, 'modifier' => 'modifierWsLangActive']],
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
        'objectNodeName'  => 'bees_blog_post',
        'objectsNodeName' => 'bees_blog_posts',
        'fields'          => [
            'id_category' => ['xlink_resource' => 'bees_blog_categories'],
            'id_employee' => ['xlink_resource' => 'employees'],
        ],
    ];

    /**
     * @var bool $active
     */
    public $active = true;

    /**
     * @var int $id_employee
     */
    public $id_employee;

    /**
     * @var int $id_category
     */
    public $id_category;

    /**
     * @var int $position
     */
    public $position = 0;

    /**
     * @var string $date_add
     */
    public $date_add;

    /**
     * @var string $date_upd
     */
    public $date_upd;

    /**
     * @var string $published
     */
    public $published;

    /**
     * @var int $viewed
     */
    public $viewed;

    /**
     * @var bool $comments_enabled
     */
    public $comments_enabled = true;

    /**
     * @var int $post_type
     */
    public $post_type;

    /**
     * @var string|string[] $title
     */
    public $title;

    /**
     * @var string $image
     */
    public $image;

    /**
     * @var string|string[] $content
     */
    public $content;

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
     * @var bool|bool[] $lang_active
     */
    public $lang_active;

    /**
     * @var array $imageTypes Default image types
     */
    public static $imageTypes = ['post_default', 'post_list_item'];

    /**
     * @var BeesBlogCategory
     */
    public $category;

    /**
     * @var Employee
     */
    public $employee;

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

        $this->image_dir = _PS_IMG_DIR_ . '/beesblog/' . static::IMAGE_TYPE;

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
     * @param int|null $idShop
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function resolveAssociations($idLang, $idShop)
    {
        $this->employee = new Employee((int)$this->id_employee);
        $this->category = new BeesBlogCategory((int)$this->id_category, $idLang, $idShop);
        if ($idLang) {
            // single language context
            if (is_string($this->link_rewrite)) {
                $this->link = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $this->link_rewrite], $idShop, $idLang);
            } else {
                $this->link = '';
            }
        } else {
            // multiple language context
            $this->link = [];
            if (is_array($this->link_rewrite)) {
                foreach ($this->link_rewrite as $lang => $rewrite) {
                    $this->link[$lang] = BeesBlog::getBeesBlogLink('beesblog_post', ['blog_rewrite' => $rewrite], $idShop, $lang);
                }
            }
        }
    }

    /**
     * Get posts
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return array|int
     * @throws PrestaShopException
     */
    public static function getPosts($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        return static::getPostsForShop($idLang, $page, $limit, $count, $raw, $propertyFilter);
    }

    /**
     * Get posts by popularity
     *
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     *
     * @return array|int
     * @throws PrestaShopException
     */
    public static function getPopularPosts($idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        return static::getPostsForShop($idLang, $page, $limit, $count, $raw, $propertyFilter, null, 'ps.`viewed` DESC');
    }

    /**
     * @param int $idCategory
     * @param int|null $idLang
     * @param int $page
     * @param int $limit
     * @param bool $count
     * @param bool $raw
     * @param array $propertyFilter
     * @return array|int
     * @throws PrestaShopException
     */
    public static function getPostsByCategory($idCategory, $idLang = null, $page = 0, $limit = 0, $count = false, $raw = false, $propertyFilter = [])
    {
        return static::getPostsForShop($idLang, $page, $limit, $count, $raw, $propertyFilter, (int) $idCategory);
    }

    /**
     * @return array|int
     * @throws PrestaShopException
     */
    protected static function getPostsForShop($idLang, $page, $limit, $count, $raw, array $propertyFilter, $idCategory = null, $orderBy = 'ps.`published` DESC', $idShop = null)
    {
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;
        $idShop = $idShop ? (int) $idShop : (int) Context::getContext()->shop->id;
        $query = new DbQuery();
        $query->from(static::TABLE, 'p');
        $query->innerJoin(static::SHOP_TABLE, 'ps', 'ps.`'.static::PRIMARY.'` = p.`'.static::PRIMARY.'` AND ps.`id_shop` = '.$idShop);
        $query->innerJoin(static::LANG_TABLE, 'pl', 'pl.`'.static::PRIMARY.'` = p.`'.static::PRIMARY.'` AND pl.`id_shop` = ps.`id_shop` AND pl.`id_lang` = '.$idLang);
        $query->where('ps.`published` <= \''.pSQL(date('Y-m-d H:i:s')).'\'');
        $query->where('ps.`active` = 1');
        $query->where('pl.`lang_active` = 1');
        if ($idCategory !== null) {
            $query->where('ps.`id_category` = '.(int) $idCategory);
        }

        if ($count) {
            $query->select('COUNT(*)');
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        }

        $query->select('p.`'.static::PRIMARY.'`');
        $query->orderBy($orderBy);
        if ($limit > 0) {
            $offset = max(0, (int) $page - 1) * (int) $limit;
            $query->limit((int) $limit, $offset);
        }

        $results = [];
        foreach ((array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query) as $row) {
            $post = new static((int) $row[static::PRIMARY], $idLang, $idShop);
            if ($raw) {
                if ($propertyFilter) {
                    $item = [];
                    foreach ($propertyFilter as $property) {
                        $item[$property] = $post->{$property};
                    }
                    $results[] = $item;
                } else {
                    $results[] = (array) $post;
                }
            } else {
                $results[] = $post;
            }
        }

        return $results;
    }

    /**
     * Count posts assigned to a category in any of the supplied shops.
     *
     * @param int $idCategory
     * @param int[] $shopIds
     * @return int
     * @throws PrestaShopException
     */
    public static function countByCategoryInShops($idCategory, array $shopIds)
    {
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        if (!$shopIds) {
            return 0;
        }

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(DISTINCT `'.static::PRIMARY.'`)'.
            ' FROM `'._DB_PREFIX_.static::SHOP_TABLE.'`'.
            ' WHERE `id_category` = '.(int) $idCategory.
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
        );
    }

    /**
     * Increment view count
     *
     * @param int $idBeesBlogPost BeesBlogPost ID
     *
     * @return bool Whether view count has been successfully incremented
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function viewed($idBeesBlogPost, $idShop = null)
    {
        $idShop = $idShop ?: (int) Context::getContext()->shop->id;
        $sql = 'UPDATE `'._DB_PREFIX_.static::SHOP_TABLE.'` SET `viewed` = `viewed` + 1'.
            ' WHERE `'.static::PRIMARY.'` = '.(int) $idBeesBlogPost.' AND `id_shop` = '.(int) $idShop;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Get blog image
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBlogImage()
    {
        $sql = new DbQuery();
        $sql->select('p.`'.self::PRIMARY.'`');
        $sql->from(self::TABLE, 'p');
        $sql->innerJoin(self::SHOP_TABLE, 'ps', 'ps.`'.self::PRIMARY.'` = p.`'.self::PRIMARY.'` AND ps.`id_shop` = '.(int) Context::getContext()->shop->id);
        $sql->where('ps.`active` = 1');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get BeesBlogPost rewrite by ID
     *
     * @param int|null $idPost BeesBlogPost ID
     * @param int|null $idLang Language ID
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getPostRewriteById($idPost, $idLang = null)
    {
        if ($idLang == null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        $idShop = (int) Context::getContext()->shop->id;

        $sql = new DbQuery();
        $sql->select('sbpl.`link_rewrite`');
        $sql->from(self::TABLE, 'sbp');
        $sql->innerJoin(self::SHOP_TABLE, 'sbps', 'sbp.`'.self::PRIMARY.'` = sbps.`'.self::PRIMARY.'` AND sbps.`id_shop` = '.(int) $idShop);
        $sql->innerJoin(self::LANG_TABLE, 'sbpl', 'sbp.`'.self::PRIMARY.'` = sbpl.`'.self::PRIMARY.'` AND sbpl.`id_shop` = sbps.`id_shop`');
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);
        $sql->where('sbpl.`lang_active` = 1');
        $sql->where('sbps.`active` = 1');
        $sql->where('sbp.`'.self::PRIMARY.'` = '.(int) $idPost);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get BeesBlogPost ID by rewrite
     *
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
        $sql->select('sbp.`'.self::PRIMARY.'`');
        $sql->from(self::TABLE, 'sbp');
        $sql->innerJoin(self::SHOP_TABLE, 'sbps', 'sbp.`'.self::PRIMARY.'` = sbps.`'.self::PRIMARY.'` AND sbps.`id_shop` = '.(int) $idShop);
        $sql->innerJoin(self::LANG_TABLE, 'sbpl', 'sbp.`'.self::PRIMARY.'` = sbpl.`'.self::PRIMARY.'` AND sbpl.`id_shop` = sbps.`id_shop`');
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);
        $sql->where('sbps.`active` = '.(int) $active);
        $sql->where('sbpl.`lang_active`');
        $sql->where('sbpl.`link_rewrite` = \''.pSQL($rewrite).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @param int $idBeesBlogPost BeesBlogPost ID
     * @param int $idLang Language ID
     *
     * @return false|null|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getLangActive($idBeesBlogPost, $idLang, $idShop = null)
    {
        $idShop = $idShop ?: (int) Context::getContext()->shop->id;
        $sql = new DbQuery();
        $sql->select('sbpl.`lang_active`');
        $sql->from(static::LANG_TABLE, 'sbpl');
        $sql->where('sbpl.`'.static::PRIMARY.'` = '.(int) $idBeesBlogPost);
        $sql->where('sbpl.`id_lang` = '.(int) $idLang);
        $sql->where('sbpl.`id_shop` = '.(int) $idShop);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * @param int $idBeesBlogPost BeesBlogPost ID
     * @param array $langActive
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function setLangActive($idBeesBlogPost, $langActive, array $shopIds = [])
    {
        if (!is_array($langActive)) {
            return;
        }

        $shopIds = $shopIds ?: [(int) Context::getContext()->shop->id];
        foreach ($langActive as $idLang => $active) {
            Db::getInstance()->update(
                static::LANG_TABLE,
                ['lang_active' => (int) $active],
                static::PRIMARY.' = '.(int) $idBeesBlogPost.' AND id_lang = '.(int) $idLang.
                ' AND id_shop IN ('.implode(', ', array_map('intval', $shopIds)).')'
            );
        }
    }

    /**
     * Get summary of content
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getSummary()
    {
        $content = strip_tags($this->content);
        if (mb_strlen($content) < 512) {
            return $content;
        }
        return mb_substr($content, 0, 512).' [...]';
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
     */
    public static function getImagePath($id, $type = 'post_default', $idShop = null, $idLang = null)
    {
        return BeesBlogImage::getImagePath(
            BeesBlogImage::ENTITY_POST,
            $id,
            $type,
            $idShop,
            $idLang
        );
    }

    /**
     * Create the database tables for BeesBlogPost model
     *
     * @param string|null $className Class name
     * @return bool Indicates whether the database was successfully added
     * @throws PrestaShopException
     */
    public static function createDatabase($className = null)
    {
        return (
            parent::createDatabase($className) &&
            static::createRelatedProductsTable()
        );
    }

    /**
     * Drop the database for BeesBlogPost model
     *
     * @param string|null $className Class name
     * @return bool Indicates whether the database was successfully dropped
     * @throws PrestaShopException
     */
    public static function dropDatabase($className = null)
    {
        return (
            parent::dropDatabase($className) &&
            static::dropRelatedProductsTable()
        );
    }

    /**
     * Creates database table to store related products
     *
     * @return boolean
     * @throws PrestaShopException
     */
    public static function createRelatedProductsTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bees_blog_post_product` (
               `id_product`        INT(11) UNSIGNED NOT NULL,
               `id_bees_blog_post` INT(11) UNSIGNED NOT NULL,
               `id_shop`           INT(11) NOT NULL,
               PRIMARY KEY (`id_product`, `id_bees_blog_post`, `id_shop`),
               KEY `beesblog_post_shop` (`id_bees_blog_post`, `id_shop`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    /**
     * Drop related products database table
     *
     * @return boolean
     * @throws PrestaShopException
     */
    public static function dropRelatedProductsTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'bees_blog_post_product`');
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

    /**
     * Defaults omitted lang_active values to enabled on webservice writes;
     * the webservice assigns an empty string to omitted i18n fields, which
     * would otherwise be stored as 0 and hide the post in the front office
     * while the API reports success
     *
     * @return bool
     */
    public function modifierWsLangActive()
    {
        if (is_array($this->lang_active)) {
            foreach ($this->lang_active as $idLang => $value) {
                if ($value === '' || $value === null) {
                    $this->lang_active[$idLang] = 1;
                }
            }
        } elseif ($this->lang_active === '' || $this->lang_active === null) {
            $this->lang_active = 1;
        }

        return true;
    }

    /**
     * @param int[] $shopIds
     * @return void
     */
    protected function deleteShopDependencies(array $shopIds)
    {
        Db::getInstance()->delete(
            'bees_blog_post_product',
            '`'.static::PRIMARY.'` = '.(int) $this->id.
            ' AND `id_shop` IN ('.implode(', ', array_map('intval', $shopIds)).')'
        );
    }
}
