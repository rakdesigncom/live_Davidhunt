<?php

namespace Fyb\Theme\Plugin;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Image;

class DefaultConfigurableImage
{
    /**
     * @param AbstractProduct $subject
     * @param Product $product
     * @param string $imageId
     * @param array $attributes
     *
     * @return array
     */
    public function beforeGetImage(AbstractProduct $subject, $product, $imageId, $attributes = []): array
    {
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE ) {
            if (!$product->getSmallImage() || $product->getSmallImage() == 'no_selection') {
                $product->setSmallImage($product->getImage())
                    ->setImage($product->getImage())
                    ->setThumbnail($product->getImage());
            }
        }

        return [$product, $imageId, $attributes];
    }
}
