<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ApplicationStatus
 */
class Status implements OptionSourceInterface
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
        $options[] = ['label' => 'Paid', 'value' => '2'];
        $options[] = ['label' => 'Cancel', 'value' => '3'];
        $options[] = ['label' => 'Error', 'value' => '4'];
        $options[] = ['label' => 'Refunded', 'value' => '5'];
        return $options;
    }
}
