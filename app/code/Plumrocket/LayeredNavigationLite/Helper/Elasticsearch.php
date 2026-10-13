<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Search\Model\EngineResolver;

/**
 * @since 1.0.0
 * @deprecated since 1.3.0
 * @see \Plumrocket\LayeredNavigationLite\Helper\SearchEngine
 */
class Elasticsearch extends AbstractHelper
{
    /**
     * @var \Magento\Search\Model\EngineResolver
     */
    private $engineResolver;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Search\Model\EngineResolver $engineResolver
     */
    public function __construct(
        Context $context,
        EngineResolver $engineResolver
    ) {
        parent::__construct($context);
        $this->engineResolver = $engineResolver;
    }

    /**
     * Check if elastic search is enabled.
     *
     * @return bool
     */
    public function isCurrentSearchEngine(): bool
    {
        $searchEngine = $this->engineResolver->getCurrentSearchEngine();

        return false !== \mb_strpos($searchEngine, 'elasticsearch');
    }
}
