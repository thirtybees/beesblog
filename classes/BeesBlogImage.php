<?php
/**
 * Copyright (C) 2017-2026 thirty bees
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
 * @copyright 2017-2026 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

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
 * Stores and resolves shop-scoped blog images with optional language overrides.
 * Language ID 0 represents the default image for a shop.
 */
class BeesBlogImage
{
    const TABLE = 'bees_blog_image';
    const ENTITY_POST = 'posts';
    const ENTITY_CATEGORY = 'categories';

    /**
     * @return bool
     */
    public static function createDatabase()
    {
        $database = Db::getInstance();
        if (!$database->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.static::TABLE.'` ('.
            ' `entity_type` VARCHAR(16) NOT NULL,'.
            ' `id_object` INT(11) UNSIGNED NOT NULL,'.
            ' `id_shop` INT(11) NOT NULL,'.
            ' `id_lang` INT(11) NOT NULL DEFAULT 0,'.
            ' `filename` VARCHAR(255) NOT NULL,'.
            ' `thumbnail_extension` VARCHAR(16) NOT NULL DEFAULT \'\','.
            ' `date_upd` DATETIME NOT NULL,'.
            ' PRIMARY KEY (`entity_type`, `id_object`, `id_shop`, `id_lang`),'.
            ' KEY `bees_blog_image_shop` (`id_shop`, `entity_type`)'.
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        )) {
            return false;
        }

