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

use Db;
use Hook;
use PrestaShopException;
use Shop;
use Validate;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Provides context-scoped persistence for module ObjectModels.
 *
 * Core ObjectModel::add() writes fk_shop language rows for every shop, even
 * when id_shop_list contains a smaller set. That behaviour is unsuitable for
 * blog content created in an individual shop or shop-group context.
 */
trait BeesBlogMultistoreObjectModelTrait
{
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (isset($this->id) && !$this->force_id) {
            unset($this->id);
        }

        $shopIds = $this->getWriteShopIds();
        if (!$shopIds) {
            throw new PrestaShopException('No authorized shop was selected');
        }

        $this->id_shop_list = $shopIds;
        if (!$this->id_shop) {
            $this->id_shop = (int) reset($shopIds);
        }

        Hook::triggerEvent('actionObjectAddBefore', ['object' => $this]);
        Hook::triggerEvent('actionObject'.get_class($this).'AddBefore', ['object' => $this]);

        if ($autoDate && property_exists($this, 'date_add')) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        if ($autoDate && property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');
        }

        $connection = Db::getInstance();
        $connection->execute('START TRANSACTION');

        try {
            if (!$connection->insert($this->def['table'], $this->getFieldsPrimary(), $nullValues)) {
                throw new PrestaShopException('Unable to insert '.get_class($this));
            }

            $this->id = (int) $connection->Insert_ID();
            $shopFields = $this->getFieldsShop();
            $shopFields[$this->def['primary']] = (int) $this->id;

            foreach ($shopIds as $idShop) {
                $shopFields['id_shop'] = (int) $idShop;
                if (!$connection->insert($this->def['table'].'_shop', $shopFields, $nullValues)) {
                    throw new PrestaShopException('Unable to insert shop data for '.get_class($this));
                }
            }

            if (!empty($this->def['multilang'])) {
                foreach ((array) $this->getFieldsLang() as $languageFields) {
                    foreach (array_keys($languageFields) as $key) {
                        if (!Validate::isTableOrIdentifier($key)) {
                            throw new PrestaShopException('Invalid field name '.$key);
                        }
                    }

                    $languageFields[$this->def['primary']] = (int) $this->id;
                    foreach ($shopIds as $idShop) {
                        $languageFields['id_shop'] = (int) $idShop;
                        if (!$connection->insert($this->def['table'].'_lang', $languageFields, $nullValues)) {
                            throw new PrestaShopException('Unable to insert translations for '.get_class($this));
                        }
                    }
                }
            }

            Hook::triggerEvent('actionObjectAddAfter', ['object' => $this]);
            Hook::triggerEvent('actionObject'.get_class($this).'AddAfter', ['object' => $this]);
            $connection->execute('COMMIT');

            return true;
        } catch (\Throwable $e) {
            $connection->execute('ROLLBACK');
            unset($this->id);
            throw $e;
        }
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $shopIds = $this->getWriteShopIds();
        if (!$shopIds) {
            throw new PrestaShopException('No authorized shop was selected');
        }

        $this->id_shop_list = $shopIds;
        $connection = Db::getInstance();
        $connection->execute('START TRANSACTION');

