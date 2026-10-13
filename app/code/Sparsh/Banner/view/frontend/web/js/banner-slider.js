define([
    'jquery',
    'slick'
], function ($) {
    'use strict';
    return function (config) {
        $('.sparsh_banner_slider').not('.slick-initialized').slick(
            {
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                dots: false,
                autoplay: parseInt(config.autoplay),
                autoplaySpeed: parseInt(config.autoplay_speed),
                pauseOnHover:false,
                arrows: true
            }
        );
    }
});