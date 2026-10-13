<?php

namespace Fyb\StockistLocator\Model;

class Localstockists extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Fyb\StockistLocator\Model\ResourceModel\Localstockists::class);
    }
}
