<?php

namespace Rakdesign\Configurator\Model\ResourceModel;

class Configurator extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('rakdesign_configurator', 'entity_id');
    }

}
