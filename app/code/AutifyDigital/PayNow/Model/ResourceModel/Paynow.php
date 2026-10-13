<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Paynow extends AbstractDb
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('autifydigital_paynow_paynow', 'paynow_id');
    }
}

