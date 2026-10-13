<?php

namespace Fyb\Shipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class DarCarrier extends AbstractCarrier implements CarrierInterface
{
    /**
     * @inhertdoc
     */
    protected $_code = 'dar';

    /**
     * @inhertdoc
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $freeMethod = [
        'carrier_title' => 'Standard',
        'method_code' => 'freeshipping',
        'method_title' => 'Free of Charge',
    ];

    protected $additionalMethods = [
        [
            'carrier_title' => 'Standard',
            'method_code' => 'standard',
            'method_title' => '2-3 days',
        ],
        [
            'carrier_title' => 'Overnight',
            'method_code' => 'overnight',
            'method_title' => 'Next Day',
        ],
    ];

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();

        $this->_updateFreeMethodQuote($request);
        if ($request->getFreeShipping() || $this->isFreeShippingRequired($request)) {
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->freeMethod['carrier_title']);

            $method->setMethod($this->freeMethod['method_code']);
            $method->setMethodTitle($this->freeMethod['method_title']);

            $method->setPrice('0.00');
            $method->setCost('0.00');

            $result->append($method);
        } else {
            foreach ($this->additionalMethods as $additionalMethod) {
                $method = $this->_rateMethodFactory->create();

                $method->setCarrier($this->_code);
                $method->setCarrierTitle($additionalMethod['carrier_title']);

                $method->setMethod($additionalMethod['method_code']);
                $method->setMethodTitle($additionalMethod['method_title']);

                $price = $this->getConfigData('shipping_cost_' . $additionalMethod['method_code']);
                $method->setPrice($price);
                $method->setCost($price);

                $result->append($method);
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function _updateFreeMethodQuote($request)
    {
        $freeShipping = false;
        $items = $request->getAllItems();
        $c = count($items);
        for ($i = 0; $i < $c; $i++) {
            if ($items[$i]->getProduct() instanceof \Magento\Catalog\Model\Product) {
                if ($items[$i]->getFreeShipping()) {
                    $freeShipping = true;
                } else {
                    return;
                }
            }
        }
        if ($freeShipping) {
            $request->setFreeShipping(true);
        }
    }

    /**
     * Check subtotal for allowed free shipping
     *
     * @param RateRequest $request
     *
     * @return bool
     */
    private function isFreeShippingRequired(RateRequest $request): bool
    {
        $minSubtotal = $request->getPackageValueWithDiscount();
        if ($request->getBaseSubtotalWithDiscountInclTax()
            && $this->getConfigFlag('tax_including')) {
            $minSubtotal = $request->getBaseSubtotalWithDiscountInclTax();
        }

        $isFree = $minSubtotal >= $this->getConfigData('free_shipping_subtotal');
        if ($isFree) {
            return true;
        }

        $isFree = true;
        $freeCategory = $this->getFreeCategoryId($request);
        foreach ($request->getAllItems() as $item) {
            $product = $this->productRepository->getById($item->getProduct()->getId());

            if (!in_array($freeCategory, $product->getCategoryIds())) {
                $isFree = false;
                break;
            }
        }

        return $isFree;
    }

    protected function getFreeCategoryId(RateRequest $request)
    {
        $rootCat = $this->categoryFactory->create()->load($this->storeManager->getStore()->getRootCategoryId());
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name', 'Catalogues')
            ->addPathFilter($rootCat->getPath())
            ->setStore($request->getStoreId())
            ->setPageSize(1);

        return $collection->getFirstItem()->getId();
    }

    public function getAllowedMethods()
    {
        return [
            $this->freeMethod['method_code'] => $this->freeMethod['method_title'],
            $this->additionalMethods[0]['method_code'] => $this->additionalMethods[0]['method_title'],
            $this->additionalMethods[1]['method_code'] => $this->additionalMethods[1]['method_title'],
        ];
    }
}
