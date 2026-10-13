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
        "uiRegistry",
        "underscore",
        "jquery",
        "Magento_InventoryInStorePickupFrontend/js/view/store-pickup",
        "Magento_Checkout/js/checkout-data",
        "Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service",
        "Magento_Checkout/js/action/create-shipping-address",
        "Magento_Checkout/js/model/payment/additional-validators",
        "mage/utils/wrapper"
    ],
function (
    uiRegistry,
    _,
    jQuery,
    storePickup,
    checkoutData,
    pickupLocationsService,
    createShippingAddress,
    additionalValidators,
    wrapper
) {
    "use strict";
    return storePickup.extend({
        initialize : function () {
            this._super();

            var location = pickupLocationsService.getSelectedPickupAddress();
            if(!_.isNull(location)){
                pickupLocationsService.selectForShipping(location);
            }
            uiRegistry.async("checkout.iosc.shippingfields")(
                function (shippingfields) {

                    var validator = false;
                    validator = _.find(
                        additionalValidators.getValidators(),
                        function(validator){
                            return !_.isUndefined(validator.name) && validator.name === "shippingAddressValidator";
                        }
                    );

                    if(validator) {
                        validator.validate = wrapper.wrap( validator.validate , function (originalMethod) {
                            var result = true
                            if(!this.isStorePickupSelected()) {
                                result = originalMethod();
                            }
                            return result;
                        }.bind(this));
                    }
                }.bind(this)
            );
            uiRegistry.async("checkout.iosc.ajax")(
                function (ajax) {
                    ajax.paramsMethods.shippingAddress = wrapper.wrap( ajax.paramsMethods.shippingAddress , function (originalMethod) {
                        var result = checkoutData.getSelectedPickupAddress();
                        if(!this.isStorePickupSelected()) {
                            result = originalMethod();
                        }
                        return result;
                    }.bind(this));
                }.bind(this)
            );
            this.isStorePickupSelected.extend({ notify: "always" });
            this.onStorePickupSelected(this.isStorePickupSelected());
            this.isStorePickupSelected.subscribe(function (status) {
                this.onStorePickupSelected(status);
            }, this);

            additionalValidators.registerValidator(this.getValidator());

        },

        onStorePickupSelected: function (status) {
            this.toggleBillingAddress(status);
        },

        toggleBillingAddress: function(status) {
            uiRegistry.async("checkout.steps.shipping-step.iosc-billing-fields")(
                function (billingAddress) {
                    if(status) {
                        var address = checkoutData.getSelectedPickupAddress();
                        if(
                            this.isStorePickupSelected() &&
                            (
                                _.isNull(address) ||
                                _.isUndefined(address.extension_attributes.pickup_location_code) ||
                                (_.isFunction(address.getType) && address.getType() !== "store-pickup-address")
                            )
                        ) {
                            pickupLocationsService.selectedLocation(null);
                        }
                        billingAddress.isUseBillingAddress(status);
                    }
                }.bind(this)
            );

            uiRegistry.async("checkout.steps.shipping-step.shippingAddress")(
                function (shippingAddress) {
                    uiRegistry.async("checkout.steps.shipping-step.shippingAddress.iosc-shippingaddress-button")(
                        function (button) {
                            if(status && shippingAddress.isFormPopUpVisible()) {
                                button.showFormPopUp();
                            }
                        }.bind(this)
                    );
                }.bind(this)
            );

        },

        getValidator: function () {
                return {
                    name: "pickupValidator",
                    validate: this.validationHandler.bind(this)
                };
            },

        validationHandler: function () {
            var result = true;
            var address = checkoutData.getSelectedPickupAddress();

            if(
                this.isStorePickupSelected() &&
                (
                    _.isNull(address) ||
                    _.isUndefined(address.extension_attributes.pickup_location_code)
                )
            ) {
                pickupLocationsService.selectedLocation(null);
                jQuery("#store-selector").addClass("invalid");
                result = false;
            } else {
                jQuery("#store-selector").removeClass("invalid");
            }

            return result;
        }

    });

});
