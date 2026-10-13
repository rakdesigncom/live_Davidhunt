<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\Variable;

/**
 * @since 1.0.0
 */
class Registry
{

    /**
     * @var array
     */
    private $variables = [];

    /**
     * Save current variables and their values.
     *
     * @param array $variables
     */
    public function set(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * Get current variables and their values.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->variables;
    }
}
