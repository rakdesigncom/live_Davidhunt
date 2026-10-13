/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

define([], function () {
    'use strict';

    return {
        filterButton: document.getElementById('manual-filter'),
        filterButtonListener: false,
        buttonTopClass: 'pr-filter-fixed-top',
        buttonBottomClass: 'pr-filter-fixed-bottom',
        initParams: {},

        showFilterButton: function (filterItem, filterSelector) {
            if (!this.filterButtonListener) {
                this.addFilterButtonListener(this.initParams);
            }

            //delete init params
            for (const prop of Object.getOwnPropertyNames(this.initParams)) {
                delete this.initParams[prop];
            }

            this._clearClasses(this.filterButton);

            this._updateNode();

            this._setButtonPosition(filterItem, this.filterButton, filterSelector)
        },

        addFilterButtonListener: function (initParams) {
            var self = this;

            this._handleScroll = function (e) {
                let filterButtonRect = self.filterButton.getBoundingClientRect();
                let scrollPosition = window.scrollY;

                //set scroll position to initParams
                if (filterButtonRect.top < 20) {
                    if (!initParams.hasOwnProperty('top')) {
                        initParams.top = window.scrollY;
                    }

                    if (!initParams.hasOwnProperty('currentClass') || initParams.currentClass !== self.buttonTopClass) {
                        initParams.currentClass = self.buttonTopClass;
                    }

                } else if (window.innerHeight - filterButtonRect.bottom < 20) {
                    if (!initParams.hasOwnProperty('bottom')) {
                        initParams.bottom = window.scrollY;
                    }

                    if (!initParams.hasOwnProperty('currentClass') || initParams.currentClass !== self.buttonBottomClass) {
                        initParams.currentClass = self.buttonBottomClass;
                    }
                }

                //Switch css classes
                if (scrollPosition >= initParams.top || scrollPosition <= initParams.bottom) {
                    self.filterButton.classList.add(initParams.currentClass);
                } else {
                    self._clearClasses();
                }
            };

            document.removeEventListener('scroll', this._handleScroll);
            document.addEventListener('scroll', this._handleScroll);
            this.filterButtonListener = true;
        },

        _setButtonPosition: function (filterItem, button, filterSelector) {
            const bodyElement = document.querySelector(filterSelector),
                sidebar = document.querySelector('.sidebar'),
                bodyRect = bodyElement.getBoundingClientRect();

            if (filterItem.closest('.sidebar')) {
                const itemRect = filterItem.getBoundingClientRect(),
                    offset = Math.round(itemRect.top + (itemRect.height / 2) - bodyRect.top);

                button.style.setProperty('--pr-top-position', offset + 'px');
                button.style.setProperty('--pr-sidebar-width', Math.round(bodyRect.width) + 'px');

            } else {
                const rectItemSection = filterItem.closest('.filter-options-item');
                bodyElement.classList.add('pr-toolbar-wrapper');
                button.style.setProperty('--pr-left-position', rectItemSection.offsetLeft + rectItemSection.clientWidth / 2 + 'px');

                if (sidebar) {
                    button.style.setProperty('--pr-sidebar-left', sidebar.getBoundingClientRect().left + 'px');
                    button.style.setProperty('--pr-sidebar-width', sidebar.clientWidth + 'px');
                }
            }
        },

        _clearClasses: function () {
            this.filterButton.classList.remove(this.buttonTopClass, this.buttonBottomClass);
        },

        _updateNode: function () {
            this.filterButton = document.getElementById('manual-filter');
            this.filterButton.removeAttribute("style");
            this.filterButton.style.setProperty('--pr-button-height', this.filterButton.offsetHeight + 'px');
        },
    };
});
