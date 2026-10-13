/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'underscore',
    'domReady!'
], function ($) {
    "use strict";

    return {
        updateDataLayer: function (data) {
            if (data.productlist && typeof dataLayer != 'undefined' && data.googleTagManager.length > 0) {
                $('.products-grid').append(data.googleTagManager);

                var impressions = [];
                var impressionsData;

                if (typeof staticImpressions['search_result_list'] != 'undefined') {
                    impressionsData = staticImpressions['search_result_list'];
                } else {
                    impressionsData = staticImpressions['category.products.list'];
                }

                for (var i = 0; i < impressionsData.length; i++) {
                    impressions.push({
                        'id': impressionsData[i].id,
                        'name': impressionsData[i].name,
                        'category': impressionsData[i].category,
                        'list': impressionsData[i].list,
                        'position': impressionsData[i].position
                    });
                }

                dataLayer.push({
                    'event': 'productImpression',
                    'ecommerce': {
                        'impressions': impressions
                    }
                });
            }
        }
    };
});
