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
    "PayPal_Braintree/js/view/payment/method-renderer/paypal",
    "Magento_Checkout/js/model/quote"
    ],
    function ( originalMethodRenderer, quote) {
        "use strict";

        return originalMethodRenderer.extend({

            initialize: function () {
                /**
                 * rewriting the getShipping Method for paypal component prototype
                 * cause it is executed during init there's no other sane way to do it on runtime
                 * it skips attribute validation and would return errors otherwise
                 */
                originalMethodRenderer.prototype.getShippingAddress = this.getShippingAddress;

                this._super();

            },

            getShippingAddress: function () {

                var line0, line1, address;

                address = quote.shippingAddress();

                if(
                    !_.isUndefined(address.street) &&
                    _.isArray(address.street)
                ) {
                    line0 = !_.isUndefined(address.street[0]) ? address.street[0] : "";
                    line1 = "";
                    if(address.street.length > 1) {
                        var firstline = address.street[0]
                        address.street.shift();
                        line1 = address.street.slice(0, 2).filter(Boolean).join(' ');
                        address.street.unshift(firstline);
                    }
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
    }
);
