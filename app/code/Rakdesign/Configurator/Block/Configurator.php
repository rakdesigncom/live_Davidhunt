<?php

namespace Rakdesign\Configurator\Block;

class Configurator extends \Magento\Framework\View\Element\Template {

    protected $_registry;
    protected $currency;
    protected $root;
    protected $placeholder;
    protected $_request;
    protected $productRepository;
    protected $_storeManager;
    protected $currentStoreId;
    protected $customerSession;
    protected $customerBrands;
    protected $rakdesignConfiguration;
    protected $rakdesignConfiguratorLogic;
    protected $productType;
    protected $stockState;
    protected $productInfoArray;
    protected $stockAvailableArray;
    protected $priceArray;
    protected $transactionRRPArray;
//    protected $transactionRRPHelper;

    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Customer\Model\Session $customerSession,
            \Magento\Framework\App\Request\Http $request,
            \Magento\CatalogInventory\Api\StockStateInterface $stockState,
            \Rakdesign\Configurator\Model\Configurator $rakdesignConfiguration,
            \Rakdesign\Configurator\Model\Shadelogic $rakdesignConfiguratorLogic,
//            \Rakdesign\ManualProductprice\Helper\Data $transactionRRPHelper,
            array $data = []
    ) {
        $this->_registry = $registry;
        $this->rakdesignConfiguration = $rakdesignConfiguration;
        $this->rakdesignConfiguratorLogic = $rakdesignConfiguratorLogic;
        $this->root = $this->getRoot();
        $this->productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->stockState = $stockState;
        $this->customerSession = $customerSession;
//        $this->transactionRRPHelper=$transactionRRPHelper;

        $this->productInfoArray = [];
        $this->stockAvailableArray = [];
        $this->priceArray = [];
        $this->transactionRRPArray= [];
        $this->currency = "£";
        $this->placeholder = '/placeholder/placeholder.jpg';
        $this->currentStoreId = $this->_getWebsiteId();
        $this->customerBrands = $this->_getCustomerBrands();
        parent::__construct($context, $data);
    }

    protected function getRoot() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        //var_dump($directory->getPath('media')); exit;
        return $directory->getPath('media');
    }

    public function getProduct() {
        $sku = $this->_request->getParam('sku');
        if ($sku) {
            return $this->loadMyProduct($sku);
        }
    }

    /*

     *
     * function to get customer allowed selling brands for b2b
     *
     *  */

    protected function _getCustomerBrands() {
//        if ($this->currentStoreId == 2) {
//            $customer = $this->customerSession->getCustomer();
//            $customerBrands = $customer->getResource()->getAttribute('allowed_selling_brand')->getFrontend()->getValue($customer);
//            //var_dump($customerBrands); exit;
//            $customerBrands = explode(',', $customerBrands);
//            foreach ($customerBrands as $key => $item) {
//                $customerBrands[$key] = trim($item);
//            }
//            return $customerBrands;
//        }
        return [];
    }

    /*

     *
     * function to check if the combination fits the buying restriction for b2b
     *
     *  */

    protected function checkBuyingRestrictions($list) {

        // if xpallet is enabled then dont sell this product
        if (isset($list['combination_line_one']['xpallet']) &&
                $list['combination_line_one']['xpallet'] &&
                isset($list['combination_line_two']['xpallet']) &&
                $list['combination_line_two']['xpallet']) {

            return false;
        }


        // if buying_restriction_code==GLD then dont sell this product
        if (isset($list['combination_line_one']['buying_restriction_code']) &&
                $list['combination_line_one']['buying_restriction_code'] == 'GLD' &&
                isset($list['combination_line_two']['buying_restriction_code']) &&
                $list['combination_line_two']['buying_restriction_code'] == 'GLD') {

            return false;
        }

        // if the first and second product fits the buying restriction
        if (isset($list['combination_line_one']['buying_restriction_code']) &&
                in_array($list['combination_line_one']['buying_restriction_code'], $this->customerBrands) &&
                isset($list['combination_line_two']['buying_restriction_code']) &&
                in_array($list['combination_line_two']['buying_restriction_code'], $this->customerBrands)) {

            return true;
        }



        // if there are no buying restrictions
        if (!isset($list['combination_line_one']) && !isset($list['combination_line_one'])) {
            return true;
        }
        return false;
    }

    protected function loadMyProduct($sku) {
        return $this->productRepository->get($sku);
    }

    protected function _getWebsiteId() {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function getWebsiteId() {
        return $this->currentStoreId;
    }

    /*

     * IF this product type is Table Lamp or Floor Lamp then the 'base' arrows are at the bottom
     * if it's anything else then the 'base' arrows are at the top
     * please put sku if the product is shade because we need to read in the type only from the base
     *      */

    public function getArrowPositionLogic($sku = false) {
        $_product = false;
        if ($sku) {
            $_product = $this->loadMyProduct($sku);
        } else {
            $_product = $this->getCurrentProduct();
        }
        if (!$_product) {
            return '';
        }
        $prod_type = $_product->getAttributeText('prod_type');
        $return = 'arrows-reverse';
        $arrayNotReverse = ['Table Lamp', 'Floor Lamp','Shade'];

        if (in_array($prod_type, $arrayNotReverse)) {
            $return = 'arrows-standard';
        }
        return $return;
    }

    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

    public function getAdditionalData() {
        $sku = $this->_request->getParam('sku');

        if ($sku) {

            $product = $this->loadMyProduct($sku);
            $attributes = $product->getAttributes();
            $data = [];
            $excludeAttr = [];
            foreach ($attributes as $attribute) {
                if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                    $value = $attribute->getFrontend()->getValue($product);

                    if (!$product->hasData($attribute->getAttributeCode())) {
                        $value = __('N/A');
                    } elseif (is_array($value)) {
                        if(isset($value['value'])){
                            $value = $value['value'];
                        }else{
                           $value = '';
                        }
                    } elseif (is_string($value) && $value == '') {
                        $value = __('No');
                    } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {

                        $value = $value;
                    }


                    $data[$attribute->getAttributeCode()] = [
                        'label' => __($attribute->getStoreLabel()),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                    ];
                }
            }
            return $data;
        }

        return false;
    }

    public function getCurrentProductSku() {
        return $this->getCurrentProduct()->getSku();
    }

    public function getPreloadConfiguration() {

        if ($this->_request->getParam('base') && $this->_request->getParam('shade')) {
            $return = array();
            $return['combination_line_one'] = 'combination_line_one__' . $this->_request->getParam('base');
            $return['combination_line_two'] = 'combination_line_two__' . $this->_request->getParam('shade');
            return json_encode($return);
        }
        return false;
    }

    public function getDescription() {
        return $this->getCurrentProduct()->getDescription();
    }

    protected function flattenToMultiDimensional($keys, $lastVal) {
        $result = [];
        $keys = array_reverse($keys);

        foreach ($keys as $key) {

            $lastVal = [
                $key => $lastVal
            ];
        }
        $result = array_merge_recursive($result, $lastVal);
        return $result;
    }

    /*

     * if shade contains combination_line_one attribute it reads the configraution from the base to display the configurator
     *
     *
     *      */

    protected function checkIfShadeIsConfigurable($product) {
        $useConfigurationFromSku = $product->getData('combination_line_one');
        if (!$useConfigurationFromSku) {
            return false;
        }
        $productShade = $this->loadMyProduct($useConfigurationFromSku);
        if (!$productShade) {
            return false;
        }

        $modelShade = $this->rakdesignConfiguration->load($productShade->getId(), 'product_id');
        if ($modelShade->getId()) {
            return $modelShade->getCongifuration();
        }
        return false;
    }

    protected function getConfiguration() {
        $product = $this->getCurrentProduct();

        //check if product is configurable by checking the product attributes
        if (!$product->getData('combination_line_one') && !$product->getData('combination_line_one')) {
            return false;
        }
        $this->productType = $product->getData('combination_line_one');
        $model = $this->rakdesignConfiguration->load($product->getId(), 'product_id');
        if (!$model->getId()) {
            return $this->checkIfShadeIsConfigurable($product);
        }
        return $model->getCongifuration();
    }

    public function getShadesAndBaseJson() {
        return json_encode($this->getShadesAndBase());
    }

    public function getShadesAndBase() {
        $product = $this->getCurrentProduct();
        $configuration = $this->rakdesignConfiguratorLogic->getConfiguration($product);
        if (!$configuration) {
            return false;
        }
        if ($this->isJson($configuration)) {
            $configuration = json_decode($configuration, true);
            $shadesArray = array();
            foreach ($configuration as $key => $item) {
                if ($key == 0) {
                    //continue;
                }
                if ($key == 4) {
                    //break;
                }
                $keyNameShade = $item['combination_line_two']['name'];

                if (!file_exists($this->root . '/catalog/product' . $item['combination_line_two']['cover'])) {
                    $shadesArray['shade'][$keyNameShade]['image'] = $this->placeholder;
                } else {
                    $shadesArray['shade'][$keyNameShade]['image'] = $item['combination_line_two']['cover'];
                }
                $shadesArray['shade'][$keyNameShade]['name'] = $item['combination_line_two']['name'];

                $keyNameBase = $item['combination_line_one']['name'];
                if (!file_exists($this->root . '/catalog/product' . $item['combination_line_one']['cover'])) {
                    $shadesArray['base'][$keyNameBase]['image'] = $this->placeholder;
                } else {
                    $shadesArray['base'][$keyNameBase]['image'] = $item['combination_line_one']['cover'];
                }
                $shadesArray['base'][$keyNameBase]['name'] = $item['combination_line_one']['name'];
            }
//            echo '<pre>';
//            var_dump($shadesArray);
//            exit;
            // echo '<pre>'; var_dump($configuration); exit;
            return $shadesArray;
        }
        return false;
    }

    protected function getProductPrice($sku, $qty = 1) {
        if (isset($this->priceArray[$sku])) {
            return $this->priceArray[$sku] * (int) $qty;
        }
        $product = $this->loadMyProduct($sku);
        if (!$product) {
            return 0;
        }
        $this->priceArray[$sku] = (float) $product->getPrice();
        return (float) $product->getPrice() * (int) $qty;
    }


    protected function getRRPTransactionalPrice($sku, $qty = 1) {
        $vat=1.2;
        if (isset($this->transactionRRPArray[$sku])) {
            return $this->transactionRRPArray[$sku] * (int) $qty;
        }
        $product = $this->loadMyProduct($sku);
        if (!$product) {
            return 0;
        }
//        $rrpPrice=$this->transactionRRPHelper->getTransactionalRRPPrice($product);
        $rrpPrice=$product->getFinalPrice();
        $this->transactionRRPArray[$sku] = (float) $rrpPrice * $vat;
        return (float) $rrpPrice  * $vat * (int) $qty;
    }

    /*

     * check if product status enabled
     *
     *      */

    protected function checkIfIsSaleable($list) {
        // if is enable
        if (isset($list['combination_line_one']['store_status'][$this->currentStoreId]) &&
                (bool) $list['combination_line_one']['store_status'][$this->currentStoreId] &&
                isset($list['combination_line_two']['store_status'][$this->currentStoreId]) &&
                (bool) $list['combination_line_two']['store_status'][$this->currentStoreId]) {

            return true;
        }
        // if enabled is not there
        if (!isset($list['combination_line_one']['store_status'][$this->currentStoreId]) && !isset($list['combination_line_one']['store_status'][$this->currentStoreId])) {
            return true;
        }
        return false;
    }

    /*

     * function to put the shade to the first place
     *
     *
     *      */

    protected function shadeConfigurationOrder($array, $sku) {
        $return = array();
        foreach ($array as $key => $item) {
            if ($item['combination_line_two']['name'] == $sku) {
                $return[] = $item;
            }
        }
        foreach ($array as $key => $item) {
            if ($item['combination_line_two']['name'] != $sku) {
                $return[] = $item;
            }
        }
        return $return;
    }

    /* protected function stockAvailable($sku, $material = false) {
      //var_dump($this->stockAvailable);
      if (isset($this->stockAvailableArray[$sku])) {
      return $this->stockAvailableArray[$sku];
      }
      $product = $this->loadMyProduct($sku);
      if (!$product) {
      return '';
      }
      $qty = $this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
      $return = '';
      if ($qty <= 0) {
      $return = __('Out of Stock');
      } elseif ($qty >= 100) {
      $return = __('100+ Available');
      } else {
      $return = $qty . ' ' . __('Available');
      }
      if ($material) {
      $return .= ' ' . $material;
      }
      $this->stockAvailableArray[$sku] = ['formatted' => $return, 'stock' => $qty];
      return ['formatted' => $return, 'stock' => $qty];
      } */

    public function getSettings() {
        $arrowArgument = false;
        $product = $this->getCurrentProduct();
        $configuration = $this->rakdesignConfiguratorLogic->getConfiguration($product);

        if (!$configuration) {
            return false;
        }
        if ($this->isJson($configuration)) {
            $productInfo = $this->rakdesignConfiguratorLogic->getProductDetails($configuration);
            // echo '<pre>'; var_dump($productInfo); exit;
            $configuratorType = $this->rakdesignConfiguratorLogic->configuratorType($product);
            $configuration = json_decode($configuration, true);

            if ($configuratorType == 'shade') {
                //if this is a shade we need to reorder the configuration to put the shade to the first place
                $configuration = $this->shadeConfigurationOrder($configuration, $product->getSku());
                /*ar_dump($product->getData('combination_line_one')); exit;
                $arrowArgument=$product->getData('combination_line_one');*/
            }


            $attList = [];
            foreach ($configuration as $itemID => $configurationSingle) {
                foreach ($configurationSingle as $key => $item) {
                    //var_dump($item);
                    if ($key != 'image' && $item != '' && !in_array(trim($key), $attList)) {
                        $attList[] = trim($key);
                    }
                }
            }
            //var_dump($attList); exit;
            $tree = array();
            $tree_reverse = array();

            //echo '<pre>'; var_dump($configuration); exit;
            foreach ($configuration as $itemID => $list) {
                $tempKeys = [];
                $tempKeysreverse = [];
                if (isset($list['image']) && $list['image'] == '') {
                    continue;
                }
                if (!$this->checkIfIsSaleable($list)) {

                    continue; //remove this combination if it doesnt fit the buying restrictions
                }

                $price = false;
                $rrpexvat=false;
                if ($this->currentStoreId == 1) {
                    $list['combination_line_one']['rrp'] = number_format($this->getRRPTransactionalPrice($list['combination_line_one']['name'], $list['combination_line_one']['qty']),2);
                    $list['combination_line_two']['rrp'] = number_format($this->getRRPTransactionalPrice($list['combination_line_two']['name'], $list['combination_line_two']['qty']),2);

                }
                if ($this->currentStoreId == 2) {
                    $list['combination_line_one']['rrpexvat']= $list['combination_line_one']['rrp']/1.2;
                    $list['combination_line_two']['rrpexvat']= $list['combination_line_two']['rrp']/1.2;
                    $rrpexvat = number_format((float) $list['combination_line_one']['rrpexvat'] + (float) $list['combination_line_two']['rrpexvat'], 2);

                    $priceLineOne = $this->getProductPrice($list['combination_line_one']['name'], $list['combination_line_one']['qty']);
                    $priceLineTwo = $this->getProductPrice($list['combination_line_two']['name'], $list['combination_line_two']['qty']);
                    $list['combination_line_one']['price'] = number_format($priceLineOne, 2);
                    $list['combination_line_two']['price'] = number_format($priceLineTwo, 2);

                    $price = number_format($priceLineOne + $priceLineTwo, 2);

                    $list['combination_line_one']['stock_info'] = $productInfo[$list['combination_line_one']['name']]['stockAvailable'];
                    $list['combination_line_two']['stock_info'] = $productInfo[$list['combination_line_two']['name']]['stockAvailable'];

                } else {

                    //$list['combination_line_one']['stock_info'] = $this->stockAvailable($list['combination_line_one']['name']);
                    //$list['combination_line_two']['stock_info'] = $this->stockAvailable($list['combination_line_two']['name']);

                    $list['combination_line_one']['stock_info'] = $productInfo[$list['combination_line_one']['name']]['stockAvailable'];
                    $list['combination_line_two']['stock_info'] = $productInfo[$list['combination_line_two']['name']]['stockAvailable'];
                }

                $rrp = number_format((float) $list['combination_line_one']['rrp'] + (float) $list['combination_line_two']['rrp'], 2);

                /* ------cleanup to prevent displaying on frontend------------ */
                if (isset($list['combination_line_two']['buying_restriction_code'])) {
                    unset($list['combination_line_two']['buying_restriction_code']);
                }
                if (isset($list['combination_line_one']['buying_restriction_code'])) {
                    unset($list['combination_line_one']['buying_restriction_code']);
                }
                if (isset($list['combination_line_two']['xpallet'])) {
                    unset($list['combination_line_two']['xpallet']);
                }
                if (isset($list['combination_line_one']['xpallet'])) {
                    unset($list['combination_line_one']['xpallet']);
                }
                /* ---------------------------end-cleanup----------------------------- */


                foreach ($list as $attribute => $key) {
                    if ($key == '') {
                        continue;
                    }
                    if ($attribute != 'image') {
                        $tempKeys[] = trim($attribute) . '__' . $key['name'];
                        $tempKeysreverse[] = trim($attribute) . '__' . $key['name'];
                    } else {
                        $tempKeys[] = 'summary';
                        $tempKeysreverse = array_reverse($tempKeysreverse);
                        $tempKeysreverse[] = 'summary';
                    }
                }
                $tempArray = $this->flattenToMultiDimensional($tempKeys, array('base' => $list['combination_line_one'], 'shade' => $list['combination_line_two'], 'currency' => $this->currency, 'price' => $price, 'rrp' => $rrp, 'rrpexvat' => $rrpexvat, 'image' => $list['image'], 'combination_id' => $itemID));
                $tempArrayReverse = $this->flattenToMultiDimensional($tempKeysreverse, array('base' => $list['combination_line_one'], 'shade' => $list['combination_line_two'], 'currency' => $this->currency, 'price' => $price, 'rrp' => $rrp,'rrpexvat' => $rrpexvat, 'image' => $list['image'], 'combination_id' => $itemID));


                $tree_reverse = array_merge_recursive($tree_reverse, $tempArrayReverse);
                $tree = array_merge_recursive($tree, $tempArray);
            }

            return array('list' => $attList, 'tree' => $tree, 'tree_reverse' => $tree_reverse, 'arrow_argument' => $arrowArgument, 'product_sku' => $product->getSku(), 'configurator_type' => $configuratorType);
        }
        return false;
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}
