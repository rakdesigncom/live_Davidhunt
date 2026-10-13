<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\CatalogSearch\Model\ResourceModel\Fulltext\Collection;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;

/**
 * Temporary fix for
 * @link https://github.com/magento/magento2/issues/28919
 * TODO: remove after left support magento version with bug
 *
 * @since 1.0.0
 */
class CurrentLoading
{
    /**
     * @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection|null
     */
    private $collection;

    /**
     * @var array
     */
    private $priceData = [];

    /**
     * @return \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection|null
     */
    public function get()
    {
        return $this->collection;
    }

    /**
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection
     * @return $this
     */
    public function set(Collection $collection): CurrentLoading
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): CurrentLoading
    {
        $this->collection = null;
        $this->priceData = [];
        return $this;
    }

    /**
     * @return array
     */
    public function getPriceData(): array
    {
        return $this->priceData;
    }

    /**
     * @param array $priceData
     * @return $this
     */
    public function setPriceData(array $priceData): CurrentLoading
    {
        $this->priceData = $priceData;
        return $this;
    }
}
