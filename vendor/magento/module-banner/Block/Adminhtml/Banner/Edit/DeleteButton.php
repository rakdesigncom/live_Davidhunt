<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2017 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
namespace Magento\Banner\Block\Adminhtml\Banner\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Banner\Block\Adminhtml\Banner\Edit;
use Magento\Banner\Model\BannerFactory;
use Magento\Banner\Model\ResourceModel\Banner;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\ObjectManager;

/**
 * Class for "Delete" button on the create/edit banner form
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Escaper for secure output rendering
     *
     * @var Escaper
     */
    private $escaper;

    /**
     * Constructor
     *
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param BannerFactory $bannerFactory
     * @param Banner $bannerResourceModel
     * @param Escaper|null $escaper
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        BannerFactory $bannerFactory,
        Banner $bannerResourceModel,
        ?Escaper $escaper = null
    ) {
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(Escaper::class);
        parent::__construct($urlBuilder, $request, $bannerFactory, $bannerResourceModel);
    }

    /**
     * Retrieve button configuration data for the delete button.
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getBannerId()) {
            $confirmMessage = $this->escaper->escapeJs(
                $this->escaper->escapeHtml(__('Are you sure you want to do this?'))
            );
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . $confirmMessage . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get the URL for the delete action.
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getBannerId()]);
    }
}
