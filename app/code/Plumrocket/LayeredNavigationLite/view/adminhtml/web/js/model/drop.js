/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    return {
        init: function (options, item) {
            var droppableParams = {
                accept: options.itemSelector,
                drop: function (event, ui) {
                    event.toElement['dropTo'] = event.target;
                }
            };

            if (! item) {
                $(options.attributeEnabledListSelector)
                    .find('.attr_item')
                    .droppable(droppableParams);
            } else {
                item.droppable(droppableParams);
            }
        }
    };
});
