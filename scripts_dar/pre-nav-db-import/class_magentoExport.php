<?php
class magentoExport {
	static private $procObj;

	const OVERWRITE_FILE = true;
	const NO_OVERWRITE_FILE = false;
	
	
	//###########################
	static public function setProcObj($procObj) {
		self::$procObj = $procObj;
	}
	
	
	//###########################	
	static public function createXmlNode($dom, $parent, $nodeName, $nodeValue) {	
		// create text node
		$newNode = $dom->createElement($nodeName);
		$parent->appendChild($newNode);
	
		$text = $dom->createTextNode($nodeValue);
		$newNode->appendChild($text);
	}
	
	//###########################
	static public function exportCustomers($stores, $externalCustAttr, $dir, $customerFileName) {
		$fileObj = new stdClass();
		$fileObj->customers = array();
			
		$dom = new DOMDocument('1.0');
		$dom->formatOutput = true;
		
		$expCustomers = $dom->createElement('Customers');
		$dom->appendChild($expCustomers);
		
		foreach ($stores as $storeId => $storeDet) {
			$customers = Mage::getModel('customer/customer')
        						->getCollection()
								->addAttributeToSelect('export_cust')
								->addAttributeToFilter('export_cust', '1')
								->addAttributeToFilter('store_id', $storeId)	
								->load();
					
			foreach ($customers as $customer) {	
				$magCustId = $customer->getId();
				
				$customer = Mage::getModel('customer/customer')->load($magCustId);
				
				$expCustomer = $dom->createElement('Customer');
				$expCustomers->appendChild($expCustomer);				
				
				magentoExport::createXmlNode($dom, $expCustomer, 'ExternalCustomerID', $customer->getData($externalCustAttr));
				magentoExport::createXmlNode($dom, $expCustomer, 'MagentoCustomerID', $magCustId); 
				magentoExport::createXmlNode($dom, $expCustomer, 'EmailAddr', $customer->getEmail());
				
				
				$processFunc = 'addlCustExportFields';
				
				if (method_exists(self::$procObj, $processFunc)) {
					self::$procObj->$processFunc($customer, $dom, $expCustomer);
				}
				
				$fileObj->customers[] = $magCustId;
			}
		}
		
		if (count($fileObj->customers) > 0) {
			$customersXml = $dom->saveXML();
						
			if (magentoExport::exportFile('xml', $dir, $customerFileName, $customersXml, self::NO_OVERWRITE_FILE)) {
				$fileObj->fileName = $customerFileName;
			}

			return $fileObj;		
		}
		
		return false;		
	}
	
	
	//###########################
	static public function exportProducts($externalProdAttr, $dir, $productFileName) {
		$fileObj = new stdClass();
		$fileObj->products = array();
			
		$dom = new DOMDocument('1.0');
		$dom->formatOutput = true;
		
		$expProducts = $dom->createElement('Products');
		$dom->appendChild($expProducts);
		
		$products = Mage::getModel('catalog/product')
							->getCollection()
							->addAttributeToSelect('export_prod')
							->addAttributeToFilter('export_prod', '1')
							->load();
				
		foreach ($products as $product) {	
			$magProdId = $product->getId();
			
			$product = Mage::getModel('catalog/product')->load($magProdId);				
			$expProduct = $dom->createElement('Product');
			$expProducts->appendChild($expProduct);				
							
			magentoExport::createXmlNode($dom, $expProduct, 'ExternalProductID', $product->getData($externalProdAttr));
			magentoExport::createXmlNode($dom, $expProduct, 'MagentoProductID', $magProdId);
			magentoExport::createXmlNode($dom, $expProduct, 'Name', $product->getName());
			magentoExport::createXmlNode($dom, $expProduct, 'Sku', $product->getSku());
			

			$processFunc = 'addlProdExportFields';
				
			if (method_exists(self::$procObj, $processFunc)) {
				self::$procObj->$processFunc($product, $dom, $expProduct);
			}
				
			$fileObj->products[] = $magProdId;
		}
		
		if (count($fileObj->products) > 0) {
			$productsXml = $dom->saveXML();
						
			if (magentoExport::exportFile('xml' , $dir, $productFileName, $productsXml, self::NO_OVERWRITE_FILE)) {
				$fileObj->fileName = $productFileName;
			}

			return $fileObj;		
		}
		
		return false;		
	}
	
	
	//###########################
	static public function resetCustomerExportFlag($customerIds) {
		foreach($customerIds as $customerId) {
			$customer = Mage::getModel('customer/customer')->load($customerId);
			$customer->setExportCust('0');

			try {
				generalLib::mesg('Resetting export flag for customer id ' . $customerId);
				$customer->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				continue;
			}			
		}
	}
	
	
	//###########################
	static public function resetProductExportFlag($productIds) {
		foreach($productIds as $productId) {
			$product = Mage::getModel('catalog/product')->load($productId);
			$product->setExportProd('0');

			try {
				generalLib::mesg('Resetting export flag for product id ' . $productId);
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				continue;
			}			
		}
	}
	
	
	//###########################	
	static public function exportFile($fileType, $filePath, $fileName, $fileData, $overwriteFile = false) {	
		$fullLocalFileName = $filePath . '/' . $fileName;
		$rc = true;		
		
		if (file_exists($fullLocalFileName) && (!$overwriteFile)) {
			generalLib::mesg($fileType . ' file ' . $fileName . ' already exists. Skipping file export');
		} else {	
			if (($fp = fopen($fullLocalFileName, 'w')) != false) {	
				if (!fwrite($fp, $fileData)) {
					generalLib::error('Unable to write to the ' . $fileType . ' file ' . $fullLocalFileName);
					$rc = false;
				}
				
				if ($rc) {
					generalLib::mesg('Exported file ' . $fileName);
				}
	
				fclose($fp);
			} else {
				generalLib::error('Unable to open the ' . $fileType . ' file ' . $fullLocalFileName . ' for writing');
				$rc = false;
			}
		}
		
		return $rc;
	}
	
	
	//###########################
	static public function mergeOrders($orders, &$mergedOrders) {

		foreach($orders as $order) {

			//print_r($order->getData());

			if (!self::gotMergedOrder($order->getId(), $mergedOrders)) {
				$mergedOrders->addItem($order);
			}
		}

	}
	
	
	//###########################
	static public function gotMergedOrder($orderId, $mergedOrders) {

		if ($mergedOrders->getItemById($orderId)) {
			$gotMergedOrder = true;
		} else {
			$gotMergedOrder = false;
		}
		
		return $gotMergedOrder;
	}
}

?>
