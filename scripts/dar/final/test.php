<?php

ini_set('output_buffering', 'off');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Locale\Format;

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

$variation = $obj->get(\Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices::class);
$localeFormat = $obj->get(Format::class);
$productFactory = $obj->get('\Magento\Catalog\Api\ProductRepositoryInterface');
//$product = $productFactory->get("ZHI0999-18", false, null, true);
////$prices = $variation->getFormattedPrices($product->getPriceInfo());
//$priceInfo = $product->getPriceInfo();
//$optionPrices[$product->getId()] = [
//    'baseOldPrice' => [
//        'amount' => $localeFormat->getNumber(
//            $priceInfo->getPrice('regular_price')->getAmount()->getBaseAmount()
//        ),
//    ],
//    'oldPrice' => [
//        'amount' => $localeFormat->getNumber(
//            $priceInfo->getPrice('regular_price')->getAmount()->getValue()
//        ),
//    ],
//    'basePrice' => [
//        'amount' => $localeFormat->getNumber(
//            $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount()
//        ),
//    ],
//    'finalPrice' => [
//        'amount' => $localeFormat->getNumber(
//            $priceInfo->getPrice('final_price')->getAmount()->getValue()
//        ),
//    ],
////    'tierPrices' => $this->getTierPricesByProduct($product),
////    'msrpPrice' => [
////        'amount' => $localeFormat->getNumber(
////            $this->priceCurrency->convertAndRound($product->getMsrp())
////        ),
////    ],
//];
//print_r($optionPrices); exit;
//$obj->get(\Fyb\Pimberly\Cron\ProductSync::class)->execute();
//$cron = $obj->get(\Fyb\Pimberly\Cron\ProductsExport::class);
$cron = $obj->get(\Fyb\Pimberly\Cron\ProductSync::class);
$cron->execute();
