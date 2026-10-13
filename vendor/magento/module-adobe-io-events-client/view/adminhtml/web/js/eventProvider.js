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
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/lib/validation/utils'
], function ($, modal, utils) {

    return function (config) {
        function showError(error) {
            $('body').trigger('processStop');
            $('#create_event_provider').after(
                '<div id="create_event_provider_error">' + error + '</div>'
            );
            $('#create_event_provider_error').addClass('message-text message message-error error');
        }

        function removeError() {
            $('#create_event_provider_error').remove();
        }

        function validateProviderFormFields() {
            const label = $('#event-provider-label').val(),
                description = $('#event-provider-description').val(),
                labelNote = $('[name=labelNote]'),
                descriptionNote = $('[name=descriptionNote]');

            let validLabel = !utils.isEmpty(label) && /^[A-Za-z0-9_\s-]+$/.test(label),
                validDescription = !utils.isEmpty(description) && /^[A-Za-z0-9_\s-]+$/.test(description);

            validLabel ? labelNote.hide() : labelNote.show();
            validDescription ? descriptionNote.hide() : descriptionNote.show();

            $('#formBtn').prop('disabled', !(validDescription && validLabel));
        }

        function disableCreateEventProvider() {
            const eventProviderButton = $('#create_event_provider'),
                eventProviderInfo = $('p.note'),
                providerInfo = 'Save your configuration before creating event provider.',
                providerInfoNote = '<p id="providerNote" class="note"><span>' + providerInfo + '</span></p>';

            eventProviderButton.prop('disabled', true);
            if (!$('#providerNote').length) {
                eventProviderInfo.after(providerInfoNote);
            }
        }

        const options = {
            type: 'popup',
            modalClass: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Event Provider Details',
            buttons: [{
                text: $.mage.__('Submit'),
                class: 'button action submit primary',
                attr: {
                    id: 'formBtn'
                },
                click: function () {
                    const label = $('#event-provider-label').val(),
                        description = $('#event-provider-description').val();

                    $.ajax({
                        url: config.ajaxUrl,
                        type: 'POST',
                        dataType: 'json',
                        showLoader: true,
                        context: this,
                        data: {'label': label.trim(), 'description': description.trim()},
                        success: function (result) {
                            if (result.error) {
                                showError(result.error);
                                return;
                            }
                            location.reload();
                        },
                        error: function () {
                            showError('Failed to create event provider.');
                        }
                    });
                    $('#create-event-provider-popup').modal('closeModal');
                    $('body').trigger('processStart');
                }
            }]
        };

        modal(options, $('#create-event-provider-popup'));
        $('#create_event_provider').on('click', function () {
            removeError();
            $('#create-event-provider-popup').show();
            $('#create-event-provider-popup').modal('openModal');
            $('#event-provider-label').val('');
            $('#event-provider-description').val('');
            $('[name=labelNote]').show();
            $('[name=descriptionNote]').show();
            $('#formBtn').prop('disabled', true);
        });

        $('#adobe_io_events_integration').on('keyup change', disableCreateEventProvider.bind(this));
        $('input#event-provider-label, input#event-provider-description').on(
            'change keyup input',
            validateProviderFormFields.bind(this)
        );
    };
});
