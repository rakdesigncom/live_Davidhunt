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
$productActions = $obj->get('\Magento\Catalog\Model\ResourceModel\Product\Action');
$csvFile = $baseDir . '/scripts/dar/final/csv/dar_updated_products.csv';
$csvData = $csvReader->getData($csvFile);

$attributesMatch = [
    // default magento attributes
    'Primary ID' => 'sku',
    'Web Description' => 'name',
    'Internal Copy Text' => 'short_description',
    'Primary Copy Text' => 'description',
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
$productStoreCategorisationDhl = array_search('DHL Categorisation', $initialHeader);
$productStoreCategorisationLss = array_search('LSS Categorisation', $initialHeader);
unset($csvData[0]);

function createOptionForAttribute($label, $attributeId)
{
    global $obj;
    global $attributeOptionManagement;

    $optionNew = $obj->get(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class)->create();
    $optionNew->setLabel($label);
    $optionNew->setSortOrder(0);
    $optionNew->setIsDefault(false);

    $optionId = $attributeOptionManagement->add(
        \Magento\Catalog\Model\Product::ENTITY,
        $attributeId,
        $optionNew
    );

    return $optionId;
}

function prepareValue($attribute, $value) {
    if ($value === null) {
        return $value;
    }

    switch ($attribute) {
/*        case 'shade_included':
            if (strtolower($value) === 'yes') {
                $value = 'Yes';
            } else if (strtolower($value) === 'no') {
                $value = 'No';
            } else {
                $value = null;
            }
            break;*/
        case 'iprating':
            $value = preg_replace('/^IP/', '', $value);
            break;
        case 'pallet_delivery':
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
        case 'our_palette':
        case 'our_fabric':
            $value = (bool)filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            break;
        case 'electrical_accessories':
            if ($value == '1 - Earthed') {
                $value = 'Class I, Earthed';
            }
            if ($value == '2 - Double Insulated') {
                $value =  'Class II, Double Insulated';
            }
            if ($value == '3 - Low Voltage') {
                $value =  'Class III, Low Voltage';
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

$categoriesPerStore = [];
function getCategoryIds($store) {
    global $obj;
    global $categoriesPerStore;

    $storeId = $store->getId();
    if (isset($categoriesPerStore[$storeId])) {
        return $categoriesPerStore[$storeId];
    }
    $rootCategoryId = $store->getRootCategoryId();
    /** @var \Magento\Catalog\Model\Category $collection */
    $collection = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create();
    $collection = $collection->getCategories($rootCategoryId, 6, false, true, false);

    $final = [];


    foreach ($collection as $category) {
        $id = $category->getId(); //Ex - 20
        $path = $category->getPath(); // Ex - 1/3/15/20
        $path_togo = explode("/",$path);

        $path_as_name =array();

        foreach($path_togo as $paths){
            $cat_id = $paths;
            $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $category_data = $_objectManager->create('Magento\Catalog\Model\Category')
            ->load($cat_id);
            $cat_name = $category_data->getName();
            $path_as_name[]= $cat_name;
        }
        unset($path_as_name[0], $path_as_name[1]);//Remove the root category from the path  string

        $path_as_name =  preg_replace('/^Categories\//', '', implode('/', $path_as_name));
        $path_as_name =  preg_replace('/^Bespoke Shades & Lights\//', '', $path_as_name);
        if ($path_as_name) {
            $final[$id] = $path_as_name;
        }
    }

    $categoriesPerStore[$storeId] = $final;
    return $categoriesPerStore[$storeId];
}

function addProductToCategories($row, $product, $store) {
    global $productStoreCategorisationDhl;
    global $productStoreCategorisationLss;

    if ($store->getCode() == 'dhl') {
        $categories = explode('|', $row[$productStoreCategorisationDhl]);
    } else {
        $categories = explode('|', $row[$productStoreCategorisationLss]);
    }

    $categoryIds = [];

    $allCategoriesPath = getCategoryIds($store);
    foreach ($categories as $category) {
        $category = trim(preg_replace('/^default/i', '', $category), '/');
        $category = trim(preg_replace('/^products/i', '', $category), '/');
        $category = str_replace(' and ', ' & ', $category);
        $category = str_replace('The Collections', 'Collections', $category);
        $category = preg_replace('/\/all/i', '', $category);

        foreach ($allCategoriesPath as $ids => $catPath) {
            if (strtolower($catPath) == strtolower($category)) {
                $categoryIds = array_merge($categoryIds, [$ids]);
                break;
            }
        }
    }

    $res = true;
    $categoryIds = array_unique($categoryIds);
    return $categoryIds;
}

$obj->get('Magento\Framework\Registry')->register('isSecureArea', true);
$urlHelper = $obj->get('Magento\Catalog\Model\Product\Url');

$limit = 10000;
$i = 0;
$startsFrom = 0;
$storeDhl = $storeManager->getStore("dhl");
$storeIdDhl = $storeDhl->getId();
$displayOnStoreDhl = array_search('Display On DHL', $initialHeader);
$storeLss = $storeManager->getStore("lss");
$storeIdLss = $storeLss->getId();
$displayOnStoreLss =  array_search('Display On LSS', $initialHeader);
$isCatalogue =  array_search('Product Type', $initialHeader);
$visibilityOnStoreDhl = array_search('DHL Website Visibility',$initialHeader);
$visibilityOnStoreLss = array_search('LSS Website Visibility',$initialHeader);


$status = array_search('Magento Status', $initialHeader);
$visibilityMapping = [
    'Not Visible Individually' => 1,
    'Catalog' => 2,
    'Search' => 3,
    'Catalog, Search' => 4,
];

$statusMapping = [
    'Enable' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
    'Disable' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED
];

//$productCollection = $obj->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)->create();
//$productCollection->addAttributeToSelect('*')
//    ->addFieldToFilter('type_id', 'configurable');
//foreach ($productCollection as $product) {
//    $productRepository->delete($oduct);
//    print_r('Product deleted: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
//}

$resource = $obj->get('\Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

foreach ($csvData as $rowCountKey => $row) {
    if ($i >= $limit) {
        continue;
    }

    $i++;

    if ($rowCountKey < $startsFrom) {
        continue;
    }


    if (strtoupper(trim($row[$isCatalogue])) === 'MARKETING SUPPORT') {
        continue;
    }

    /** @var Magento\Catalog\Model\Product $product */
    try {
        $product = $productRepository->get($row[$skuKey], true, 0, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $product = $productFactory->create();
        $product->setAttributeSetId('4');
        $product->setSku($row[$skuKey]);
        $product->setStoreId(0);
        $product->setTypeId('simple');
        $product->setPrice(1);
        $product->setQuantityAndStockStatus(['qty' => 999, 'is_in_stock' => 1]);
    }

    foreach ($row as $key => $field) {
        if (!isset($header[$key])) {
            continue;
        }
        $field = trim($field);
        $attribute = $header[$key];

        if (strstr($field, 'Ã')) {
            $field = str_replace('Ã', 'Ø', $field);
        }
        if (strtolower($field) == 'n/a') {
            $field = null;
        }

        $field = prepareValue($attribute, $field);
        $productFinishValues = [];

        try {
            $attributeModel = $productAttributeRepository->get($attribute);

            if ($attributeModel->getFrontendInput() == 'boolean') {
                $field = strtolower($field) == 'yes' || strtolower($field) == 'y' || $field === true;
            }

            if ($attributeModel->getFrontendInput() == 'select' && $field && $attribute != 'country_of_origin' && $attribute != 'country_of_manufacture') {
                $allOptions = $attributeModel->getSource()->getAllOptions();
                $existingOptions = array_filter(array_column($allOptions, 'label'));
                $exists = false;
                foreach ($existingOptions as $existingOption) {
                    if (strtolower($field) == strtolower($existingOption)) {
                        $exists = true;
                        $field = $existingOption;
                        break;
                    }
                }

                if (!$exists) {
                    $optionId = createOptionForAttribute($field, $attributeModel->getAttributeId());
                } else {
                    $optionId = $attributeModel->getSource()->getOptionId(trim($field));
                }

                $field = $attributeModel->getSource()->getOptionId(trim($field));
            }

            if ($attributeModel->getFrontendInput() == 'multiselect' && $field) {
                if ($attribute == 'product_finish') {
                    $field = array_map('trim', explode(',', $field));
                } else if ($attribute == 'product_categorisation') {
                    $field = array_map('trim', explode('|', $field));
                    $field = array_filter($field, function ($valFieldFilter) {
                        return !str_contains($valFieldFilter, '/');
                    });
                } else if ($attribute == 'primary_secondary_appearance') {
                    $field = array_map('trim', explode('&', $field));
                } else if ($attribute == 'material') {
                    $field = array_map('trim', explode('&', $field));
                } else {
                    $field = array_map('trim', explode('|', $field));
                }
                $allOptions = $attributeModel->getSource()->getAllOptions();
                $existingOptions = array_filter(array_column($allOptions, 'label'));

                $newField = [];
                foreach ($field as $valField) {
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
                        $optionId = createOptionForAttribute($valField, $attributeModel->getAttributeId());
                    } else {
                        $optionId = $attributeModel->getSource()->getOptionId($valField);
                    }

                    $newField[$optionId] = $optionId;
                }

                $field = array_values($newField);
            }
        } catch (\Exception $e) {
            print_r("ERROR: " . $e->getMessage() . ' PRODUCT: ' . $product->getSku() . "\n");
            exit;
        }

        $product->setData($attribute, $field);
    }

    $product->setMetaTitle($product->getName());
    $product->setMetaDescription($product->getName());

    if (!$product->getName()) {
        $product->setName($row[$skuKey]);
    }

    $product->setUrlKey(
        $urlHelper->formatUrlKey($product->getName() . '-' . $product->getSku())
    );

    try {
        $storesDisplay = [];
        $displayOnDhl = filter_var($row[$displayOnStoreDhl], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($displayOnDhl) {
            $storesDisplay[] = $storeIdDhl;
        }

        $displayOnLss = filter_var($row[$displayOnStoreLss], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($displayOnLss) {
            $storesDisplay[] = $storeIdLss;
        }
        $product->setWebsiteIds($storesDisplay);
        //        $product->addData([
        //            'shade_range_type' => null,
        //            'shade_styles' => null,
        //            'shade_range_product' => null,
        //        ]);


        if(isset($statusMapping[$row[$status]])){
            $product->setStatus($statusMapping[$row[$status]]);
        }

        $product = $productRepository->save($product);

        if(in_array($storeIdDhl,$storesDisplay) && isset($visibilityMapping[$row[$visibilityOnStoreDhl]])){

            $productActions->updateAttributes([$product->getId()], ['visibility' => $visibilityMapping[$row[$visibilityOnStoreDhl]] ], 2 );

        }

        if(in_array($storeIdLss,$storesDisplay) && isset($visibilityMapping[$row[$visibilityOnStoreLss]])){

            $productActions->updateAttributes([$product->getId()], ['visibility' => $visibilityMapping[$row[$visibilityOnStoreLss]]] ,3);

        }




        //        if ($product->getId()) {
        //            $connection
        //                ->query("DELETE from catalog_category_product where product_id = '". $product->getId(). "' ");
        //        }
        //        $categoriesIdsLss = [];
        //        $categoriesIdsDhl = [];
        //        if ($displayOnDhl) {
        //            $categoriesIdsDhl = addProductToCategories($row, $product, $storeDhl);
        //        }
        //        if ($displayOnLss) {
        //            $categoriesIdsLss = addProductToCategories($row, $product, $storeLss);
        //        }
        //
        //        try {
        //            $categoriesIdsAll = array_unique(array_merge($categoriesIdsLss, $categoriesIdsDhl));
        //            $res = $categoryLinkManagement->assignProductToCategories(
        //                $product->getSku(),
        //                $categoriesIdsAll
        //            );
        //        } catch (\Exception $e) {
        //            print_r("ERROR CAT: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
        //            print_r($categoriesIdsAll);
        //            print_r($storesDisplay);
        //            exit;
        //        }

        print_r('Product imported: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
    } catch (\Exception $e) {
        print_r("ERROR: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
        exit;
    }
}

$productCollection = $obj->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)->create();
$productCollection->addAttributeToSelect('*');
$allSkus = [];
//foreach ($csvData as $rowCountKey => $row) {
//    $allSkus[] = $row[$skuKey];
//}
//foreach ($productCollection as $product) {
//    if (!in_array($product->getSku(), $allSkus)) {
//        $productRepository->delete($product);
//        print_r('Product deleted: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
//    }
//}
