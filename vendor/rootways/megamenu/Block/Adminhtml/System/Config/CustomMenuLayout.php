<?php
/**
 * Mega Menu CustomMenuCategory Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block\Adminhtml\System\Config;

/**
 * CustomMenuLayout
 */
class CustomMenuLayout extends \Magento\Framework\View\Element\Html\Select
{
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->addOption('', '-- Select --');
            $this->addOption('1', 'Full Width');
            $this->addOption('2', 'Half - Right');
            $this->addOption('3', 'Half - Left');
            $this->addOption('4', 'Dropdown - Right');
            $this->addOption('5', 'Dropdown - Left');
        }

        return parent::_toHtml();
    }
    
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
