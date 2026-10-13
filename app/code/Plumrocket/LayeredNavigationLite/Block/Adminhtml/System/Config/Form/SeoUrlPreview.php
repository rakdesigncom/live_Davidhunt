<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @since 1.3.0
 */
class SeoUrlPreview extends AbstractElement
{

    /**
     * Get Preview URL HTML.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        return '<span id="' . $this->getHtmlId() . '" ' .
            'style="display: block;padding: 6px;background: #aacbff;color: #000000;border-radius: 3px;" ' .
            'class="admin__control">https://example.com/jackets.html</span>';
    }
}
