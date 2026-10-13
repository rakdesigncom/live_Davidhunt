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

use Magento\AdobeCommerceEventsClient\Api\EventProviderManagementInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Action for deleting event providers
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_AdobeIoEventsClient::event_provider_delete';

    /**
     * @param Context $context
     * @param EventProviderManagementInterface $eventProviderManagement
     */
    public function __construct(
        Context $context,
        private readonly EventProviderManagementInterface $eventProviderManagement
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
            $eventProvider = $this->eventProviderManagement->getById((int)$this->getRequest()->getParam('id'));

            $this->eventProviderManagement->delete($eventProvider);
            $this->messageManager->addSuccessMessage(
                __('The event provider "%1" has been deleted.', $eventProvider->getProviderId())
            );
        } catch (NoSuchEntityException|CouldNotDeleteException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('adminhtml/eventprovider/index');
    }
}
