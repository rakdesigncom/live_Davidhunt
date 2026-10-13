define([
    'Magento_Ui/js/grid/columns/column',
    'jquery',
    'Magento_Ui/js/modal/modal'
    ], function (Column, $) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html',
            fieldClass: {
                'data-grid-html-cell': true
            }
        },
        getBillingAddress: function (row) { 
            return row[this.index + '_billing']; 
        },
        getShippingAddress: function (row) { 
            return row[this.index + '_shipping']; 
        },
        preview: function (row) {
            var html ="<div class='modal-body'>";
            html +="<div class='admin__field field'>";
            html += "<label class='admin__field-label label'>" + $.mage.__('Billing Address') + "</label>";
            html += "<div class='admin__field-control control'>" + this.getBillingAddress(row) + "</div>";
            html += "</div>";
            html +="<div class='admin__field field'>";
            html += "<label class='admin__field-label label'>" + $.mage.__('Shipping Address') + "</label>";
            html += "<div class='admin__field-control control'>" + this.getShippingAddress(row) + "</div>";
            html += "</div>";

            var previewPopup = $('<div/>').html(html);
            previewPopup.modal({
                title: $.mage.__('Address'),
                innerScroll: true,
                modalClass: '_address-box',
            }).trigger('openModal');
        },
        getFieldHandler: function (row) {
            return this.preview.bind(this, row);
        }
    });
});