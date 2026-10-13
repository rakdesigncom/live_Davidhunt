<?php

namespace Rakdesign\Configurator\Controller\Adminhtml\Customconfigurator;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action {

    /**
     * @var PageFactory
     */
    protected $resultPagee;
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
    public function execute() {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Rakdesign_Configurator::configuratorsetting');
        $resultPage->addBreadcrumb(__('Configurator'), __('Configurator'));
        // $resultPage->addBreadcrumb(__('Manage item'), __('Manage Xpromo'));
        $resultPage->getConfig()->getTitle()->prepend(__('Configurator'));

        return $resultPage;
    }

}

?>
