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

namespace Magento\AdobeCommerceEventsClient\Config\Validator;

use Magento\AdobeCommerceEventsClient\Config\ValidatorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates IDs that can only contain alphanumeric characters and underscores.
 */
class EventingIdValidator implements ValidatorInterface
{
    /**
     * @param string $idName
     */
    public function __construct(private string $idName)
    {
    }

    /**
     * Validates that an ID contains only alphanumeric characters and underscores.
     *
     * @param mixed $value
     * @return bool
     * @throws ValidatorException
     */
    public function validate(mixed $value): bool
    {
        if (!preg_match('/^\w*$/', (string)$value)) {
            throw new ValidatorException(
                __(sprintf(
                    '%s is invalid. ID can contain only alphanumeric characters and underscores.',
                    $this->idName
                ))
            );
        }
        return true;
    }
}
