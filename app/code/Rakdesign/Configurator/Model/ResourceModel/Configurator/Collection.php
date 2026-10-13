<?php

namespace Rakdesign\Configurator\Model\ResourceModel\Configurator;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Rakdesign\Configurator\Model\Configurator',
            'Rakdesign\Configurator\Model\ResourceModel\Configurator'
        );
    }

}
