<?php
class magentoImgImport {
	static private $imgPath, $imgMagRelPath;
	static private $watchedFileFormat = true;
	static private $imgFP;
	static private $procObj;


	//###########################
	static public function setImagePath($imgPath) {
		self::$imgPath = $imgPath;

		//echo 'imgPath = ' . self::$imgPath . "\n";
	}

	//###########################
	static public function setImageConfig($imgPath, $imgMagRelPath, $watchedFileFormat) {
		self::$imgPath = $imgPath;
		self::$imgMagRelPath = $imgMagRelPath;
		self::$watchedFileFormat = $watchedFileFormat;

		//echo 'imgPath = ' . self::$imgPath . "\n";
		//die();
	}
	
	
	//###########################
	static public function setProcObj($procObj) {
		self::$procObj = $procObj;
	}


	//###########################
	static public function downloadImageFromUrl($imageUrl, $newImageName = null) {
		$rc = true;
		
		if (!isset(self::$imgPath)) {
			generalLib::error('Please set the image path before calling downloadImageFromUrl()');
			return false;
		}
		
		$tmpFileName = self::$imgPath . '/';

		if ( is_null($newImageName) ) {
			$tmpFileName .= pathinfo($imageUrl, PATHINFO_BASENAME);
		} else {
			$tmpFileName .= $newImageName;
		}

		//echo 'download tmpFileName = ' . $tmpFileName . "\n";

		if ( (self::$imgFP = fopen($tmpFileName, 'wb')) == FALSE) {
			generalLib::warn('Cannot open temporary image file \'' . $tmpFileName . '\' for writing');
		}

	    $ch = curl_init($imageUrl);
	    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // enable if you want
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    //curl_setopt($ch, CURLOPT_TIMEOUT, 1000);      // some large value to allow curl to run for a long time
	    //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
	    //curl_setopt($ch, CURLOPT_BUFFERSIZE, 8096);
	    //curl_setopt($ch, CURLOPT_HEADER, 0);

	    curl_setopt($ch, CURLOPT_FILE, self::$imgFP);
	    curl_setopt($ch, CURLOPT_HEADER, 0);

	    //curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'magentoImgImport::imgDownloadCallBack');

	    	//array(self, 'imgDownloadCallBack') );
		//curl_setopt($ch, CURLOPT_VERBOSE, true);	// Enable this line to see debug prints

	    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	//curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

    	curl_exec($ch);

    	if ( ($error = curl_error($ch)) != '') {
    		generalLib::warn('download Image error: ' . $error . ' - ' . $imageUrl);
    		$rc = false;
		} else {
			generalLib::mesg('Downloaded Image URL \'' . $imageUrl . '\' to ' . self::$imgPath);
		}

    	curl_close($ch);

    	//fwrite($imgFP, $raw);
    	fclose( self::$imgFP );

	    // curl_setopt($ch, CURLOPT_VERBOSE, true);	// Enable this line to see debug prints

	    return $rc;
	}


