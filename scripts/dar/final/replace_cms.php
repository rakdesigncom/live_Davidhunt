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

$filesystem = $obj->get(\Magento\Framework\Filesystem\Io\File::class);


$content = file_get_contents($baseDir . '/scripts/dar/final/cms_page/current.html');
$matches = null;
preg_match_all('/(http[s]{0,1}\:\/\/\S{4,}\/static\/[^"]{4,})/i', $content, $matches);
$matches = $matches[0];

function makeDirPath($path) {
    return file_exists($path) || mkdir($path, 0777, true);
}

foreach ($matches as $match) {
    $path = preg_replace('/.*frontend/', '', $match);
    $path = preg_replace('/\/en_GB/', '', $path);
    $path = preg_replace('/\/images\//', '/web/images/', $path);
    $currentPath = $baseDir . '/' . 'app/design/frontend' . $path;

    $path = preg_replace('/.*Fyb\//', '', $path);
    $path = preg_replace('/Magento_Cms.*images\//', '', $path);
    $targetPath = $baseDir . '/' . 'pub/media/wysiwyg/cms/' . $path;

    $targetDir = pathinfo($targetPath)['dirname'];
    makeDirPath($targetDir);
    $res = $filesystem->cp($currentPath, $targetPath);

    $url = trim(preg_replace('/.*media/', '', $targetPath), '/');
    $url = '{{media url=' . $url . '}}';
    $content = str_replace($match, $url, $content);


}

$content = preg_replace('(href="https.*thelightshadestudio[^\/]+)', 'href="', $content);
$content = preg_replace('(href="https.*davidhuntlighting[^\/]+)', 'href="', $content);

file_put_contents($baseDir . '/scripts/dar/final/cms_page/new_current.html', $content);
