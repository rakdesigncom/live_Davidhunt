<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model;

/**
 * @since 1.0.0
 */
class AjaxRequestLocator
{

    /**
     * @var bool
     */
    private $active = false;

    /**
     * Check whether current request is filter ajax request.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set that current request is filter ajax request.
     *
     * @param mixed $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }
}
