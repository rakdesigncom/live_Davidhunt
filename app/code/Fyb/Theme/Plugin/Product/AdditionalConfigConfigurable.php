<?php

namespace Fyb\Theme\Plugin\Product;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;

class AdditionalConfigConfigurable
{
    public function afterGetJsonConfig(Configurable $subject, $result) {
        $jsonResult = json_decode($result, true);
        $product = $subject->getProduct();

        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $jsonResult['confData'][$simpleProduct->getId()] = [
                'title' => $simpleProduct->getName()
            ];
        }

        $jsonResult['baseConfData'] = [
            'sku' => $product->getSku(),
            'title' => $product->getName(),
        ];

        $result = json_encode($jsonResult);
        return $result;
    }
}
