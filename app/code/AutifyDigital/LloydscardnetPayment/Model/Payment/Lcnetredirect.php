<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Payment;

use Magento\Framework\Message\ManagerInterface;
use AutifyDigital\LloydscardnetPayment\Model\Config;

/**
 * [Description Lcnetredirect]
 */
class Lcnetredirect extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var $_code
     */
    
    protected $_code = "lcnetredirect";
    /**
     * @var $_isGateway
     */
    protected $_isGateway = true;

    /**
     * @var $_canCapture
     */
    protected $_canCapture = true;

    /**
     * @var $_canRefund
     */
    protected $_canRefund = true;

    /**
     * @var $_canRefundInvoicePartial
     */
    protected $_canRefundInvoicePartial = true;
    
    /**
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helper
     * @param ManagerInterface $managerInterface
     * @param Config $config
     * @param array $data
     *
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper,
        ManagerInterface $managerInterface,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->helper = $helper;
        $this->managerInterface = $managerInterface;
        $this->config = $config;
    }

    /**
     * Check Is Available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return [type]
     * // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }

    /**
     * Create Refund Offline and Online
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \Magento\Payment\Model\Method\AbstractMethod|void
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        $order = $payment->getOrder();
        $orderId = $order->getId();
        $currency = $order->getOrderCurrencyCode();
        $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($currency);

        $orderPayment = $this->helper->getPaymentByOrderId($orderId);

        if ($orderPayment->getCardnetOrderId()) {
            
            $mode = $this->config->getConfig('payment/lcnetredirect/lloyds_mode');
            
            $paymentConfig = $this->config->getBasicConfigurations($mode);

            $apiUrl = $paymentConfig['rest_url'];

            $apiKey = $paymentConfig['api_key'];

            $apiSecret = $paymentConfig['api_secret'];

            $refundEndpoint = $apiUrl . 'gateway/v2/orders/' . $orderPayment->getCardnetOrderId();

            $refundRequest = [
                "requestType" => "ReturnTransaction",
                "transactionAmount" => [
                    "total" => $amount,
                    "currency" => $currency,
                ],
            ];

            $response = $this->helper->callCurl($refundEndpoint, "POST", $refundRequest, $apiKey, $apiSecret);

            if (isset($response['data'])) {
                $responseData = $response['data'];
                $approvalCode = $responseData->approvalCode;

                $transactionStatus = $responseData->transactionStatus;

                try {
                    if ($response['status'] == 'success' && $response['httpCode'] == 200) {
                        if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                            $transactionId = $responseData->ipgTransactionId;
                            $payment
                                ->setTransactionId($transactionId)
                                ->setIsTransactionClosed(false);
                            $orderPayment->setData('cardnet_refund_id', $transactionId);
                            $orderPayment->setData('status', 5)->save();
                
                            $order->save();
                
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            } else {
                return false;
            }
        }
    }
}
