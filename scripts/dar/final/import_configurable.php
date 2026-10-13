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
);
$storeManager = $obj->get('\Magento\Store\Model\StoreManagerInterface');
$csvReader = $obj->get('\Magento\Framework\File\Csv');
$productRepository = $obj->get('Magento\Catalog\Model\ProductRepository');
$productFactory = $obj->get('Magento\Catalog\Model\ProductFactory');
$productAttributeRepository = $obj->get('\Magento\Catalog\Api\ProductAttributeRepositoryInterface');
$attributeOptionManagement = $obj->get('\Magento\Eav\Api\AttributeOptionManagementInterface');
$categoryLinkManagement = $obj->get('\Magento\Catalog\Api\CategoryLinkManagementInterface');

$csvFile = $baseDir . '/scripts/dar/final/csv/dar_updated_products.csv';
$csvData = $csvReader->getData($csvFile);

$attributesMatch = [
    // default magento attributes
    'Primary ID' => 'sku',
    'Web Description' => 'name',
    'Internal Copy Text' => 'short_description',
    'Web FAQs' => 'description',
    'Country of Origin' => 'country_of_manufacture',
    'RRP (ex vat)' => 'price',

    // DAR Attributes
    'Product Weight' => 'nettweight',
    'Project No' => 'project_no',
    'Spec No' => 'spec_no',
    'Range Name' => 'range_name',
    'Selling Season' => 'selling_season',
    'Selling Year' => 'selling_year',
    'WEB-Product Type' => 'dar_product_type',
    'Product Type' => 'cat_product_type',
    'Primary Material' => 'primary_material',
    'Primary Material Details' => 'primary_material_details',
    'Primary Finish' => 'primary_finish',
    'Primary Colour' => 'primary_color',
    'Secondary Material' => 'secondary_material',
    'Secondary Material Details' => 'secondary_material_details',
    'Secondary Finish' => 'secondary_finish',
    'Secondary Colour' => 'secondary_color',
    'Appearance' => 'appearance',
    'Min Height' => 'dar_min_height',
    'Max Height' => 'dar_max_height',
    'Measurements' => 'measurements',
    'Base Measurements' => 'basemeasurements',
    'Shade Measurements' => 'shademeasurements',
    'Fixing Bracket Measurements' => 'fixint_bracket_measurements',
    'Replenishment System' => 'replenishment_system',
    'Material Composition' => 'material_composition',
    'Shade Included' => 'shade_included',
    'Shade Shape' => 'shade_shape',
    'Gimble Type' => 'gimble_type',
    'Outer Shade Colour' => 'outer_shadecolour',
    'Inner Shade Material' => 'inner_shadematerial',
    'Inner Shade Colour' => 'inner_shadecolour',
    'Outer Shade Material' => 'outer_shadematerial',
    'Associated Shade' => 'associated_shade',
    'Primary Bulb Detail' => 'primary_bulb_detail',
    'Lamp Holder Type' => 'lamp_holder_type',
    'No Of Lamps' => 'number_of_lamps',
    'Wattage' => 'total_watts',
    'Subject to Shade' => 'subject_to_shade',
    'Primary Lamp' => 'primary_lamp',
    'Secondary Lamp' => 'secondary_lamp',
    'Bulb Type' => 'bulb_type',
    'Lamp Included' => 'lamp_included',
    'Primary Bulb Included Detail' => 'primary_bulb_included_detail',
    'Lamp Replaceable' => 'lamp_replaceable',
    'Dimmable' => 'dimmable',
    'Switch Type' => 'switch_type',
    'Cable Colour' => 'cable_colour',
    'Bluetooth' => 'has_bluetooth',
    'Motion Sensor' => 'motion_sensor',
    'Solar Powered' => 'solar_powered',
    'Batteries Required' => 'batteries_required',
    'Batteries Included' => 'batteries_included',
    'USB' => 'has_usb',
    'Class' => 'electrical_accessories',
    'IP Rating' => 'iprating',
    'Icons' => 'icons',
    'Lamplife' => 'lamplife',
    'Efficiency' => 'efficiency',
    'Lumens' => 'lumens',
    'Colour Rendering' => 'colour_rendering',
    'Colour Temp' => 'colour_temp',
    'SLS Lamp Holder Type' => 'sls_lamp_holder_type',
    'SLS No Of Lamps' => 'sls_number_of_lamps',
    'SLS Wattage' => 'sls_total_watts',
    'SLS Subject to Shade' => 'sls_subject_to_shade',
    'SLS Bulb Type' => 'sls_bulb_type',
    'SLS Recommended Lamp' => 'sls_recommendedlamp',
    'SLS Lamp Included' => 'sls_lamp_included',
    'Secondary Bulb Included Detail' => 'secondary_bulb_included_detail',
    'SLS Lamp Replaceable' => 'sls_lamp_replaceable',
    'SLS Dimmable' => 'sls_dimmable',
    'SLS Switch Type' => 'sls_switch_type',
    'SLS Cable Colour' => 'sls_cable_colour',
    'SLS Lamplife' => 'sls_lamplife',
    'SLS Efficiency' => 'sls_efficiency',
    'SLS Lumens' => 'sls_lumens',
    'SLS Colour Rendering' => 'sls_colour_rendering',
    'SLS Colour Temp' => 'sls_colour_temp',
    'Dual Mount' => 'dual_mount',
    'Selling Pack Size' => 'selling_pack_size',
    'Qty In Export Carton' => 'qty_in_export_carton',
    'Inner Barcode' => 'inner_barcode',
    'Outer Barcode' => 'outer_barcode',
    'Pallet Delivery' => 'pallet_delivery',
    'Pallet Type' => 'pallet_type',
    'Two Man Lift' => 'two_man_lift',
    'Product Style' => 'product_style',
    'Suitable For' => 'suitable',
    'Current Catalogue' => 'current_catalogue',
    'Primary & Secondary Appearance' => 'primary_secondary_appearance',
    'Hanging Orientation' => 'hanging_orientation',
    'Primary Associated Shade' => 'primary_associated_shade',
];

