<?php

class magentoGeneral {
	static private $procObj;

	//###########################
	static public function genConfig($procObj) {
		self::$procObj = $procObj;
	}

	//###########################
	static public function updateOrderStatus($orderNumber, $orderState, $orderStatus, $createComment = false, $program) {
		$rc = true;
		
		$order = self::$procObj->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderNumber);
		
		if (is_numeric($order->getEntityId())) {
			try {			
				if ($orderState != '') {
					generalLib::mesg('Setting order state to \'' . $orderState . '\'  for order \'' . $orderNumber . '\'');
					$order->setData('state', $orderState);
				}
				
				generalLib::mesg('Setting order status to \'' . $orderStatus . '\'  for order \'' . $orderNumber . '\'');
				$order->setStatus($orderStatus);
				
				if ($createComment) {
					$history = $order->addStatusHistoryComment('Order marked as ' . $orderStatus . ' by the ' . $program . '.', false);
					$history->setIsCustomerNotified(false);
				}
			
				$order->save();
			}
			
			catch(Exception $e) {
				generalLib::mesg($e->getMessage());
				$rc = false;
			}
		} else {
			generalLib::error('Cannot update status. Order ' . $orderNumber . ' not found');
			$rc = false;
		}
		
		return $rc;
	}
	
	
	//###########################
	static public function createOrderComment($ordNum, $comment, $notifyCustomer = false) {
		$rc = true;

		if (empty($comment)) {
			generalLib::error('Cannot create comment for order \'' . $ordNum . '\'  - comment empty');
			$rc = false;
		}

		if ($rc) {
			$order = self::$procObj->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($ordNum);
		
			if (is_numeric($order->getEntityId())) {
				$history = $order->addStatusHistoryComment($comment, $notifyCustomer);
				$history->setIsCustomerNotified($notifyCustomer);
			
				try {
					generalLib::mesg('Adding order status comment \'' . $comment . '\' for order \'' . $ordNum . '\'');
					$order->save();
				}
			
				catch(Exception $e) {
					generalLib::mesg($e->getMessage());
					$rc = false;
				}
			} else {
				generalLib::error('Cannot create comment. Order \'' . $ordNum . '\' not found');
				$rc = false;
			}
		}
	
		return $rc;
	}
	
	
	//###########################
	static public function updateOrderExportStatus($ordNum, $ordExpStatus) {
		$rc = true;
		
		$order = self::$procObj->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($ordNum);
		
		if (is_numeric($order->getEntityId())) {
			$order->setOrderExportStatus($ordExpStatus);

			try {
					generalLib::mesg('Setting order export status to \'' . $ordExpStatus . '\'  for order \'' . $ordNum . '\'');
					$order->save();
			}
			
			catch(Exception $e) {
				generalLib::mesg($e->getMessage());
				$rc = false;
			}
		} else {
			generalLib::error('Cannot update order export status. Order \'' . $ordNum . '\' not found');
			$rc = false;
		}
		
		return $rc;
	}
	
	
	//###########################
	static public function getLocalTimeStamp($dateTime = null) {

		$tz = self::$procObj->objectManager->get('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');

		$dateTimeAsTimeZone = $tz
                                        ->date(new \DateTime( $dateTime ))
                                        ->format('d-m-Y H:i:s');

		return strtotime( $dateTimeAsTimeZone );
	}
	
}

?>
