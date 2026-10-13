<?php
/**
 * Mega Menu ContentBlockWidth Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Attribute;

class ContentBlockWidth extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => '0', 'label' => __('Do Not Show Content')],
                ['value' => '1', 'label' => '1/12'],
                ['value' => '2', 'label' => '2/12'],
                ['value' => '3', 'label' => '3/12'],
                ['value' => '4', 'label' => '4/12'],
                ['value' => '5', 'label' => '5/12'],
                ['value' => '6', 'label' => '6/12'],
                ['value' => '7', 'label' => '7/12'],
                ['value' => '8', 'label' => '8/12'],
                ['value' => '9', 'label' => '9/12'],
                ['value' => '10', 'label' => '10/12'],
                ['value' => '11', 'label' => '11/12'],
                ['value' => '12', 'label' => '12/12']
            ];
        }
        return $this->_options;
    }
}
