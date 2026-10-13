<?php

namespace Fyb\Customizer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const BASE_CUSTOMISE_PATH = 'customizer/general/url';

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface
     */
    protected $itemResolver;

    /**
     * @var \Magento\Catalog\Block\Product\ImageFactory
     */
    protected $imageFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver
     * @param \Magento\Catalog\Block\Product\ImageFactory $imageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver,
        \Magento\Catalog\Block\Product\ImageFactory $imageFactory,
    ) {
        parent::__construct($context);
        $this->itemResolver = $itemResolver;
        $this->imageFactory = $imageFactory;
    }

    public function getBaseCustomiseLink()
    {
        return (string)$this->scopeConfig->getValue(self::BASE_CUSTOMISE_PATH, ScopeInterface::SCOPE_STORE, null);
    }

    /**
     * @param $product
     *
     * @return string
     */
    public function getCustomizeLink($product = null)
    {
        $linkCustomise = trim($this->getBaseCustomiseLink(), '/') . '?';
        if ($product) {
            $darConf = ['CAT:' . $product->getData('shade_range_type')];
            if ($product->getData('shade_styles')) {
                $darConf[] = 'STY:' . $product->getData('shade_styles');
            }
            if ($product->getData('shade_range_name')) {
                $darConf[] = 'COL:' . strtoupper($product->getAttributeText('shade_range_name'));
            }
            $darConf[] = 'PRD:' . $product->getSku();
            $linkCustomise .= 'darconf=' . implode(',', $darConf) . '&';
        }

        $linkCustomise .= 'returnurl=' . $this->_urlBuilder->getUrl('customizer/cart/add');

        return $linkCustomise;
    }

    public function getOrderItemImage($item, $imageId = 'cart_page_product_thumbnail', $attributes = [])
    {
        $product = null;
        $itemProduct = $item->getProduct();
        if ($itemProduct->getTypeId() == 'configurable') {
            $simpleSku = $item->getProductOptions()['simple_sku'] ?? '';
            if ($simpleSku) {
                $child = ObjectManager::getInstance()->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
                try {
                    $product = $child->get($simpleSku);
                } catch (\Exception $e ) {
                    $product = null;
                }
            }
        }

        if (!$product) {
            $itemQuote = ObjectManager::getInstance()->create(\Magento\Quote\Model\Quote\Item::class)
                ->setProduct($itemProduct);
            $product = $this->itemResolver->getFinalProduct($itemQuote);
        }

        if ($customizerImage = $item->getData('customizer_img')) {
            $product->setSmallImage($customizerImage)
                ->setImage($customizerImage)
                ->setThumbnail($customizerImage);
        }

        return $this->imageFactory->create($product, $imageId, $attributes);
    }

    /**
     * @param $product
     *
     * @return bool
     */
    public function isCustomizable($product)
    {
        return $product->getData('shade_range_product');
    }
}
