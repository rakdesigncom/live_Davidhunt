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

namespace Magento\AdobeCommerceEventsClient\Controller\Adminhtml\Synchronization;

use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;

/**
 * Synchronizes events to Adobe I/O
 */
class SynchronizeEvents extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeIoEventsClient::synchronize_events';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AdobeIoEventMetadataSynchronizer $metadataSynchronizer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private AdobeIoEventMetadataSynchronizer $metadataSynchronizer,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $synchronizeResult = $this->metadataSynchronizer->run();
            if (empty($synchronizeResult->getFailedEvents())) {
                $resultJson->setData([
                    'success' => true
                ]);
            } else {
                $resultJson->setData([
                    'error' => 'Synchronization failed for the following: ' .
                        implode(", ", $synchronizeResult->getFailedEvents()),
                ]);
            }
        } catch (SynchronizerException | EventInitializationException $e) {
            $resultJson->setData([
                'error' => $e->getMessage()
            ]);
            $this->logger->error(
                sprintf('Failed to synchronize events: %s', $e->getMessage()),
                ['destination' => ['internal', 'external']]
            );
        }

        return $resultJson;
    }
}
