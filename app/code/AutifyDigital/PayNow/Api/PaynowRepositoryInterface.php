<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PaynowRepositoryInterface
{

    /**
     * Save Paynow
     * @param \AutifyDigital\PayNow\Api\Data\PaynowInterface $paynow
     * @return \AutifyDigital\PayNow\Api\Data\PaynowInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \AutifyDigital\PayNow\Api\Data\PaynowInterface $paynow
    );

    /**
     * Retrieve Paynow
     * @param string $paynowId
     * @return \AutifyDigital\PayNow\Api\Data\PaynowInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($paynowId);

    /**
     * Retrieve Paynow matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AutifyDigital\PayNow\Api\Data\PaynowSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Paynow
     * @param \AutifyDigital\PayNow\Api\Data\PaynowInterface $paynow
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \AutifyDigital\PayNow\Api\Data\PaynowInterface $paynow
    );

    /**
     * Delete Paynow by ID
     * @param string $paynowId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($paynowId);
}

