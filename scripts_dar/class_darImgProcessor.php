<?php

class darImgProcessor {

	public $imgProdAttrToFilter;
	private $imgProcConfig;
	public $objectManager;
	public $imgSizeLimit = -1;

	
	//###########################	
	function __construct($objectManager) {
		global $argv;

		$progConfigObj = new progConfig();

		$this->objectManager = $objectManager;

		$this->imgProcConfig = $progConfigObj->progConfig;

		generalLib::setLineBrk("\n");

		if (!is_dir($this->imgProcConfig->localLogDir)) {
			if (!mkdir($this->imgProcConfig->localLogDir)) {
				generalLib::term(1, 'Cannot create directory ' . $this->imgProcConfig->localLogDir);
			}
		}

		magentoGeneral::genConfig( $this );
	
		$logFilename = $this->imgProcConfig->localLogDir . '/dar_IMAGES-' . date('Ymd_His', magentoGeneral::getLocalTimeStamp());

		generalLib::setGeneralLogFile($logFilename . '-general.log', false);
		generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
		generalLib::setWarnLogFile($logFilename  . '-warn.log', true, false);

		$this->imgProcConfig->matchingFilenames = isset($argv[2]) ? $argv[2] : 'ALL';

		$this->objectManager = $objectManager;

		$this->imgProdAttrToFilter = $this->imgProcConfig->imgProdAttrToFilter;

		magentoImgImport::setProcObj($this);

		$dloadConfig = new stdClass();
		
		## Main Product Images #
		$dloadConfig->localMainImgInboxDir = $this->imgProcConfig->imageBaseWatchedDir . '/' . $this->imgProcConfig->mainImagesDir;
		
		if (empty($dloadConfig->localMainImgInboxDir)) {
			generalLib::term(1,'Local Server Main Image Inbox Dir must be set in the Magento configuration');
		}
		
		$dloadConfig->localMainImgInboxProcDir = $this->imgProcConfig->imageBaseWatchedDir . '/' . $this->imgProcConfig->mainImagesDir . '/processed';
		
		if (empty($dloadConfig->localMainImgInboxProcDir)) {
			generalLib::term(1,'Local Server Main Image Inbox Processed Dir must be set in the Magento configuration');
		}
		
		
		## Main Product Labels #
		$dloadConfig->localMainTxtInboxDir = $this->imgProcConfig->imageBaseWatchedDir . '/' . $this->imgProcConfig->mainImagesDir;
		
		if (empty($dloadConfig->localMainTxtInboxDir)) {
			generalLib::term(1,'Local Server Main Image Labels Inbox Dir must be set in the Magento configuration');
		}
		
		$dloadConfig->localMainTxtInboxProcDir = $this->imgProcConfig->imageBaseWatchedDir . '/' . $this->imgProcConfig->mainImagesDir . '/processed';
		
		if (empty($dloadConfig->localMainTxtInboxProcDir)) {
			generalLib::term(1,'Local Server Main Image Labels Inbox Processed Dir must be set in the Magento configuration');
		}
		
		
		$dloadConfig->setDownloadedLocalFilesToProcessed = true;
		downloadFileLib::setDloadConfig($dloadConfig);
		downloadFileLib::setLocalProcessedDirs( array('MainImg', 'MainTxt'), array('Inbox'), false);

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		$this->setImgSizelimit( $this->imgProcConfig->imgSizelimit );
	}


	//###########################	
	public function setImgSizelimit( $sizeLimit ) {
		$this->imgSizeLimit = $sizeLimit;
	}


	//###########################	
	public function processDownloadedImgFiles() {

		$feedLkFile = $this->imgProcConfig->localLogDir . '/images.lck';

		if (!generalLib::createProcLckFile($feedLkFile)) {
			generalLib::warn('Cannot process images feed - image import already in progress');
			return;
		}
				
		/*Main Images*/
		$downloadDets = array();

		$dloadObj = new stdClass();
		$dloadObj->type = 'MainImg';
		$dloadObj->suffix = 'jpg';
		$dloadObj->matchingFilenames = $this->imgProcConfig->matchingFilenames;
		$dloadObj->localInBoxDir = downloadFileLib::getDloadConfigVar('localMainImgInboxDir');	
		$downloadDets[] = $dloadObj;

		downloadFileLib::processDownloadedFiles($this, $downloadDets);

		/*Main Labels*/
		$downloadDets = array();

		$dloadObj = new stdClass();
		$dloadObj->type = 'MainTxt';
		$dloadObj->suffix = 'txt';
		$dloadObj->matchingFilenames = $this->imgProcConfig->matchingFilenames;
		$dloadObj->localInBoxDir = downloadFileLib::getDloadConfigVar('localMainTxtInboxDir');	
		$downloadDets[] = $dloadObj;

		downloadFileLib::processDownloadedFiles($this, $downloadDets);

		generalLib::deleteProcLckFile($feedLkFile);
	}
	
	
	//###########################	
	public function preProcessDownloadedMainImgFiles(&$fullDownloadedMainImgFilename) {

		arsort($fullDownloadedMainImgFilename);

	}
	

	//###########################	
	public function processDownloadedMainImgFile($fullDownloadedMainImgFilename, $imgFileFP) {
		return $this->processDownloadedFile('img', $fullDownloadedMainImgFilename, $imgFileFP);
	}


	//###########################	
	public function processDownloadedMainTxtFile($fullDownloadedMainTxtFilename, $imgFileFP) {
		return $this->processDownloadedFile('txt', $fullDownloadedMainTxtFilename, $imgFileFP);
	}
	

