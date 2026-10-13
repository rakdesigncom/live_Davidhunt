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
namespace Onestepcheckout\Iosc\Model;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Onestepcheckout\Iosc\Helper\Data;

/**
 * PaymentConfigProvider implementing ConfigProviderInterface
 */
class PaymentConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;
    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        ServiceOutputProcessor $serviceOutputProcessor,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $return = [];
        if ($this->helper->isEnabled() && !$this->checkoutSession->getQuote()->isVirtual()) {
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement
                ->getList($this->checkoutSession->getQuote()->getId()));
            $data = $this->serviceOutputProcessor
                ->convertValue(
                    $paymentDetails,
                    PaymentDetailsInterface::class
                );
            if (!empty($data['payment_methods'])) {
                $return = [
                'paymentMethods' => $data['payment_methods'],
                ];
            }
        }

        return $return;
    }
}
