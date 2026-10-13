<?php
/**
 * Mega Menu SubCatLevel Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Attribute;

class SubCatLevel extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => '0', 'label' => __('Please Select')],
                ['value' => '1', 'label' => 'Up to Level 1'],
                ['value' => '2', 'label' => 'Up to Level 2'],
                ['value' => '3', 'label' => 'Up to Level 3'],
                ['value' => '4', 'label' => 'Up to Level 4'],
            ];
        }
        return $this->_options;
    }
}
