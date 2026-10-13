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
namespace Onestepcheckout\Iosc\Api;

/**
 * ExtensionInterface class for @see \Magento\Quote\Api\Data\AddressInterface
 */
interface DobAssignmentInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    /**
     * Get DOB
     *
     * @return string|null
     */
    public function getDob();

    /**
     * Set DOB
     *
     * @param string $value
     * @return $this
     */
    public function setDob(
        $value
    );
}
