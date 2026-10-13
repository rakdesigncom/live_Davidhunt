<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Redirect Type Mode Class
 */
class RedirectMode implements ArrayInterface
{
    /**
     * Return Array of Types
     *
     * @return option array in configuration
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'payonly',
                'label' => 'PayOnly',
            ],
            [
                'value' => 'payplus',
                'label' => 'PayPlus',
            ],
            [
                'value' => 'fullpay',
                'label' => 'FullPay',
            ],
        ];
    }
}
