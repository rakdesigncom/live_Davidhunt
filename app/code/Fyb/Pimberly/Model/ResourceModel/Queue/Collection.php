<?php

namespace Fyb\Pimberly\Model\ResourceModel\Queue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(
            \Fyb\Pimberly\Model\Queue::class,
            \Fyb\Pimberly\Model\ResourceModel\Queue::class
        );
    }
}
