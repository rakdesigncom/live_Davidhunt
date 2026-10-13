<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Api\Data;

interface PaymentsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Payments list.
     *
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface[]
     */
    public function getItems();

    /**
     * Set amount list.
     *
     * @param \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
