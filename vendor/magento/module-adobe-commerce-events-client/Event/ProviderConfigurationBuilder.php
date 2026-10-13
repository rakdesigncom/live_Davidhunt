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

namespace Magento\AdobeCommerceEventsClient\Event;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\EventProviderManagement;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\ConfigurationFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Builds an AdobeConsoleConfiguration object based on the configuration of event providers
 */
class ProviderConfigurationBuilder
{
    /**
     * @param EventProviderManagement $eventProviderManagement
     * @param ConfigurationFactory $configurationFactory
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly EventProviderManagement $eventProviderManagement,
        private readonly ConfigurationFactory $configurationFactory,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * Builds an AdobeConsoleConfiguration object for the workspace config of the input provider if one is configured.
     *
     * @param string $providerId
     * @return AdobeConsoleConfiguration|null
     * @throws InvalidConfigurationException
     * @throws NoSuchEntityException
     */
    public function build(string $providerId): ?AdobeConsoleConfiguration
    {
        $provider = $this->eventProviderManagement->getByProviderId($providerId);
        if (empty($provider->getWorkspaceConfiguration())) {
            return null;
        }

        try {
            $workspaceConfig = $this->encryptor->decrypt($provider->getWorkspaceConfiguration());
        } catch (Exception $e) {
            $workspaceConfig = $provider->getWorkspaceConfiguration();
        }

        return $this->configurationFactory->createFromWorkspaceString($workspaceConfig);
    }
}
