<?php

namespace Rakdesign\Configurator\Controller\Ajax;

class Getdescription extends \Magento\Framework\App\Action\Action {

    protected $resultJsonFactory;
    protected $request;
    protected $productRepository;

    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory) {

        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->request = $request;

        parent::__construct($context);
    }

   protected function loadMyProduct($sku) {
        $product = false;
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $product = false;
        }
        return $product;
    }

    protected function getDescription($sku) {
        if (!$sku) {
            return false;
        }
        $product = $this->loadMyProduct($sku);
        if (!$product) {
            return false;
        }
        return $product->getDescription();
    }

    public function execute() {
        $sku = $this->request->getParam('sku');
        $sku2 = $this->request->getParam('sku2');
        $resultJson = $this->resultJsonFactory->create();
        $descrition = $this->getDescription($sku);
        $descrition2 = $this->getDescription($sku2);
        if (!$descrition && !$descrition2) {
            return $resultJson->setData(['status' => false, 'description' => false]);
        }
        return $resultJson->setData(['status' => true, 'description' => $descrition, 'description2' => $descrition2]);
    }

}
