<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Payments extends AbstractDb
{

    /**
     * Construct 
     */
    protected function _construct()
    {
        $this->_init('autify_lloydscardnetpayment_payments', 'payments_id');
    }
}
