var config = {
    paths: {
        'fybOwlCarousel': 'Fyb_Theme/js/lib/owl.carousel.min',
        'fybSwiper': 'Fyb_Theme/js/swiper',
        'fybSwiperLib': 'Fyb_Theme/js/lib/swiper-bundle.min',
    },
    shim: {
        'fybOwlCarousel': {
            deps: ['jquery']
        },
        'fybSwiper': {
            deps: [
                'jquery',
                'Fyb_Theme/js/lib/swiper-bundle.min'
            ]
        },
    },
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'Fyb_Theme/js/swatch/auto-select-option': true
            },
        }
    }
};
