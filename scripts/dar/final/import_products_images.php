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
$state = $obj->get('\Magento\Framework\App\State');

$storeManager = $obj->get('\Magento\Store\Model\StoreManagerInterface');
$csvReader = $obj->get('\Magento\Framework\File\Csv');
$productRepository = $obj->get('Magento\Catalog\Model\ProductRepository');
$productFactory = $obj->get('Magento\Catalog\Model\ProductFactory');
$productAttributeRepository = $obj->get('\Magento\Catalog\Api\ProductAttributeRepositoryInterface');

$csvFile = $baseDir . '/scripts/dar/final/csv/dar_updated_products.csv';
$csvData = $csvReader->getData($csvFile);

$attributesMatch = [
    'Primary ID' => 'sku',
    'Categorisation' => 'product_categorisation',
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
$productStoreCategorisation = array_search('product_categorisation', $header);
unset($csvData[0]);

$fileReader = $obj->get(\Magento\Framework\Filesystem\Io\File::class);
function uploadImage($imageUrl, $product, $isMeasurement = false) {
    global $baseDir;
    global $fileReader;
    global $obj;
    $fileSystem = $obj->create('\Magento\Framework\Filesystem');
    $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();

    $image_path_info = pathinfo(parse_url($imageUrl)["path"]);
    $filename   = $product->getSku() . '_' . uniqid() . "." . $image_path_info["extension"];
    if ($isMeasurement) {
        $filename = $product->getSku() . "_M." . $image_path_info["extension"];
    }
    $fileReader->checkAndCreateFolder( $mediaPath . 'wysiwyg/tmp/');
    $filepath = $mediaPath . 'wysiwyg/tmp/' . $filename;
    if ($isMeasurement) {
        $filepath = $mediaPath . 'catalog/product_measurements/' . $filename;
    }

    $result = $fileReader->read($imageUrl, $filepath);

    return $filepath;
}

function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
        -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

$mainImage = array_search('Website Images 1', $initialHeader);
$additionalImages = [];

$attrAdditionalImages = [
    'Website Images 2',
    'Website Images 3',
    'Website Images 4',
    'Website Images 5',
    'Website Images 6',
    'Measurement Image',
];

foreach ($initialHeader as $keyHeader => $value) {
    if (in_array($value, $attrAdditionalImages)) {
        $additionalImages[$value] = $keyHeader;
    }
}

function importImages($row, $product) {
    /** @var \Magento\Catalog\Model\Product $product */
    global $productRepository;
    global $mainImage;
    global $additionalImages;
    global $state;
    global $imagesImported;

    $product->setMediaGalleryEntries([]);
    $productRepository->save($product);
    $imagesImported = [];

    if ($row[$mainImage] && !in_array($row[$mainImage], $imagesImported)) {
        $imagesImported[] = $row[$mainImage];
        $productImage = uploadImage($row[$mainImage], $product);
        $attr = ['image', 'small_image', 'thumbnail'];

        $product->addImageToMediaGallery($productImage, $attr, true, false);
    }

    if ($additionalImages) {
        foreach ($additionalImages as $typeImg => $additionalImage) {
            if ($row[$additionalImage] && !in_array($row[$additionalImage], $imagesImported)) {
                $imagesImported[] = $row[$additionalImage];
                $productImage = uploadImage($row[$additionalImage], $product, $typeImg == 'Measurement Image');
                if ($typeImg != 'Measurement Image') {
                    $product->addImageToMediaGallery($productImage, null, true, false);
                }
            }
        }
    }

    $state->emulateAreaCode(
        \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
        function() use ($productRepository, $product) {
            $productRepository->save($product);
        }
    );
}

$limit = 100000;
$i = 0;
$startsFrom = 0;
$isCatalogue =  array_search('Product Type', $initialHeader);
$imagesImported = [];
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
        continue;
    }

    $imageProcessor = $obj->create('\Magento\Catalog\Model\Product\Gallery\Processor');
    $images = $product->getMediaGalleryImages();
    foreach($images as $child){
        $imageProcessor->removeImage($product, $child->getFile());
    }

    try {
        importImages($row, $product);

        print_r('Product imported: ' . $product->getId() . ' - ' . $product->getSku() . "\n");
    } catch (\Exception $e) {
        print_r("ERROR: " . $row[$skuKey] . ' - ' . $e->getMessage() . "\n");
        exit;
    }
}
