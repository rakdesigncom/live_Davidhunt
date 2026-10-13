<?php
/**
 * Mega Menu Dependency Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block\Adminhtml\Category\Helper;

class Dependency extends \Magento\Framework\Data\Form\Element\Select
{

    /**
     * Retrieve Element HTML fragment
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = parent::getElementHtml();
        $html .= ' <label for="vish" class="normal">' . __('Use Config Settings Vish') . '</label>';
        return $html;
    }
}
