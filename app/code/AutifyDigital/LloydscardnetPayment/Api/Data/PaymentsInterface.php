<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Api\Data;

interface PaymentsInterface
{

    public const AMOUNT = 'amount';
    public const PAYMENTS_ID = 'payments_id';
    public const ORDER_ID = 'order_id';
    public const REMOTE_REFERENCE = 'remote_reference';
    public const STATUS = 'status';
    public const REMOTE_MESSAGE = 'remote_message';
    public const REDIRECT_EMAIL_SENT = 'redirect_email_sent';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const CARDNET_ORDER_ID = 'cardnet_order_id';
    public const REMOTE_STATUS_OR_CODE = 'remote_status_or_code';

    /**
     * Get payments_id
     *
     * @return string|null
     */
    public function getPaymentsId();

    /**
     * Set payments_id
     *
     * @param string $paymentsId
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setPaymentsId($paymentsId);

    /**
     * Get amount
     *
     * @return string|null
     */
    public function getAmount();

    /**
     * Set amount
     *
     * @param string $amount
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setAmount($amount);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setStatus($status);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setOrderId($orderId);

    /**
     * Get order_increment_id
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set order_increment_id
     *
     * @param string $orderIncrementId
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get remote_reference
     *
     * @return string|null
     */
    public function getRemoteReference();

    /**
     * Set remote_reference
     *
     * @param string $remoteReference
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setRemoteReference($remoteReference);

    /**
     * Get remote_message
     *
     * @return string|null
     */
    public function getRemoteMessage();

    /**
     * Set remote_message
     *
     * @param string $remoteMessage
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setRemoteMessage($remoteMessage);

    /**
     * Get remote_status_or_code
     *
     * @return string|null
     */
    public function getRemoteStatusOrCode();

    /**
     * Set remote_status_or_code
     *
     * @param string $remoteStatusOrCode
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setRemoteStatusOrCode($remoteStatusOrCode);

    /**
     * Get cardnet_order_id
     *
     * @return string|null
     */
    public function getCardnetOrderId();

    /**
     * Set cardnet_order_id
     *
     * @param string $cardnetOrderId
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setCardnetOrderId($cardnetOrderId);

    /**
     * Get redirect_email_sent
     *
     * @return string|null
     */
    public function getRedirectEmailSent();

    /**
     * Set redirect_email_sent
     *
     * @param string $redirectEmailSent
     * @return \AutifyDigital\LloydscardnetPayment\Payments\Api\Data\PaymentsInterface
     */
    public function setRedirectEmailSent($redirectEmailSent);
}
