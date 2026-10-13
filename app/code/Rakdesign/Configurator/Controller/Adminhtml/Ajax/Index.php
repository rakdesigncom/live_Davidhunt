<?php

namespace Rakdesign\Configurator\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action {

    protected $storeManager;
    protected $resultJsonFactory;
    protected $request;
    protected $configurator;
    protected $state;
    protected $delimiter;
    protected $qtyDelimiter;
    protected $inlineTranslation;

    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Rakdesign\Configurator\Model\ResourceModel\Configurator $configurator,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\Translate\Inline\StateInterface $state,
            \Magento\Framework\App\Request\Http $request
    ) {
        $this->configurator = $configurator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->delimiter = '|';
        $this->qtyDelimiter = '*';
        $this->inlineTranslation = $state;
        $this->request = $request;
        parent::__construct($context);
    }

    public function execute() {
        $resultJson = $this->resultJsonFactory->create();
        if ($this->request->getMethod() != 'POST') {
            return $resultJson->setData(['status' => false, 'data' => '', 'error' => 'ONLY POST']);
        }
        $productID = $this->request->getPost('product');

        $action = $this->request->getPost('action');
//print_r($this->request->getPost());
        $return = array();
        switch ($action) {
            case 'generate':
                $return = $this->generateAction($productID);
                break;
            case 'getcombinations':
                $return = $this->getcombinationsAction($productID);
                break;
            case 'saveprices':
                $return = $this->savepricesAction($productID);
                break;

            default:
                break;
        }
        return $resultJson->setData($return);
    }

    protected function combinations($arrays) {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_key => $property_value) {
                    //$tmp[] = $result_item + array($property => array($property_key => $property_value));
                    $tmp[] = $result_item + array($property => $property_value);
                }
            }
            $result = $tmp;
        }

        return $result;
    }

    protected function prepareDataArray($dataArray) {
        $returnArray = array();
        foreach ($dataArray as $item) {
            $tempData = explode($this->qtyDelimiter, $item);

            $product = $this->_objectManager->get('Magento\Catalog\Model\ProductFactory')->create()->loadByAttribute('sku', $tempData[0]);
            if (!$product) {
                continue;
            }
            $returnArray[$tempData[0]]['id'] = $product->getId();
            $returnArray[$tempData[0]]['name'] = $tempData[0];
            $returnArray[$tempData[0]]['title'] = $product->getName();
            $returnArray[$tempData[0]]['cover'] = $product->getData('image'); //get base image
            $returnArray[$tempData[0]]['price'] = $product->getPrice();
            $returnArray[$tempData[0]]['rrp'] = (float)$product->getRrp()*1.2;
            $returnArray[$tempData[0]]['dimwidth'] = $product->getData('dimwidth');
            $returnArray[$tempData[0]]['dimheight'] = $product->getData('dimheight');
            $returnArray[$tempData[0]]['dimdepth'] = $product->getData('dimdepth');
            $returnArray[$tempData[0]]['dia'] = $product->getData('dia');
            $returnArray[$tempData[0]]['measurements'] = $product->getData('measurements');
            $returnArray[$tempData[0]]['shademeasurements'] = $product->getData('shademeasurements');
            if (isset($tempData[1])) {
                $returnArray[$tempData[0]]['qty'] = $tempData[1];
            } else {
                $returnArray[$tempData[0]]['qty'] = 1;
            }
        }
        return $returnArray;
    }

    protected function generateCombinations($productID) {
        $templateAttributes = ['combination_line_one', 'combination_line_two'];
        $attributesArray = array();
        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productID);
        foreach ($templateAttributes as $item) {
            if ($product->getData($item)) {

                $attributesArray[$item] = $this->prepareDataArray(explode($this->delimiter, $product->getData($item)));
            }
        }

        return $this->combinations($attributesArray);
    }

    protected function savepricesAction($productID) {

        $model = $this->_objectManager->create('Rakdesign\Configurator\Model\Configurator')->load($productID, 'product_id');
        if (!$model->getId()) {
            return ['status' => false, 'data' => $productID, 'error' => ''];
        }
        if ($model->getCongifuration()) {

            $configuration = json_decode($model->getCongifuration(), true);
            foreach ($configuration as $key => $item) {
                $configuration[$key]['image'] = $item['combination_line_one']['name'] . '/' . $item['combination_line_two']['name'] . '.jpg';
            }
            $model->setCongifuration(json_encode($configuration));
            $model->setModified(date('Y-m-d H:i:s'));
            try {
                $model->save();
                return ['status' => true, 'data' => $productID,];
            } catch (\Exception $e) {
                return ['status' => false, 'error' => $e->getMessage()];
            }
        }
        return ['status' => false, 'data' => $productID, 'error' => ''];
    }

    protected function getcombinationsAction($productID) {



        $model = $this->_objectManager->create('Rakdesign\Configurator\Model\Configurator')->load($productID, 'product_id');
        if (!$model->getId()) {
            return ['status' => false, 'data' => $productID, 'error' => ''];
        }
        if ($model->getCongifuration()) {
            return ['status' => true, 'tt' => $model->getCongifuration(), 'data' => json_decode($model->getCongifuration(), true)];
        }
        return ['status' => false, 'data' => $productID, 'error' => ''];
    }

    protected function generateAction($productID) {

        $combinations = $this->generateCombinations($productID);
        foreach ($combinations as $key => $item) {
            $combinations[$key]['image'] = '/media/shade_configurator/' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg';
        }

        $model = $this->_objectManager->create('Rakdesign\Configurator\Model\Configurator')->load($productID, 'product_id');
        if (!$model->getId()) {
            $model = $this->_objectManager->create('Rakdesign\Configurator\Model\Configurator');
        }
        $model->setName('name');
        $model->setProductId($productID);
        $model->setCongifuration(json_encode($combinations));
        $model->setCreated(date('Y-m-d H:i:s'));
        $model->setModified(date('Y-m-d H:i:s'));
        try {
            $model->save();
            return ['status' => true, 'data' => ''];
        } catch (\Exception $e) {
            return ['status' => false, 'data' => $productIDt, 'error' => $e->getMessage()];
        }
        return ['status' => false, 'data' => $productID, 'error' => 'cant save'];
    }

}
