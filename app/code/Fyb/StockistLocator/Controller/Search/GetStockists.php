<?php

namespace Fyb\StockistLocator\Controller\Search;

class GetStockists extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Fyb\StockistLocator\Model\LocalstockistsFactory
     */
    protected $localStockistsFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Fyb\StockistLocator\Model\LocalstockistsFactory $localStockistsFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Fyb\StockistLocator\Model\LocalstockistsFactory $localStockistsFactory,
        array $data = []
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->localStockistsFactory = $localStockistsFactory;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $postcode = $this->getRequest()->getPost('address');
        $radius = $this->getRequest()->getPost('radius');
        $isFirstSearch = (int)$this->getRequest()->getPost('isFirstSearch');
        $stockistSearch = $this->localStockistsFactory->getNearestStockists($postcode, $radius);
        if (count($stockistSearch) <= 1 && $isFirstSearch) {
            $defaultAddress = $this->_objectManager->get(\Fyb\Theme\Helper\StoreData::class)->getConfig('stockistlocator/general/default_location');
            $stockistSearch = $this->localStockistsFactory->getNearestStockists($defaultAddress, $radius);
        }

        $layout = $this->layoutFactory->create();

        $html = $layout->createBlock('Fyb\StockistLocator\Block\Index\Index')->setTemplate(
            'Fyb_StockistLocator::stockistlocator_index_results.phtml'
        )->setStockists($stockistSearch)->toHtml();

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($html);
    }
}
