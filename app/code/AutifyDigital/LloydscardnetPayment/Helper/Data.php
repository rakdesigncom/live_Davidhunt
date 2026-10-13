<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use AutifyDigital\LloydscardnetPayment\Logger\Logger as AutifyDigitalLcLogger;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\WebService\LloydsBankCardsNetWebService;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Helper Data Class
 */
class Data extends AbstractHelper
{
    /**
     * @var LLOYDS_COOKIE
     */
    public const LLOYDS_COOKIE = 'autify_lloyds_cookie';
    /**
     * @var AutifyDigitalLcLogger
     */
    protected $autifyLcLogger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;

     /**
      * Curl
      * @var object
      */
    protected $_ch;

    /**
     * Request timeout
     * @var int type
     */
    protected $_timeout = 40;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    private $invoiceSender;

    private $_transactionFactory;

    private $lcPaymentsFactory;

    private $priceHelper;

    private $_cookieManager;

    private $_cookieMetadataFactory;

    private $_sessionManager;

    protected $_invoiceService;

     /**
      * Helper constructor
      *
      * @param \Magento\Framework\App\Helper\Context $context
      * @param AutifyDigitalLcLogger $autifyLcLogger
      * @param Config $config
      * @param TypeListInterface $cacheTypeList
      * @param CheckoutSession $checkoutSession
      * @param TimezoneInterface $timezoneInterface
      * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
      * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
      * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
      * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
      * @param LcPaymentsFactory $lcPaymentsFactory
      * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
      * @param CookieManagerInterface $cookieManager
      * @param CookieMetadataFactory $cookieMetadataFactory
      * @param SessionManagerInterface $sessionManager
      * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
      */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        AutifyDigitalLcLogger $autifyLcLogger,
        Config $config,
        TypeListInterface $cacheTypeList,
        CheckoutSession $checkoutSession,
        TimezoneInterface $timezoneInterface,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        LcPaymentsFactory $lcPaymentsFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->autifyLcLogger = $autifyLcLogger;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->checkoutSession = $checkoutSession;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderSender = $orderSender;
        $this->_transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->priceHelper = $priceHelper;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_sessionManager = $sessionManager;
        $this->_invoiceService = $invoiceService;
    }

    /**
     * Create Custom log.
     *
     * @param string $message
     * @param bool $array
     */
    public function addLog($message, $array = false)
    {
        if ($this->config->getConfig('payment/lcnetredirect/log') === '1') {

            if ($array === true) {
                $this->autifyLcLogger->info("message:\n" . json_encode($message, JSON_PRETTY_PRINT));
            } else {
                $this->autifyLcLogger->info($message);
            }
        }
    }

    /**
     * Restore quote
     */
    public function restoreQuote()
    {
        $this->checkoutSession->restoreQuote();
    }

   /**
    * Get Time Zone
    *
    * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
    */
    public function timezone()
    {
        return $this->timezoneInterface;
    }

    /**
     * Create Hash
     *
     * @param string $storeName
     * @param string|int $transactionTime
     * @param int $chargeTotal
     * @param string $currency
     * @return string
     */
    public function createHash($storeName, $transactionTime, $chargeTotal, $currency)
    {
        $sharedSecret = $this->config->getSharedSecret();
        $stringToHash = $storeName . $transactionTime . $chargeTotal . $currency . $sharedSecret;
        $ascii = bin2hex($stringToHash);
        return hash('sha256', $ascii);
    }

    /**
     * Verify Response
     *
     * @param string $response_hash
     * @param string|int $transactionTime
     * @param string|int $approvalCode
     * @param string|int $chargeTotal
     * @param string $currency
     * @param string|int $storeName
     * @return bool
     */
    public function verifyResponse($response_hash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeName)
    {
        $sharedSecret = $this->config->getSharedSecret();
        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currency . $transactionTime . $storeName;
        $ascii = bin2hex($stringToHash);
        $myHash = hash('sha256', $ascii);

        if ($myHash === $response_hash) {
            return true;
        }
        return false;
    }

   /**
    * Verify Response Notification
    *
    * @param string $notification_hash
    * @param string|int $transactionTime
    * @param string|int $approvalCode
    * @param string|int $chargeTotal
    * @param string $currency
    * @param string|int $storeName
    * @return bool
    */
    public function verifyResponseNotification(
        $notification_hash,
        $transactionTime,
        $approvalCode,
        $chargeTotal,
        $currency,
        $storeName
    ) {
        $sharedSecret = $this->config->getSharedSecret();

        $notificationStringToHash = $chargeTotal . $sharedSecret . $currency . $transactionTime . $storeName . $approvalCode; // phpcs:ignore
            
        $asciinotificationNewHash = bin2hex($notificationStringToHash);
        
        $notificationNewHash = hash('sha256', $asciinotificationNewHash);

        if ($notificationNewHash === $notification_hash) {
            return true;
        }
        return false;
    }

    /**
     * Start With
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     * */
    public function startsWith($haystack, $needle)
    {
        if (substr($haystack, 0, strlen($needle)) === $needle) {
            return true;
        }
        return false;
    }

    /**
     * Get Payment By Order Id
     *
     * @param int $orderId
     * @return mixed
     */
    public function getPaymentByOrderId($orderId)
    {
        $payment = $this->lcPaymentsFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->getFirstItem(); // phpcs:ignore

        return $payment;
    }

   /**
    * Send Order Email
    *
    * @param object $orderId
    * @return bool
    */
    public function getPaymentByLcOrderId($orderId)
    {
        $payment = $this->lcPaymentsFactory->create()
            ->getCollection()
            ->addFieldToFilter('cardnet_order_id', $orderId)
            ->getFirstItem(); // phpcs:ignore

        return $payment;
    }

    /**
     * Send Order Email
     *
     * @param object $order
     * @return bool
     */
    public function sendOrderEmail($order)
    {
        return $this->orderSender->send($order);
    }

   /**
    * End With
    *
    * @param string|int $haystack
    * @param string|int $needle
    * @return bool
    * */
    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return false;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Convert In Price Format
     *
     * @param float|string $price
     * @return float|string
     */
    public function priceFormat($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Cancel Order by Order Id
     *
     * @param string|int $order
     */
    public function cancelOrder($order)
    {
        if ($order->getStatus() == "pending" || $order->getStatus() == "pending_payment") {
            $order->cancel($order->getId());
            $orderState = Order::STATE_CANCELED;
            $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
            $order->setCanSendNewEmailFlag(false);
            $order->save();
        } else {
            $orderState = Order::STATE_CANCELED;
            $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
            $order->setCanSendNewEmailFlag(false);
            $order->save();
        }
    }

    /**
     * Process Order
     *
     * @param Order $order
     * @param string|int $sendEmail
     */
    public function processOrder($order, $sendEmail = 0)
    {
        $orderState = Order::STATE_PROCESSING;
        $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
        $order->save();

        if(!$order->hasInvoices()) {         
            $orderPayment = $this->getPaymentByOrderId($order->getId());
            if ($orderPayment->getRedirectEmailSent() != '1') {
                $orderPayment->setData('redirect_email_sent', 1);
                $orderPayment->save();
                $this->sendOrderEmail($order);
            }

            $this->generateInvoice($order, $sendEmail);
        }

        return $order;
    }

    /**
     * Generate Invoice
     *
     * @param string|int $order
     * @param string|int $sendEmail
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateInvoice($order, $sendEmail = 0)
    {
        if (!$order->hasInvoices()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->setTransactionId($order->getId());
            $order->addStatusHistoryComment('Invoice was generated after receiving the payment', false);

            $transaction = $this->_transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
            if ($sendEmail !== '1') {
                $this->invoiceSender->send($invoice);
            }
            $order->save();
        }
    }

    /**
     * Get Webservice Object
     *
     * @param string $mode
     */
    public function getLloydsObject($mode = 'Test')
    {
        if ($mode === 'Test') {
            $sandbox = true;
        } else {
            $sandbox = false;
        }

        $config = $this->config->getBasicConfigurations($mode);

        $caCertPassword = $config['certificate_password'];
        $caCertPath = $config['certificate_file'];
        $userName = $config['user_name'];
        $userPassword = $config['user_password'];

        $lloydsCardWebServ = LloydsBankCardsNetWebService::getInstance(
            $caCertPassword,
            $caCertPath,
            $userName,
            $userPassword,
            $sandbox
        );
        return $lloydsCardWebServ;
    }

    /**
     * Detect Card Type
     *
     * @param strint $cardNumber
     * @return cardtype
     * */
    public function detectCardType($cardNumber)
    {
        $regularExpressionWithKeys = [
            'electron'=> '/^(4026|417500|4405|4508|4844|4913|4917)\d+$/',
            'maestro'=> '/^(5018|5020|5038|5612|5893|6304|6705|6759|6761|6762|6766|6763|6777|0604|6390)\d+$/',
            'dankort'=> '/^(5019)\d+$/',
            'interpayment'=> '/^(636)\d+$/',
            'unionpay'=> '/^(62|88)\d+$/',
            'visa'=> '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'mastercard'=> '/^5[1-5][0-9]{14}$/',
            'amex'=> '/^3[47][0-9]{13}$/',
            'diners'=> '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'discover'=> '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'jcb'=> '/^(?:2131|1800|35\d{3})\d{11}$/'
        ];

        foreach ($regularExpressionWithKeys as $key => $value) {
            if (preg_match($value, $cardNumber)) {
                return $key;
            }
        }
    }

    /**
     * Get ISO Currency Code
     *
     * @param string $currencyCode
     * @return ISOCurrecyCode
     * */
    public function getIsoCurrencyCodeFromCurrencyCode($currencyCode)
    {
        $isoCurrencyCodeList = [
            "AED"=> 784,
            "AFN"=> 971,
            "ALL"=> 8,
            "AMD"=> 51,
            "ANG"=> 532,
            "AOA"=> 973,
            "ARS"=> 32,
            "AUD"=> 36,
            "AWG"=> 533,
            "AZN"=> 944,
            "BAM"=> 977,
            "BBD"=> 52,
            "BDT"=> 50,
            "BGN"=> 975,
            "BHD"=> 48,
            "BIF"=> 108,
            "BMD"=> 60,
            "BND"=> 96,
            "BOB"=> 68,
            "BOV"=> 984,
            "BRL"=> 986,
            "BSD"=> 44,
            "BTN"=> 64,
            "BWP"=> 72,
            "BYR"=> 974,
            "BZD"=> 84,
            "CAD"=> 124,
            "CDF"=> 976,
            "CHE"=> 947,
            "CHF"=> 756,
            "CHW"=> 948,
            "CLF"=> 990,
            "CLP"=> 152,
            "CNY"=> 156,
            "COP"=> 170,
            "COU"=> 970,
            "CRC"=> 188,
            "CUC"=> 931,
            "CUP"=> 192,
            "CVE"=> 132,
            "CZK"=> 203,
            "DJF"=> 262,
            "DKK"=> 208,
            "DOP"=> 214,
            "DZD"=> 12,
            "EGP"=> 818,
            "ERN"=> 232,
            "ETB"=> 230,
            "EUR"=> 978,
            "FJD"=> 242,
            "FKP"=> 238,
            "GBP"=> 826,
            "GEL"=> 981,
            "GHS"=> 936,
            "GIP"=> 292,
            "GMD"=> 270,
            "GNF"=> 324,
            "GTQ"=> 320,
            "GYD"=> 328,
            "HKD"=> 344,
            "HNL"=> 340,
            "HRK"=> 191,
            "HTG"=> 332,
            "HUF"=> 348,
            "IDR"=> 360,
            "ILS"=> 376,
            "INR"=> 356,
            "IQD"=> 368,
            "IRR"=> 364,
            "ISK"=> 352,
            "JMD"=> 388,
            "JOD"=> 400,
            "JPY"=> 392,
            "KES"=> 404,
            "KGS"=> 417,
            "KHR"=> 116,
            "KMF"=> 174,
            "KPW"=> 408,
            "KRW"=> 410,
            "KWD"=> 414,
            "KYD"=> 136,
            "KZT"=> 398,
            "LAK"=> 418,
            "LBP"=> 422,
            "LKR"=> 144,
            "LRD"=> 430,
            "LSL"=> 426,
            "LTL"=> 440,
            "LVL"=> 428,
            "LYD"=> 434,
            "MAD"=> 504,
            "MDL"=> 498,
            "MGA"=> 969,
            "MKD"=> 807,
            "MMK"=> 104,
            "MNT"=> 496,
            "MOP"=> 446,
            "MRO"=> 478,
            "MUR"=> 480,
            "MVR"=> 462,
            "MWK"=> 454,
            "MXN"=> 484,
            "MXV"=> 979,
            "MYR"=> 458,
            "MZN"=> 943,
            "NAD"=> 516,
            "NGN"=> 566,
            "NIO"=> 558,
            "NOK"=> 578,
            "NPR"=> 524,
            "NZD"=> 554,
            "OMR"=> 512,
            "PAB"=> 590,
            "PEN"=> 604,
            "PGK"=> 598,
            "PHP"=> 608,
            "PKR"=> 586,
            "PLN"=> 985,
            "PYG"=> 600,
            "QAR"=> 634,
            "RON"=> 946,
            "RSD"=> 941,
            "RUB"=> 643,
            "RWF"=> 646,
            "SAR"=> 682,
            "SBD"=> 90,
            "SCR"=> 690,
            "SDG"=> 938,
            "SEK"=> 752,
            "SGD"=> 702,
            "SHP"=> 654,
            "SLL"=> 694,
            "SOS"=> 706,
            "SRD"=> 968,
            "SSP"=> 728,
            "STD"=> 678,
            "SYP"=> 760,
            "SZL"=> 748,
            "THB"=> 764,
            "TJS"=> 972,
            "TMT"=> 934,
            "TND"=> 788,
            "TOP"=> 776,
            "TRY"=> 949,
            "TTD"=> 780,
            "TWD"=> 901,
            "TZS"=> 834,
            "UAH"=> 980,
            "UGX"=> 800,
            "USD"=> 840,
            "USN"=> 997,
            "USS"=> 998,
            "UYI"=> 940,
            "UYU"=> 858,
            "UZS"=> 860,
            "VEF"=> 937,
            "VND"=> 704,
            "VUV"=> 548,
            "WST"=> 882,
            "XAF"=> 950,
            "XCD"=> 951,
            "XOF"=> 952,
            "XPF"=> 953,
            "YER"=> 886,
            "ZAR"=> 710,
            "ZMW"=> 967,
        ];

        return isset($isoCurrencyCodeList[trim(strtoupper($currencyCode))])?
        $isoCurrencyCodeList[trim(strtoupper($currencyCode))]:0;
    }

    /**
     * Get data from cookie
     *
     * @return value
     */
    public function getLloydsCookie()
    {
        return $this->_cookieManager->getCookie(self::LLOYDS_COOKIE);
    }

    /**
     * Set data to cookie
     *
     * @param [string] $value
     * @param integer  $duration
     *
     * @return void
     */
    public function setLloydsCookie($value, $duration = 86400)
    {
        $metadata = $this->_cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->_sessionManager->getCookiePath())
            ->setDomain($this->_sessionManager->getCookieDomain());

        $this->_cookieManager->setPublicCookie(
            self::LLOYDS_COOKIE,
            $value,
            $metadata
        );
    }

    /**
     * Delete cookie
     *
     * @return void
     */
    public function deleteLloydsCookie()
    {
        $this->_cookieManager->deleteCookie(
            self::LLOYDS_COOKIE,
            $this->_cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->_sessionManager->getCookiePath())
                ->setDomain($this->_sessionManager->getCookieDomain())
        );
    }

    /**
     * Call Curl function for paymentjs
     *
     * @param string $url
     * @param string $method
     * @param array $postArray
     * @param string $apiKey
     * @param string $apiSecret
     *
     * @return array
     *
     * // @codingStandardsIgnoreStart
     */
    public function callCurl($url, $method = "GET", $postArray = [], $apiKey = '', $apiSecret = '')
    {

        $requestDatas = json_encode($postArray);

        $responseArray = [];
        
        $paymentTimeStamp = time() * 1000;

        $paymentNonce= sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', random_int(0, 65535), random_int(0, 65535), random_int(0, 65535), random_int(16384, 20479), random_int(32768, 49151), random_int(0, 65535), random_int(0, 65535), random_int(0, 65535));

        $msg = $apiKey . $paymentNonce . $paymentTimeStamp . $requestDatas;

        $messageSignature = base64_encode(hash_hmac('sha256', $msg, $apiSecret));

        $headers = [
            'Api-Key: ' . $apiKey,
            'Client-Request-Id: ' . $paymentNonce,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($requestDatas),
            'Message-Signature: ' . $messageSignature,
            'Timestamp: ' . $paymentTimeStamp
        ];

        $this->addLog($requestDatas, true);
        try {
           
            $this->_ch = curl_init();

            if ($method == 'POST') {
                $this->curlOption(CURLOPT_POST, 1);
                $this->curlOption(CURLOPT_POSTFIELDS, $requestDatas);
                $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
            } elseif ($method == "GET") {
                $url .= http_build_query($postArray);
                $this->curlOption(CURLOPT_HTTPGET, 1);
            } else {
                $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
                $this->curlOption(CURLOPT_SSL_VERIFYPEER, 0);
                
            }
            $this->curlOption(CURLOPT_RETURNTRANSFER, true);
            $this->curlOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curlOption(CURLOPT_SSL_VERIFYHOST, 0);
            $this->curlOption(CURLOPT_SSL_VERIFYPEER, 0);
            $this->curlOption(CURLOPT_URL, $url);
            $this->curlOption(CURLOPT_HTTPHEADER, $headers);
    
            $response = curl_exec($this->_ch);
            $this->addLog($response, true);
            $err = curl_errno($this->_ch);
            $httpCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
            if ($err) {
                $this->doError(curl_error($this->_ch));
            }
            curl_close($this->_ch);

            if (! $err && (
    
                    $httpCode === 200 ||
    
                    $httpCode === 201 ||
    
                    $httpCode === 202 ||
    
                    $httpCode === 203 ||
    
                    $httpCode === 204 )
    
            ) {

                $responseArray['status'] = 'success';

            } else {

                $responseArray['status'] = 'error';

            }

            $responseArray['httpCode'] = $httpCode;

            $responseArray['data'] = json_decode($response);

            return $responseArray;

        } catch (\Exception $ex) {
            $responseArray['message'] = $ex->getMessage();
            $responseArray['status'] = 'error';
            $responseArray['data'] = json_decode($response);
        } 
        $this->addLog("message:\n" . json_encode($response, JSON_PRETTY_PRINT));
        return $responseArray; // @codingStandardsIgnoreEnd
    }

    /**
     * Set curl option directly
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    protected function curlOption($name, $value)
    {
        curl_setopt($this->_ch, $name, $value); // phpcs:ignore
    }

    /**
     * Throw error exception
     *
     * @param string $string
     * @return void
     * @throws \Exception
     */
    public function doError($string)
    {
        throw new \InvalidArgumentException($string);
    }
}
