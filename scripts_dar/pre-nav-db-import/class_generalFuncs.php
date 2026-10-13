<?php
class generalLib {
	static private $lineBrk = PHP_EOL;
	static private $logConfig = false;
	static private $emailConfig = false;
	static private $remotePhpFile  = array();
	static private $lineDesc = array('general' => '', 'warn' => 'WARNING: ', 'error' => 'ERROR: ', 'tech' => 'TECHNICAL: ');
	static private $mainBodySizeLimit = -1;	//NO DEFAULT - CAN BE SET VIA setMailBodySizelimit();
	static private $recordLogEntries = false;
	static public $generalLogLines = array();
	static public $warnLogLines = array();
	static public $errorLogLines = array();
	static public $techLogLines = array();
	static public $fileIdentReadLen = 4096;

	//###########################	
	static public function setMailBodySizelimit($sizeLimit) {
		self::$mainBodySizeLimit = $sizeLimit;
	}
	
	
	//###########################	
	static public function setLineBrk($lineBrk=PHP_EOL) {
		self::$lineBrk = $lineBrk;
	}
	
	//###########################	
	static public function setRecordLogEntries($recordEntries) {
		self::$recordLogEntries = $recordEntries;
	}
	
	//###########################
	static public function setGeneralLogFile($logFile, $delete = false) {		
		self::setLogFile('general', $logFile, false, $delete);
	}
	
	//###########################
	static public function setWarnLogFile($logFile, $exclusive, $delete = false) {
		self::setLogFile('warn', $logFile, $exclusive, $delete);
	}
	
		
	//###########################
	static public function setErrorLogFile($logFile, $exclusive, $delete = false) {
		self::setLogFile('error', $logFile, $exclusive, $delete);
	}
	
	
	//###########################
	static public function setTechLogFile($logFile, $exclusive, $delete = false) {
		self::setLogFile('tech', $logFile, $exclusive, $delete);
	}
				
				
	//###########################	
	static private function setLogFile($logType, $logFile, $exclusive, $delete) {
		if (!self::$logConfig) {
			self::$logConfig = new stdClass();
			self::$logConfig->gotGeneral = false;
			self::$logConfig->gotError = false;
			self::$logConfig->gotWarn = false;
			self::$logConfig->gotTech = false;
			self::$logConfig->logTypes = array();
		}
		
		$logTypeFile = $logType . 'LogFile';
		$logTypeFP = $logType . 'LogFP';
		$logTypeExclusive = $logType . 'LogExclusive';
		$logTypeDelete = $logType . 'LogDelete';
				
		self::$logConfig->$logTypeFile = $logFile;
		
		//IF THE LOG FILE ALREADY EXISTS - POINT THIS LOG TO THE SAME FILE FP AND OVERRIDE EXCLUSIVE FLAG WITH WHATEVER WAS SET PREVIOUSLY FOR THIS LOG
		$gotLogFileAlready = false;
		
		foreach(self::$logConfig->logTypes as $savedLogType) {
			
			if (self::$logConfig->{$savedLogType . 'LogFile'} == $logFile) {
				self::$logConfig->$logTypeFP = self::$logConfig->{$savedLogType . 'LogFP'};
				
				if (self::$logConfig->{$savedLogType . 'LogExclusive'} != $exclusive) {
					generalLib::mesg('Overriding exclusive setting for ' . $logFile);
				}
				
				self::$logConfig->$logTypeExclusive = self::$logConfig->{$savedLogType . 'LogExclusive'};
				$gotLogFileAlready = true;
			}
		}
		
		if (!$gotLogFileAlready) {
			if ((self::$logConfig->$logTypeFP = fopen(self::$logConfig->$logTypeFile, 'w')) == false) {
				self::term(1, 'Unable to open log file ' . self::$logConfig->$logTypeFile);
			}
			
			self::$logConfig->$logTypeExclusive = $exclusive;
		}
		
		self::$logConfig->$logTypeDelete = $delete;
		
		self::$logConfig->gotOpenLogFile = true;
		
		self::$logConfig->logTypes[] = $logType;	
	}
	
	
	//###########################	
	static public function setGeneralLogEmailDetails($subject, $from, $to, $attachLog = false) {
		self::setLogEmailDets('general', $subject, $from, $to, $attachLog);
	}
	
