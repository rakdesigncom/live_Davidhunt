/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
define(
    [
        'jquery',
        'ko',
        'underscore',
        'reCaptcha',
        'uiComponent',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'mage/utils/wrapper',
        'Magento_Checkout/js/model/quote'
    ],
    function (jQuery, ko, _, reCaptcha, uiComponent, domObserver, wrapper, quote) {
        'use strict';


        return uiComponent.extend({

            initialize: function () {
                this._super();

                this.token = ko.observable(false);
                this.widgetId = ko.observable(false);
                this.captchaInitialized = ko.observable(false);

                grecaptcha.ready(
                    function(){
                        this.prepDomElements();
                    }.bind(this)
                );

            },

            wrapAjaxRequests: function() {
                jQuery.ajax = wrapper.wrap(jQuery.ajax ,
                    function (originalMethod, url, options) {
                        if ( typeof url === "object" ) {
                            options = url;
                            url = undefined;
                        }
                        var allowed  = ["checkout/onepage/update"].concat(this.cnf);
                        var methods = ["post","delete","put","get"];
                        var matches = _.filter(
                            allowed,
                            function (seek) {
                                seek = seek.replace(':cartId',quote.getQuoteId());
                                return options.url.indexOf(seek) !== -1;
                            }
                        );

                        if (
                            matches.length > 0 &&
                            methods.indexOf(options.type.toLowerCase()) >= 0 &&
                            !_.isUndefined(window.grecaptcha)
                        ) {

                            var deferred = jQuery.Deferred();
                            var widgetId = this.widgetId();
                            deferred.success = deferred.done;

                            grecaptcha.execute(widgetId).then(
                                function(token) {
                                    options.headers = _.extend({'X-Recaptcha-Token': token}, options.headers);
                                    options.headers = _.extend({'X-Cart-Token': quote.getQuoteId()}, options.headers);

                                    originalMethod(url, options).then(
                                        function( data, textStatus, jqXHR ) {
                                            deferred.resolve(data, textStatus, jqXHR);
                                        },
                                        function( jqXHR, textStatus, errorThrown ) {
                                            grecaptcha.reset(widgetId);
                                            deferred.reject(jqXHR, textStatus, errorThrown);
                                        }
                                    );
                                }
                            );
                            return deferred;
                        } else {
                            return originalMethod(url, options);
                        }
                    }.bind(this)
                );
            },

            prepDomElements: function() {
                domObserver.get(
                    '#iosc-summary > #' + this.getReCaptchaId() + '-wrapper',
                    function(elem) {
                        jQuery(elem).find('.g-recaptcha').attr('id', this.getReCaptchaId());
                        domObserver.get(
                            '#opc-sidebar > #' + this.getReCaptchaId() + '-wrapper',
                            function(elem) {
                               jQuery(elem).remove();
                               this.initCaptcha();

                            }.bind(this)
                        );
                    }.bind(this)
                );
            },

            /**
             * Initialize reCaptcha after first rendering
             */
            initCaptcha: function () {

                if (!this.captchaInitialized()) {
                    var widgetId;
                    widgetId = grecaptcha.render(this.getReCaptchaId(), {
                        'sitekey': this.settings.rendering.sitekey,
                        'theme': this.settings.rendering.theme,
                        'size': this.settings.rendering.size,
                        'badge': this.settings.rendering.badge,
                        'callback': function (token) {
                                        this.reCaptchaCallback(token);
                                        this.validateReCaptcha(true);
                                    }.bind(this),
                        'expired-callback': function () {
                                                this.validateReCaptcha(false);
                                            }.bind(this)
                    });
                    this.widgetId(widgetId);
                    this.captchaInitialized(true);
                    this.wrapAjaxRequests();
                }
            },

            /**
             * Recaptcha callback
             * @param {String} token
             * @return {String}
             */
            reCaptchaCallback: function (token) {
                this.token(token);
            },

            /**
             * Recaptcha validation callback
             * @param {Boolean} status
             * @return {Boolean}
             */
            validateReCaptcha: function (status) {
                return status;
            },

            /**
             * Get Recaptcha Id
             * @return {String}
             */
            getReCaptchaId: function() {
                return this.reCaptchaId.toString();
            }

        });
    }


);
