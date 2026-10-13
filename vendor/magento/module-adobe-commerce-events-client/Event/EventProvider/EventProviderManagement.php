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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider;

use Exception;
use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterfaceFactory;
use Magento\AdobeCommerceEventsClient\Api\EventProviderManagementInterface;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\EventProvider as ResourceModel;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\EventProvider\CollectionFactory;
use Magento\AdobeIoEventsClient\Model\WorkspaceConfigurationJsonCleanerInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @inheritDoc
 */
class EventProviderManagement implements EventProviderManagementInterface
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventProviderInterfaceFactory $eventProviderFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     * @param EncryptorInterface $encryptor
     * @param ValidatorInterface $saveValidator
     * @param ValidatorInterface $deleteValidator
     * @param WorkspaceConfigurationJsonCleanerInterface $jsonCleaner
     */
    public function __construct(
        private readonly AdobeIOConfigurationProvider $configurationProvider,
        private readonly EventProviderInterfaceFactory $eventProviderFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceModel $resourceModel,
        private readonly EncryptorInterface $encryptor,
        private readonly ValidatorInterface $saveValidator,
        private readonly ValidatorInterface $deleteValidator,
        private readonly WorkspaceConfigurationJsonCleanerInterface $jsonCleaner,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getList(): array
    {
        $eventProviders = [];
        $collection = $this->collectionFactory->create();
        $collection->setPageSize(200);

        /** @var EventProviderInterface $eventProvider */
        foreach ($collection->getItems() as $eventProvider) {
            $eventProviders[$eventProvider->getProviderId()] = $eventProvider;
        }

        if (!$this->configurationProvider->isConfigured()) {
            return $eventProviders;
        }

        try {
            $defaultProvider = $this->eventProviderFactory->create(['data' => [
                EventProviderInterface::PROVIDER_ID => $this->configurationProvider->getProvider()->getId(),
                EventProviderInterface::INSTANCE_ID => $this->configurationProvider->retrieveInstanceId(),
                EventProviderInterface::WORKSPACE_CONFIGURATION =>
                    $this->configurationProvider->getWorkspaceConfiguration(),
            ]]);

            return array_merge([$defaultProvider->getProviderId() => $defaultProvider], $eventProviders);
        } catch (Exception $e) {
            return $eventProviders;
        }
    }

    /**
     * @inheritDoc
     */
    public function getById(int $entityId): EventProviderInterface
    {
        $eventProvider = $this->eventProviderFactory->create();
        $this->resourceModel->load($eventProvider, $entityId);

        return $eventProvider;
    }

    /**
     * @inheritDoc
     */
    public function getByProviderId(string $providerId): EventProviderInterface
    {
        $allProviders = $this->getList();

        if (isset($allProviders[$providerId])) {
            return $allProviders[$providerId];
        }

        throw new NoSuchEntityException(__('The event provider with ID "%1" not found.', $providerId));
    }

    /**
     * @inheritDoc
     */
    public function save(EventProviderInterface $eventProvider): EventProviderInterface
    {
        $allProviders = $this->getList();
        $this->saveValidator->validate($eventProvider, $allProviders);

        if (empty($eventProvider->getInstanceId())) {
            throw new LocalizedException(__('The event provider instance ID is required and can not be empty.'));
        }

        if (preg_match('/^\*+$/', $eventProvider->getWorkspaceConfiguration())) {
            $eventProvider->setWorkspaceConfiguration(
                $allProviders[$eventProvider->getProviderId()]?->getWorkspaceConfiguration()
            );
        } elseif (!empty($eventProvider->getWorkspaceConfiguration())) {
            $cleaned = $this->jsonCleaner->clean($eventProvider->getWorkspaceConfiguration());
            $eventProvider->setWorkspaceConfiguration(
                $this->encryptor->encrypt($cleaned)
            );
        }

        $this->resourceModel->save($eventProvider);

        return $eventProvider;
    }

    /**
     * @inheritDoc
     */
    public function deleteByProviderId(string $providerId): bool
    {
        $allProviders = $this->getList();
        if (!isset($allProviders[$providerId])) {
            throw new NoSuchEntityException(__('The event provider with ID "%1" not found.', $providerId));
        }

        return $this->delete($allProviders[$providerId]);
    }

    /**
     * @inheritDoc
     */
    public function delete(EventProviderInterface $eventProvider): bool
    {
        try {
            $this->deleteValidator->validate($eventProvider);
            $this->resourceModel->delete($eventProvider);

            return true;
        } catch (Exception $e) {
            throw new CouldNotDeleteException(
                __(
                    'The event provider "%1" cannot be removed. %2',
                    $eventProvider->getProviderId(),
                    $e->getMessage()
                ),
                $e
            );
        }
    }
}
