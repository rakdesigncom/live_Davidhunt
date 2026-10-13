<?php
namespace Fyb\Faq\Model;
class Qa extends \Magento\Framework\Model\AbstractModel
{
	protected function _construct()
	{
		$this->_init('Fyb\Faq\Model\ResourceModel\Qa');
	}
}
