<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\OptionSource;

use Plumrocket\Base\Model\OptionSource\AbstractSource;

/**
 * @since 1.3.0
 */
class InsertFiltersIn extends AbstractSource
{
    public const URL_PATH = 0;
    public const GET_PARAMS = 1;

    /**
     * Get filter mode.
     *
     * @return array
     */
    public function toOptionHash(): array
    {
        return [
            self::URL_PATH   => __('URL path'),
            self::GET_PARAMS => __('GET parameters'),
        ];
    }
}
