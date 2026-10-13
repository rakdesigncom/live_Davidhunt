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
namespace Onestepcheckout\Iosc\Block\Adminhtml\System\Config\Form\Field\FieldArray;

use Magento\Framework\Data\Form\Element;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory;
use Magento\Framework\View\Element\Context;

class Renderer extends \Magento\Framework\View\Element\AbstractBlock
{

    /**
     * @var Element\Factory
     */
    public $elementFactory;

    /**
     * @var Factory
     */
    public $objectFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Element\Factory $elementFactory
     * @param Factory $objectFactory
     * @param array $data
     */
    public function __construct(
        Context         $context,
        Element\Factory $elementFactory,
        Factory         $objectFactory,
        array           $data = []
    ) {

        $this->elementFactory = $elementFactory;
        $this->objectFactory = $objectFactory;
        parent::__construct($context, $data);
    }

    /**
     * ObjectFactory wrapper to receive dull objects with data
     *
     * @param array $data
     * @return DataObject $formData
     */
    protected function _getFormMock(array $data = [])
    {
        $formData = $this->objectFactory->create($data);
        return $formData;
    }

    /**
     * ElementFactory wrapper to get elements by type, assign data and form dependency
     *
     * @param string $type
     * @param array $data
     * @param array $formData
     * @return Element $element
     */
    protected function _getElement($type = 'checkbox', array $data = [], array $formData = [])
    {
        $element = $this->elementFactory
            ->create($type, ['data' => $data])
            ->setForm($this->_getFormMock($formData));
        return $element;
    }

    /**
     * @inheritDoc
     * @see \Magento\Framework\View\Element\AbstractBlock::_toHtml()
     */
    protected function _toHtml()
    {
        $data = [
            'html_id' => $this->getInputId(),
            'name' => $this->getInputName(),
            'options' => $this->getElementOptions()
        ];

        /*
         *  \Magento\Framework\Data\Form\Element\*
         *  has a dependency where you need form data to be present to get the element name
         */
        $formData = ['html_id_prefix' => '', 'html_id_suffix' => ''];

        $element = $this->_getElement($this->getElementType(), $data, $formData);
        $elementHtml = str_replace(["\r", "\n"], '', $element->getElementHtml());

        if ($this->getElementType() === 'checkbox') {
            $elementHtml = str_replace(
                'value=""',
                'value="<%- ' . $this->getColumnName() . ' %>"',
                $element->getElementHtml()
            );
        }
        if ($this->getElementType() == 'checkbox') {
            $elementHtml = str_replace('checkbox', 'hidden', $elementHtml).$elementHtml;
        }

        return $elementHtml;
    }
}
