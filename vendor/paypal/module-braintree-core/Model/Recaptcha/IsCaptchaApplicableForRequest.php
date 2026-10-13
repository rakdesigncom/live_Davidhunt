<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Recaptcha;

use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;

class IsCaptchaApplicableForRequest implements IsCaptchaApplicableForRequestInterface
{
    /**
     * Determine whether Captcha should be used for request.
     *
     * This is the default class loaded which currently should always validate as true.
     *
     * @param EndpointInterface $endpoint
     * @return bool
     */
    public function execute(EndpointInterface $endpoint): bool
    {
        return true;
    }
}
