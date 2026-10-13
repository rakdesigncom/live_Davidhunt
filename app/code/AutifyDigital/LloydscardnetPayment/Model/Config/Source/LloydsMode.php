<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Mode Type Class
 */
class LloydsMode implements ArrayInterface
{
    /**
     * Return Array of Modes
     *
     * @return option array in configuration
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Test',
                'label' => 'Test',
            ],
            [
                'value' => 'Live',
                'label' => 'Live',
            ],
        ];
    }
}
