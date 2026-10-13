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
define([
    'jquery',
    'underscore',
    'ko',
    'uiRegistry',
    'uiComponent',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Onestepcheckout_Iosc/js/shared/fields',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Ui/js/lib/view/utils/async',
    'mage/utils/wrapper',
    'bluebird'
], function (
    jQuery,
    _,
    ko,
    uiRegistry,
    uiComponent,
    customer,
    quote,
    addressConverter,
    additionalValidators,
    fieldsObj,
    createBillingAddress,
    domObserver,
    createShippingAddress,
    async,
    wrapper,
    Promise
) {
    "use strict";

    return uiComponent.extend({

        initialize: function () {
            this._super();
            var self = this;
            self.setUiComponentVariables().bind(self)
        },

        setAllFields: function () {
            var self = this;
            /*
             * to be executed in sync as promised in order
             */
            return self.getFirstVisiblePaymentmethod().bind(self).then(function(result) {
                    return self.getFormobjects(self.addressObjects(), self.formObjects());
                })
                .then(function(addressForm) {
                    return self.setFormobjects(addressForm);
                })
                .then(function(addressForm) {
                    return self.wrapFormobjects(addressForm);
                })
                .then(function(result) {
                    return self.getLastVisiblefield('div[name="' + self.lastVisibleField + '"]', self.formObjects());
                })
                .then(function(result) {
                    return self.getDomtarget('#iosc-billing-container');
                })
                .then(function(result) {
                    return self.applyDomChanges();
                })
                .then(function(result) {
                    return self.applyCssToFields(self.formObjects().dataScopePrefix, '#iosc-billing-container fieldset > div, #iosc-billing-container fieldset > fieldset');
                })
                .then(function(result) {
                    return self.setAjaxPrefilters();
                })
                .then(function(result) {
                    return additionalValidators.registerValidator(self.getValidator());
                })
                .finally(function() {
                })
                .catch( function(err) {
                    console.log("EERRR", err);
                });
        },

        /**
         * set variables to this.
         */
        setUiComponentVariables: function() {
            var self = this;
            return new Promise(function(resolve, reject) {

                var virtual = (quote.isVirtual()) ? quote.isVirtual() : self.cnf.open ;
                self.isUseBillingAddress = ko.observable(virtual);
                self.isBillingAddressDetailsVisible = ko.observable(false);
                self.isAddressDetailsVisible = ko.observable(false);
                self.formObjects = ko.observable(false);
                self.quote = quote;
                self.addressObjects = ko.observableArray([
                    "checkout.steps.billing-step.payment.afterMethods.billing-address-form"
                    ]);
                self.showOscBilling = ko.observable(false);
                resolve(self);
            });
        },

        /**
         * Get First visible payment method name to reference address fields from that method
         */
        getFirstVisiblePaymentmethod: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                uiRegistry.async({"parent": "checkout.steps.billing-step.payment.payments-list"})(
                    function (list) {
                        self.addressObjects.push(list.name + '-form');
                        resolve(self);
                    }
                );
            });
        },
        /**
         * get form Objects from uiRegistry
         * @param []
         * @param ko.observable()
         */
        getFormobjects: function(addressObjects, formObjects) {
            var self = this;
            var found = false;
            return new Promise(function(resolve, reject) {
                _.each(addressObjects,function(address) {
                    uiRegistry.async(address)(
                        function (addressForm) {
                            if (!formObjects) {

                                addressForm.isAddressDetailsVisible(false);
                                resolve(addressForm);

                            }
                        }
                    );
                });
            });
        },

        /**
         * set formObjects to context
         */
        setFormobjects: function(addressForm) {
            var self = this;
            return new Promise(function(resolve, reject) {
                self.formObjects(addressForm);

                resolve(addressForm);
            });
        },

        /**
         * Wrap formObjects methods to suitable state
         */
        wrapFormobjects: function(addressForm) {
            var self = this;
            return new Promise(function(resolve, reject) {
                self.formObjects().isAddressDetailsVisible =
                    wrapper.wrap(self.formObjects().isAddressDetailsVisible,
                        function (originalMethod) {
                            return  false;
                        }
                    );
                self.formObjects().updateAddresses =
                    wrapper.wrap(self.formObjects().updateAddresses,
                        function (originalMethod) {
                            return true;
                        }
                    );

                resolve(addressForm);
            });
        },

        /**
         * get the last dom element of uiComponent template from DOM, to say its rendered
         * @param string
         * @param ko.observable()
         */
        getLastVisiblefield: function(lastVisibleField, formObjects) {
            var self = this;
            return new Promise(function (resolve, reject) {
                async.async(
                    {
                        component: formObjects,
                        selector: lastVisibleField
                    },
                    function (node) {
                        resolve(node);
                    }.bind(self)
                );
            });
        },

        /**
         * get the last dom element of uiComponent template from DOM, to say its rendered
         * @param string
         * @param ko.observable()
         */
        getDomtarget: function(domTarget) {
            var self = this;
            return new Promise(function(resolve, reject) {
                async.async(
                    {
                        selector: domTarget
                    },
                    function(node) {

                        resolve(node);
                    }
                );
            });
        },

        /**
         * Mutate DOM
         */
        applyDomChanges: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                var billingAreaDom, billingContainer, target, addressFields, addressTarget,
                    cloneTarget, elemToCreate;

                billingAreaDom = jQuery('#iosc-billing-container').get(0);

                if (billingAreaDom) {

                    billingContainer = jQuery('#iosc-billing-container');
                    domObserver.get('#checkout-step-shipping', function (target) {
                        target = jQuery(target);
                        addressFields = jQuery('.checkout-billing-address > fieldset').get(0);
                        addressTarget =  jQuery("#rendermethis");
                        addressTarget.replaceWith(addressFields);

                        if (quote.isVirtual()) {
                            jQuery('#co-payment-form br').hide();

                            cloneTarget = jQuery('#shipping');
                            elemToCreate = document.createElement(cloneTarget.get(0).tagName);
                            target = jQuery(elemToCreate).attr("id", "iosc-billing");
                            target.append(billingContainer);
                            cloneTarget.before(target);
                            uiRegistry.async("checkout.steps.billing-step.payment.customer-email")(
                                function (customerEmail) {
                                    domObserver.get('li#payment form.form-login', function (elem) {
                                        var emailElem, billingAddressForm;
                                        emailElem = jQuery(elem).hide();
                                        billingAddressForm = jQuery('#iosc-billing .billing-address-form');
                                        billingAddressForm.before(emailElem);
                                        emailElem.show();
                                    });
                                }.bind(self)
                            );
                        } else {
                            target.append(billingContainer);
                        }
                        self.showOscBilling(true);

                        resolve(true);
                    }.bind(this));
                }

            }.bind(this));
        },

        /**
         * Apply OneStepCheckout specific css classes to DOM
         */
        applyCssToFields: function(dataScopePrefix, selector) {
            var self = this;
            return new Promise(function(resolve, reject) {
                fieldsObj.applyCssClassnames(
                    dataScopePrefix,
                    selector
                );
                if (!customer.isLoggedIn() && self.isUseBillingAddress()) {
                    var formObjects = self.formObjects();
                    var addressData = quote.billingAddress();
                    var addressFromData = formObjects.source.get(formObjects.dataScopePrefix);
                    if(
                        _.isObject(addressData) &&
                        _.isObject(addressFromData) &&
                        !_.isUndefined(addressData.countryId) &&
                        !_.isUndefined(addressFromData.country_id) &&
                        addressData.countryId != addressFromData.country_id
                    ) {
                        quote.billingAddress().countryId = addressFromData.country_id;
                    }
                }

                resolve(true);
            });
        },

        /**
         * Set ajax prefilters on needed methods
         */
        setAjaxPrefilters: function() {
            var self = this;
            return new Promise(function(resolve, reject) {
                uiRegistry.async('checkout.iosc.ajax')(
                    function (ajax) {
                        if (customer.isLoggedIn()) {
                            ajax.addMethod('params', 'address', self.paramsHandler.bind(self));
                        }

                        ajax.addMethod('params', 'billingAddress', self.paramsHandler.bind(self));
                        async.async(
                            {
                                selector: "#iosc-billing-container select[name$='_id'], #iosc-billing-container input[name='postcode']"
                            },
                            function(node) {

                                jQuery(node).change(ajax.update.bind(ajax));
                                resolve(node);
                            }
                        );

                    }.bind(self)
                );

            });
        },

        /**
        * show/hide billing address
        */
        showBillingAddress: function () {

            var self = this;
            var formObjects = self.formObjects();

            if (self.isUseBillingAddress()) {
                formObjects.isAddressSameAsShipping(false);
                formObjects.isAddressDetailsVisible(false);
                self.isBillingAddressDetailsVisible(true);
            } else {
                formObjects.isAddressSameAsShipping(true);
                formObjects.isAddressDetailsVisible(true);
                self.isBillingAddressDetailsVisible(false);
                formObjects.useShippingAddress();
            }

            uiRegistry.async("checkout.iosc.ajax")(
                function (ajax) {
                    ajax.update();
                }
            );

            return true;
        },

        /**
         *
         */
        paramsHandler: function () {

            var formObjects = this.formObjects();
            if (!formObjects.dataScopePrefix) {
                return false;
            }

            var addressData, newBillingAddress, formdata, checkoutProvider;
            if (_.isNull(quote.shippingAddress())) {
                quote.shippingAddress(createShippingAddress({}));
            }

            if (_.isNull(quote.billingAddress())) {
                quote.billingAddress(createBillingAddress({}));
            }
            if (!this.isUseBillingAddress() && !quote.isVirtual()) {
                if (customer.isLoggedIn()) {
                    addressData = quote.shippingAddress();
                    newBillingAddress = _.clone(addressData);
                } else {
                    checkoutProvider = uiRegistry.get('checkoutProvider');
                    if(
                        !_.isUndefined(checkoutProvider) &&
                        _.isUndefined(this.source)
                    )
                    {
                        this.source = checkoutProvider;
                    }
                    formdata = this.source.get('shippingAddress');
                    addressData = _.extend (
                        quote.shippingAddress(),
                        addressConverter.formAddressDataToQuoteAddress(formdata)
                    );
                    if (!_.isUndefined(formdata.extension_attributes)) {
                        addressData.extension_attributes = formdata.extension_attributes;
                    }
                    newBillingAddress = addressData;
                }
            } else {
                if (customer.isLoggedIn()) {
                    if (formObjects.selectedAddress() && _.isFunction(formObjects.selectedAddress().getCacheKey)) {
                        newBillingAddress = formObjects.selectedAddress();
                    } else {
                        addressData = formObjects.source.get(formObjects.dataScopePrefix);
                        newBillingAddress = createBillingAddress(addressData);
                    }
                } else {
                    addressData = formObjects.source.get(formObjects.dataScopePrefix);
                    newBillingAddress = createBillingAddress(addressData);
                }
            }
            if (customer.isLoggedIn()) {
                var saveInAddressBook = formObjects.saveInAddressBook();
                if (!this.isUseBillingAddress()) {
                    saveInAddressBook = 0;
                } else {
                    if(!_.isUndefined(newBillingAddress.customerAddressId)){
                        saveInAddressBook = 0;
                    }
                }
                saveInAddressBook = (saveInAddressBook == "1") ? 1 : 0;
                newBillingAddress.save_in_address_book  = saveInAddressBook;
                newBillingAddress.saveInAddressBook = saveInAddressBook;
                formObjects.saveInAddressBook(saveInAddressBook)
            }
            if(
                _.isUndefined(newBillingAddress.extension_attributes)
            ) {
                newBillingAddress.extension_attributes = {}
            }

            if (
                !_.isUndefined(addressData) &&
                !_.isNull(addressData)      &&
                !_.isUndefined(addressData.extension_attributes)
            ) {
                newBillingAddress.extension_attributes = _.extend (
                    addressData.extension_attributes,
                    newBillingAddress.extension_attributes,
                );
            }

            newBillingAddress.extension_attributes.isUseBillingAddress = this.isUseBillingAddress() ? "1" : "0";

            return newBillingAddress;
        },

        /**
         *
         */
        getValidator: function () {
            return {
                validate: this.validationHandler.bind(this)
            };
        },

        /**
         *
         */
        validationHandler: function () {
            if (!this.isUseBillingAddress() && !quote.isVirtual()) {
                if(
                    !_.isUndefined(this.formObjects())
                ) {
                    this.formObjects().isAddressSameAsShipping(true);
                    this.formObjects().useShippingAddress();
                }
                return true;
            }

            var isValid = false;
            var formObjects = this.formObjects();
            if (formObjects.selectedAddress() && typeof formObjects.selectedAddress().getCacheKey === "function") {
                isValid = true;
            } else {
                formObjects.source.set('params.invalid', false);
                formObjects.source.trigger(formObjects.dataScopePrefix + '.data.validate');

                if (formObjects.source.get(formObjects.dataScopePrefix + '.custom_attributes')) {
                    formObjects.source.trigger(formObjects.dataScopePrefix + '.custom_attributes.data.validate');
                }

                if (formObjects.source.get(formObjects.dataScopePrefix + ".extension_attributes")) {
                    formObjects.source.trigger(formObjects.dataScopePrefix + ".extension_attributes.data.validate");
                }

                if (!formObjects.source.get('params.invalid')) {
                    isValid = true;
                }
            }

            if (!isValid && _.isFunction(formObjects.focusInvalid)) {
                formObjects.focusInvalid();
            }

            return isValid;
        }

    });

});

