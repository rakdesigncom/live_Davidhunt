<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderFactory;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\View\Result\LayoutFactory;

/**
 * Class AbstractAction
 */
abstract class AbstractAction extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     * */
    protected $helper;

    /**
     * @var string
     */
    protected $formKey;

    /**
     * @var $_baseUrl
     */
    protected $_baseUrl;

    /**
     * @var Magento\Framework\View\Page
     */
    protected $_cancel_url;
    /**
     * @var Magento\Framework\View\Page
     */
    protected $_return_url;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\media_path
     */
    protected $mediaUrl;

     /**
     * @var LloydscardnetDataHelper
     */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

     /**
     * @var OrderFactory
     */
    protected $orderFactory;

    protected $lcPaymentsFactory;

     /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

     /**
     * @var SessionManagerInterface
     */
    private $_coreSession;
    
    /**
     * AbstractLloydsAction constructor
     *
     * @param Context $context
     * @param HelperData $helperData
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param OrderFactory $orderFactory
     * @param LcPaymentsFactory $lcPaymentsFactory
     * @param CartRepositoryInterface $quoteRepository
     *
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Config $config,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        OrderFactory $orderFactory,
        LcPaymentsFactory $lcPaymentsFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->helper = $helperData;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->orderFactory = $orderFactory;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_baseUrl = $this->config->getStoreUrl();
    }

    /**
     *Create Csrf Validation Exception
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Validate For Csrf
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Initialize Core Parameters
     *
     * @return void
     */
    protected function initializeUrls()
    {
        $this->_coreSession = $this->_objectManager->get(\Magento\Framework\Session\SessionManagerInterface::class);

        $orderId = $this->getCurrentOrder()->getId();
        $cancelUrl = $this->_url->getUrl(
            'lcnetpayment/index/cancel',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );
        $returnUrl = $this->_url->getUrl(
            'lcnetpayment/index/redirectResponse',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );
        $transactionNotificationURL = $this->_url->getUrl(
            'lcnetpayment/index/transactionNotificationUrl',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );

        $config['cancel_url'] = $cancelUrl;
        $config['return_url'] = $returnUrl;
        $config['transaction_notification_url'] = $transactionNotificationURL;
        return $config;
    }

    /**
     * Get Current Order
     *
     * @return Order
     */
    public function getCurrentOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * Initialize ReDirect Payment Parameters
     *
     * @param string $mode
     * @return void
     */
    protected function initializeReDirectPaymentParameters($mode)
    {
        $config = $this->config->getBasicConfigurations($mode);
        $allUrls = $this->initializeUrls();

        $allParams = array_merge($config, $allUrls);
        return $allParams;
    }
}
