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
use PrestaShopCollection as Collection;
use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogCategory
 */
class BeesBlogCategory extends ObjectModel
{
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
        'multishop'      => true,
        'fields' => [
            'id_parent'         => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,  'default' => '0',                   'db_type' => 'INT(11) UNSIGNED'],
            'position'          => ['type' => self::TYPE_INT,                    'validate' => 'isUnsignedInt', 'required' => true,  'default' => '1',                   'db_type' => 'INT(11) UNSIGNED'],
            'active'            => ['type' => self::TYPE_BOOL,                   'validate' => 'isBool',        'required' => true,                                      'db_type' => 'TINYINT(1)'],
            'date_add'          => ['type' => self::TYPE_DATE,                   'validate' => 'isString',      'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'date_upd'          => ['type' => self::TYPE_DATE,                   'validate' => 'isString',      'required' => true,  'default' => '1970-01-01 00:00:00', 'db_type' => 'DATETIME'],
            'title'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(255)'],
            'description'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => false,                                     'db_type' => 'VARCHAR(512)'],
            'link_rewrite'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString',      'required' => true,                                      'db_type' => 'VARCHAR(256)'],
            'meta_title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(128)'],
            'meta_description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
            'meta_keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false,                                     'db_type' => 'VARCHAR(255)'],
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
        parent::__construct($id, $idLang, $idShop);
        $this->resolveAssociations($idLang, $idShop);
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
                    $this->link[$lang] = BeesBlog::getBeesBlogLink('beesblog_category', ['cat_rewrite' => $rewrite], $idShop, $idLang);
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
        $postCollection = new Collection('BeesBlogModule\\BeesBlogPost', $idLang);
        $postCollection->setPageSize($limit);
        $postCollection->setPageNumber($page);
        $postCollection->orderBy('published', 'desc');
        $postCollection->where('published', '<=', date('Y-m-d H:i:s'));
        $postCollection->where('id_category', '=', $this->id);
        $postCollection->where('active', '=', '1');
        $postCollection->sqlWhere('lang_active = \'1\'');

        if ($count) {
            return $postCollection->count();
        }

        $results = $postCollection->getResults();
        static::filterCollectionResults($results, $raw, $propertyFilter);

        return $results;
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
        $categoryCollection = new Collection('BeesBlogModule\\BeesBlogCategory', $idLang);
        $categoryCollection->setPageSize($limit);
        $categoryCollection->setPageNumber($page);

        if ($count) {
            return $categoryCollection->count();
        }

        $results = $categoryCollection->getResults();
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

        $categoryCollection = new Collection('BeesBlogModule\\BeesBlogCategory', $idLang);
        $categoryCollection->where('id_parent', '=', 0);

        /** @var BeesBlogCategory|false $ret */
        $ret = $categoryCollection->getFirst();
        return $ret;
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
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcs.`id_shop` = '.(int) $idShop);
        $sql->where('sbc.`active` = '.(int) $active);
        $sql->where('sbcl.`link_rewrite` = \''.pSQL($rewrite).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get local image path
     *
     * @param int    $id
     * @param string $type
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getImagePath($id, $type = 'category_default')
    {
        $baseLocation = _PS_IMG_DIR_.'beesblog/categories/';
        $id = (int)$id;

        if ($type === 'original') {
            if (file_exists("{$baseLocation}{$id}.png")) {
                return "{$baseLocation}{$id}.png";
            } else {
                return "{$baseLocation}{$id}.jpg";
            }
        }

        if (file_exists("{$baseLocation}{$id}-{$type}.png")) {
            return "{$baseLocation}{$id}-{$type}.png";
        } else {
            return "{$baseLocation}{$id}-{$type}.jpg";
        }
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
     * @param int|null $id_lang
     * @return string single array string (title of category)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getNameById($id, $id_lang = null)
    {
        if (empty($idLang)) {
            $idLang = (int) Context::getContext()->language->id;
        }

        $sql = new DbQuery();
        $sql->select('sbcl.`title`');
        $sql->from(static::TABLE, 'sbc');
        $sql->innerJoin(static::LANG_TABLE, 'sbcl', 'sbc.`'.static::PRIMARY.'` = sbcl.`'.static::PRIMARY.'`');
        $sql->innerJoin(static::SHOP_TABLE, 'sbcs', 'sbc.`'.static::PRIMARY.'` = sbcs.`'.static::PRIMARY.'`');
        $sql->where('sbcl.`id_lang` = '.(int) $idLang);
        $sql->where('sbcl.`'.static::PRIMARY.'` = \''.pSQL($id).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}
