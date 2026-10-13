<?php

namespace Fyb\Faq\Model\ResourceModel;

class Qa extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('fyb_faq_qa', 'entity_id');
    }
}
