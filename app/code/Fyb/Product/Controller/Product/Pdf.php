<?php

namespace Fyb\Product\Controller\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Pdf extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param null|\Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($context);

        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(LoggerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        return $this->executeTc();
    }

    public function executeTc()
    {
        $isPdf = $this->getRequest()->getParam('pdf', 0);
        $product = $this->_initProduct();
        if (!$product) {
            return $this->noProductRedirect();
        }

        /** @var \Fyb\Theme\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->removeOutputElement('logo');
        if (!$isPdf) {
            return $resultPage;
        }

        try {
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $this->addFonts();

            $html = $resultPage->getLayout()->getBlock('specification.pdf')
                ->setTemplate('Fyb_Product::specification/pdf/product_specification_full.phtml')
                ->toHtml();

            $pdf->SetFont('DejaVuSans', '', 10);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetFooterMargin(10);

            $pdf->AddPage();

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->SetAutoPageBreak(false);

            $pdf->Output($this->getPdfFileName($product), 'D');
            exit;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->noProductRedirect();
        }
    }

//    public function executeSpiritix()
//    {
//        $isPdf = $this->getRequest()->getParam('pdf', 0);
//        $product = $this->_initProduct();
//        if (!$product) {
//            return $this->noProductRedirect();
//        }
//
//        /** @var \Fyb\Theme\Framework\View\Result\Page $resultPage */
//        $resultPage = $this->resultPageFactory->create();
//        if (!$isPdf) {
//            return $resultPage;
//        }
//
//        try {
//            $html = $resultPage->renderHtml();
//            $input = new StringInput();
//            $input->setHtml($html);
//            $converter = new Converter($input, new DownloadOutput());
//            $output = $converter->convert();
//            $output->download($this->getPdfFileName($product), true);
//        } catch (\Exception $e) {
//            $this->logger->critical($e);
//
//            return $this->noProductRedirect();
//        }
//
//        return $this->noProductRedirect();
//    }

    protected function addFonts()
    {
        $viewDir = ObjectManager::getInstance()->get(\Magento\Framework\Module\Dir\Reader::class)
            ->getModuleDir(
                \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
                'Fyb_Product'
            );

        $fonts = [
            'GillSans.ttf',
            'DidotLTStd-Italic.ttf',
            'GillSansLight.ttf',
        ];

        foreach ($fonts as $font) {
            \TCPDF_FONTS::addTTFfont($viewDir . '/frontend/web/fonts/' . $font, 'TrueType', 32);
        }
    }

    /**
     * Return pdf name for product
     *
     * @param Product $product
     *
     * @return string
     */
    protected function getPdfFileName($product)
    {
        return 'Specification-for-' . $product->getSku() . '.pdf';
    }

    /**
     * Redirect if product failed to load
     *
     * @return Redirect|Forward
     */
    protected function noProductRedirect()
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->forward('noroute');

        return $resultForward;
    }

    /**
     * Initialize requested product object
     *
     * @return Product
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('id');

        /** @var \Magento\Catalog\Helper\Product $product */
        $product = $this->_objectManager->get(\Magento\Catalog\Helper\Product::class);

        return $product->initProduct($productId, $this);
    }
}
