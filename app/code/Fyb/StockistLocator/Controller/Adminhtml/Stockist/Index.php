<?php

namespace Fyb\StockistLocator\Controller\Adminhtml\Stockist;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Fyb_StockistLocator::stockist');
        $resultPage->addBreadcrumb(__('FYB'), __('FYB'));
        $resultPage->addBreadcrumb(__('Manage item'), __('Manage Stockist'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Stockist'));

        return $resultPage;
    }
}
?>
