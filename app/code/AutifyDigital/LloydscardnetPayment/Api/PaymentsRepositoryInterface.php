<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PaymentsRepositoryInterface
{

    /**
     * Save Payments
     *
     * @param \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface $payments
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface $payments
    );

    /**
     * Retrieve Payments
     *
     * @param string $paymentsId
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($paymentsId);

    /**
     * Retrieve Payments matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Payments
     *
     * @param \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface $payments
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface $payments
    );

    /**
     * Delete Payments by ID
     *
     * @param string $paymentsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($paymentsId);
}
