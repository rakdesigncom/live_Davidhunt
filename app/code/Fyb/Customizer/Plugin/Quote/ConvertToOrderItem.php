<?php

namespace Fyb\Customizer\Plugin\Quote;

use Closure;

class ConvertToOrderItem
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);
        $orderItem->setCustomizerImg($item->getCustomizerImg());

        if ($additionalOptionsQuote = $item->getOptionByCode('additional_options')) {
            if ($additionalOptionsQuote->getValue()) {
                $options = $orderItem->getProductOptions();
                $options['additional_options'] = json_decode($additionalOptionsQuote->getValue());
                $orderItem->setProductOptions($options);
            }
        }

        return $orderItem;
    }
}
