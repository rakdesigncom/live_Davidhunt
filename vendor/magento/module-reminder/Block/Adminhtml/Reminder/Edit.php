<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2014 Adobe
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
namespace Magento\Reminder\Block\Adminhtml\Reminder;

use Magento\Framework\Registry;

/**
 * Reminder rule edit form block
 *
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Service for accessing registry data and managing temporary variables.
     *
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * Helper for accessing reminder-specific functionality and configuration.
     *
     * @var \Magento\Reminder\Helper\Data
     */
    protected $_reminderData = null;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Reminder\Helper\Data $reminderData
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Reminder\Helper\Data $reminderData,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_reminderData = $reminderData;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     *
     * Add standard buttons
     * Add "Run Now" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Magento_Reminder';
        $this->_controller = 'adminhtml_reminder';

        parent::_construct();

        /** @var $rule \Magento\Reminder\Model\Rule */
        $rule = $this->_coreRegistry->registry('current_reminder_rule');
        if ($rule && $rule->getId()) {
            $confirm = __('Are you sure you want to match this rule now?');
            if ($limit = $this->_reminderData->getOneRunLimit()) {
                $confirm .= ' ' . __(
                    'No more than %1 customers may receive the reminder email after this action.',
                    $limit
                );
            }
            $escapedConfirm = $this->escapeJs($this->escapeHtml($confirm));
            $this->buttonList->add(
                'run_now',
                [
                    'label' => __('Run Now'),
                    'onclick' => "confirmSetLocation('{$escapedConfirm}', '{$this->getRunUrl()}')"
                ],
                -1
            );
        }

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            3
        );
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $rule = $this->_coreRegistry->registry('current_reminder_rule');
        if ($rule->getRuleId()) {
            return __("Edit Rule '%1'", $this->escapeHtml($rule->getName()));
        } else {
            return __('New Rule');
        }
    }

    /**
     * Get url for immediate run sending process
     *
     * @return string
     */
    public function getRunUrl()
    {
        $rule = $this->_coreRegistry->registry('current_reminder_rule');
        return $this->getUrl('adminhtml/*/run', ['id' => $rule->getRuleId()]);
    }
}
