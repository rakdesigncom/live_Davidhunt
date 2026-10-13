<?php

namespace Fyb\Pimberly\Model;

class ShadeGenerator
{
    public const PRODUCT_DELIMETER = '|';

    public const QTY_DELIMETER = '*';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var string
     */
    protected $mediaRoot;

    /**
     * @var \Rakdesign\Configurator\Model\ConfiguratorFactory
     */
    protected $configuratorFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Rakdesign\Configurator\Model\ResourceModel\Configurator
     */
    protected $configuratorResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Action
     */
    protected $productAction;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $productShadeArray = [];

    protected $shadeOptionsForBaseQtyArray = [];

    protected $baseOptionsForShadeArray = [];

    protected $shadeOptionsForBaseArray = [];

    protected $associateConfigurationShadeToBase = [];

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Rakdesign\Configurator\Model\ConfiguratorFactory $configuratorFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Rakdesign\Configurator\Model\ResourceModel\Configurator $configuratorResource,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productAction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->mediaRoot = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
        $this->configuratorFactory = $configuratorFactory;
        $this->productRepository = $productRepository;
        $this->configuratorResource = $configuratorResource;
        $this->productAction = $productAction;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $products = $this->getProductCollection();

        $this->productShadeArray = [];
        $this->shadeOptionsForBaseQtyArray = [];
        $this->baseOptionsForShadeArray = [];
        $this->shadeOptionsForBaseArray = [];
        $this->associateConfigurationShadeToBase = [];

        $this->prepareBaseForShade($products);
        $this->prepareShadeForBase();
        $this->prepareShadeAssociations();

        $this->configuratorResource->getConnection()->truncateTable(
            $this->configuratorResource->getMainTable()
        );
        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {
                continue;
            }

            $combinations = $this->generateCombinations($product);

            $model = $this->configuratorFactory->create();
            $model->setName('name');
            $model->setProductId($product->getId());
            $model->setCongifuration(json_encode($combinations));
            $model->setCreated(date('Y-m-d H:i:s'));
            $model->setModified(date('Y-m-d H:i:s'));

