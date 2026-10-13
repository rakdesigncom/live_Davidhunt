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

namespace Magento\AdobeCommerceEventsClient\Controller\Adminhtml\EventProvider;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterfaceFactory;
use Magento\AdobeCommerceEventsClient\Api\EventProviderManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Action for saving event providers
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = Edit::ADMIN_RESOURCE;

    /**
     * @param Context $context
     * @param EventProviderManagementInterface $eventProviderManagement
     * @param EventProviderInterfaceFactory $eventProviderFactory
     */
    public function __construct(
        Context $context,
        private readonly EventProviderManagementInterface $eventProviderManagement,
        private readonly EventProviderInterfaceFactory $eventProviderFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $eventProvider = $this->eventProviderFactory->create();
            $eventProvider->addData($this->getRequest()->getParam('general'));
            $this->eventProviderManagement->save($eventProvider);

            $this->messageManager->addSuccessMessage(__('The event provider has been updated.'));

            return $resultRedirect->setPath(
                'adminhtml/eventprovider/edit',
                [
                    'id' => $eventProvider->getId()
                ]
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                __('The event provider can not be saved. ' . $e->getMessage())
            );
            return $resultRedirect->setRefererUrl();
        }
    }
}
