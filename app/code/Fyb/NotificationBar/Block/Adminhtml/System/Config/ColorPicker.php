<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Notification Bar for Magento 2
 */

namespace Fyb\NotificationBar\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ColorPicker extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('readonly', 1);
        $html = $element->getElementHtml();
        $value = $element->getData('value');

        $html .= '<script type="text/javascript">
            require(["jquery","jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var element = $("#' . $element->getHtmlId() . '");
                    element.css("backgroundColor", "' . $value . '");
                    element.ColorPicker({
                        color: "' . $value . '",
                        onChange: function (hsb, hex, rgb) {
                            element.css("backgroundColor", "#" + hex).val("#" + hex);
                        },
                        onHide: function (colpkr) {
                            element.trigger("change");
                            return true;
                        },
                    }).keyup(function(){
                       $(this).ColorPickerSetColor(this.value);
                    });
                });
            });
            </script>';

        return $html;
    }
}
