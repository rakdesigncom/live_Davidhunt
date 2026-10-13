<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * @since 2.9.2
 */
trait HtmlAsserts
{

    /**
     * Compare HTML ignoring space between tags.
     *
     * @param string $expectedHtml
     * @param string $html
     * @param string $case
     * @return void
     */
    public static function assertSameHtml(string $expectedHtml, string $html, string $case = ''): void
    {
        TestCase::assertSame(self::cleanHtml($expectedHtml), self::cleanHtml($html), $case);
    }

    /**
     * @param string $expectedHtml
     * @return array|string|string[]|null
     */
    protected static function cleanHtml(string $expectedHtml)
    {
        $expectedHtml = preg_replace('/>\s*?</', ">\n<", trim($expectedHtml));
        return preg_replace('/\s*?>/', '>', $expectedHtml);
    }
}