	//###########################	
	static public function setErrorLogEmailDetails($subject, $from, $to, $attachLog = false) {
		self::setLogEmailDets('error', $subject, $from, $to, $attachLog);
	}
	
	
	//###########################	
	static public function setWarnLogEmailDetails($subject, $from, $to, $attachLog = false) {
		self::setLogEmailDets('warn', $subject, $from, $to, $attachLog);
	}
	
	
	//###########################	
	static public function setTechLogEmailDetails($subject, $from, $to, $attachLog = false) {
		self::setLogEmailDets('tech', $subject, $from, $to, $attachLog);
	}
	
	
	//###########################
	static private function setLogEmailDets($emailType, $subject, $from, $to, $attachLog = false) {
		if (!self::$emailConfig) {
			self::$emailConfig = new stdClass();
		}
				
		self::$emailConfig->{$emailType . 'EmailSubject'} = $subject;
		self::$emailConfig->{$emailType . 'EmailFrom'} = $from;
		self::$emailConfig->{$emailType . 'EmailTo'} = $to;
		self::$emailConfig->{$emailType . 'EmailAttachLog'} = $attachLog;
		self::$emailConfig->{$emailType . 'EmailDetails'} = true;
	}
	
	
	//###########################	
	static public function mesg($statusMesg) {
		self::outputLine('general', $statusMesg);
	}
	
 	
	//###########################	
	static public function warn($warnMesg) {
		self::outputLine('warn', $warnMesg);
	}
	
	
	//###########################	
	static public function error($errorMesg) {
		self::outputLine('error', $errorMesg);
	}
	
	
	//###########################	
	static public function tech($techMesg) {
		self::outputLine('tech', $techMesg);
	}