	/** callback function for curl */
	/*
	static public function imgDownloadCallBack($ch, $bytes) {
		echo 'bytes = ' . $bytes . "\n";

	    $len = fwrite(self::$imgFP, $bytes);
	    // if you want, you can use any progress printing here
	    return $len;
	}
	*/

	
	//###########################
	static public function processProductImages($product, $imgList) {
		
		if (!isset(self::$imgPath)) {
			generalLib::error('Please set the image path before calling processImages()');
			return false;
		}
		
		//CREATE A FLAG FOR EACH IMAGE TO RECORD WHETHER IT ALREADY EXISTS IN MAGENTO FOR THIS PRODUCT
		foreach($imgList as $anImg) {
			$anImg->imgInMagento = false;
			$anImg->imgMagentoFullPath = '';
		}
		
		//echo 'num of magento images = ' . count($product->getMediaGalleryImages()) . "\n";
		
		if (count($product->getMediaGalleryImages()) > 0) {
			//Delete any images in Magento which are not in the product XML
												
			foreach ($product->getMediaGalleryImages() as $magImg) {
				$magImgFullPath = $magImg->getPath();

				//echo $magImgFullPath."\n";

				$magImgName =  basename($magImgFullPath);

				generalLib::mesg('Magento image file name = ' . $magImgName);
				$imgInXml = false;
				
				foreach($imgList as $anImg) {

					$imageName = $anImg->imgName;

					//generalLib::mesg('checking xml image name = ' . $anImg->imgName);
					
					/*$checkString = pathinfo($anImg->imgName, PATHINFO_FILENAME);

					
					if (strtolower(substr($checkString, -1)) == 'd'){ 
						echo "Check if image exists to delete \n\r";
						$imageName = str_replace('d.', '.', $anImg->imgName)."\n\r"; 

					}*/

					if (self::imageExists($imageName, $magImgName)) {

						//echo "processProductImages: Image exists \n";

						$anImg->imgInMagento = true;
						$anImg->imgMagentoFullPath = $magImgFullPath;
						break;
					}
				}
				
				if ($anImg->imgInMagento) {
					break;
				}
			}
		}
							
		//Add/update images to Magento which are in the product XML, but not currently in Magento
		//print_r($imgList);
		//die();
		//return false;
		
		foreach($imgList as $anImg) {
			//echo 'imgInMagento = ' . $anImg->imgInMagento . "\n";
			//continue;

			$checkString = pathinfo($anImg->imgName, PATHINFO_FILENAME);

			if (!$anImg->imgInMagento) {

				if (self::$watchedFileFormat && strtolower(substr($checkString, -1)) == 'd'){  

					//REMOVE THE DELETION INSTRUCTIONS FROM THE IMAGE NAME
					$anImg->imgName = str_replace('d.', '.', $anImg->imgName);

					$rc = self::deleteProductImage($product, $anImg);
				
				} else {

					//echo "Add ".$anImg->imgName."\n\r";

					$rc = self::createProductImage($product, $anImg);

				}

			} else {

				if ($anImg->imgAllowUpdate) {
					//echo "got to update for " . $anImg->imgName . "\n";
					$rc = self::updateProductImage($product, $anImg);
				} else {
					generalLib::warn('Product image update disabled');
					$rc = false;
				}

			}

		}
		
		//echo 'rc = ' . $rc . "\n";
		return $rc;
	}
	
	
	//###########################
	static private function imageExists($inpImgName, $magImgName) {

		//echo " Checking if image exists \n\r";

		$inpImgNameTmp = pathinfo($inpImgName, PATHINFO_FILENAME);
		$magImgNameTmp = pathinfo($magImgName, PATHINFO_FILENAME);

		//echo "imageExists: Import Image Name: ".$inpImgNameTmp."\n";
		//echo "imageExists: Magento Image Name: ".$magImgNameTmp."\n";
		//die();


		$path_parts = pathinfo($inpImgName);
		$inpImgName = $path_parts['filename'];

		if (strpos($magImgNameTmp, $inpImgNameTmp)===0 || $magImgNameTmp == $inpImgNameTmp){
    		//echo "imageExists: Images match \n";

    		$checkImage = str_replace($inpImgNameTmp, '', $magImgNameTmp);// check to make sure it doesn't key on wrong image. eg. a_sg1 on a_sg11

    		//echo "Replaced String".$checkImage."\n";
    		//echo "T String length ".strlen($checkImage)."\n";	
    		//echo "First Character ".$checkImage[0]."\n";

    		if (strlen($checkImage) == 0 || $checkImage[0] == '_'){

    			//echo "imageExists: Image exists \n";

    			return true;

    		}

    	}
		
		//MAGENTO REPLACES ALL THE FOLLOWING CHARACTERS IN IMAGE NAMES WITH UNDERSCORES WHEN CREATED - SO NEED TO CHANGE BEFORE COMPARISON
		$inpImgName = str_replace( array('\'', '%', '(', ')', ' ', '@', ',', '&', '+', '=', '~'), '_', $inpImgName);

		//echo "inpImgName: ".$inpImgName."\n\r";

		//generalLib::mesg('inpImgName = ' . $inpImgName);
		//generalLib::mesg('magImgName = ' . $magImgName);
		//die();
		
		if ( (strpos($magImgName, $inpImgName) !== false) || (strpos($magImgName, strtoupper($inpImgName)) !== false) || 
			(strpos($magImgName, strtolower($inpImgName)) !== false) ) {

			$checkImage = str_replace($inpImgName, '', $magImgName);// check to make sure it doesn't key on wrong image. eg. a_sg1 on a_sg11

			//echo 'checkImage = ' . $checkImage . "\n";
    			//echo "String length ".strlen($checkImage)."\n\r";
    			//echo "First Character ".$checkImage[0]."\n\r";
			//die();

    			if (strlen($checkImage) == 0 || $checkImage[0] == '_' || $checkImage[0] == '.'){
    				//echo "imageExists: Image exists \n";
    				return true;
    			} else {
    				//echo "imageExists: Image does not exist\n";
    				return false;
    			}

		} else {
			return false;
		}
	}
	
	
	//###########################
	static private function createProductImage($product, $anImg) {

		if ($anImg->imgNameCaseSensitive) {
			$rc = self::addImageToProduct($product,  $anImg->imgName, $anImg);
		} else {
			//ATTEMPT TO FIND THE IMAGE NAME IN THE CASE GIVEN, LOWER THEN UPPER CASE
			if (! ($rc = self::addImageToProduct($product,  $anImg->imgName, $anImg)) ) {
				if (! ($rc = self::addImageToProduct($product, strtolower($anImg->imgName), $anImg)) ) {
					$rc = self::addImageToProduct($product, strtoupper($anImg->imgName), $anImg);
				}
			}
		}
		
		return $rc;
	}
	
	
	//###########################
	static private function addImageToProduct($product, $imgName, $anImg) {

		$rc = false;
		$imgFullPath = self::$imgPath . '/' . $imgName;

		//$imgFullPath = '/import' . '/' . $imgName;
		
		//echo 'imgFullPath = ' . $imgFullPath . "\n";
		//print_r($anImg);


		if (file_exists($imgFullPath) && $imgFullPath != NULL) {


			$imgMagRelPath = self::$imgMagRelPath . '/' . $imgName;

			//echo 'imgMagRelPath = ' . $imgMagRelPath . "\n";

			try {

				//NOTE: 3RD ARGUMENT INDICATES WHETHER TO MOVE THE IMAGE TO IT'S DESTINATION OR COPY AND CHMOD TO 0777
				$product->addImageToMediaGallery($imgMagRelPath, $anImg->imgType, false, $anImg->excludeImg);

				generalLib::mesg('Saved image \'' . $imgFullPath . '\'');
				generalLib::mesg('');
				$rc = true;
				
			} catch (Exception $e) {
				generalLib::error( $e->getMessage() );
				//die();	
			}
		} else {
			generalLib::mesg('Cannot save image as the \'' . $imgFullPath . '\' file does not exist');
		}

		if ($rc && isset($anImg->position)) {
		
			self::processImgPosition($product, $anImg);

		}

		//die();
				
		return $rc;
	}
	
	
	//###########################
	static public function processImgPosition($product, $anImg) {
		$galleryUpd = false;
		
		//print_r($anImg);

		$gallery = $product->getData('media_gallery');

		//echo "gallery\n";
		//print_r($gallery);

		if (count($gallery['images']) > 0) {
			$idx = 0;

			foreach ($gallery['images'] as $idx => $magImg) {
				//echo "magImg:\n";
				//print_r($magImg);

				$magImgName = basename($magImg['file']);

				//echo 'magImgName = ' . $magImgName . "\n";
				//echo 'idx = ' . $idx . "\n";

				if (self::imageExists($anImg->imgName, $magImgName)) {
					
					if ($magImg['position'] != $anImg->position) {
						
						$gallery['images'][$idx]['position'] = $anImg->position;
						generalLib::mesg('Updated image position for \'' . $anImg->imgName . '\'');
						$galleryUpd = true;
						
					}
					
				}
				
				$idx++;
			}
			
			if ($galleryUpd) {
				$product->setData('media_gallery', $gallery);
				//echo "gallery upd:\n";
				//print_r($gallery);
			}
		}
		
		//die();
		return $galleryUpd;
	}


