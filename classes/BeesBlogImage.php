<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

use Context;
use Db;
use ImageManager;
use PrestaShopException;
use Shop;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Stores and resolves shop-scoped blog images with optional language overrides.
 *
 * Language ID 0 represents the default image for a shop. Existing global
 * files remain valid and are used as the final compatibility fallback.
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
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.static::TABLE.'` ('.
            ' `entity_type` VARCHAR(16) NOT NULL,'.
            ' `id_object` INT(11) UNSIGNED NOT NULL,'.
            ' `id_shop` INT(11) NOT NULL,'.
            ' `id_lang` INT(11) NOT NULL DEFAULT 0,'.
            ' `filename` VARCHAR(255) NOT NULL,'.
            ' `date_upd` DATETIME NOT NULL,'.
            ' PRIMARY KEY (`entity_type`, `id_object`, `id_shop`, `id_lang`),'.
            ' KEY `bees_blog_image_shop` (`id_shop`, `entity_type`)'.
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return bool
     */
    public static function dropDatabase()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.static::TABLE.'`');
    }

    /**
     * Resolve a language override, then a shop default, then a legacy image.
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

        if ($idShop && $idLang > 0) {
            $filename = static::getFilename($entityType, $idObject, $idShop, $idLang);
            if ($path = static::resolveStoredPath($entityType, $filename, $imageType)) {
                return $path;
            }
        }

        if ($idShop) {
            $filename = static::getFilename($entityType, $idObject, $idShop, 0);
            if ($path = static::resolveStoredPath($entityType, $filename, $imageType)) {
                return $path;
            }
            // An explicit empty shop-default row is a tombstone. It lets a
            // merchant remove a legacy shared image in one shop without
            // deleting the file still used as fallback by other shops.
            if ($filename === '') {
                return false;
            }

            // The post shop table already had an image column, although old
            // module versions never populated it consistently. Honour it for
            // installations or integrations which did use the field.
            if ($entityType === static::ENTITY_POST) {
                $filename = (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                    'SELECT `image` FROM `'._DB_PREFIX_.BeesBlogPost::SHOP_TABLE.'`'.
                    ' WHERE `'.BeesBlogPost::PRIMARY.'` = '.$idObject.' AND `id_shop` = '.$idShop
                );
                if ($path = static::resolveStoredPath($entityType, $filename, $imageType)) {
                    return $path;
                }
            }
        }

        return static::resolveLegacyPath($entityType, $idObject, $imageType);
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
        $filename = static::getFilename($entityType, (int) $idObject, (int) $idShop, (int) $idLang);

        return static::resolveStoredPath($entityType, $filename, $imageType);
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
            $error
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
     * @return bool
     * @throws PrestaShopException
     */
    public static function saveImageFile($sourceFile, $entityType, $idObject, array $shopIds, $idLang = 0, &$error = null)
    {
        static::assertEntityType($entityType);
        $idObject = (int) $idObject;
        $idLang = max(0, (int) $idLang);
        $shopIds = array_values(array_filter(array_unique(array_map('intval', $shopIds))));
        $extension = strtolower((string) ImageManager::getImageExtension($sourceFile));
        if (in_array($extension, ['jpeg', 'jpe', 'pjpeg'], true)) {
            $extension = 'jpg';
        } elseif ($extension === 'x-png') {
            $extension = 'png';
        }
        $allowed = ImageManager::getAllowedImageExtensions(false, true);
        if (!$idObject || !$shopIds || !$extension || !in_array($extension, $allowed, true)) {
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

            if (!ImageManager::resize($sourceFile, $temporaryOriginal, null, null, $extension, true)) {
                $error = 'Unable to write the uploaded image.';
                static::deleteGeneratedFiles(array_keys($generated));
                return false;
            }
            $generated[$temporaryOriginal] = $directory.$base.'.'.$extension;

            foreach (static::getImageTypes($entityType, $idShop) as $imageType) {
                $temporaryVariant = $directory.$temporaryBase.'-'.$imageType['name'].'.'.$extension;
                if (!ImageManager::resize(
                    $temporaryOriginal,
                    $temporaryVariant,
                    (int) $imageType['width'],
                    (int) $imageType['height'],
                    $extension,
                    true
                )) {
                    $error = 'Unable to generate image type '.$imageType['name'].'.';
                    static::deleteGeneratedFiles(array_keys($generated));
                    @unlink($temporaryOriginal);
                    return false;
                }
                $generated[$temporaryVariant] = $directory.$base.'-'.$imageType['name'].'.'.$extension;
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
                ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`, `date_upd`) VALUES'.
                " ('".pSQL($entityType)."', ".$idObject.', '.$idShop.', '.$idLang.", '".pSQL($filename)."', NOW())".
                ' ON DUPLICATE KEY UPDATE `filename` = VALUES(`filename`), `date_upd` = NOW()'
            )) {
                $error = 'Unable to save image association.';
                return false;
            }

            if ($entityType === static::ENTITY_POST && $idLang === 0) {
                Db::getInstance()->update(
                    BeesBlogPost::SHOP_TABLE,
                    ['image' => pSQL($filename)],
                    '`'.BeesBlogPost::PRIMARY.'` = '.$idObject.' AND `id_shop` = '.$idShop
                );
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

        $result = Db::getInstance()->delete(static::TABLE, $where);
        if ($idLang !== null && (int) $idLang === 0) {
            foreach ($shopIds as $idShop) {
                $result = Db::getInstance()->execute(
                    'INSERT INTO `'._DB_PREFIX_.static::TABLE.'`'.
                    ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`, `date_upd`) VALUES'.
                    " ('".pSQL($entityType)."', ".$idObject.', '.$idShop.", 0, '', NOW())".
                    ' ON DUPLICATE KEY UPDATE `filename` = \'\', `date_upd` = NOW()'
                ) && $result;
            }
        }
        if ($entityType === static::ENTITY_POST && ($idLang === null || (int) $idLang === 0)) {
            Db::getInstance()->update(
                BeesBlogPost::SHOP_TABLE,
                ['image' => ''],
                '`'.BeesBlogPost::PRIMARY.'` = '.$idObject.' AND `id_shop` IN ('.implode(', ', $shopIds).')'
            );
        }

        return $result;
    }

    /**
     * Delete legacy global files after the final entity association is gone.
     *
     * @param string $entityType
     * @param int $idObject
     * @return void
     * @throws PrestaShopException
     */
    public static function deleteLegacyImages($entityType, $idObject)
    {
        static::assertEntityType($entityType);
        static::deleteFilesForBase($entityType, (string) (int) $idObject);
    }

    /**
     * Duplicate explicit source-shop images into independent target files.
     * Legacy images need no rows and remain available through fallback.
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
                if (!static::saveEmptyAssociation(
                    $row['entity_type'],
                    (int) $row['id_object'],
                    $newShopId,
                    (int) $row['id_lang']
                )) {
                    return false;
                }
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
     * Regenerate configured variants for explicit and legacy source images.
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
                'SELECT `filename` FROM `'._DB_PREFIX_.static::TABLE.'`'.
                ' WHERE `entity_type` = \''.pSQL($entityType).'\''.
                ' AND `id_shop` IN ('.implode(', ', $shopIds).')'
            ) as $row) {
                $path = static::resolveStoredPath($entityType, $row['filename'], 'original');
                if ($path) {
                    $sources[$path] = $path;
                }
            }
        }

        $directory = static::getDirectory($entityType);
        if (is_dir($directory)) {
            $extensions = array_map('preg_quote', ImageManager::getAllowedImageExtensions(false, true));
            foreach (scandir($directory) as $filename) {
                if (preg_match('/^[0-9]+\.(?:'.implode('|', $extensions).')$/i', $filename)) {
                    $sources[$directory.$filename] = $directory.$filename;
                }
            }
        }

        foreach ($sources as $source) {
            $extension = strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
            $base = substr(basename($source), 0, -(strlen($extension) + 1));
            foreach ($formats as $format) {
                $target = $directory.$base.'-'.$format['name'].'.'.$extension;
                if ($deleteOldImages && file_exists($target)) {
                    @unlink($target);
                }
                if (!file_exists($target) && !ImageManager::resize(
                    $source,
                    $target,
                    (int) $format['width'],
                    (int) $format['height'],
                    $extension,
                    true
                )) {
                    $errors[] = 'Failed to resize image file '.$source.' to '.$format['name'].'.';
                }
                if ((bool) \Configuration::get('PS_HIGHT_DPI')) {
                    $highDpiTarget = $directory.$base.'-'.$format['name'].'2x.'.$extension;
                    if ($deleteOldImages && file_exists($highDpiTarget)) {
                        @unlink($highDpiTarget);
                    }
                    if (!file_exists($highDpiTarget) && !ImageManager::resize(
                        $source,
                        $highDpiTarget,
                        (int) $format['width'] * 2,
                        (int) $format['height'] * 2,
                        $extension,
                        true
                    )) {
                        $errors[] = 'Failed to resize high-DPI image file '.$source.' to '.$format['name'].'.';
                    }
                }
            }
        }

        return $errors;
    }

    /** @return string|null */
    protected static function getFilename($entityType, $idObject, $idShop, $idLang)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT `filename` FROM `'._DB_PREFIX_.static::TABLE.'`'.
            ' WHERE `entity_type` = \''.pSQL($entityType).'\''.
            ' AND `id_object` = '.(int) $idObject.
            ' AND `id_shop` = '.(int) $idShop.
            ' AND `id_lang` = '.max(0, (int) $idLang)
        );

        return is_array($row) && array_key_exists('filename', $row) ? (string) $row['filename'] : null;
    }

    /** @return bool */
    protected static function saveEmptyAssociation($entityType, $idObject, $idShop, $idLang)
    {
        return Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.static::TABLE.'`'.
            ' (`entity_type`, `id_object`, `id_shop`, `id_lang`, `filename`, `date_upd`) VALUES'.
            " ('".pSQL($entityType)."', ".(int) $idObject.', '.(int) $idShop.', '.max(0, (int) $idLang).", '', NOW())".
            ' ON DUPLICATE KEY UPDATE `filename` = \'\', `date_upd` = NOW()'
        );
    }

    /** @return string|false */
    protected static function resolveStoredPath($entityType, $filename, $imageType)
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
        $base = substr($filename, 0, -(strlen($extension) + 1));
        $variant = $directory.$base.'-'.$imageType.'.'.$extension;

        return file_exists($variant) ? $variant : $original;
    }

    /** @return string|false */
    protected static function resolveLegacyPath($entityType, $idObject, $imageType)
    {
        $directory = static::getDirectory($entityType);
        $candidates = [];
        foreach (ImageManager::getAllowedImageExtensions(false, true) as $extension) {
            $original = $directory.(int) $idObject.'.'.$extension;
            clearstatcache(true, $original);
            if (file_exists($original)) {
                $candidates[$original] = (int) filemtime($original);
            }
        }
        arsort($candidates);
        foreach (array_keys($candidates) as $original) {
            $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
            if ($imageType === 'original') {
                return $original;
            }
            $variant = $directory.(int) $idObject.'-'.$imageType.'.'.$extension;
            return file_exists($variant) ? $variant : $original;
        }

        return false;
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
