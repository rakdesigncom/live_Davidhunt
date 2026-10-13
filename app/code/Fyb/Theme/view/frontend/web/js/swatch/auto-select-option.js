define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/smart-keyboard-handler',
    'mage/translate',
    'priceUtils',
    'jquery-ui-modules/widget',
    'jquery/jquery.parsequery',
    'mage/validation/validation'
], function ($, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    return function (targetWidget) {
        $.widget('mage.SwatchRenderer', targetWidget, {
            _init: function () {
                this.options.numberToShow = 21;
                this.options.moreButtonText = '...';

                this._super();
            },

            _selectSingleOptions: function() {
                var $widget = this,
                    options = this.options.classes;
                var productId = $widget.getProduct();

                if (!productId) {
                    $.each($('.' + options.attributeOptionsWrapper), function (_, wrapperElem) {
                        var optionElems = $(wrapperElem).find('.' + options.optionClass);

                        $widget._OnClick($(optionElems[0]), $widget);
                    });
                }
            },

            _OnChange: function ($this, $widget) {
                if (!this.inProductList) {
                    var $parent = $this.parents('.' + $widget.options.classes.attributeClass),
                        $input = $parent.find('.' + $widget.options.classes.attributeInput);

                    $input.on('change', function () {
                        $widget._updateProductData();
                    });
                }

                this._super($this, $widget);
            },

            _OnClick: function ($this, $widget) {
                if (!this.inProductList) {
                    if ($this.hasClass('selected')) {
                        return;
                    }

                    var $parent = $this.parents('.' + $widget.options.classes.attributeClass),
                        $input = $parent.find('.' + $widget.options.classes.attributeInput);
                    var productId = $widget.getProduct();

                    $input.on('change', function () {
                        $widget._updateProductData();
                    });
                }
                this._super($this, $widget);
            },

            _updateProductData: function () {
                var $widget = this,
                    options = this.options.jsonConfig,
                    optionClasses = this.options.classes,
                    queryUrl = {};

                if (typeof options.confData === 'object') {
                    var productId = $widget.getProduct();
                    if (productId) {
                        $('.product.attribute.sku .value').html(options.sku[productId]);
                        $('.special-delivery-sku').html(options.sku[productId]);
                        $('.page-title span').text(options.confData[productId].title);
                        $('.breadcrumbs .items .item').last().text(options.confData[productId].title);
                    } else {
                        $('.product.attribute.sku .value').text(options.baseConfData.sku);
                        $('.special-delivery-sku').html(options.baseConfData.sku);
                        $('.page-title span').text(options.baseConfData.title);
                        $('.breadcrumbs .items .item').last().text(options.baseConfData.title);
                    }
                }
            },

            _RenderControls: function () {
                $.each(this.options.jsonConfig.attributes, function (key, item) {
                    if (item.label === 'Finishes') {
                        item.label = 'Finish';
                        return false;
                    }
                });

                this._super();

                if (!this.options.useAjax) {
                    this._selectSingleOptions();
                }
            },
        });

        return $.mage.SwatchRenderer;
    };
})
