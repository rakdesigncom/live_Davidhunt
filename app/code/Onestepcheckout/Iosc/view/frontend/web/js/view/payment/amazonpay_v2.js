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
    "mage/utils/wrapper",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Customer/js/model/customer",
    "Magento_Customer/js/model/address-list",
    "Magento_Checkout/js/model/payment/renderer-list",
    "Magento_Ui/js/lib/view/utils/dom-observer",
    "Amazon_Pay/js/model/storage",
    "Magento_Checkout/js/model/full-screen-loader"
    ],
    function (
        uiComponent,
        uiRegistry,
        ko,
        jQuery,
        _,
        wrapper,
        quote,
        shippingService,
        customer,
        customerAddressList,
        rendererList,
        domObserver,
        amazonStorage,
        fullScreenLoader
    ) {
    "use strict";
    return uiComponent.extend({
        initialize: function () {
            this._super();
            
            this.billingElems = ko.observable();
            this.shippingElems = ko.observable();
            this.shippingElems.subscribe(
                function (value) {
                    this.controlShippingAddressElems(value);
                }.bind(this), null, 'change'
            );
            this.billingElems.subscribe(
                function (value) {
                    this.controlBillingAddressElems(value);
                }.bind(this), null, 'change'
            );

            if(amazonStorage.isAmazonCheckout()) {
                this.shippingElems(true);
                this.billingElems(true);
                this.initWrappers(true);
            }


        },

        initWrappers: function() {
            uiRegistry.async(
                {"component": "Amazon_Pay/js/view/shipping-address/list"}
            ) (
                function (addressList) {
                    if(amazonStorage.isAmazonCheckout()) {
                        var addressComponent = "Amazon_Pay/js/view/shipping-address/address-renderer/default";
                        addressList['rendererTemplates']
                                   ['customer-address']
                                   ['component'] = addressComponent;
                        addressList['rendererTemplates']
                                   ['new-customer-address']
                                   ['component'] = addressComponent;
                    }
                }.bind(this)
            );
            uiRegistry.async(
                {"component": "Amazon_Pay/js/view/billing-address"}
            ) (
                function (amznbAddress) {
                    if(amazonStorage.isAmazonCheckout()) {
                        amznbAddress.isAddressLoaded(true);
                        if(
                            amazonStorage.getRegion() !== 'us' ||
                            amazonStorage.getRegion() !== 'jp'
                        ) {
                            amznbAddress.isAddressFormVisible =
                            wrapper.wrap(amznbAddress.isAddressFormVisible,
                                function (originalMethod) {
                                    return true;
                                }
                            );
                        }

                    }
                }.bind(this)
            );

            uiRegistry.async(
                {"component": "Onestepcheckout_Iosc/js/billingfields"}
            ) (
                function (oscBaddress) {
                    if(amazonStorage.isAmazonCheckout()) {
                        if(
                            amazonStorage.getRegion() === 'us' ||
                            amazonStorage.getRegion() === 'jp'
                        ) {
                            oscBaddress.showOscBilling =
                            wrapper.wrap(oscBaddress.showOscBilling,
                                function (originalMethod) {
                                    return  false;
                                }
                            );
                        } else {
                            oscBaddress.showOscBilling =
                            wrapper.wrap(oscBaddress.showOscBilling,
                                function (originalMethod) {
                                    return true;
                                }
                            );
                        }
                    }
                }.bind(this)
            );
        },

        controlShippingAddressElems: function(state) {
            if(state){
                if(amazonStorage.isAmazonCheckout()) {
                    uiRegistry.async(
                        "checkout.steps.shipping-step.shippingAddress"
                    ) (
                        function (shippingAddressView) {
                            shippingAddressView.isNewAddressAdded = ko.observable(true);
                            shippingAddressView.isFormInline = false;
                            shippingAddressView.saveInAddressBook = 0;
                        }.bind(this)
                    );
                }
            }
        },

        controlBillingAddressElems: function(state) {
            if(state){
                uiRegistry.async(
                    "checkout.steps.shipping-step.iosc-billing-fields"
                ) (
                    function (billingAddressView) {
                        billingAddressView.shippingDomReady = function(val){ return false; };
                        billingAddressView.formObjects = ko.observable(false);
                    }.bind(this)
                );
            } else  {
                uiRegistry.async(
                    "checkout.steps.shipping-step.iosc-billing-fields"
                ) (
                    function (billingAddressView) {
                        billingAddressView.shippingDomReady = ko.observable(false);
                    }.bind(this)
                );
            }
        }
    });
});
