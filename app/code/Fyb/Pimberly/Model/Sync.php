<?php

namespace Fyb\Pimberly\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;

class Sync
{
    protected $attributeMatch = [
        // default magento attributes
        'Primary ID' => 'sku',
        'Web Description' => 'name',
        'Internal Copy Text' => 'short_description',
        'Primary Copy Text' => 'description',
        'Country of Origin' => 'country_of_manufacture',
        'RRP (ex vat)' => 'price',
        'Product Weight' => 'weight',
        'Brand' => 'manufacturer',
        'WEB-Sale Price' => 'special_price',

        // DAR Attributes
        'Range Name' => 'shade_range_name',
        'Clearance' => 'clearance',
        'Selling Season' => 'selling_season',
        'Selling Year' => 'selling_year',
        'WEB-Product Type' => 'prod_type',
        'Product Type' => 'cat_product_type',
        'Primary Material' => 'primary_material',
        'Primary Material Details' => 'primary_material_details',
        'Primary Finish' => 'primary_finish',
        'Primary Colour' => 'primary_color',
        'Secondary Material' => 'secondary_material',
        'Secondary Material Details' => 'secondary_material_details',
        'Secondary Finish' => 'secondary_finish',
        'Secondary Colour' => 'secondary_color',
        'Hanging Orientation' => 'hanging_orientation',
        'Material Composition' => 'material_composition',
        'Our Palette' => 'our_palette',
        'Our Fabric' => 'our_fabric',
        'Availability' => 'delivery_text',
        'Shade Included' => 'shade_included',
        'Outer Shade Material' => 'outer_shadematerial',
        'Outer Shade Colour' => 'outer_shadecolour',
        'Shade Shape' => 'shade_shape',
        'Gimble Type' => 'gimble_type',
        'Inner Shade Material' => 'inner_shadematerial',
        'Inner Shade Colour' => 'inner_shadecolour',
        'Measurements' => 'measurements',
        'Base Measurements' => 'basemeasurements',
        'Shade Measurements' => 'shademeasurements',
        'Dimension With Shade' => 'dimension_shade',
        'Fixing Bracket Measurements' => 'fixint_bracket_measurements',
        'Min Height' => 'dar_min_height',
        'Max Height' => 'dar_max_height',
        'Dual Mount' => 'dual_mount',
        'Primary Bulb Detail' => 'primary_bulb_detail',
        'Recommended Lamp' => 'recommendedlamp',
        'Lamp Holder Type' => 'lamp_holder_type',
        'Bulb Type' => 'bulb_type',
        'No Of Lamps' => 'number_of_lamps',
        'Lamp Included' => 'lamp_included',
        'Primary Bulb Included Detail' => 'primary_bulb_included_detail',
        'Wattage' => 'total_watts',
        'Subject to Shade' => 'subject_to_shade',
        'Dimmable' => 'dimmable',
        'Lamp Replaceable' => 'lamp_replaceable',
        'Bluetooth' => 'has_bluetooth',
        'USB' => 'has_usb',
        'Motion Sensor' => 'motion_sensor',
        'Solar Powered' => 'solar_powered',
        'Batteries Required' => 'batteries_required',
        'Batteries Included' => 'batteries_included',
        'Switch Type' => 'switch_type',
        'Cable Colour' => 'cable_colour',
        'IP Rating' => 'iprating',
        'Class' => 'electrical_accessories',
        'Efficiency' => 'efficiency',
        'Lamplife' => 'lamplife',
        'Lumens' => 'lumens',
        'Colour Rendering' => 'colour_rendering',
        'Colour Temp' => 'colour_temp',
        'Secondary Bulb Detail' => 'secondary_bulb_detail',
        'SLS Recommended Lamp' => 'sls_recommendedlamp',
        'SLS Lamp Holder Type' => 'sls_lamp_holder_type',
        'SLS Bulb Type' => 'sls_bulb_type',
        'SLS No Of Lamps' => 'sls_number_of_lamps',
        'SLS Lamp Included' => 'sls_lamp_included',
        'Secondary Bulb Included Detail' => 'secondary_bulb_included_detail',
        'SLS Wattage' => 'sls_total_watts',
        'SLS Subject to Shade' => 'sls_subject_to_shade',
        'SLS Dimmable' => 'sls_dimmable',
        'SLS Lamp Replaceable' => 'sls_lamp_replaceable',
        'SLS Cable Colour' => 'sls_cable_colour',
        'SLS Efficiency' => 'sls_efficiency',
        'SLS Lamplife' => 'sls_lamplife',
        'SLS Lumens' => 'sls_lumens',
        'SLS Colour Rendering' => 'sls_colour_rendering',
        'SLS Colour Temp' => 'sls_colour_temp',
        'Inner Barcode' => 'inner_barcode',
        'Outer Barcode' => 'outer_barcode',
        'Qty In Export Carton' => 'qty_in_export_carton',
        'Selling Pack Size' => 'selling_pack_size',
        'Pallet Delivery' => 'pallet_delivery',
        'Pallet Type' => 'pallet_type',
        'Two Man Lift' => 'two_man_lift',
        'Primary & Secondary Appearance' => 'primary_secondary_appearance',
        'Product Style' => 'product_style',
        'Suitable For' => 'suitable',
        'Materials' => 'material',
        'Appearance' => 'appearance',
        'Icons' => 'icons',
        'Current Catalogue' => 'current_catalogue',
        'SLS Switch Type' => 'sls_switch_type',
        'Products sku codes for SHADE' => 'combination_line_two',
        'Web FAQs' => 'web_faqs',
        'DHL Web FAQs' => 'dhl_web_faqs',
        'Primary Associated Shade' => 'primary_associated_shade',
        'Associated Shades' => 'associated_shades',
        'Download Link' => 'downloadable_link',

        // In which stores should be
        'Display On DHL' => 'display_on_dhl_website',
        'Display On LSS' => 'display_on_lss_website',
        'DHL Website Visibility' => 'visibility_on_dhl_store',
        'LSS Website Visibility' => 'visibility_on_lss_store',

        // Enable/Disable Product
        'Magento Status' => 'status',

        // Store Categories
        'DHL Categorisation' => 'dhl_categories',
        'LSS Categorisation' => 'lss_categories',

        // Configurable Products
        'Variant Data' => 'configurable_product_data',

        // Customizable Products
//        '' => 'shade_range_type',
//        '' => 'shade_styles',
//        '' => 'shade_range_product',

        // Related/Upsell/Crossell Products
        'Related Products' => 'upsell_products',
        'Upsell Products' => 'crossell_products',

        // Images
        'Primary Image' => 'image',
        'Website Images 1' => 'image_1',
        'Website Images 2' => 'image_2',
        'Website Images 3' => 'image_3',
        'Website Images 4' => 'image_4',
        'Website Images 5' => 'image_5',
        'Website Images 6' => 'image_6',
        'Measurement Image' => 'measurement_image',
    ];

