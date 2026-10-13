define([
    'jquery',
    'Fyb_Theme/js/lib/swiper-bundle.min',
    'Magento_Ui/js/modal/modal'
], function ($) {
    'use strict';

    $.widget('fyb.swiper', {
        options: {
            swiper: {
                grabCursor: true,
                watchSlidesProgress: true,
                breakpoints: {
                    768: {
                        slidesPerView: 4
                    },
                    600: {
                        slidesPerView: 3
                    },
                    480: {
                        slidesPerView: 2
                    }
                }
            }
        },

        /**
         * @private
         */
        _create: function () {
            if (this.options.lazy) {
                this.options.on = $.extend({}, this.options.on, {
                    lazyImageReady: this.updateSwiper.bind(this)
                });
            }

            var elem = $(this.element);
            var swiperOptions = this.options.swiper;

            if (swiperOptions.scrollbar) {
                swiperOptions.scrollbar = {
                    el: ".swiper-scrollbar",
                    draggable: true,
                    hide: true,
                    snapOnRelease: false
                }
                this.addScroll(swiperOptions);
            }
            if (swiperOptions.navigation) {
                swiperOptions.navigation = {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev"
                }
                this.addNavigation(swiperOptions);
            }
            new window.Swiper('#' + elem.attr('id'), swiperOptions);
        },

        addNavigation: function (swiperOptions) {
            var elem = $(this.element);
            elem.append('<div class="swiper-button-next"></div>' +
                '<div class="swiper-button-prev"></div>');
        },

        addScroll: function (swiperOptions) {
            var elem = $(this.element);
            elem.append('<div class="swiper-scrollbar"></div>');
        },

        /**
         * Update swiper
         */
        updateSwiper: function () {
            var swiper = this.element.get(0).swiper;

            if (swiper) {
                swiper.update();
            }
        }
    });

    return $.fyb.swiper;
});
