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
    "jquery",
    "Magento_Checkout/js/model/full-screen-loader",
    "mage/utils/wrapper",
    "Magento_Checkout/js/model/payment/additional-validators",
    "PayPal_Braintree/js/view/payment/adapter",
    "Magento_Checkout/js/model/payment/renderer-list",
    "Magento_Checkout/js/model/payment-service",
    "Magento_Checkout/js/action/select-payment-method",
    "Magento_Checkout/js/model/quote",
    "underscore",
    "ko"
    ],
    function (
        uiComponent,
        uiRegistry,
        jQuery,
        fullScreenLoader,
        wrapper,
        additionalValidators,
        Braintree,
        renderList,
        paymentService,
        selectPaymentMethod,
        quote,
        _,
        ko
    ) {
    "use strict";

    /*
     * loaded for 2.4 >
     */
    return uiComponent.extend({

        initialize: function () {

            /*
             * replacing the paypal method component it does not validate variables
             * while initializing the component and only way is to replace the module
             * with your own on the fly and fix the errors in your own uiCompoent
             * that just extends the original
             */
            _.filter(renderList(),  function(method) {
                if(method.type === "braintree_paypal") {
                    var wrappedMethod = method;
                    wrappedMethod.component = "Onestepcheckout_Iosc/js/view/payment/method-renderer/paypal-wrapper";
                    renderList.replace( method, wrappedMethod )
                }
            }.bind(this));

            /**
             * fixing vault method selection
             */
            paymentService.setPaymentMethods = wrapper.wrap(
                paymentService.setPaymentMethods, function(originalMethod, methods) {
                    var selectedMethod = quote.paymentMethod();
                    var parentMethodIsAvailable = false;
                    if(!_.isNull(selectedMethod)) {
                        parentMethodIsAvailable = methods.some(function (item) {
                            return selectedMethod.method.indexOf(item.method);
                        });
                    }

                    var result = originalMethod();
                    if(parentMethodIsAvailable) {
                        selectPaymentMethod(selectedMethod);
                    }

                    return result;
                }
            );
            this._super();
            uiRegistry.async("checkout.iosc.payments")(
                function (payments) {
                    if(typeof payments.getComponentButtonElem === 'function' ) {
                        payments.getComponentButtonElem = wrapper.wrap(
                            payments.getComponentButtonElem,
                            function(originalMethod, buttonElem) {
                                var button;

                                if(buttonElem.id === "braintree_paypal"){
                                    button = jQuery(buttonElem).parents(".payment-method")
                                        .find(".actions-toolbar:not([style*='display: none']) button.action.primary.checkout:not(.disabled)").first();

                                    if(button.length <= 0) {
                                        button = jQuery(buttonElem).parents(".payment-method")
                                            .find(".actions-toolbar:not([style*='display: none']) div[data-container='paypal-button']").first();
                                    }

                                    if(button.length <= 0) {
                                        button = jQuery(buttonElem).parents(".payment-method")
                                            .find(".actions-toolbar:not([style*='display: none']) div#braintree_paypal_placeholder").first();
                                    }

                                    if(button.length <= 0) {
                                        button = jQuery(buttonElem).parents(".payment-method")
                                            .find(".actions-toolbar:not([style*='display: none']) div.primary button.checkout").first();
                                    }

                                    return button
                                }
                                if(
                                    _.isUndefined(button) &&
                                    buttonElem.id.indexOf('braintree_cc_vault_') === 0
                                ){
                                    button = originalMethod(buttonElem);

                                    if(!_.isNull(quote.paymentMethod()) && quote.paymentMethod().method === buttonElem.id) {
                                        if(button.length <= 0) {
                                            button = jQuery('#iosc-summary > .iosc-place-order-container button').first();
                                            uiRegistry.async("checkout.steps.billing-step.payment.payments-list." + quote.paymentMethod().method)(
                                                function (vault) {
                                                    ko.applyBindings(vault, button[0]);
                                                    return button;
                                                }
                                            );
                                        }
                                    }

                                }

                                if(buttonElem.id === "braintree_local_payment"){
                                    button = jQuery(buttonElem).parents(".payment-method")
                                        .find(".actions-toolbar:not([style*='display: none']) div.primary button.checkout").first();

                                    return button
                                }

                                return originalMethod(buttonElem);
                            }
                        );
                    }
                    if(typeof payments.getComponentFromButton === 'function' ) {
                        payments.getComponentFromButton = wrapper.wrap(
                            payments.getComponentFromButton,
                            function(originalMethod, buttonElem) {
                                var component = originalMethod(buttonElem);
                                if(!_.isUndefined(component)
                                    && !_.isUndefined(component.modules)
                                    && !_.isUndefined(component.modules.hostedFields)
                                    && component.modules.hostedFields == "checkout.steps.billing-step.payment.payments-list.braintree"
                                ){
                                    component = uiRegistry.get(component.modules.hostedFields);
                                };
                                if(!_.isUndefined(component)
                                    && _.isUndefined(component.modules)
                                    && !_.isUndefined(component.method)
                                ){
                                    component = uiRegistry.get("checkout.steps.billing-step.payment.payments-list.braintree_local_payment");
                                };

                                return component;
                            }
                        );
                    }
                    if (typeof payments.getComponentButtonElem === 'function') {
                        payments.getComponentButtonElem = wrapper.wrap(payments.getComponentButtonElem, function(originalMethod, buttonElem) {
                            var el = originalMethod(buttonElem);
                            if (el.length <= 0 && (!_.isUndefined(buttonElem.id) && buttonElem.id == 'braintree_googlepay')) {
                                el = jQuery(buttonElem).parents(".payment-method").find("div#braintree-google-checkout-btn").first();
                            }
                            return el;
                        });
                    }

                    if (typeof payments.getButtonText === 'function') {
                        payments.getButtonText = wrapper.wrap(payments.getButtonText, function(originalMethod, buttonElem, methodScope) {
                            var button = buttonElem.get(0);
                            if (!_.isUndefined(button.id) && button.id === "braintree-google-checkout-btn") {
                                return;
                            }
                            return originalMethod(buttonElem, methodScope);
                        });
                    }
                    if (typeof payments.getComponentButtonElem === 'function') {
                        payments.getComponentButtonElem = wrapper.wrap(payments.getComponentButtonElem, function(originalMethod, buttonElem) {
                            var el = originalMethod(buttonElem);
                            if (el.length <= 0 && (!_.isUndefined(buttonElem.id) && buttonElem.id == 'braintree_applepay')) {
                                el = jQuery(buttonElem).parents(".payment-method").find("div#braintree-applepay-checkout-btn").first();
                            }
                            return el;
                        });
                    }

                    if (typeof payments.getButtonText === 'function') {
                        payments.getButtonText = wrapper.wrap(payments.getButtonText, function(originalMethod, buttonElem, methodScope) {
                            var button = buttonElem.get(0);
                            if (!_.isUndefined(button.id) && button.id === "braintree-applepay-checkout-btn") {
                                return;
                            }
                            return originalMethod(buttonElem, methodScope);
                        });
                    }
                    if (typeof payments.getComponentButtonElem === 'function') {
                        payments.getComponentButtonElem = wrapper.wrap(payments.getComponentButtonElem, function(originalMethod, buttonElem) {
                            var el = originalMethod(buttonElem);
                            if (el.length <= 0 && (!_.isUndefined(buttonElem.id) && buttonElem.id == 'braintree_applepay')) {
                                el = jQuery(buttonElem).parents(".payment-method").find("div#braintree-applepay-checkout-btn").first();
                            }
                            return el;
                        });
                    }


                }.bind(this)
            );
            uiRegistry.async("checkout.steps.billing-step.payment.payments-list.braintree_paypal")(
                function (braintreePayment) {
                    if(typeof braintreePayment.payWithPayPal === 'function' ) {
                        braintreePayment.payWithPayPal = wrapper.wrap(
                            braintreePayment.payWithPayPal,
                            function(originalMethod) {
                                if (!additionalValidators.validate()) {
                                    braintreePayment.enableButton();
                                    return;
                                }
                                setTimeout(function(){
                                    try {
                                        Braintree.checkout.paypal.initAuthFlow();
                                    } catch (e) {
                                        braintreePayment.messageContainer.addErrorMessage({
                                            message: jQuery.mage.__('Payment ' + braintreePayment.getTitle() + ' can\'t be initialized.')
                                        });
                                        braintreePayment.enableButton();
                                    }
                                }, 1000);
                            }.bind(braintreePayment)
                        );
                    }
                    if(typeof braintreePayment.getPlaceOrderDeferredObject === 'function' ) {
                        braintreePayment.getPlaceOrderDeferredObject = wrapper.wrap(
                            braintreePayment.getPlaceOrderDeferredObject,
                            function(originalMethod) {
                                fullScreenLoader.startLoader();
                                braintreePayment.isPlaceOrderActionAllowed(false);
                                var deferred = originalMethod();
                                fullScreenLoader.stopLoader();
                                fullScreenLoader.startLoader();
                                braintreePayment.isPlaceOrderActionAllowed(false);
                                deferred.fail(
                                    function () {
                                        fullScreenLoader.stopLoader();
                                        braintreePayment.isPlaceOrderActionAllowed(true);
                                    }
                                );
                                return deferred;
                            }
                        );
                    }
                    if(typeof braintreePayment.getShippingAddress === 'function' ) {
                        braintreePayment.getShippingAddress = wrapper.wrap(
                            braintreePayment.getShippingAddress,
                            function(originalMethod) {
                                var address = quote.shippingAddress();
                                var response = {};
                                if (address.postcode === null || typeof address.street === "undefined") {
                                    response = {};
                                } else {
                                    response = originalMethod();
                                }
                               return response;
                            }
                        );
                    }
                    if(typeof braintreePayment.reInitPayPal === 'function' ) {
                        braintreePayment.reInitPayPal = wrapper.wrap(
                            braintreePayment.reInitPayPal,
                            function(originalMethod) {
                                return setTimeout(function(){
                                    return originalMethod();
                                }, 1000);
                            }.bind(braintreePayment)
                        );
                    }
                }
            );
            uiRegistry.async({code:"braintree_cc_vault"})(
                function () {
                    uiRegistry.filter({code:"braintree_cc_vault"}).forEach(
                        function(comp) {
                            comp.getPaymentMethodNonce = wrapper.wrap(
                                comp.getPaymentMethodNonce,
                                function(originalMethod) {
                                    comp.hostedFields().messageContainer = comp.messageContainer;
                                    return originalMethod();
                                }
                            );
                        }
                    )
                }
            );
            uiRegistry.async("checkout.steps.billing-step.payment.payments-list.braintree")(
                function (braintreePayment) {

                    if(typeof braintreePayment.validateCardType === 'function' ) {
                        braintreePayment.validateCardType = wrapper.wrap(
                            braintreePayment.validateCardType,
                            function(originalMethod) {
                                var result = originalMethod();
                                if(result === false) {
                                    braintreePayment.isPlaceOrderActionAllowed(true)
                                }
                                return result;
                            }
                        );
                    }

                    if(typeof braintreePayment.getPlaceOrderDeferredObject === 'function' ) {
                        braintreePayment.getPlaceOrderDeferredObject = wrapper.wrap(
                            braintreePayment.getPlaceOrderDeferredObject,
                            function(originalMethod) {
                                braintreePayment.isPlaceOrderActionAllowed(false);
                                var deferred = originalMethod();
                                braintreePayment.isPlaceOrderActionAllowed(false);
                                return deferred;
                            }
                        );
                    }

                }.bind(this)
            );
        },

        getShippingAddress: function () {

            var line0, line1, address;

            address = quote.shippingAddress();

            if(!_.isUndefined(address.street) && _.isArray(address.street)) {
                line0 = !_.isUndefined(address.street[0]) ? address.street[0] : "";
                address.street.shift();
                line1 = address.street.slice(0, 2).filter(Boolean).join(' ');
            }

            if(!_.isUndefined(address.street) && _.isString(address.street)) {
                line0 = address.street;
            }

            return {
                recipientName: [address.firstname, address.lastname].filter(Boolean).join(' '),
                line1: line0,
                line2: line1,
                city: address.city,
                countryCode: address.countryId,
                postalCode: address.postcode,
                state: address.region
            };
        }
    });
});
