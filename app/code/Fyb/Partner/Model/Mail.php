<?php

namespace Fyb\Partner\Model;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;

class Mail
{
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager = null
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Send email from contact form
     *
     * @param string $replyTo
     * @param array $variables
     * @return void
     */
    public function send($replyTo, array $variables)
    {
        /** @see \Magento\Contact\Controller\Index\Post::validatedParams() */
        $replyToName = !empty($variables['data']['name']) ? $variables['data']['name'] : null;

        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('partner_request')
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFromByScope($this->scopeConfig->getValue('partner/general/email_sender', ScopeInterface::SCOPE_STORE))
                ->addTo($this->scopeConfig->getValue('partner/general/email_recipient', ScopeInterface::SCOPE_STORE))
                ->setReplyTo($replyTo, $replyToName)
                ->getTransport();

            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
