<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

use Configuration;
use Context;
use Db;
use ImageManager;
use Media;
use PrestaShopException;
use Shop;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Generates and resolves proportional responsive blog images.
 *
 * Each database row is a manifest for one shop/language image. Variants live
 * in a versioned directory, so regeneration can build and validate a complete
 * replacement before it makes that replacement visible on the storefront.
 */
class BeesBlogResponsiveImage
{
    const TABLE = 'bees_blog_responsive_image';
    const CONFIG_WIDTHS = 'BEESBLOG_RESPONSIVE_WIDTHS';
    const DEFAULT_WIDTHS = '320,480,640,768,1024,1280,1536,1920';
    const MIN_WIDTH = 160;
    const MAX_WIDTH = 3840;
    const MAX_CANDIDATES = 12;

    /** @return bool */
    public static function createDatabase()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.static::TABLE.'` ('.
            ' `entity_type` VARCHAR(16) NOT NULL,'.
            ' `id_object` INT(11) UNSIGNED NOT NULL,'.
            ' `id_shop` INT(11) NOT NULL,'.
            ' `id_lang` INT(11) NOT NULL DEFAULT 0,'.
            ' `generation` VARCHAR(40) NOT NULL DEFAULT \'\','.
            ' `modern_extension` VARCHAR(16) NOT NULL DEFAULT \'\','.
            ' `fallback_extension` VARCHAR(16) NOT NULL DEFAULT \'\','.
            ' `widths` VARCHAR(255) NOT NULL DEFAULT \'\','.
            ' `fallback_width` INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
            ' `fallback_height` INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
            ' `configuration_hash` VARCHAR(40) NOT NULL DEFAULT \'\','.
            ' `status` VARCHAR(16) NOT NULL DEFAULT \'pending\','.
            ' `error_message` VARCHAR(255) NOT NULL DEFAULT \'\','.
            ' `date_upd` DATETIME NOT NULL,'.
            ' PRIMARY KEY (`entity_type`, `id_object`, `id_shop`, `id_lang`),'.
            ' KEY `bees_blog_responsive_status` (`id_shop`, `status`, `entity_type`)'.
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return bool */
    public static function dropDatabase()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.static::TABLE.'`');
    }

    /** @return bool */
    public static function installConfiguration()
    {
        if (Configuration::getGlobalValue(static::CONFIG_WIDTHS) === false) {
            return Configuration::updateGlobalValue(static::CONFIG_WIDTHS, static::DEFAULT_WIDTHS);
        }

        return true;
    }

    /**
     * Strictly normalize a merchant-supplied comma-separated width list.
     *
     * @param string|array $value
     * @param string|null $error
     * @return string|false
     */
    public static function normalizeWidths($value, &$error = null)
    {
        $error = null;
        $parts = is_array($value) ? $value : explode(',', (string) $value);
        $widths = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '' || !preg_match('/^[0-9]+$/', $part)) {
                $error = 'Enter widths as comma-separated whole numbers.';
                return false;
            }
            $width = (int) $part;
            if ($width < static::MIN_WIDTH || $width > static::MAX_WIDTH) {
                $error = 'Each responsive width must be between '.static::MIN_WIDTH.' and '.static::MAX_WIDTH.' pixels.';
                return false;
            }
            $widths[$width] = $width;
        }
        if (!$widths) {
            $error = 'At least one responsive width is required.';
            return false;
        }
        if (count($widths) > static::MAX_CANDIDATES) {
            $error = 'Use no more than '.static::MAX_CANDIDATES.' responsive widths.';
            return false;
        }
        sort($widths, SORT_NUMERIC);

        return implode(',', $widths);
    }

    /** @return int[] */
    public static function getConfiguredWidths($idShop = null)
    {
        $idShop = $idShop === null ? (int) Context::getContext()->shop->id : (int) $idShop;
        $idShopGroup = $idShop ? (int) Shop::getGroupFromShop($idShop) : null;
        $value = Configuration::get(static::CONFIG_WIDTHS, null, $idShopGroup, $idShop ?: null);
        $error = null;
        $normalized = static::normalizeWidths($value, $error);
        if ($normalized === false) {
            $normalized = static::DEFAULT_WIDTHS;
        }

        return array_map('intval', explode(',', $normalized));
    }

    /** @return string */
    public static function getConfigurationHash($idShop = null)
    {
        $idShop = $idShop === null ? (int) Context::getContext()->shop->id : (int) $idShop;

        return sha1(
            implode(',', static::getConfiguredWidths($idShop)).'|'.
            strtolower((string) ImageManager::getDefaultImageExtension())
        );
    }

    /**
     * Validate a manifest row against the current settings and its files.
     * Queue queries alias manifest fields to avoid collisions with job fields.
     *
     * @param string $entityType
     * @param array $row
     * @param string|null $configurationHash
     * @return bool
     */
    public static function isManifestDataCurrent($entityType, array $row, $configurationHash = null)
    {
        $manifest = $row;
        if (array_key_exists('manifest_status', $row)) {
            $manifest['status'] = $row['manifest_status'];
        }
        if (array_key_exists('manifest_hash', $row)) {
            $manifest['configuration_hash'] = $row['manifest_hash'];
        }
        $configurationHash = $configurationHash === null
            ? static::getConfigurationHash((int) $row['id_shop'])
            : (string) $configurationHash;

        return !empty($manifest['configuration_hash'])
            && hash_equals($configurationHash, (string) $manifest['configuration_hash'])
            && (bool) static::buildResponsiveData(
                $entityType,
                (int) $row['id_object'],
                (int) $row['id_shop'],
                (int) $row['id_lang'],
                $manifest
            );
    }

    /**
     * Generate a complete responsive set for one exact image scope.
     *
     * @return bool
     */
    public static function generate(
        $sourceFile,
        $entityType,
        $idObject,
        $idShop,
        $idLang = 0,
        $preserveCurrent = false,
        &$error = null
    ) {
        static::assertEntityType($entityType);
        $idObject = (int) $idObject;
        $idShop = (int) $idShop;
        $idLang = max(0, (int) $idLang);
        $error = null;

        $imageInfo = @getimagesize($sourceFile);
        if (!$idObject || !$idShop || !$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            $error = 'Unable to read the source image dimensions.';
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }

        $sourceWidth = (int) $imageInfo[0];
        $configuredWidths = static::getConfiguredWidths($idShop);
        $widths = array_values(array_filter($configuredWidths, function ($width) use ($sourceWidth) {
            return (int) $width <= $sourceWidth;
        }));
        if (!$widths) {
            // A tiny source still gets one proportional candidate, never an upscale.
            $widths = [$sourceWidth];
        }

        $modernExtension = strtolower((string) ImageManager::getDefaultImageExtension());
        if (!in_array($modernExtension, ImageManager::getAllowedImageExtensions(true, true), true)) {
            $error = 'The configured thirty bees image format is not available on this server.';
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }
        $fallbackExtension = static::getFallbackExtension($imageInfo);
        $generation = date('YmdHis').'-'.substr(sha1(uniqid('', true)), 0, 12);
        $scopeRoot = static::getScopeRoot($entityType, $idObject, $idShop, $idLang);
        $temporaryDirectory = $scopeRoot.'.tmp-'.$generation.DIRECTORY_SEPARATOR;
        $finalDirectory = $scopeRoot.$generation.DIRECTORY_SEPARATOR;
        $oldManifest = static::getManifest($entityType, $idObject, $idShop, $idLang);

        if (!is_dir($scopeRoot) && !mkdir($scopeRoot, 0777, true)) {
            $error = 'Unable to create the responsive image directory.';
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }
        if (!mkdir($temporaryDirectory, 0777, true)) {
            $error = 'Unable to create a temporary responsive image directory.';
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }

        foreach ($widths as $width) {
            $target = $temporaryDirectory.(int) $width.'.'.$modernExtension;
            $generatedHeight = 0;
            if (!static::resizeAndVerify(
                $sourceFile,
                $target,
                (int) $width,
                $modernExtension,
                $generatedHeight
            )) {
                $error = 'Unable to generate the '.(int) $width.'px responsive image.';
                static::deleteDirectory($temporaryDirectory);
                static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
                return false;
            }
        }

        $fallbackWidth = (int) end($widths);
        reset($widths);
        $fallbackHeight = 0;
        $fallbackTarget = $temporaryDirectory.'fallback-'.$fallbackWidth.'.'.$fallbackExtension;
        if (!static::resizeAndVerify(
            $sourceFile,
            $fallbackTarget,
            $fallbackWidth,
            $fallbackExtension,
            $fallbackHeight
        )) {
            $error = 'Unable to generate the legacy fallback image.';
            static::deleteDirectory($temporaryDirectory);
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }

        if (!@rename(rtrim($temporaryDirectory, '/\\'), rtrim($finalDirectory, '/\\'))) {
            $error = 'Unable to activate the generated responsive images.';
            static::deleteDirectory($temporaryDirectory);
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }

        $widthString = implode(',', array_map('intval', $widths));
        $saved = static::saveManifest([
            'entity_type' => $entityType,
            'id_object' => $idObject,
            'id_shop' => $idShop,
            'id_lang' => $idLang,
            'generation' => $generation,
            'modern_extension' => $modernExtension,
            'fallback_extension' => $fallbackExtension,
            'widths' => $widthString,
            'fallback_width' => $fallbackWidth,
            'fallback_height' => $fallbackHeight,
            'configuration_hash' => static::getConfigurationHash($idShop),
            'status' => 'ready',
            'error_message' => '',
        ]);
        if (!$saved) {
            $error = 'Unable to save the responsive image manifest.';
            static::deleteDirectory($finalDirectory);
            static::recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent);
            return false;
        }

        BeesBlogResponsiveImageJob::markCompleted(
            $entityType,
            $idObject,
            $idShop,
            $idLang,
            static::getConfigurationHash($idShop)
        );

        if ($oldManifest && $oldManifest['generation'] !== $generation) {
            static::deleteGeneration($entityType, $idObject, $idShop, $idLang, $oldManifest['generation']);
        }

        return true;
    }

    /**
     * Build storefront data for a collection of objects with one database query.
     *
     * @return array<int,array>
     */
    public static function getImagesData($entityType, array $objectIds, $legacyType, $idShop = null, $idLang = null)
    {
        static::assertEntityType($entityType);
        $objectIds = array_values(array_filter(array_unique(array_map('intval', $objectIds))));
        if (!$objectIds) {
            return [];
        }
        $context = Context::getContext();
        $idShop = $idShop === null ? (int) $context->shop->id : (int) $idShop;
        $idLang = $idLang === null ? (int) $context->language->id : max(0, (int) $idLang);
        if (!$idShop) {
            return [];
        }
        $languageIds = $idLang > 0 ? [$idLang, 0] : [0];
        $rows = (array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT i.`id_object`, i.`id_lang`, i.`filename`, i.`thumbnail_extension`,'.
            ' r.`generation`, r.`modern_extension`, r.`fallback_extension`, r.`widths`,'.
            ' r.`fallback_width`, r.`fallback_height`, r.`status`'.
            ' FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'` i'.
            ' LEFT JOIN `'._DB_PREFIX_.static::TABLE.'` r'.
            ' ON r.`entity_type` = i.`entity_type` AND r.`id_object` = i.`id_object`'.
            ' AND r.`id_shop` = i.`id_shop` AND r.`id_lang` = i.`id_lang`'.
            ' WHERE i.`entity_type` = \''.pSQL($entityType).'\''. 
            ' AND i.`id_object` IN ('.implode(', ', $objectIds).')'.
            ' AND i.`id_shop` = '.$idShop.
            ' AND i.`id_lang` IN ('.implode(', ', array_map('intval', $languageIds)).')'.
            ' ORDER BY i.`id_object` ASC, i.`id_lang` DESC'
        );

        $result = [];
        foreach ($rows as $row) {
            $idObject = (int) $row['id_object'];
            if (isset($result[$idObject])) {
                continue;
            }
            $original = BeesBlogImage::resolveStoredPath($entityType, $row['filename'], 'original');
            if (!$original) {
                continue;
            }
            $data = static::buildResponsiveData($entityType, $idObject, $idShop, (int) $row['id_lang'], $row);
            if (!$data) {
                $legacy = BeesBlogImage::resolveStoredPath(
                    $entityType,
                    $row['filename'],
                    $legacyType,
                    $row['thumbnail_extension']
                );
                $data = static::buildLegacyData($legacy);
            }
            if ($data) {
                $result[$idObject] = $data;
            }
        }

        return $result;
    }

    /** @return array|false */
    public static function getImageData($entityType, $idObject, $legacyType, $idShop = null, $idLang = null)
    {
        $images = static::getImagesData($entityType, [(int) $idObject], $legacyType, $idShop, $idLang);

        return isset($images[(int) $idObject]) ? $images[(int) $idObject] : false;
    }

    /** @return string[] */
    public static function regenerateForShops($entityType, array $shopIds)
    {
        static::assertEntityType($entityType);
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        if (!$shopIds) {
            return [];
        }
        $errors = [];
        $rows = (array) Db::getInstance()->executeS(
            'SELECT `id_object`, `id_shop`, `id_lang`, `filename`'.
            ' FROM `'._DB_PREFIX_.BeesBlogImage::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_shop` IN ('.implode(', ', $shopIds).') AND `filename` != \'\''
        );
        foreach ($rows as $row) {
            $source = BeesBlogImage::resolveStoredPath($entityType, $row['filename'], 'original');
            $error = null;
            if ($source && !static::generate(
                $source,
                $entityType,
                (int) $row['id_object'],
                (int) $row['id_shop'],
                (int) $row['id_lang'],
                true,
                $error
            )) {
                $errors[] = 'Responsive image #'.(int) $row['id_object'].' (shop '.(int) $row['id_shop'].
                    ', language '.(int) $row['id_lang'].'): '.$error;
            }
        }

        return $errors;
    }

    /** @return bool */
    public static function deleteForShops($entityType, $idObject, array $shopIds, $idLang = null)
    {
        static::assertEntityType($entityType);
        $idObject = (int) $idObject;
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        if (!$idObject || !$shopIds) {
            return false;
        }
        $where = '`entity_type` = \''.pSQL($entityType).'\' AND `id_object` = '.$idObject.
            ' AND `id_shop` IN ('.implode(', ', $shopIds).')';
        if ($idLang !== null) {
            $where .= ' AND `id_lang` = '.max(0, (int) $idLang);
        }
        $rows = (array) Db::getInstance()->executeS(
            'SELECT `id_shop`, `id_lang` FROM `'._DB_PREFIX_.static::TABLE.'` WHERE '.$where
        );
        foreach ($rows as $row) {
            static::deleteDirectory(static::getScopeRoot(
                $entityType,
                $idObject,
                (int) $row['id_shop'],
                (int) $row['id_lang']
            ));
        }

        return Db::getInstance()->delete(static::TABLE, $where);
    }

    /** @return array|null */
    public static function getManifest($entityType, $idObject, $idShop, $idLang)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''. 
            ' AND `id_object` = '.(int) $idObject.
            ' AND `id_shop` = '.(int) $idShop.
            ' AND `id_lang` = '.max(0, (int) $idLang)
        );

        return is_array($row) && !empty($row['entity_type']) ? $row : null;
    }

    /** @return array|false */
    protected static function buildResponsiveData($entityType, $idObject, $idShop, $idLang, array $row)
    {
        if ($row['status'] !== 'ready'
            || !preg_match('/^[0-9]{14}-[a-f0-9]{12}$/', (string) $row['generation'])
            || !preg_match('/^[a-z0-9]+$/', (string) $row['modern_extension'])
            || !preg_match('/^[a-z0-9]+$/', (string) $row['fallback_extension'])
        ) {
            return false;
        }
        $widths = static::parseStoredWidths($row['widths']);
        if (!$widths) {
            return false;
        }
        $directory = static::getScopeRoot($entityType, $idObject, $idShop, $idLang).
            $row['generation'].DIRECTORY_SEPARATOR;
        $srcset = [];
        foreach ($widths as $width) {
            $path = $directory.$width.'.'.$row['modern_extension'];
            if (!file_exists($path)) {
                return false;
            }
            $srcset[] = static::getMediaUrl($path).' '.$width.'w';
        }
        $fallback = $directory.'fallback-'.(int) $row['fallback_width'].'.'.$row['fallback_extension'];
        if (!file_exists($fallback)) {
            return false;
        }
        $fileInformation = Media::getFileInformations('images', $row['modern_extension']);
        $mimeType = is_array($fileInformation) && !empty($fileInformation['mimeType'])
            ? $fileInformation['mimeType']
            : 'image/'.$row['modern_extension'];

        return [
            'modern_type' => $mimeType,
            'srcset' => implode(', ', $srcset),
            'fallback_url' => static::getMediaUrl($fallback),
            'width' => (int) $row['fallback_width'],
            'height' => (int) $row['fallback_height'],
        ];
    }

    /** @return array|false */
    protected static function buildLegacyData($path)
    {
        if (!$path || !file_exists($path)) {
            return false;
        }
        $imageInfo = @getimagesize($path);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return false;
        }

        return [
            'modern_type' => '',
            'srcset' => '',
            'fallback_url' => static::getMediaUrl($path),
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
        ];
    }

    /** @return bool */
    protected static function resizeAndVerify($source, $target, $width, $extension, &$actualHeight)
    {
        $actualHeight = 0;
        $sourceInfo = @getimagesize($source);
        if (!$sourceInfo || empty($sourceInfo[0]) || empty($sourceInfo[1])
            || !ImageManager::checkImageMemoryLimit($source)
        ) {
            return false;
        }
        $actualHeight = max(1, (int) round((int) $sourceInfo[1] * ((int) $width / (int) $sourceInfo[0])));
        $sourceImage = ImageManager::create((int) $sourceInfo[2], $source);
        $targetImage = imagecreatetruecolor((int) $width, $actualHeight);
        if (!$sourceImage || !$targetImage) {
            if ($sourceImage) {
                @imagedestroy($sourceImage);
            }
            if ($targetImage) {
                @imagedestroy($targetImage);
            }
            return false;
        }

        if (in_array($extension, ['png', 'webp', 'avif'], true)) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, (int) $width, $actualHeight, $transparent);
        } else {
            $white = imagecolorallocate($targetImage, 255, 255, 255);
            imagefilledrectangle($targetImage, 0, 0, (int) $width, $actualHeight, $white);
        }
        $resampled = imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            (int) $width,
            $actualHeight,
            (int) $sourceInfo[0],
            (int) $sourceInfo[1]
        );
        @imagedestroy($sourceImage);
        if (!$resampled) {
            @imagedestroy($targetImage);
            return false;
        }
        // ImageManager::write() applies the shop quality setting and destroys
        // the target resource whether the encoder succeeds or fails.
        if (!ImageManager::write($extension, $targetImage, $target)) {
            return false;
        }

        $info = @getimagesize($target);
        if ($info) {
            $actualHeight = (int) $info[1];
        }

        return $info && (int) $info[0] === (int) $width && $actualHeight > 0;
    }

    /** @return string */
    protected static function getFallbackExtension(array $imageInfo)
    {
        $type = isset($imageInfo[2]) ? (int) $imageInfo[2] : 0;

        return in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true) ? 'png' : 'jpg';
    }

    /** @return bool */
    protected static function saveManifest(array $values)
    {
        return Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.static::TABLE.'`'.
            ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `generation`, `modern_extension`,'.
            ' `fallback_extension`, `widths`, `fallback_width`, `fallback_height`,'.
            ' `configuration_hash`, `status`, `error_message`, `date_upd`) VALUES'.
            ' (\''.pSQL($values['entity_type']).'\', '.(int) $values['id_object'].', '.(int) $values['id_shop'].
            ', '.(int) $values['id_lang'].', \''.pSQL($values['generation']).'\', \''.
            pSQL($values['modern_extension']).'\', \''.pSQL($values['fallback_extension']).'\', \''.
            pSQL($values['widths']).'\', '.(int) $values['fallback_width'].', '.(int) $values['fallback_height'].
            ', \''.pSQL($values['configuration_hash']).'\', \''.pSQL($values['status']).'\', \''.
            pSQL($values['error_message']).'\', NOW())'.
            ' ON DUPLICATE KEY UPDATE `generation` = VALUES(`generation`),'.
            ' `modern_extension` = VALUES(`modern_extension`),'.
            ' `fallback_extension` = VALUES(`fallback_extension`), `widths` = VALUES(`widths`),'.
            ' `fallback_width` = VALUES(`fallback_width`), `fallback_height` = VALUES(`fallback_height`),'.
            ' `configuration_hash` = VALUES(`configuration_hash`), `status` = VALUES(`status`),'.
            ' `error_message` = VALUES(`error_message`), `date_upd` = NOW()'
        );
    }

    /** @return void */
    protected static function recordFailure($entityType, $idObject, $idShop, $idLang, $error, $preserveCurrent)
    {
        $current = static::getManifest($entityType, $idObject, $idShop, $idLang);
        if ($preserveCurrent && $current) {
            return;
        }
        static::saveManifest([
            'entity_type' => $entityType,
            'id_object' => (int) $idObject,
            'id_shop' => (int) $idShop,
            'id_lang' => max(0, (int) $idLang),
            'generation' => '',
            'modern_extension' => '',
            'fallback_extension' => '',
            'widths' => '',
            'fallback_width' => 0,
            'fallback_height' => 0,
            'configuration_hash' => '',
            'status' => 'failed',
            'error_message' => substr((string) $error, 0, 255),
        ]);
        if ($current && !empty($current['generation'])) {
            static::deleteGeneration($entityType, $idObject, $idShop, $idLang, $current['generation']);
        }
    }

    /** @return string */
    protected static function getScopeRoot($entityType, $idObject, $idShop, $idLang)
    {
        $base = (int) $idObject.'-s'.(int) $idShop.($idLang > 0 ? '-l'.(int) $idLang : '');

        return rtrim(_PS_IMG_DIR_, '/\\').DIRECTORY_SEPARATOR.'beesblog'.DIRECTORY_SEPARATOR.
            $entityType.DIRECTORY_SEPARATOR.'responsive'.DIRECTORY_SEPARATOR.$base.DIRECTORY_SEPARATOR;
    }

    /** @return void */
    protected static function deleteGeneration($entityType, $idObject, $idShop, $idLang, $generation)
    {
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/', (string) $generation)) {
            static::deleteDirectory(
                static::getScopeRoot($entityType, $idObject, $idShop, $idLang).$generation.DIRECTORY_SEPARATOR
            );
        }
    }

    /** @return void */
    protected static function deleteDirectory($directory)
    {
        $directory = rtrim((string) $directory, '/\\');
        if (!$directory || strpos($directory, DIRECTORY_SEPARATOR.'beesblog'.DIRECTORY_SEPARATOR) === false
            || strpos($directory, DIRECTORY_SEPARATOR.'responsive'.DIRECTORY_SEPARATOR) === false
            || !is_dir($directory)
        ) {
            return;
        }
        foreach ((array) scandir($directory) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $directory.DIRECTORY_SEPARATOR.$name;
            if (is_dir($path)) {
                static::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }

    /** @return int[] */
    protected static function parseStoredWidths($value)
    {
        $widths = [];
        foreach (explode(',', (string) $value) as $part) {
            if (!preg_match('/^[0-9]+$/', $part) || (int) $part < 1) {
                return [];
            }
            $widths[] = (int) $part;
        }

        return $widths;
    }

    /** @return string */
    protected static function getMediaUrl($path)
    {
        // Media::getMediaPath() keeps Windows directory separators when the
        // shop is developed on Windows; URLs must always use forward slashes.
        $mediaPath = str_replace('\\', '/', Media::getMediaPath($path));

        return Context::getContext()->link->getMediaLink($mediaPath);
    }

    /** @return void */
    protected static function assertEntityType($entityType)
    {
        if (!in_array($entityType, [BeesBlogImage::ENTITY_POST, BeesBlogImage::ENTITY_CATEGORY], true)) {
            throw new PrestaShopException('Invalid blog image entity type');
        }
    }
}
