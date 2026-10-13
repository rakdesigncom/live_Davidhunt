<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Configuration for eventing
 */
class Config
{
    /**
     * Maximum allowed event data size in bytes (64KB)
     */
    private const DEFAULT_MAX_EVENT_DATA_SIZE = 65536;

    public const CONFIG_PATH_ENABLED = 'adobe_io_events/eventing/enabled';
    public const CONFIG_PATH_MERCHANT_ID = 'adobe_io_events/eventing/merchant_id';
    public const CONFIG_PATH_ENVIRONMENT_ID = 'adobe_io_events/eventing/env_id';
    public const CONFIG_PATH_INSTANCE_ID = 'adobe_io_events/integration/instance_id';
    public const CONFIG_PATH_PROVIDER_ID = 'adobe_io_events/integration/provider_id';
    public const CONFIG_PATH_WORKSPACE_CONFIGURATION = 'adobe_io_events/integration/workspace_configuration';
    public const CONFIG_PATH_MAX_EVENT_DATA_SIZE = 'adobe_io_events/eventing/max_event_data_size';

    private const CONFIG_PATH_ENVIRONMENT = 'adobe_io_events/integration/adobe_io_environment';
    private const CONFIG_PATH_MAX_RETRIES = 'adobe_io_events/eventing/max_retries';

    private const ENVIRONMENT_STAGING = 'staging';
    private const ENVIRONMENT_DEVELOPMENT = 'development';

    private const CONFIG_PATH_ENDPOINT_PROD = 'adobe_io_events/integration/endpoint_production';
    private const CONFIG_PATH_ENDPOINT_STAGE = 'adobe_io_events/integration/endpoint_stage';
    private const CONFIG_PATH_ENDPOINT_DEV = 'adobe_io_events/integration/endpoint_dev';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Checks if eventing is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Returns instance id.
     *
     * @return string
     */
    public function getInstanceId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_INSTANCE_ID);
    }

    /**
     * Returns endpoint url.
     *
     * @return string
     */
    public function getEndpointUrl(): string
    {
        if ($this->config->getValue(self::CONFIG_PATH_ENVIRONMENT) === self::ENVIRONMENT_STAGING) {
            return $this->config->getValue(self::CONFIG_PATH_ENDPOINT_STAGE);
        }

        if ($this->config->getValue(self::CONFIG_PATH_ENVIRONMENT) === self::ENVIRONMENT_DEVELOPMENT) {
            return $this->config->getValue(self::CONFIG_PATH_ENDPOINT_DEV);
        }

        return $this->config->getValue(self::CONFIG_PATH_ENDPOINT_PROD);
    }

    /**
     * Returns Environment id.
     *
     * @return string
     */
    public function getEnvironmentId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_ENVIRONMENT_ID);
    }

    /**
     * Returns Merchant id.
     *
     * @return string
     */
    public function getMerchantId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_MERCHANT_ID);
    }

    /**
     * Returns Max retries
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return (int)$this->config->getValue(self::CONFIG_PATH_MAX_RETRIES);
    }

    /**
     * Get maximum event data size in bytes
     *
     * @return int
     */
    public function getMaxEventDataSize(): int
    {
        return (int)$this->config->getValue(self::CONFIG_PATH_MAX_EVENT_DATA_SIZE) ?: self::DEFAULT_MAX_EVENT_DATA_SIZE;
    }
}
