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

namespace PayPal\Braintree\Plugin;

use Magento\ReCaptchaCheckout\Model\WebapiConfigProvider;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaWebapiApi\Api\Data\EndpointInterface;
use PayPal\Braintree\Model\Recaptcha\IsCaptchaApplicableForRequestInterface;

class DisableReCaptchaWebapiApiRestPlaceOrderValidation
{
    /**
     * @param IsCaptchaApplicableForRequestInterface $isCaptchaApplicableForRequest
     */
    public function __construct(
        private readonly IsCaptchaApplicableForRequestInterface $isCaptchaApplicableForRequest
    ) {
    }

    /**
     * Check whether ReCaptcha should be applied on the endpoint, no validation if result is already null.
     *
     * @param WebapiConfigProvider $subject
     * @param ValidationConfigInterface|null $result
     * @param EndpointInterface $endpoint
     * @return ValidationConfigInterface|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfigFor(
        WebapiConfigProvider $subject,
        ?ValidationConfigInterface $result,
        EndpointInterface $endpoint
    ): ?ValidationConfigInterface {
        return $result === null || $this->isCaptchaApplicableForRequest->execute($endpoint) ? $result : null;
    }
}
