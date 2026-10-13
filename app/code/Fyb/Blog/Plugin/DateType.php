<?php

namespace Fyb\Blog\Plugin;

use Mageplaza\Blog\Model\Config\Source\DateFormat\Type;

class DateType
{
    /**
     * @param Type $subject
     * @param array $result
     *
     * @return array
     */
    public function afterToOptionArray(Type $subject, array $result): array
    {
        $type = [
            'm.d.Y',
            'd.m.Y',
        ];
        foreach ($type as $item) {
            $result[] = [
                'value' => $item,
                'label' => $item . ' (' . date($item) . ')'
            ];
        }

        return $result;
    }
}
