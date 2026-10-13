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

namespace Magento\AdobeCommerceEventsClient\Config;

use Magento\AdobeCommerceEventsClient\Api\Data\ConfigurationInterface;
use Magento\Framework\DataObject;

/**
 * Stores eventing configuration
 */
class Configuration extends DataObject implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->getData(self::FIELD_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function setEnabled(bool $enabled): ConfigurationInterface
    {
        $this->setData(self::FIELD_ENABLED, $enabled);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMerchantId(): string
    {
        return $this->getData(self::FIELD_MERCHANT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMerchantId(string $merchantId): ConfigurationInterface
    {
        $this->setData(self::FIELD_MERCHANT_ID, $merchantId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEnvironmentId(): string
    {
        return $this->getData(self::FIELD_ENVIRONMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEnvironmentId(string $environmentId): ConfigurationInterface
    {
        $this->setData(self::FIELD_ENVIRONMENT_ID, $environmentId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProviderId(): string
    {
        return $this->getData(self::FIELD_PROVIDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProviderId(string $providerId): ConfigurationInterface
    {
        $this->setData(self::FIELD_PROVIDER_ID, $providerId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInstanceId(): string
    {
        return $this->getData(self::FIELD_INSTANCE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInstanceId(string $instanceId): ConfigurationInterface
    {
        $this->setData(self::FIELD_INSTANCE_ID, $instanceId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWorkspaceConfiguration(): string
    {
        return $this->getData(self::FIELD_WORKSPACE_CONFIGURATION);
    }

    /**
     * @inheritDoc
     */
    public function setWorkspaceConfiguration(string $workspaceConfiguration): ConfigurationInterface
    {
        $this->setData(self::FIELD_WORKSPACE_CONFIGURATION, $workspaceConfiguration);

        return $this;
    }
}
