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

namespace Magento\AdobeCommerceEventsClient\Api;

/**
 * Event subscription creator interface
 *
 * @api
 */
interface EventSubscriberInterface
{
    /**
     * Subscribes to the event.
     *
     * @param \Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface $event
     * @param bool $force
     * @return void
     * @throws \Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException
     */
    public function subscribe(
        \Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface $event,
        bool $force = false
    ): void;

    /**
     * Unsubscribes from the event with the provided name.
     *
     * @param string $name
     * @return void
     * @throws \Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException
     */
    public function unsubscribe(
        string $name
    ): void;
}
