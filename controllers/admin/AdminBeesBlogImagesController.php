<?php
/**
 * Copyright (C) 2017-2026 thirty bees
 *
 * @license Academic Free License (AFL 3.0)
 */

use BeesBlogModule\BeesBlogImage;
use BeesBlogModule\BeesBlogMultistore;
use BeesBlogModule\BeesBlogResponsiveImage;
use BeesBlogModule\BeesBlogResponsiveImageJob;

if (!defined('_TB_VERSION_')) {
    exit;
}

// Module admin controllers can be reached from a cached tab before the module
// has been instantiated. Make their model dependencies deterministic.
require_once dirname(__DIR__, 2).'/classes/autoload.php';

/**
 * Responsive blog image settings, migration status, and resumable generation.
 */
class AdminBeesBlogImagesController extends ModuleAdminController
{
    /** @throws PrestaShopException */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = BeesBlogResponsiveImage::TABLE;
        $this->className = '';
        $this->context = Context::getContext();
        $this->multishop_context = Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP;
        BeesBlogMultistore::registerAssociations();

        if (!BeesBlogResponsiveImageJob::ensureDatabase()) {
            throw new PrestaShopException('Unable to initialize the responsive blog image queue');
        }

        $this->fields_options = [
            'responsive' => [
                'title' => $this->l('Responsive images'),
                'icon' => 'icon-picture',
                'description' => $this->l(
                    'The module generates proportional candidates in the image format configured by thirty bees, plus one legacy-browser fallback. Images are never enlarged.'
                ),
                'fields' => [
                    BeesBlogResponsiveImage::CONFIG_WIDTHS => [
                        'title' => $this->l('Candidate widths'),
                        'type' => 'text',
                        'required' => true,
                        'validation' => 'isString',
                        'cast' => 'strval',
                        'desc' => $this->l(
                            'Comma-separated pixels. Defaults: 320, 480, 640, 768, 1024, 1280, 1536, 1920. Saving marks existing sets with different settings as missing; use the progress panel to rebuild them.'
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save responsive image settings'),
                ],
            ],
        ];

        parent::__construct();
    }

    /** @return string */
    public function renderList()
    {
        $statuses = BeesBlogResponsiveImageJob::getStatuses(BeesBlogMultistore::getContextShopIds(), true);
        $statuses[BeesBlogImage::ENTITY_POST]['display_name'] = $this->l('Posts');
        $statuses[BeesBlogImage::ENTITY_CATEGORY]['display_name'] = $this->l('Categories');

        $this->context->smarty->assign([
            'beesblogResponsiveStatuses' => $statuses,
            'beesblogResponsiveAjaxUrl' => static::$currentIndex.'&token='.$this->token,
        ]);

        return $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/bees_blog_images/responsive.tpl'
        );
    }

