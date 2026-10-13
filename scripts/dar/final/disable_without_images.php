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

$csvFile = $baseDir . '/scripts/dar/final/csv/DAR_PRODUCTS.csv';
$csvData = $csvReader->getData($csvFile);

$attributesMatch = [
    // default magento attributes
    'Primary ID' => 'sku',
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
$galleryReadHandler = $obj->get(\Magento\Catalog\Model\Product\Gallery\ReadHandler::class);

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

    if ($product->getTypeId() == 'configurable') {
        continue;
    }

    $galleryReadHandler->execute($product);
    $images = $product->getMediaGalleryImages();
    $existImage = false;
    foreach ($images as $image) {
        if ($image->getFile()) {
            $existImage = true;
            break;
        }
    }

    if (!$existImage) {
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
        $product->save();
    }
}
