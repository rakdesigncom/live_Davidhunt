/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

define([
    'jquery',
    'Plumrocket_ExtendedAdminUi/js/lib/chosen.jquery.min'
], function ($) {
    'use strict';

    return {
        /**
         * Registry for all selects with class pr-chosen
         */
        initializedSelects: [],

        /**
         * @param {string} selector
         * @param {{enabled: boolean, allIndex: string}} dependConfig
         */
        initializeChosen: function (selector, dependConfig) {
            var selects = this.getChosenSelects(selector);
            selects.forEach(function (select) {
                if (! this.isInitializedSelect(select)) {
                    this.initializeSelect(select);
                    this.initializeOptionDepends(select, dependConfig);
                }
            }.bind(this));
        },

        /**
         * @param {string} selector
         * @returns {HTMLSelectElement[]}
         */
        getChosenSelects: function (selector) {
            var result = $(selector);
            return result.length ? result.toArray() : [];
        },


        /**
         * @param {HTMLSelectElement} select
         * @returns {boolean|number|*}
         */
        initializeSelect: function (select) {
            if (!select instanceof HTMLElement) {
                return false;
            }

            if (this.isInitializedSelect(select)) {
                return this.getIndexOfRegisteredSelect(select);
            }

            var enableSearch = true;
            if ('readonly' === select.getAttribute('readonly')) {
                enableSearch = false;
            }

            $(select).chosen({
                'display_selected_options': true,
                'display_disabled_options': true,
                'hide_results_on_select': true,
                'group_search': enableSearch
            });

            return this.initializedSelects.push(select.name);
        },

        /**
         * @param {HTMLSelectElement} select
         * @returns {number}
         */
        getIndexOfRegisteredSelect: function (select) {
            return this.initializedSelects.indexOf(select.name);
        },

        /**
         * @param {HTMLSelectElement} select
         * @returns {boolean}
         */
        isInitializedSelect: function (select) {
            return -1 !== this.getIndexOfRegisteredSelect(select);
        },

        /**
         * @param {HTMLSelectElement} select
         */
        reinitializeSelect: function (select) {
            if (this.isInitializedSelect(select)) {
                $(select).chosen('destroy');
                this.initializedSelects.splice(this.getIndexOfRegisteredSelect(select), 1);
                this.initializeSelect(select);
            }
        },

        /**
         * @param {HTMLSelectElement} select
         */
        updateSelect: function (select) {
            this.initializeSelect(select);
            $(select).trigger('chosen:updated');
        },

        /**
         * @param {HTMLSelectElement} select
         * @param {{enabled: boolean, allIndex: string}} dependConfig
         */
        initializeOptionDepends: function (select, dependConfig) {
            if (! dependConfig.enabled) {
                return;
            }

            this.initializeSelect(select);

            var $select = $(select);

            var initialValues = !$select.val() ? [] : $select.val();
            var initialShowToAllIndex = initialValues.indexOf(dependConfig.allIndex);

            var chosenModel = this;
            $select.on('change', function () {
                var values = !$(this).val() ? [] : $(this).val();
                var showToAllIndex = values.indexOf(dependConfig.allIndex);

                if (-1 !== initialShowToAllIndex) {
                    if (-1 !== showToAllIndex && values.length > 1) {
                        for (var i = 0; i < values.length; i++) {
                            values.splice(showToAllIndex, 1);
                        }
                        $select.val(values);
                    }
                } else {
                    if (-1 !== showToAllIndex && values.length > 1) {
                        $select.val([dependConfig.allIndex]);
                    }
                }

                if (!$(this).val()) {
                    $(this).val([dependConfig.allIndex]);
                }

                initialValues = $(this).val() === null ? [] : $(this).val();
                initialShowToAllIndex = initialValues.indexOf(dependConfig.allIndex);
                chosenModel.updateSelect(select);
            });
        }
    };
});
