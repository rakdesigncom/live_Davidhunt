<?php
/**
 * @copyright Copyright (c) 2023
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentStatus
 */
class PaymentStatus implements OptionSourceInterface
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray(): array
	{
		return [
			['value' => 'pending', 'label' => __('Pending')],
			['value' => 'pending_payment', 'label' => __('Pending Payment')],
		];
	}

}