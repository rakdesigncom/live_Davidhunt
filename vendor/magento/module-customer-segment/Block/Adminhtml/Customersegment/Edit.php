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
namespace Magento\CustomerSegment\Block\Adminhtml\Customersegment;

use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Phrase;
use Magento\Framework\App\ObjectManager;

/**
 * Edit form for customer segment configuration
 *
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Service for accessing registry data and managing temporary variables.
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     * @param Escaper|null $escaper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = [],
        ?Escaper $escaper = null
    ) {
        $this->_coreRegistry = $registry;
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(Escaper::class);
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Update "Delete" button
     * Add "Refresh Segment Data" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_customersegment';
        $this->_blockGroup = 'Magento_CustomerSegment';

        parent::_construct();

        $objId = (int)$this->getRequest()->getParam($this->_objectId);
        if (!empty($objId)) {
            $confirmMessage = $this->escaper->escapeJs(
                $this->escaper->escapeHtml(__('Are you sure you want to do this?'))
            );
            $this->buttonList->update(
                'delete',
                'onclick',
                'deleteConfirm(\'' . $confirmMessage . '\', \'' . $this->getDeleteUrl()
                . '\', {\'data\': {\'customersegment_id\': ' . $objId . '}})'
            );
        }

        /** @var $segment \Magento\CustomerSegment\Model\Segment */
        $segment = $this->_coreRegistry->registry('current_customer_segment');
        if ($segment && $segment->getId()) {
            $this->buttonList->add(
                'match_customers',
                [
                    'label' => __('Refresh Segment Data'),
                    'onclick' => 'setLocation(\'' . $this->getMatchUrl() . '\')'
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
     * Get url for run segment customers matching
     *
     * @return string
     */
    public function getMatchUrl()
    {
        $segment = $this->_coreRegistry->registry('current_customer_segment');
        return $this->getUrl('*/*/match', ['id' => $segment->getId()]);
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $segment = $this->_coreRegistry->registry('current_customer_segment');
        if ($segment->getSegmentId()) {
            return __("Edit Segment '%1'", $this->escaper->escapeHtml($segment->getName()));
        } else {
            return __('New Segment');
        }
    }
}
