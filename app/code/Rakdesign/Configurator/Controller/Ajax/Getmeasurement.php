<?php

namespace Rakdesign\Configurator\Controller\Ajax;

class Getmeasurement extends \Magento\Framework\App\Action\Action {

    protected $resultJsonFactory;
    protected $request;
    protected $filesystem;
    protected $productRepository;

    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\App\Request\Http $request,
            \Magento\Framework\Filesystem $filesystem,
            \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory) {

        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    protected function getMeasurementFile($sku) {
        $mediaPath = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
        if (file_exists($mediaPath . 'wysiwyg/pdf-data-sheet/' . $sku . '_M.jpg')) {
            return 'pub/media/wysiwyg/pdf-data-sheet/' . $sku . '_M.jpg';
        }
        return false;
    }

    protected function fileExists(){
        
    }
    protected function loadMyProduct($sku) {
        $product = false;
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $product = false;
        }
        return $product;
    }

    protected function getImages($_product) {
        if (!$_product) {
            return [];
        }
        $images = $_product->getMediaGalleryImages();
        $allImages = [];

        $kkey = 2;
        foreach ($images as $image) {
            if (isset($image['url']) && $image['url'] !== '' && file_exists($image['path'])) {
                if (strpos(strtolower($image->getUrl()), '_m.jpg') != false) {
                    $allImages[0] = $image->getUrl();
                } elseif (strpos(strtolower($image->getUrl()), '_ls1.jpg') != false) {
                    $allImages[1] = $image->getUrl();
                } else {
                    $allImages[$kkey] = $image->getUrl();
                    $kkey++;
                }
            }
        }
        ksort($allImages);
        return $allImages;
    }

    public function execute() {
        $sku = $this->request->getParam('sku');
        $_product = $this->loadMyProduct($sku);
        $gallery = $this->getImages($_product);
        $resultJson = $this->resultJsonFactory->create();
        $baseImg = $this->getMeasurementFile($sku);
        $file = $baseImg ? $baseImg : array_shift($gallery);
        return $resultJson->setData(['sku' => $sku, 'file' => $file, 'gallery' => $gallery]);
    }

}
