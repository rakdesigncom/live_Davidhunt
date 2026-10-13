<?php
namespace AutifyDigital\PayNow\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Amount extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                // Format the 'amount' value to two decimal places
                if (isset($item['amount'])) {
                    $item['amount'] = number_format($item['amount'], 2);
                }
            }
        }

        return $dataSource;
    }
}