	//###########################
	static private function updateProductImage($product, $anImg) {

		$imgFullPath = self::$imgPath . '/' . $anImg->imgName;
		$rc = true;
		
		//echo 'imgFullPath = ' . $imgFullPath . "\n";
		//echo 'imgMagentoFullPath = ' . $anImg->imgMagentoFullPath . "\n";

		if ( !generalLib::filesIdentical($imgFullPath, $anImg->imgMagentoFullPath) ) {
			generalLib::mesg('Image has been replaced. Deleting original image \'' . $anImg->imgMagentoFullPath . '\'');
			
			if (self::deleteProductImage($product, $anImg)) {

				$rc = self::createProductImage($product, $anImg);
			}
	
		} else {
			$rc = -2; 	//NO UPDATE TO APPLY
		}
		
		return $rc;
	}
	
	
	//###########################
	static public function processProductLabels($product, $imgList) {

		$rc = false;

		foreach($imgList as $anImg) {

			if (isset($anImg->label)) {
				$rc = self::processImgLabel($product, $anImg);
			}

		}

		return $rc;
	}


	//###########################
	static public function processImgLabel($product, $anImg) {
		$galleryUpd = false;
		
		//print_r($anImg);

		$gallery = $product->getData('media_gallery');
		//print_r($gallery);

		if (count($gallery['images']) > 0) {
			$idx = 0;

			foreach ($gallery['images'] as $magImg) {
				//print_r($magImg);

				$magImgName = basename($magImg['file']);

				if (self::imageExists($anImg->imgName, $magImgName)) {
					//echo "Image found \n\r";
					//echo "Mag Label ".$magImg['label']."\n\r";
					//echo "New Label ".$anImg->label."\n\r";

					if ($magImg['label'] != $anImg->label) {
						$gallery['images'][$idx]['label'] = $anImg->label;
						generalLib::mesg('Updated image label for \'' . $anImg->imgName . '\' to \'' . $anImg->label . '\'');
						$galleryUpd = true;
					}
				}
				
				$idx++;
			}
			
			if ($galleryUpd) {
				$product->setData('media_gallery', $gallery);
				//print_r($gallery);
			} else {
				generalLib::warn('Cannot update image label \'' . $anImg->label . '\' for \'' . $anImg->imgName . '\' - image not found');
			}
		}
		
		return $galleryUpd;
	}
	

