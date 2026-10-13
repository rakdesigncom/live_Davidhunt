<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Block\Index;

class Confirmation extends \Magento\Framework\View\Element\Template
{

    protected $coreRegistry;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
    }

    public function getTransactionData()
    {
        return $this->coreRegistry->registry('transaction_data');

    }

    public function getGatewayTransactionData()
    {
        return $this->coreRegistry->registry('gateway_transaction_data');
    }

}

