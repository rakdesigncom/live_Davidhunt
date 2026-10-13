<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Plugin\Elasticsearch;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Helper\SearchEngine;
use Plumrocket\LayeredNavigationLite\Model\CatalogSearch\Model\ResourceModel\Fulltext\Collection\CurrentLoading;

/**
 * Temporary fix for
 * @link https://github.com/magento/magento2/issues/28919
 * TODO: remove after left support magento version with bug
 *
 * @since 1.0.0
 */
class FixMaxAndMinPrices
{
    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\CatalogSearch\Model\ResourceModel\Fulltext\Collection\CurrentLoading
     */
    private $currentLoadingCollection;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\SearchEngine
     */
    private $searchEngine;

    /**
     * @param CurrentLoading                                        $currentLoadingCollection
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config       $config
     * @param \Plumrocket\LayeredNavigationLite\Helper\SearchEngine $searchEngine
     */
    public function __construct(
        CurrentLoading $currentLoadingCollection,
        Config $config,
        SearchEngine $searchEngine
    ) {
        $this->currentLoadingCollection = $currentLoadingCollection;
        $this->config = $config;
        $this->searchEngine = $searchEngine;
    }

    /**
     * Fix min and max prices.
     *
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject
     * @param bool                                                           $printQuery
     * @param bool                                                           $logQuery
     */
    public function beforeLoad(
        Collection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        if (! $this->needFix()) {
            return;
        }

        $this->currentLoadingCollection->set($subject);
    }

    /**
     * Fix min and max prices.
     *
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection        $result
     * @param bool                                                           $printQuery
     * @param bool                                                           $logQuery
     * @return mixed
     */
    public function afterLoad(
        Collection $subject,
        $result,
        $printQuery = false,
        $logQuery = false
    ) {
        if ($priceData = $this->currentLoadingCollection->getPriceData()) {
            $this->setPropertyValue(
                $subject,
                '_maxPrice',
                (double) ($priceData['max'] ?? 0) * $subject->getCurrencyRate()
            );
            $this->setPropertyValue(
                $subject,
                '_minPrice',
                (double) ($priceData['min'] ?? 0) * $subject->getCurrencyRate()
            );
        }

        $this->currentLoadingCollection->reset();
        return $result;
    }

    /**
     * Check if we need to apply fix.
     *
     * @return bool
     */
    public function needFix(): bool
    {
        return $this->config->isModuleEnabled() && $this->searchEngine->isElasticSearch();
    }

    /**
     * Set private property.
     *
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject
     * @param string                                                         $propertyName
     * @param float                                                          $value
     */
    private function setPropertyValue(Collection $subject, string $propertyName, float $value)
    {
        try {
            $reflectionSubject = new \ReflectionObject($subject);
            $reflectionProperty = $reflectionSubject->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($subject, $value);
            $reflectionProperty->setAccessible(false);
        } catch (\ReflectionException $e) {
            return;
        }
    }
}
