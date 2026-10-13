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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request;

use Magento\Framework\DataObject\IdentityGeneratorInterface;

/**
 * @inheritDoc
 */
class RequestId implements RequestIdInterface
{
    /**
     * @var string|null
     */
    private ?string $requestId = null;

    /**
     * @param IdentityGeneratorInterface $generator
     */
    public function __construct(private readonly IdentityGeneratorInterface $generator)
    {
    }

    /**
     * @inheritDoc
     */
    public function get(): string
    {
        if ($this->requestId === null) {
            $this->requestId = $this->generator->generateId();
        }

        return $this->requestId;
    }
}
