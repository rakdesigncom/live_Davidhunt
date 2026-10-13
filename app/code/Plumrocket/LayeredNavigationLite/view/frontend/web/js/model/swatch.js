/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2016 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'underscore',
    'jquery'
], function (_, $) {
    "use strict";

    return {
        options: {
            swatchLinkClass: 'swatch-option-link-layered',
            swatchAttributeContainer: 'swatch-attribute',
            swatchOptionClass: 'swatch-option'
        },

        /**
         * Check if item is a configurable swatch.
         *
         * @param {HTMLElement} item
         * @return {Boolean}
         */
        isSwatch: function (item) {
            return item.classList.contains(this.options.swatchLinkClass)
                || item.classList.contains(this.options.swatchOptionClass);
        },

        /**
         * @param {HTMLElement} item
         * @return {{variable: null|string, value: null|string}}
         */
        getItemRequest: function (item) {
            var request = {variable: null, value: null};
            if (this.isSwatch(item)) {
                var optionContainer = $(item).parents('.' + this.options.swatchAttributeContainer)[0];
                if (optionContainer.dataset.attributeCode) {
                    request.variable = optionContainer.dataset.attributeCode;
                } else { // for magento 2.3 and older
                    request.variable = optionContainer.getAttribute('attribute-code');
                }

                var swatchOption = this.getSwatchOption(item);
                if (swatchOption.dataset.optionId) {
                    request.value = swatchOption.dataset.optionId;
                } else { // for magento 2.3 and older
                    request.value = swatchOption.getAttribute('option-id');
                }
            }
            return request;
        },

        getSwatchOption: function (item) {
            return item.classList.contains(this.options.swatchOptionClass)
                ? item
                : item.querySelector('.' + this.options.swatchOptionClass);
        },

        /**
         * @param {HTMLElement} item
         * @return {boolean}
         */
        toggleSelected: function (item) {
            var swatch = this.getSwatchOption(item);
            swatch.classList.toggle('selected');
            return swatch.classList.contains('selected');
        },

        /**
         * This function is the analog of swatches function.
         * Look to SwatchRender widget to method _EmulateSelected.
         *
         * @param params
         * @param noForce
         */
        emulateSelected: function (params, noForce) {
            var updateProductList = true;
            var $productItemDetails = $('.product-item-details');

            if (noForce && $productItemDetails.find('.swatch-attribute-options .swatch-option.selected').length > 0) {
                updateProductList = false;
            }

            var attributeClass = $.mage.SwatchRenderer
                ? $.mage.SwatchRenderer.prototype.options.classes.attributeClass
                : 'swatch-attribute';

            var $filterContainer = $('#narrow-by-list');

            $filterContainer.find('.swatch-layered .swatch-option').removeClass('selected');
            if (updateProductList) {
                $productItemDetails.find('.swatch-attribute-options .swatch-option').removeClass('selected');
            }

            _.each(params, $.proxy(function (optionIds, attributeCode) {
                _.each(optionIds, function (value) {
                    if (updateProductList) {
                        $productItemDetails.find('.' + attributeClass +
                            '[attribute-code="' + attributeCode + '"] [option-id="' + value + '"]').trigger('click');
                        $productItemDetails.find('.' + attributeClass +
                            '[data-attribute-code="' + attributeCode + '"] [data-option-id="' + value + '"]').trigger('click');
                    }

                    /** First selector for magento 2.0-2.3, second for 2.4 */
                    var selectors = '.swatch-layered[attribute-code="' + attributeCode + '"] [option-id="' + value + '"], '
                        + '.swatch-layered[data-attribute-code="' + attributeCode + '"] [data-option-id="' + value + '"]'

                    $filterContainer.find(selectors).addClass('selected');
                    $filterContainer.find(selectors).parent().addClass('selected');
                });
            }, this));
        },

        /**
         * Callback for after filter.
         *
         * @param {{}} data
         */
        afterFilterCallback: function (data) {
            var self = this;
            $('.swatch-option-tooltip').hide();
            setTimeout(function () {
                if (self && data.variables) {
                    self.emulateSelected(data.variables, true);
                }
            }, 1000);
        },
    };
});
