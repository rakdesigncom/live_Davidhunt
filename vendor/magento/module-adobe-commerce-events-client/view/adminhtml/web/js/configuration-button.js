/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
    'uiComponent'
], function ($, Component) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.initEventHandlers();
        },

        initEventHandlers: function () {
            $(this.buttonId).on('click', this.onClick.bind(this));
            $('#adobe_io_events_integration').on('keyup change', this.disableButton.bind(this));
        },

        onClick: function () {
            $.ajax({
                url: this.ajaxUrl,
                type: 'GET',
                dataType: 'json',
                showLoader: true,
                context: this,
                success: function (result) {
                    if (result['success']) {
                        $(`${this.buttonInfoId} ${this.messageDivId} .message-text`).text(this.messages.success);
                        $(`${this.buttonInfoId} ${this.messageDivId}`)
                            .addClass('message message-success success').show();
                    } else {
                        $(`${this.buttonInfoId} ${this.messageDivId} .message-text`).text(
                            result['error'] ? result['error'] : this.messages.failure
                        );
                        $(`${this.buttonInfoId} ${this.messageDivId}`)
                            .addClass('message message-error error').show();
                    }
                },
                error: function () {
                    $(`${this.buttonInfoId} ${this.messageDivId} .message-text`).text(this.messages.failure);
                    $(`${this.buttonInfoId} ${this.messageDivId}`)
                        .addClass('message message-error error').show();
                }
            });

            $(this.buttonId).prop('disabled', true);
        },

        disableButton: function () {
            $(this.buttonId).prop('disabled', true);
            const buttonInfo = $(this.buttonInfoId);

            if (!buttonInfo.find('p.note').length) {
                buttonInfo.append(
                    `<p class="note"><span>${this.messages.saveConfig}</span></p>`
                );
            }
        }
    });
});