	//###########################	
	public function processDownloadedFile($fileType, $fullDownloadedFilename, $imgFileFP) {

		$fileProcessed = false;

		generalLib::mesg("\n====\n");
		//echo 'fullDownloadedFilename = ' . $fullDownloadedFilename . "\n";

		$imgFileNameParts = pathinfo($fullDownloadedFilename);
		$imgFileName = $imgFileNameParts['filename'];

		if ($this->imgSizeLimit != -1) {

			if ( filesize( $fullDownloadedFilename ) > $this->imgSizeLimit ) {
				generalLib::warn('image file \'' . $imgFileNameParts['basename'] . '\' over \'' . $this->imgSizeLimit . '\' bytes image limit - image rejected');
				return $fileProcessed;
			}

		}

		if (!preg_match('/_[0-9]+d?$/', $imgFileName) && !preg_match('/_LS[0-9]+d?$/', $imgFileName)&& !preg_match('/_M$/', $imgFileName)) {
			generalLib::warn('Image file name \'' . $imgFileNameParts['basename'] . '\' is not of the correct format');
			return $fileProcessed;
		}
                /*if (!preg_match('/_[0-9]+d?$/', $imgFileName)) {
			generalLib::warn('Image file name \'' . $imgFileNameParts['basename'] . '\' is not of the correct format');
			return $fileProcessed;
		}*/

		//echo "imgFileName 1: " . $imgFileName." \n";

		//IF THERE ARE ANY DASHES IN THE FILENAME, REPLACE THEM WITH '/' FOR THE SKU SEARCH
		/*
		if (strpos($imgFileName, '-') !== false) {
			$imgFileName = str_replace('-', '/', $imgFileName);
		}
		*/

		//echo "imgFileName 2: ".$imgFileName." \n";
		
		//return false;

		$sku = $this->getImgId($imgFileName);

		//$product = Mage::getModel('catalog/product')->loadByAttribute($this->imgProdAttrToFilter, $sku);

		//echo 'this->imgProdAttrToFilter = ' . $this->imgProdAttrToFilter . "\n";

		if ($this->imgProdAttrToFilter == 'sku') {
			try {
				$product = $this->objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute($this->imgProdAttrToFilter, $sku);

			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
			}

		} else {
			generalLib::error('Unsupported imgProdAttrToFilter');
			generalLib::term('1', 'Unsupported imgProdAttrToFilter \'' . $this->imgProdAttrToFilter . '\'');
		}

		//echo 'sku = ' . $sku . "\n";

		//print_r($product->getData());
		//die();
		/*
		if ($product) {
			$productId = $product->getEntityId();	
			unset($product);
		} else {
			generalLib::warn('Cannot find product for sku \'' . $sku . '\'');
			return $fileProcessed;
		}

		$product = Mage::getModel('catalog/product')->load($productId);
		*/

		if (!$product) {
			generalLib::warn('Cannot find product for sku \'' . $sku . '\'');
			return $fileProcessed;
		}

		if ($product) {

			$productId = $product->getId();

			$product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);

			//print_r($product->getData());
			//die();

			generalLib::mesg('Processing \'' . $sku . '\'');

			$imgObjs = array();

			$imgObj = new stdClass();
			$imgObj->imgName = $imgFileNameParts['basename'];	
			$imgObj->imgNameCaseSensitive = true;
			$imgObj->excludeImg = false;

			if ($fileType == 'img') {

				$imagePositionArr = explode('_', $imgFileNameParts['filename']);
                                $position=$imagePositionArr[1];
                                $position= str_replace('LS', '2', $position);
                                $position= str_replace('M', '50', $position);
				$imgObj->position = $position;
			
				if ($imgObj->position == 0) {
					$imgObj->imgType = array('thumbnail', 'small_image', 'image');
				} else {
					$imgObj->imgType = array();
				}

				$imgObj->imgAllowUpdate = true;
			} else {

				$label = file_get_contents($fullDownloadedFilename);

				if ( isset($label) ) {
					$imgObj->label =  $label;
				} else {
					generalLib::error('Cannot read image label for \'' . $fullDownloadedFilename . '\'');
					return $fileProcessed;
				}

				$imgObj->position = '';
				$imgObj->imgType = '';

			}


			$imgObjs[] = $imgObj;
 			print_r($imgObjs);
 			//die();
			//return false;
			
			//magentoImgImport::setImagePath($imgFileNameParts['dirname'], );

			$mediaFolder = $this->objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');// Im Magento 2
			//$mediaFolder = '/home/mtwodarl/live.darlighting.co.uk/html/pub/media';
			$mediaFolder .= '/import/images/in';
			magentoImgImport::setImageConfig($imgFileNameParts['dirname'], $mediaFolder, true);

			if ($fileType == 'img') {
				$fileProcessed = magentoImgImport::processProductImages($product, $imgObjs);
			} else {
				$fileProcessed = magentoImgImport::processProductLabels($product, $imgObjs);
			}
			
			if ($fileProcessed) {
				try {
					generalLib::mesg('Saving product id \'' . $productId . '\' sku \'' . $sku . '\'');
					$product->save();
					$fileProcessed = true;
				}
			
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Product id \'' . $productId . '\' sku \'' . $sku . '\' not saved');
					$fileProcessed = false;
				}
			}
		} else {
			generalLib::error('Product id \'' . $productId . '\' sku \'' . $sku . '\' not found');
		}
		
		//echo 'fileProcessed = ' . $fileProcessed . "\n";
		return $fileProcessed;
	}

	
	//###########################
	private function getImgId($imgFileName) {
		$imgIdEndPos = strpos($imgFileName, '_');	

		if ($imgIdEndPos !== false) {
			$imgId = substr($imgFileName, 0, $imgIdEndPos);
		} else {
			$imgId = $imgFileName;
		}
		
		//echo 'imgId = ' . $imgId . "\n";
		return $imgId;	
	}
}

?>
