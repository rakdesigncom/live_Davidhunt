/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'Plumrocket_LayeredNavigationLite/js/model/drop',
    'jquery',
    'underscore',
    'jquery/ui'
], function (drop, $, _) {
    'use strict';

    return {
        inited : [],

        /**
         * Drag'n'drop logic for attributes
         */
        init: function (options) {
            if (true === _.has(this.inited, options.elementId)) {
                return;
            }

            var self = this;
            drop.init(options);

            $(options.attributeListSelector + "," + options.attributeEnabledListSelector).sortable({
                connectWith: options.connector,
                items: 'li.attr_item',
                receive: function (event) {
                    var currentElement = self.getCurrent(event);
                    drop.init(options, currentElement);
                },
                stop: function (event, ui) {
                    if (typeof event.target.dropTo != 'undefined') {
                        var currentElement = self.getCurrent(event);
                        delete event.target.dropTo;
                    }
                }
            });

            drop.init(options);
            self.inited.push(options.elementId);
        },

        getCurrent: function (event) {
            var currentElement;
            if (event.target.nodeName === "LABEL") {
                currentElement = $(event.target).parent();
            } else {
                currentElement = $(event.target);
            }
            return currentElement;
        }
    };
});
