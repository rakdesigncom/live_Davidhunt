<?php

namespace Fyb\Pimberly\Model;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Fyb\Pimberly\Model\ResourceModel\Queue::class);
    }
}
