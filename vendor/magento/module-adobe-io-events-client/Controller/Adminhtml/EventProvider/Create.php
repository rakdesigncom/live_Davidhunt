<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeIoEventsClient\Controller\Adminhtml\EventProvider;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventProviderClient;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Controller for creating event provider id
 */
class Create extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeIoEventsClient::create_event_provider';

    /**
     * @param Context $context
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventProviderFactory $eventProviderFactory
     * @param EventProviderClient $eventProviderClient
     * @param TypeListInterface $cacheTypeList
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        private AdobeIOConfigurationProvider $configurationProvider,
        private EventProviderFactory $eventProviderFactory,
        private EventProviderClient $eventProviderClient,
        private TypeListInterface $cacheTypeList,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $defaultErrorMessage = 'Failed to create event provider. ';
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $providerData = $this->eventProviderFactory->create([
            'data' => [
                'label' => $this->getRequest()->getParam('label'),
                'description' => $this->getRequest()->getParam('description')
            ]
        ]);

        try {
            $provider = $this->eventProviderFactory->create(['data' => $this->eventProviderClient->createEventProvider(
                $this->configurationProvider->retrieveInstanceId(),
                $providerData
            )]);
            $this->configurationProvider->saveProvider($provider);

            $this->messageManager->addSuccessMessage(__('Event Provider created with ID '. $provider->getId()));
            $this->logger->info(sprintf('Event Provider created with ID %s', $provider->getId()));
            $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
        } catch (NotFoundException|InputException|LocalizedException $exception) {
            $errorMessage = $exception instanceof InputException
                ? __($defaultErrorMessage . 'See logs for details')
                : __($exception->getMessage());

            $this->logger->error(sprintf(
                $defaultErrorMessage . 'Error: %s',
                $exception->getMessage()
            ));
        }

        $result->setData(['error' => $errorMessage ?? null]);
        return $result;
    }
}
