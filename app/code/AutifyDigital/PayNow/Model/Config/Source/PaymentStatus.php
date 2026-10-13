<?php

namespace AutifyDigital\PayNow\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class GetApplicationStatus
 */
class PaymentStatus implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => 'Please Select', 'value' => ''];
        $options[] = ['label' => 'Pending', 'value' => '1'];
        $options[] = ['label' => 'Success', 'value' => '2'];
        $options[] = ['label' => 'Cancelled', 'value' => '3'];
        $options[] = ['label' => 'Failed', 'value' => '4'];
        
        return $options;
    }
}