$initialHeader = $csvData[0];
$header = $csvData[0];
$newHeader = [];
foreach ($header as $key => $field) {
    $field = trim($field);
    if (isset($attributesMatch[$field])) {
        $newHeader[$key] = $attributesMatch[$field];
    }
}
$header = $newHeader;
$skuKey = array_search('sku', $header);
unset($csvData[0]);

function createOptionForAttribute($label, $color, $attributeId)
{
    global $obj;
    global $attributeOptionManagement;

    $optionNew = $obj->get(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class)->create();
    $swatchNew = $obj->get(\Magento\Swatches\Model\SwatchFactory::class)->create();
    $optionNew->setLabel($label);
    $optionNew->setSortOrder(0);
    $optionNew->setIsDefault(false);

    $optionId = $attributeOptionManagement->add(
        \Magento\Catalog\Model\Product::ENTITY,
        $attributeId,
        $optionNew
    );

    $swatchNew->setData([
        'option_id' => $optionId,
        'type'=> 1,
        'value' => '#'. $color
    ]);
    $swatchNew->save();
    return $optionId;
}

$obj->get('Magento\Framework\Registry')->register('isSecureArea', true);
$urlHelper = $obj->get('Magento\Catalog\Model\Product\Url');

function prepareConfigurableProducts($configurableProducts) {
    $res = [];
    foreach ($configurableProducts as $keyC => $configurableProduct) {
        $configurableProduct = explode(',', $configurableProduct);
        foreach ($configurableProduct as $key => $value) {
            $value = explode('=', $value);
            $res[$keyC][trim(strtoupper($value[0]))] = trim($value[1]);
        }
    }
    return $res;
}

$limit = 1000000;
$i = 0;
$startsFrom = 0;
$configurableData =  array_search('Variant Data', $initialHeader);

$attributeCode = 'finishes';
$attributeModel = $productAttributeRepository->get($attributeCode);
$attributes = [$attributeModel->getId()];

foreach ($csvData as $rowCountKey => $row) {
    if ($i >= $limit) {
        continue;
    }

    $i++;
    if (!$row[$configurableData]) {
        continue;
    }
    if ($rowCountKey < $startsFrom) {
        continue;
    }

    /** @var Magento\Catalog\Model\Product $product */
    try {
        $product = $productRepository->get($row[$skuKey], true, 0, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        continue;
    }

    $configurableProducts = explode('|', str_replace(';', ',', $row[$configurableData]));
    $configurableProducts = prepareConfigurableProducts($configurableProducts);

    $associatedProductIds = [];
    foreach ($configurableProducts as $configurableProductOption) {
        $optionToSearch = $configurableProductOption['COLOUR'];
        if (!$optionToSearch || !$configurableProductOption['HEX CODE']) {
            continue;
        }
        try {
            $allOptions = $attributeModel->getSource()->getAllOptions();
            $existingOptions = array_filter(array_column($allOptions, 'label'));
            if (!in_array($optionToSearch, $existingOptions)) {
                $optionId = createOptionForAttribute($optionToSearch, $configurableProductOption['HEX CODE'], $attributeModel->getAttributeId());
            } else {
                $optionId = $attributeModel->getSource()->getOptionId($optionToSearch);
            }

            try {
                $productToAdd = $productRepository->get($configurableProductOption['SKU'], true, 0, true);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }

            $productToAdd->addData([$attributeCode => $optionId]);

            $productToAdd->save();
            $associatedProductIds[] = $productToAdd->getId();
        } catch (\Exception $e) {
            print_r("ERROR: " . $e->getMessage() . ' PRODUCT: ' . $product->getSku() . "\n");
        }
    }

    if (!$associatedProductIds) {
        continue;
    }

    try {
        $product->setTypeId("configurable"); // Setting Product Type As Configurable
        $product->setAffectConfigurableProductAttributes(4);
        $obj->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')
            ->setUsedProductAttributeIds($attributes, $product);
        $product->setNewVariationsAttributeSetId(4); // Setting Attribute Set Id
        $product->setAssociatedProductIds($associatedProductIds);// Setting Associated Products
        $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        $product->setConfigurableAttributesData($configurableAttributesData);
        $product->setCanSaveConfigurableAttributes(true);

        $product->save();

        print_r('Product imported: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
    } catch (\Exception $e) {
        print_r("ERROR: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
        exit;
    }
}
