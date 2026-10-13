<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Model\Invitation\Message;

/**
 * Validator class for invitation message
 */
class Validator
{
    /**
     * Message pattern
     */
    private const PATTERN = '(https:/|http:/|ftp:/|www\.)i';

    /**
     * Error message
     */
    public const ERROR_MESSAGE = "Invalid message";

    /**
     * Validate invitation message
     *
     * @param string $message
     * @return bool
     */
    public function isValid(string $message): bool
    {
        $matches = [];
        preg_match_all(self::PATTERN, $message, $matches);
        return (bool)$matches[0];
    }
}
