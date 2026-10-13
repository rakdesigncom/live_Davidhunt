<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Utils;

/**
 * @since 2.9.3
 */
class IsMatchFilePath
{

    /**
     * Check if file path match given pattern.
     *
     * @param string $filePath
     * @param string $pattern
     * @return bool
     */
    public function execute(string $filePath, string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }
        return 1 === preg_match($this->patternToRegex($pattern), $filePath);
    }

    /**
     * Check if file path match at least one of patterns.
     *
     * @param string $filePath
     * @param array  $patterns
     * @return bool
     */
    public function executeList(string $filePath, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->execute($filePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert pattern to regex.
     *
     * @param string $pattern
     * @return string
     */
    public function patternToRegex(string $pattern): string
    {
        if (0 === strpos($pattern, 'regex::')) {
            return str_replace('regex::', '', $pattern);
        }

        $mapping = [
            '\\\\\\+' => '[+]',
            '\\\\\\*' => '[*]',
            '/' => '\/',
            '\*' => '(?:.*)',
            '\+' => '(?:.+)',
        ];

        $regex = str_replace(
            array_keys($mapping),
            array_values($mapping),
            preg_quote($pattern, '~')
        );

        return '~^' . $regex . '$~';
    }
}
