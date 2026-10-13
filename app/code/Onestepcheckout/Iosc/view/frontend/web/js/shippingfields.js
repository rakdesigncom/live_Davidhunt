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
        "ko",
        "jquery",
        "uiRegistry",
        "underscore",
        "Magento_Checkout/js/model/quote",
        'Magento_Checkout/js/model/address-converter',
        'Magento_Customer/js/model/customer',
        "Magento_Checkout/js/model/payment/additional-validators",
        "Onestepcheckout_Iosc/js/shared/fields",
        "Magento_Checkout/js/checkout-data"
    ],
    function (
        uiComponent,
        ko,
        jQuery,
        uiRegistry,
        _,
        quote,
        addressConverter,
        customer,
        additionalValidators,
        fieldsObj,
        checkoutData
    ) {
        "use strict";

        return uiComponent.extend({

            initialize: function () {
                this._super();

                this.shippingAddressView = false;
                this.sameAsBilling = ko.observable(0);
                this.skipListLengthValidation = ko.observable(false);

                this.domReady = ko.observable(false);
                this.dataScopePrefix = "shippingAddress";
                this.formObjects = false;
                this.source = false;
                fieldsObj.domReadyHandler(this.lastVisibleField, this);
                this.domReady
                    .subscribe(this.onDomReady.bind(this), null,"change");
                uiRegistry.async("checkout.iosc.ajax")(
                    function (ajax) {
                        ajax.addMethod("params", "shippingAddress", this.paramsHandler.bind(this));
                    }.bind(this)
                );
                if (!quote.isVirtual()) {
                    uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                        function (shippingAddressView) {
                            this.source = shippingAddressView.source;
                            this.shippingAddressView = shippingAddressView;
                            additionalValidators.registerValidator(this.getValidator());
                        }.bind(this)
                    );
                }
                uiRegistry.async("checkout.steps.shipping-step.iosc-billing-fields")(
                    function (billingAddressView) {
                        billingAddressView.isUseBillingAddress.subscribe(
                            function (value) {
                                this.sameAsBilling((value ? 0 : 1));
                            }.bind(this),
                            null,
                            'change'
                        );
                    }.bind(this)
                );
            },

            onDomReady: function () {
                fieldsObj.applyCssClassnames(
                    this.dataScopePrefix,
                    "#shipping-new-address-form>div, #shipping-new-address-form>fieldset"
                );
                if (!customer.isLoggedIn()) {
                    var addressData = quote.shippingAddress();
                    var addressFromData = checkoutData.getShippingAddressFromData();

                    if(
                        _.isObject(addressData) &&
                        _.isObject(addressFromData) &&
                        !_.isUndefined(addressData.countryId) &&
                        !_.isUndefined(addressFromData.country_id) &&
                        addressData.countryId != addressFromData.country_id
                    ) {
                        quote.shippingAddress().countryId = addressFromData.country_id;
                    }
                }
            },

            paramsHandler: function () {

                var addressData, newShippingAddress, formdata;

                if (_.isUndefined(this.source) || !this.source) {
                    return null;
                }
                addressData = quote.shippingAddress();
                if (
                    !customer.isLoggedIn() ||
                    (
                        customer.isLoggedIn() &&
                        customer.getShippingAddressList().length == 0
                    )
                ) {
                    formdata = this.source.get(this.dataScopePrefix);
                    newShippingAddress = _.extend (
                        addressData,
                        addressConverter.formAddressDataToQuoteAddress(formdata)
                    );
                    if (!_.isUndefined(formdata) && !_.isUndefined(formdata.extension_attributes)) {
                        newShippingAddress.extension_attributes = formdata.extension_attributes;
                    }
                } else {
                    newShippingAddress = addressData;
                }
                if (customer.isLoggedIn()) {
                    if (newShippingAddress.customerId) {
                        newShippingAddress.save_in_address_book = 0;
                    } else {
                        newShippingAddress.save_in_address_book = this.shippingAddressView.saveInAddressBook ? "1" : "0";
                    }
                    newShippingAddress.same_as_billing = this.sameAsBilling();
                }

                return newShippingAddress;
            },

            isFormInline : function () {
               return uiRegistry.get("checkout.steps.shipping-step.shippingAddress").isFormInline;
            },

            getValidator: function () {
                return {
                    name: "shippingAddressValidator",
                    validate: this.validationHandler.bind(this)
                };
            },

           validationHandler: function () {

               var isValid = false;
               var addressListIsValid = true;
               var addressList = uiRegistry.get("checkout.steps.shipping-step.shippingAddress.address-list").elems();
               var addressListLength = addressList.length;
               var self = this;
               if (addressListLength > 0 && !this.skipListLengthValidation()) {
                   self.isFormPopUpVisible = false;

                   uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                       function (shippingView) {
                           self.isFormPopUpVisible = shippingView.isFormPopUpVisible();
                       }.bind(self)
                   );

                   _.each(addressList, function (address) {
                       if (address.isSelected() && quote.shippingAddress() && quote.shippingAddress().getKey() === address.address().getKey()) {
                           isValid = true;
                           return false;
                       }
                   });

                   if (!isValid) {
                       uiRegistry.async("checkout.errors")(
                           function (errors) {
                               errors.messageContainer.clear();
                               errors.messageContainer.errorMessages.push(jQuery.mage.__("Please select shipping address by clicking on the address. If new address form is open, please close the form or add new address to the list."));
                           }
                       );

                       addressListIsValid = false;
                   }

                   if (self.isFormPopUpVisible) {
                       uiRegistry.async("checkout.errors")(
                           function (errors) {
                               errors.messageContainer.clear();
                               errors.messageContainer.errorMessages.push(jQuery.mage.__("If new address form is open, please close the form or add new address to the list."));
                           }
                       );
                       addressListIsValid = false;
                   }

                   if (!addressListIsValid) {
                       return false;
                   }
               }
               if (!isValid) {
                   this.source.set("params.invalid", false);
                   this.source.trigger(this.dataScopePrefix + ".data.validate");
                   if (this.source.get(this.dataScopePrefix + ".custom_attributes")) {
                       this.source.trigger(this.dataScopePrefix + ".custom_attributes.data.validate");
                   }
                   if (this.source.get(this.dataScopePrefix + ".extension_attributes")) {
                       this.source.trigger(this.dataScopePrefix + ".extension_attributes.data.validate");
                   }
                   if (!this.source.get("params.invalid")) {
                       isValid = true;
                   }
               }

               if (!isValid && _.isFunction(this.shippingAddressView.focusInvalid)) {
                   this.shippingAddressView.focusInvalid();
               }

               return isValid;
           }

        });

    }
);