	//###########################	
	static public function techTimed($statusMesg) {

		$statusMesg .= ' - ' . date('G:i:s');
		
		self::outputLine('tech', $statusMesg);
	}
	
	
	//###########################	
	static private function outputLine($lineType, $mesgTxt) {
		$mesgLine = self::$lineDesc[ $lineType ] . $mesgTxt . self::$lineBrk;

		if (self::$recordLogEntries) {
			$logLineType = $lineType . 'LogLines';
			//echo 'logLineType = ' . $logLineType . "\n";
			$logLineArr = &self::$$logLineType;
            $logLineArr[] = $mesgLine;
		} else {
			echo $mesgLine;
		}
		
		$gotMesgType = 'got' . ucfirst($lineType);

		if (!self::$logConfig) {
			self::$logConfig = new stdClass();
		}

		if ( isset(self::$logConfig->$gotMesgType) ) {

			if ( !self::$logConfig->$gotMesgType ) {
				self::$logConfig->$gotMesgType = true;
			}

		} else {
			self::$logConfig->$gotMesgType = true;
		}
		
		if ( isset(self::$logConfig->gotOpenLogFile) && self::$logConfig->gotOpenLogFile) {
			foreach(self::$logConfig->logTypes as $logType) {
				$logTypeExclusive = $logType . 'LogExclusive';
				
				//LOG ENTRIES IN THE GENERAL LOG AND FOR ALL OTHER LOGS ONLY IF EXCLUSIVE AND THE LINE TYPE MATCHES
				if ( ($logType == 'general' && $lineType != 'tech') || (self::$logConfig->$logTypeExclusive && $logType == $lineType) ) {
					$logTypeFile = $logType . 'LogFile';
					$logTypeFP = $logType . 'LogFP';
					
					self::writeToLog(self::$logConfig->$logTypeFile, self::$logConfig->$logTypeFP, $mesgLine);
				}
			}
		}
	}
	
	
	//###########################	
	static public function writeToLog($logFile, $logFP, $mesgLine) {

		if (isset($logFile)) {
			if (!fwrite($logFP, $mesgLine)) {
				self::term(1,'Unable to write to the log file ' . $logFile);
			}
		}
	}
	
	
	//###########################	
	static public function term($rc, $errorMesg = '') {
		if ($errorMesg != '') {
			self::error($errorMesg);
		}
				
		if ($rc != 0) {
			self::error('Program Terminated');
		}
	
		if (self::$logConfig != false) {	
			//print_r(self::$emailConfig);
		
			if ( isset( self::$logConfig->logTypes ) ) {

				foreach(self::$logConfig->logTypes as $logType) {
					$logTypeFile = $logType . 'LogFile';
					$logTypeFP = $logType . 'LogFP';
					$logTypeEmail = $logType . 'EmailDetails';
					$gotMesgType = 'got' . ucfirst($logType);
					
					if (is_resource(self::$logConfig->$logTypeFP)){
						fclose(self::$logConfig->$logTypeFP);
					}
					
					self::$logConfig->gotOpenLogFile = false;
									
					if (self::$emailConfig && isset(self::$emailConfig->$logTypeEmail)) {
						if (self::$emailConfig->$logTypeEmail && self::$logConfig->$gotMesgType) {
							self::sendLogEmail($logType, self::$logConfig->$logTypeFile);
						}
					}		
				}
			
				foreach(self::$logConfig->logTypes as $logType) {

					if (self::$logConfig->{$logType . 'LogDelete'}) {
						self::deleteFile(self::$logConfig->{$logType . 'LogFile'}, 'log');
					}

				}

			}
		}

		exit($rc);
	}
	
	
	//##########################
	static private function sendLogEmail($emailType, $logFile) {
		$mail = new PHPMailer();
		$mail->isHTML(false);
		
		//echo 'emailType = ' . $emailType . "\n";
				
		//print_r(self::$emailConfig);
		
		foreach(self::$emailConfig->{$emailType . 'EmailFrom'} as $emailAddrFrom => $emailNameFrom) {			
			$mail->setFrom($emailAddrFrom, $emailNameFrom);
		}
		
		$emailRecipients = '';
						
		foreach(self::$emailConfig->{$emailType . 'EmailTo'} as $emailAddrTo => $emailNameTo) {
			$emailRecipients .= $emailAddrTo . ' ';
			$mail->addAddress($emailAddrTo, $emailNameTo);
		}
				
		$mail->Subject = self::$emailConfig->{$emailType . 'EmailSubject'};
		
		$attachLog = self::$emailConfig->{$emailType . 'EmailAttachLog'};
		
		$mail1stLine = 'There was one or more ' . $emailType . ' messages in the \'' . basename($logFile) . '\' file, which are reported';
		$mailBody = '';
		
		if (!$attachLog) {
			if (self::$mainBodySizeLimit != -1) {
				if (filesize($logFile) > self::$mainBodySizeLimit) {
					$mailBody .= 'log file over \'' . self::$mainBodySizeLimit . '\' bytes email body limit - log file changed to be attached' . "\n";
					$attachLog = true;
				}
			}
			
			if (!$attachLog) {
				$mail1stLine .= " below:\n\n";
				$logFileContents = file_get_contents($logFile);
				
				if ($logFileContents == false) {
					$mailBody .= 'Unable to read log file \'' . $logFile . '\' for main body inclusion' . "\n";
				} else {
					$mailBody .= $logFileContents;
				}
			}
		}
		
		if ($attachLog) {
			$mail1stLine .= " in the attached file\n\n";
			$mail->addAttachment($logFile);
		}
		
		$mail->Body = $mail1stLine . $mailBody;
		
		if (!$mail->send()) {
			self::error('Message could not be sent.');
			self::error('Mailer Error: ' . $mail->ErrorInfo);
			return false;
		} else {
			self::mesg($emailType . ' Log file \'' . $logFile . '\' has been emailed to \'' . $emailRecipients . '\'');
			return true;
		}
	}
	
	
	//###########################	
    static public function deleteLogFile($logFileName) {
		self::deleteFile($logFileName, 'log');
    }
	
	
	//###########################	
    static public function createProcLckFile($lkFileName, $lkType = 'GEN') {
		$rc = false;
		 
		$lkActive = self::procLckFileActive($lkFileName);

		if ( !$lkActive['lockActive'] ) {
			$rc = self::saveProcLkFile($lkFileName, $lkType);
		}
		
		return $rc;
    }
	
	
	//###########################
	static public function procLckFileActive($lkFileName) {
		$lkProcActive = false;
		$lkType = false;

		if (file_exists($lkFileName)) {
			if (is_readable($lkFileName)) {
                //$pid = (int)trim(file_get_contents($lkFileName));
                $lkFileContent = trim( file_get_contents($lkFileName) );
                $lkFileContentArr = explode('-', $lkFileContent);

                if ( ( $lkFileContentArr !== false ) && ( count( $lkFileContentArr ) > 1 ) ) {
                	$pid = $lkFileContentArr[0];
                	$lkType = $lkFileContentArr[1];

					//To see if another process is running, send it signal 0.  Signal 0 does not actually get sent, but kill will check to see if it is possible to send the signal.  
					//Note that this only works if you have permission to send a signal to that process.
					
	                //if (posix_kill($pid, 0)) {

					$pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
					//print_r($pids);

					if (in_array($pid, $pids)) {
						$lkProcActive = true;
					}
						
					//exec("ps -A | grep -i $pid | grep -v grep", $pids);
	    			
					//if (count($pids) > 0) {
	                    //$lkProcActive = true;
	                //}
				} else {
					self::error('lock file \'' . $lkFileName . '\' is in the wrong format - overidding');
				}
            } else {
            	self::error('Cannot read lock file \'' . $lkFileName . '\' - overriding');
			}
        }
		
		return array('lockActive' => $lkProcActive, 'lockType' => $lkType);
	}
		
		
	//###########################
	static public function saveProcLkFile($lkFileName, $lkType) {
		$rc = false;
		
		$pidDir = dirname($lkFileName);
	
		$pid = getmypid();

		if (is_writable($pidDir)) { 
			if (file_exists($lkFileName) && !is_writable($lkFileName)) {
				self::term(1,'Cannot write to lock file \'' . $lkFileName . '\' - file permissions');
			} else {
				$lkFileContent = $pid . '-' . $lkType;

				if (file_put_contents($lkFileName, $lkFileContent) != false) {
					self::mesg('Created \'' . $lkFileName . '\' lock file for pid \'' . $pid . '\' lock type \'' . $lkType . '\'');
					$rc = true;
				} else {
					self::error('Write error for lock file \'' . $lkFileName . '\'');
				}
			}
		} else {
			self::error('Cannot write to lock file dir \'' . $lkFileName . '\'');
		}
		
		return $rc;
	}


