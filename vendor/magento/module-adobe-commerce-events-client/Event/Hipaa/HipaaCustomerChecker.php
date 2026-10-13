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

namespace Magento\AdobeCommerceEventsClient\Event\Hipaa;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @inheritDoc
 */
class HipaaCustomerChecker implements HipaaCustomerInterface
{
    private const PATH_TO_HIPAA_FLAG = 'general/instance/hipaa';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Checks if the customer is a HIPAA customer by checking the HIPAA flag in the configuration.
     *
     * @return bool
     */
    public function isHipaaCustomer(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH_TO_HIPAA_FLAG);
    }
}
