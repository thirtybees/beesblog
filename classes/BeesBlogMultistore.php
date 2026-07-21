<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

use Context;
use Db;
use ObjectModel;
use PrestaShopException;
use Shop;
use Tools;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Shared multistore registration, validation and migration helpers.
 */
class BeesBlogMultistore
{
    /**
     * Register associations before ObjectModel or collections inspect them.
     * Re-registering an existing association is intentionally harmless.
     */
    public static function registerAssociations()
    {
        Shop::addTableAssociation(BeesBlogPost::TABLE, ['type' => 'shop']);
        Shop::addTableAssociation(BeesBlogPost::LANG_TABLE, ['type' => 'fk_shop']);
        Shop::addTableAssociation(BeesBlogCategory::TABLE, ['type' => 'shop']);
        Shop::addTableAssociation(BeesBlogCategory::LANG_TABLE, ['type' => 'fk_shop']);
        Shop::addTableAssociation(BeesBlogImageType::TABLE, ['type' => 'shop']);
    }

    /**
     * @param int[] $shopIds
     * @return int[]
     * @throws PrestaShopException
     */
    public static function filterAuthorizedShopIds(array $shopIds)
    {
        $employee = Context::getContext()->employee;
        $result = [];
        foreach (array_unique(array_map('intval', $shopIds)) as $idShop) {
            if ($idShop <= 0 || !Shop::getShop($idShop)) {
                continue;
            }
            if ($employee && $employee->id && !$employee->hasAuthOnShop($idShop)) {
                continue;
            }
            $result[] = $idShop;
        }

        sort($result);

        return $result;
    }

    /**
     * @return int[]
     * @throws PrestaShopException
     */
    public static function getContextShopIds()
    {
        return static::filterAuthorizedShopIds(Shop::getContextListShopID());
    }

    /**
     * @return int
     * @throws PrestaShopException
     */
    public static function getRepresentativeShopId()
    {
        $shopIds = static::getContextShopIds();

        return $shopIds ? (int) reset($shopIds) : (int) Context::getContext()->shop->id;
    }

    /**
     * The native BO context selector is authoritative. All Shops means all
     * authorized shops, a group means every authorized shop in that group,
     * and a shop context means that shop only.
     *
     * @param string $table
     * @return int[]
     * @throws PrestaShopException
     */
    public static function getSubmittedShopIds($table)
    {
        return static::getContextShopIds();
    }

    /**
     * Select the first associated shop inside the active BO context. This is
     * used as the source values when an all/group-context edit is propagated.
     *
     * @param string $table
     * @param string $primary
     * @param int $idObject
     * @return int
     * @throws PrestaShopException
     */
    public static function getObjectRepresentativeShopId($table, $primary, $idObject)
    {
        $shopIds = static::getContextShopIds();
        if ($idObject && $shopIds) {
            $idShop = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT MIN(`id_shop`) FROM `'._DB_PREFIX_.bqSQL($table).'_shop`'.
                ' WHERE `'.bqSQL($primary).'` = '.(int) $idObject.
                ' AND `id_shop` IN ('.implode(', ', array_map('intval', $shopIds)).')'
            );
            if ($idShop) {
                return $idShop;
            }
        }

