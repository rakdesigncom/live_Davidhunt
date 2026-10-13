<?php
class magentoCache {
	static private $procObj;
	
	//###########################
	static public function cacheConfig($procObj) {
		self::$procObj = $procObj;
	}

 	//###########################
 	static public function clearImagesCache() {

		try {
			generalLib::mesg('Clearing images cache');
		    flush();
		    Mage::getModel('catalog/product_image')->clearCache();
		    generalLib::mesg('Images cache cleared');

		} catch (exception $e) {
				generalLib::error($e->getMessage());
		}

	}


	//###########################
	static public function clearMainCaches($mainCacheList = 'ALL') {
		try {

			if ($mainCacheList == 'ALL') {
				$cachesToClear = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
			} else {
				$cachesToClear = $mainCacheList;
			}

			$cacheTypeList = self::$procObj->objectManager->create('Magento\Framework\App\Cache\TypeListInterface');

			$cacheFrontendPool = self::$procObj->objectManager->create('Magento\Framework\App\Cache\Frontend\Pool');

		    foreach($cachesToClear as $cacheToClear) {
		    	generalLib::mesg('Clearing ' . $cacheToClear  . ' cache');
		    	$cacheTypeList->cleanType( $cacheToClear );
		        generalLib::mesg('Cleared ' . $cacheToClear  . ' cache');
		    }

		    foreach($cacheFrontendPool as $cacheFrontend) {
		    	$cacheFrontend->getBackend()->clean();
		    }

		} catch (Exception $e) {
		    generalLib::error($e->getMessage());
		}
	}

}

?>
