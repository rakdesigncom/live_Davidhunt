<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Block\Adminhtml\System\Config;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;

/**
 * Field for creating event provider button and popup form.
 */
class EventProviderButton extends Field implements CommentInterface
{
    /**
     * Path to template file
     *
     * @var string
     */
    protected $_template = 'Magento_AdobeIoEventsClient::system/config/event_provider.phtml';

    /**
     * @param Context $context
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param FormFactory $formFactory
     * @param string $eventProviderListUrl
     */
    public function __construct(
        private Context $context,
        private AdobeIOConfigurationProvider $configurationProvider,
        private FormFactory $formFactory,
        private readonly string $eventProviderListUrl = 'adminhtml/eventProvider/index'
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
     * Gets ajax url to create event provider
     *
     * @return string
     */
    public function getEventProviderUrl(): string
    {
        return $this->getUrl('adminhtml/eventProvider/create');
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
                'id' => 'create_event_provider',
                'label' => __('Create Event Provider'),
                'disabled' => $this->isButtonDisabled()
            ]
        );

        return $button->toHtml();
    }

    /**
     * Generates event provider html form
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormHtml(): string
    {
        $form = $this->formFactory->create();
        $fieldset = $form->addFieldset(
            'event-provider-fieldset',
            ['class' => 'admin__scope-old form-inline']
        );

        $fieldset->addField(
            'event-provider-label',
            'text',
            [
                'name' => 'label',
                'label' => __('Label'),
                'title' => __('label'),
                'class' => 'required-entry',
                'required' => true,
                'after_element_html' => $this->addSpanElement('labelNote')
            ]
        );

        $fieldset->addField(
            'event-provider-description',
            'text',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('description'),
                'class' => 'required-entry',
                'required' => true,
                'after_element_html' => $this->addSpanElement('descriptionNote')
            ]
        );

        return $form->toHtml();
    }

    /**
     * Gets the comment which includes a hyperlink to navigate to the event provider grid.
     *
     * @param string $elementValue
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getCommentText($elementValue): string
    {
        return sprintf(
            'Use the <a href="%s">Event Providers page</a> to view and manage event providers',
            $this->getUrl($this->eventProviderListUrl)
        );
    }

    /**
     * Checks Adobe I/O configuration instance field is configured
     *
     * @return bool
     */
    private function isButtonDisabled(): bool
    {
        try {
            $this->configurationProvider->retrieveInstanceId();
        } catch (NotFoundException $e) {
            return true;
        }

        return false;
    }

    /**
     * Adds html span element after the input field in provider form
     *
     * @param String $elementName
     * @return string
     */
    private function addSpanElement(String $elementName): string
    {
        return '<span name="' . $elementName . '" class="admin__field-note">Use only letters (a-z or A-Z),
                numbers (0-9), underscores(_), spaces, or hyphens (-).</span>';
    }
}
