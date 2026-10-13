<?php

ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;

$baseDir = dirname(__FILE__, 4);

require $baseDir . '/app/bootstrap.php';

$params = $_SERVER;
$params[Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = [
    DirectoryList::PUB => [DirectoryList::URL_PATH => ''],
    DirectoryList::MEDIA => [DirectoryList::URL_PATH => 'media'],
    DirectoryList::STATIC_VIEW => [DirectoryList::URL_PATH => 'static'],
    DirectoryList::UPLOAD => [DirectoryList::URL_PATH => 'media/upload'],
];
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);

$obj = $bootstrap->getObjectManager();
$obj->get('\Magento\Framework\App\State')->setAreaCode(
    \Magento\Framework\App\Area::AREA_GLOBAL
); // for remove Area code is not set error
$storeManager = $obj->get('\Magento\Store\Model\StoreManagerInterface');
$csvReader = $obj->get('\Magento\Framework\File\Csv');
$productRepository = $obj->get('Magento\Catalog\Model\ProductRepository');
$productFactory = $obj->get('Magento\Catalog\Model\ProductFactory');
$productAttributeRepository = $obj->get('\Magento\Catalog\Api\ProductAttributeRepositoryInterface');
$attributeOptionManagement = $obj->get('\Magento\Eav\Api\AttributeOptionManagementInterface');

$csvFile = $baseDir . '/scripts/dar/final/csv/DAR_ATTRIBUTES_NEW.csv';
$csvData = $csvReader->getData($csvFile);
$header = $csvData[0];

$eavSetupFactory = $obj->get(\Magento\Eav\Setup\EavSetupFactory::class);
$setup = $obj->get(\Magento\Framework\Setup\ModuleDataSetupInterface::class);
$eavSetup = $eavSetupFactory->create(['setup' => $setup]);

unset($csvData[0]);
$storeId = 1;

$setup->startSetup();

function updateAttribute($attribute, $rowData) {
    global $eavSetup;
    /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
    $inputType = $attribute->getFrontendInput();


    $usedInProductListening = filter_var($rowData['Visible on Catalog Pages on Storefront'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $visibleOnFront = filter_var($rowData['Used in Product Listing'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $useInFiler = filter_var($rowData['Use in Filter'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if (!in_array($inputType, ['select', 'boolean', 'multiselect'])) {
        $useInFiler = false;
    }
    $eavSetup->updateAttribute(
        $attribute->getEntityTypeId(),
        $attribute->getAttributeId() ,
        'used_in_product_listing',
        $usedInProductListening
    );
    $eavSetup->updateAttribute(
        $attribute->getEntityTypeId(),
        $attribute->getAttributeId() ,
        'is_visible_on_front',
        $visibleOnFront
    );

    $eavSetup->updateAttribute(
        $attribute->getEntityTypeId(),
        $attribute->getAttributeId() ,
        'is_filterable',
        $useInFiler
    );
    $eavSetup->updateAttribute(
        $attribute->getEntityTypeId(),
        $attribute->getAttributeId() ,
        'is_filterable_in_search',
        $useInFiler
    );
    $eavSetup->updateAttribute(
        $attribute->getEntityTypeId(),
        $attribute->getAttributeId() ,
        'frontend_label',
        $rowData['frontend_label']
    );
}

foreach ($csvData as $row) {
    $rowData = [];
    foreach ($row as $keyField => $field) {
        $rowData[$header[$keyField]] = $field;
    }

    if (!$rowData['attribute_code']) {
        continue;
    }

    $isExists = true;
    try {
        $attributeModel = $productAttributeRepository->get($rowData['attribute_code']);
    } catch (\Exception $e) {
        $isExists = false;
    }

    if ($isExists) {
        updateAttribute($attributeModel, $rowData);
        continue;
    }

    $usedInProductListening = filter_var($rowData['Visible on Catalog Pages on Storefront'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $visibleOnFront = filter_var($rowData['Used in Product Listing'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $useInFiler = filter_var($rowData['Use in Filter'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if (!in_array($rowData['frontend_input'], ['select', 'boolean', 'multiselect'])) {
        $useInFiler = false;
    }
    if ($rowData['frontend_input'] == 'textarea'
        || str_contains($rowData['attribute_code'], 'sls_')
        || $rowData['attribute_code'] == 'product_categorisation'
    ) {
        $visibleOnFront = false;
    }

    $eavSetup->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        $rowData['attribute_code'],
        [
            'group' => str_contains($rowData['attribute_code'], 'sls_') ? 'SLS DAR Attributes': 'DAR Attributes',
            'input' => $rowData['frontend_input'],
            'type' => $rowData['backend_type'],
            'label' => $rowData['frontend_label'],
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false, // Use in Search
            'comparable' => false, // Comparable on Storefront
            'is_used_in_grid' => $rowData['frontend_input'] != 'textarea', // Add to Column Options
            'is_filterable_in_grid' => $rowData['frontend_input'] != 'textarea',  // Use in Filter Options
            'visible_on_front' => $visibleOnFront, // Visible on Catalog Pages on Storefront
            'visible_in_advanced_search' => false,
            'is_html_allowed_on_front' => $rowData['frontend_input'] != 'boolean',  // Allow HTML Tags on Storefront
            'used_in_product_listing' => $rowData['frontend_input'] != 'textarea', // Used in Product Listing
            'used_for_promo_rules' => false,
            'source' => $rowData['source_model'],
            'backend' => $rowData['backend_model'],
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'unique' => false,
            'wysiwyg_enabled' => $rowData['frontend_input'] == 'textarea'
        ]
    );
}
$setup->endSetup();
