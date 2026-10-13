<?php
/**
 * Mega Menu Menulabel Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Config\Backend\Design;

class Menulabel implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'none', 'label' => __('Normal')],
            ['value' => 'uppercase', 'label' => __('Uppercase')],
            ['value' => 'lowercase', 'label' => __('Lowercase')],
            ['value' => 'capitalize', 'label' => __('Capitalize')]
        ];
    }
}
