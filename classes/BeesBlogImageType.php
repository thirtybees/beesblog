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

use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionClass;
use Shop;
use Tools;
use Validate;

/**
 * Class BeesBlogImageType
 *
 * @since 1.0.0
 */
class BeesBlogImageType extends ObjectModel
{
    use BeesBlogMultistoreObjectModelTrait;

    const PRIMARY = 'id_bees_blog_image_type';
    const TABLE = 'bees_blog_image_type';
    const LANG_TABLE = 'bees_blog_image_type_lang';
    const SHOP_TABLE = 'bees_blog_image_type_shop';

    const POST_LIST_ITEM_WIDTH = 800;
    const POST_LIST_ITEM_HEIGHT = 500;
    const POST_DEFAULT_WIDTH = 800;
    const POST_DEFAULT_HEIGHT = 500;
    const CATEGORY_DEFAULT_WIDTH = 800;
    const CATEGORY_DEFAULT_HEIGHT = 500;

    /**
     * @var array Image types cache
     */
    protected static $imagesTypesCache = [];

    /**
     * @var array
     */
    protected static $imagesTypesNameCache = [];

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var int Width
     */
    public $width;

    /**
     * @var int Height
     */
    public $height;

    /**
     * @var bool $posts Apply to posts
     */
    public $posts;

    /**
     * @var bool $categories Apply to categories
     */
    public $categories;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => self::TABLE,
        'primary'   => self::PRIMARY,
        'multishop' => true,
        'fields'    => [
            'name'       => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64, 'db_type' => 'VARCHAR(64)'],
            'width'      => ['type' => self::TYPE_INT,  'validate' => 'isImageSize', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'height'     => ['type' => self::TYPE_INT,  'validate' => 'isImageSize', 'required' => true, 'db_type' => 'INT(11) UNSIGNED'],
            'posts'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',      'required' => true, 'db_type' => 'TINYINT(1)'],
            'categories' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool',      'required' => true, 'db_type' => 'TINYINT(1)'],
        ],
        'keys' => [
            self::SHOP_TABLE => [
                'primary' => ['type' => self::PRIMARY_KEY, 'columns' => [self::PRIMARY, 'id_shop']],
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $webserviceParameters = [];

    /**
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     * @throws PrestaShopException
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        BeesBlogMultistore::registerAssociations();
        parent::__construct($id, $idLang, $idShop);
    }

    /**
     * Returns image type definitions
     *
     * @param string|null $type Image type
     * @param bool $orderBySize
     *
     * @return array Image type definitions
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getImagesTypes($type = null, $orderBySize = false)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $cacheKey = $idShop.'|'.(string) $type.'|'.(int) $orderBySize;
        if (!isset(static::$imagesTypesCache[$cacheKey])) {
            $where = 'WHERE its.`id_shop` = '.(int) $idShop;
            if (!empty($type)) {
                $where .= ' AND it.`'.bqSQL($type).'` = 1 ';
            }

            if ($orderBySize) {
                $order = 'it.`width` DESC, it.`height` DESC, it.`name` ASC';
            } else {
                $order = 'it.`name` ASC';
            }
            $query = 'SELECT it.`'.static::PRIMARY.'`, it.`name`, it.`width`, it.`height`, it.`posts`, it.`categories`'.
                ' FROM `'._DB_PREFIX_.bqSQL(static::TABLE).'` it'.
                ' INNER JOIN `'._DB_PREFIX_.bqSQL(static::SHOP_TABLE).'` its'.
                ' ON its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`'.
                ' '.$where.' ORDER BY '.$order;
            static::$imagesTypesCache[$cacheKey] = Db::getInstance()->executeS($query);
        }

        return static::$imagesTypesCache[$cacheKey];
    }

    /**
     * Check if type already is already registered in database
     *
     * @param string $typeName Name
     *
     * @return int Number of results found
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function typeAlreadyExists($typeName)
    {
        if (!Validate::isImageTypeName($typeName)) {
            die(Tools::displayError());
        }

        Db::getInstance()->executeS(
            '
			SELECT `id_image_type`
			FROM `'._DB_PREFIX_.'image_type`
			WHERE `name` = \''.pSQL($typeName).'\''
        );

        return Db::getInstance()->NumRows();
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFormatedName($name)
    {
        $themeName = Context::getContext()->shop->theme_name;
        $nameWithoutThemeName = str_replace(['_'.$themeName, $themeName.'_'], '', $name);

        //check if the theme name is already in $name if yes only return $name
        if (strstr($name, $themeName) && static::getByNameNType($name)) {
            return $name;
        } elseif (static::getByNameNType($nameWithoutThemeName.'_'.$themeName)) {
            return $nameWithoutThemeName.'_'.$themeName;
        } elseif (static::getByNameNType($themeName.'_'.$nameWithoutThemeName)) {
            return $themeName.'_'.$nameWithoutThemeName;
        } else {
            return $nameWithoutThemeName.'_default';
        }
    }

    /**
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string|null $type
     * @param int $order
     *
     * @return bool|array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByNameNType($name, $type = null, $order = 0)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $key = $idShop."_{$name}_{$type}_{$order}";
        if (!array_key_exists($key, static::$imagesTypesNameCache)) {
            static::$imagesTypesNameCache[$key] = false;
            foreach (static::getImagesTypes($type, (bool) $order) as $result) {
                if ($result['name'] === $name) {
                    static::$imagesTypesNameCache[$key] = $result;
                    break;
                }
            }
        }

        return static::$imagesTypesNameCache[$key];
    }

    /**
     * Get basic type IDs
     *
     * @return array|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public static function getBasicTypeIds()
    {
        $idShop = Context::getContext()->shop->id;

        $sql = new DbQuery();
        $sql->select('it.'.static::PRIMARY);
        $sql->from(static::TABLE, 'it');
        $sql->innerJoin(static::SHOP_TABLE, 'its', 'its.`'.static::PRIMARY.'` = it.`'.static::PRIMARY.'`');
        $sql->where('its.`id_shop` = '.(int) $idShop);
        $sql->where('it.`name` IN (\'post_list_item\', \'post_default\', \'category_default\')');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (is_array($result)) {
            return array_column($result, static::PRIMARY);
        }

        return false;
    }

    /**
     * Install basic image types
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public static function installBasics()
    {
        $basicTypes = ['post_list_item', 'post_default', 'category_default'];
        $shops = Shop::getShops(false, null, true);

        $reflection = new ReflectionClass(__CLASS__);
        $consts = $reflection->getConstants();

        foreach ($basicTypes as $basicType) {
            foreach ($shops as $idShop) {
                $idType = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    'SELECT `'.static::PRIMARY.'` FROM `'._DB_PREFIX_.static::TABLE.'`'.
                    ' WHERE `name` = \''.pSQL($basicType).'\''
                );
                if (!$idType) {
                    Db::getInstance()->insert(
                        static::TABLE,
                        [
                            'name' => pSQL($basicType),
                            'width' => (int) $consts[strtoupper($basicType).'_WIDTH'],
                            'height' => (int) $consts[strtoupper($basicType).'_HEIGHT'],
                            'posts' => (int) (substr($basicType, 0, 4) === 'post'),
                            'categories' => (int) (substr($basicType, 0, 4) !== 'post'),
                        ]
                    );
                    $idType = (int) Db::getInstance()->Insert_ID();
                }
                Db::getInstance()->insert(
                    static::SHOP_TABLE,
                    [
                        static::PRIMARY => $idType,
                        'id_shop' => (int) $idShop,
                    ],
                    false,
                    true,
                    Db::INSERT_IGNORE
                );
            }
        }
    }
}
