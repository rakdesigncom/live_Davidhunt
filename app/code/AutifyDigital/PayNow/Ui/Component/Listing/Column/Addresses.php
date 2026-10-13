<?php
declare(strict_types=1);

namespace AutifyDigital\PayNow\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;

class Addresses extends Column
{

    private $_urlBuilder;

    private $_formKey;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $_urlBuilder,
        FormKey $_formKey,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $_urlBuilder;
        $this->_formKey = $_formKey;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['paynow_id'])) {
                    $billingAddress = $shippingAddress = "";
                    $billingAddress .= $item['billto_name'] . "<br>";
                    $billingAddress .= (isset($item['company_name']) && !empty($item['company_name'])) ? $item['company_name'] . "<br>" : "";
                    $billingAddress .= $item['billto_street1'] . "<br>";
                    $billingAddress .= $item['billto_street2'] . "<br>";
                    $billingAddress .= $item['billto_city'] . "<br>";
                    $billingAddress .= $item['billto_state'] . "<br>";
                    $billingAddress .= $item['billto_country'] . "<br>";
                    $billingAddress .= $item['billto_postcode'] . "<br>";
                    $billingAddress .= $item['billto_phone'] . "<br>";

                    $shippingAddress .= (isset($item['shipto_name']) && !empty($item['shipto_name'])) ? $item['shipto_name'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_street1']) && !empty($item['shipto_street1'])) ? $item['shipto_street1'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_street2']) && !empty($item['shipto_street2'])) ? $item['shipto_street2'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_city']) && !empty($item['shipto_city'])) ? $item['shipto_city'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_state']) && !empty($item['shipto_state'])) ? $item['shipto_state'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_country']) && !empty($item['shipto_country'])) ? $item['shipto_country'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_postcode']) && !empty($item['shipto_postcode'])) ? $item['shipto_postcode'] . "<br>" : "";
                    $shippingAddress .= (isset($item['shipto_phone']) && !empty($item['shipto_phone'])) ? $item['shipto_phone'] . "<br>" : "";

                    $item[$name . '_billing'] = $billingAddress;
                    $item[$name . '_shipping'] = $shippingAddress;
                    $item[$name] = '<a class="action-activity-log-view">'. __('View Address').'</a>';
                }
            }
        }
        return $dataSource;
    }
}
