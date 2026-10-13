<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Utils;

/**
 * Check if url is matched by pattern, compare url without "get" params and fragment
 *
 * @since 2.3.1
 */
class IsMatchUrl
{
    /**
     * @var \Plumrocket\Base\Model\Utils\GetRelativePathFromUrl
     */
    private $getRelativePathFromUrl;

    /**
     * @param \Plumrocket\Base\Model\Utils\GetRelativePathFromUrl $getRelativePathFromUrl
     */
    public function __construct(GetRelativePathFromUrl $getRelativePathFromUrl)
    {
        $this->getRelativePathFromUrl = $getRelativePathFromUrl;
    }

    /**
     * Check if url match the pattern.
     *
     * @param string $url
     * @param string $pattern
     * @return bool
     */
    public function execute(string $url, string $pattern): bool
    {
        if ('' === $pattern) {
            return false;
        }

        $relativeUrl = $this->getRelativePathFromUrl->execute($url);
        $regex = $this->patternToRegex($pattern);
        return 1 === preg_match($regex, $relativeUrl);
    }

    /**
     * Check if url match at least one of patterns.
     *
     * @param string $url
     * @param array  $patterns
     * @return bool
     */
    public function executeList(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->execute($url, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert pattern to regex.
     *
     * @param string $pattern
     * @return string|string[]
     */
    private function patternToRegex(string $pattern): string
    {
        if (0 === strpos($pattern, 'regex::')) {
            return str_replace('regex::', '', $pattern);
        }

        if (false !== strpos($pattern, 'http://') || false !== strpos($pattern, 'https://')) {
            $pattern = $this->getRelativePathFromUrl->execute($pattern);
        } elseif ('' === $pattern || ! in_array($pattern[0], ['/', '*', '+'], true)) {
            $pattern = '/' . $pattern;
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