        return static::getRepresentativeShopId();
    }

    /**
     * Remove associations that were explicitly unchecked inside the current
     * context while preserving every association outside that context.
     *
     * @param ObjectModel $object
     * @param int[] $selectedShopIds
     * @return bool
     * @throws PrestaShopException
     */
    public static function synchronizeAssociations(ObjectModel $object, array $selectedShopIds)
    {
        $definition = ObjectModel::getDefinition(get_class($object));
        $contextIds = static::getContextShopIds();
        $removeIds = array_values(array_diff($contextIds, $selectedShopIds));
        if (!$removeIds) {
            return true;
        }

        $connection = Db::getInstance();
        $where = '`'.bqSQL($definition['primary']).'` = '.(int) $object->id.
            ' AND `id_shop` IN ('.implode(', ', array_map('intval', $removeIds)).')';

        if (!empty($definition['multilang_shop'])) {
            $connection->delete($definition['table'].'_lang', $where);
        }

        return $connection->delete($definition['table'].'_shop', $where);
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $idObject
     * @param array $slugs id_lang => slug
     * @param int[] $shopIds
     * @return array[]
     */
    public static function findSlugConflicts($table, $primary, $idObject, array $slugs, array $shopIds)
    {
        if (!$shopIds) {
            return [];
        }

        $conflicts = [];
        foreach ($slugs as $idLang => $slug) {
            $slug = trim((string) $slug);
            if ($slug === '') {
                continue;
            }
            $query = 'SELECT `id_shop`, `'.bqSQL($primary).'` FROM `'._DB_PREFIX_.bqSQL($table).'_lang`'.
                ' WHERE `id_shop` IN ('.implode(', ', array_map('intval', $shopIds)).')'.
                ' AND `id_lang` = '.(int) $idLang.
                ' AND `link_rewrite` = \''.pSQL($slug).'\''.
                ($idObject ? ' AND `'.bqSQL($primary).'` != '.(int) $idObject : '');
            foreach ((array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query) as $row) {
                $conflicts[] = [
                    'id_shop' => (int) $row['id_shop'],
                    'id_lang' => (int) $idLang,
                    'slug' => $slug,
                    'id_object' => (int) $row[$primary],
                ];
            }
        }

        return $conflicts;
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $idObject
     * @param int[] $shopIds
     * @return int[]
     * @throws PrestaShopException
     */
    public static function getMissingAssociationShopIds($table, $primary, $idObject, array $shopIds)
    {
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        if (!$idObject || !$shopIds) {
            return $shopIds;
        }

        $associated = array_map('intval', array_column((array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `id_shop` FROM `'._DB_PREFIX_.bqSQL($table).'_shop`'.
            ' WHERE `'.bqSQL($primary).'` = '.(int) $idObject.
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
        ), 'id_shop'));

        return array_values(array_diff($shopIds, $associated));
    }

    /**
     * Upgrade an existing 1.8 schema in place. Legacy columns are kept in the
     * base tables because thirty bees ObjectModel mirrors multishop fields
     * there, while reads are explicitly resolved from the selected shop row.
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function migrateSchema()
    {
        static::registerAssociations();

        if (!BeesBlogImage::createDatabase()) {
            throw new PrestaShopException('Unable to create the blog image association table');
        }

        $legacyPostSchema = !static::columnExists(BeesBlogPost::LANG_TABLE, 'id_shop');
        $legacyCategorySchema = !static::columnExists(BeesBlogCategory::LANG_TABLE, 'id_shop');

        // Repair missing associations before copying legacy values so repaired
        // rows receive the original values rather than the column defaults.
        static::ensureOrphanAssociations(BeesBlogPost::TABLE, BeesBlogPost::PRIMARY);
        static::ensureOrphanAssociations(BeesBlogCategory::TABLE, BeesBlogCategory::PRIMARY);
        static::ensureOrphanAssociations(BeesBlogImageType::TABLE, BeesBlogImageType::PRIMARY);
        static::ensurePostShopColumns($legacyPostSchema);
        static::ensureCategoryShopColumns($legacyCategorySchema);
        static::migrateLanguageTable(BeesBlogPost::TABLE, BeesBlogPost::PRIMARY, 255);
        static::migrateLanguageTable(BeesBlogCategory::TABLE, BeesBlogCategory::PRIMARY, 256);
        static::migrateRelatedProducts();
        if (!BeesBlogImage::migrateLegacyImages()) {
            throw new PrestaShopException('Unable to migrate legacy blog images');
        }

        return true;
    }

    /** @return void */
    protected static function ensurePostShopColumns($copyLegacyValues)
    {
        $fields = [
            'active' => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 1',
            'comments_enabled' => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 1',
            'date_upd' => "DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'",
            'published' => "DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'",
            'id_category' => 'INT(11) UNSIGNED NOT NULL DEFAULT 0',
            'id_employee' => 'INT(11) UNSIGNED NOT NULL DEFAULT 0',
            'image' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'position' => 'INT(11) UNSIGNED NOT NULL DEFAULT 1',
            'post_type' => "VARCHAR(45) NOT NULL DEFAULT '0'",
            'viewed' => 'INT(20) UNSIGNED NOT NULL DEFAULT 0',
        ];
        static::ensureColumns(BeesBlogPost::SHOP_TABLE, $fields);
        if ($copyLegacyValues && static::columnExists(BeesBlogPost::TABLE, 'active')) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` s'.
                ' INNER JOIN `'._DB_PREFIX_.BeesBlogPost::TABLE.'` b ON b.`'.BeesBlogPost::PRIMARY.'` = s.`'.BeesBlogPost::PRIMARY.'`'.
                ' SET s.`active` = b.`active`, s.`comments_enabled` = b.`comments_enabled`,'.
                ' s.`date_upd` = b.`date_upd`, s.`published` = b.`published`,'.
                ' s.`id_category` = b.`id_category`, s.`id_employee` = b.`id_employee`,'.
                ' s.`image` = b.`image`, s.`position` = b.`position`,'.
                ' s.`post_type` = b.`post_type`, s.`viewed` = b.`viewed`'
            );
        }
    }

    /** @return void */
    protected static function ensureCategoryShopColumns($copyLegacyValues)
    {
        $fields = [
            'id_parent' => 'INT(11) UNSIGNED NOT NULL DEFAULT 0',
            'position' => 'INT(11) UNSIGNED NOT NULL DEFAULT 1',
            'active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'date_upd' => "DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'",
        ];
        static::ensureColumns(BeesBlogCategory::SHOP_TABLE, $fields);
        if ($copyLegacyValues && static::columnExists(BeesBlogCategory::TABLE, 'active')) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.BeesBlogCategory::SHOP_TABLE.'` s'.
                ' INNER JOIN `'._DB_PREFIX_.BeesBlogCategory::TABLE.'` b ON b.`'.BeesBlogCategory::PRIMARY.'` = s.`'.BeesBlogCategory::PRIMARY.'`'.
                ' SET s.`id_parent` = b.`id_parent`, s.`position` = b.`position`,'.
                ' s.`active` = b.`active`, s.`date_upd` = b.`date_upd`'
            );
        }
    }

    /**
     * @param string $table
     * @param array $fields
     * @return void
     */
    protected static function ensureColumns($table, array $fields)
    {
        foreach ($fields as $field => $definition) {
            if (!static::columnExists($table, $field)) {
                if (!Db::getInstance()->execute(
                    'ALTER TABLE `'._DB_PREFIX_.bqSQL($table).'` ADD `'.bqSQL($field).'` '.$definition
                )) {
                    throw new PrestaShopException('Unable to add '.$table.'.'.$field);
                }
            }
        }
    }

    /**
     * @param string $table
     * @param string $primary
     * @return void
     */
    protected static function ensureOrphanAssociations($table, $primary)
    {
        $idShop = (int) \Configuration::get('PS_SHOP_DEFAULT');
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `'._DB_PREFIX_.bqSQL($table).'_shop` (`'.bqSQL($primary).'`, `id_shop`)'.
            ' SELECT b.`'.bqSQL($primary).'`, '.$idShop.
            ' FROM `'._DB_PREFIX_.bqSQL($table).'` b'.
            ' LEFT JOIN `'._DB_PREFIX_.bqSQL($table).'_shop` s ON s.`'.bqSQL($primary).'` = b.`'.bqSQL($primary).'`'.
            ' WHERE s.`'.bqSQL($primary).'` IS NULL'
        );
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $slugSize
     * @return void
     */
    protected static function migrateLanguageTable($table, $primary, $slugSize)
    {
        $langTable = $table.'_lang';
        if (!static::columnExists($langTable, 'id_shop')) {
            $temporary = $langTable.'_multistore';
            Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($temporary).'`');
            Db::getInstance()->execute(
                'CREATE TABLE `'._DB_PREFIX_.bqSQL($temporary).'` LIKE `'._DB_PREFIX_.bqSQL($langTable).'`'
            );
            Db::getInstance()->execute(
                'ALTER TABLE `'._DB_PREFIX_.bqSQL($temporary).'` DROP PRIMARY KEY,'.
                ' ADD `id_shop` INT(11) NOT NULL,'.
                ' ADD PRIMARY KEY (`'.bqSQL($primary).'`, `id_shop`, `id_lang`)'
            );

            $columns = static::getColumns($langTable);
            $quotedColumns = array_map(function ($column) {
                return '`'.bqSQL($column).'`';
            }, $columns);
            $sourceColumns = array_map(function ($column) {
                return 'l.`'.bqSQL($column).'`';
            }, $columns);

            if (!Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.bqSQL($temporary).'` ('.implode(', ', $quotedColumns).', `id_shop`)'.
                ' SELECT '.implode(', ', $sourceColumns).', s.`id_shop`'.
                ' FROM `'._DB_PREFIX_.bqSQL($langTable).'` l'.
                ' INNER JOIN `'._DB_PREFIX_.bqSQL($table).'_shop` s ON s.`'.bqSQL($primary).'` = l.`'.bqSQL($primary).'`'
            )) {
                throw new PrestaShopException('Unable to copy '.$langTable.' translations');
            }

            $legacy = $langTable.'_legacy_180';
            Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($legacy).'`');
            if (!Db::getInstance()->execute(
                'RENAME TABLE `'._DB_PREFIX_.bqSQL($langTable).'` TO `'._DB_PREFIX_.bqSQL($legacy).'`,'.
                ' `'._DB_PREFIX_.bqSQL($temporary).'` TO `'._DB_PREFIX_.bqSQL($langTable).'`'
            )) {
                throw new PrestaShopException('Unable to activate '.$langTable.' multistore schema');
            }
            Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.bqSQL($legacy).'`');
        } else {
            static::ensurePrimaryKey($langTable, [$primary, 'id_shop', 'id_lang']);
        }

        static::resolveSlugConflicts($langTable, $primary, $slugSize);
        static::ensureIndex($langTable, 'beesblog_slug_shop_lang', ['id_shop', 'id_lang', 'link_rewrite'], true);
    }

    /** @return void */
    protected static function migrateRelatedProducts()
    {
        $table = 'bees_blog_post_product';
        if (!static::tableExists($table)) {
            BeesBlogPost::createRelatedProductsTable();
            return;
        }
        if (static::columnExists($table, 'id_shop')) {
            static::ensurePrimaryKey($table, ['id_product', BeesBlogPost::PRIMARY, 'id_shop']);
            return;
        }

        $temporary = $table.'_multistore';
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($temporary).'`');
        Db::getInstance()->execute(
            'CREATE TABLE `'._DB_PREFIX_.bqSQL($temporary).'` ('.
            ' `id_product` INT(11) UNSIGNED NOT NULL,'.
            ' `'.BeesBlogPost::PRIMARY.'` INT(11) UNSIGNED NOT NULL,'.
            ' `id_shop` INT(11) NOT NULL,'.
            ' PRIMARY KEY (`id_product`, `'.BeesBlogPost::PRIMARY.'`, `id_shop`),'.
            ' KEY `beesblog_post_shop` (`'.BeesBlogPost::PRIMARY.'`, `id_shop`)'.
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `'._DB_PREFIX_.bqSQL($temporary).'` (`id_product`, `'.BeesBlogPost::PRIMARY.'`, `id_shop`)'.
            ' SELECT r.`id_product`, r.`'.BeesBlogPost::PRIMARY.'`, s.`id_shop`'.
            ' FROM `'._DB_PREFIX_.bqSQL($table).'` r'.
            ' INNER JOIN `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` s'.
            ' ON s.`'.BeesBlogPost::PRIMARY.'` = r.`'.BeesBlogPost::PRIMARY.'`'
        );
        $legacy = $table.'_legacy_180';
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($legacy).'`');
        Db::getInstance()->execute(
            'RENAME TABLE `'._DB_PREFIX_.bqSQL($table).'` TO `'._DB_PREFIX_.bqSQL($legacy).'`,'.
            ' `'._DB_PREFIX_.bqSQL($temporary).'` TO `'._DB_PREFIX_.bqSQL($table).'`'
        );
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.bqSQL($legacy).'`');
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $slugSize
     * @return void
     */
    protected static function resolveSlugConflicts($table, $primary, $slugSize)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_shop`, `id_lang`, `link_rewrite`, COUNT(*) AS duplicate_count'.
            ' FROM `'._DB_PREFIX_.bqSQL($table).'`'.
            ' GROUP BY `id_shop`, `id_lang`, `link_rewrite` HAVING duplicate_count > 1'
        );
        foreach ((array) $groups as $group) {
            $rows = Db::getInstance()->executeS(
                'SELECT `'.bqSQL($primary).'` FROM `'._DB_PREFIX_.bqSQL($table).'`'.
                ' WHERE `id_shop` = '.(int) $group['id_shop'].
                ' AND `id_lang` = '.(int) $group['id_lang'].
                ' AND `link_rewrite` = \''.pSQL($group['link_rewrite']).'\''.
                ' ORDER BY `'.bqSQL($primary).'` ASC'
            );
            array_shift($rows);
            foreach ($rows as $row) {
                $idObject = (int) $row[$primary];
                $suffix = '-'.$idObject;
                $base = mb_substr((string) $group['link_rewrite'], 0, max(1, $slugSize - mb_strlen($suffix)));
                $candidate = $base.$suffix;
                $counter = 2;
                while (Db::getInstance()->getValue(
                    'SELECT 1 FROM `'._DB_PREFIX_.bqSQL($table).'`'.
                    ' WHERE `id_shop` = '.(int) $group['id_shop'].
                    ' AND `id_lang` = '.(int) $group['id_lang'].
                    ' AND `link_rewrite` = \''.pSQL($candidate).'\''
                )) {
                    $extra = '-'.$counter++;
                    $candidate = mb_substr($base, 0, max(1, $slugSize - mb_strlen($suffix.$extra))).$suffix.$extra;
                }
                Db::getInstance()->update(
                    $table,
                    ['link_rewrite' => pSQL($candidate)],
                    '`'.bqSQL($primary).'` = '.$idObject.
                    ' AND `id_shop` = '.(int) $group['id_shop'].
                    ' AND `id_lang` = '.(int) $group['id_lang']
                );
            }
        }
    }

    /**
     * @param string $table
     * @param string[] $columns
     * @return void
     */
    protected static function ensurePrimaryKey($table, array $columns)
    {
        $current = [];
        foreach ((array) Db::getInstance()->executeS(
            'SHOW INDEX FROM `'._DB_PREFIX_.bqSQL($table).'` WHERE `Key_name` = \'PRIMARY\''
        ) as $row) {
            $current[(int) $row['Seq_in_index']] = $row['Column_name'];
        }
        ksort($current);
        if (array_values($current) === array_values($columns)) {
            return;
        }
        $parts = array_map(function ($column) {
            return '`'.bqSQL($column).'`';
        }, $columns);
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.bqSQL($table).'`'.
            ($current ? ' DROP PRIMARY KEY,' : '').' ADD PRIMARY KEY ('.implode(', ', $parts).')'
        );
    }

    /**
     * @param string $table
     * @param string $name
     * @param string[] $columns
     * @param bool $unique
     * @return void
     */
    protected static function ensureIndex($table, $name, array $columns, $unique = false)
    {
        if (Db::getInstance()->getValue(
            'SELECT 1 FROM `information_schema`.`statistics`'.
            ' WHERE `table_schema` = DATABASE()'.
            ' AND `table_name` = \'' . pSQL(_DB_PREFIX_.$table) . '\''.
            ' AND `index_name` = \'' . pSQL($name) . '\''
        )) {
            return;
        }
        $parts = array_map(function ($column) {
            return '`'.bqSQL($column).'`';
        }, $columns);
        Db::getInstance()->execute(
            'ALTER TABLE `'._DB_PREFIX_.bqSQL($table).'` ADD '.($unique ? 'UNIQUE ' : '').
            'KEY `'.bqSQL($name).'` ('.implode(', ', $parts).')'
        );
    }

    /** @return bool */
    protected static function tableExists($table)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE()'.
            ' AND `table_name` = \''.pSQL(_DB_PREFIX_.$table).'\''
        );
    }

    /** @return bool */
    protected static function columnExists($table, $column)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE()'.
            ' AND `table_name` = \''.pSQL(_DB_PREFIX_.$table).'\''.
            ' AND `column_name` = \''.pSQL($column).'\''
        );
    }

    /** @return string[] */
    protected static function getColumns($table)
    {
        return array_column(
            Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.bqSQL($table).'`'),
            'Field'
        );
    }
}
