/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([
    'underscore',
    "jquery",
    'plumrocket/product-filter/action',
    'Plumrocket_LayeredNavigationLite/js/product/list/toolbar',
    'Plumrocket_LayeredNavigationLite/js/model/swatch',
    'Plumrocket_LayeredNavigationLite/js/model/price',
    'Plumrocket_LayeredNavigationLite/js/model/real-variables',
    'plumrocket/utils',
    'Plumrocket_LayeredNavigationLite/js/view/apply-button',
    'Fyb_Theme/js/lib/jquery.sticky',
    'domReady!'
], function (_, $, filterAction, toolbar, swatch, price, realVariables, utils, applyButton) {
    "use strict";

    $.widget('plumrocket.productfilter', {

        options: {
            filterItemSelector: '#narrow-by-list .item a, #narrow-by-list .swatch-option-link-layered',
            pagerButtons: '.pages-items .item a',
            clearButton: '#product-filter-clear',
            removeFilterLink: '.filter-current .item a.action',
            productListSelector: '.column.main',
            filterSelector: '#layered-filter-block',
            auto: true,
            mobileAuto: true,
            cleanUrl: '',
            variables: {},
            loader: {
                icon: '',
                containerSelector: '.products.wrapper',
            },
            scrollUp: true,
        },

        _create: function () {
            if (! this.options.cleanUrl) {
                return;
            }

            // Disable manual mode for mobile as it is not convenient on mobile phones.
            if (utils.isMobile()) {
                this.options.auto = this.options.mobileAuto;
            }

            this.options.variables = this.fixNoVariablesFormat(this.options.variables);

            //Set parameters for other js
            realVariables.init(this.options.variables);

            filterAction.init(this.options);
            filterAction.setAfterFilterCallback('initListeners', this.afterFilterCallback.bind(this), 40);

            price.auto = this.options.auto;
            price.init = this;

            //Rewrite toolbar function for getting url for toolbar item
            toolbar.rewrite();

            //Emulate selected parameters for swatches
            swatch.emulateSelected(this.options.variables);

            this.addOptionChangeListener();
            this.addPriceChangeListener();
            this.addPageChangeListener();
            this.initFilterContainer(this.options.filterSelector);
        },

        addOptionChangeListener: function () {
            var self = this;

            var filterContainer = document.getElementById('layered-filter-block');
            if (! filterContainer) {
                return;
            }

            function handleFilterItemClick(event)
            {
                var elem = event.target;
                if (swatch.isSwatch(elem)) {
                    event.preventDefault();
                    return self.onOptionClick(elem);
                }

                elem = self.tryFindFilterElement(elem);

                if (! elem.dataset.prFilter) {
                    return;
                }

                event.preventDefault();
                switch (elem.dataset.prFilter) {
                    case 'item':
                        self.onOptionClick(elem);
                        break;
                    case 'clearItem':
                        realVariables.remove(elem.dataset.variable, elem.dataset.value);
                        filterAction.applyChanges();
                        break;
                    case 'clearAll':
                        realVariables.clear();
                        filterAction.applyChanges();
                        break;
                    case 'doManualFilter':
                        filterAction.applyChanges();
                        break;
                }

                return false;
            }

            filterContainer.removeEventListener('click', handleFilterItemClick);
            filterContainer.addEventListener('click', handleFilterItemClick);
        },

        addPriceChangeListener: function () {
            $('#narrow-by-list .item a[data-request="price"]').on('click', $.proxy(price.changeRange, this));
        },

        addPageChangeListener: function () {
            $(this.options.pagerButtons).on('click', $.proxy(this.changePageAction, this));
        },

        /**
         * Handle click on not selected option.
         *
         * @param {HTMLElement} filterItem
         * @return {boolean}
         */
        onOptionClick: function (filterItem) {
            //Fix for disabled configurable swatches
            if (filterItem.classList.contains('swatch-option-link-layered')) {
                if ($(filterItem).find('.disabled').length) {
                    return false;
                }
                return false;
            }

            if (swatch.isSwatch(filterItem)) {
                var request = swatch.getItemRequest(filterItem);
                if (swatch.toggleSelected(filterItem)) {
                    realVariables.add(request.variable, request.value);
                } else {
                    realVariables.remove(request.variable, request.value);
                }
            } else {
                // Remove another value, but leave current to toggle work correctly.
                if (filterItem.dataset.radio
                    && ! realVariables.has(filterItem.dataset.variable, filterItem.dataset.value)
                ) {
                    $(filterItem).parents('.filter-options-content').find('.item a').removeClass('selected');
                    realVariables.removeAllValues(filterItem.dataset.variable);
                }
                filterItem.classList.toggle('selected');
                realVariables.toggle(filterItem.dataset.variable, filterItem.dataset.value);
            }

            if (this.options.auto) {
                filterAction.applyChanges();
            } else {
                filterAction.preloadChanges();
                applyButton.showFilterButton(filterItem, this.options.filterSelector);
            }

            return false;
        },

        /**
         * Handle click on pager link.
         *
         * @param event
         * @return {boolean}
         */
        changePageAction: function (event) {
            var pageNumber = utils.getUrlParameter('p', event.currentTarget.href);
            realVariables.setCurrentPage(pageNumber);
            filterAction.applyChanges();
            return false;
        },

        /**
         * @param selector
         */
        initFilterContainer: function (selector) {
            var containerCollapsible = $(selector).find('[data-role="collapsible"]');
            if ($(window).width() <= 750 ) {
                try {
                    containerCollapsible.collapsible('deactivate');
                } catch (e) {
                }
            }

            if ($('body').hasClass('ppf-pos-toolbar')) {
                $(document).on('mouseup', function (e) {
                    if (! containerCollapsible.is(e.target) // if the target of the click isn't the container...
                        && containerCollapsible.has(e.target).length === 0 // ... nor a descendant of the container
                    ) {
                        try {
                            containerCollapsible.collapsible('deactivate');
                        } catch (e) {
                        }
                    }
                });
            }
        },

        /**
         * Convert empty array to empty object.
         *
         * If there is no variables, server send empty array instead of empty object.
         *
         * @param variables
         * @return {object}
         */
        fixNoVariablesFormat: function (variables) {
            return _.isEmpty(variables) ? {} : variables;
        },

        /**
         * Find needed product filter element.
         *
         * @param {HTMLElement} elem
         * @return {object}
         */
        tryFindFilterElement: function (elem) {
            for (var i = 1; i < 5; i++) {
                if (elem.dataset.prFilter) {
                    return elem;
                }
                if (elem.parentElement) {
                    elem = elem.parentElement
                } else {
                    return elem;
                }
            }
            return elem;
        },

        /**
         * Find needed product filter element.
         *
         * @param {{}} data
         */
        afterFilterCallback: function (data) {
            var self = this;
            setTimeout(function () {
                self._create();
            }, 500)
        },
    });

    return $.plumrocket.productfilter;
});
