/**
 * @package     Plumrocket_ProductFilter
 * @copyright   Copyright (c) 2016 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'jquery',
    'productListToolbarForm',
    'plumrocket/product-filter/action',
    'Plumrocket_LayeredNavigationLite/js/model/real-variables',
], function ($, toolbar, processor, realVariables) {

    return {
        rewrite: function () {
            $.mage.productListToolbarForm.prototype.changeUrl = this.changeUrl;
        },
        changeUrl: function (pName, pValue, defValue) {
            realVariables.removeAllValues(pName);
            realVariables.add(pName, pValue);
            processor.applyChanges();
        }
    }
});
