<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Custom fieldset without any visible elements.
 *
 * @since 1.0.6
 */
class PureFieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    /**
     * Render field group without header and footer
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->_getChildrenElementsHtml($element);
    }
}