    protected $attributeType = [
        // default magento attributes
        'sku' => 'string',
        'name' => 'string',
        'short_description' => 'string',
        'description' => 'string',
        'country_of_manufacture' => 'string',
        'price' => 'string',
        'special_price' => 'string',
        'weight' => 'string',
        'manufacturer' => 'string',

        // DAR Attributes
        'shade_range_name' => 'string',
        'clearance' => 'string',
        'selling_season' => 'string',
        'selling_year' => 'string',
        'prod_type' => 'string',
        'cat_product_type' => 'string',
        'primary_material' => 'string',
        'primary_material_details' => 'string',
        'primary_finish' => 'string',
        'primary_color' => 'string',
        'secondary_material' => 'string',
        'secondary_material_details' => 'string',
        'secondary_finish' => 'string',
        'secondary_color' => 'string',
        'hanging_orientation' => 'string',
        'material_composition' => 'string',
        'our_palette' => 'string',
        'our_fabric' => 'string',
        'delivery_text' => 'string',
        'shade_included' => 'string',
        'outer_shadematerial' => 'string',
        'outer_shadecolour' => 'string',
        'shade_shape' => 'string',
        'gimble_type' => 'string',
        'inner_shadematerial' => 'string',
        'inner_shadecolour' => 'string',
        'measurements' => 'string',
        'basemeasurements' => 'string',
        'shademeasurements' => 'string',
        'dimension_shade' => 'string',
        'fixint_bracket_measurements' => 'string',
        'dar_min_height' => 'string',
        'dar_max_height' => 'string',
        'dual_mount' => 'string',
        'primary_bulb_detail' => 'string',
        'recommendedlamp' => 'string',
        'lamp_holder_type' => 'string',
        'bulb_type' => 'string',
        'number_of_lamps' => 'string',
        'lamp_included' => 'string',
        'primary_bulb_included_detail' => 'string',
        'total_watts' => 'string',
        'subject_to_shade' => 'string',
        'dimmable' => 'string',
        'lamp_replaceable' => 'string',
        'has_bluetooth' => 'string',
        'has_usb' => 'string',
        'motion_sensor' => 'string',
        'solar_powered' => 'string',
        'batteries_required' => 'string',
        'batteries_included' => 'string',
        'switch_type' => 'string',
        'cable_colour' => 'string',
        'iprating' => 'string',
        'electrical_accessories' => 'string',
        'efficiency' => 'string',
        'lamplife' => 'string',
        'lumens' => 'string',
        'colour_rendering' => 'string',
        'colour_temp' => 'string',
        'secondary_bulb_detail' => 'string',
        'sls_recommendedlamp' => 'string',
        'sls_lamp_holder_type' => 'string',
        'sls_bulb_type' => 'string',
        'sls_number_of_lamps' => 'string',
        'sls_lamp_included' => 'string',
        'secondary_bulb_included_detail' => 'string',
        'sls_total_watts' => 'string',
        'sls_subject_to_shade' => 'string',
        'sls_dimmable' => 'string',
        'sls_lamp_replaceable' => 'string',
        'sls_cable_colour' => 'string',
        'sls_efficiency' => 'string',
        'sls_lamplife' => 'string',
        'sls_lumens' => 'string',
        'sls_colour_rendering' => 'string',
        'sls_colour_temp' => 'string',
        'inner_barcode' => 'string',
        'outer_barcode' => 'string',
        'qty_in_export_carton' => 'string',
        'selling_pack_size' => 'string',
        'pallet_delivery' => 'string',
        'pallet_type' => 'string',
        'two_man_lift' => 'string',
        'primary_secondary_appearance' => 'string',
        'product_style' => 'array',
        'suitable' => 'string',
        'material' => 'string',
        'appearance' => 'string',
        'icons' => 'string',
        'current_catalogue' => 'string',
        'sls_switch_type' => 'string',
        'combination_line_two' => 'string',
        'web_faqs' => 'string',
        'dhl_web_faqs' => 'string',
        'primary_associated_shade' => 'string',
        'associated_shades' => 'string',
        'downloadable_link' => 'string',

        // In which stores should be
        'display_on_dhl_website' => 'string',
        'display_on_lss_website' => 'string',
        'visibility_on_dhl_store' => 'string',
        'visibility_on_lss_store' => 'string',

        // Enable/Disable Product
        'status' => 'string',

        // Store Categories
        'dhl_categories' => 'array',
        'lss_categories' => 'array',

        // Configurable Products
        'configurable_product_data' => 'array',

        // Related/Upsell/Crossell Products
        'upsell_products' => 'array',
        'crossell_products' => 'array',

        // Images
        'image' => 'array',
        'image_1' => 'array',
        'image_2' => 'array',
        'image_3' => 'array',
        'image_4' => 'array',
        'image_5' => 'array',
        'image_6' => 'array',
        'measurement_image' => 'array',
    ];

