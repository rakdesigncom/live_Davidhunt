<?php

namespace Fyb\Blog\Plugin;

use Mageplaza\Blog\Model\Config\Source\DateFormat\TypeMonth;

class AdditionalTypeMonth
{
    /**
     * @param TypeMonth $subject
     * @param array $result
     *
     * @return array
     */
    public function afterToOptionArray(TypeMonth $subject, array $result): array
    {
        $type = ['F Y'];
        foreach ($type as $item) {
            $result [] = [
                'value' => $item,
                'label' => $item . ' (' . date($item) . ')'
            ];
        }
        return $result;
    }
}
