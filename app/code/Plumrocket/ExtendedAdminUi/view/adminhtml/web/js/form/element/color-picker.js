/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

/**
 * @deprecated added for magento 2.2
 * for magento >= 2.3 please use their color picker
 * @link https://devdocs.magento.com/guides/v2.4/ui_comp_guide/components/ui-colorpicker.html
 */
define([
    'mage/translate',
    'Magento_Ui/js/form/element/abstract',
    'pickr',
], function ($t, Abstract, Pickr) {
    'use strict';

    return Abstract.extend({

        defaults: {
            colorPickerConfig: {
                theme: 'monolith',
                useAsButton: false,
                comparison: false,
                swatches: [
                    'rgba(244, 67, 54, 1)',
                    'rgba(233, 30, 99, 0.95)',
                    'rgba(156, 39, 176, 0.9)',
                    'rgba(103, 58, 183, 0.85)',
                    'rgba(63, 81, 181, 0.8)',
                    'rgba(33, 150, 243, 0.75)',
                    'rgba(3, 169, 244, 0.7)',
                    'rgba(0, 188, 212, 0.7)',
                    'rgba(0, 150, 136, 0.75)',
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(139, 195, 74, 0.85)',
                    'rgba(205, 220, 57, 0.9)',
                    'rgba(255, 235, 59, 0.95)',
                    'rgba(255, 193, 7, 1)'
                ],

                components: {

                    // Main components
                    preview: true,
                    opacity: true,
                    hue: true,

                    // Input / output Options
                    interaction: {
                        hex: true,
                        rgba: true,
                        hsla: false,
                        hsva: false,
                        cmyk: false,
                        input: true,
                        clear: true,
                        save: false
                    }
                }
            }
        },

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic
         */
        initialize: function () {
            this._super();

            this.colorPickerConfig.default = this.value() ? this.value() : null;
            this.colorPickerConfig.disabled = this.disabled();
            this.colorPickerConfig.el = '#' + this.uid + '_color_picker';

            return this;
        },

        initColorPicker: function () {
            var self = this;
            var pickr = Pickr.create(self.colorPickerConfig);

            pickr.on('change', function (color, instance) {
                if (null === color) {
                    self.value('');
                } else {
                    self.value(color.toHEXA().toString());
                }
            });

            pickr.on('clear', function (instance) {
                self.value('');
            });

            self.value.subscribe(function (newValue) {
                if (newValue === '') {
                    pickr.setColor(null);
                } else {
                    pickr.setColor(newValue);
                }
            });

            return this;
        }
    });
});
