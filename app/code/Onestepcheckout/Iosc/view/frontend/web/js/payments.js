// phpcs:ignoreFile
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
        "ko",
        "Magento_Ui/js/lib/view/utils/dom-observer",
        "Magento_Checkout/js/model/payment-service",
        "Magento_Checkout/js/model/payment/method-converter",
        "Magento_Checkout/js/model/payment/method-list",
        "mage/utils/wrapper",
        "Magento_Ui/js/lib/knockout/template/renderer",
        "Magento_Ui/js/lib/knockout/template/loader"
    ],
    function (
        uiComponent,
        uiRegistry,
        _,
        jQuery,
        quote,
        ko,
        domObserver,
        paymentService,
        methodConverter,
        methodList,
        wrapper,
        renderer,
        loader
    ) {
        "use strict";

        /**
         * avoid mock values to be initialised to quote on frontend (out of OneStepCheckout control)
        */
        if(
            !_.isUndefined(window.checkoutConfig.shippingAddressFromData) &&
            _.isObject(window.checkoutConfig.shippingAddressFromData)
        ) {
            window.checkoutConfig.shippingAddressFromData = JSON.parse(
                JSON.stringify(
                    window.checkoutConfig.shippingAddressFromData
                ).replaceAll('\:\"-\"','\:\"\"')
            );
        }
        /**
         * fix of m2 issue where rate checking is done in template level and not on actual values in quote
         */
        renderer.render = wrapper.wrap(renderer.render , function (originalMethod, tmplPath) {
            var loadPromise = loader.loadTemplate(tmplPath);
            return loadPromise.then(renderer.parseTemplate.bind(renderer,tmplPath));
        });

        /**
         * fix for m2 issue where shipping method is selected only in UI level when only 1 rate is available
         */
        renderer.parseTemplate = wrapper.wrap(renderer.parseTemplate , function (originalMethod,tmplPath, html) {
            if (tmplPath === "Magento_Checkout/shipping") {
               html = html.replace("checked: $parent.rates().length == 1", "checked: $parent.isSelected");
            }
            if (tmplPath === "Amasty_ShippingTableRates/shipping") {
               html = html.replace("checked: $parent.rates().length == 1", "checked: $parent.isSelected");
            }
            if (tmplPath === "Magento_Checkout/shipping-address/shipping-method-item") {
                html = html.replace("element.rates().length == 1 || element.isSelected", "$parent.isSelected");
            }

            if (tmplPath === "Magento_Checkout/billing-address/details") {
                html = html.replace("currentBillingAddress().street.join(', ')", "_.values(currentBillingAddress().street).join(', ')");
            }
            if (tmplPath === "Magento_Checkout/payment") {
                // phpcs:disable Magento2.Html.HtmlClosingVoidTags
                html = html.replace(new RegExp("<br />", "g"), '');
                html = html.replace(' data-bind="fadeVisible: isVisible"', '');
            }
            if (tmplPath === "Magento_ReCaptchaCheckout/payment-recaptcha-container") {
                // phpcs:disable Magento2.Html.HtmlClosingVoidTags
                html = html.replace(new RegExp("<br />", "g"), '');

                html = html.replace(new RegExp("<hr />", "g"), ''); // phpcs:disable Magento2.Html.HtmlClosingVoidTags
            }

            return originalMethod(html);
        });

        return uiComponent.extend({

            defaults: {
                template: "Onestepcheckout_Iosc/place_order",
                displayArea: "summary"
            },

            initialize: function () {
                this._super();
                this.buttonIsMoved = false;
                this.parentObj = {};
                this.waitWithMove  = ko.observable(false);
                this.lastUpdated = false;
                uiRegistry.async("checkout.steps.billing-step.payment")(
                    function (paymentStep) {
                        paymentStep.isVisible = ko.observable(true);

                        if(
                            _.has(this.cnf, 'update_on_selection') &&
                            this.cnf['update_on_selection'] === '1'
                        ) {
                            domObserver.get(
                                '#checkout-payment-method-load',
                                function (elem) {
                                    jQuery(elem).click(function(event) {
                                        if(
                                            event.target &&
                                            event.target.name &&
                                            event.target.name == "payment[method]" &&
                                            this.lastUpdated != event.target
                                        ) {
                                            this.updateOnSelection();
                                            this.lastUpdated = event.target;
                                        }
                                    }.bind(this));
                                }.bind(this)
                            );
                        }
                    }.bind(this)
                );

                uiRegistry.async("vaultGroup")(
                    function (vaultGroup) {
                        var newAlias = 'default';
                        vaultGroup.alias = newAlias;
                        vaultGroup.displayArea = vaultGroup.displayArea.replace('vault', newAlias);
                    }.bind(this)
                );

                uiRegistry.async("checkout.iosc.ajax")(
                    function (ajax) {
                        this.ajax = ajax;
                        ajax.addMethod("success", "paymentMethod", this.successHandler.bind(this));
                        ajax.addMethod("error", "paymentMethod", this.errorHandler.bind(this));
                        quote.paymentMethod.subscribe(this.selectMethod.bind(this), null, "change");
                        if(!_.isNull(quote.paymentMethod()) && !_.isUndefined(quote.paymentMethod().method)) {
                            this.selectMethod(quote.paymentMethod());
                        }
                    }.bind(this)
                );

                uiRegistry.async("checkout.steps.billing-step.payment.payments-list")(
                    function (paymentListView) {
                        paymentListView.getGroupTitle = wrapper.wrap(paymentListView.getGroupTitle, function (originalMethod, group) {
                            return jQuery.mage.__("Payment Methods");
                        });
                    }
                );

                this.selectDefault();
                this.addTitleNumber();
            },

            /**
             * Observes payment method change
             *
             * @param observable
             */
            selectMethod: function (observable) {
                var placeOrderButton, permanentButton, methodScope,
                origin, targetOrigin;
                if(observable == null){
                    permanentButton = jQuery(".iosc-place-order-button").first();
                    targetOrigin =  permanentButton.parent();
                    placeOrderButton = jQuery("aside .iosc-place-order-button").first().clone(true);
                    this.restoreButton(placeOrderButton, permanentButton, targetOrigin);
                    return;
                }
                var observed = this.getObservableId(observable);
                domObserver.get(
                    observed,
                    function (elem) {
                        placeOrderButton = this.getComponentButtonElem(elem);
                        origin = placeOrderButton.parent();
                        permanentButton = jQuery(".iosc-place-order-button").first();
                        targetOrigin =  permanentButton.parent();
                        if (permanentButton.length && placeOrderButton.length && elem.checked) {
                            methodScope = this.getComponentFromButton(placeOrderButton[0]);
                            methodScope.isPlaceOrderActionAllowed(true);
                            this.ajax.addMethod("params", "paymentMethod", methodScope.getData.bind(methodScope));
                            if(!this.waitWithMove()){
                                this.moveAndRestoreButton(placeOrderButton, permanentButton, methodScope, origin, targetOrigin);
                            }
                        }
                        domObserver.off(observed);
                    }.bind(this)
                );
            },

            /**
             * update totals on selection if set from backend
             * @return void
             */
            updateOnSelection: function() {
                if(_.has(this.cnf, 'update_on_selection')) {
                    if(this.cnf['update_on_selection'] === '1'){
                        uiRegistry.async("checkout.iosc.ajax")(
                            function (ajax) {
                                ajax.update();
                            }
                        );
                    }
                }
            },

            /**
             * Get reference to uiComponent bound to payment method place order button
             */
            getComponentFromButton: function(buttonElem) {
                return ko.dataFor(buttonElem);
            },

            /**
             * Get reference to payment method place order button
             */
            getComponentButtonElem: function(buttonElem) {
                return jQuery(buttonElem).parents(".payment-method").find("button.action.primary.checkout:not(.disabled)").first();
            },

            /**
             * Get element id or selector out of object
             */
            getObservableId: function (observable){
                return "#" + observable.method;
            },

            /**
             * restore default place order button
             *
             * @param placeOrderButton
             * @param permanentButton
             * @param targetOrigin
             * @returns
             */
            restoreButton: function(placeOrderButton, permanentButton, targetOrigin) {
                if(permanentButton.length > 0){
                    permanentButton.replaceWith(placeOrderButton);
                    return;
                }
                targetOrigin.append(placeOrderButton);
            },

            /**
             * move button to right location and restore on demand
             */
            moveAndRestoreButton: function (placeOrderButton, permanentButton, methodScope, origin, targetOrigin) {

                if (!this.buttonIsMoved) {
                    permanentButton.remove();
                } else {
                    this.parentObj['prevOrign'].append(permanentButton);
                    permanentButton
                    .removeClass("iosc-place-order-button")
                    .removeClass(methodScope.index)
                    .prop("disabled", this.parentObj['prevButtonDisabled']);
                }
                placeOrderButton = this.prepButtonContent(placeOrderButton, methodScope);
                targetOrigin.append(placeOrderButton);
                this.parentObj['prevOrign'] = origin;
                this.parentObj['prevButtonText'] = placeOrderButton.text();
                this.parentObj['prevButtonDisabled'] = placeOrderButton.prop("disabled");
                this.buttonIsMoved = true;
            },

            /**
             * add attributes
             */
            prepButtonContent: function(placeOrderButton, methodScope) {
                placeOrderButton
                .addClass("iosc-place-order-button")
                .addClass(methodScope.index)
                .prop("disabled", false)

                if(this.getButtonText(placeOrderButton, methodScope) !== ''){
                    placeOrderButton
                    .text(this.getButtonText(placeOrderButton, methodScope));
                }
                return placeOrderButton;
            },

            /**
             * get button default text
             */
            getButtonText: function(placeOrderButton, methodScope) {
                return jQuery.mage.__("Place Order Now");
            },

            /**
             * select default payment method
             */
            selectDefault: function () {
                var defaultIfOne = false;
                if (window.checkoutConfig.paymentMethods.length === 1) {
                    this.cnf.methods = checkoutConfig.paymentMethods[0].method;
                    defaultIfOne = true;
                }

                if (this.cnf.methods !== null) {
                    uiRegistry.async("checkout.steps.billing-step.payment.payments-list." + this.cnf.methods)(
                        function (paymentMethod) {
                            if (defaultIfOne || !quote.paymentMethod() || quote.paymentMethod().method === paymentMethod.index) {
                                paymentMethod.selectPaymentMethod();
                            } else if (quote.paymentMethod() && typeof quote.paymentMethod().method !== "undefined") {
                                uiRegistry.async("checkout.steps.billing-step.payment.payments-list." + quote.paymentMethod().method)(
                                    function (newMethod) {
                                        newMethod.selectPaymentMethod();
                                    }
                                );
                            }
                        }
                    );
                } else {
                    if (quote.paymentMethod() && typeof quote.paymentMethod().method !=='undefined') {
                        this.selectMethod(quote.paymentMethod());
                    }
                }
            },

            /**
             *
             * @param data
             */
            successHandler: function (response) {
                var currentList = methodList();
                if (this._has(response, "data.paymentMethod.payment_methods")) {
                    if (!_.isEqual(methodList(), methodConverter(response.data.paymentMethod.payment_methods))) {
                        paymentService.setPaymentMethods(methodConverter(response.data.paymentMethod.payment_methods));
                    }
                }
                if (this._has(response, "data.paymentMethod.totals")) {
                    quote.setTotals(response.data.paymentMethod.totals);
                }
            },

            _has: function (obj, key) {
                return key.split(".").every(function (x) {
                    if (typeof obj !== "object" || obj === null || !(x in obj)) {
                        return false; }
                    obj = obj[x];
                    return true;
                });
            },

            /**
             *
             * @param data
             */
            errorHandler: function (data) {
            },

            addTitleNumber: function () {
                uiRegistry.async("checkout.steps.billing-step.payment")(
                    function (paymentStep) {
                        domObserver.get("div.payment-methods div.step-title", function (elem) {
                            var number = "3 ";
                            if (quote.isVirtual()) {
                                number = "2 ";
                            }
                            jQuery(elem).prepend(jQuery("<span class='title-number'><span>" + number + "</span></span>").get(0));
                        });
                    }
                );
            }

        });
    }
);
