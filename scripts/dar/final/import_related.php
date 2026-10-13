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
    'Primary Copy Text' => 'short_description',
    'DHL Web FAQs' => 'description',
    'Country of Origin' => 'country_of_manufacture',
    'RRP (ex vat)' => 'price',
    'Product Weight' => 'weight',

    // DAR Attributes
    'Range Name' => 'shade_range_name',
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
    'Electrical Accessories' => 'electrical_accessories',
    'Materials' => 'material',
    'Appearance' => 'appearance',
    'Icons' => 'icons',
    'Current Catalogue' => 'current_catalogue',
    'SLS Switch Type' => 'sls_switch_type',
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

$obj->get('Magento\Framework\Registry')->register('isSecureArea', true);
$urlHelper = $obj->get('Magento\Catalog\Model\Product\Url');

$limit = 100000;
$i = 0;
$startsFrom = 0;
$requiredProducts = [];
$relatedProductsKey =  array_search('Related Products', $initialHeader);
$upsellProductsKey =  array_search('Upsell Products', $initialHeader);

foreach ($csvData as $rowCountKey => $row) {
    if ($i >= $limit) {
        continue;
    }

    $i++;

    if ($rowCountKey < $startsFrom) {
        continue;
    }

    /** @var Magento\Catalog\Model\Product $product */
    try {
        $product = $productRepository->get($row[$skuKey], true, 0, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        continue;
    }

    $relatedProducts = explode('|', $row[$relatedProductsKey]);
    $linkData = [];
    foreach ($relatedProducts as $index => $relatedProduct) {
        try {
            $related = $productRepository->get($relatedProduct, true, 0, true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            continue;
        }

        $productLink = $obj->get(\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory::class)->create();
        $linkData[] =  $productLink->setSku($product->getSku())
            ->setLinkedProductSku($related->getSku())
            ->setPosition($index)
            ->setLinkType('upsell');
    }


    $upsellProducts = explode('|', $row[$upsellProductsKey]);
    foreach ($upsellProducts as $index => $upsellProduct) {
        try {
            $crosssell = $productRepository->get($upsellProduct, true, 0, true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            continue;
        }

        $productLink = $obj->get(\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory::class)->create();
        $linkData[] =  $productLink->setSku($product->getSku())
            ->setLinkedProductSku($crosssell->getSku())
            ->setPosition($index)
            ->setLinkType('crosssell');
    }

    try {
        $product->setProductLinks($linkData)->save();
        print_r('Product imported: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
    } catch (\Exception $e) {
        print_r("ERROR: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
        exit;
    }
}
