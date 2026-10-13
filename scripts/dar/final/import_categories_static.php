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
$categoryLinkManagement = $obj->get('\Magento\Catalog\Api\CategoryLinkManagementInterface');

$csvFile = $baseDir . '/scripts/dar/final/csv/DAR_CATEGORIES.csv';
$csvData = $csvReader->getData($csvFile);

$header = $csvData[0];
unset($csvData[0]);
$storeId = 2;
$parentId = $storeManager->getStore($storeId)->getRootCategoryId();
$parentCategory = $obj
    ->create('Magento\Catalog\Model\Category')
    ->load($parentId);

$mainCategories = [];
$subCategories = [];
$subCategories2 = [];
$mainCatKey = array_search('cat_1', $header);
$subCatKey = array_search('cat_2', $header);
$subCatKey2 = array_search('cat_3', $header);

$obj->get('Magento\Framework\Registry')->register('isSecureArea', true);
function deleteAllCategories(\Magento\Catalog\Model\Category $parentCategory)
{
    global $obj;
    $childCategories = $parentCategory->getChildrenCategories();

    foreach ($childCategories as $category) {
        if ($category->getChildren()) {
            deleteAllCategories($category);
        }

        try {
            $category->delete();
            echo 'Category Removed ' . $category->getId() . PHP_EOL;
        } catch (Exception $e) {
            echo 'Failed to remove category ' . $category->getId() . PHP_EOL;
            echo $e->getMessage() . "\n" . PHP_EOL;
        }

    }
}

deleteAllCategories($parentCategory);

foreach ($csvData as $row) {
    $mainCat = trim($row[$mainCatKey]);
    if (!isset($mainCategories[$mainCat])) {
        $collection = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create()
            ->getCollection()
            ->addAttributeToFilter('name', $mainCat)->setPageSize(1);
        $mainCatItem = $collection->getFirstItem();
        if (!$mainCatItem->getId()) {
            $mainCatItem->setPath($parentCategory->getPath())
                ->setParentId($parentId)
                ->setName($mainCat)
                ->setIncludeInMenu(false)
                ->setIsActive(true);
            $mainCatItem->save();
        }

        $mainCategories[$mainCat] = $mainCatItem;
    }

    $subCat = trim($row[$subCatKey]);
    if ($subCat && !isset($subCategories[$mainCat . '_' . $subCat])) {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create()
            ->getCollection()
            ->addAttributeToFilter('name', $subCat)->setPageSize(1);
        $collection->addPathFilter($mainCategories[$mainCat]->getPath());

        $subCatItem = $collection->getFirstItem();
        if (!$subCatItem->getId()) {
            $subCatItem = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create();
            $subCatItem->setPath($mainCategories[$mainCat]->getPath())
                ->setParentId($mainCategories[$mainCat]->getId())
                ->setName($subCat)
                ->setIsActive(true);
            $subCatItem->save();
        }

        $subCategories[$mainCat . '_' . $subCat] = $subCatItem;
    }

    $subCat2 = trim($row[$subCatKey2]);
    if ($subCat2 && !isset($subCategories2[$mainCat . '_' . $subCat . '_' . $subCat2])) {
        $collection = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create()
            ->getCollection()
            ->addAttributeToFilter('name', $subCat2)->setPageSize(1);
        $collection->addPathFilter($subCategories[$mainCat . '_' . $subCat]->getPath());

        $subCatItem2 = $collection->getFirstItem();
        if (!$subCatItem2->getId()) {
            $subCatItem = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create();
            $subCatItem2->setPath($subCategories[$mainCat . '_' . $subCat]->getPath())
                ->setParentId($subCategories[$mainCat . '_' . $subCat]->getId())
                ->setName($subCat2)
                ->setIsActive(true);
            $subCatItem2->save();
        }

        $subCategories2[$mainCat . '_' . $subCat . '_' . $subCat2] = $subCatItem2;
    }
}

print_r([
    'main' => count($mainCategories),
    'sub' => count($subCategories),
    'sub2' => count($subCategories2),
]);
