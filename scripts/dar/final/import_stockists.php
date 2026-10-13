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
$skockistCollection = $obj->get('Fyb\StockistLocator\Model\ResourceModel\Localstockists\CollectionFactory')->create();
$skockistFactory = $obj->get('Fyb\StockistLocator\Model\LocalstockistsFactory');
$googleApi = $obj->get('Fyb\StockistLocator\Model\GoogleApi');

$csvFile = $baseDir . '/scripts/dar/final/csv/Stockists.csv';
$csvData = $csvReader->getData($csvFile);
$header = $csvData[0];

$eavSetupFactory = $obj->get(\Magento\Eav\Setup\EavSetupFactory::class);
$setup = $obj->get(\Magento\Framework\Setup\ModuleDataSetupInterface::class);
$eavSetup = $eavSetupFactory->create(['setup' => $setup]);

unset($csvData[0]);
$storeId = 1;
//$skockistCollection->walk('delete');

foreach ($csvData as $key=>$row) {
//    if ($key != 34) {
//        continue;
//    }
    $stockist = $skockistFactory->create();
    $address = implode(',', [
        $row[2],
        $row[4],
        $row[5],
    ]);

    $stockist->addData([
        'name' => $row[1],
        'address_1' => $row[2],
        'address_2' => $row[3],
        'telephone' => $row[6],
        'city' => $row[4],
        'postcode' => $row[5],
    ]);
    try {
        $response = $googleApi->getGeocode($address);
    } catch (\Exception $e) {
        $response = $googleApi->getGeocode($row[5]);
    }

    if ($response) {
        $coordinates = $response[0]['geometry']['location'];
        $stockist->setData('latitude', $coordinates['lat']);
        $stockist->setData('longitude', $coordinates['lng']);
        $region = array_filter($response[0]['address_components'], function ($item) {
            return $item['types'][0] == 'administrative_area_level_2';
        });
        $region = reset($region);
        if (is_bool($region)) {
            $region = array_filter($response[0]['address_components'], function ($item) {
                return $item['types'][0] == 'administrative_area_level_1';
            });
            $region = reset($region);
        }

        $region = $region['short_name'] ?: $region['long_name'];
        $stockist->setData('county', $region);

    } else {
        $stockist->setData('latitude', 0);
        $stockist->setData('longitude', 0);
        $stockist->setData('county', '');
    }

    sleep(1);
    $stockist->save();
}
