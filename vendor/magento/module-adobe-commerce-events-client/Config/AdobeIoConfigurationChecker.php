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

use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Config\Source\AuthorizationType;
use Magento\Framework\Exception\NotFoundException;

/**
 * Checks if Adobe I/O configuration is complete
 */
class AdobeIoConfigurationChecker
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(private AdobeIOConfigurationProvider $configurationProvider)
    {
    }

    /**
     * Checks if Adobe I/O workspace configuration field is configured.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        $authType = $this->configurationProvider->getScopeConfig(
            AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE
        );

        try {
            if ($authType === AuthorizationType::JWT) {
                $this->configurationProvider->getPrivateKey();
            }
            $this->configurationProvider->getConfiguration();
        } catch (NotFoundException $e) {
            return false;
        } catch (InvalidConfigurationException $e) {
            // Workspace configuration has been set but is not valid.
            return true;
        }

        return true;
    }
}
