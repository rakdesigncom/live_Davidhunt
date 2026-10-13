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

namespace Magento\AdobeCommerceEventsClient\Api\Data;

use Magento\AdobeCommerceEventsClient\Event\Config;

/**
 * Eventing configuration
 */
interface ConfigurationInterface
{
    public const FIELD_ENABLED = Config::CONFIG_PATH_ENABLED;
    public const FIELD_MERCHANT_ID = Config::CONFIG_PATH_MERCHANT_ID;
    public const FIELD_ENVIRONMENT_ID = Config::CONFIG_PATH_ENVIRONMENT_ID;
    public const FIELD_INSTANCE_ID = Config::CONFIG_PATH_INSTANCE_ID;
    public const FIELD_PROVIDER_ID = Config::CONFIG_PATH_PROVIDER_ID;
    public const FIELD_WORKSPACE_CONFIGURATION = Config::CONFIG_PATH_WORKSPACE_CONFIGURATION;

    /**
     * Returns if is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Sets enabled value
     *
     * @param bool $enabled
     * @return ConfigurationInterface
     */
    public function setEnabled(bool $enabled): ConfigurationInterface;

    /**
     * Returns merchant id
     *
     * @return string
     */
    public function getMerchantId(): string;

    /**
     * Sets merchant id
     *
     * @param string $merchantId
     * @return ConfigurationInterface
     */
    public function setMerchantId(string $merchantId): ConfigurationInterface;

    /**
     * Returns environment id
     *
     * @return string
     */
    public function getEnvironmentId(): string;

    /**
     * Sets environment id
     *
     * @param string $environmentId
     * @return ConfigurationInterface
     */
    public function setEnvironmentId(string $environmentId): ConfigurationInterface;

    /**
     * Returns provider id
     *
     * @return string
     */
    public function getProviderId(): string;

    /**
     * Sets provider id
     *
     * @param string $providerId
     * @return ConfigurationInterface
     */
    public function setProviderId(string $providerId): ConfigurationInterface;

    /**
     * Returns instance id
     *
     * @return string
     */
    public function getInstanceId(): string;

    /**
     * Sets instance id
     *
     * @param string $instanceId
     * @return ConfigurationInterface
     */
    public function setInstanceId(string $instanceId): ConfigurationInterface;

    /**
     * Returns workspace configuration
     *
     * @return string
     */
    public function getWorkspaceConfiguration(): string;

    /**
     * Sets workspace configuration
     *
     * @param string $workspaceConfiguration
     * @return ConfigurationInterface
     */
    public function setWorkspaceConfiguration(string $workspaceConfiguration): ConfigurationInterface;
}
