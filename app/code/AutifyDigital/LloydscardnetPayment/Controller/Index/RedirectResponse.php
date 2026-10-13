<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);
namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Redirect Response for Lloydscard net payment
 */
class RedirectResponse extends AbstractAction
{
    
     /**
     * Handle the case when both orderId and responseHash are empty.
     */
    private function handleEmptyOrder()
    {
        $this->messageManager->addErrorMessage(__("Something went wrong. Order not found."));
        return $this->_redirect($this->_baseUrl . 'checkout/cart/');
    }

     /**
     * Redirect to the cart page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectToCart()
    {
        return $this->_redirect('checkout/cart');
    }

    /**
     * Handle an exception.
     *
     * @param \Exception $e
     */
    // private function handleException(\Exception $e)
    private function handleException(\Exception $e)
    {
        // If error code not found in the lloydsPaymentErrorCallback function, display a generic error message
        $this->messageManager->addErrorMessage(__('An error occurred. Please contact customer support.'));
    }

    public function lloydsPaymentErrorCallback($failRc)
    {
        if (!empty($failRc)) {
            $errorCodeList = [
                '32000', '50001', '50002', '50003', '50004', '50005', '50006', '50007', '50008', '50010', '50011',
                '50012', '50013', '50014', '50015', '50016', '50716', '50019', '50020', '50021', '50022', '50023',
                '50030', '50031', '50033', '50034', '50035', '50036', '50037', '50038', '50039', '50041', '50042',
                '50043', '50051', '50052', '50053', '50054', '50055', '50056', '50057', '50058', '50061', '50062',
                '50063', '50065', '50066', '50067', '50068', '50070', '50075', '50078', '50082', '50087', '50090',
                '50091', '50092', '50093', '50094', '50095', '50096', '50098', '500I1', '500I2', '500N0', '500O6',
                '500P9', '500S4', '500T6', '500T8', '500U0', '500U1', '500U2', '500U3', '500U4', '500U5', '500U6',
                '500U7', '500U8', '500V0', '500V1', '500V2', '500V3', '500V4', '500V7', '500V8', '500V9', '5001A',
                '500M1', '500M2', '500M3', '500N7', '500NB', '500NC', '500X1', '500X2', '500X3', '500X4', '5102', '5101'
            ];

            if ($failRc == '5993') {
                return __('The payment was not successful; kindly attempt it once more.');
            } elseif (in_array($failRc, $errorCodeList)) {
                return __('Declined: Your bank has declined the payment. Please try again or use an alternative payment method.');
            } else {
                return __('An internal error has occurred, please try again. If the error persists please contact the Seller.');
            }
        }
        // If no error code provided, return null
        return null;
        
    }
    public function execute()
    {
        $this->helper->addLog('Redirect Response Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $orderId = $this->getRequest()->getParam('order_id');
        $responseHash = $this->getRequest()->getParam('response_hash');

        $failRc = $this->getRequest()->getParam('fail_rc');
        
        // Get the error message using lloydsPaymentErrorCallback method
        $errorMessage = $this->lloydsPaymentErrorCallback($failRc);
               
        if (empty($orderId) && empty($responseHash)) {
            $this->handleEmptyOrder();
            return $this->redirectToCart();
        }

        try {
            $mode = $this->config->getConfig('payment/lcnetredirect/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);
            $order = $this->orderFactory->create()->load($orderId);

            if (!$this->isValidOrder($order)) {
                return $this->redirectToCart();
            }

            $transactionTime = (null !== $this->getRequest()->getParam('txndatetime'))?
                $this->getRequest()->getParam('txndatetime'):null;
                $approvalCode = (null !== $this->getRequest()->getParam('approval_code'))?
                $this->getRequest()->getParam('approval_code'):null;
                $chargeTotal = (null !== $this->getRequest()->getParam('chargetotal'))?
                $this->getRequest()->getParam('chargetotal'):null;
                $currency = (null !== $this->getRequest()->getParam('currency'))?
                $this->getRequest()->getParam('currency'):null;
                $fail_reason = (null !== $this->getRequest()->getParam('fail_reason'))?
                $this->getRequest()->getParam('fail_reason'):null;
                $status = (null !== $this->getRequest()->getParam('status'))?
                $this->getRequest()->getParam('status'):null;
                $response_code_3dsecure = (null !== $this->getRequest()->getParam('response_code_3dsecure'))?
                $this->getRequest()->getParam('response_code_3dsecure'):null;
                $processor_response_code = (null !== $this->getRequest()->getParam('processor_response_code'))?
                $this->getRequest()->getParam('processor_response_code'):null;
                $lloydsOrderId = (null !== $this->getRequest()->getParam('oid'))?
                $this->getRequest()->getParam('oid'):null;

                $verifyResponse = $this->helper->verifyResponse(
                    $responseHash,
                    $transactionTime,
                    $approvalCode,
                    $chargeTotal,
                    $currency,
                    $config['store_id']
                );

                $paymentModel = $this->helper->getPaymentByOrderId($order->getId());
                $transactionUpdate = $paymentModel->getTransactionUpdateResponse();
                

                if($transactionUpdate != 1) {
                    
                    $paymentModel->setData('remote_status_or_code', $approvalCode);
                    $paymentModel->setData('remote_message', $approvalCode.'|'.$status.'|'.$response_code_3dsecure.'|'.$processor_response_code.'|'.$lloydsOrderId); // phpcs:ignore
                    $paymentModel->setData('cardnet_order_id', $lloydsOrderId);
                    $paymentModel->setData('transaction_update_response', 1);
                    $paymentModel->save();

                    
                    //Check status of response
                    if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:') ||
                    strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false) &&
                    $status === 'APPROVED') {
                        return $this->saveAfterApprovePayment($order);

                    } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
                        $paymentModel->setData('status', 3);
                        $paymentModel->save();

                        ($errorMessage) ? $this->messageManager->addErrorMessage(__($errorMessage)) : '';
                        
                        // Payment Cancelled
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }

                        //Set Cookie
                        $this->helper->setLloydsCookie('1');
                        return $this->_redirect($this->_baseUrl.'checkout/cart/');

                    } else {
                        $paymentModel->setData('status', 4);
                        $paymentModel->save();

                        ($errorMessage) ? $this->messageManager->addErrorMessage(__($errorMessage)) : '';
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }

                        //Set Cookie
                        $this->helper->setLloydsCookie('1');
                        return $this->_redirect($this->_baseUrl.'checkout/cart/');
                    }
                } else {
                    $this->messageManager->addErrorMessage(__($errorMessage));
                }


        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $this->helper->setLloydsCookie('1');
        return $this->redirectToCart();
    }

    /**
     * Check if the order is valid.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function isValidOrder($order)
    {
        // logic to check the validity of the order.
        return $order && $order->getId();
    }


    /**
     * Save After Approve Payment
     *
     * @return
     */

    public function saveAfterApprovePayment($order) 
    {

        $paymentModel = $this->helper->getPaymentByOrderId($order->getId());

        $redirectEmailSent = $paymentModel->getRedirectEmailSent();
        try {
            $order = $this->helper->processOrder($order, $redirectEmailSent);
            $paymentModel->setData('status', 2);
            $paymentModel->save();
            $this->messageManager->addSuccessMessage(__('Your order number with '.$order->getIncrementId().' is successful')); // phpcs:ignore
        } catch (\Exception $ex) {
            $this->helper->addLog('Re Direct Response Exception: '. $ex->getMessage());
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage(__('Something went wrong'));
        }

        /** "last successful quote" */
        $this->checkoutSession->setLastQuoteId($order->getQuoteId())->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId())->setLastOrderStatus($order->getStatus());

        $successUrl = $this->_url->getUrl('checkout/onepage/success');
        return $this->_redirect($successUrl);
    }

}
