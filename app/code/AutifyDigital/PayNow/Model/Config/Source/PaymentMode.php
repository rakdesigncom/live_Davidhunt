<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Model\Config\Source;

class PaymentMode implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [['value' => 'payonly', 'label' => __('payonly')],['value' => 'fullpay', 'label' => __('fullpay')],['value' => 'payplus', 'label' => __('payplus')]];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return ['payonly' => __('payonly'),'fullpay' => __('fullpay'),'payplus' => __('payplus')];
    }
}

