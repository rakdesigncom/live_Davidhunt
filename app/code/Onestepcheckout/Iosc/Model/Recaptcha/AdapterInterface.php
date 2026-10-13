<?php
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
declare(strict_types=1);

namespace Onestepcheckout\Iosc\Model\Recaptcha;

/**
 * Interface for reCaptcha adapters
 */
interface AdapterInterface
{
    /**
     * Server side validate token
     *
     * @param string $token
     * @param string $clientIp
     * @param string $hostname
     */
    public function validate($token, $clientIp, $hostname);

    /**
     * Get reCaptcha settings
     */
    public function getCaptchaSettings();

    /**
     * If reCaptcha is configured
     */
    public function isConfigured();
}
