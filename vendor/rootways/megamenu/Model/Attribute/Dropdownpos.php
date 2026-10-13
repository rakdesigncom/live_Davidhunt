<?php
/**
 * Mega Menu Dropdownpos Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Attribute;

class Dropdownpos extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        $options = [];
        if (!$this->_options) {
            $options = [
                ['value' => '0', 'label' => __('Right - Default')],
                ['value' => '1', 'label' => __('Left')]
            ];

            $this->_options = $options;
        }
        return $options;
    }
}
