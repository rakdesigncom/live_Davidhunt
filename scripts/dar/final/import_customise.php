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

$csvFile = $baseDir . '/scripts/dar/final/csv/catalog_product_20240911_142908.csv';
$csvData = $csvReader->getData($csvFile);

$attributesMatch = [
    'shade_range_type',
    'shade_styles',
    'shade_range_product',
];
$initialHeader = $csvData[0];
$header = $csvData[0];
$newHeader = [];
foreach ($header as $key => $field) {
    $field = strtolower(trim($field));
    $newHeader[$field] = $key;
}
$header = $newHeader;
$skuKey = $newHeader['sku'];
unset($csvData[0]);

$obj->get('Magento\Framework\Registry')->register('isSecureArea', true);
$urlHelper = $obj->get('Magento\Catalog\Model\Product\Url');

$limit = 20000;
$i = 0;
$startsFrom = 0;

function addOption($options, $product) {
    global $obj;
    $options = explode('|', $options);
    $customOptions = [];

    $data = [];
    foreach ($options as $option) {
        $newOption = [];
        foreach (explode(',', $option) as $value) {
            $d = explode('=', $value);
            if ($d[0] == 'name') {
                $d[0] = 'title';
            }
            if ($d[0] == 'required') {
                $d[0] = 'is_require';
                $d[1] = (bool)$d[1];
            }
            $newOption[$d[0]] = $d[1];
        }
        if (!isset($data[$newOption['title']])) {
            $data[$newOption['title']] = $newOption;
        }
        if (in_array($newOption['type'], array('drop_down', 'checkbox', 'radio', 'multiple'))) {
            $keyArr = $newOption['title'];
            $newOption['title'] = $newOption['option_title'];
            $data[$keyArr]['values'][] = $newOption;
        }
    }

    foreach ($data as $arrayOption) {
        $isRequire = false;
        $customOption = $obj->create('Magento\Catalog\Api\Data\ProductCustomOptionInterface');
        $customOption->setTitle($arrayOption['title'])
            ->setType($arrayOption['type'])
            ->setIsRequire($isRequire)
            ->setSortOrder($arrayOption['sort_order'] ?? null);
        if (!in_array($arrayOption['type'], array('drop_down', 'checkbox', 'radio', 'multiple'))) {
            $customOption->setPrice($arrayOption['price'])
                ->setPriceType($arrayOption['price_type']);
        } else {
            $customOption->setValues($arrayOption['values']);
        }
        $customOption->setProductSku($product->getSku());
        $customOptions[] = $customOption;
    }
    $product->setOptions($customOptions)->setHasOptions(true)->save();
}

$configurableType = $obj->get(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::class);
foreach ($csvData as $rowCountKey => $row) {
    if ($i >= $limit) {
        continue;
    }
    $i++;
    if ($rowCountKey < $startsFrom) {
        continue;
    }


    $productAttr = explode(',', $row[$header['additional_attributes']]);
    if (!array_search('shade_range_product=Yes', $productAttr) || !$row[$header['custom_options']]) {
        continue;
    }

    /** @var Magento\Catalog\Model\Product $product */
    try {
        $product = $productRepository->get($row[$skuKey], true, 0, true);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        continue;
    }

    if ($product->getTypeId() != 'simple') {
        continue;
    }
    $parentIds = $configurableType->getParentIdsByChild($product->getId());
    $parentId = array_shift($parentIds);
    if ($parentId) {
        continue;
    }

    foreach ($productAttr as  $attrData) {
        $attrData = explode('=', $attrData);
        $attribute = trim($attrData[0] ?? '');
        $field = trim($attrData[1] ?? '');
        if (!$attribute || !$field) {
            continue;
        }

        if (!in_array($attribute, $attributesMatch)) {
            continue;
        }

        if ($attribute == 'shade_range_name') {
            $field = ucwords(strtolower($field));
        }

        try {
            $attributeModel = $productAttributeRepository->get($attribute);

            if ($attributeModel->getFrontendInput() == 'boolean') {
                $field = strtolower($field) == 'yes' || strtolower($field) == 'y';
            }

            if ($attributeModel->getFrontendInput() == 'select' && $field && $attribute != 'country_of_origin' && $attribute != 'country_of_manufacture') {
                $allOptions = $attributeModel->getSource()->getAllOptions();
                $existingOptions = array_filter(array_column($allOptions, 'label'));
                if (!in_array($field, $existingOptions)) {
                    print_r([$field, $attribute, $product->getSku()]);
                    continue;
                    $optionId = createOptionForAttribute($field, $attributeModel->getAttributeId());
                } else {
                    $optionId = $attributeModel->getSource()->getOptionId(trim($field));
                }

                $field = $attributeModel->getSource()->getOptionId(trim($field));
            }

        } catch (\Exception $e) {
            print_r("ERROR: " . $e->getMessage() . ' PRODUCT: ' . $product->getSku() . "\n");
        }

        if ($field == 'DHLPFF' && $attribute == 'shade_range_type') {
            $field = 'DHLPF';
        }
        $product->setData($attribute, $field);
    }

    $customOptions = $row[$header['custom_options']];

    try {
        $product = $productRepository->save($product);
//        addOption($customOptions, $product);

        print_r('Product imported: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
    } catch (\Exception $e) {
        print_r("ERROR: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
    }
}