        if (!$database->getValue(
            'SELECT 1 FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE()'.
            ' AND `table_name` = \''.pSQL(_DB_PREFIX_.static::TABLE).'\''.
            ' AND `column_name` = \'thumbnail_extension\''
        )) {
            return $database->execute(
                'ALTER TABLE `'._DB_PREFIX_.static::TABLE.'`'.
                ' ADD `thumbnail_extension` VARCHAR(16) NOT NULL DEFAULT \'\' AFTER `filename`'
            );
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function dropDatabase()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.static::TABLE.'`');
    }

    /**
     * Resolve a language override, then a shop default.
     *
     * @param string $entityType
     * @param int $idObject
     * @param string $imageType
     * @param int|null $idShop
     * @param int|null $idLang Use 0 to resolve only the shop default/fallback.
     * @return string|false
     * @throws PrestaShopException
     */
    public static function getImagePath($entityType, $idObject, $imageType, $idShop = null, $idLang = null)
    {
        static::assertEntityType($entityType);
        $idObject = (int) $idObject;
        if (!$idObject) {
            return false;
        }

        $context = Context::getContext();
        $idShop = $idShop === null ? (int) $context->shop->id : (int) $idShop;
        $idLang = $idLang === null ? (int) $context->language->id : (int) $idLang;

        if (!$idShop) {
            return false;
        }

        $languageIds = $idLang > 0 ? [$idLang, 0] : [0];
        $rows = (array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `filename`, `thumbnail_extension` FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''.
            ' AND `id_object` = '.$idObject.
            ' AND `id_shop` = '.$idShop.
            ' AND `id_lang` IN ('.implode(', ', array_map('intval', $languageIds)).')'.
            ' ORDER BY `id_lang` DESC'
        );
        foreach ($rows as $row) {
            if ($path = static::resolveStoredPath(
                $entityType,
                $row['filename'],
                $imageType,
                $row['thumbnail_extension']
            )) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Resolve only an explicitly stored shop/language image.
     *
     * @param string $entityType
     * @param int $idObject
     * @param string $imageType
     * @param int $idShop
     * @param int $idLang
     * @return string|false
     * @throws PrestaShopException
     */
    public static function getScopedImagePath($entityType, $idObject, $imageType, $idShop, $idLang)
    {
        static::assertEntityType($entityType);
        $record = static::getRecord($entityType, (int) $idObject, (int) $idShop, (int) $idLang);

        return static::resolveStoredPath(
            $entityType,
            $record ? $record['filename'] : null,
            $imageType,
            $record ? $record['thumbnail_extension'] : null
        );
    }

    /**
     * Validate and store one uploaded image in every supplied shop.
     *
     * @param array $file A single $_FILES entry
     * @param string $entityType
     * @param int $idObject
     * @param int[] $shopIds
     * @param int $idLang 0 for the shop default, otherwise a language override
     * @param string|null $error
     * @return bool
     * @throws PrestaShopException
     */
    public static function saveUploadedImage(array $file, $entityType, $idObject, array $shopIds, $idLang = 0, &$error = null)
    {
        static::assertEntityType($entityType);
        $error = ImageManager::validateUpload($file, 4000000);
        if ($error) {
            return false;
        }

        return static::saveImageFile(
            $file['tmp_name'],
            $entityType,
            (int) $idObject,
            $shopIds,
            (int) $idLang,
            $error,
            pathinfo((string) $file['name'], PATHINFO_EXTENSION)
        );
    }

    /**
     * Store a validated local image file. Public for migration/integration use.
     *
     * @param string $sourceFile
     * @param string $entityType
     * @param int $idObject
     * @param int[] $shopIds
     * @param int $idLang
     * @param string|null $error
     * @param string|null $originalExtension Extension from the original upload name
     * @return bool
     * @throws PrestaShopException
     */
    public static function saveImageFile(
        $sourceFile,
        $entityType,
        $idObject,
        array $shopIds,
        $idLang = 0,
        &$error = null,
        $originalExtension = null
    )
    {
        static::assertEntityType($entityType);
        $idObject = (int) $idObject;
        $idLang = max(0, (int) $idLang);
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        $detectedExtension = strtolower((string) ImageManager::getImageExtension($sourceFile));
        $extension = strtolower((string) ($originalExtension ?: pathinfo($sourceFile, PATHINFO_EXTENSION)));
        $allowed = ImageManager::getAllowedImageExtensions(false, true);
        $thumbnailExtension = strtolower((string) ImageManager::getDefaultImageExtension());
        $allowedThumbnailExtensions = ImageManager::getAllowedImageExtensions(true, true);
        $extensionFamily = static::getExtensionFamily($extension);
        if (!$idObject || !$shopIds || !$detectedExtension || !$extension || !$thumbnailExtension
            || !in_array($extension, $allowed, true) || $extensionFamily !== $detectedExtension
            || !in_array($thumbnailExtension, $allowedThumbnailExtensions, true)
        ) {
            $error = 'Image format not recognized or no target shop was selected.';
            return false;
        }

        $directory = static::getDirectory($entityType);
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            $error = 'Unable to create image directory: '.$directory;
            return false;
        }

        foreach ($shopIds as $idShop) {
            if (!Shop::getShop($idShop)) {
                $error = 'Invalid target shop: '.$idShop;
                return false;
            }

            $base = static::getScopeBaseName($idObject, $idShop, $idLang);
            $token = substr(sha1(uniqid('', true)), 0, 10);
            $temporaryBase = $base.'-new-'.$token;
            $temporaryOriginal = $directory.$temporaryBase.'.'.$extension;
            $generated = [];

            if (!copy($sourceFile, $temporaryOriginal)) {
                $error = 'Unable to write the uploaded image.';
                return false;
            }
            $generated[$temporaryOriginal] = $directory.$base.'.'.$extension;

            foreach (static::getImageTypes($entityType, $idShop) as $imageType) {
                $temporaryVariant = $directory.$temporaryBase.'-'.$imageType['name'].'.'.$thumbnailExtension;
                if (!ImageManager::resize(
                    $temporaryOriginal,
                    $temporaryVariant,
                    (int) $imageType['width'],
                    (int) $imageType['height'],
                    $thumbnailExtension,
                    true
                )) {
                    $error = 'Unable to generate image type '.$imageType['name'].'.';
                    static::deleteGeneratedFiles(array_keys($generated));
                    @unlink($temporaryOriginal);
                    return false;
                }
                $generated[$temporaryVariant] = $directory.$base.'-'.$imageType['name'].'.'.$thumbnailExtension;

                if ((bool) \Configuration::get('PS_HIGHT_DPI')) {
                    $temporaryHighDpiVariant = $directory.$temporaryBase.'-'.$imageType['name'].'2x.'.$thumbnailExtension;
                    if (!ImageManager::resize(
                        $temporaryOriginal,
                        $temporaryHighDpiVariant,
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2,
                        $thumbnailExtension,
                        true
                    )) {
                        $error = 'Unable to generate high-DPI image type '.$imageType['name'].'.';
                        static::deleteGeneratedFiles(array_keys($generated));
                        @unlink($temporaryOriginal);
                        return false;
                    }
                    $generated[$temporaryHighDpiVariant] = $directory.$base.'-'.$imageType['name'].'2x.'.$thumbnailExtension;
                }
            }

            static::deleteFilesForBase($entityType, $base);
            $activated = [];
            foreach ($generated as $temporary => $target) {
                if (!@rename($temporary, $target)) {
                    $error = 'Unable to activate generated image '.$target.'.';
                    static::deleteGeneratedFiles(array_keys($generated));
                    static::deleteGeneratedFiles($activated);
                    return false;
                }
                $activated[] = $target;
            }

            $filename = $base.'.'.$extension;
            if (!Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.static::TABLE.'`'.
                ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`, `thumbnail_extension`, `date_upd`) VALUES'.
                " ('".pSQL($entityType)."', ".$idObject.', '.$idShop.', '.$idLang.", '".pSQL($filename)."', '".
                pSQL($thumbnailExtension)."', NOW())".
                ' ON DUPLICATE KEY UPDATE `filename` = VALUES(`filename`),'.
                ' `thumbnail_extension` = VALUES(`thumbnail_extension`), `date_upd` = NOW()'
            )) {
                $error = 'Unable to save image association.';
                return false;
            }

        }

        return true;
    }

    /**
     * Delete one scope (language ID supplied) or every image for target shops.
     *
     * @param string $entityType
     * @param int $idObject
     * @param int[] $shopIds
     * @param int|null $idLang
     * @return bool
     * @throws PrestaShopException
     */
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
            'SELECT `filename` FROM `'._DB_PREFIX_.static::TABLE.'` WHERE '.$where
        );
        foreach ($rows as $row) {
            static::deleteFilesForFilename($entityType, $row['filename']);
        }

        return Db::getInstance()->delete(static::TABLE, $where);
    }

    /**
     * Duplicate explicit source-shop images into independent target files.
     *
     * @param int $oldShopId
     * @param int $newShopId
     * @return bool
     * @throws PrestaShopException
     */
    public static function duplicateShop($oldShopId, $newShopId)
    {
        $oldShopId = (int) $oldShopId;
        $newShopId = (int) $newShopId;
        $rows = (array) Db::getInstance()->executeS(
            'SELECT `entity_type`, `id_object`, `id_lang`, `filename`'.
            ' FROM `'._DB_PREFIX_.static::TABLE.'` WHERE `id_shop` = '.$oldShopId
        );
        foreach ($rows as $row) {
            if ($row['filename'] === '') {
                continue;
            }
            $source = static::resolveStoredPath($row['entity_type'], $row['filename'], 'original');
            $error = null;
            if (!$source || !static::saveImageFile(
                $source,
                $row['entity_type'],
                (int) $row['id_object'],
                [$newShopId],
                (int) $row['id_lang'],
                $error
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert pre-1.9 global/post_shop images into explicit shop defaults.
     * Legacy files are removed only after every associated shop for an object
     * has either been migrated or already has an explicit image record.
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function migrateLegacyImages()
    {
        $entities = [
            static::ENTITY_POST => [
                'table' => BeesBlogPost::SHOP_TABLE,
                'primary' => BeesBlogPost::PRIMARY,
                'image_column' => true,
            ],
            static::ENTITY_CATEGORY => [
                'table' => BeesBlogCategory::SHOP_TABLE,
                'primary' => BeesBlogCategory::PRIMARY,
                'image_column' => false,
            ],
        ];

        foreach ($entities as $entityType => $definition) {
            $rows = (array) Db::getInstance()->executeS(
                'SELECT `'.bqSQL($definition['primary']).'` AS `id_object`, `id_shop`'.
                ($definition['image_column'] ? ', `image`' : ", '' AS `image`").
                ' FROM `'._DB_PREFIX_.bqSQL($definition['table']).'`'.
                ' ORDER BY `'.bqSQL($definition['primary']).'`, `id_shop`'
            );
            $objects = [];
            foreach ($rows as $row) {
                $objects[(int) $row['id_object']][] = $row;
            }

            foreach ($objects as $idObject => $shopRows) {
                $sharedLegacyPath = static::findLegacyOriginalPath($entityType, $idObject);
                foreach ($shopRows as $shopRow) {
                    $idShop = (int) $shopRow['id_shop'];
                    $record = static::getRecord($entityType, $idObject, $idShop, 0);
                    $filename = $record ? $record['filename'] : null;
                    $storedPath = static::resolveStoredPath($entityType, $filename, 'original');
                    $expectedPrefix = static::getScopeBaseName($idObject, $idShop, 0).'.';

                    // Empty rows from an intermediate 1.9 build represented
                    // an explicit deletion. Preserve that decision while the
                    // shared legacy source still exists; the row is removed
                    // after conversion because no fallback remains afterward.
                    if ($filename === '') {
                        continue;
                    }
                    if ($storedPath && strpos(basename($storedPath), $expectedPrefix) === 0) {
                        continue;
                    }

                    $shopLegacyPath = static::resolveStoredPath($entityType, $shopRow['image'], 'original');
                    $sourcePath = $storedPath ?: static::getNewestExistingPath([
                        $shopLegacyPath,
                        $sharedLegacyPath,
                    ]);
                    if (!$sourcePath) {
                        continue;
                    }

                    $error = null;
                    if (!static::saveImageFile(
                        $sourcePath,
                        $entityType,
                        $idObject,
                        [$idShop],
                        0,
                        $error
                    )) {
                        throw new PrestaShopException(
                            'Unable to migrate '.$entityType.' image #'.$idObject.' for shop #'.$idShop.
                            ($error ? ': '.$error : '')
                        );
                    }
                }

                // All usable legacy sources for this object are now copied to
                // independent shop files. Removing them makes reruns naturally
                // idempotent and keeps legacy checks out of the request path.
                static::deleteFilesForBase($entityType, (string) $idObject);
            }
        }

        // Intermediate 1.9 builds did not persist the derivative extension.
        // Regenerate only those derivatives while copying each original
        // byte-for-byte through saveImageFile().
        foreach ((array) Db::getInstance()->executeS(
            'SELECT `entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`'.
            ' FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `filename` != \'\' AND `thumbnail_extension` = \'\''
        ) as $row) {
            $sourcePath = static::resolveStoredPath($row['entity_type'], $row['filename'], 'original');
            if (!$sourcePath) {
                continue;
            }
            $error = null;
            if (!static::saveImageFile(
                $sourcePath,
                $row['entity_type'],
                (int) $row['id_object'],
                [(int) $row['id_shop']],
                (int) $row['id_lang'],
                $error,
                pathinfo($row['filename'], PATHINFO_EXTENSION)
            )) {
                throw new PrestaShopException(
                    'Unable to regenerate migrated '.$row['entity_type'].' image #'.(int) $row['id_object'].
                    ' for shop #'.(int) $row['id_shop'].($error ? ': '.$error : '')
                );
            }
        }

        if (!Db::getInstance()->delete(static::TABLE, '`filename` = \'\'')) {
            return false;
        }

        // Kept as schema fields for ObjectModel compatibility, but no longer
        // used as a second source of truth for image loading.
        return Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'` SET `image` = \'\''
        ) && Db::getInstance()->execute(
            'UPDATE `'._DB_PREFIX_.BeesBlogPost::TABLE.'` SET `image` = \'\''
        );
    }

    /**
     * Regenerate configured variants for explicit source images.
     *
     * @param string $entityType
     * @param array[] $formats
     * @param int[] $shopIds
     * @param bool $deleteOldImages
     * @return string[] Errors
     * @throws PrestaShopException
     */
    public static function regenerateThumbnails($entityType, array $formats, array $shopIds, $deleteOldImages = false)
    {
        static::assertEntityType($entityType);
        $errors = [];
        $sources = [];
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        if ($shopIds) {
            foreach ((array) Db::getInstance()->executeS(
                'SELECT `entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`, `thumbnail_extension`'.
                ' FROM `'._DB_PREFIX_.static::TABLE.'`'.
                ' WHERE `entity_type` = \''.pSQL($entityType).'\''.
                ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
            ) as $row) {
                $path = static::resolveStoredPath($entityType, $row['filename'], 'original');
                if ($path) {
                    $sources[$path] = $row;
                }
            }
        }

        $directory = static::getDirectory($entityType);
        $configuredThumbnailExtension = strtolower((string) ImageManager::getDefaultImageExtension());
        $imageTypesByShop = [];

        foreach ($sources as $source => $row) {
            $originalExtension = strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
            $base = substr(basename($source), 0, -(strlen($originalExtension) + 1));
            $sourceFailed = false;
            $idShop = (int) $row['id_shop'];
            if (!isset($imageTypesByShop[$idShop])) {
                $imageTypesByShop[$idShop] = static::getImageTypes($entityType, $idShop);
            }
            $availableNames = array_map('strval', array_column($imageTypesByShop[$idShop], 'name'));
            $requestedNames = array_map('strval', array_column($formats, 'name'));
            $regeneratesEveryFormat = !array_diff($availableNames, $requestedNames);
            $storedThumbnailExtension = strtolower((string) $row['thumbnail_extension']);
            $thumbnailExtension = $regeneratesEveryFormat || !$storedThumbnailExtension
                ? $configuredThumbnailExtension
                : $storedThumbnailExtension;
            foreach ($formats as $format) {
                if ($deleteOldImages) {
                    static::deleteVariantFiles($entityType, $base, $format['name']);
                }
                $target = $directory.$base.'-'.$format['name'].'.'.$thumbnailExtension;
                if (!file_exists($target) && !ImageManager::resize(
                    $source,
                    $target,
                    (int) $format['width'],
                    (int) $format['height'],
                    $thumbnailExtension,
                    true
                )) {
                    $errors[] = 'Failed to resize image file '.$source.' to '.$format['name'].'.';
                    $sourceFailed = true;
                }
                if ((bool) \Configuration::get('PS_HIGHT_DPI')) {
                    $highDpiTarget = $directory.$base.'-'.$format['name'].'2x.'.$thumbnailExtension;
                    if (!file_exists($highDpiTarget) && !ImageManager::resize(
                        $source,
                        $highDpiTarget,
                        (int) $format['width'] * 2,
                        (int) $format['height'] * 2,
                        $thumbnailExtension,
                        true
                    )) {
                        $errors[] = 'Failed to resize high-DPI image file '.$source.' to '.$format['name'].'.';
                        $sourceFailed = true;
                    }
                }
            }
            if (!$sourceFailed && ($regeneratesEveryFormat || !$storedThumbnailExtension)) {
                Db::getInstance()->update(
                    static::TABLE,
                    ['thumbnail_extension' => pSQL($thumbnailExtension)],
                    '`entity_type` = \''.pSQL($row['entity_type']).'\''.
                    ' AND `id_object` = '.(int) $row['id_object'].
                    ' AND `id_shop` = '.(int) $row['id_shop'].
                    ' AND `id_lang` = '.(int) $row['id_lang']
                );
            }
        }

        return $errors;
    }

    /** @return array|null */
    protected static function getRecord($entityType, $idObject, $idShop, $idLang)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT `filename`, `thumbnail_extension` FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''.
            ' AND `id_object` = '.(int) $idObject.
            ' AND `id_shop` = '.(int) $idShop.
            ' AND `id_lang` = '.max(0, (int) $idLang)
        );

        return is_array($row) && array_key_exists('filename', $row) ? $row : null;
    }

    /** @return string|false */
    protected static function resolveStoredPath($entityType, $filename, $imageType, $thumbnailExtension = null)
    {
        $filename = basename((string) $filename);
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$/', $filename)) {
            return false;
        }
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, ImageManager::getAllowedImageExtensions(false, true), true)) {
            return false;
        }
        $directory = static::getDirectory($entityType);
        $original = $directory.$filename;
        if (!file_exists($original)) {
            return false;
        }
        if ($imageType === 'original') {
            return $original;
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string) $imageType)) {
            return false;
        }
        $thumbnailExtension = strtolower((string) $thumbnailExtension);
        if (!$thumbnailExtension
            || !in_array($thumbnailExtension, ImageManager::getAllowedImageExtensions(true, true), true)
        ) {
            return $original;
        }
        $base = substr($filename, 0, -(strlen($extension) + 1));
        $variant = $directory.$base.'-'.$imageType.'.'.$thumbnailExtension;

        return file_exists($variant) ? $variant : $original;
    }

    /** @return string|false */
    protected static function findLegacyOriginalPath($entityType, $idObject)
    {
        $directory = static::getDirectory($entityType);
        $candidates = [];
        foreach (ImageManager::getAllowedImageExtensions(false, true) as $extension) {
            $candidates[] = $directory.(int) $idObject.'.'.$extension;
        }

        return static::getNewestExistingPath($candidates);
    }

    /** @return string|false */
    protected static function getNewestExistingPath(array $paths)
    {
        $candidates = [];
        foreach (array_filter($paths, 'is_string') as $path) {
            clearstatcache(true, $path);
            if (file_exists($path)) {
                $candidates[$path] = (int) filemtime($path);
            }
        }
        arsort($candidates);

        return $candidates ? (string) key($candidates) : false;
    }

    /** @return string|null */
    protected static function getExtensionFamily($extension)
    {
        $extension = strtolower((string) $extension);
        foreach (Media::getFileInformations('images') as $mainExtension => $information) {
            if (in_array($extension, $information['extensions'], true)) {
                return (string) $mainExtension;
            }
        }

        return null;
    }

    /** @return array[] */
    protected static function getImageTypes($entityType, $idShop)
    {
        return (array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT it.`name`, it.`width`, it.`height`'.
            ' FROM `'._DB_PREFIX_.BeesBlogImageType::TABLE.'` it'.
            ' INNER JOIN `'._DB_PREFIX_.BeesBlogImageType::SHOP_TABLE.'` its'.
            ' ON its.`'.BeesBlogImageType::PRIMARY.'` = it.`'.BeesBlogImageType::PRIMARY.'`'.
            ' WHERE its.`id_shop` = '.(int) $idShop.' AND it.`'.bqSQL($entityType).'` = 1'.
            ' ORDER BY it.`name` ASC'
        );
    }

    /** @return string */
    protected static function getScopeBaseName($idObject, $idShop, $idLang)
    {
        return (int) $idObject.'-s'.(int) $idShop.($idLang > 0 ? '-l'.(int) $idLang : '');
    }

    /** @return string */
    protected static function getDirectory($entityType)
    {
        return rtrim(_PS_IMG_DIR_, '/\\').DIRECTORY_SEPARATOR.'beesblog'.DIRECTORY_SEPARATOR.
            $entityType.DIRECTORY_SEPARATOR;
    }

    /** @return void */
    protected static function deleteFilesForFilename($entityType, $filename)
    {
        $filename = basename((string) $filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!$extension) {
            return;
        }
        static::deleteFilesForBase($entityType, substr($filename, 0, -(strlen($extension) + 1)));
    }

    /** @return void */
    protected static function deleteFilesForBase($entityType, $base)
    {
        $directory = static::getDirectory($entityType);
        if (!is_dir($directory) || !preg_match('/^[a-zA-Z0-9_-]+$/', (string) $base)) {
            return;
        }
        $suffixes = [''];
        foreach (static::getAllImageTypeNames($entityType) as $name) {
            $suffixes[] = '-'.$name;
            $suffixes[] = '-'.$name.'2x';
        }
        foreach (ImageManager::getAllowedImageExtensions(false, true) as $extension) {
            foreach ($suffixes as $suffix) {
                $path = $directory.$base.$suffix.'.'.$extension;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /** @return void */
    protected static function deleteVariantFiles($entityType, $base, $imageType)
    {
        $directory = static::getDirectory($entityType);
        if (!is_dir($directory)
            || !preg_match('/^[a-zA-Z0-9_-]+$/', (string) $base)
            || !preg_match('/^[a-zA-Z0-9_-]+$/', (string) $imageType)
        ) {
            return;
        }
        foreach (ImageManager::getAllowedImageExtensions(false, true) as $extension) {
            foreach (['-'.$imageType, '-'.$imageType.'2x'] as $suffix) {
                $path = $directory.$base.$suffix.'.'.$extension;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /** @return string[] */
    protected static function getAllImageTypeNames($entityType)
    {
        return array_map('strval', array_column((array) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `name` FROM `'._DB_PREFIX_.BeesBlogImageType::TABLE.'`'.
            ' WHERE `'.bqSQL($entityType).'` = 1'
        ), 'name'));
    }

    /** @return void */
    protected static function deleteGeneratedFiles(array $files)
    {
        foreach ($files as $file) {
            if (is_string($file) && file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /** @return void */
    protected static function assertEntityType($entityType)
    {
        if (!in_array($entityType, [static::ENTITY_POST, static::ENTITY_CATEGORY], true)) {
            throw new PrestaShopException('Invalid BeesBlog image entity type');
        }
    }
}
