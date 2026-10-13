/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2016 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'underscore',
    'jquery',
    'Plumrocket_LayeredNavigationLite/js/model/full-screen-loader',
    'Plumrocket_LayeredNavigationLite/js/model/swatch',
    'plumrocket/utils',
    'Plumrocket_LayeredNavigationLite/js/model/real-variables',
    'Plumrocket_LayeredNavigationLite/js/model/data-layered',
    'Plumrocket_LayeredNavigationLite/js/model/debug-logger',
    'Plumrocket_LayeredNavigationLite/js/model/cache',
    'Magento_Ui/js/modal/alert',
    'Fyb_Theme/js/lib/jquery.sticky',
    'domReady!'
], function (_, $, loader, swatch, utils, realVariables, gtm, debugLogger, cache, alert) {
    "use strict";

    var skipHistory = false;

    return {

        currentRequest: null,

        preloadRequest: null,

        applyChangesInProcess: false,

        /**
         * @var {{auto: boolean, scrollUp: boolean, scrollUpTo: string}}
         */
        options : {},

        /**
         * @var {[{key: string, action: function, sortOrder: number}]}
         */
        afterFilterCallbacks: [],

        /**
         * Save configurations from init.js
         *
         * @param options
         */
        init: function (options) {
            this.reset();
            this.options = options;
            loader.init(options.loader);

            this.defaultCallbacks.stickSidebar();

            this.setAfterFilterCallback('replaceProductList', this.defaultCallbacks.replaceProductListCallback.bind(this), 10);
            this.setAfterFilterCallback('replaceFilters', this.defaultCallbacks.replaceFiltersCallback.bind(this), 20);
            this.setAfterFilterCallback('replaceH1', this.defaultCallbacks.replaceH1.bind(this), 25);
            this.setAfterFilterCallback('updateGtmDataLayer', gtm.updateDataLayer.bind(gtm), 30);
            this.setAfterFilterCallback('updateBrowserHistory', this.defaultCallbacks.updateBrowserHistoryCallback.bind(this), 40);
            this.setAfterFilterCallback('scrollToTop', this.defaultCallbacks.scrollUp.bind(this), 50);
            this.setAfterFilterCallback('stickSidebar', this.defaultCallbacks.stickSidebar.bind(this), 55);
            this.setAfterFilterCallback('knockout', this.defaultCallbacks.initKnockoutCallback.bind(this), 60);
            this.setAfterFilterCallback('fixesCallback', this.defaultCallbacks.fixesCallback.bind(this), 70);
            this.setAfterFilterCallback('catalogAddToCart', this.defaultCallbacks.initCatalogAddToCartCallback.bind(this), 80);
            this.setAfterFilterCallback('initSwatches', swatch.afterFilterCallback.bind(swatch), 90);
            this.setAfterFilterCallback('stopLoader', loader.stopLoader.bind(loader), 100);
        },

        /**
         * Reset state.
         *
         * After filter done callback init.js afterFilterCallback will init this object again,
         * so we need to reset to avoid bugs.
         */
        reset: function () {
            this.options = {};
            this.afterFilterCallbacks = [];
        },

        /**
         * Send request with selected options.
         *
         * Will update product list and sidebar after.
         */
        applyChanges: function () {
            var self = this;
            var cacheKey = realVariables.getCacheKey();

            if (this.currentRequest) { // Cancel existing request to start new one.
                this.currentRequest.abort();
            }

            if (this.preloadRequest) {
                loader.startLoader();
                this.applyChangesInProcess = true;
                return false;
            }

            if (cache.has(cacheKey)) {
                loader.fixLoader();
                self.done(cache.get(cacheKey));
            } else {
                loader.startLoader();
                this.currentRequest = $.ajax({
                    url: self.getRequestUrl(),
                    data: self.getRequestData(),
                    cache: true,
                    success: function (response) {
                        cache.save(cacheKey, response);
                        self.done(response);
                        $('body').trigger('collectionUpdated');
                    },
                    error: function (xhr, type, message) {
                        if (type === 'abort') {
                            return;
                        }
                        alert({
                            title: message,
                            content: $.mage.__('Sorry, something went wrong while filtering. Please try again later.'),
                        });
                    }
                });
            }
        },

        preloadChanges: function () {
            var self = this;
            var cacheKey = realVariables.getCacheKey();

            if (this.preloadRequest) { // Cancel existing request to start new one.
                this.preloadRequest.abort();
                this.preloadRequest = null;
            }

            if (cache.has(cacheKey)) {
                self.preloadChangesDone(cache.get(cacheKey));
            } else {
                document.getElementById('pr-filter-amount').classList.add('pr-filter-loader');
                this.preloadRequest = $.ajax({
                    url: self.getRequestUrl(),
                    data: self.getRequestData(),
                    cache: true,
                    success: function (response) {
                        cache.save(cacheKey, response);
                        self.preloadChangesDone(response);
                    },
                    error: function (xhr, type, message) {
                        if (type === 'abort') {
                            return;
                        }
                        alert({
                            title: message,
                            content: $.mage.__('Sorry, something went wrong while filtering. Please try again later.'),
                        });
                    }
                });
            }
        },

        preloadChangesDone: function (data) {
            if (! this.applyChangesInProcess) {
                this.updateFilterCount(data);
            } else {
                this.done(data);
            }
        },

        getRequestUrl: function () {
            let url = this.options.cleanUrl;
            if (realVariables.getCurrentPage() !== 1) {
                url = utils.setParameter(url, 'p', realVariables.getCurrentPage());
            } else {
                url = utils.setParameter(url, 'p', '')
            }

            return url;
        },

        getRequestData: function () {
            let data = this.toGetParamsArray(realVariables.getList());
            data.push({name: 'prfilter_ajax', value: '1'});
            let queryParam = new URLSearchParams(window.location.search).get('q');
            if (queryParam) {
                data.push({name: 'q', value: queryParam});
            }
            return data;
        },

        updateFilterCount: function (data) {
            this.preloadRequest = null;
            document.getElementById('pr-filter-amount').innerHTML = data.productsCount;
            document.getElementById('pr-filter-amount').classList.remove('pr-filter-loader');
        },

        toGetParamsArray: function (variables) {
            return _.map(variables, function (values, variable) {
                return {name: 'prfilter_variables[' + variable + ']', value: values.join(',')};
            })
        },

        /**
         * @param {string}   key
         * @param {function} callback
         * @param {number}   sortOrder
         */
        setAfterFilterCallback: function (key, callback, sortOrder) {
            this.afterFilterCallbacks.push({key: key, action: callback, sortOrder: sortOrder});
        },

        /**
         * Retrieve sorted callbacks.
         *
         * @return {[{key: string, action: function, sortOrder: number}]}
         */
        getSortedAfterFilterCallbacks: function () {
            this.afterFilterCallbacks.sort(function (callback1, callback2) {
                return callback1.sortOrder - callback2.sortOrder;
            });
            return this.afterFilterCallbacks;
        },

        /**
         * @param {{productlist: string, leftnav: string, nextUrl: string}} data
         */
        done: function (data) {
            this.currentRequest = null;
            this.preloadRequest = null;
            this.applyChangesInProcess = false;

            debugLogger.log('response data', data);

            this.getSortedAfterFilterCallbacks().forEach(function (callback) {
                debugLogger.log('Run callback ' + callback.key);
                callback.action(data);
            });
        },

        applyUrl: function (url) {
            if (cache.hasUrlCache(url)) {
                realVariables.toState(cache.getKeyByUrl(url));
                this.applyChanges();
            } else {
                window.location.reload();
            }
        },

        setSkipHistory: function (flag) {
            skipHistory = flag;
        },

        /**
         * Replace toolbar, filters and product list.
         *
         * If any of them cannot be replaced (fail to find old position) this method returns false.
         *
         * @param html
         * @param containerSelector
         * @returns {boolean}
         * @private
         */
        _replaceElements: function (html, containerSelector) {
            var items = $(html.trim());

            var container = document.querySelector(containerSelector);
            var jsContainer = document.querySelector(this.options.productListSelector);
            if (! jsContainer) {
                jsContainer = document.querySelector('body');
            }
            var toolbar = 'top';

            return _.every(items, function (item) {
                if (! item.nodeName || item.nodeName === 'TEXT') {
                    return true;
                }

                if (item.nodeName === 'SCRIPT') {
                    jsContainer.append(item);
                    return true;
                }

                var oldItem;
                if (item.id) {
                    oldItem = document.getElementById(item.id);
                    if (! oldItem) {
                        return false;
                    }
                    $(oldItem).replaceWith(item);
                    return true;
                }

                if (item.className) {
                    var selector = '';
                    item.classList.forEach(function (className) {
                        if (className.indexOf(':') !== -1 || className.indexOf('.') !== -1) {
                            return true;
                        }
                        selector += '.' + className;
                    });

                    if (item.classList.contains('toolbar-products') && toolbar !== 'top') {
                        oldItem = _.last(container.querySelectorAll(selector));
                    } else {
                        oldItem = container.querySelector(selector);
                        toolbar = 'bottom';
                    }
                    if (! oldItem) {
                        return false;
                    }
                    $(oldItem).replaceWith(item);
                    return true;
                }
            }.bind(this));
        },

        /**
         * List of callbacks for calling after finish request.
         */
        defaultCallbacks: {
            scrollUp: function () {
                if (this.options.scrollUp) {
                    var toolbar = document.querySelector('#maincontent');
                    if (toolbar && toolbar.scrollIntoView) {
                        toolbar.scrollIntoView();
                    }
                }
            },

            stickSidebar: function () {
                var $el = $('.sidebar.sidebar-main');
                var bottomSpace = 0;
                $('.page-footer, .footer-badges, .copyright').each(function () {
                    bottomSpace += $(this).outerHeight(true);
                })

                var stickyMenuActive = false;
                var initSticky = function(scrollTop) {
                    var startStickyPosition = $('.breadcrumbs').offset().top - 50;
                    if (scrollTop > startStickyPosition && !stickyMenuActive) {
                        var topSpacing = $('.page-header').outerHeight(true);
                        $el.sticky({
                            topSpacing: topSpacing + 10,
                            bottomSpacing: bottomSpace + 100,
                            wrapperClassName: 'sticky-wrapper-sidebar',
                            className: 'is-sticky-sidebar'
                        });
                        stickyMenuActive = true;
                    }

                    if (scrollTop < startStickyPosition && stickyMenuActive){
                        $el.unstick();
                        stickyMenuActive = false;
                    }

                    resizeInit();
                }

                var resizeInit = function () {
                    if (stickyMenuActive) {
                        var topSpacing = $('.page-header').outerHeight(true);
                        var maxWidth = $('.columns').width() - $('.column.main').width() - 30;
                        $el.css({
                            'max-height': (window.innerHeight - topSpacing - 50) + 'px',
                            'overflow-x': 'hidden',
                            'max-width': maxWidth + 'px'
                        });
                    } else {
                        $el.css({'max-height': 'unset', 'overflow-x': 'auto'});
                    }
                }

                $(window).on("resize", function() {
                    resizeInit();
                });
                var timer;
                $(window).on("scroll", function () {
                    window.clearTimeout(timer);
                    timer = setTimeout(function () {
                        initSticky($(window).scrollTop());
                    }, 10)
                });
                initSticky($(window).scrollTop());
            },

            replaceProductListCallback: function (data) {
                if (! data.productlist) {
                    return;
                }

                if (! this._replaceElements(data.productlist, this.options.productListSelector)) {
                    $(this.options.productListSelector).html(data.productlist);
                }
            },

            replaceFiltersCallback: function (data) {
                if (! data.leftnav) {
                    return;
                }

                if (! this._replaceElements(data.leftnav, this.options.filterSelector)) {
                    if ($(this.options.filterSelector).length) {
                        $(this.options.filterSelector).replaceWith(data.leftnav);
                    } else {
                        $(this.options.productListSelector).prepend(data.leftnav);
                        $('.filter-options').hide(); /* fix */
                    }
                }
            },

            replaceH1: function (data) {
                if (! data.h1) {
                    return;
                }

                var title = document.getElementById('page-title-heading');
                if (title) {
                    title.innerHTML = data.h1;
                }
            },

            /**
             * Add history to browser address line
             *
             * @param data
             */
            updateBrowserHistoryCallback: function (data) {
                if (skipHistory) {
                    skipHistory = false;
                    return;
                }
                try {
                    let pageTitle = data.pageTitle ? data.pageTitle : '';
                    window.history.pushState(data.nextUrl, pageTitle, data.nextUrl);
                    if (pageTitle && pageTitle !== document.title) {
                        document.title = pageTitle;
                    }
                } catch (e) {
                    console.log(e);
                }
            },

            fixesCallback: function () {
                if (window.setGridItemsEqualHeight) {
                    setGridItemsEqualHeight($);
                }
            },

            initKnockoutCallback: function () {
                $('body').trigger('contentUpdated');
            },

            initCatalogAddToCartCallback: function () {
                setTimeout(function () {
                    if ($.fn.catalogAddToCart) {
                        $("form[data-role='tocart-form']").each(function () {
                            var form = jQuery(this);
                            // Check if listener is already bound.
                            if (!$._data(form[0], 'events') || !$._data(form[0], 'events')['submit']) {
                                form.catalogAddToCart();
                            }
                        });
                    }
                }, 500);
            },
        },
    };
});
