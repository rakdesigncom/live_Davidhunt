/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
define(
    [
        "uiComponent",
        "uiRegistry",
        "underscore",
        "jquery",
        "Magento_Checkout/js/model/quote",
        "Magento_Checkout/js/model/shipping-save-processor",
        "Magento_Checkout/js/model/shipping-service"
    ],
function (
    uiComponent,
    uiRegistry,
    _,
    jQuery,
    quote,
    shippingSaveProcessor,
    shippingService
) {
    "use strict";
    return uiComponent.extend({
        initialize : function () {
            this._super();
            this.initPrefilters();
        },

        initPrefilters: function() {

            jQuery.ajaxPrefilter(
                function ( options, localOptions, jqXHR ) {

                    var mergeJson = function (target, add) {
                        for (var key in add) {
                            if (add.hasOwnProperty(key)) {
                                if (target[key] && isObject(target[key]) && isObject(add[key])) {
                                mergeJson(target[key], add[key]);
                                } else if (typeof target[key] === "undefined" || target[key] === null) {
                                    target[key] = add[key];
                                }
                            }
                        }
                        return target;
                    };

                    var isObject = function (obj) {
                        if (typeof obj === "object") {
                            for (var key in obj) {
                                if (obj.hasOwnProperty(key)) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    };
                    var allowed  = ["/guestrequest-option-rates"];
                    var methods = ["post"];
                    var matches = _.filter(
                        allowed,
                        function (seek) {
                            return options.url.indexOf(seek) !== -1;
                        }
                    );

                    if (matches.length > 0 && methods.indexOf(options.type.toLowerCase()) >= 0) {
                        options.async = false;
                        jqXHR.then(
                            function(data) {
                                uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                                    function (shippingAddress) {
                                        if(!_.isUndefined(data) && !_.isUndefined(data['0'])) {
                                            shippingAddress.rates().splice(shippingAddress.rates().findIndex(e => e.carrier_code === data['0'].carrier_code),1);
                                            shippingAddress.rates().push(data['0']);
                                            shippingService.setShippingRates(rates);
                                        }
                                        shippingSaveProcessor.saveShippingInformation();
                                    }.bind(this)
                                );
                            }
                        );
                    }
                    var allowed  = ["shipperhq_option/checkout/loadConfig"];
                    var methods = ["get"];
                    var matches = _.filter(
                        allowed,
                        function (seek) {
                            return options.url.indexOf(seek) !== -1;
                        }
                    );

                    if (matches.length > 0 && methods.indexOf(options.type.toLowerCase()) >= 0) {
                        options.async = false;
                    }
                    var allowed  = ["/shipping-information", ];
                    var methods = ["post"];
                    var matches = _.filter(
                        allowed,
                        function (seek) {
                            return options.url.indexOf(seek) !== -1;
                        }
                    );

                    if (matches.length > 0 && methods.indexOf(options.type.toLowerCase()) >= 0) {
                        var existingData = false;
                        var newData = this.getShqOptions();
                        if (typeof localOptions.data === "string") {
                            try {
                                existingData = JSON.parse(localOptions.data);
                            } catch (e) {
                            }
                        } else {
                            existingData = localOptions.data;
                        }

                        if (!existingData || _.isObject(existingData)) {
                            var mergedData = mergeJson(newData, existingData);
                            options.data = JSON.stringify(mergedData);
                        }
                    }

                }.bind(this)
            );
        },

        getShqOptions: function () {

            var shippingAddress = quote.shippingAddress();
            var shippingMethod = quote.shippingMethod();
            var carrierCode = (!_.isNull(shippingMethod) && !_.isUndefined(shippingMethod.carrier_code)) ? shippingMethod.carrier_code : '';
            var isCustomerAccount = carrierCode.indexOf('account') !== -1 ;

            if (!_.isNull(shippingAddress) && _.isUndefined(shippingAddress.extension_attributes)) {
                shippingAddress['extension_attributes'] = {};
            }
            var returnValues = {
                'destination_type' : '',
                'inside_delivery' : '0',
                'liftgate_required' : '',
                'limited_delivery' : '0',
                'notify_required': '',
                'customer_carrier': '',
                'customer_carrier_ph': '',
                'customer_carrier_account': ''
            };

            var customerAccountFields = [
                'customer_carrier',
                'customer_carrier_ph',
                'customer_carrier_account'
            ];

            jQuery("[name=shipperhq-option]").each(function () {
                var code = this.id.replace('shipperhq_', '');
                var value = this.value;
                var thisIsACustomerAccountField = customerAccountFields.indexOf(code) > -1;
                if (this.type == 'checkbox') {
                    value = (this.checked ? "1" : "0");
                }
                if(thisIsACustomerAccountField) {
                    if(isCustomerAccount) {
                        if(value === '') {
                        }
                        returnValues[code] = value;
                    }
                }
                else {
                    if (value !== '') {
                        returnValues[code] = value;
                    }
                }
            });

            return {
                addressInformation: {
                    shipping_address: {
                        extension_attributes: {
                            shipperhq_option_values: returnValues
                        }
                    }
                }
            };
        }
    });

});
