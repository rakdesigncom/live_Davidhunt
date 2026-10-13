<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Model;

use AutifyDigital\PayNow\Api\Data\PaynowInterface;
use AutifyDigital\PayNow\Api\Data\PaynowInterfaceFactory;
use AutifyDigital\PayNow\Api\Data\PaynowSearchResultsInterfaceFactory;
use AutifyDigital\PayNow\Api\PaynowRepositoryInterface;
use AutifyDigital\PayNow\Model\ResourceModel\Paynow as ResourcePaynow;
use AutifyDigital\PayNow\Model\ResourceModel\Paynow\CollectionFactory as PaynowCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class PaynowRepository implements PaynowRepositoryInterface
{

    /**
     * @var PaynowInterfaceFactory
     */
    protected $paynowFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var Paynow
     */
    protected $searchResultsFactory;

    /**
     * @var PaynowCollectionFactory
     */
    protected $paynowCollectionFactory;

    /**
     * @var ResourcePaynow
     */
    protected $resource;


    /**
     * @param ResourcePaynow $resource
     * @param PaynowInterfaceFactory $paynowFactory
     * @param PaynowCollectionFactory $paynowCollectionFactory
     * @param PaynowSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourcePaynow $resource,
        PaynowInterfaceFactory $paynowFactory,
        PaynowCollectionFactory $paynowCollectionFactory,
        PaynowSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->paynowFactory = $paynowFactory;
        $this->paynowCollectionFactory = $paynowCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param PaynowInterface $paynow
     * @return PaynowInterface
     * @throws CouldNotSaveException
     */
    public function save(PaynowInterface $paynow)
    {
        try {
            $this->resource->save($paynow);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the paynow: %1',
                $exception->getMessage()
            ));
        }
        return $paynow;
    }

    /**
     * @param $paynowId
     * @return PaynowInterface
     * @throws NoSuchEntityException
     */
    public function get($paynowId)
    {
        $paynow = $this->paynowFactory->create();
        $this->resource->load($paynow, $paynowId);
        if (!$paynow->getId()) {
            throw new NoSuchEntityException(__('Paynow with id "%1" does not exist.', $paynowId));
        }
        return $paynow;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \AutifyDigital\PayNow\Api\Data\PaynowSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->paynowCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param PaynowInterface $paynow
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PaynowInterface $paynow)
    {
        try {
            $paynowModel = $this->paynowFactory->create();
            $this->resource->load($paynowModel, $paynow->getPaynowId());
            $this->resource->delete($paynowModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Paynow: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @param $paynowId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($paynowId)
    {
        return $this->delete($this->get($paynowId));
    }
}

