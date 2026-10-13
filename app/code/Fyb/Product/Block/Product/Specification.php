<?php

namespace Fyb\Product\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Framework\View\ConfigInterface;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Catalog\Model\View\Asset\ImageFactory as AssetImageFactory;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Writer\PngWriter;

class Specification extends \Magento\Catalog\Block\Product\View
{
    protected $moduleReader;

    /**
     * @var ConfigInterface
     */
    private $presentationConfig;

    /**
     * @var AssetImageFactory
     */
    private $viewAssetImageFactory;

    /**
     * @var ParamsBuilder
     */
    private $imageParamsBuilder;


    /**
     * @var PlaceholderFactory
     */
    private $viewAssetPlaceholderFactory;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        ConfigInterface $presentationConfig,
        AssetImageFactory $viewAssetImageFactory,
        PlaceholderFactory $viewAssetPlaceholderFactory,
        ParamsBuilder $imageParamsBuilder,
        array $data = []
    ) {
        parent::__construct(
            $context, $urlEncoder, $jsonEncoder, $string, $productHelper, $productTypeConfig, $localeFormat,
            $customerSession, $productRepository, $priceCurrency, $data
        );

        $this->moduleReader = $moduleReader;
        $this->presentationConfig = $presentationConfig;
        $this->viewAssetPlaceholderFactory = $viewAssetPlaceholderFactory;
        $this->viewAssetImageFactory = $viewAssetImageFactory;
        $this->imageParamsBuilder = $imageParamsBuilder;
    }

    public function getBase64Image($imgPath)
    {
        return 'data:image/png;base64, ' . base64_encode(file_get_contents($imgPath));
    }

    public function getQrCode($url)
    {
        $labelMargin = new \Endroid\QrCode\Label\Margin\Margin(
            40, 0, 30, 0
        );
        $viewDir = $this->moduleViewDir('Fyb_Product');
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->size(450)
            ->margin(50)
            ->labelMargin($labelMargin)
            ->labelText('Create your own')
            ->labelFont(new \Endroid\QrCode\Label\Font\Font(
                $viewDir . '/frontend/web/fonts/DidotLTStd-Italic.otf',
                50
            ))
            ->labelAlignment(new LabelAlignmentCenter)
            ->validateResult(false)
            ->build();
        return $result->getDataUri();
    }

    protected function moduleViewDir($module)
    {
        return $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
            $module
        );
    }

    public function getLogo()
    {
        $store = $this->_storeManager->getStore()->getCode();
        $viewDir = $this->moduleViewDir('Fyb_Theme');

        return $this->getBase64Image(
            $viewDir . '/frontend/web/images/logo_' . $store . '.png'
        );
    }

    public function getProductImage($product, $imageId, $attributes = [])
    {
        $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Magento_Catalog',
            ImageHelper::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );

        $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
        $originalFilePath = $product->getData($imageMiscParams['image_type']);


        if ($originalFilePath === null || $originalFilePath === 'no_selection') {
            $imageAsset = $this->viewAssetPlaceholderFactory->create(
                [
                    'type' => $imageMiscParams['image_type'],
                ]
            );
        } else {
            /** @var \Magento\Catalog\Model\View\Asset\Image $imageAsset */
            $imageAsset = $this->viewAssetImageFactory->create(
                [
                    'miscParams' => $imageMiscParams,
                    'filePath' => $originalFilePath,
                ]
            );
        }

        if ($imageAsset && $imageAsset->getPath()) {
            return $this->getBase64Image($imageAsset->getPath());
        }

        return '';
    }
}
