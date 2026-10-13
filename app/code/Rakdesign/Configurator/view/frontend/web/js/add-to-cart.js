require([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {

    function hideShadeConfirmation() {
        jQuery('.configurator-add-to-cart-confirm').fadeOut(200);
    }
    jQuery(document).on("click", ".modal-cancel", function (e) {
        e.preventDefault();
        hideShadeConfirmation();
    });
    jQuery(document).on("click", ".configuration-add-to-cart-btn.separate-btn", function (e) {
        e.preventDefault();
        var element = jQuery(this);
        element.addClass('blocked');
        var url = element.attr('data-url');
        var product_id = element.attr('data-id');
        var qtyTimes = 1;
        var qty = parseInt(element.attr('data-qty')) * qtyTimes;
        jQuery('.action.skip-cart .tooltip').remove();

        if (element.hasClass('configuration-add-to-cart-btn')) {
            element.find('span').text('adding...');
        }
        jQuery.ajax({
            type: "POST",
            url: url,
            data: {product: [product_id], qty: [qty]}
        })
                .done(function (response, status, xhr) {
                    if (status === "success") {


                        if (response.status) {

                            var sections = ['cart'];
                            customerData.invalidate(sections);
                            customerData.reload(sections, true);
                            element.removeClass('blocked');
                            element.addClass('active');
                            if (element.hasClass('configuration-add-to-cart-btn')) {
                                element.find('span').text('added');
                                setTimeout(
                                        function ()
                                        {
                                            element.find('span').text(element.attr('title'));
                                        }, 2000);
                            }
                            hideShadeConfirmation();
                            setTimeout(
                                    function ()
                                    {
                                        jQuery('.action.showcart .tooltip').remove();
                                    }, 4000);

                        } else {
                            element.removeClass('blocked');
                            element.find('span').text('not added');
                            setTimeout(
                                    function ()
                                    {
                                        element.find('span').text(element.attr('title'));
                                    }, 2000);
                        }
                    }

                });

    });

    jQuery(document).on("click", ".configuration-add-to-cart-btn:not(.separate-btn)", function (e) {
        e.preventDefault();
        var element = jQuery(this);

        var url = element.attr('data-url');
        var product_id = element.attr('data-id');
        var product_id2 = element.attr('data-id2');

        var stock1 = parseInt(element.attr('data-stock'));
        var stock2 = parseInt(element.attr('data-stock2'));

        if ((stock1 <= 0 || stock2 <= 0) && !element.hasClass('force-add')) {
            jQuery('.configurator-add-to-cart-confirm').fadeIn(200);
            var bulkBtn = jQuery('.configurator-add-to-cart-confirm .configuration-add-to-cart-btn:not(.separate-btn)');
            var shadeBtn = jQuery('.configurator-add-to-cart-confirm .configuration-add-to-cart-btn-shade');
            var baseBtn = jQuery('.configurator-add-to-cart-confirm .configuration-add-to-cart-btn-base');
            shadeBtn.hide();
            baseBtn.hide();
            if (typeof element.attr('data-base') !== 'undefined' && typeof element.attr('data-shade') !== 'undefined') {
                jQuery('.product-not-available').hide().text('');
            }
            if (bulkBtn.length > 0) {
                bulkBtn.attr('data-id', product_id);
                bulkBtn.attr('data-id2', product_id2);
                bulkBtn.attr('data-stock', stock1);
                bulkBtn.attr('data-stock2', stock2);
                bulkBtn.attr('data-qty', element.attr('data-qty'));
                bulkBtn.attr('data-qty2', element.attr('data-qty2'));
            }
            if (stock1 > 0) {
                baseBtn.show();
                baseBtn.attr('data-qty', element.attr('data-qty'));
                baseBtn.attr('data-id', product_id);
            } else if (typeof element.attr('data-base') !== 'undefined') {
                jQuery('.product-not-available-base').show().text(element.attr('data-base'));

            }
            if (stock2 > 0) {
                shadeBtn.show();
                shadeBtn.attr('data-qty', element.attr('data-qty2'));
                shadeBtn.attr('data-id', product_id2);
            } else if (typeof element.attr('data-shade') !== 'undefined') {
                jQuery('.product-not-available-shade').show().text(element.attr('data-shade'));
            }

            return;
        }
        element.addClass('blocked');
        var qtyTimes = 1;
        if (jQuery('.configurator-base-shade-qty').length > 0) {
            qtyTimes = parseInt(jQuery('.configurator-base-shade-qty').val())
        }
        var qty = parseInt(element.attr('data-qty')) * qtyTimes;
        var qty2 = parseInt(element.attr('data-qty2')) * qtyTimes;
        jQuery('.action.skip-cart .tooltip').remove();

        if (element.hasClass('configuration-add-to-cart-btn')) {
            element.find('span').text('adding...');
        }
        jQuery.ajax({
            type: "POST",
            url: url,
            data: {product: [product_id2], qty: [qty2]}
        })
                .done(function (response, status, xhr) {
                    if (status === "success") {

                        if (response.status) {
                            jQuery.ajax({
                                type: "POST",
                                url: url,
                                data: {product: [product_id], qty: [qty]}
                            })
                                    .done(function (response, status, xhr) {
                                        if (status === "success") {


                                            if (response.status) {

                                                var sections = ['cart'];
                                                customerData.invalidate(sections);
                                                customerData.reload(sections, true);
                                                element.removeClass('blocked');
                                                element.addClass('active');
                                                if (element.hasClass('configuration-add-to-cart-btn')) {
                                                    element.find('span').text('added');
                                                    setTimeout(
                                                            function ()
                                                            {
                                                                element.find('span').text(element.attr('title'));
                                                            }, 2000);
                                                }
                                                hideShadeConfirmation();
                                                setTimeout(
                                                        function ()
                                                        {
                                                            jQuery('.action.showcart .tooltip').remove();
                                                        }, 4000);

                                            } else {
                                                element.removeClass('blocked');
                                                element.find('span').text('base not added');
                                                hideShadeConfirmation();
                                                setTimeout(
                                                        function ()
                                                        {
                                                            element.find('span').text(element.attr('title'));
                                                        }, 2000);
                                            }
                                        }

                                    });
                        } else {
                            element.removeClass('blocked');
                            element.find('span').text('base not added');
                            hideShadeConfirmation();
                            setTimeout(
                                    function ()
                                    {
                                        element.find('span').text(element.attr('title'));
                                    }, 2000);
                        }
                    }
                    if (status === "error") {
                        hideShadeConfirmation()
                        element.removeClass('blocked');
                    }

                });
    });

});
