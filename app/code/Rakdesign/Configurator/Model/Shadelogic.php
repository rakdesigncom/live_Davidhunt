<?php

namespace Rakdesign\Configurator\Model;

class Shadelogic extends \Magento\Framework\Model\AbstractModel
{

    protected $rakdesignConfiguration;

    protected $productType;

    protected $productRepository;

    protected $configurationType;

    protected $productCollectionFactory;

    protected $stockState;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Rakdesign\Configurator\Model\Configurator $rakdesignConfiguration
    ) {
        $this->productRepository = $productRepository;
        $this->rakdesignConfiguration = $rakdesignConfiguration;
        $this->stockState = $stockState;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function getProductDetails($configuration)
    {
        $configuration = json_decode($configuration, true);
        $skuArray = [];
        foreach ($configuration as $item) {
            $skuArray[] = $item['combination_line_one']['name'];
            $skuArray[] = $item['combination_line_two']['name'];
        }
        $skuArray = array_unique($skuArray);
        $returnArray = [];
        foreach ($skuArray as $item) {
            $returnArray[$item]['price'] = 0; //TODO temp fix
            $returnArray[$item]['stockAvailable'] = $this->stockAvailable(0);
        }

        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('sku', ['in' => $skuArray])
            ->addStoreFilter()
            ->load();

        foreach ($collection as $product) {
            //$qty = $this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
            $prodObj = $this->loadMyProduct($product->getSku());
            $qty = $prodObj->getExtensionAttributes()->getStockItem()->getQty();
            $returnArray[$product->getSku()]['price'] = (float)$product->getPrice(
            ); //TODO this is not working properly for b2b
            $returnArray[$product->getSku()]['stockAvailable'] = $this->stockAvailable($qty, $product->getMaterial());
        }

        return $returnArray;
    }

    public function configuratorType($product)
    {
        if ($product->getData('combination_line_two')) {
            return 'base';
        }
        if ($product->getData('combination_line_one')) {
            return 'shade';
        }

        return false;
    }

    /*

     * function to determine if the page is Base or Shade
     *
     *  */

    public function getConfiguration($product)
    {
        //check if product is configurable by checking the product attributes
        if (!$product->getData('combination_line_one') && !$product->getData('combination_line_two')) {
            return false;
        }

        $model = $this->rakdesignConfiguration->load($product->getId(), 'product_id');
        if (!$model->getId()) {
            return $this->checkIfShadeIsConfigurable($product);
        }

        return $model->getCongifuration();
    }

    protected function stockAvailable($qty, $material = false)
    {
        $return = '';
        if ($qty <= 0) {
            $return = __('Out of Stock');
        } else if ($qty >= 100) {
            $return = __('100+ Available');
        } else {
            $return = $qty . ' ' . __('Available');
        }
        if ($material) {
            $return .= ' ' . $material;
        }

        return ['formatted' => $return, 'stock' => $qty];
    }

    /*

     * if shade contains combination_line_one attribute, the function reads the configraution from the base to display the configurator
     *
     *
     *      */

    protected function loadMyProduct($sku)
    {
        return $this->productRepository->get($sku);
    }

    protected function checkIfShadeIsConfigurable($product)
    {
        $useConfigurationFromSku = $product->getData('combination_line_one');
        if (!$useConfigurationFromSku) {
            return false;
        }
        $productBase = $this->loadMyProduct($useConfigurationFromSku);
        if (!$productBase) {
            return false;
        }
        // if the base combination_line_two is empty
        if (!$productBase->getData('combination_line_two')) {
            return false;
        }
        $modelBase = $this->rakdesignConfiguration->load($productBase->getId(), 'product_id');
        if ($modelBase->getId()) {
            //read the configuration from base
            return $modelBase->getCongifuration();
        }

        return false;
    }

}
