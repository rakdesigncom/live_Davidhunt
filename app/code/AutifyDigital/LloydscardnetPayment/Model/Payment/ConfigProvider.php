<?php
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{

    protected $methodCode = 'lcnetredirect';

    protected $method;

    protected $autifyConfig;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \AutifyDigital\LloydscardnetPayment\Model\Config $autifyConfig
    ) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->autifyConfig = $autifyConfig;
    }

    public function getConfig()
    {
        $paymentLogo = $this->autifyConfig->getConfig('payment/lcnetredirect/payment_logo') ? $this->autifyConfig->getMediaUrl() . "lloydscardnet/" . $this->autifyConfig->getConfig('payment/lcnetredirect/payment_logo') : '';
        
        $outConfig = [
            'payment' => [
                $this->methodCode => [
                    'payment_logo' => $paymentLogo,
                    'payment_tooltip' => $this->autifyConfig->getConfig('payment/lcnetredirect/tooltip_before_button')
                ]
            ]
        ];
        return $outConfig;
    }

}
