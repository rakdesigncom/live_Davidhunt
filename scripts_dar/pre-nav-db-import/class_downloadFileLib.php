<?php
class downloadFileLib {
	static private $dloadConfig;	
	
	static public function setDloadConfig($dloadConfig) {
		self::$dloadConfig = $dloadConfig;
	}
	
	
	static public function getDloadConfigVar($var) {
		return self::$dloadConfig->$var;
	}
	
	
	static public function processDownloadedFiles($procObj, $downloadDets) {		
		foreach($downloadDets as $downloadDet) {
			$downloadedFileList = array();
			$processedInboxSubDir = self::getLocalProcessedSubDir($downloadDet->type, 'Inbox');
			
			$matchedFileNames = explode(',', $downloadDet->matchingFilenames);
			
			//print_r($downloadDet);
			
			if ($handle = opendir($downloadDet->localInBoxDir)) {
				while (($dirEntry = readdir($handle)) !== false) {
					if ($dirEntry != '.' && $dirEntry != '..') {
						if ( ( pathinfo($dirEntry, PATHINFO_EXTENSION) == $downloadDet->suffix ) || ( pathinfo($dirEntry, PATHINFO_EXTENSION) == strtoupper($downloadDet->suffix) ) ) {
							if ($matchedFileNames[0] != 'ALL') {
								if (!self::filterMatchedFileNames($dirEntry, $matchedFileNames)) {		
									$downloadedFileList[] = $dirEntry;
								}
							} else {
								$downloadedFileList[] = $dirEntry;
							}
						}
					}
				}
				
				closedir($handle);		
			} else {
				generalLib::error('Cannot open the input directory ' . $downloadDet->localInBoxDir);
			}
			
			/*
			echo "downloaded list\n";
			print_r($downloadedFileList);
			die();
			*/
							
			if (count($downloadedFileList) > 0) {
				asort($downloadedFileList);
				$ucfirstType = ucfirst($downloadDet->type);
				
				$preProcessFunc = 'preProcessDownloaded' . $ucfirstType . 'Files';
				$processFunc = 'processDownloaded' . $ucfirstType . 'File';
				
				if (method_exists($procObj, $preProcessFunc)) {
					$procObj->$preProcessFunc($downloadedFileList);
				}
			}
			
			/*
			echo "after pre process downloaded list\n";
			print_r($downloadedFileList);
			die();
			*/
			
			if (count($downloadedFileList) > 0) {	
				foreach($downloadedFileList as $downloadedFileKey => $downloadedFileName) {	
					$fileProcessed = false;		
					$fullDownloadedFilename = $downloadDet->localInBoxDir . '/' . $downloadedFileName;
				
					if (($downloadedFP = fopen($fullDownloadedFilename, 'r')) != false) {
						generalLib::mesg('====');
												
						if (method_exists($procObj, $processFunc)) {
							$fileProcessed = $procObj->$processFunc($fullDownloadedFilename, $downloadedFP);
						} else {
							generalLib::error('Processing file function ' . $processFunc . ' not found');
						}
						
						fclose($downloadedFP);
												
						if (self::$dloadConfig->setDownloadedLocalFilesToProcessed) {
							if ($fileProcessed)	{						
								if (rename($fullDownloadedFilename, $processedInboxSubDir . '/' . $downloadedFileName)) {
									generalLib::mesg('Moved ' . $downloadedFileName . ' to ' . $processedInboxSubDir);
								} else {
									generalLib::error('Cannot move ' . $downloadedFileName . ' to ' . $processedInboxSubDir);
								}
							} else {
								generalLib::mesg('Cannot process ' . $downloadedFileName);
							}
						} else {
							generalLib::warn('Set downloaded local files to processed is disabled');
						}
					} else {
						generalLib::error('Cannot open file ' . $downloadedFilename);
					}
				}
				
				$processFunc = 'postProcessDownloaded' . $ucfirstType . 'Files';
				
				if (method_exists($procObj, $processFunc)) {
					$procObj->$processFunc();
				}			
			} else {
				generalLib::mesg('No ' . $downloadDet->suffix . ' batches to update Magento');
			}
		}
	}
	
	
	//ALL PROCESSED LOCAL FILES ARE PLACED IN A 'Processed' SUB-DIRECTORY, WHICH IS ALSO SPLIT BY A FURTHER YEAR/MONTH SUB-DIRECTORY (UNLESS DISABLED)
	static public function setLocalProcessedDirs($fileTypes, $procTypes, $createYMSubDir = true) {
		
		foreach($fileTypes as $fileType) {
			foreach($procTypes as $procType) {
				$procVar = 'local' . ucfirst($fileType) . $procType . 'ProcDir';
			
				if (isset(self::$dloadConfig->$procVar)) {
					$processedSubDir = $fileType . $procType . 'ProcessedSubDir';
					self::$dloadConfig->$processedSubDir = self::$dloadConfig->$procVar;
					
					if ($createYMSubDir) {
						self::$dloadConfig->$processedSubDir .= '/' . date('Y_m');
					}
										
					if (!is_dir(self::$dloadConfig->$processedSubDir)) {
						if (!mkdir(self::$dloadConfig->$processedSubDir)) {
							generalLib::term(1, 'Cannot create directory ' . self::$dloadConfig->$processedSubDir);
						}
					}
				} else {
					generalLib::term(1, 'downloadFileLib: Configuration value ' . $procVar . ' is not defined');
				}
			}
		}
	}
	
	
	static public function getLocalProcessedSubDir($fileType, $procType) {
		$processedSubDir = $fileType . $procType . 'ProcessedSubDir';
		return self::$dloadConfig->$processedSubDir;
	}
	
	
	//DESIGNATE A FILE TO BE FILTERED, WHERE THE FILENAME DOES NOT CONTAIN ANY OF THE matchingFilenames
	static public function filterMatchedFileNames($dirFile, $matchedFileNames) {
		$filterFile = false;
		$foundMatch = false;
		
		foreach($matchedFileNames as $matchFilename) {
			if (strpos($dirFile, $matchFilename) !== false) {
				$foundMatch = true;
				break;
			}
		}
		
		if (!$foundMatch) {
			$filterFile = true;
		}
		
		return $filterFile;
	}
}
?>
