<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Payments;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    
    /**
     * Admin resource identifier for the module
     */

    public const ADMIN_RESOURCE = 'AutifyDigital_LloydscardnetPayment::autify_lloydscardnetpayment_payments';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    
    protected $resultPageFactory;

    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var string[]
     */
    protected $_publicActions = ['index'];

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('AutifyDigital'), __('AutifyDigital Lloydscardnet'))
            ->addBreadcrumb(__('Payments'), __('Payments'));
        $resultPage->getConfig()->getTitle()->prepend(__("Lloydscardnet Payments"));
        return $resultPage;
    }
}
