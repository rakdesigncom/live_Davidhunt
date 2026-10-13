<?php

namespace Fyb\Pimberly\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('fyb_pimberly_queue', 'entity_id');
    }
}
