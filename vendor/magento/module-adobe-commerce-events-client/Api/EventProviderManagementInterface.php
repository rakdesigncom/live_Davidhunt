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

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Manages event providers
 *
 * @api
 */
interface EventProviderManagementInterface
{
    /**
     * Returns the list of event providers
     *
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface[]
     */
    public function getList(): array;

    /**
     * Get a single event provider by entity ID
     *
     * @param int $entityId
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
     * @throws NoSuchEntityException when provider can't be found
     */
    public function getById(int $entityId): EventProviderInterface;

    /**
     * Get a single event provider by provider ID
     *
     * @param string $providerId
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
     * @throws NoSuchEntityException when provider can't be found
     */
    public function getByProviderId(string $providerId): EventProviderInterface;

    /**
     * Create or update the event provider
     *
     * @param \Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface $eventProvider
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface
     * @throws LocalizedException when the event provider can't be saved
     */
    public function save(EventProviderInterface $eventProvider): EventProviderInterface;

    /**
     * Delete an event provider by provider ID
     *
     * @param string $providerId
     * @return bool
     * @throws NoSuchEntityException when the event provider can't be found
     * @throws CouldNotDeleteException when the event provider can't be deleted
     */
    public function deleteByProviderId(string $providerId): bool;

    /**
     * Delete an event provider
     *
     * @param EventProviderInterface $eventProvider
     * @return bool
     * @throws NoSuchEntityException when the event provider can't be found
     * @throws CouldNotDeleteException when the event provider can't be deleted
     */
    public function delete(EventProviderInterface $eventProvider): bool;
}
