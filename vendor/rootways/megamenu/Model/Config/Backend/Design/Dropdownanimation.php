<?php
/**
 * Mega Menu Dropdownanimation Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Config\Backend\Design;

class Dropdownanimation implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('None')],
            ['value' => 'topanimation', 'label' => __('From Top')],
            ['value' => 'bottomanimation', 'label' => __('From Bottom')],
            ['value' => 'rightanimation', 'label' => __('From Right')],
            ['value' => 'leftanimation', 'label' => __('From Left')]
        ];
    }
}
