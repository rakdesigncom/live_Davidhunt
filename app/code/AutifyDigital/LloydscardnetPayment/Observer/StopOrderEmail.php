<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Observer;

/**
 * Stop Order Email Trigger Observer
 */
class StopOrderEmail implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\Config
     */
    protected $config;

    /**
     * @param \AutifyDigital\LloydscardnetPayment\Model\Config $config
     */
    public function __construct(
        \AutifyDigital\LloydscardnetPayment\Model\Config $config
    ) {
        $this->config = $config;
    }
    /**
     * Execute Observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order
         */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        if ($payment == 'lcnetredirect') {
            $this->stopNewOrderEmail($order);
        }
    }

    /**
     * Set Stop Order Email Data
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function stopNewOrderEmail(\Magento\Sales\Model\Order $order)
    {
        if($this->config->getConfig('payment/lcnetredirect/order_status') == 'pending_payment') {
            $order->setStatus('pending_payment');
            $order->setState('pending_payment');
        } else {
            $order->setStatus('pending');
            $order->setState('pending');
        }

        $order->setCanSendNewEmailFlag(false);
        $order->setSendEmail(false);
        $order->save();
    }
}
