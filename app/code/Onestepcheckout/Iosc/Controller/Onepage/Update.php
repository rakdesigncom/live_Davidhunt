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
namespace Onestepcheckout\Iosc\Controller\Onepage;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Onestepcheckout\Iosc\Model\DataManager;

class Update extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var DataManager
     */
    protected $dataManager;
    /**
     * @var $url
     */
    protected $url;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param DataManager $dataManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        DataManager $dataManager
    ) {
            $this->resultJsonFactory = $resultJsonFactory;
            $this->dataManager = $dataManager;
            $this->url = $context->getUrl();
            parent::__construct($context);
    }

    /**
     * Checkout page
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /**
         * get raw json payload and parse
         */
        $payload = [];
        $data = [];
        $content = $this->getRequest()->getContent();

        $response = $this->resultJsonFactory->create();

        if ($content) {
            try {
                $payload = $this->dataManager->deserializeJsonPost($content);
                $data = $this->dataManager->process($payload);

                if ($data['error']) {
                    $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
                }
            } catch (\Exception $e) {
                $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
                $error = $e->getMessage();
                $data = [
                    'success' => false,
                    'error' => true,
                    'data' => $data,
                    'message' => $error
                ];
            }
        }

        $response->setData($data);

        return $response;
    }
}