    protected $skippedAttributes = [
        'display_on_dhl_website',
        'special_price',
        'clearance',
        'display_on_lss_website',
        'visibility_on_dhl_store',
        'visibility_on_lss_store',
        'configurable_product_data',
        'dhl_categories',
        'lss_categories',
        'upsell_products',
        'crossell_products',
        'country_of_origin',
        'country_of_manufacture',
        'status',
        'image',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'measurement_image',
    ];

    protected $imageAttributes = [
        'image',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'measurement_image',
    ];

    protected $visibilityMapping = [
        'Not Visible Individually' => Visibility::VISIBILITY_NOT_VISIBLE,
        'Catalog' => Visibility::VISIBILITY_IN_CATALOG,
        'Search' => Visibility::VISIBILITY_IN_SEARCH,
        'Catalog, Search' => Visibility::VISIBILITY_BOTH,
    ];

    /**
     * @var \Fyb\Pimberly\Model\QueueRepository
     */
    protected $queueRepository;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $productAttributeRepository;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory
     */
    protected $attributeOptionInterfaceFactory;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $urlModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Action
     */
    protected $productAction;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurableType;

    /**
     * @var \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory
     */
    protected $productLinkInterfaceFactory;

    /**
     * @var \Magento\Swatches\Model\SwatchFactory
     */
    protected $swatchFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $mediaPath;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileReader;

    /**
     * @var \Magento\Catalog\Model\Product\Gallery\Processor
     */
    protected $galleryProcessor;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $websites = [];

    /**
     * @var array
     */
    protected $stores = [];

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var bool
     */
    protected $needShadeRegenerate = false;

