<?php

namespace Fyb\StockistLocator\Block\Adminhtml\Edit\Button;

class Delete extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getStockistId()) {
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this?'
                    ) . '\', \'' . $this->getDeleteUrl() . '\')',
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id' => $this->getStockistId()]);
    }
}
