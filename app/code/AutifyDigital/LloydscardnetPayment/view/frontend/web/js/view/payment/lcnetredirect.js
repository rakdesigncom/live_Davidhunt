define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'lcnetredirect',
                component: 'AutifyDigital_LloydscardnetPayment/js/view/payment/method-renderer/lcnetredirect-method'
            }
        );
        return Component.extend({});
    }
);