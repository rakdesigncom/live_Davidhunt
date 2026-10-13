<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

class TransactionNotificationUrl extends AbstractAction
{
    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        sleep(2);
        $this->helper->addLog('Redirect TransactionNotificationUrl Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $orderId = $this->getRequest()->getParam('order_id');
        $notificationHash = (null !== $this->getRequest()->getParam('notification_hash'))?
        $this->getRequest()->getParam('notification_hash'):'';

        // If order id not found in response params
        if (!empty($orderId) && !empty($notificationHash)) {
            try {
                $mode = $this->config->getConfig('payment/lcnetredirect/lloyds_mode');
                $config = $this->initializeReDirectPaymentParameters($mode);
                $order = $this->orderFactory->create()->load($orderId);

                $transactionTime = $this->getRequest()->getParam('txndatetime');
                $approvalCode = $this->getRequest()->getParam('approval_code');
                $chargeTotal = $this->getRequest()->getParam('chargetotal');
                $currency = $this->getRequest()->getParam('currency');
                $merchantTransactionId = $this->getRequest()->getParam('merchantTransactionId');
                $fail_reason = $this->getRequest()->getParam('fail_reason');
                $status = $this->getRequest()->getParam('status');
                $response_code_3dsecure = $this->getRequest()->getParam('response_code_3dsecure');
                $processor_response_code = $this->getRequest()->getParam('processor_response_code');
                $lloydsOrderId = $this->getRequest()->getParam('oid');

                $verifyResponse = $this->helper->verifyResponseNotification(
                    $notificationHash,
                    $transactionTime,
                    $approvalCode,
                    $chargeTotal,
                    $currency,
                    $config['store_id']
                );

                $paymentModel = $this->helper->getPaymentByOrderId($order->getId());
                $transactionUpdateWebHook = $paymentModel->getTransactionUpdateWebhook();

                if ($transactionUpdateWebHook != 1) {
                    $order->addStatusHistoryComment('The transaction notification URL has been sent for this order.', false);
                    $order->save();
                    $paymentModel->setData('remote_status_or_code', $approvalCode);
                    $paymentModel->setData('remote_message', $approvalCode.'|'.$status.'|'.$response_code_3dsecure.'|'.$processor_response_code.'|'.$lloydsOrderId); // phpcs:ignore
                    $paymentModel->setData('cardnet_order_id', $lloydsOrderId);
                    $paymentModel->setData('transaction_update_webhook', 1);
                    $paymentModel->save();

                    $redirectEmailSent = $paymentModel->getRedirectEmailSent();

                    // Check status of response
                    if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:')
                        || strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false)
                        && $status === 'APPROVED') {
                        try {
                            $order = $this->helper->processOrder($order, $redirectEmailSent);
                            $paymentModel->setData('status', 2);
                            $paymentModel->save();
                        } catch (\Exception $ex) {
                            $this->helper->addLog('Re Direct Response Exception: '.$ex->getMessage());
                        }

                        /** "last successful quote" */
                        $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                            ->setLastSuccessQuoteId($order->getQuoteId());
                        $this->checkoutSession->setLastOrderId($order->getId())
                            ->setLastRealOrderId($order->getIncrementId())
                            ->setLastOrderStatus($order->getStatus());
                    } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
                        $paymentModel->setData('status', 3);
                        $paymentModel->save();

                        // Payment Cancelled
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }
                    } else {
                        $paymentModel->setData('status', 4);
                        $paymentModel->save();

                        // Payment Cancelled
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }
                    }
                } else {
                    $this->helper->addLog('TransactionNotificationUrl already processed');
                }
            } catch (\Exception $e) {
                $this->helper->addLog('TransactionNotificationUrl Exception: '.$e->getMessage());
            }
        }
    }
}
