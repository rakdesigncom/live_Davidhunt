<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\Variable\Value;

/**
 * @since 1.0.0
 */
class Slugify
{

    /**
     * Generate slug from string.
     *
     * Create string that can be used is url from random string.
     * To create beautiful slag and support cyrillic/hieroglyphs
     * we replace word separators by underscore and other symbols by encoded values.
     *
     * @param  string|array $value
     * @return string
     */
    public function execute(string $value): string
    {
        $value = \trim($value);
        $value = \strip_tags($value);
        $value = \mb_strtolower($value);
        $value = str_replace(
            ['#', '?', '&', '-', ' ', '/'],
            '_',
            $value
        );
        $value = urlencode($value);
        return \html_entity_decode($value);
    }
}