	//###########################
	static public function deleteProductImage($product, $anImg) {
		//echo "in deleteProductImage\n";
		//echo 'imgName = ' . $anImg->imgName . "\n";
		$foundImg = false;
		$deletedImg = false;
		$processFunc = 'otherImgDeletionProcess';

		if (count($product->getMediaGalleryImages()) > 0) {
			foreach ($product->getMediaGalleryImages() as $magImg) {
				$magImgName =  basename($magImg->getPath());
				
				//echo 'magImgName = ' . $magImgName . "\n";
				//echo 'imgName = ' . $anImg->imgName . "\n";
				
				if (self::imageExists($anImg->imgName, $magImgName)) {
					$foundImg = true;

					$imageProcessor = self::$procObj->objectManager->create('\Magento\Catalog\Model\Product\Gallery\Processor');
					
					try {
						//echo $magImg->getFile();

						$imageProcessor->removeImage($product, $magImg->getFile());
						generalLib::mesg('Deleted image \'' . $anImg->imgName . '\'');
						generalLib::mesg('');
						
						if (method_exists(self::$procObj, $processFunc)) {
							self::$procObj->$processFunc($anImg->imgName);
						}
												
						$deletedImg = true;
					} 
					
					catch (Exception $e) {
						generalLib::error( $e->getMessage() );
					}				
				}
			}
			
			if (!$foundImg) {
				generalLib::error('Cannot find image \'' . $anImg->imgName . '\' to delete');
				$deletedImg = true;	//AS THE IMAGE CANNOT BE FOUND - IT HAS BEEN DELETED ALREADY
			}
		}
		
		return $deletedImg;
	}
}
?>
