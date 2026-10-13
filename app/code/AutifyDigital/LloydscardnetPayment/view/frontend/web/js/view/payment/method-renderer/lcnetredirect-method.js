define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        ko,
        $,
        Component,
        quote,
        url,
        customerData,
        errorProcessor,
        fullScreenLoader,
        formBuilder,
        globalMessageList,
        additionalValidators,
        redirectOnSuccessAction
    ) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            defaults: {
                template: 'AutifyDigital_LloydscardnetPayment/payment/lcnetredirect'
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                var self = this;
                $.ajax({
                    showLoader: true,
                    url: url.build('lcnetpayment/index/redirectPostData'),
                    data: {},
                    type: 'POST'
                }).done(function (response) {
                    console.log(response)
                    if(response && !response.error) {
                        self.redirectAfterPlaceOrder = true;
                        formBuilder(response).submit(); //this function builds and submits the form
                        return true;
                    } else {
                        errorProcessor.process(response, this.messageContainer);
                        fullScreenLoader.stopLoader();
                        return false;
                    }
                }).fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                    fullScreenLoader.stopLoader();
                });
                return false;
            },
        });
    }
);
