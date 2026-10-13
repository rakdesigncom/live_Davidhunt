/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([], function () {
    'use strict';

    return {
        dataCache: {},

        /**
         * Url to cache key mapping.
         * e.g. https://example.com/... -> CACHE_KEY
         *
         * Used for navigation backward and forward.
         */
        urlCacheMapping: {},

        has: function (cacheKey) {
            return this.dataCache.hasOwnProperty(cacheKey);
        },

        hasUrlCache: function (url) {
            return this.urlCacheMapping.hasOwnProperty(url);
        },

        save: function (cacheKey, data) {
            this.dataCache[cacheKey] = data;
            this.urlCacheMapping[data.nextUrl] = cacheKey;
        },

        get: function (cacheKey) {
            return this.dataCache[cacheKey];
        },

        getKeyByUrl: function (url) {
            return this.urlCacheMapping[url];
        },
    };
});