            try {
                $model->save();
            } catch (\Exception $e) {
            }
        }

        $skuCollection = [];
        foreach ($this->associateConfigurationShadeToBase as $shade => $base) {
            $skuCollection[] = $shade;
        }

        if (!empty($skuCollection)) {
            $shadeProducts = $this->collectionFactory->create();
            $shadeProducts->addAttributeToSelect('*')
                ->addAttributeToFilter('sku', ['in' => $skuCollection]);

            foreach ($shadeProducts as $product) {
                if (isset($this->associateConfigurationShadeToBase[$product->getSku()])) {
                    try {
                        $this->productAction->updateAttributes([$product->getId()], ['combination_line_one' => $this->associateConfigurationShadeToBase[$product->getSku()]], 0);
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    protected function getProductCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('combination_line_two', ['notnull' => true]);

        return $collection;
    }

    protected function prepareBaseForShade($products)
    {
        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {
                continue;
            }
            $productShadeArray = [];
            $tempArray = explode(self::PRODUCT_DELIMETER, $product->getData('combination_line_two'));
            foreach ($tempArray as $singleItem) {
                $skuAndQty = $this->getSkuAndQty($singleItem);
                $productShadeArray[] = $skuAndQty['sku'];
                $this->shadeOptionsForBaseQtyArray[$product->getSku()][$this->getSkuAndQty($singleItem)['sku']] = $skuAndQty['qty'];
            }

            $this->productShadeArray[$product->getSku()] = implode(self::PRODUCT_DELIMETER, $productShadeArray);
        }

        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {
                continue;
            }
            $shades = explode(self::PRODUCT_DELIMETER, $product->getData('combination_line_two'));
            $productSku = $product->getSku();

            foreach ($shades as $itemShadeSku) {
                if (!isset($this->baseOptionsForShadeArray[$productSku])) {
                    $this->baseOptionsForShadeArray[$productSku][] = $productSku;
                }

                $this->baseOptionsForShadeArray[$productSku] = array_unique(array_merge($this->baseOptionsForShadeArray[$productSku], $this->findPattern($itemShadeSku)));
            }
        }
    }

    protected function isConfigrator($product)
    {
        return str_contains($product->getData('combination_line_two'), self::PRODUCT_DELIMETER);
    }

    protected function getSkuAndQty($skuAndQty)
    {
        if (str_contains($skuAndQty, self::QTY_DELIMETER)) {
            $skuAndQty = explode(self::QTY_DELIMETER, $skuAndQty);

            return ['sku' => $skuAndQty[0], 'qty' => $skuAndQty[1]];
        }

        return ['sku' => $skuAndQty, 'qty' => 1];
    }

    protected function findPattern($shadeSku)
    {
        $returnBaseSkuCodes = [];

        $shadeSku = $this->getSkuAndQty($shadeSku)['sku'];

        foreach ($this->productShadeArray as $baseSku => $item) {
            if (str_contains($item, $shadeSku . self::PRODUCT_DELIMETER)
                || str_contains($item, self::PRODUCT_DELIMETER . $shadeSku)
                || str_contains($item, $shadeSku . self::QTY_DELIMETER)
            ) {
                $returnBaseSkuCodes[] = $baseSku;
            }
        }

        return $returnBaseSkuCodes;
    }

    protected function prepareShadeForBase()
    {
        foreach ($this->baseOptionsForShadeArray as $baseSku => $items) {
            foreach ($items as $allBases) {
                if (!isset($this->shadeOptionsForBaseArray[$baseSku])) {
                    $this->shadeOptionsForBaseArray[$baseSku] = explode(
                        self::PRODUCT_DELIMETER, $this->productShadeArray[$allBases]
                    );
                } else {
                    $this->shadeOptionsForBaseArray[$baseSku] = array_unique(
                        array_merge(
                            $this->shadeOptionsForBaseArray[$baseSku],
                            explode(self::PRODUCT_DELIMETER, $this->productShadeArray[$allBases])
                        )
                    );

                    sort($this->shadeOptionsForBaseArray[$baseSku]);
                }
            }
        }
    }

    /**
     *  Function to search if a base has this certain Shade if yes it add to array
     *
     * @return void
     */
    protected function prepareShadeAssociations()
    {
        foreach ($this->shadeOptionsForBaseArray as $base => $shades) {
            foreach ($shades as $shadeitem) {
                if ($this->checkCanBeCombination($base, $shadeitem)) {
                    $this->associateConfigurationShadeToBase[$shadeitem] = $base;
                }
            }
        }
    }

    protected function checkCanBeCombination($baseSku, $shadeSku)
    {
        if (file_exists($this->mediaRoot . 'shade_configurator/' . $baseSku . ',' . $shadeSku . '.jpg')) {
            return true;
        }

        return false;
    }

    /**
     * function to check if the base and shade can be stored as combination
     *
     * @param $product
     *
     * @return array
     */
    protected function generateCombinations($product)
    {
        $attributesArray = [];
        // to check if the combination has a Root image and the product can be displayed as a shade configurator page
        $hasRootImage = false;

        $attributesArray['combination_line_one'] = $this->prepareDataArray($this->baseOptionsForShadeArray[$product->getSku()], $product->getSku());
        $attributesArray['combination_line_two'] = $this->prepareDataArray($this->shadeOptionsForBaseArray[$product->getSku()], $product->getSku());

        $combinations = $this->combinations($attributesArray);
        foreach ($combinations as $key => $item) {
            if (file_exists($this->mediaRoot . 'shade_configurator/' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg')) {
                $combinations[$key]['image'] = '/media/shade_configurator/' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg';
                if ($product->getSku() == $item['combination_line_one']['name']) {
                    $hasRootImage = true;
                }
            } else {
                unset($combinations[$key]);
                continue;
            }

            if ($item['combination_line_one']['name'] == 'RIM4246' && $item['combination_line_two']['name'] == 'CAN1029') {
                unset($combinations[$key]);
                continue;
            }

            $baseSku = $item['combination_line_one']['name'];
            $shadeSku = $item['combination_line_two']['name'];
            if (isset($this->shadeOptionsForBaseQtyArray[$baseSku][$shadeSku])) {
                $combinations[$key]['combination_line_two']['qty'] = (float)$this->shadeOptionsForBaseQtyArray[$baseSku][$shadeSku];
                $combinations[$key]['combination_line_two']['rrp'] = (float)$combinations[$key]['combination_line_two']['qty'] * (float)$item['combination_line_two']['rrp'];
            }
        }

        if (!$hasRootImage) {
            return [];
        }

        return $combinations;
    }

    /**
     * function to prepare array with Shades to associate with base configuration
     *
     * @param $dataArray
     * @param $productSku
     *
     * @return array
     */
    protected function prepareDataArray($dataArray, $productSku)
    {
        $returnArray = [];
        $stores = $this->storeManager->getStores();

        foreach ($dataArray as $item) {
            if ($item == '') {
                continue;
            }
            try {
                $product = $this->productRepository->get($item, true, 0, true);
            } catch (\Exception $e) {
                continue;
            }

            $qty = 1;
            $returnArray[$item]['id'] = $product->getId();
            $returnArray[$item]['name'] = $item;
            $returnArray[$item]['title'] = $product->getName();
            $returnArray[$item]['cover'] = $product->getData('thumbnail');
            $returnArray[$item]['price'] = $product->getPrice();
            $returnArray[$item]['rrp'] = (float)$product->getRrp() * $qty * 1.2;
            $returnArray[$item]['dimwidth'] = $product->getData('dimwidth');
            $returnArray[$item]['dimheight'] = $product->getData('dimheight');
            $returnArray[$item]['dimdepth'] = $product->getData('dimdepth');

            /* for filters */
            $returnArray[$item]['height'] = $product->getData('height');
            $returnArray[$item]['width'] = $product->getData('width');
            $returnArray[$item]['diameter'] = $product->getData('diameter');
            $returnArray[$item]['product_material'] = ''; //$product->getAttributeText('product_material');
            $returnArray[$item]['features'] = ''; //$product->getAttributeText('features');
            $returnArray[$item]['finish'] = ''; //$product->getAttributeText('appearance');
            /* end for filters */

            $returnArray[$item]['buying_restriction_code'] = $product->getData('buying_restriction_code');
            $returnArray[$item]['xpallet'] = !($product->getData('xpallet') == '0');

            $returnArray[$item]['dia'] = $product->getData('dia');
            $returnArray[$item]['measurements'] = $product->getData('measurements');
            $returnArray[$item]['shademeasurements'] = $product->getData('shademeasurements');

            $returnArray[$item]['material'] = $product->getData('material');

            $returnArray[$item]['qty'] = $qty;
            foreach ($stores as $store) {
                $storeId = $store->getId();
                $returnArray[$item]['store_status'][$storeId] = $product->getResource()->getAttributeRawValue($product->getId(), 'status', $storeId) == '1';
            }
        }

        return $returnArray;
    }

    /**
     * @param $arrays
     *
     * @return array
     */
    protected function combinations($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = $result_item + [$property => $property_value];
                }
            }
            $result = $tmp;
        }

        return $result;
    }
}
