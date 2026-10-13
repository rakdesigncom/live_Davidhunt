/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

define([
    'jquery'
], function ($) {
    return {
        initialize: function (rowId) {
            var $grid = $('#' + rowId + ' > td.value .data-grid tbody');

            this.waitMageInitialization($grid, function (rowId, $grid) {
                const $inherit = $('#' + rowId + '_inherit:checked');
                if ($inherit.length) {
                    $inherit.click();
                }

                let rowTemplate = $grid.find('tr:first-child').html();
                $grid.find('tr:first-child').remove();
                $grid.addClass('loaded');

                this.initAddButton(rowId, $grid, rowTemplate);
                this.initRemoveButton($grid);
            }.bind(this, rowId, $grid))
        },
        initAddButton: function (rowId, $grid, template) {
            $('#' + rowId + ' > td.value > .add-row-value').on('click', function () {
                let name = 'row-value-' + Date.now();
                $grid.append('<tr>'+ template.split('_TMPNAME_').join(name) +'</tr>');
                return false;
            });
        },
        initRemoveButton: function ($grid) {
            $grid.on('click', '.remove-row-value', function () {
                $(this).parent().parent().remove();
            });
        },
        waitMageInitialization: function ($grid, callback) {
            let interval = setInterval(function () {
                if ($grid.find('*[data-mage-init]').length === 0) {
                    callback();
                    clearInterval(interval);
                }
            }, 200);
        }
    }
});
