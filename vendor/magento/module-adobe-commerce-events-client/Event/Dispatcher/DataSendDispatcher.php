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

namespace Magento\AdobeCommerceEventsClient\Event\Dispatcher;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @inheritDoc
 */
class DataSendDispatcher implements DataSendDispatcherInterface
{
    private const EVENT_NAME = 'data_sent_outside';

    /**
     * @param ManagerInterface $eventManager
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param string $senderName
     */
    public function __construct(
        private ManagerInterface $eventManager,
        private LoggerInterface $logger,
        private DateTime $dateTime,
        private string $senderName
    ) {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(array $payload, string $type = 'anything'): void
    {
        try {
            $this->eventManager->dispatch(
                self::EVENT_NAME,
                [
                    'sender' => $this->senderName,
                    'destination' => 'Commerce Eventing Service',
                    'timestamp' => $this->dateTime->timestamp(),
                    'type' => $type,
                    'data' => $payload
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error('Error while dispatching data sent to the eventing service: ' . $e->getMessage());
        }
    }
}
