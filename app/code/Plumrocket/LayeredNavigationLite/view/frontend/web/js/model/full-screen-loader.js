/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2016 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
        'jquery',
        'loader'
    ], function ($) {
        'use strict';

        var loaderOptions = {
            icon: '',
            containerSelector: '.products.wrapper',
        };

        return {
            init: function (options) {
                loaderOptions = _.extend(loaderOptions, options);
            },

            /**
             * Fix removing spinner of loader when loader had not showed before.
             */
            fixLoader: function () {
                $(loaderOptions.containerSelector).loader({icon: loaderOptions.icon});
                $(loaderOptions.containerSelector).loader('show');
            },

            startLoader: function () {
                $(loaderOptions.containerSelector).loader({icon: loaderOptions.icon});
                $(loaderOptions.containerSelector).loader('show');
                this.setLoaderPosition();
            },

            stopLoader: function () {
                $(loaderOptions.containerSelector).loader({icon: loaderOptions.icon});
                $(loaderOptions.containerSelector).loader('hide');
            },

            calcLoaderPosition: function (el) {
                let elH = el.offsetHeight,
                    viewPortHeight = window.innerHeight,
                    r = el.getBoundingClientRect(),
                    topPosition = r.top,
                    bottomPosition = r.bottom,
                    visibleArea = function () {
                        var position;
                        if (topPosition > 0) {
                            position = Math.max(0, Math.min(elH, viewPortHeight - topPosition) / 2);
                            return position < 70 ? 70 : position;
                        }

                        if (Math.min(bottomPosition, viewPortHeight) === viewPortHeight
                            || (viewPortHeight >= bottomPosition && bottomPosition > 0)
                        ) {
                            position = Math.max(0, (Math.abs(topPosition * 2) + Math.min(bottomPosition, viewPortHeight)) / 2);
                            return bottomPosition < 70 ? position - 70 : position;
                        }

                        return 0;
                    }

                return visibleArea();
            },

            setLoaderPosition: function () {
                var container = document.querySelector(loaderOptions.containerSelector),
                    setParams = function (el) {
                        if (el) {
                            el.style.setProperty('--loader-position', this.calcLoaderPosition(el) + 'px');
                            el.style.position = 'relative';
                            el.classList.add('pr-loader__wrapper')
                        }
                    }.bind(this);

                window.addEventListener('scroll', function () {
                    setParams(container);
                });
                setParams(container);
            }
        };
    }
);
