define(function () {
    'use strict';

    /**
     * Remove any non-digit characters from string.
     *
     * @param {string} value
     * @return {string}
     */
    return function (value) {
        return value.replace(/\D/g, '');
    };
});
