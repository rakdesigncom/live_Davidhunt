<?php

namespace Fyb\Customizer\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverComposite;

class SetCustomizerData
{
    /**
     * @param ItemResolverComposite $subject
     * @param ProductInterface $result
     * @param ItemInterface $item
     *
     * @return ProductInterface
     */
    public function afterGetFinalProduct(
        ItemResolverComposite $subject,
        ProductInterface $finalProduct,
        ItemInterface $item
    ): ProductInterface {
        if ($customizerImage = $item->getData('customizer_img')) {
            $finalProduct->setSmallImage($customizerImage)
                ->setImage($customizerImage)
                ->setThumbnail($customizerImage);
        }

        return $finalProduct;
    }
}
