<?php

namespace Rakdesign\Configurator\Block;

class shade extends \Magento\Framework\View\Element\Template {

    protected $_registry;
    protected $_customerSession;
    protected $_productCollectionFactory;
    protected $_rakdesignConfiguration;
    protected $_request;
    protected $_currency;
    protected $productRepository;
    protected $_storeManager;
    protected $currentStoreId;
    protected $customerBrands;
    protected $rakdesignConfiguratorLogic;
    protected $customerData;
    protected $priceArray;
    protected $transactionRRPArray;
//    protected $transactionRRPHelper;

    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Catalog\Model\ProductFactory $productCollectionFactory,
            \Rakdesign\Configurator\Model\Configurator $rakdesignConfiguration,
            \Magento\Customer\Model\Session $customerSession,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
            \Rakdesign\Configurator\Model\Shadelogic $rakdesignConfiguratorLogic,
//            \StarDigital\GeneralFunctions\Helper\CustomerData $customerData,
            \Magento\Framework\Registry $registry,
//            \Rakdesign\ManualProductprice\Helper\Data $transactionRRPHelper,
            array $data = []
    ) {
        $this->_registry = $registry;
        $this->_request = $request;
        $this->rakdesignConfiguratorLogic = $rakdesignConfiguratorLogic;
        $this->_currency = '£';
        $this->_customerSession = $customerSession;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_rakdesignConfiguration = $rakdesignConfiguration;
        $this->_storeManager = $storeManager;
        $this->productRepository = $productRepository;
//        $this->customerData = $customerData;
//        $this->transactionRRPHelper=$transactionRRPHelper;


        $this->priceArray=[];
        $this->transactionRRPArray= [];
        $this->currentStoreId = $this->_getWebsiteId();
        $this->customerBrands = $this->_getCustomerBrands();
        parent::__construct($context, $data);
    }

    protected function _getWebsiteId() {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function getStoreId() {
        return $this->currentStoreId;
    }

    /*

     *
     * function to get customer allowed selling brands for b2b
     *
     *  */

    protected function _getCustomerBrands() {
//        if ($this->currentStoreId == 2) {
//            $customer = $this->_customerSession->getCustomer();
//            $customerBrands = $customer->getResource()->getAttribute('allowed_selling_brand')->getFrontend()->getValue($customer);
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

    protected function getOptionType() {
        $option = false;
        $type = false;
        if ($this->_request->getParam('base')) {
            $option = 'combination_line_one';
            $type = 'base';
            $type = 'base';
        }
        if ($this->_request->getParam('shade')) {
            $option = 'combination_line_two';
            $type = 'shade';
        }
        return ['option' => $option, 'type' => $type];
    }

    public function getCollection() {
        $sku = false;
        $option = $this->getOptionType()['option'];
        $baseOrShade = false;
        if ($this->_request->getParam('base')) {
            $baseOrShade = $this->_request->getParam('base');
        }
        if ($this->_request->getParam('sku')) {
            $sku = $this->_request->getParam('sku');
        }
        if ($this->_request->getParam('shade')) {
            $baseOrShade = $this->_request->getParam('shade');
        }
        if (!$sku) {
            return false;
        }
        // $configuration = $this->getConfiguration($sku);
        $product = $this->loadMyProduct($sku);
        $configuration = $this->rakdesignConfiguratorLogic->getConfiguration($product);
        if ($configuration) {
            $configuration = json_decode($configuration, true);
            return $this->prepareCollection($configuration, $option, $baseOrShade);
        }
        return false;
    }

    public function getFilters($collection) {
        $filterType = $this->getOptionType()['type'];
        $filters = array();
        foreach ($collection as $item) {
            if (!isset($item['filters'][$filterType])) {
                continue;
            }
            foreach ($item['filters'][$filterType] as $key => $single) {
                if (!isset($filters[$key])) {
                    $filters[$key] = array();
                }
                if (is_array($single)) {
                    foreach ($single as $multipleItem) {
                        if (!in_array($multipleItem, $filters[$key])) {
                            $filters[$key][] = $multipleItem;
                        }
                    }
                    continue;
                }
                if (!in_array($single, $filters[$key])) {
                    $filters[$key][] = $single;
                }
            }
        }
        if (empty($filters)) {
            return false;
        }


        return $filters;
    }

    /*

     * get Permalink from the requested sku code from get param
     *

     */

    public function getPermalink() {
        $request = $this->getRequestType();
        if (!$request['sku']) {
            return false;
        }
        $product = $this->loadMyProduct($request['sku']);
        if (!$product) {
            return false;
        }
        return $product->getProductUrl();
    }

    protected function loadMyProduct($sku) {
        return $this->productRepository->get($sku);
    }

    protected function getProductPrice($sku, $qty = 1) {
        if (isset($this->priceArray[$sku])) {
            return $this->priceArray[$sku] * (int) $qty;
        }
        $product = $this->loadMyProduct($sku);
        if (!$product) {
            return 0;
        }
        $this->priceArray[$sku] = (float) $product->getFinalPrice();
        return (float) $product->getPrice() * (int) $qty;
    }

     protected function getRRPTransactionalPrice($sku, $qty = 1) {
        $vat= 1;
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

    public function getRequestType() {
        $sku = false;
        $type = false;
        if ($this->_request->getParam('sku')) {
            $sku = $this->_request->getParam('sku');
        }
        if ($this->_request->getParam('base')) {
            $sku = $this->_request->getParam('base');
            $type = 'base';
        }
        if ($this->_request->getParam('shade')) {
            $sku = $this->_request->getParam('shade');
            $type = 'shade';
        }
        return ['sku' => $sku, 'type' => $type];
    }

    public function getProduct() {
        $request = $this->getRequestType();
        if (!$request['sku']) {
            return false;
        }
        return $this->loadMyProduct($request['sku']);
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

    protected function prepareCollection($collection, $option, $baseOrshade) {

        $customerMode = false;
//        if ($this->currentStoreId == 2) {
//            $customerMode = (bool) $this->customerData->getCustomerSession()->getCustomerFriendly();
//        }
        $productInfo = $this->rakdesignConfiguratorLogic->getProductDetails(json_encode($collection));
        foreach ($collection as $key => $item) {
            if ($item[$option]['name'] != $baseOrshade) {
                unset($collection[$key]);
                continue;
            }

            $collection[$key]['combination_line_one']['stock_info'] = $productInfo[$collection[$key]['combination_line_one']['name']]['stockAvailable'];
            $collection[$key]['combination_line_two']['stock_info'] = $productInfo[$collection[$key]['combination_line_two']['name']]['stockAvailable'];

//            $collection[$key]['price'] = $this->_currency . number_format(
//                (float)$this->getProductPrice( $item['combination_line_one']['price'] + (float) $item['combination_line_two']['price'], 2);
            $item['combination_line_one']['price'] = number_format($this->getProductPrice($item['combination_line_one']['name'], $item['combination_line_one']['qty']), 2);
            $item['combination_line_two']['price'] = number_format($this->getProductPrice($item['combination_line_two']['name'], $item['combination_line_two']['qty']), 2);
            $collection[$key]['price'] = $this->_currency . number_format((float) $item['combination_line_one']['price'] + (float) $item['combination_line_two']['price'], 2);
//
            if (!$this->checkIfIsSaleable($item)) {
                unset($collection[$key]); //remove this combination if one product is disabled
                continue; //remove this combination if one product is disabled
            }

//            if ($this->currentStoreId == 1) {
//                $item['combination_line_one']['rrp'] = number_format($this->getRRPTransactionalPrice($item['combination_line_one']['name'], $item['combination_line_one']['qty']),2);
//                $item['combination_line_two']['rrp'] = number_format($this->getRRPTransactionalPrice($item['combination_line_two']['name'], $item['combination_line_two']['qty']),2);
//
//            }

//            if ($this->currentStoreId == 2) {
//                if (!$this->checkBuyingRestrictions($item)) {
//                    unset($collection[$key]); //remove this combination if it doesnt fit the buying restrictions
//                    continue; //remove this combination if it doesnt fit the buying restrictions
//                }
//                $item['combination_line_one']['price'] = number_format($this->getProductPrice($item['combination_line_one']['name'], $item['combination_line_one']['qty']), 2);
//                $item['combination_line_two']['price'] = number_format($this->getProductPrice($item['combination_line_two']['name'], $item['combination_line_two']['qty']), 2);
//                $collection[$key]['price'] = $this->_currency . number_format((float) $item['combination_line_one']['price'] + (float) $item['combination_line_two']['price'], 2);
//            }


            $collection[$key]['rrp'] = $this->_currency . number_format((float) $item['combination_line_one']['rrp'] + (float) $item['combination_line_two']['rrp'], 2);

            $collection[$key]['filters']['shade']['complete_price'] = number_format((float) $item['combination_line_one']['rrp'] + (float) $item['combination_line_two']['rrp'], 2);
            $collection[$key]['filters']['base']['complete_price'] = number_format((float) $item['combination_line_one']['rrp'] + (float) $item['combination_line_two']['rrp'], 2);
            if ($this->currentStoreId == 2) {
                if ($customerMode) {
                    $collection[$key]['filters']['shade']['complete_price'] = number_format((float) $item['combination_line_one']['rrp'] + (float) $item['combination_line_two']['rrp'], 2);
                    $collection[$key]['filters']['base']['complete_price'] = number_format((float) $item['combination_line_one']['rrp'] + (float) $item['combination_line_two']['rrp'], 2);
                } else {
                    $collection[$key]['filters']['shade']['complete_price'] = number_format((float) $item['combination_line_one']['price'] + (float) $item['combination_line_two']['price'], 2);
                    $collection[$key]['filters']['base']['complete_price'] = number_format((float) $item['combination_line_one']['price'] + (float) $item['combination_line_two']['price'], 2);
                }
            }

            // BASE filters
            $collection[$key]['filters']['base']['shade_finish'] = $item['combination_line_two']['finish'] ?? '';


            if ((float) $item['combination_line_two']['diameter'] > 0) {
                $collection[$key]['filters']['base']['shade_diameter'] = number_format((float) $item['combination_line_two']['diameter'], 0);
            }
            if ((float) $item['combination_line_two']['height'] > 0) {
                $collection[$key]['filters']['base']['shade_height'] = number_format((float) $item['combination_line_two']['height'], 0);
            }

            if ((float) $item['combination_line_two']['width'] > 0) {
                $collection[$key]['filters']['base']['shade_width'] = number_format((float) $item['combination_line_two']['width'], 0);
            }


            // SHADE filters
            if ($item['combination_line_one']['features']) {
                $collection[$key]['filters']['shade']['base_features'] = $item['combination_line_one']['features'];
            }
            if ($item['combination_line_one']['finish']) {
                $collection[$key]['filters']['shade']['base_finish'] = $item['combination_line_one']['finish'];
            }
            if ($item['combination_line_one']['product_material']) {
                $collection[$key]['filters']['shade']['base_material'] = $item['combination_line_one']['product_material'];
            }
            if ((float) $item['combination_line_one']['height'] > 0) {
                $collection[$key]['filters']['shade']['base_height'] = number_format((float) $item['combination_line_one']['height'], 0);
            }
            if ((float) $item['combination_line_one']['width'] > 0) {
                $collection[$key]['filters']['shade']['base_width'] = number_format((float) $item['combination_line_one']['width'], 0);
            }
            if ((float) $item['combination_line_one']['diameter'] > 0) {
                $collection[$key]['filters']['shade']['base_diameter'] = number_format((float) $item['combination_line_one']['diameter'], 0);
            }
        }

        return $collection;
    }

}
