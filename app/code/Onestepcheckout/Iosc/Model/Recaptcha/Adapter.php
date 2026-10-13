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

use Google\Recaptcha;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\ReCaptchaVersion3Invisible\Model\Frontend\UiConfigProvider;
use Magento\ReCaptchaVersion3Invisible\Model\Frontend\ValidationConfigProvider;
use Magento\Store\Model\ScopeInterface;
use MSP\ReCaptcha\Model\Config;
use MSP\ReCaptcha\Model\LayoutSettings;
use ReCaptcha\ReCaptchaFactory;

/**
 * Adapter for the recaptcha libraries
 * to solve BC breaking between magento2.3 and magento2.4 series
 * while still keep settings for customers convenience
 */
class Adapter implements AdapterInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var ReCaptchaFactory
     */
    protected $reCaptchaFactory;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ReCaptchaFactory $reCaptchaFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ReCaptchaFactory $reCaptchaFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->reCaptchaFactory = $reCaptchaFactory;
    }

    /**
     * Validate method
     *
     * @param string $token
     * @param string $clientIp
     * @param string $hostname
     * @return boolean
     */
    public function validate($token = null, $clientIp = null, $hostname = null)
    {
        if (class_exists(UiConfigProvider::class)) {
            $privKey = $this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/private_key');
            $threshold = (float)$this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/score_threshold');
        }
        if (empty($privKey) && class_exists(LayoutSettings::class)) {
            $privKey = $this->getConfigValue('msp_securitysuite_recaptcha/general/private_key');
            $threshold  = (float)$this->getConfigValue('msp_securitysuite_recaptcha/general/score_threshold');
        }

        /** @var ReCaptcha $reCaptcha */
        $reCaptcha = $this->reCaptchaFactory->create(['secret' => $privKey]);
        $result = $reCaptcha
                    ->setScoreThreshold($threshold)
                    ->setExpectedHostname($hostname)
                    ->verify($token, $clientIp);

        return $result;
    }

    /**
     * Get frontend settigns for captcha
     *
     * @return array
     */
    public function getCaptchaSettings()
    {

        $settings = [];
        if (class_exists(UiConfigProvider::class)) {

            $settings = [
                'rendering' => [
                    'sitekey' => $this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/public_key'),
                    'badge' => $this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/position'),
                    'size' => 'invisible',
                    'theme' => $this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/theme'),
                    'hl'=> $this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/lang')
                ],
                'invisible' => true,
            ];

        }
        if (empty($settings) && class_exists(LayoutSettings::class)) {
            $settings = [
                'rendering' => [
                    'sitekey' => $this->getConfigValue('msp_securitysuite_recaptcha/general/public_key'),
                    'badge' => $this->getConfigValue('msp_securitysuite_recaptcha/frontend/position'),
                    'size' => 'invisible',
                    'theme' => $this->getConfigValue('msp_securitysuite_recaptcha/frontend/theme'),
                    'hl'=> $this->getConfigValue('msp_securitysuite_recaptcha/frontend/lang')
                ],
                'invisible' => true,
            ];
        }

        return $settings;
    }

    /**
     * Check if recaptcha has configuration
     *
     * @return boolean
     */
    public function isConfigured()
    {

        $result = false;
        if (class_exists(ValidationConfigProvider::class)) {
            if (!empty($this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/public_key')) &&
                !empty($this->getConfigValue('recaptcha_frontend/type_recaptcha_v3/private_key'))
            ) {
                $result = true;
            }
        }
        if (class_exists(Config::class)) {
            if (!empty($this->getConfigValue('msp_securitysuite_recaptcha/general/public_key')) &&
                !empty($this->getConfigValue('msp_securitysuite_recaptcha/general/private_key')) &&
                $this->getConfigValue('msp_securitysuite_recaptcha/general/type') === 'recaptcha_v3'
            ) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * GetConfigValue method
     *
     * @param string $path
     * @return string
     */
    protected function getConfigValue($path)
    {
        return trim((string)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        ));
    }
}
