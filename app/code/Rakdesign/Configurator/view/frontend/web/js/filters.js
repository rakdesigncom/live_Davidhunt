require([
    'jquery',
    'shadenouislider'
], function ($, noUiSlider) {

    var priceSliderId = '.price-slider';
    var multiSelect = false;
    if (jQuery('.shade-configurator-filters').length > 0) {
        var multiSelect = parseInt(jQuery('.shade-configurator-filters').attr('data-multiselect'));
    }
    function clearShadeConfiguratorFilters() {
        resetFilteredList();
        jQuery('.filters a.active,.filter.active').removeClass('active');
        jQuery('.crossed-out').removeClass('crossed-out');
        jQuery('.hide-product').addClass('hide-product');
    }
    function resetFilteredList() {
        jQuery('#ajax-product-list>li').removeClass('hide-product');
    }
    function excludeShadeConfiguratorFilters() {
        jQuery('.crossed-out').removeClass('crossed-out');
        if (jQuery('.filter:not(.active)').length <= 0) {
            return;
        }
        jQuery('.filter:not(.actives) a').each(function () {
            var filterElement = jQuery(this);
            var dataType = filterElement.attr('data-type');
            var value = filterElement.attr('data-value');
            var hasElement = false;
            jQuery('#ajax-product-list>li:not(.hide-product)').each(function () {
                var listElement = jQuery(this);
                var match = value + '|';
                var match2 = '|' + value;

                if (typeof listElement.attr(dataType) === 'undefined') {

                } else if (listElement.attr(dataType).indexOf('|') >= 0) { // is type arrau
                    if ((listElement.attr(dataType).indexOf(match) >= 0 || listElement.attr(dataType).indexOf(match2) >= 0)) {
                        hasElement = true;
                    }

                } else {// is type string
                    if (listElement.attr(dataType) == value) {

                        hasElement = true;

                    }
                }

            });

            if (!hasElement && !multiSelect) {
                filterElement.addClass('crossed-out');
            }
        });

    }
    function showResetFilter() {
        jQuery('.filters span.reset-all').show();
    }
    function hideResetFilter() {
        jQuery('.filters span.reset-all').hide();
    }
    function checkPriceMatch(min, max, type,mismatchId) {
        var showClear = false;
        jQuery('#ajax-product-list li').removeClass('mismatch-'+mismatchId);
        jQuery('#ajax-product-list li').each(function () {
            var currentValue = parseFloat(jQuery(this).attr(type));
            if (!currentValue) {
                return;
            }
            if (currentValue < min || currentValue > max) {
                jQuery(this).addClass('mismatch-'+mismatchId);
                showClear = true;
            }

        });
        if (showClear) {
            showResetFilter();
        }
        return;
    }

    function shadeConfiguratorFilterMultiselect() {
        resetFilteredList();
        if (jQuery('.filters a.active').length <= 0) {
            return;
        }
        jQuery('#ajax-product-list>li').addClass('hide-product');
        jQuery('.filters a.active').each(function () {
            var filterElement = jQuery(this);
            var dataType = filterElement.attr('data-type');
            var value = filterElement.attr('data-value');

            jQuery('#ajax-product-list>li').each(function () {
                var listElement = jQuery(this);
                var match = value + '|';
                var match2 = '|' + value;

                if (typeof listElement.attr(dataType) === 'undefined') {
                    //listElement.removeClass('hide-product');
                } else if (listElement.attr(dataType).indexOf('|') >= 0) { // is type arrau
                    if ((listElement.attr(dataType).indexOf(match) >= 0 || listElement.attr(dataType).indexOf(match2) >= 0)) {
                        listElement.removeClass('hide-product');
                    }
                } else { // is type string
                    if (listElement.attr(dataType) === value) {
                        console.log('show -> ' + filterElement.find('.product-name').text());
                        listElement.removeClass('hide-product');
                    } else {
                        console.log('dontshow -> ' + filterElement.find('.product-name').text());
                    }
                }



            });
        });


        return;
    }

    function shadeConfiguratorFilter() {
        resetFilteredList();
        if (jQuery('.filters a.active').length <= 0) {
            return;
        }
        jQuery('.filters a.active').each(function () {
            var filterElement = jQuery(this);
            var dataType = filterElement.attr('data-type');
            var value = filterElement.attr('data-value');
            jQuery('#ajax-product-list>li').each(function () {
                var listElement = jQuery(this);
                var match = value + '|';
                var match2 = '|' + value;

                if (typeof listElement.attr(dataType) === 'undefined') {
                    listElement.addClass('hide-product');
                } else if (listElement.attr(dataType).indexOf('|') >= 0) { // is type arrau
                    if ((listElement.attr(dataType).indexOf(match) < 0 && listElement.attr(dataType).indexOf(match2) < 0)) {
                        listElement.addClass('hide-product');
                    }
                } else { // is type string
                    if (listElement.attr(dataType) !== value) {
                        console.log('hide' + value);
                        listElement.addClass('hide-product');
                    }
                }



            });
        });


        return;
    }
    function maxMobileMaxHeight() {
        var height = parseInt(jQuery(window).height());
        if (window.matchMedia("(max-width: 700px)").matches) {
            jQuery('.filters>.content').css('max-height', (height - 100) + 'px');
        } else {
            jQuery('.filters>.content').removeAttr('style');
        }

    }
    function resetPriceSlider() {
        jQuery(priceSliderId).each(function(){
             jQuery(this)[0].noUiSlider.reset();
        });
       
        jQuery('#ajax-product-list li').removeClass('mismatch-1').removeClass('mismatch-2').removeClass('mismatch-3').removeClass('mismatch-4');
       
    }
    jQuery(document).on('click', '.filters span.reset-all', function (e) {
        hideResetFilter();
        clearShadeConfiguratorFilters();
        resetPriceSlider();

    });
    jQuery(document).on('click', '.filters a', function (e) {
        e.preventDefault();
        if (jQuery(this).hasClass('active')) {
            jQuery(this).removeClass('active');
            if (!multiSelect) {
                jQuery(this).parents('.filter').eq(0).removeClass('active');
            }

        } else {
            if (!multiSelect) {
                jQuery(this).parent().find('a').removeClass('active');
            }
            jQuery(this).addClass('active');
            if (!multiSelect) {
                jQuery(this).parents('.filter').eq(0).addClass('active');
            }
        }


        if (!multiSelect) {
            shadeConfiguratorFilter();
            excludeShadeConfiguratorFilters();
        } else {
            shadeConfiguratorFilterMultiselect();
        }
        showResetFilter();
    });
    jQuery(document).on('click', '.open-filters,.close-filter,.show-filter ', function (e) {
        jQuery('.filters').toggleClass('active');
        jQuery('html').toggleClass('no-scroll-filters');
    });

    jQuery(document).ready(function () {

      
        jQuery(priceSliderId).each(function () {
            var thisElement = jQuery(this);
            var nextElement = jQuery(this).next();
            var rangeMin = parseInt(thisElement.attr('data-min'));
            var rangeMax = parseInt(thisElement.attr('data-max'));
            var dataType = thisElement.attr('data-type');
            var slideKey = thisElement.attr('data-count');
            var completePriceSlider = thisElement.get(0);
            noUiSlider.create(completePriceSlider, {
                start: [rangeMin, rangeMax],
                connect: true,
                step: 1,
                range: {
                    'min': [rangeMin],
                    'max': [rangeMax]
                },
            });

            completePriceSlider.noUiSlider.on('update', function (values) {
                nextElement.text(parseInt(values[0]) + ' - ' + parseInt(values[1]));

            });
            completePriceSlider.noUiSlider.on('change.one', function (values) {
                var min = values[0];
                var max = values[1];
                checkPriceMatch(min, max, dataType,slideKey);
            });
           
        });
        maxMobileMaxHeight();
    });
    jQuery(window).load(function () {
        maxMobileMaxHeight();
    });
    jQuery(window).resize(function () {
        maxMobileMaxHeight();
    });



});