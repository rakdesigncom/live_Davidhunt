<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context;

use Magento\Framework\ObjectManagerInterface;

/**
 * Stores reference and class/interface names for contexts that are available for access.
 */
class ContextPool
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $contexts
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private array $contexts = []
    ) {
    }

    /**
     * Checks if the input string refers to a supported context class.
     *
     * @param string $contextReference
     * @return bool
     */
    public function has(string $contextReference): bool
    {
        return isset($this->contexts[$contextReference]);
    }

    /**
     * Retrieves the class name corresponding to the input context reference.
     *
     * @param string $contextReference
     * @return object|null
     */
    public function get(string $contextReference): ?object
    {
        if (!isset($this->contexts[$contextReference])) {
            return null;
        }

        return $this->objectManager->get($this->contexts[$contextReference]);
    }
}
