/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

/** @deprecated since 1.1.0 */
define([], function () {
    'use strict';

    return {
        /**
         * Parse GET params
         *
         * @param name
         * @param url
         * @return {string}
         */
        getUrlParameter: function (name, url) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(url ? url : location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Adds or updates a URL parameter.
         *
         * @param {string} url  the URL to modify
         * @param {string} param  the name of the parameter
         * @param {string} paramVal  the new value for the parameter
         * @return {string} the updated URL
         */
        setParameter: function (url, param, paramVal) {
            // http://stackoverflow.com/a/10997390/2391566
            var parts = url.split('?');
            var baseUrl = parts[0];
            var oldQueryString = parts[1];
            var newParameters = [];
            if (oldQueryString) {
                var oldParameters = oldQueryString.split('&');
                for (var i = 0; i < oldParameters.length; i++) {
                    if (oldParameters[i].split('=')[0] != param) {
                        newParameters.push(oldParameters[i]);
                    }
                }
            }
            if (paramVal) {
                newParameters.push(param + '=' + encodeURI(paramVal));
            }
            if (newParameters.length > 0) {
                newParameters.sort(); // minify count of different url
                return baseUrl + '?' + newParameters.join('&');
            } else {
                return baseUrl;
            }
        },

        /**
         * Add/update/remove param in url
         *
         * @param {string} paramName
         * @param {string} value
         * @return {exports}
         */
        updateUrlParam: function (paramName, value) {
            if (window.history.replaceState) {
                //prevents browser from storing history with each change:
                var newUrl = this.setParameter(window.location.href, paramName, value);
                window.history.replaceState('', '', newUrl);
            }
            return this;
        },
    };
});
