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
    "ko",
    "jquery",
    "underscore",
    "Magento_Checkout/js/model/full-screen-loader",
    "mage/utils/wrapper",
    "Magento_Checkout/js/model/quote"
    ],
    function (
        uiComponent,
        uiRegistry,
        ko,
        jQuery,
        _,
        fullScreenLoader,
        wrapper,
        quote
) {
    "use strict";

    return uiComponent.extend({
        initialize: function () {
            this._super();

            uiRegistry.async("checkout.iosc.payments")(
                function (payments) {
                    if (_.isFunction(payments.getObservableId)) {
                        payments.getObservableId = wrapper.wrap(payments.getObservableId, function(originalMethod, observable) {
                            var el;
                            if(
                                observable.method === 'adyen_hpp' &&
                                !_.isUndefined(observable.additional_data) &&
                                !_.isUndefined(observable.additional_data.brand_code)
                            ) {
                                el = "#adyen_" + observable.additional_data.brand_code;
                            } else {
                                el = originalMethod(observable);
                            }
                            return el;
                        });
                    }
                    if (_.isFunction(payments.getComponentFromButton)) {
                        payments.getComponentFromButton = wrapper.wrap(payments.getComponentFromButton, function(originalMethod, buttonElem) {
                            var component = originalMethod(buttonElem);
                            if (!_.isFunction(component.isPlaceOrderActionAllowed)) {
                                component.isPlaceOrderActionAllowed = ko.observable(true);
                            }

                            if (!_.isFunction(component.getData)) {
                                component.$parent = ko.contextFor(buttonElem).$parent;
                                component.getData = function () {
                                    var innerSelf = this.$parent;
                                    var data = {};
                                    data.method = component.method;

                                    var additionalData = {};
                                    additionalData.brand_code = innerSelf.selectedAlternativePaymentMethodType();

                                    let stateData;
                                    if ('component' in innerSelf) {
                                        stateData = innerSelf.component.data;
                                    } else {
                                        if (innerSelf.paymentMethod.methodGroup === innerSelf.paymentMethod.methodIdentifier){
                                            stateData = {
                                                paymentMethod: {
                                                    type: innerSelf.selectedAlternativePaymentMethodType(),
                                                },
                                            };
                                        } else {
                                            stateData = {
                                                paymentMethod: {
                                                    type: innerSelf.paymentMethod.methodGroup,
                                                    brand: innerSelf.paymentMethod.methodIdentifier
                                                }
                                            };
                                        }

                                    }

                                    additionalData.stateData = JSON.stringify(
                                        stateData);

                                    if (innerSelf.selectedAlternativePaymentMethodType() ==
                                        'ratepay') {
                                        additionalData.df_value = innerSelf.getRatePayDeviceIdentToken();
                                    }

                                    data.additional_data = additionalData;

                                    return data;

                                }.bind(component);
                            }
                            return component;
                        });
                    }

                    if (_.isFunction(payments.getComponentButtonElem)) {
                        payments.getComponentButtonElem = wrapper.wrap(payments.getComponentButtonElem, function(originalMethod, buttonElem) {
                            var bttn = null;
                            if(!_.isUndefined(buttonElem.id) && buttonElem.id.includes('adyen_')) {

                                var context  = ko.dataFor(buttonElem);
                                if(!_.isUndefined(context.paymentMethod)) {
                                    var target = 'adyen_' + context.paymentMethod.methodIdentifier;
                                    var targetCss = context.method + '_' + context.paymentMethod.methodIdentifier;
                                    if(!_.isUndefined(buttonElem.id) && buttonElem.id == target) {
                                        var isContainer = jQuery(buttonElem).parents(".payment-method").find("fieldset#payment_fieldset_" + targetCss + " > div:not(.disabled)").children().length;
                                        if(isContainer > 0) {
                                            bttn = jQuery(buttonElem).parents(".payment-method").find("fieldset#payment_fieldset_" + targetCss + ":not(.disabled)").first();
                                        }
                                    }
                                }
                            }
                            return (!_.isNull(bttn)) ? bttn : originalMethod(buttonElem);
                        });
                    }

                    if (_.isFunction(payments.getButtonText)) {
                        payments.getButtonText = wrapper.wrap(payments.getButtonText, function(originalMethod, placeOrderButton, methodScope) {
                            var bttn = true;

                            if(!_.isUndefined(methodScope.paymentMethod)) {
                                var target = 'adyen_' + methodScope.paymentMethod.methodIdentifier;
                                var targetCss = methodScope.method + '_' + methodScope.paymentMethod.methodIdentifier;
                                if(!_.isUndefined(placeOrderButton.context) && !_.isUndefined(placeOrderButton.context.id) && placeOrderButton.context.id == target) {
                                    var isContainer = jQuery(placeOrderButton).parents(".payment-method").find("fieldset#payment_fieldset_" + targetCss + " > div:not(.disabled)").children().length;
                                    if(isContainer > 0) {
                                        bttn = false;
                                    }
                                }
                            }

                            return (!bttn) ? '' : originalMethod(placeOrderButton, methodScope);
                        });
                    }

                    uiRegistry.async("checkout.steps.billing-step.payment.payments-list.adyen_hpp")(
                        function (payment) {
                            fullScreenLoader.stopLoader();
                            payment.isPlaceOrderActionAllowed(true);

                            if (_.isFunction(payment.getAdyenHppPaymentMethods)) {
                                payment.getAdyenHppPaymentMethods = wrapper.wrap(payment.getAdyenHppPaymentMethods, function(originalMethod, paymentMethodsResponse) {
                                    var result = originalMethod(paymentMethodsResponse);
                                    var target = false;
                                    var exists = false;
                                    if(_.isArray(result) && result.length > 0 && !_.isNull(quote.paymentMethod())) {
                                        var target = 'adyen_' + quote.paymentMethod().additional_data.brand_code;
                                        var exists = _.find(result, function(res){ return res.item.method == quote.paymentMethod().additional_data.brand_code; });
                                    }
                                    if(exists) {
                                        setTimeout(function() {
                                            jQuery('#'+ target).prop('checked', true);
                                            payments.selectMethod(quote.paymentMethod());
                                        }, 200);
                                    } else {
                                        payments.selectMethod(null);
                                    }
                                    return result;
                                });
                            }

                            if (_.isFunction(payment.getFormattedAddress)) {
                                payment.getFormattedAddress = wrapper.wrap(payment.getFormattedAddress, function(originalMethod, address) {
                                    address = _.isUndefined(address) ? {} : address;
                                    address.street = _.isUndefined(address.street) ? [] : address.street;
                                    return originalMethod(address);
                                });
                            }

                        }.bind(this)
                    );

                    uiRegistry.async("checkout.steps.billing-step.payment.payments-list.adyen_cc")(
                        function (payment) {
                            fullScreenLoader.stopLoader();
                            payment.isPlaceOrderActionAllowed(true);
                            payment.placeOrderAllowed(true);

                            if (_.isFunction(payment.isButtonActive)) {
                                payment.isButtonActive = wrapper.wrap(payment.isButtonActive, function(originalMethod) {
                                    return true;
                                });
                            }
                        }.bind(this)
                    );

                }.bind(this)
            );

        }
    });
});
