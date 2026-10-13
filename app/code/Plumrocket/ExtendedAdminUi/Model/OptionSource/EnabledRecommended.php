<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Model\OptionSource;

use Plumrocket\Base\Model\OptionSource\AbstractSource;

/**
 * @since 1.1.0
 */
class EnabledRecommended extends AbstractSource
{

    /**
     * @inheritDoc
     */
    public function toOptionHash(): array
    {
        return [
            1 => __('Enabled (Recommended)'),
            0 => __('Disabled'),
        ];
    }
}
