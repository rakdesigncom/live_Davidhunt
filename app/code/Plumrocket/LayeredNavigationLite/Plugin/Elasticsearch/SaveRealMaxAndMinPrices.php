<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Plugin\Elasticsearch;

use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Request\BucketInterface;
use Plumrocket\LayeredNavigationLite\Model\CatalogSearch\Model\ResourceModel\Fulltext\Collection\CurrentLoading;

/**
 * Temporary fix for
 * @link https://github.com/magento/magento2/issues/28919
 * TODO: remove after left support magento version with bug
 *
 * @since 1.0.0
 */
class SaveRealMaxAndMinPrices
{
    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\CatalogSearch\Model\ResourceModel\Fulltext\Collection\CurrentLoading
     */
    private $currentLoadingCollection;

    /**
     * @param CurrentLoading $currentLoadingCollection
     */
    public function __construct(CurrentLoading $currentLoadingCollection)
    {
        $this->currentLoadingCollection = $currentLoadingCollection;
    }

    /**
     * @param \Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\Dynamic $subject
     * @param BucketInterface                                                  $bucket
     * @param Dimension[]                                                      $dimensions
     * @param array                                                            $queryResult
     * @param DataProviderInterface                                            $dataProvider
     */
    public function beforeBuild(
        \Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\Dynamic $subject,
        \Magento\Framework\Search\Request\BucketInterface $bucket,
        array $dimensions,
        array $queryResult,
        DataProviderInterface $dataProvider
    ) {
        if (isset($queryResult['aggregations']['price_bucket']) && 'price_bucket' === $bucket->getName()) {
            $priceData = $queryResult['aggregations']['price_bucket'];
            $this->currentLoadingCollection->setPriceData($priceData);
        }
    }
}
