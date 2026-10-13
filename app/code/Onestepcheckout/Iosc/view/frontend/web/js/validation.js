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
        "uiComponent",
        "jquery",
        "Magento_Ui/js/lib/validation/validator"
    ],
    function (
        uiComponent,
        jQuery,
        validator
    ) {
        "use strict";
        return uiComponent.extend({
            initialize: function () {
                this._super();
                validator.addRule(
                    'validate-customer-name',
                    function(value){
                        let result = true;
                        if (value.length > 0) {
                            const regex = /(?:[\p{L}\p{M}\s\d,\-_'’`])+/iu;
                            result = regex.exec(value);
                            result = (result !== null && result[0].length === result['input'].length) ? true : false;
                        }
                        return result;
                    },
                    jQuery.mage.__('Only letters, numbers, spaces and mix of \- \_ \' \’ \` are allowed.')
                );
            }
        });
    }
);
