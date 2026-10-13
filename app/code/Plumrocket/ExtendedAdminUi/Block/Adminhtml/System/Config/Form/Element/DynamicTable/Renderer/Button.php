<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @since 1.1.0
 */
class Button extends AbstractRenderer
{

    /**
     * @inheritDoc
     */
    public function render(DataObject $row): string
    {
        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->addData(
                [
                    'id' => 'preload_url_list_remove_button',
                    'label' => __('Remove'),
                    'type' => 'button',
                    'class' => 'delete remove-row-value ' . $this->getColumn()->getId(),
                ]
            )
            ->toHtml();
    }
}
