<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Api\Data;

interface PaynowInterface
{

    const MERCHANT_TRANSACTION_ID = 'merchant_transaction_id';
    const UPDATED_AT = 'updated_at';
    const NAME = 'name';
    const PAYNOW_ID = 'paynow_id';
    const BILLTO_NAME = 'billto_name';
    const CREATED_AT = 'created_at';
    const INVOICE_NUMBER = 'invoice_number';
    const BILLTO_COUNTRY = 'billto_country';
    const SHIPTO_CITY = 'shipto_city';
    const SHIPTO_STREET2 = 'shipto_street2';
    const SHIPTO_STATE = 'shipto_state';
    const SHIPTO_PHONE = 'shipto_phone';
    const BILLTO_CITY = 'billto_city';
    const SHIPTO_NAME = 'shipto_name';
    const BILLTO_STREET2 = 'billto_street2';
    const SHIPTO_POSTCODE = 'shipto_postcode';
    const PAYMENT_STATUS = 'payment_status';
    const BILLTO_STATE = 'billto_state';
    const GATEWAY_TRANSACTION_ID = 'gateway_transaction_id';
    const EMAIL_ADDRESS = 'email_address';
    const BILLTO_PHONE = 'billto_phone';
    const SHIPTO_COUNTRY = 'shipto_country';
    const SHIPTO_STREET1 = 'shipto_street1';
    const BILLTO_POSTCODE = 'billto_postcode';
    const BILLTO_STREET1 = 'billto_street1';
    const COMPANY_NAME = 'company_name';
    const AMOUNT = 'amount';

    /**
     * Get paynow_id
     * @return string|null
     */
    public function getPaynowId();

    /**
     * Set paynow_id
     * @param string $paynowId
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setPaynowId($paynowId);

    /**
     * Get invoice_number
     * @return string|null
     */
    public function getInvoiceNumber();

    /**
     * Set invoice_number
     * @param string $invoiceNumber
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setInvoiceNumber($invoiceNumber);


    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setName($name);

    /**
     * Get email_address
     * @return string|null
     */
    public function getEmailAddress();

    /**
     * Set email_address
     * @param string $emailAddress
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setEmailAddress($emailAddress);

    /**
     * Get amount
     * @return string|null
     */
    public function getAmount();

    /**
     * Set amount
     * @param string $amount
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setAmount($amount);

    /**
     * Get billto_name
     * @return string|null
     */
    public function getBilltoName();

    /**
     * Set billto_name
     * @param string $billtoName
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoName($billtoName);

    /**
     * Get company_name
     * @return string|null
     */
    public function getCompanyName();

    /**
     * Set company_name
     * @param string $companyName
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setCompanyName($companyName);

    /**
     * Get billto_street1
     * @return string|null
     */
    public function getBilltoStreet1();

    /**
     * Set billto_street1
     * @param string $billtoStreet1
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoStreet1($billtoStreet1);

    /**
     * Get billto_street2
     * @return string|null
     */
    public function getBilltoStreet2();

    /**
     * Set billto_street2
     * @param string $billtoStreet2
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoStreet2($billtoStreet2);

    /**
     * Get billto_city
     * @return string|null
     */
    public function getBilltoCity();

    /**
     * Set billto_city
     * @param string $billtoCity
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoCity($billtoCity);

    /**
     * Get billto_postcode
     * @return string|null
     */
    public function getBilltoPostcode();

    /**
     * Set billto_postcode
     * @param string $billtoPostcode
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoPostcode($billtoPostcode);

    /**
     * Get billto_state
     * @return string|null
     */
    public function getBilltoState();

    /**
     * Set billto_state
     * @param string $billtoState
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoState($billtoState);

    /**
     * Get billto_country
     * @return string|null
     */
    public function getBilltoCountry();

    /**
     * Set billto_country
     * @param string $billtoCountry
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoCountry($billtoCountry);

    /**
     * Get billto_phone
     * @return string|null
     */
    public function getBilltoPhone();

    /**
     * Set billto_phone
     * @param string $billtoPhone
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoPhone($billtoPhone);

    /**
     * Get shipto_name
     * @return string|null
     */
    public function getShiptoName();

    /**
     * Set shipto_name
     * @param string $shiptoName
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoName($shiptoName);

    /**
     * Get shipto_street1
     * @return string|null
     */
    public function getShiptoStreet1();

    /**
     * Set shipto_street1
     * @param string $shiptoStreet1
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoStreet1($shiptoStreet1);

    /**
     * Get shipto_street2
     * @return string|null
     */
    public function getShiptoStreet2();

    /**
     * Set shipto_street2
     * @param string $shiptoStreet2
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoStreet2($shiptoStreet2);

    /**
     * Get shipto_city
     * @return string|null
     */
    public function getShiptoCity();

    /**
     * Set shipto_city
     * @param string $shiptoCity
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoCity($shiptoCity);

    /**
     * Get shipto_postcode
     * @return string|null
     */
    public function getShiptoPostcode();

    /**
     * Set shipto_postcode
     * @param string $shiptoPostcode
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoPostcode($shiptoPostcode);

    /**
     * Get shipto_state
     * @return string|null
     */
    public function getShiptoState();

    /**
     * Set shipto_state
     * @param string $shiptoState
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoState($shiptoState);

    /**
     * Get shipto_country
     * @return string|null
     */
    public function getShiptoCountry();

    /**
     * Set shipto_country
     * @param string $shiptoCountry
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoCountry($shiptoCountry);

    /**
     * Get shipto_phone
     * @return string|null
     */
    public function getShiptoPhone();

    /**
     * Set shipto_phone
     * @param string $shiptoPhone
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoPhone($shiptoPhone);

    /**
     * Get merchant_transaction_id
     * @return string|null
     */
    public function getMerchantTransactionId();

    /**
     * Set merchant_transaction_id
     * @param string $merchantTransactionId
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setMerchantTransactionId($merchantTransactionId);

    /**
     * Get gateway_transaction_id
     * @return string|null
     */
    public function getGatewayTransactionId();

    /**
     * Set gateway_transaction_id
     * @param string $gatewayTransactionId
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setGatewayTransactionId($gatewayTransactionId);

    /**
     * Get payment_status
     * @return string|null
     */
    public function getPaymentStatus();

    /**
     * Set payment_status
     * @param string $paymentStatus
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setPaymentStatus($paymentStatus);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setUpdatedAt($updatedAt);
}