        try {
            // ObjectModel only creates missing _shop rows in a single-shop
            // context. Seed complete rows here so a restricted update (for
            // example a status toggle) never creates associations containing
            // only database defaults.
            $restrictedFields = $this->update_fields;
            $this->update_fields = null;
            $shopFields = $this->getFieldsShop();
            $languageRows = !empty($this->def['multilang']) ? (array) $this->getFieldsLang() : [];
            $this->update_fields = $restrictedFields;
            $shopFields[$this->def['primary']] = (int) $this->id;
            foreach ($shopIds as $idShop) {
                $shopFields['id_shop'] = (int) $idShop;
                if (!$connection->insert(
                    $this->def['table'].'_shop',
                    $shopFields,
                    $nullValues,
                    true,
                    Db::INSERT_IGNORE
                )) {
                    throw new PrestaShopException('Unable to associate '.get_class($this).' with shop '.(int) $idShop);
                }

                if (!empty($this->def['multilang_shop'])) {
                    foreach ($languageRows as $languageFields) {
                        $languageFields[$this->def['primary']] = (int) $this->id;
                        $languageFields['id_shop'] = (int) $idShop;
                        if (!$connection->insert(
                            $this->def['table'].'_lang',
                            $languageFields,
                            $nullValues,
                            true,
                            Db::INSERT_IGNORE
                        )) {
                            throw new PrestaShopException('Unable to associate translations for '.get_class($this));
                        }
                    }
                }
            }

            if (!parent::update($nullValues)) {
                throw new PrestaShopException('Unable to update '.get_class($this));
            }

            $connection->execute('COMMIT');

            return true;
        } catch (\Throwable $e) {
            if (isset($restrictedFields)) {
                $this->update_fields = $restrictedFields;
            }
            $connection->execute('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Removes the object only from the selected context. The global row is
     * removed by ObjectModel after its final shop association disappears.
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $shopIds = $this->getWriteShopIds();
        if (!$shopIds) {
            throw new PrestaShopException('No authorized shop was selected');
        }

        $this->id_shop_list = $shopIds;
        Hook::triggerEvent('actionObjectDeleteBefore', ['object' => $this]);
        Hook::triggerEvent('actionObject'.get_class($this).'DeleteBefore', ['object' => $this]);
        $this->clearCache();

        $connection = Db::getInstance();
        $connection->execute('START TRANSACTION');

        try {
            if (!empty($this->def['multilang_shop'])) {
                $connection->delete(
                    $this->def['table'].'_lang',
                    '`'.bqSQL($this->def['primary']).'` = '.(int) $this->id.
                    ' AND `id_shop` IN ('.implode(', ', array_map('intval', $shopIds)).')'
                );
            }

            $this->deleteShopDependencies($shopIds);
            if (!$connection->delete(
                $this->def['table'].'_shop',
                '`'.bqSQL($this->def['primary']).'` = '.(int) $this->id.
                ' AND `id_shop` IN ('.implode(', ', array_map('intval', $shopIds)).')'
            )) {
                throw new PrestaShopException('Unable to remove shop association for '.get_class($this));
            }

            $remainingAssociations = (int) $connection->getValue(
                'SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL($this->def['table']).'_shop`'.
                ' WHERE `'.bqSQL($this->def['primary']).'` = '.(int) $this->id
            );
            if (!$remainingAssociations) {
                if (!empty($this->def['multilang']) && !$connection->delete(
                    $this->def['table'].'_lang',
                    '`'.bqSQL($this->def['primary']).'` = '.(int) $this->id
                )) {
                    throw new PrestaShopException('Unable to delete translations for '.get_class($this));
                }
                if (!$connection->delete(
                    $this->def['table'],
                    '`'.bqSQL($this->def['primary']).'` = '.(int) $this->id
                )) {
                    throw new PrestaShopException('Unable to delete '.get_class($this));
                }
            }

            Hook::triggerEvent('actionObjectDeleteAfter', ['object' => $this]);
            Hook::triggerEvent('actionObject'.get_class($this).'DeleteAfter', ['object' => $this]);
            $connection->execute('COMMIT');

            return true;
        } catch (\Throwable $e) {
            $connection->execute('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @return int[]
     * @throws PrestaShopException
     */
    protected function getWriteShopIds()
    {
        $shopIds = is_array($this->id_shop_list) && $this->id_shop_list
            ? $this->id_shop_list
            : Shop::getContextListShopID();

        return BeesBlogMultistore::filterAuthorizedShopIds($shopIds);
    }

    /**
     * @param int[] $shopIds
     * @return void
     */
    protected function deleteShopDependencies(array $shopIds)
    {
    }
}
