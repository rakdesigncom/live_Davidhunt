<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Observer;

use Magento\Framework\Session\SessionManagerInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Predispatch Ovserber Class
 */
class ActionPredispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var sessionManagerInterface
     */
    protected $sessionManagerInterface;

    /**
     * @var LloydscardnetDataHelper
     */
    private $helper;

    /**
     * @var LloydscardnetDataHelper
     */
    protected $config;

    private $checkoutSession;

    private $quoteRepository;

    /**
     * @var SessionManagerInterface
     */
    private $_coreSession;


    /**
     * Construct
     *
     * @param SessionManagerInterface $sessionManagerInterface
     * @param HelperData $helperData
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param LloydscardnetDataHelper $helper
     * @param Context $context
     * @param LloydscardnetDataHelper $config
     */
    public function __construct(
        SessionManagerInterface $sessionManagerInterface,
        HelperData $helperData,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->sessionManagerInterface = $sessionManagerInterface;
        $this->helper = $helperData;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        // Restore Quote if payment failed
        $lloydsCookie = $this->helper->getLloydsCookie();
        if ($lloydsCookie) {
            $this->helper->restoreQuote();
            $this->helper->deleteLloydsCookie();
        }

        $request = $observer->getEvent()->getRequest();
        $actionFullName = strtolower($request->getFullActionName());
        $redirectArray = ['lcnetpayment_index_redirectresponse', 'lcnetpayment_index_redirectpostdata', 'lcnetpayment_index_directresponse', 'lcnetpayment_index_directpostdata', 'lcnetpayment_index_confirmresponse'];

        if (in_array($actionFullName, $redirectArray)) {
            $this->_coreSession = $this->sessionManagerInterface;
            if (!$this->_coreSession->getSessionId() || $request->getParam('SID') !== $this->_coreSession->getSessionId()) {
                $this->_coreSession->setSessionId($request->getParam('SID'));
            }
        }
    }
}
