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

use BeesBlog;
use Context;
use Link;
use PrestaShopException;
use Validate;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Link decorator used on blog pages so generic language switchers can link to
 * the same shop-scoped entity with its target-language rewrite.
 */
class BeesBlogLanguageLink extends Link
{
    const ENTITY_POST = 'post';
    const ENTITY_CATEGORY = 'category';

    /** @var string */
    protected $entityType;

    /** @var int */
    protected $entityId;

    /** @var int */
    protected $shopId;

    /**
     * @param Link $sourceLink
     * @param string $entityType
     * @param int $entityId
     * @param int $shopId
     *
     * @throws PrestaShopException
     */
    public function __construct(Link $sourceLink, $entityType, $entityId, $shopId)
    {
        parent::__construct($sourceLink->protocol_link, $sourceLink->protocol_content);

        $this->entityType = (string) $entityType;
        $this->entityId = (int) $entityId;
        $this->shopId = (int) $shopId;
    }

    /**
     * Replace the request's Link instance and the matching Smarty variable.
     * This keeps existing language-switcher templates working without a theme
     * override while leaving Link behavior unchanged outside blog pages.
     *
     * @param string $entityType
     * @param int $entityId
     * @param int $shopId
     *
     * @return static
     * @throws PrestaShopException
     */
    public static function install($entityType, $entityId, $shopId)
    {
        $context = Context::getContext();
        $link = new static($context->link, $entityType, $entityId, $shopId);
        $context->link = $link;
        $context->smarty->assign('link', $link);

        return $link;
    }

    /**
     * Create a direct language-switch URL for the same blog entity.
     *
     * @param int $idLang
     * @param Context|null $context
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getLanguageLink($idLang, Context $context = null)
    {
        $idLang = (int) $idLang;
        if ($idLang > 0 && $this->shopId > 0) {
            if ($this->entityType === static::ENTITY_POST && $this->entityId > 0) {
                $post = new BeesBlogPost($this->entityId, $idLang, $this->shopId);
                if (Validate::isLoadedObject($post) && $post->active && $post->lang_active && $post->link) {
                    return $post->link;
                }
            } elseif ($this->entityType === static::ENTITY_CATEGORY && $this->entityId > 0) {
                $category = new BeesBlogCategory($this->entityId, $idLang, $this->shopId);
                if (Validate::isLoadedObject($category) && $category->active && $category->link) {
                    return $category->link;
                }
            }

            return BeesBlog::getBeesBlogLink('beesblog', [], $this->shopId, $idLang);
        }

        return parent::getLanguageLink($idLang, $context);
    }
}
