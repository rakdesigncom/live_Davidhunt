define([
    'jquery',
    'jquery/validate',
    'mage/translate'
// eslint-disable-next-line strict
], function ($) {
    $.validator.addMethod(
        'validate-invitation-message', function (value) {
            if (value) {
                if (value.toLowerCase().match(/https:\/|http:\/|www\.|ftp:/)) {
                    return false;
                }
            }
            return true;
        }, $.mage.__('Invalid message'));
});
