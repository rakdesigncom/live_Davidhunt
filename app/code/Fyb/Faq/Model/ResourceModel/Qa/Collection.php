<?php

namespace Fyb\Faq\Model\ResourceModel\Qa;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('Fyb\Faq\Model\Qa', 'Fyb\Faq\Model\ResourceModel\Qa');
    }
}
