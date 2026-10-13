<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Model\Output;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;

class Totals implements OutputManagementInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;
    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;
    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Output\OutputManagement::getOutputKey()
     */
    public function getOutputKey()
    {
        return 'paymentMethod';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     * @param Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ServiceOutputProcessor $serviceOutputProcessor,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        CartTotalRepositoryInterface $cartTotalsRepository,
        Session $checkoutSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     *
     * @param $input
     * @see \Onestepcheckout\Iosc\Model\Output\OutputManagement::processPayload()
     */
    public function processPayload($input)
    {
        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getId()) {
            /** @var PaymentDetailsInterface $paymentDetails */
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($quote->getId()));
            $paymentDetails->setTotals($this->cartTotalsRepository->get($quote->getId()));
            $data = $this->serviceOutputProcessor
                ->convertValue($paymentDetails, PaymentDetailsInterface::class);
        }

        return $data;
    }
}
