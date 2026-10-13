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

/**
 * Extracts method arguments from a context field source.
 */
class ArgumentExtractor
{
    private const ARGUMENT_DELIMITER = ':';

    /**
     * Extracts method arguments, contained within curly braces and delimited by colons, from a source string part.
     *
     * @param string $sourcePart
     * @return array
     */
    public function extract(string $sourcePart): array
    {
        if (preg_match('/(?<arguments>{.*?})/', $sourcePart, $matches)) {
            $arguments = trim($matches['arguments'], '{}');
            return explode(self::ARGUMENT_DELIMITER, $arguments);
        }
        return [];
    }
}
