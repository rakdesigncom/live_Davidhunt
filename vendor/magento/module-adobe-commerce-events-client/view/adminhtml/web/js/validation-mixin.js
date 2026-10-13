/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
define([
    'jquery',
    'Magento_Ui/js/lib/validation/utils'
], function ($, utils) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-eventing-id',
            function (value) {
                return utils.isEmptyNoTrim(value) || /^[A-Za-z0-9_]+$/.test(value);
            },
            $.mage.__('The ID can contain only alphanumeric characters and underscores.')
        );
    };
});
