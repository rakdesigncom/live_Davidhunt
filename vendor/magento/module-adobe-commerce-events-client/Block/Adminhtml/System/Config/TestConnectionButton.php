<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Block\Adminhtml\System\Config;

use Magento\AdobeCommerceEventsClient\Config\AdobeIoConfigurationChecker;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Field for testing Commerce eventing connection
 */
class TestConnectionButton extends Field
{
    /**
     * Path to template file
     *
     * @var string
     */
    protected $_template = 'Magento_AdobeCommerceEventsClient::system/config/test_connection.phtml';

    /**
     * @param Context $context
     * @param AdobeIoConfigurationChecker $configChecker
     */
    public function __construct(
        Context $context,
        private AdobeIoConfigurationChecker $configChecker
    ) {
        parent::__construct($context);
    }

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Generates button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id' => 'test_connection',
                'label' => __('Send Test Event'),
                'disabled' => $this->isButtonDisabled()
            ]
        );

        return $button->toHtml();
    }

    /**
     * Gets ajax url for testing Commerce eventing connection
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('adminhtml/connection/testConnection');
    }

    /**
     * Checks Adobe I/O configuration to determine whether the button should be disabled
     *
     * @return bool
     */
    public function isButtonDisabled(): bool
    {
        return !$this->configChecker->isComplete();
    }
}
