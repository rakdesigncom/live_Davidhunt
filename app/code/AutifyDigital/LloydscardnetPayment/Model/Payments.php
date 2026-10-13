<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model;

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface;
use Magento\Framework\Model\AbstractModel;

class Payments extends AbstractModel implements PaymentsInterface
{

   /**
     * Construct
     */
    public function _construct()
    {
        $this->_init(\AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments::class);
    }

    /**
     * Get Payments Id
     */
    public function getPaymentsId()
    {
        return $this->getData(self::PAYMENTS_ID);
    }

    /**
     * Set Payments Id
     */
    public function setPaymentsId($paymentsId)
    {
        return $this->setData(self::PAYMENTS_ID, $paymentsId);
    }

    /**
     * Get Amount
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set Amount
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

     /**
     * Get Status
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Order Id
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

   /**
     * Set Order Id
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get Order Increment Id
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * Set Order Increment Id
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * Get Remote Reference
     */
    public function getRemoteReference()
    {
        return $this->getData(self::REMOTE_REFERENCE);
    }

    /**
     * Set Remote Reference
     */
    public function setRemoteReference($remoteReference)
    {
        return $this->setData(self::REMOTE_REFERENCE, $remoteReference);
    }

    /**
     * Get Remote Message
     */
    public function getRemoteMessage()
    {
        return $this->getData(self::REMOTE_MESSAGE);
    }

    /**
     * Set Remote Message
     */
    public function setRemoteMessage($remoteMessage)
    {
        return $this->setData(self::REMOTE_MESSAGE, $remoteMessage);
    }

    /**
     * Get Remote Status Or Code
     */
    public function getRemoteStatusOrCode()
    {
        return $this->getData(self::REMOTE_STATUS_OR_CODE);
    }

    /**
     * Set Remote Status Or Code
     */
    public function setRemoteStatusOrCode($remoteStatusOrCode)
    {
        return $this->setData(self::REMOTE_STATUS_OR_CODE, $remoteStatusOrCode);
    }

   /**
     * Get Cardnet Order Id
     */
    public function getCardnetOrderId()
    {
        return $this->getData(self::CARDNET_ORDER_ID);
    }

    /**
     * Set Cardnet Order Id
     */
    public function setCardnetOrderId($cardnetOrderId)
    {
        return $this->setData(self::CARDNET_ORDER_ID, $cardnetOrderId);
    }

    /**
     * Get Redirect Email Sent
     */
    public function getRedirectEmailSent()
    {
        return $this->getData(self::REDIRECT_EMAIL_SENT);
    }

    /**
     * Set Redirect Email Sent
     */
    public function setRedirectEmailSent($redirectEmailSent)
    {
        return $this->setData(self::REDIRECT_EMAIL_SENT, $redirectEmailSent);
    }
}
