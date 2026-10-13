<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Ui\Component\Listing\EventProvider\Column;

use Magento\AdobeCommerceEventsClient\Controller\Adminhtml\EventProvider\Delete;
use Magento\AdobeCommerceEventsClient\Controller\Adminhtml\EventProvider\Edit;
use Magento\Backend\Ui\Component\Listing\Column\EditAction;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Edit and delete actions for items in the event provider grid.
 */
class EventProviderActions extends EditAction
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private UrlInterface $urlBuilder,
        private AuthorizationInterface $authorization,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data);
    }

    /**
     * Hides actions if appropriate ACL resource is not allowed. Adds delete button to the action list.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);
        $actionsName = $this->getData('name');

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item[$actionsName]['edit'])) {
                    $item[$actionsName]['edit']['hidden'] = !$this->authorization->isAllowed(Edit::ADMIN_RESOURCE);
                }
                $deleteUrlPath = $this->getData('config/deleteUrlPath') ?: '#';
                $item[$actionsName]['delete'] = [
                    'href' => $this->urlBuilder->getUrl(
                        $deleteUrlPath,
                        [
                            'id' => $item['id']
                        ]
                    ),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Event Provider'),
                        'message' => __(
                            'Are you sure you want to delete an event provider "%1"?',
                            $item['provider_id']
                        )
                    ],
                    'hidden' => !$this->authorization->isAllowed(Delete::ADMIN_RESOURCE),
                    'post' => true
                ];
            }
        }

        return $dataSource;
    }
}
