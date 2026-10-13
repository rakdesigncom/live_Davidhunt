<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model;

use Plumrocket\LayeredNavigationLite\Api\Data\FilterMetaInterface;

/**
 * @since 1.0.0
 */
class FilterMeta implements FilterMetaInterface
{

    /**
     * @var string
     */
    private $requestVar;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $requestVar
     * @param string $type
     */
    public function __construct(string $requestVar, string $type)
    {
        $this->requestVar = $requestVar;
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getRequestVariable(): string
    {
        return $this->requestVar;
    }

    /**
     * @inheritDoc
     */
    public function isAttribute(): bool
    {
        return $this->type === self::TYPE_ATTRIBUTE;
    }

    /**
     * @inheritDoc
     */
    public function isCustomOption(): bool
    {
        return $this->type === self::TYPE_CUSTOM_OPTION;
    }

    /**
     * @inheritDoc
     */
    public function isSpecial(): bool
    {
        return $this->type === self::TYPE_SPECIAL;
    }

    /**
     * @inheritDoc
     */
    public function isCategory(): bool
    {
        return $this->type === self::TYPE_CATEGORY;
    }

    /**
     * @inheritDoc
     */
    public function isToolbarVariable(): bool
    {
        return $this->type === self::TYPE_TOOLBAR_VAR;
    }
}
