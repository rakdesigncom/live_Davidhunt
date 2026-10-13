var config = {
    map : {
        "*" : {
            "bluebird": "Onestepcheckout_Iosc/js/lib/bluebird"
        }
    },
    paths: {
        'reCaptcha': '//www.google.com/recaptcha/api'
    },
    config : {
        mixins : {
            "Magento_Checkout/js/view/payment/default" : {
                "Onestepcheckout_Iosc/js/mixin/view/payment/default" : true
            },
            "Magento_Ui/js/form/form" : {
                "Onestepcheckout_Iosc/js/mixin/form/form" : true
            },
            "Magento_Ui/js/form/element/abstract" : {
                "Onestepcheckout_Iosc/js/mixin/form/element/abstract" : true
            },
            "Magento_Checkout/js/model/checkout-data-resolver" : {
                "Onestepcheckout_Iosc/js/mixin/checkout/checkout-data-resolver" : true
            },
            'Magento_Checkout/js/action/select-payment-method' : {
                'Magento_SalesRule/js/action/select-payment-method-mixin' : false
            },
            'Magento_Checkout/js/action/place-order': {
                'Magento_SalesRule/js/model/place-order-mixin': false
            }
        }
    }
};
