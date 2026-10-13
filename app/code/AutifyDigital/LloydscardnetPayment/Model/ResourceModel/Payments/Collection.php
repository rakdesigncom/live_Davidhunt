<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var $_idFieldName
     */
    protected $_idFieldName = 'payments_id';

    /**
     * Construct 
     */
    protected function _construct()
    {
        $this->_init(
            \AutifyDigital\LloydscardnetPayment\Model\Payments::class,
            \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments::class
        );
    }
}
