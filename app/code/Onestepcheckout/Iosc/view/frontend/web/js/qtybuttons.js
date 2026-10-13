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
        'uiComponent',
        'uiRegistry',
        'jquery',
        'ko',
        'Magento_Ui/js/modal/confirm',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        uiComponent,
        uiRegistry,
        jQuery,
        ko,
        confirmation,
        redirectOnSuccessAction
    ) {
        "use strict";
        return uiComponent.extend({

            initialize: function () {
                this._super();
                this.updateItem = ko.observable(null);
                uiRegistry.async('checkout.iosc.ajax')(
                    function (ajax) {
                        ajax.addMethod('params', 'updateqty', this.paramsHandler.bind(this));
                        ajax.addMethod('success', 'updateqty', this.successHandler.bind(this));
                    }.bind(this)
                )
            },

            paramsHandler: function() {
                var data = this.updateItem();
                if(data !== null) {
                    data = {'item_id': data.item_id, 'qty': data.qty};
                }
                return data ;
            },

            successHandler: function(response) {
                if (this._has(response , "data.updateqty.cart_items")) {
                    if(response.data.updateqty.cart_items <= 0) {
                        redirectOnSuccessAction.redirectUrl = window.checkoutConfig.cartUrl;
                        redirectOnSuccessAction.execute()
                    }
                }
            },

            _has: function (obj, key) {
                return key.split(".").every(
                    function (x) {
                        if (typeof obj !== "object" || obj === null || !(x in obj)) {
                            return false;
                        }
                        obj = obj[x];
                        return true;
                    }
                );
            },

            updateCart: function(elem, qty) {
                uiRegistry.async('checkout.iosc.ajax')(
                    function (ajax) {
                       elem.qty = qty;
                       this.updateItem(elem);
                       ajax.update().then(
                           function() {
                               this.updateItem(null);
                           }.bind(this)
                       );

                    }.bind(this)
                );
            },

            add: function (elem) {
                if(elem.qty <= 0) {
                    elem.qty = 0;
                }
                var qty = parseInt(elem.qty) + 1;
                this.updateCart(elem, qty);
            },

            sub: function (elem) {
                if(elem.qty <= 0) {
                    elem.qty = 1;
                }
                var qty = parseInt(elem.qty) - 1;
                if(qty === 0){
                    this.delConfirm(elem, qty);
                } else {
                    this.updateCart(elem, qty);
                }
            },

            delConfirm: function(elem) {
                var qty = 0;
                var self = this;
                confirmation({
                    title: jQuery.mage.__('Deleting from cart!'),
                    content: jQuery.mage.__('Are you sure you want to remove this item from your cart?'),
                    actions: {
                        confirm: function() {this.del(elem, qty)}.bind(self, elem, qty),
                        cancel: function(){},
                        always: function(){}
                    }
                });
            },

            del: function(elem, qty) {
                this.updateCart(elem, qty);
            }
        });
    }
);
