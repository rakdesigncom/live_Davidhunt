<?php

namespace Fyb\Faq\Block\Adminhtml\Qa\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;

class Info extends Generic implements TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Qa Info');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Qa Info');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form fields
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var $model \Fyb\Faq\Model\Qa */
        $model = $this->_coreRegistry->registry('fyb_faq_qa');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('qa_');
        $form->setFieldNameSuffix('qa');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General')]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'entity_id',
                'hidden',
                ['name' => 'entity_id']
            );
        }

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Is Active'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Yes'), '0' => __('No')],
            ]
        );

        $fieldset->addField(
            'question',
            'text',
            [
                'name' => 'question',
                'label' => __('Question'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'required' => true,
            ]
        );

        $wysiwygConfig = $this->_wysiwygConfig->getConfig();
        $fieldset->addField(
            'answer',
            'editor',
            [
                'name' => 'answer',
                'label' => __('Answer'),
                'required' => true,
                'config' => $wysiwygConfig,
            ]
        );

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
