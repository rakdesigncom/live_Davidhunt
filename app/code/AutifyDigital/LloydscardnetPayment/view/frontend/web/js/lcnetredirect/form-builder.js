define(
    [
        'jquery',
        'underscore',
        'mage/template'
    ],
    function ($, _, mageTemplate) {
        'use strict';

        /* This is the form template used to generate the form, from your Controller JSON data */
        var form_template =
            '<form id="lloydscardsnetredirect-form" name="lloydscardsnetredirect-form" action="<%= data.action %>" method="POST" hidden enctype="application/x-www-form-urlencoded">' +
            '<% _.each(data.fields, function(val, key){ %>' +
            '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
            '<% }); %>' +
            '</form>';

        return function (response) {
            var form = mageTemplate(form_template);
            var final_form = form({
                data: {
                    action: response.action,
                    fields: response.fields
                }
            });
            
            return $(final_form).appendTo($('[data-container="body"]'));
        };
    }
);