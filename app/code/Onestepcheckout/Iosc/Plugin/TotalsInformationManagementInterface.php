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
namespace Onestepcheckout\Iosc\Plugin;

use Exception;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentPersister;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\Output\ShippingMethod;

class TotalsInformationManagementInterface
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ShippingMethod
     */
    protected $outputShippingMethod;
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;
    /**
     * @var ShippingAssignmentPersister
     */
    protected $shippingAssignmentPersister;

    /**
     *
     * @param Data $helper
     * @param ShippingMethod $outputShippingMethod
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingAssignmentPersister $shippingAssignmentPersister
     */
    public function __construct(
        Data $helper,
        ShippingMethod $outputShippingMethod,
        CartRepositoryInterface $cartRepository,
        ShippingAssignmentPersister $shippingAssignmentPersister
    ) {

        $this->helper = $helper;
        $this->outputShippingMethod = $outputShippingMethod;
        $this->cartRepository = $cartRepository;
        $this->shippingAssignmentPersister = $shippingAssignmentPersister;
    }

    /**
     * AfterCalculate plugin
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param TotalsInformationInterface $addressInformation
     */
    public function afterCalculate(
        $subject,
        $result,
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {

        if (!$this->helper->isEnabled()) {
            return $result;
        }
        try {
            $quote = $this->cartRepository->get($cartId);
            $shippingCarrierCode = $addressInformation->getShippingCarrierCode();
            $shippingMethodCode = $addressInformation->getShippingMethodCode();
            if (is_object($quote) && !empty($shippingCarrierCode) && !empty($shippingMethodCode)) {
                $quote = $this
                ->outputShippingMethod
                ->prepareShippingAssignment(
                    $quote,
                    $quote->getShippingAddress(),
                    $shippingCarrierCode . '_' . $shippingMethodCode
                );
                $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
                $this->shippingAssignmentPersister->save($quote, current($shippingAssignments));
            }
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (Exception $e) {
            /*
             * silence, we don't care if this fails,
             * affects shipping method saving from cart page
             */
        }

        return $result;
    }
}
