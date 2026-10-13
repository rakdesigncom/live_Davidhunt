<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Model\ResourceModel\Paynow;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'paynow_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \AutifyDigital\PayNow\Model\Paynow::class,
            \AutifyDigital\PayNow\Model\ResourceModel\Paynow::class
        );
    }
}

