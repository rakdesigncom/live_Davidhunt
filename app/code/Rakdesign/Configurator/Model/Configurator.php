<?php

namespace Rakdesign\Configurator\Model;

class Configurator extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Rakdesign\Configurator\Model\ResourceModel\Configurator');
    }

}
