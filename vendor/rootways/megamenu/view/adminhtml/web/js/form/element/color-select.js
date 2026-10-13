define([
    'Magento_Ui/js/form/element/abstract',
    'mageUtils',
    'jquery',
    'jquery/colorpicker/js/colorpicker'
], function (Element, utils, $) {
    'use strict';

    return Element.extend({
        defaults: {
            visible: true,
            label: '',
            error: '',
            uid: utils.uniqueid(),
            disabled: false,
            links: {
                value: '${ $.provider }:data.megamenu_type_labelclr'
            }
        },

        initialize: function () {
            this._super();
            console.log(this.value);
            $(this).css("backgroundColor", "#000000");
        },

        initColorPickerCallback: function (element) {
            var self = this;

            $(element).ColorPicker({
                onSubmit: function(hsb, hex, rgb, el) {
                    self.value(hex);
                    $(el).ColorPickerHide();
                    $(element).css("backgroundColor", "#"+hex);
                },
                onBeforeShow: function () {
                    $(this).ColorPickerSetColor(this.value);
                },
                onChange: function(hsb, hex, rgb, el) {
                    self.value(hex);
                    $(el).ColorPickerHide();
                    $(element).css("backgroundColor", "#"+hex);
                },
            }).bind('keyup', function(){
                $(this).ColorPickerSetColor(this.value);
            });
        }
    });
});
