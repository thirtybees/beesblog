<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

use Configuration;
use Db;
use PrestaShopException;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Persistent, resumable queue for responsive blog image generation.
 */
class BeesBlogResponsiveImageJob
{
    const TABLE = 'bees_blog_responsive_image_job';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const CONFIG_SCHEMA_VERSION = 'BEESBLOG_RESPONSIVE_JOB_SCHEMA_VERSION';
    const SCHEMA_VERSION = '1';

    /** @var bool */
    protected static $databaseReady = false;

    /** @return bool */
    public static function createDatabase()
    {
        if (!Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.static::TABLE.'` ('.
            ' `entity_type` VARCHAR(16) NOT NULL,'.
            ' `id_object` INT(11) UNSIGNED NOT NULL,'.
            ' `id_shop` INT(11) NOT NULL,'.
            ' `id_lang` INT(11) NOT NULL DEFAULT 0,'.
            ' `status` VARCHAR(16) NOT NULL DEFAULT \'pending\','. 
            ' `force_regeneration` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,'.
            ' `configuration_hash` VARCHAR(40) NOT NULL DEFAULT \'\','. 
            ' `error_message` VARCHAR(255) NOT NULL DEFAULT \'\','. 
            ' `date_upd` DATETIME NOT NULL,'.
            ' PRIMARY KEY (`entity_type`, `id_object`, `id_shop`, `id_lang`),'.
            ' KEY `bees_blog_responsive_job_status` (`id_shop`, `entity_type`, `status`)'.
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ) || !Configuration::updateGlobalValue(static::CONFIG_SCHEMA_VERSION, static::SCHEMA_VERSION)) {
            return false;
        }

        static::$databaseReady = true;

