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
 * @since 1.0.0
 */
abstract class AbstractTitlePosition extends AbstractSource
{
    public const POSITION_BEFORE = 'before';
    public const POSITION_AFTER = 'after';
    public const POSITION_NONE = 'none';
}
