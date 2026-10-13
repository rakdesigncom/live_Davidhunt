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

$csvFile = $baseDir . '/scripts/dar/final/csv/DAR_PRODUCTS.csv';
$csvData = $csvReader->getData($csvFile);

$header = $csvData[0];
unset($csvData[0]);
$store = "lss";
$storeId = $storeManager->getStore($store)->getId();
$parentId = $storeManager->getStore($storeId)->getRootCategoryId();
$parentCategory = $obj
    ->create('Magento\Catalog\Model\Category')
    ->load($parentId);

$allCategories = [];
$catKeyCsv =  array_search('LSS Categorisation', $header);
foreach ($csvData as $row) {
    $catCsv = $row[$catKeyCsv];
    if (!$catCsv) {
        continue;
    }

    $catCsv = explode("|", $catCsv);
    foreach ($catCsv as $catKey => $catValue) {
        $catValue = trim($catValue);
        $catValue = str_replace(' and ', ' & ', $catValue);
        $catValue = str_replace('Products/', 'Categories/', $catValue);
        $catValue = preg_replace('/\/all/i', '', $catValue);
        $catValue = str_replace('The Collections', 'Collections', $catValue);
        $catValue = trim($catValue, '/');
        if ($catValue) {
            $allCategories[$catValue] = $catValue;
        }
    }
}
$allCategories = array_values($allCategories);

$mainCategories = [];
$subCategories = [];
$subCategories2 = [];
$mainCatKey = 0;
$subCatKey = 1;
$subCatKey2 = 2;

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
            if ($category->getLevel() <= 2) {
                continue;
            }

            $category->delete();
            echo 'Category Removed ' . $category->getId() . PHP_EOL;
        } catch (Exception $e) {
            echo 'Failed to remove category ' . $category->getId() . PHP_EOL;
            echo $e->getMessage() . "\n" . PHP_EOL;
        }

    }
}

deleteAllCategories($parentCategory);

foreach ($allCategories as $row) {
    $row = explode('/', $row);
    $mainCat = trim($row[$mainCatKey]);
    if (!isset($mainCategories[$mainCat])) {
        $collection = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create()
            ->getCollection()
            ->addAttributeToFilter('name', $mainCat)->setPageSize(1);
        $collection->addPathFilter($parentCategory->getPath());

        $mainCatItem = $collection->getFirstItem();
        if (!$mainCatItem->getId()) {
            $mainCatItem->setPath($parentCategory->getPath())
                ->setParentId($parentId)
                ->setName($mainCat)
                ->setIncludeInMenu(true)
                ->setIsActive(true);
            $mainCatItem->save();
        }

        $mainCategories[$mainCat] = $mainCatItem;
    }

    $subCat = trim($row[$subCatKey]);
    if ($subCat && !isset($subCategories[$mainCat . '_' . $subCat])) {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $childs = $mainCategories[$mainCat]->getChildrenCategories();
        $subCatItem = null;
        foreach ($childs as $child) {
            if ($child->getName() == $subCat) {
                $subCatItem = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create()->load($child->getId());
            }
        }

        if (!$subCatItem || !$subCatItem->getId()) {
            /** @var \Magento\Catalog\Model\Category $subCatItem */
            $subCatItem = $obj->get(\Magento\Catalog\Model\CategoryFactory::class)->create();
            $subCatItem->setPath($mainCategories[$mainCat]->getPath())
                ->setParentId($mainCategories[$mainCat]->getId())
                ->setName($subCat)
                ->setIsActive(true);
            if ($subCat == 'Ceiling') {
                $subCatItem->setUrlKey('in-ceiling');
            }

            $subCatItem->save();
        }

        $subCategories[$mainCat . '_' . $subCat] = $subCatItem;


    }

    $subCat2 = trim($row[$subCatKey2] ?? '');
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