    public function __construct(
        \Fyb\Pimberly\Model\QueueRepository $queueRepository,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $attributeOptionInterfaceFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Catalog\Model\Product\Url $urlModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productAction,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement,
        \Magento\Swatches\Model\SwatchFactory $swatchFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableType,
        \Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkInterfaceFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileReader,
        \Magento\Catalog\Model\Product\Gallery\Processor $galleryProcessor,
        \Magento\Framework\App\State $state,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        LoggerInterface $logger = null
    ) {
        $this->queueRepository = $queueRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->attributeOptionInterfaceFactory = $attributeOptionInterfaceFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->urlModel = $urlModel;
        $this->storeManager = $storeManager;
        $this->productAction = $productAction;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->swatchFactory = $swatchFactory;
        $this->configurableType = $configurableType;
        $this->productLinkInterfaceFactory = $productLinkInterfaceFactory;
        $this->filesystem = $filesystem;
        $this->mediaPath = $filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $this->fileReader = $fileReader;
        $this->galleryProcessor = $galleryProcessor;
        $this->state = $state;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute()
    {
        $queue = $this->queueRepository->getActiveItems();
        $allProducts = [];
        $configurableProducts = [];
        $errors = [];
        $this->needShadeRegenerate = false;

        $this->logger->info('PIM: Start Sync ' . count($queue) . ' product(s)');
        $i = 0;
        foreach ($queue as $queueItem) {
            $data = $this->jsonSerializer->unserialize($queueItem->getData('data'));
            $sku = $queueItem->getData('sku');

            try {
                $row = $this->processProduct($sku, $data);
                $row['queue'] = $queueItem;
                if ($row['configurable_product_data']) {
                    $configurableProducts[$sku] = $row;
                }

                $this->updateProductCategories($sku, $row);

                $allProducts[$sku] = $row;
                $i++;
                if ($i > 100) {
                    $i = 0;
                    sleep(3);
                }
            } catch (\Exception $e) {
                $this->logger->error('PIM: Error to sync product ' . $sku . ': ' . $e->getMessage());
                $errors[] = $sku . ' Error to sync product: ' . $e->getMessage();

                $queueItem->setIsSynced(0)->setError($e->getMessage())->setSyncedAt(date('Y-m-d H:i:s'));
                $this->queueRepository->save($queueItem);
                unset($allProducts[$sku]);
            }
        }

        sleep(5);

        $i = 0;
        foreach ($configurableProducts as $sku => $configurableProduct) {
            $queueItem = $configurableProduct['queue'];

            try {
                $this->processConfigurableProduct($sku, $configurableProduct);

                $i++;
                if ($i > 100) {
                    $i = 0;
                    sleep(3);
                }
            } catch (\Exception $e) {
                $this->logger->error('PIM: Error to sync configurable product ' . $sku . ': ' . $e->getMessage());
                $errors[] = $sku . ' Error to sync configurable product: ' . $e->getMessage();

                unset($allProducts[$sku]);
                $queueItem->setIsSynced(0)->setError($e->getMessage())->setSyncedAt(date('Y-m-d H:i:s'));
                $this->queueRepository->save($queueItem);
            }
        }
        unset($configurableProducts);

        sleep(5);

        $i = 0;
        foreach ($allProducts as $sku => $product) {
            $queueItem = $product['queue'];
            try {
                $this->processImages($sku, $product);
                $this->processLinkedProducts($sku, $product);

                $queueItem->setIsSynced(1)->setError(null)->setSyncedAt(date('Y-m-d H:i:s'));
                $this->queueRepository->save($queueItem);

                $i++;
                if ($i > 100) {
                    $i = 0;
                    sleep(3);
                }
            } catch (\Exception $e) {
                $this->logger->error('PIM: Error to sync images product ' . $sku . ': ' . $e->getMessage());
                $errors[] = $sku . ' Error to sync images product: ' . $e->getMessage();

                unset($allProducts[$sku]);
                $queueItem->setIsSynced(0)->setError($e->getMessage())->setSyncedAt(date('Y-m-d H:i:s'));
                $this->queueRepository->save($queueItem);
            }
        }
        $this->logger->info('PIM: Finish Success Sync ' . count($allProducts) . ' product(s)');

        unset($allProducts);

        if ($errors) {
            $this->sendErrorEmail($errors);
        }

        return $this->needShadeRegenerate;
    }

    public function sendErrorEmail($errors)
    {
        $errors = implode("<br>", $errors);

        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('pim_error')
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars(['error_text' => $errors])
                ->setFromByScope([
                    'email' => 'website@davidhuntlighting.co.uk',
                    'name' => 'admin',
                ])
                ->addTo('sandbox@fyb.ro', 'Fyb Romania')
                ->addBcc('daniel@fyb.ro')
                ->addBcc('mircea@fyb.ro')
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('PIM: Error to send error email: ' . $e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @param string $sku
     * @param array $data
     *
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processProduct($sku, $data)
    {
        $row = [];

        $product = $this->getProduct($sku, true);

        $isCatalogue = false;
        foreach ($data as $key => $value) {
            $attribute = $this->attributeMatch[$key] ?? null;
            if (!$attribute) {
                continue;
            }

            $attributeValue = $this->prepareAttribute($attribute, $value);
            if ($attribute == 'cat_product_type' && strtoupper($attributeValue) === 'MARKETING SUPPORT') {
                $isCatalogue = true;
            }

            $attributeValue = $this->prepareAndCreate($attribute, $attributeValue);
            $row[$attribute] = $attributeValue;

            $product->setData($attribute, $attributeValue);
        }

        // special_price is only kept when the product is flagged as Clearance (true) AND a
        // sale price was provided in this run; otherwise clear it so stale sale prices don't linger.
        if (($row['clearance'] ?? null) !== true || ($row['special_price'] ?? null) === null) {
            $row['special_price'] = null;
            $product->setData('special_price', null);
        }

        $row['is_catalogue'] = $isCatalogue;

        if (!$product->getName()) {
            $product->setName($sku);
        }

        $product->setMetaTitle($product->getName());
        $product->setMetaDescription($product->getName());
        $product->setUrlKey(
            $this->urlModel->formatUrlKey($product->getName() . '-' . $product->getSku())
        );
        $product->setWebsiteIds($this->getWebsitesToDisplay($row));

        $product = $this->productRepository->save($product);
        if ($product->getData('combination_line_one') || $product->getData('combination_line_two')) {
            $this->needShadeRegenerate = true;
        }

        if (!$isCatalogue) {
            foreach ($this->getAllStores() as $storeCode => $storeId) {
                $storeKey = 'visibility_on_' . $storeCode . '_store';
                if (isset($row[$storeKey]) && $row[$storeKey]) {
                    $this->productAction->updateAttributes([$product->getId()], ['visibility' => $row[$storeKey]], $storeId);
                }
            }
        }

        return $row;
    }

    /**
     * @param string $sku
     * @param bool $create
     *
     * @return \Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProduct($sku, $create = true)
    {
        try {
            $product = $this->productRepository->get($sku, true, 0, true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            if ($create) {
                $product = $this->productFactory->create();
                $product->setAttributeSetId('4');
                $product->setSku($sku);
                $product->setStoreId(0);
                $product->setTypeId('simple');
                $product->setPrice(1);
                $product->setQuantityAndStockStatus(['qty' => 999, 'is_in_stock' => 1]);
            } else {
                throw new LocalizedException(__($e->getMessage()));
            }
        }

        return $product;
    }

    protected function validateAttributeType($attribute, $value)
    {
        $attributeType = $this->attributeType[$attribute] ?? null;
        if (!$attributeType) {
            return $value;
        }

        switch ($attributeType) {
            case 'string':
                if (is_array($value)) {
                    $value = $value[0] ?? null;
                }
                break;
            case 'array':
                if (!is_array($value)) {
                    $value = $value ? [$value]: [];
                }
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     *
     * @return mixed
     */
    protected function prepareAttribute($attribute, $value)
    {
        $value = $this->validateAttributeType($attribute, $value);
        if ($value === null) {
            return $value;
        }

        if (!is_array($value)) {
            $value = str_replace('Ã', 'Ø', trim($value));
            if (strtolower($value) == 'n/a') {
                return null;
            }
        }

        switch ($attribute) {
            case 'inner_shadematerial':
            case 'inner_shadecolour':
            case 'outer_shadematerial':
                if (is_array($value)) {
                    $value = $value[0] ?? null;
                }
                break;
            case 'product_style':
                $value = $value[0] ?? null;
                break;
            case 'shade_range_name':
                $value = ucwords(strtolower($value));
                break;
            case 'iprating':
                $value = preg_replace('/^IP/', '', $value);
                break;
            case 'status':
                $value = strtolower($value) == 'enable' ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;
                break;
            case 'visibility_on_dhl_store':
            case 'visibility_on_lss_store':
                $value = $this->visibilityMapping[$value] ?? null;
                break;
            case 'display_on_lss_website':
            case 'display_on_dhl_website':
            case 'our_fabric':
            case 'our_palette':
            case 'pallet_delivery':
            case 'clearance':
                $value = (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                break;
            case 'description':
                if ($value) {
                    $newValue = [];
                    $value = explode('|', $value);
                    foreach ($value as $dd) {
                        if ($dd) {
                            $newValue[] = trim($dd);
                        }
                    }

                    if ($newValue) {
                        $newValue = implode(' | ', $newValue);
                        $value = $newValue;
                    } else {
                        $value = null;
                    }
                } else {
                    $value = null;
                }
                break;
            case 'dhl_web_faqs':
                $value = implode(' | ', array_map('trim',
                    array_filter(explode('|', $value))
                ));
                break;
            case 'electrical_accessories':
                if ($value == '1 - Earthed') {
                    $value = 'Class I, Earthed';
                }
                if ($value == '2 - Double Insulated') {
                    $value = 'Class II, Double Insulated';
                }
                if ($value == '3 - Low Voltage') {
                    $value = 'Class III, Low Voltage';
                }
                if (strtolower($value) == 'n/a') {
                    $value = null;
                }
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareAndCreate($attribute, $value)
    {
        if (in_array($attribute, $this->skippedAttributes)) {
            return $value;
        }

        $attributeModel = $this->productAttributeRepository->get($attribute);

        if ($attributeModel->getFrontendInput() === 'boolean') {
            $value = strtolower((string)$value) == 'yes' || strtolower((string)$value) == 'y' || $value == 1 || $value === true;
        }

        if ($attributeModel->getFrontendInput() == 'select' && $value && $attribute != 'country_of_origin' && $attribute != 'country_of_manufacture') {
            $allOptions = $attributeModel->getSource()->getAllOptions();
            $existingOptions = array_filter(array_column($allOptions, 'label'));
            $exists = false;
            foreach ($existingOptions as $existingOption) {
                if (strtolower($value) == strtolower($existingOption)) {
                    $exists = true;
                    $value = $existingOption;
                    break;
                }
            }

            if (!$exists) {
                $optionId = $this->createOptionForAttribute($value, $attributeModel->getAttributeId());
            } else {
                $optionId = $attributeModel->getSource()->getOptionId(trim($value));
            }

            $value = $optionId;
        }

        if ($attributeModel->getFrontendInput() == 'multiselect' && $value) {
            if (!is_array($value)) {
                if (str_contains($value, '|')) {
                    $value = explode('|', $value);
                } else {
                    $value = explode('&', $value);
                }

                $value = array_filter(array_map('trim', $value));
            }
            $allOptions = $attributeModel->getSource()->getAllOptions();
            $existingOptions = array_filter(array_column($allOptions, 'label'));

            $newField = [];
            foreach ($value as $valField) {
                $valField = trim($valField);
                if (!$valField) {
                    continue;
                }

                $exists = false;
                foreach ($existingOptions as $existingOption) {
                    if (strtolower($valField) == strtolower($existingOption)) {
                        $exists = true;
                        $valField = $existingOption;
                        break;
                    }
                }

                if (!$exists) {
                    $optionId = $this->createOptionForAttribute($valField, $attributeModel->getAttributeId());
                } else {
                    $optionId = $attributeModel->getSource()->getOptionId($valField);
                }

                $newField[$optionId] = $optionId;
            }

            $value = array_values($newField);
        }

        return $value;
    }

    /**
     * @param string $value
     * @param int $attributeId
     *
     * @return string
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function createOptionForAttribute($value, $attributeId)
    {
        $optionNew = $this->attributeOptionInterfaceFactory->create();
        $optionNew->setLabel($value);
        $optionNew->setSortOrder(0);
        $optionNew->setIsDefault(false);

        $optionId = $this->attributeOptionManagement->add(
            \Magento\Catalog\Model\Product::ENTITY,
            $attributeId,
            $optionNew
        );

        return $optionId;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function getWebsitesToDisplay($row)
    {
        $websiteIds = [];
        foreach ($this->getAllWebsites() as $websiteCode => $websiteId) {
            $websiteKey = 'display_on_' . $websiteCode;
            if (isset($row[$websiteKey]) && $row[$websiteKey]) {
                $websiteIds[] = $websiteId;
            }
        }

        return $websiteIds;
    }

    /**
     * @return array
     */
    protected function getAllWebsites()
    {
        if (!$this->websites) {
            foreach ($this->storeManager->getWebsites() as $website) {
                $this->websites[$website->getCode()] = $website->getId();
            }
        }

        return $this->websites;
    }

    /**
     * @return array
     */
    protected function getAllStores()
    {
        if (!$this->stores) {
            foreach ($this->storeManager->getStores() as $store) {
                $this->stores[$store->getCode()] = $store->getId();
            }
        }

        return $this->stores;
    }

    /**
     * @param string $sku
     * @param array $row
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateProductCategories($sku, $row)
    {
        $categoryIds = [];

        foreach ($this->getAllStores() as $storeCode => $storeId) {
            $storeKey = $storeCode . '_categories';
            if (isset($row[$storeKey])) {
                $storeCategories = $this->getStoreCategories($storeId);
                $categoriesFromData = $row[$storeKey];

                foreach ($categoriesFromData as $categoryFromData) {
                    $categoryFromData = trim(preg_replace('/^default/i', '', $categoryFromData), '/');
                    $categoryFromData = trim(preg_replace('/^products/i', '', $categoryFromData), '/');
                    $categoryFromData = str_replace([' and ', 'The Collections'], [' & ', 'Collections'], $categoryFromData);
                    $categoryFromData = preg_replace('/\/all/i', '', $categoryFromData);

                    foreach ($storeCategories as $storeCategoryId => $storeCategoryPath) {
                        if (strtolower($storeCategoryPath) == strtolower($categoryFromData)) {
                            $categoryIds[$storeCategoryId] = $storeCategoryId;
                            break;
                        }
                    }
                }
            }
        }

        $categoryIds = array_values(array_unique($categoryIds));

        $this->categoryLinkManagement->assignProductToCategories(
            $sku,
            $categoryIds
        );
    }

    /**
     * @param int $storeId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreCategories($storeId)
    {
        if (isset($this->categories[$storeId])) {
            return $this->categories[$storeId];
        }

        $store = $this->storeManager->getStore($storeId);
        $rootCategoryId = $store->getRootCategoryId();

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"])
            ->addIsActiveFilter()
            ->setStore($store->getId());

        $categoriesWithPath = [];
        foreach ($collection as $category) {
            $pathIds = explode("/", $category->getPath());

            $pathName = [];
            foreach ($pathIds as $pathId) {
                $categoryFromPath = $this->categoryFactory->create()->load($pathId);
                $pathName[] = $categoryFromPath->getName();
            }

            unset($pathName[0], $pathName[1]);

            $pathName = preg_replace('/^Categories\//', '', implode('/', $pathName));
            $pathName = preg_replace('/^Bespoke Shades & Lights\//', '', $pathName);
            if ($pathName) {
                $categoriesWithPath[$category->getId()] = $pathName;
            }
        }

        $this->categories[$storeId] = $categoriesWithPath;

        return $this->categories[$storeId];
    }

    /**
     * @param string $sku
     * @param array $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processConfigurableProduct($sku, $data)
    {
        $simpleProducts = array_map(function ($item) {
            return str_replace(';', ',', $item);
        }, $data['configurable_product_data']);

        if (!$simpleProducts) {
            return;
        }

        $product = $this->getProduct($sku, false);
        $attributeCode = 'finishes';
        $configurableProductOptions = [];
        $associatedProductIds = [];

        foreach ($simpleProducts as $keySimpleProduct => $simpleProduct) {
            $simpleProduct = explode(',', $simpleProduct);
            foreach ($simpleProduct as $key => $value) {
                $value = explode('=', $value);
                $configurableProductOptions[$keySimpleProduct][trim(strtoupper($value[0]))] = trim($value[1]);
            }
        }

        $attributeModel = $this->productAttributeRepository->get($attributeCode);
        foreach ($configurableProductOptions as $configurableProductOption) {
            $optionToSearch = $configurableProductOption['COLOUR'];
            if (!$optionToSearch || !$configurableProductOption['HEX CODE']) {
                continue;
            }

            $allOptions = $attributeModel->getSource()->getAllOptions();
            $existingOptions = array_filter(array_column($allOptions, 'label'));
            if (!in_array($optionToSearch, $existingOptions)) {
                $optionId = $this->createSwatchOptionForAttribute(
                    $optionToSearch,
                    '#' . $configurableProductOption['HEX CODE'],
                    $attributeModel->getAttributeId()
                );
            } else {
                $optionId = $attributeModel->getSource()->getOptionId($optionToSearch);
            }

            try {
                $productToAdd = $this->getProduct($configurableProductOption['SKU'], false);
            } catch (\Exception $e) {
                continue;
            }

            $productToAdd->addData([$attributeCode => $optionId]);
            $this->productRepository->save($productToAdd);

            $associatedProductIds[] = $productToAdd->getId();
        }

        if (!$associatedProductIds) {
            return;
        }

        $product->setTypeId("configurable");
        $product->setAffectConfigurableProductAttributes(4);
        $this->configurableType->setUsedProductAttributeIds([$attributeModel->getId()], $product);
        $product->setNewVariationsAttributeSetId(4);
        $product->setAssociatedProductIds($associatedProductIds);
        $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        $product->setConfigurableAttributesData($configurableAttributesData);
        $product->setCanSaveConfigurableAttributes(true);

        $product->save();
    }

    /**
     * @param string $label
     * @param string $swatch
     * @param int $attributeId
     *
     * @return string
     * @throws \Exception
     */
    protected function createSwatchOptionForAttribute($label, $swatch, $attributeId)
    {
        $optionId = $this->createOptionForAttribute($label, $attributeId);
        $swatchNew = $this->swatchFactory->create();
        $swatchNew->setData([
            'option_id' => $optionId,
            'type' => 1,
            'value' => $swatch,
        ]);
        $swatchNew->save();

        return $optionId;
    }

    /**
     * @param string $sku
     * @param array $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processImages($sku, $data)
    {
        $product = $this->getProduct($sku, false);
        $images = $product->getMediaGalleryImages();
        foreach ($images as $child) {
            $this->galleryProcessor->removeImage($product, $child->getFile());
        }
        $product->setMediaGalleryEntries([]);
        $this->productRepository->save($product);

        $images = [];
        $mainImageImported = false;
        $thumbnailImported = false;
        $isCatalogue = $data['is_catalogue'];

        foreach ($this->imageAttributes as $attribute) {
            $imageUrl = $data[$attribute] ?? [];
            $isMeasurement = $attribute == 'measurement_image';
            if (is_array($imageUrl)) {
                $imageUrl = $imageUrl[0] ?? null;
            }

            if (!empty($imageUrl) && !in_array($imageUrl, $images)) {
                $images[$attribute] = $imageUrl;
                $productImage = $this->uploadImage($imageUrl, $product->getSku(), $isMeasurement);

                if (!$isMeasurement) {
                    $attr = null;
                    if (!$mainImageImported) {
                        $attr = ['image', 'small_image'];
                        if (!$isCatalogue) {
                            $attr[] = 'thumbnail';
                        }

                        $mainImageImported = true;
                    } else {
                        if ($isCatalogue && !$thumbnailImported) {
                            $attr = ['thumbnail'];
                            $thumbnailImported = true;
                        }
                    }

                    $product->addImageToMediaGallery($productImage, $attr, true, false);
                }
            }
        }

        $productRepository = $this->productRepository;
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            function () use ($productRepository, $product) {
                $productRepository->save($product);
            }
        );
    }

    /**
     * Upload image from external url
     *
     * @param string $imageUrl
     * @param string $sku
     * @param bool $isMeasurement
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function uploadImage($imageUrl, $sku, $isMeasurement = false)
    {
        $imagePathInfo = pathinfo(parse_url($imageUrl)["path"]);
        $filename = $sku . '_' . uniqid('', false) . "." . $imagePathInfo["extension"];
        if ($isMeasurement) {
            $filename = $sku . "_M." . $imagePathInfo["extension"];
        }
        $this->fileReader->checkAndCreateFolder($this->mediaPath . 'wysiwyg/tmp/');
        $filepath = $this->mediaPath . 'wysiwyg/tmp/' . $filename;
        if ($isMeasurement) {
            $filepath = $this->mediaPath . 'catalog/product_measurements/' . $filename;
        }

        $result = $this->fileReader->read($imageUrl, $filepath);

        return $filepath;
    }

    /**
     * @param string $sku
     * @param array $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function processLinkedProducts($sku, $data)
    {
        $product = $this->getProduct($sku, false);
        $linkData = [];

        $upsellProducts = ($data['upsell_products'] ?? []) ?: [];
        $crossellProducts = ($data['crossell_products'] ?? []) ?: [];
        foreach ($upsellProducts as $index => $upsellProduct) {
            try {
                $upsellProduct = $this->getProduct($upsellProduct, false);
            } catch (\Exception $e) {
                continue;
            }

            $productLink = $this->productLinkInterfaceFactory->create();
            $linkData[] = $productLink->setSku($product->getSku())
                ->setLinkedProductSku($upsellProduct->getSku())
                ->setPosition($index)
                ->setLinkType('upsell');
        }

        foreach ($crossellProducts as $index => $crossellProduct) {
            try {
                $crossellProduct = $this->getProduct($crossellProduct, false);
            } catch (\Exception $e) {
                continue;
            }

            $productLink = $this->productLinkInterfaceFactory->create();
            $linkData[] = $productLink->setSku($product->getSku())
                ->setLinkedProductSku($crossellProduct->getSku())
                ->setPosition($index)
                ->setLinkType('crosssell');
        }

        $product->setProductLinks($linkData);
        $this->productRepository->save($product);
    }
}
