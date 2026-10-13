<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

class RedirectPostData extends AbstractAction
{

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog('RedirectPostData Controller call');
        $result = $this->resultJsonFactory->create();

        try {
            $mode = $this->config->getConfig('payment/lcnetredirect/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);

        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $this->helper->addLog('Redirect Order Exception1: '. $e->getMessage());
            $this->messageManager->addError(__($e->getMessage()));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
            return $result->setData($postData);
        }

        $currentOrder = $this->getCurrentOrder();

        if (!$currentOrder->getIncrementId()) {
            $this->helper->addLog('Order Id not found');
            $this->messageManager->addWarningMessage(__("Invalid payment request!"));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
            return $result->setData($postData);
        }
        
        $this->helper->addLog('Order Id : '. $currentOrder->getId());
        try {
            $action = $config['processing_url'];
            $amount = $currentOrder->getGrandTotal();
            $ccy_code = $currentOrder->getOrderCurrencyCode();
            $transactionCode = $currentOrder->getIncrementId().'-'.time();
            
            $currentOrder->setStatus("pending_payment")->save();

            $newTxCode = strtoupper($config['payment_ref_prefix'].$config['payment_leading_zeros'].'-'.
            $transactionCode.'-'.rand(0, 1000000));
            $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($ccy_code);

            $billingAddress = $currentOrder->getBillingAddress();
            $shippingAddress = $currentOrder->getShippingAddress();

            // If cart is virtual
            if (!$shippingAddress || $shippingAddress->getFirstname() == null) {
                $shippingAddress = $currentOrder->getBillingAddress();
            }

            //now lets our sage form parameters and send it over to lloydscardnet
            $timeZone = $this->helper->timezone();
            $dt = $timeZone->date();
    
            //YYYY:MM:DD-hh:mm:ss
            $transactionTime = $dt->format('Y:m:d-H:i:s');

            $billName = $billingAddress->getFirstname().' '.$billingAddress->getLastname();
            $shipName = $shippingAddress->getFirstname().' '.$shippingAddress->getLastname();

            $email = $billingAddress->getEmail();
            if (!$email) {
                $email = $currentOrder->getCustomerEmail();
            }

            $chargeTotal = number_format(floatval($amount), 2, '.', '');
            $hashValue = $this->helper->createHash($config['store_id'], $transactionTime, $chargeTotal, $currency);
            
            $formData = [
                'txntype' => 'sale',
                'timezone' => date_default_timezone_get(),
                'txndatetime' => $transactionTime,
                'hash_algorithm' => 'SHA256',
                'hash' => $hashValue,
                'oid'  => $currentOrder->getIncrementId(),
                'storename' => $config['store_id'],
                'mode' => $config['pay_mode'],
                'checkoutoption' => $config['page_option'],
                'bcompany' => $billingAddress->getCompany(),
                'bname' => $billName,
                'baddr1' => implode(' ', $billingAddress->getStreet()),
                'baddr2' => '',
                'bcity' => $billingAddress->getCity(),
                'bstate' => $billingAddress->getRegion(),
                'bcountry' => $billingAddress->getCountryId(),
                'bzip' => $billingAddress->getPostcode(),
                'phone' => $billingAddress->getTelephone(),
                'email' => $email,
                'sname' => $shipName,
                'saddr1' => implode(' ', $shippingAddress->getStreet()),
                'saddr2' => '',
                'scity' => $shippingAddress->getCity(),
                'sstate' => $shippingAddress->getRegion(),
                'scountry' => $shippingAddress->getCountryId(),
                'szip' => $shippingAddress->getPostcode(),
                'comments' => 'Autify Digital Plugin',
                'threeDSRequestorChallengeIndicator' => '1',
                'chargetotal' => $chargeTotal,
                'currency' => $currency,
                'merchantTransactionId' => $newTxCode,
                'responseFailURL' => $config['return_url'],
                'responseSuccessURL' => $config['return_url'],
                'transactionNotificationURL' => $config['transaction_notification_url'],
                'authenticateTransaction' => 'true'
            ];

            if($this->config->getConfig('payment/lcnetredirect/dynamic_merchant_name')) {
                $formData['dynamicMerchantName'] = $this->config->getConfig('payment/lcnetredirect/dynamic_merchant_name');
            }

            $this->helper->addLog($formData, true);

            $postData = [
                'action' => $action,
                'error' => false,
                'fields' => $formData
            ];

            // Save Payment Model
            $lcPaymentModel = $this->lcPaymentsFactory->create();
            $lcPaymentModel->setData('amount', $chargeTotal);
            $lcPaymentModel->setData('status', 1);
            $lcPaymentModel->setData('order_id', $currentOrder->getId());
            $lcPaymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
            $lcPaymentModel->setData('remote_reference', $newTxCode);
            $lcPaymentModel->save();

            return $result->setData($postData);
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            // Custom Logger
            $this->helper->addLog('Redirect Order Exception2: '. $e->getMessage());

            $this->messageManager->addError(__($e->getMessage()));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
        }

        return $result->setData($postData);
    }
}
