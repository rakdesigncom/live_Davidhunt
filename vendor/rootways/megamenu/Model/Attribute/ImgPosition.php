<?php
/**
 * Mega Menu Image Position Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */


namespace Rootways\Megamenu\Model\Attribute;

class ImgPosition extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => '0', 'label' => __('Before Title')],
                ['value' => '1', 'label' => __('After Title')]
            ];
        }
        return $this->_options;
    }
}
