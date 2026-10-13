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
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Onestepcheckout\Iosc\Helper\Data;
use Psr\Log\LoggerInterface;

class Coupon implements OutputManagementInterface
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var Escaper
     */
    protected $escaper;
    /**
     * @var LoggerInterface
     */
    protected $loggerInterface;
    /**
     * @var CouponFactory
     */
    protected $couponFactory;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'coupon-code';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param CartRepositoryInterface $quoteRepository
     * @param Escaper $escaper
     * @param LoggerInterface $loggerInterface
     * @param CouponFactory $couponFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        CartRepositoryInterface $quoteRepository,
        Escaper $escaper,
        LoggerInterface $loggerInterface,
        CouponFactory $couponFactory,
        Session $checkoutSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->escaper = $escaper;
        $this->loggerInterface = $loggerInterface;
        $this->couponFactory = $couponFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {
        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        $response = [
            'success' => false,
            'error' => false,
            'message' => false
        ];

        $input = (isset($input[$this->getOutputKey()])) ? $input[$this->getOutputKey()] : [];

        $ruleIds = $quote->getAppliedRuleIds() ?? false;

        if ($ruleIds && !empty($quote->getCouponCode())) {
            $isCouponApplied = $this
                ->couponFactory
                ->create()
                ->loadByCode($quote->getCouponCode()) ?? false ;

            if (!$isCouponApplied) {
                $input['remove'] = "1";
                $input['coupon_code'] = $quote->getCouponCode();
            }
        } elseif (!$ruleIds && !empty($quote->getCouponCode())) {
            $input['remove'] = "1";
            $input['coupon_code'] = $quote->getCouponCode();
        } elseif ($ruleIds && empty($quote->getCouponCode())) {
            $response['action'] = 0;
        }

        if ($input && $quote->getId()) {
            if (isset($input['remove']) || isset($input['coupon_code'])) {
                $couponCode = $input['remove'] == 1 ? '' : trim($input['coupon_code']);
                $oldCouponCode = $quote->getCouponCode();

                $codeLength = strlen($couponCode);
                if (! $codeLength && empty($oldCouponCode)) {
                    $response['success'] = true;
                    $response['error'] = false;
                }

                if (!$response['success']) {
                    try {
                        $maxLength = Cart::COUPON_CODE_MAX_LENGTH;
                        $isCodeLengthValid = $codeLength && $codeLength <= $maxLength;

                        $itemsCount = $quote->getItemsCount();
                        if ($itemsCount) {
                            $shippingAddress = $quote->getShippingAddress();
                            $quote->getShippingAddress()->setCollectShippingRates(true);
                            $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                            $this->quoteRepository->save($quote);
                        }

                        if ($codeLength) {
                            $response = $this->setCouponCode(
                                $quote,
                                $response,
                                $couponCode,
                                $isCodeLengthValid,
                                $itemsCount
                            );
                        } else {
                            $response['success'] = true;
                            $response['action'] = 0;
                            $response['message'] = __('You cancelled the coupon code.');
                        }
                    } catch (LocalizedException $e) {
                        $response['success'] = false;
                        $response['error'] = true;
                        if (isset($response['action'])) {
                            unset($response['action']);
                        }
                        $response['message'] = $e->getMessage();
                    } catch (Exception $e) {
                        $response['success'] = false;
                        $response['error'] = true;
                        if (isset($response['action'])) {
                            unset($response['action']);
                        }
                        $response['message'] = __($e->getMessage());
                        $this->loggerInterface->critical($e);
                    }
                }
            }
        }
        $data = $response;
        return $data;
    }

    /**
     * Set Coupon Code
     *
     * @param $quote
     * @param $response
     * @param $couponCode
     * @param $isCodeLengthValid
     * @param $itemsCount
     * @return array
     */
    private function setCouponCode(
        $quote,
        $response,
        $couponCode,
        $isCodeLengthValid,
        $itemsCount
    ) {
        $escapedCode = $this->escaper->escapeHtml($couponCode);
        if (! $itemsCount) {
            if ($isCodeLengthValid) {
                $coupon = $this->couponFactory->create();
                $coupon->load($couponCode, 'code');
                if ($coupon->getId()) {
                    $quote->setCouponCode($couponCode)->save();
                    $response['success'] = true;
                    $response['error'] = false;
                    $response['message'] = __('You used coupon code "%1".', $escapedCode);
                } else {
                    $response['success'] = false;
                    $response['error'] = true;
                    $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
                }
            } else {
                $response['success'] = false;
                $response['error'] = true;
                $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
            }
        } else {
            if ($isCodeLengthValid && $couponCode == $quote->getCouponCode()) {
                $response['success'] = true;
                $response['message'] = __('You used coupon code "%1".', $escapedCode);
            } else {
                $response['success'] = false;
                $response['error'] = true;
                $response['message'] = __('The coupon code "%1" is not valid.', $escapedCode);
                $this->cart->save();
            }
        }
        return $response;
    }
}
