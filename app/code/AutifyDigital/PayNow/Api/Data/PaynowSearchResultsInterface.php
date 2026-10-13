<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Api\Data;

interface PaynowSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Paynow list.
     * @return \AutifyDigital\PayNow\Api\Data\PaynowInterface[]
     */
    public function getItems();

    /**
     * Set invoice_number list.
     * @param \AutifyDigital\PayNow\Api\Data\PaynowInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