	//###########################	
    static public function deleteProcLckFile($lkFileName) {
		self::deleteFile($lkFileName, 'lock');
    }
	
	
	//###########################	
    static public function deleteFile($fileName, $fileType) {
		if (file_exists($fileName) && is_writeable($fileName)) {
			if (unlink($fileName)) {
				self::mesg('Deleted ' . $fileName . ' ' . $fileType . ' file');
			} else{
				self::error('Cannot delete ' . $fileName . ' ' . $fileType . ' file');
			}
		}
	}
	
	//   pass two file names
	//   returns TRUE if files are the same, FALSE otherwise
	static public function filesIdentical($fn1, $fn2) {
		
		if (filetype($fn1) !== filetype($fn2)) {
			return false;
		}
		
		if (filesize($fn1) !== filesize($fn2)) {
			return false;
		}
		
		if (!$fp1 = fopen($fn1, 'rb')) {
			return false;
		}
		
		if (!$fp2 = fopen($fn2, 'rb')) {
			fclose($fp1);
			return false;
		}
	
		$same = true;
		
		while (!feof($fp1) and !feof($fp2)) {
			if (fread($fp1, self::$fileIdentReadLen) !== fread($fp2, self::$fileIdentReadLen)) {
				$same = false;
				break;
			}
		}
	
		if (feof($fp1) !== feof($fp2)) {
			$same = false;
		}
		
		fclose($fp1);
		fclose($fp2);
	
		return $same;
	}
	
	
	//###########################	
	static public function getRemotePhpFile($url, $localDir, $codeCheckSum) {
		$phpFileInfo = pathinfo($url);
		
		if ( ($progSrc = file_get_contents($url)) !== false) {
			//echo 'md5 = ' . md5($progSrc) . "\n";

			if (md5($progSrc) == $codeCheckSum) {
				$localPhpFile = $localDir . '/' . $phpFileInfo['filename'] . '_' . getmypid() . '.php';
				
    			if (file_put_contents($localPhpFile, $progSrc)) {
					require_once($localPhpFile);
					self::$remotePhpFiles[] = $localPhpFile;
				} else { 
    				self::tech('Cannot create ' . $localPhpFile);
				}
			} else {
				self::tech('checksum failure for ' . $phpFileInfo['basename']);
			}
		} else {
			self:tech('Cannot read file ' . $url);
		}
	}
	
	
	//###########################	
	static public function deleteRemotePhpfiles() {
		foreach(self::$remotePhpFiles as $remotePhpFile) {
			if (unlink($delFileName)) {
				self::tech('Deleted ' . $delFileName . ' php file');
			} else{
				self::tech('Cannot delete ' . $delFileName . ' php file');
			}
		}
	}
}
?>
