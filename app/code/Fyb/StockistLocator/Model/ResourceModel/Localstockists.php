<?php
namespace Fyb\StockistLocator\Model\ResourceModel;

class Localstockists extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('fyb_stockist_locator', 'entity_id');
    }
}

