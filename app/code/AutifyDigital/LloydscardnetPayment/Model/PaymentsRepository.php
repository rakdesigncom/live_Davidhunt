<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model;

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments as ResourcePayments;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class PaymentsRepository implements PaymentsRepositoryInterface
{

    /**
     * @var ResourcePayments
     */
    protected $resource;

    /**
     * @var PaymentsInterfaceFactory
     */
    protected $paymentsFactory;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var Payments
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param ResourcePayments $resource
     * @param PaymentsInterfaceFactory $paymentsFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param PaymentsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourcePayments $resource,
        PaymentsInterfaceFactory $paymentsFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        PaymentsSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->paymentsFactory = $paymentsFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Function for save data
     */
    public function save(PaymentsInterface $payments)
    {
        try {
            $this->resource->save($payments);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the payments: %1',
                $exception->getMessage()
            ));
        }
        return $payments;
    }

    /**
     * Function for get data
     */
    public function get($paymentsId)
    {
        $payments = $this->paymentsFactory->create();
        $this->resource->load($payments, $paymentsId);
        if (!$payments->getId()) {
            throw new NoSuchEntityException(__('Payments with id "%1" does not exist.', $paymentsId));
        }
        return $payments;
    }

    /**
     * Function for get list
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->paymentsCollectionFactory->create();
        
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
     * Function for delete data
     */
    public function delete(PaymentsInterface $payments)
    {
        try {
            $paymentsModel = $this->paymentsFactory->create();
            $this->resource->load($paymentsModel, $payments->getPaymentsId());
            $this->resource->delete($paymentsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Payments: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Function for delete data by id
     */
    public function deleteById($paymentsId)
    {
        return $this->delete($this->get($paymentsId));
    }
}
