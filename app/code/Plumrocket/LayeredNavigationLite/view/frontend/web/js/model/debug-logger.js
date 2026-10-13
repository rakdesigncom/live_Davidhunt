/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([], function () {
        'use strict';

        var isDebugMode = false;

        return {
            log: function () {
                if (isDebugMode) {
                    console.log('Product filter debug log:');
                    console.log(arguments);
                }
            },
        };
    }
);
