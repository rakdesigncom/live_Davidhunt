/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define(['underscore'], function (_) {
    'use strict';

    /**
     * Contains current filter state in following format:
     *
     * {
     *     variableName: [selectedOptionId, ...],
     * }
     *
     * @var {{{string}: []}}
     */
    var variables = {};

    var currentPage = 1;

    return {
        /**
         * Convert object of strings to object of arrays and save it.
         *
         * @param {{string}} realVariables
         */
        init: function (realVariables) {
            variables = realVariables;
        },

        /**
         * Add variable value.
         *
         * @param {string} variable
         * @param {string} value
         */
        add: function (variable, value) {
            value = String(value);
            if (this.has(variable, value)) {
                return;
            }

            if (typeof variables[variable] === 'object') {
                variables[variable].push(value);
            } else {
                variables[variable] = [value];
            }

            this.resetCurrentPage();
        },

        /**
         * Remove variable value.
         *
         * @param {string} variable
         * @param {string} value
         */
        remove: function (variable, value) {
            if (! variables.hasOwnProperty(variable)) {
                return;
            }

            value = String(value);
            variables[variable] = variables[variable].filter(function (val) {
                return value !== val;
            })

            if (! variables[variable].length) {
                delete variables[variable];
            }

            this.resetCurrentPage();
        },

        /**
         * Remove all values of specified variable.
         *
         * @param {string} variable
         */
        removeAllValues: function (variable) {
            if (variables.hasOwnProperty(variable)) {
                delete variables[variable];
            }
        },

        /**
         * Remove all selected values.
         */
        clear: function () {
            this.resetCurrentPage();
            _.each(_.keys(variables), function (variable) {
                this.removeAllValues(variable);
            }.bind(this))
        },

        /**
         * Check if value is already set.
         *
         * @param {string} variable
         * @param {string} value
         */
        has: function (variable, value) {
            value = String(value);
            return typeof variables[variable] === 'object' && _.contains(variables[variable], value);
        },

        /**
         * Remove variable value.
         *
         * @param {string} variable
         * @param {string} value
         */
        toggle: function (variable, value) {
            if (this.has(variable, value)) {
                this.remove(variable, value);
            } else {
                this.add(variable, value);
            }
        },

        /**
         * Add variable.
         *
         * @return {{[]}}
         */
        getList: function () {
            return variables;
        },

        /**
         * Get variables hash.
         *
         * @return {string}
         */
        getCacheKey: function () {
            var variablesNames = _.keys(variables)
            variablesNames.sort();

            var cacheVars = [];
            _.each(variablesNames, function (variableName) {
                cacheVars.push(variableName + '|' + variables[variableName].join('|'));
            }.bind(this));
            cacheVars.push('currentPage|' + this.getCurrentPage());

            return cacheVars.join('||');
        },

        /**
         * Restore 'variables' and 'currentPage' variables by cache key.
         *
         * @param {string} cacheKey
         */
        toState: function (cacheKey) {
            this.clear();
            var vars = cacheKey.split('||');

            vars.forEach(function (item) {
                var parts = item.split('|');
                if ('currentPage' === parts[0]) {
                    this.setCurrentPage(+parts[1]);
                } else {
                    parts.slice(1).forEach(function (value) {
                        this.add(parts[0], value);
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * @return {number}
         */
        getCurrentPage: function () {
            return currentPage;
        },

        /**
         * @param {number} pageNumber
         */
        setCurrentPage: function (pageNumber) {
            currentPage = +pageNumber;
        },

        /**
         * Reset page number.
         *
         * After applying filter we get lees products and current page con be empty,
         * therefore we show first page.
         */
        resetCurrentPage: function () {
            currentPage = 1;
        }
    };
});
