<?php

namespace Fyb\Product\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;

class Data extends AbstractHelper
{
    const MEASUREMENT_PATH = 'catalog/product_measurements';

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface
     */
    protected $itemResolver;

    /**
     * @var \Magento\Catalog\Block\Product\ImageFactory
     */
    protected $imageFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileReader;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $mediaDir;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Io\File $fileReader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ImageFactory $imageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileReader,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ImageFactory $imageFactory,
    ) {
        parent::__construct($context);
        $this->itemResolver = $itemResolver;
        $this->filesystem = $filesystem;
        $this->fileReader = $fileReader;
        $this->storeManager = $storeManager;
        $this->imageFactory = $imageFactory;
        $this->mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    /**
     * @param $product
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMeasurementImage($product)
    {
        $imgPath = self::MEASUREMENT_PATH . '/' . $product->getSku() . '_M.png';
        $filePath = $this->mediaDir->getAbsolutePath($imgPath);

        $url = [];

        if ($this->fileReader->fileExists($filePath)) {
            $url['url'] = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $imgPath;
            $url['path'] = $filePath;
        }

        return $url;
    }
}
