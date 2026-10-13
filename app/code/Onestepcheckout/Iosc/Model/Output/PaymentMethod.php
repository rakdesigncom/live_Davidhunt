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

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\PaymentExtensionFactory;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Store\Model\ScopeInterface;

class PaymentMethod implements OutputManagementInterface
{
    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var PaymentInterface
     */
    protected $paymentInterface;
    /**
     * @var SimpleDataObjectConverter
     */
    protected $simpleDataObjectConverter;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PaymentExtensionFactory
     */
    protected $paymentExtensionFactory;
    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;
    /**
     * @var ScopeConfigInterface|null
     */
    public $scopeConfig = null;
    /**
     * @var string
     */
    public $restrictedMethods = '';

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
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentInterface $paymentInterface
     * @param SimpleDataObjectConverter $simpleDataObjectConverter
     * @param Session $checkoutSession
     * @param PaymentExtensionFactory $paymentExtensionFactory
     * @param TotalsCollector $totalsCollector
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentInterface $paymentInterface,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        Session $checkoutSession,
        PaymentExtensionFactory $paymentExtensionFactory,
        TotalsCollector $totalsCollector
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentInterface = $paymentInterface;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;
        $this->checkoutSession = $checkoutSession;
        $this->paymentExtensionFactory =  $paymentExtensionFactory;
        $this->totalsCollector = $totalsCollector;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Output\OutputManagement::processPayload()
     */
    public function processPayload($input)
    {
        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->getId()) {
            return $data;
        }

        if (isset($input[$this->getOutputKey()]) &&
            !$this->isRestricted($input[$this->getOutputKey()])
        ) {
            $shippingAddress = $quote->getShippingAddress();
            $updateOnPmClick = $this
                ->scopeConfig
                ->getValue(
                    'onestepcheckout_iosc/payments/update_on_selection',
                    ScopeInterface::SCOPE_STORE
                ) ?? false;

            if (!$updateOnPmClick) {
                $shippingAddress->setCollectShippingRates(true);
                $quote->setOscCollectRates("1");
                $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
            }

            try {
                $paymentData = $input[$this->getOutputKey()];
                if (! empty($paymentData)) {
                    if (isset($paymentData['extension_attributes']) &&
                        is_array($paymentData['extension_attributes'])
                    ) {
                        $paymentData['extension_attributes'] = $this->handleExtAttributes($paymentData);
                    }

                    $method = $this->paymentInterface;

                    foreach ($paymentData as $k => $v) {
                        $methodName = 'set' . $this->simpleDataObjectConverter->snakeCaseToUpperCamelCase($k);
                        if (method_exists($method, $methodName)) {
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            call_user_func([
                                $method,
                                $methodName
                            ], $v);
                        }
                    }
                }
                $this->paymentMethodManagement->set($quote->getId(), $method);
                $data['response']['selected']['success'] = true;
                $data['response']['selected']['error'] = false;
                $data['response']['selected']['message'] = $method->getMethod();
            } catch (Exception $e) {
                $data['selected']['success'] = false;
                $data['selected']['error'] = true;
                $data['selected']['message'] = $e->getMessage();
            }
        }

        return $data;
    }

    /**
     * HandleExtAttributes function
     *
     * @param array $paymentData
     */
    private function handleExtAttributes($paymentData)
    {
        $extensionAttributes = $this->paymentExtensionFactory->create();
        foreach ($paymentData['extension_attributes'] as $k => $v) {
            $methodName = 'set' . $this->simpleDataObjectConverter->snakeCaseToUpperCamelCase($k);
            if (method_exists($extensionAttributes, $methodName)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                call_user_func([
                    $extensionAttributes,
                    $methodName
                ], $v);
            }
        }
        $methodName = false;
        return $extensionAttributes;
    }

    /**
     * Match method against method codes that are allowed to be processed
     *
     * @param array $input
     * @return boolean
     */
    public function isRestricted($input)
    {
        $match = $input['method'] ?? 'empty';
        return (boolean)in_array($match, $this->getRestrictedMethods());
    }

    /**
     * Get restricted methods
     *
     * @return array
     */
    public function getRestrictedMethods()
    {

        if (empty($this->restrictedMethods)) {
            $this->restrictedMethods = explode(
                ',',
                $this
                    ->scopeConfig
                    ->getValue(
                        'onestepcheckout_iosc/payments/skip_on_ajax',
                        ScopeInterface::SCOPE_STORE
                    ) ?? ''
            );
        }

        return $this->restrictedMethods;
    }
}
