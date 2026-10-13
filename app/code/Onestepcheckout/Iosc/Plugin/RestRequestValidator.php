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
namespace Onestepcheckout\Iosc\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\ScopeInterface;
use Magento\Webapi\Controller\Rest\RequestValidator\Interceptor;
use Magento\Webapi\Controller\Rest\Router;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\Recaptcha\AdapterInterface;
use Psr\Log\LoggerInterface;

/**
 *
 * REST request validator plugin class
 *
 */
class RestRequestValidator
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Router
     */
    protected $router;
    /**
     * @var AdapterInterface
     */
    protected $reCaptchaAdapter;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     *
     * @param Request $request
     * @param Router $router
     * @param AdapterInterface $reCaptchaAdapterInterface
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        Router $router,
        AdapterInterface $reCaptchaAdapterInterface,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
            $this->request = $request;
            $this->router = $router;
            $this->reCaptchaAdapter = $reCaptchaAdapterInterface;
            $this->helper = $helper;
            $this->scopeConfig = $scopeConfig;
            $this->logger = $logger;
    }

    /**
     * AfterValidate
     *
     * @param Interceptor $subject
     * @param $result
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterValidate(
        Interceptor $subject,
        $result
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }

        $route = $this->router->match($this->request);
        $cartId = $this->request->getParam('cartId');
        $cartToken = $this->request->getHeader('x-cart-token');
        $captchaToken = $this->request->getHeader('x-recaptcha-token');
        $routePath = '/'.$route->getRoutePath();

        $validationConfig = $this->scopeConfig->getValue(
            'onestepcheckout_iosc/rest',
            ScopeInterface::SCOPE_STORE
        );

        $validationConfig = $this->helper->replaceConfigValues($validationConfig);

        $isXmlHttpRequestPaths = $validationConfig['is_ajax_rules'] ?? [];
        $isRecaptchaPaths = $validationConfig['is_recaptcha_rules'] ?? [];
        $isRateLimitPaths = $validationConfig['is_ratelimited_rules'] ?? [];
        if (!empty($isXmlHttpRequestPaths) &&
            in_array($routePath, $isXmlHttpRequestPaths) &&
            !$this->request->isXmlHttpRequest()
        ) {
            throw new Exception(__('Operation not allowed XmlHttpRequest'));
        }

        if ($this->reCaptchaAdapter->isConfigured() &&
            !empty($isRecaptchaPaths) &&
            in_array($routePath, $isRecaptchaPaths)
        ) {

            $response = $this
                ->reCaptchaAdapter
                ->validate(
                    $captchaToken,
                    $this->request->getClientIp(),
                    $this->request->getHttpHost()
                );

            if (!$response->isSuccess()) {
                $message = [
                    'route' => $routePath,
                    'cartId' => $cartId,
                    'ip' => $this->request->getClientIp(),
                    'host' => $this->request->getHttpHost(),
                    'errors' => implode(', ', $response->getErrorCodes())
                ];
                $this->logger->debug(__('reCaptcha failed for: %1', var_export($message, true)));
                throw new Exception(__('Operation not allowed, reCaptcha validation failed'));
            }
        }
    }
}