    /** @return void */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new'], $this->toolbar_btn['export']);
    }

    /** @return void */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['new'], $this->page_header_toolbar_btn['new_image_type']);
    }

    /** @return void */
    public function beforeUpdateOptions()
    {
        if (!Tools::isSubmit(BeesBlogResponsiveImage::CONFIG_WIDTHS)) {
            return;
        }
        $error = null;
        $normalized = BeesBlogResponsiveImage::normalizeWidths(
            Tools::getValue(BeesBlogResponsiveImage::CONFIG_WIDTHS),
            $error
        );
        if ($normalized === false) {
            $this->errors[] = Tools::displayError($error);
            return;
        }
        $_POST[BeesBlogResponsiveImage::CONFIG_WIDTHS] = $normalized;
    }

    /** @return void */
    public function ajaxProcessResponsiveImageStatus()
    {
        $this->sendJson([
            'hasError' => false,
            'statuses' => $this->getResponsiveStatuses(true),
        ]);
    }

    /** @return void */
    public function ajaxProcessResetResponsiveImageStatus()
    {
        if (!$this->hasResponsiveImageEditAccess()) {
            $this->sendJsonError($this->l('You do not have permission to reset image generation status.'));
        }

        try {
            if (!BeesBlogResponsiveImageJob::resetForShops(BeesBlogMultistore::getContextShopIds())) {
                $this->sendJsonError($this->l('Unable to reset responsive image generation status.'));
            }
            $this->sendJson([
                'hasError' => false,
                'statuses' => $this->getResponsiveStatuses(false, false),
            ]);
        } catch (Throwable $e) {
            $this->sendJsonError($e->getMessage());
        }
    }

    /** @return void */
    public function ajaxProcessPrepareResponsiveImages()
    {
        if (!$this->hasResponsiveImageEditAccess()) {
            $this->sendJsonError($this->l('You do not have permission to regenerate images.'));
        }

        $request = $this->getJsonRequest();
        $entityType = $this->getRequestedEntityType($request);
        $mode = isset($request['mode']) ? (string) $request['mode'] : 'missing';
        if (!$entityType || !in_array($mode, ['missing', 'all'], true)) {
            $this->sendJsonError($this->l('Invalid responsive image generation request.'));
        }

        try {
            if (!BeesBlogResponsiveImageJob::prepare(
                $entityType,
                BeesBlogMultistore::getContextShopIds(),
                $mode === 'all'
            )) {
                $this->sendJsonError($this->l('Unable to prepare responsive image generation.'));
            }
            $this->sendJson([
                'hasError' => false,
                'statuses' => $this->getResponsiveStatuses(false, false),
            ]);
        } catch (Throwable $e) {
            $this->sendJsonError($e->getMessage());
        }
    }

    /** @return void */
    public function ajaxProcessGenerateResponsiveImage()
    {
        if (!$this->hasResponsiveImageEditAccess()) {
            $this->sendJsonError($this->l('You do not have permission to regenerate images.'));
        }

        $entityType = $this->getRequestedEntityType($this->getJsonRequest());
        if (!$entityType) {
            $this->sendJsonError($this->l('Invalid responsive image entity type.'));
        }

        try {
            $result = BeesBlogResponsiveImageJob::processNext(
                $entityType,
                BeesBlogMultistore::getContextShopIds()
            );
            $this->sendJson([
                'hasError' => !empty($result['error']),
                'errors' => empty($result['error']) ? [] : [$result['error']],
                'processed' => !empty($result['processed']),
                'statuses' => $this->getResponsiveStatuses(false, false),
            ]);
        } catch (Throwable $e) {
            $this->sendJsonError($e->getMessage());
        }
    }

    /** @return array */
    protected function getResponsiveStatuses($verifyFiles = false, $synchronize = true)
    {
        $statuses = BeesBlogResponsiveImageJob::getStatuses(
            BeesBlogMultistore::getContextShopIds(),
            (bool) $verifyFiles,
            (bool) $synchronize
        );
        $statuses[BeesBlogImage::ENTITY_POST]['display_name'] = $this->l('Posts');
        $statuses[BeesBlogImage::ENTITY_CATEGORY]['display_name'] = $this->l('Categories');

        return $statuses;
    }

    /** @return bool */
    protected function hasResponsiveImageEditAccess()
    {
        return !empty($this->tabAccess['edit']);
    }

    /** @return array */
    protected function getJsonRequest()
    {
        $request = json_decode((string) file_get_contents('php://input'), true);

        return is_array($request) ? $request : [];
    }

    /** @return string|false */
    protected function getRequestedEntityType(array $request)
    {
        $entityType = isset($request['entity_type']) ? (string) $request['entity_type'] : '';

        return in_array(
            $entityType,
            [BeesBlogImage::ENTITY_POST, BeesBlogImage::ENTITY_CATEGORY],
            true
        ) ? $entityType : false;
    }

    /** @return void */
    protected function sendJsonError($message)
    {
        $this->sendJson([
            'hasError' => true,
            'errors' => [(string) $message],
            'statuses' => $this->getResponsiveStatuses(false, false),
        ]);
    }

    /** @return void */
    protected function sendJson(array $response)
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->ajaxDie(json_encode($response));
    }
}
