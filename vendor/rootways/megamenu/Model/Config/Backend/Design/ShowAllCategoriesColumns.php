<?php
/**
 * Mega Menu Alignmenttype Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */

namespace Rootways\Megamenu\Model\Config\Backend\Design;

class ShowAllCategoriesColumns implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('1')],
            ['value' => '2', 'label' => __('2')],
            ['value' => '3', 'label' => __('3')],
            ['value' => '4', 'label' => __('4')],
            ['value' => '5', 'label' => __('5')],
            ['value' => '6', 'label' => __('6')]
        ];
    }
}
