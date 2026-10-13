<?php
/**
 * Mega Menu Color Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block\System\Config\Form\Field;

use Magento\Framework\Registry;

class Color extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * Get HTML
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $element->getElementHtml();
        $cpPath = $this->getViewFileUrl('Rootways_Megamenu::js/jscolor.min.js');
        $imgPath = $this->getViewFileUrl('Rootways_Megamenu::images/color.png');
        if (!$this->_coreRegistry->registry('colorpicker_loaded')) {
            $html .= '<script type="text/javascript" src="'. $cpPath .'"></script><style type="text/css">input.jscolor { background-image: url('.$imgPath.') !important; background-position: calc(100% - 8px) center; background-repeat: no-repeat; padding-right: 44px !important; } input.jscolor.disabled,input.jscolor[disabled] { pointer-events: none; }</style>';
            $this->_coreRegistry->registry('colorpicker_loaded', 1);
        }
        $html .= '<script type="text/javascript">
                var el = document.getElementById("'. $element->getHtmlId() .'");
                el.className = el.className + " jscolor";
            </script>';
        return $html;
    }
}
