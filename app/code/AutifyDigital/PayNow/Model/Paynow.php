<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Model;

use AutifyDigital\PayNow\Api\Data\PaynowInterface;
use Magento\Framework\Model\AbstractModel;

class Paynow extends AbstractModel implements PaynowInterface
{

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(\AutifyDigital\PayNow\Model\ResourceModel\Paynow::class);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getPaynowId()
    {
        return $this->getData(self::PAYNOW_ID);
    }

    /**
     * @param $paynowId
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setPaynowId($paynowId)
    {
        return $this->setData(self::PAYNOW_ID, $paynowId);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getInvoiceNumber()
    {
        return $this->getData(self::INVOICE_NUMBER);
    }

    /**
     * @param $invoiceNumber
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        return $this->setData(self::INVOICE_NUMBER, $invoiceNumber);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @param $name
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getEmailAddress()
    {
        return $this->getData(self::EMAIL_ADDRESS);
    }

    /**
     * @param $emailAddress
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setEmailAddress($emailAddress)
    {
        return $this->setData(self::EMAIL_ADDRESS, $emailAddress);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @param $amount
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoName()
    {
        return $this->getData(self::BILLTO_NAME);
    }

    /**
     * @param $billtoName
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoName($billtoName)
    {
        return $this->setData(self::BILLTO_NAME, $billtoName);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCompanyName()
    {
        return $this->getData(self::COMPANY_NAME);
    }

    /**
     * @param $companyName
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setCompanyName($companyName)
    {
        return $this->setData(self::COMPANY_NAME, $companyName);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoStreet1()
    {
        return $this->getData(self::BILLTO_STREET1);
    }

    /**
     * @param $billtoStreet1
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoStreet1($billtoStreet1)
    {
        return $this->setData(self::BILLTO_STREET1, $billtoStreet1);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoStreet2()
    {
        return $this->getData(self::BILLTO_STREET2);
    }

    /**
     * @param $billtoStreet2
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoStreet2($billtoStreet2)
    {
        return $this->setData(self::BILLTO_STREET2, $billtoStreet2);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoCity()
    {
        return $this->getData(self::BILLTO_CITY);
    }

    /**
     * @param $billtoCity
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoCity($billtoCity)
    {
        return $this->setData(self::BILLTO_CITY, $billtoCity);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoPostcode()
    {
        return $this->getData(self::BILLTO_POSTCODE);
    }

    /**
     * @param $billtoPostcode
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoPostcode($billtoPostcode)
    {
        return $this->setData(self::BILLTO_POSTCODE, $billtoPostcode);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoState()
    {
        return $this->getData(self::BILLTO_STATE);
    }

    /**
     * @param $billtoState
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoState($billtoState)
    {
        return $this->setData(self::BILLTO_STATE, $billtoState);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoCountry()
    {
        return $this->getData(self::BILLTO_COUNTRY);
    }

    /**
     * @param $billtoCountry
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoCountry($billtoCountry)
    {
        return $this->setData(self::BILLTO_COUNTRY, $billtoCountry);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getBilltoPhone()
    {
        return $this->getData(self::BILLTO_PHONE);
    }

    /**
     * @param $billtoPhone
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setBilltoPhone($billtoPhone)
    {
        return $this->setData(self::BILLTO_PHONE, $billtoPhone);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoName()
    {
        return $this->getData(self::SHIPTO_NAME);
    }

    /**
     * @param $shiptoName
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoName($shiptoName)
    {
        return $this->setData(self::SHIPTO_NAME, $shiptoName);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoStreet1()
    {
        return $this->getData(self::SHIPTO_STREET1);
    }

    /**
     * @param $shiptoStreet1
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoStreet1($shiptoStreet1)
    {
        return $this->setData(self::SHIPTO_STREET1, $shiptoStreet1);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoStreet2()
    {
        return $this->getData(self::SHIPTO_STREET2);
    }

    /**
     * @param $shiptoStreet2
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoStreet2($shiptoStreet2)
    {
        return $this->setData(self::SHIPTO_STREET2, $shiptoStreet2);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoCity()
    {
        return $this->getData(self::SHIPTO_CITY);
    }

    /**
     * @param $shiptoCity
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoCity($shiptoCity)
    {
        return $this->setData(self::SHIPTO_CITY, $shiptoCity);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoPostcode()
    {
        return $this->getData(self::SHIPTO_POSTCODE);
    }

    /**
     * @param $shiptoPostcode
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoPostcode($shiptoPostcode)
    {
        return $this->setData(self::SHIPTO_POSTCODE, $shiptoPostcode);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoState()
    {
        return $this->getData(self::SHIPTO_STATE);
    }

    /**
     * @param $shiptoState
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoState($shiptoState)
    {
        return $this->setData(self::SHIPTO_STATE, $shiptoState);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoCountry()
    {
        return $this->getData(self::SHIPTO_COUNTRY);
    }

    /**
     * @param $shiptoCountry
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoCountry($shiptoCountry)
    {
        return $this->setData(self::SHIPTO_COUNTRY, $shiptoCountry);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShiptoPhone()
    {
        return $this->getData(self::SHIPTO_PHONE);
    }

    /**
     * @param $shiptoPhone
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setShiptoPhone($shiptoPhone)
    {
        return $this->setData(self::SHIPTO_PHONE, $shiptoPhone);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getMerchantTransactionId()
    {
        return $this->getData(self::MERCHANT_TRANSACTION_ID);
    }

    /**
     * @param $merchantTransactionId
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setMerchantTransactionId($merchantTransactionId)
    {
        return $this->setData(self::MERCHANT_TRANSACTION_ID, $merchantTransactionId);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getGatewayTransactionId()
    {
        return $this->getData(self::GATEWAY_TRANSACTION_ID);
    }

    /**
     * @param $gatewayTransactionId
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setGatewayTransactionId($gatewayTransactionId)
    {
        return $this->setData(self::GATEWAY_TRANSACTION_ID, $gatewayTransactionId);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getPaymentStatus()
    {
        return $this->getData(self::PAYMENT_STATUS);
    }

    /**
     * @param $paymentStatus
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setPaymentStatus($paymentStatus)
    {
        return $this->setData(self::PAYMENT_STATUS, $paymentStatus);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param $createdAt
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @param $updatedAt
     * @return Paynow|\AutifyDigital\PayNow\Paynow\Api\Data\PaynowInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}