        return true;
    }

    /**
     * Perform the same-version FTP deployment repair once, then keep all
     * normal request paths free from repeated CREATE TABLE statements.
     *
     * @return bool
     */
    public static function ensureDatabase()
    {
        if (static::$databaseReady) {
            return true;
        }
        if ((string) Configuration::getGlobalValue(static::CONFIG_SCHEMA_VERSION) === static::SCHEMA_VERSION) {
            static::$databaseReady = true;

            return true;
        }

        return static::createDatabase();
    }

    /** @return bool */
    public static function dropDatabase()
    {
        if (!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.static::TABLE.'`')) {
            return false;
        }
        static::$databaseReady = false;

        return Configuration::deleteByName(static::CONFIG_SCHEMA_VERSION);
    }

    /**
     * Add jobs for new sources, remove orphaned jobs, and invalidate completed
     * jobs whose responsive output no longer matches the current settings.
     *
     * @param string $entityType
     * @param int[] $shopIds
     * @param bool $verifyFiles
     * @return bool
     * @throws PrestaShopException
     */
    public static function synchronize($entityType, array $shopIds, $verifyFiles = false)
    {
        static::assertEntityType($entityType);
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds || !static::ensureDatabase()) {
            return false;
        }

        $db = Db::getInstance();
        $sources = (array) $db->executeS(
            'SELECT i.`id_object`, i.`id_shop`, i.`id_lang`, i.`filename`,'.
            ' r.`generation`, r.`modern_extension`, r.`fallback_extension`, r.`widths`,'.
            ' r.`fallback_width`, r.`fallback_height`, r.`configuration_hash` AS manifest_hash,'.
            ' r.`status` AS manifest_status'.
            ' FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'` i'.
            ' LEFT JOIN `'._DB_PREFIX_.BeesBlogResponsiveImage::TABLE.'` r'.
            ' ON r.`entity_type` = i.`entity_type` AND r.`id_object` = i.`id_object`'.
            ' AND r.`id_shop` = i.`id_shop` AND r.`id_lang` = i.`id_lang`'.
            ' WHERE i.`entity_type` = \''.pSQL($entityType).'\''. 
            ' AND i.`id_shop` IN ('.implode(', ', $shopIds).')'
        );
        $jobs = (array) $db->executeS(
            'SELECT * FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
        );

        $jobsByKey = [];
        foreach ($jobs as $job) {
            $jobsByKey[static::getKey($job)] = $job;
        }

        $sourceKeys = [];
        foreach ($sources as $source) {
            $key = static::getKey($source);
            $sourceKeys[$key] = true;
            $targetHash = BeesBlogResponsiveImage::getConfigurationHash((int) $source['id_shop']);
            $job = isset($jobsByKey[$key]) ? $jobsByKey[$key] : null;
            $sourceAvailable = BeesBlogImage::resolveStoredPath(
                $entityType,
                $source['filename'],
                'original'
            );
            if (!$sourceAvailable) {
                static::saveJob(
                    $entityType,
                    (int) $source['id_object'],
                    (int) $source['id_shop'],
                    (int) $source['id_lang'],
                    static::STATUS_FAILED,
                    false,
                    $targetHash,
                    'The stored source image is missing or unreadable.'
                );
                continue;
            }
            $manifestConfigured = isset($source['manifest_status'], $source['manifest_hash'])
                && $source['manifest_status'] === 'ready'
                && hash_equals($targetHash, (string) $source['manifest_hash']);

            if (!$job) {
                $current = $manifestConfigured
                    && BeesBlogResponsiveImage::isManifestDataCurrent($entityType, $source, $targetHash);
                static::saveJob(
                    $entityType,
                    (int) $source['id_object'],
                    (int) $source['id_shop'],
                    (int) $source['id_lang'],
                    $current ? static::STATUS_COMPLETED : static::STATUS_PENDING,
                    false,
                    $targetHash,
                    ''
                );
                continue;
            }

            $status = (string) $job['status'];
            $jobHash = (string) $job['configuration_hash'];
            if ($jobHash !== $targetHash) {
                $current = $manifestConfigured
                    && BeesBlogResponsiveImage::isManifestDataCurrent($entityType, $source, $targetHash);
                static::saveJob(
                    $entityType,
                    (int) $source['id_object'],
                    (int) $source['id_shop'],
                    (int) $source['id_lang'],
                    $current ? static::STATUS_COMPLETED : static::STATUS_PENDING,
                    false,
                    $targetHash,
                    ''
                );
            } elseif ($status === static::STATUS_COMPLETED
                && (!$manifestConfigured
                    || ($verifyFiles
                        && !BeesBlogResponsiveImage::isManifestDataCurrent($entityType, $source, $targetHash)))
            ) {
                static::saveJob(
                    $entityType,
                    (int) $source['id_object'],
                    (int) $source['id_shop'],
                    (int) $source['id_lang'],
                    static::STATUS_PENDING,
                    false,
                    $targetHash,
                    ''
                );
            }
        }

        foreach ($jobs as $job) {
            if (!isset($sourceKeys[static::getKey($job)])) {
                $db->delete(
                    static::TABLE,
                    static::getWhere(
                        $entityType,
                        (int) $job['id_object'],
                        (int) $job['id_shop'],
                        (int) $job['id_lang']
                    )
                );
            }
        }

        // An interrupted HTTP request must not leave an item locked forever.
        return $db->execute(
            'UPDATE `'._DB_PREFIX_.static::TABLE.'` SET `status` = \''.static::STATUS_PENDING.'\''. 
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'.
            ' AND `status` = \''.static::STATUS_IN_PROGRESS.'\''. 
            ' AND `date_upd` < DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
    }

    /**
     * @param string $entityType
     * @param int[] $shopIds
     * @param bool $regenerateAll
     * @return bool
     * @throws PrestaShopException
     */
    public static function prepare($entityType, array $shopIds, $regenerateAll)
    {
        static::assertEntityType($entityType);
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds || !static::synchronize($entityType, $shopIds, true)) {
            return false;
        }

        if ($regenerateAll) {
            foreach ($shopIds as $idShop) {
                Db::getInstance()->update(
                    static::TABLE,
                    [
                        'status' => static::STATUS_PENDING,
                        'force_regeneration' => 1,
                        'configuration_hash' => BeesBlogResponsiveImage::getConfigurationHash($idShop),
                        'error_message' => '',
                        'date_upd' => date('Y-m-d H:i:s'),
                    ],
                    '`entity_type` = \''.pSQL($entityType).'\' AND `id_shop` = '.(int) $idShop
                );
            }

            return true;
        }

        return Db::getInstance()->update(
            static::TABLE,
            [
                'status' => static::STATUS_PENDING,
                'force_regeneration' => 0,
                'error_message' => '',
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            '`entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'.
            ' AND `status` = \''.static::STATUS_FAILED.'\''
        );
    }

    /**
     * Reset progress for every responsive source in the selected shops.
     * Generated files and active manifests are deliberately preserved; only
     * the queue state is reset so the merchant can regenerate them again.
     *
     * @param int[] $shopIds
     * @return bool
     * @throws PrestaShopException
     */
    public static function resetForShops(array $shopIds)
    {
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds
            || !static::synchronize(BeesBlogImage::ENTITY_POST, $shopIds, true)
            || !static::synchronize(BeesBlogImage::ENTITY_CATEGORY, $shopIds, true)
        ) {
            return false;
        }

        $database = Db::getInstance();
        foreach ($shopIds as $idShop) {
            if (!$database->update(
                static::TABLE,
                [
                    'status' => static::STATUS_PENDING,
                    'force_regeneration' => 1,
                    'configuration_hash' => BeesBlogResponsiveImage::getConfigurationHash($idShop),
                    'error_message' => '',
                    'date_upd' => date('Y-m-d H:i:s'),
                ],
                '`id_shop` = '.(int) $idShop
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate one queued image. Existing responsive output remains live until
     * the new generation has been built and validated successfully.
     *
     * @param string $entityType
     * @param int[] $shopIds
     * @return array
     * @throws PrestaShopException
     */
    public static function processNext($entityType, array $shopIds)
    {
        static::assertEntityType($entityType);
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds || !static::ensureDatabase()) {
            return ['processed' => false, 'error' => 'Unable to initialize the responsive image queue.'];
        }

        $db = Db::getInstance();
        $job = $db->getRow(
            'SELECT * FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'.
            ' AND `status` = \''.static::STATUS_PENDING.'\''. 
            ' ORDER BY `id_shop` ASC, `id_object` ASC, `id_lang` ASC'
        );
        if (!$job) {
            return ['processed' => false, 'error' => ''];
        }

        $where = static::getWhere(
            $entityType,
            (int) $job['id_object'],
            (int) $job['id_shop'],
            (int) $job['id_lang']
        );
        if (!$db->update(
            static::TABLE,
            ['status' => static::STATUS_IN_PROGRESS, 'date_upd' => date('Y-m-d H:i:s')],
            $where.' AND `status` = \''.static::STATUS_PENDING.'\''
        )) {
            return ['processed' => false, 'error' => 'Unable to claim the next responsive image job.'];
        }

        $sourceRow = $db->getRow(
            'SELECT `filename` FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'` WHERE '.$where
        );
        $source = $sourceRow
            ? BeesBlogImage::resolveStoredPath($entityType, $sourceRow['filename'], 'original')
            : false;
        $error = '';
        $success = $source && BeesBlogResponsiveImage::generate(
            $source,
            $entityType,
            (int) $job['id_object'],
            (int) $job['id_shop'],
            (int) $job['id_lang'],
            true,
            $error
        );
        if (!$source) {
            $error = 'The stored source image is missing or unreadable.';
        }

        static::saveJob(
            $entityType,
            (int) $job['id_object'],
            (int) $job['id_shop'],
            (int) $job['id_lang'],
            $success ? static::STATUS_COMPLETED : static::STATUS_FAILED,
            false,
            BeesBlogResponsiveImage::getConfigurationHash((int) $job['id_shop']),
            $success ? '' : $error
        );

        return [
            'processed' => true,
            'success' => (bool) $success,
            'error' => $success ? '' : $error,
        ];
    }

    /**
     * @param string $entityType
     * @param int[] $shopIds
     * @return array
     * @throws PrestaShopException
     */
    public static function getStatus($entityType, array $shopIds, $verifyFiles = false, $synchronize = true)
    {
        static::assertEntityType($entityType);
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds || ($synchronize && !static::synchronize($entityType, $shopIds, $verifyFiles))) {
            return static::emptyStatus($entityType);
        }

        // Progress follows writes made by the immediately preceding AJAX
        // request, so read the primary connection rather than a lagging slave.
        $row = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total,'.
            ' SUM(CASE WHEN `status` = \''.static::STATUS_COMPLETED.'\' THEN 1 ELSE 0 END) AS completed,'.
            ' SUM(CASE WHEN `status` IN (\''.static::STATUS_PENDING.'\', \''.static::STATUS_IN_PROGRESS.'\') THEN 1 ELSE 0 END) AS pending,'.
            ' SUM(CASE WHEN `status` = \''.static::STATUS_FAILED.'\' THEN 1 ELSE 0 END) AS failed'.
            ' FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
        );

        return [
            'entity_type' => $entityType,
            'display_name' => $entityType === BeesBlogImage::ENTITY_POST ? 'Posts' : 'Categories',
            'total' => (int) $row['total'],
            'completed' => (int) $row['completed'],
            'pending' => (int) $row['pending'],
            'failed' => (int) $row['failed'],
        ];
    }

    /** @return array */
    public static function getStatuses(array $shopIds, $verifyFiles = false, $synchronize = true)
    {
        return [
            BeesBlogImage::ENTITY_POST => static::getStatus(
                BeesBlogImage::ENTITY_POST,
                $shopIds,
                $verifyFiles,
                $synchronize
            ),
            BeesBlogImage::ENTITY_CATEGORY => static::getStatus(
                BeesBlogImage::ENTITY_CATEGORY,
                $shopIds,
                $verifyFiles,
                $synchronize
            ),
        ];
    }

    /**
     * Keep queue status synchronized when an upload or another caller creates
     * a responsive generation outside the Back Office queue.
     *
     * @return bool
     */
    public static function markCompleted($entityType, $idObject, $idShop, $idLang, $configurationHash)
    {
        static::assertEntityType($entityType);
        if (!static::ensureDatabase()) {
            return false;
        }

        return static::saveJob(
            $entityType,
            (int) $idObject,
            (int) $idShop,
            max(0, (int) $idLang),
            static::STATUS_COMPLETED,
            false,
            (string) $configurationHash,
            ''
        );
    }

    /** @return bool */
    public static function deleteForShops($entityType, $idObject, array $shopIds, $idLang = null)
    {
        static::assertEntityType($entityType);
        $shopIds = static::normalizeShopIds($shopIds);
        if (!$shopIds) {
            return true;
        }
        if (!static::ensureDatabase()) {
            return false;
        }
        $where = '`entity_type` = \''.pSQL($entityType).'\' AND `id_object` = '.(int) $idObject.
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')';
        if ($idLang !== null) {
            $where .= ' AND `id_lang` = '.max(0, (int) $idLang);
        }

        return Db::getInstance()->delete(static::TABLE, $where);
    }

    /** @return array */
    protected static function emptyStatus($entityType)
    {
        return [
            'entity_type' => $entityType,
            'display_name' => $entityType === BeesBlogImage::ENTITY_POST ? 'Posts' : 'Categories',
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'failed' => 0,
        ];
    }

    /** @return bool */
    protected static function saveJob($entityType, $idObject, $idShop, $idLang, $status, $force, $hash, $error)
    {
        return Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.static::TABLE.'`'.
            ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `status`, `force_regeneration`,'.
            ' `configuration_hash`, `error_message`, `date_upd`) VALUES'.
            ' (\''.pSQL($entityType).'\', '.(int) $idObject.', '.(int) $idShop.', '.max(0, (int) $idLang).','.
            ' \''.pSQL($status).'\', '.(int) (bool) $force.', \''.pSQL($hash).'\','.
            ' \''.pSQL(substr((string) $error, 0, 255)).'\', NOW())'.
            ' ON DUPLICATE KEY UPDATE `status` = VALUES(`status`),'.
            ' `force_regeneration` = VALUES(`force_regeneration`),'.
            ' `configuration_hash` = VALUES(`configuration_hash`),'.
            ' `error_message` = VALUES(`error_message`), `date_upd` = NOW()'
        );
    }

    /** @return string */
    protected static function getWhere($entityType, $idObject, $idShop, $idLang)
    {
        return '`entity_type` = \''.pSQL($entityType).'\' AND `id_object` = '.(int) $idObject.
            ' AND `id_shop` = '.(int) $idShop.' AND `id_lang` = '.max(0, (int) $idLang);
    }

    /** @return string */
    protected static function getKey(array $row)
    {
        return (int) $row['id_object'].'|'.(int) $row['id_shop'].'|'.max(0, (int) $row['id_lang']);
    }

    /** @return int[] */
    protected static function normalizeShopIds(array $shopIds)
    {
        return array_values(array_filter(array_unique(array_map('intval', $shopIds))));
    }

    /** @return void */
    protected static function assertEntityType($entityType)
    {
        if (!in_array($entityType, [BeesBlogImage::ENTITY_POST, BeesBlogImage::ENTITY_CATEGORY], true)) {
            throw new PrestaShopException('Invalid responsive blog image entity type');
        }
    }
}
