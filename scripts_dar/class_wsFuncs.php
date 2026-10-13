<?php
class wsFuncs {
	private $readClient;
	private $writeClient;
	private $firstBatchRecordKey;
	private $actionTypeUpdProcessed;
	private $actionTypeDelProcessed;
	private $procObj;


	//###########################
	public function __Construct( $procObj ) {
		$this->procObj = $procObj;
	}

	//###########################
	public function setActionTypeProcessed($actionTypeUpdProcessed, $actionTypeDelProcessed) {
		$this->actionTypeUpdProcessed = $actionTypeUpdProcessed;
		$this->actionTypeDelProcessed = $actionTypeDelProcessed;
	}


	//###########################
	public function processReadUrl($url, $parameters) {

		return $this->processWsUrl($url, 'Read', $parameters);

	}


	//###########################
	public function processWsUrl($url, $action, $parameters) {

		$retArr = array();

		$rc = true;

		$response = false;

		try {

			$client = new projectNTLMSoapClient($url);

		}

		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			$rc = false;
		}

		//echo 'url = ' . $url . "\n";
		//echo 'action = ' . $action . "\n";
		//print_r($parameters);
		//die();

		if ($rc) {

			try {
				$response = $client->$action($parameters);
			}

			catch (Exception $ex) {
				generalLib::error($ex->getMessage());
				$rc = false;
			}

			//print_r($response);
		}

		$retArr['rc'] = $rc;
		$retArr['response'] = $response;

		return $retArr;
	}


	//###########################
	public function processData($readUpdUrl, $action, $parameters, $batchSize, $readWebService, $processFunc, $actionTypeName, 
									$actionTypeDeleteRecord = false) {

		$this->processDataUrls($readUpdUrl, $readUpdUrl, $action, $parameters, $batchSize, $readWebService, $readWebService, $processFunc, $actionTypeName, $actionTypeDeleteRecord);

	}


	//###########################
	//NEEDED WHERE THERE ARE TWO SEPARATE NAV URLS - ONE FOR READING DATA AND ANOTHER FOR WRITING DATA EG. THE INTEGRATION ACTION UPDATE
	public function processSepReadWriteData($readUrl, $writeUrl, $action, $parameters, $batchSize, $readWebService, $writeWebService, $processFunc, $actionTypeName, 
										$actionTypeDeleteRecord = false) {

		$this->processDataUrls($readUrl, $writeUrl, $action, $parameters, $batchSize, $readWebService, $writeWebService, $processFunc, $actionTypeName, $actionTypeDeleteRecord);

	}


	//###########################
	public function processDataUrls($readUrl, $writeUrl, $action, $parameters, $batchSize, $readWebService, $writeWebService, $processFunc, $actionTypeName, 
									$actionTypeDeleteRecord) {

		if ($this->actionTypeUpdProcessed == '' || $this->actionTypeDelProcessed == '') {
			generalLib::Error('action type process flag undefined - please set');
			return;
		}

		$gotResponse = 0;

		echo 'url = ' . $readUrl . "\n";
		//die();

		try {

			$this->readClient = new projectNTLMSoapClient($readUrl);

		}

		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			generalLib::term(1);
		}


		if ($writeUrl != $readUrl) {

			try {

				$this->writeClient = new projectNTLMSoapClient($writeUrl);

			}

			catch (Exception $ex) {
				generalLib::error($ex->getMessage());
				generalLib::term(1);
			}

		} else {
			$this->writeClient = $this->readClient;
		}


		$parameters['setSize'] = $batchSize;

		//echo "got parameters\n";
		//print_r($parameters);
		//die();
		//print_r($this->client);

		$updFunc = $processFunc . '_Update';

		$actionResult = $action . '_Result';

		$gotResponse = 0;

		//echo 'action = ' . $action . "\n";
		//echo 'actionResult = ' . $actionResult . "\n";
		

		$line = 1;

		while( $response = $this->getResponse($action, $parameters) ) {
			//var_dump($response);
			//echo "got parameters loop\n";
			//var_dump($parameters);
			//die();

			$gotResponse = count( (array)$response->$actionResult);
			//echo 'gotResponse = ' . $gotResponse . "\n";

			if ($gotResponse == 0) {
				generalLib::mesg('No ' . $readWebService . ' records to process');
				$gotResponse = 1;
				break;
			}

			foreach($response->$actionResult as $response) {

				$responseType  = gettype($response);

				//var_dump($response);
				//echo 'responseType = ' . $responseType . "\n";

				if ($responseType == 'object') {

						$retArr = $this->processOneRecord($writeWebService, $processFunc, $actionTypeName, $response, $actionTypeDeleteRecord);
						//var_dump($response);
						//die();

				} else {

					foreach($response as $responseRecord) {
						//var_dump($responseRecord);

						$retArr = $this->processOneRecord($writeWebService, $processFunc, $actionTypeName, $responseRecord, $actionTypeDeleteRecord);
						//die();
					}


				}
//var_dump($retArr['updResponseKey']);
                                if(isset($retArr['updResponseKey'])){
				$parameters['bookmarkKey'] = $retArr['updResponseKey'];
                                }
				//echo "new parameters\n";
				//print_r($parameters);
				//die();

			}
		}

		//echo "response = \n";
		//print_r($response);

		if ($gotResponse == 0) {
			generalLib::Error('No response from web service - check credentials');
		}

		//die();
		//print_r($response);
	}

	
	//###########################
	private function getResponse($action, $parameters) {

		//echo 'getResponse: action = ' . $action . "\n";
		//echo 'getResponse: parameters = ' . $parameters . "\n";

		try {
			$response = $this->readClient->$action($parameters, $action);
		}

		catch (Exception $ex) {
			//echo "response error\n";
                    var_dump($ex->getMessage());
			generalLib::error($ex->getMessage());
			$response = false;
		}

		return $response;
	}


	//###########################
	private function processOneRecord($writeWebService, $processFunc, $actionTypeName, $responseRecord, $actionTypeDeleteRecord) {
		
		//var_dump($responseRecord);

		//echo "processOneRecord:\n";

		if (isset($responseRecord->$actionTypeName)) {
			$actionType = $responseRecord->$actionTypeName;
		} else {
			$actionType = 'U';
		}


		if ($actionType == 'd' || $actionType == $this->actionTypeDelProcessed ) {
			$actionType = 'D';
		}

		//echo 'processFunc = ' . $processFunc . "\n";

		if (method_exists($this->procObj, $processFunc)) {
			$retArr = $this->procObj->$processFunc($actionType, $responseRecord);

			//OVERRIDE ACION TYPE
			if ( isset( $retArr['actionType'] ) ) {
				$actionType = $retArr['actionType'];
			}
        }

		if ($retArr['rc'] == true) {

			if ($actionType == 'D') {
				$actionTypeProcessed = $this->actionTypeDelProcessed;
			} else {
				$actionTypeProcessed = $this->actionTypeUpdProcessed;
			}

			if ($actionType == 'U' && gettype( $retArr['rc'] ) == 'integer' && $retArr['rc'] == -2) {
				$actionType = 'D';
			}

			if ($actionTypeDeleteRecord && $actionType == 'D') {

				$delRecord = new stdClass();
				$delRecord->Key = $responseRecord->Key;

				$retArr = $this->deleteOneRecord($writeWebService, $responseRecord, $delRecord);

			} else {

				if ( !empty($actionTypeName) ) {
					//echo "point 0\n";

					$updRecord = array();
					$updRecord['Key'] = $responseRecord->Key;
					$updRecord[ $actionTypeName ] = $actionTypeProcessed;

					foreach($retArr as $retArrKey => $retArrKeyVal) {
						if ( ( strpos($retArrKey, 'updWS_') ) !== false) {

							$retArrKey = str_replace('updWS_', '', $retArrKey);
							$updRecord[ $retArrKey ] = $retArrKeyVal;
						}
					}

					$retArr = $this->updateOneRecord($writeWebService, $responseRecord, $updRecord);

				} else {

					//echo "point 1\n";

					if ( isset( $retArr['Key'] ) ) {
						$retArr['updResponseKey'] = $retArr['Key'];
					} else {
						$retArr['updResponseKey'] = $responseRecord->Key;
					}

					$retArr['rc'] = true;
					
				}

			}

			//die();
		} else {

			//echo "point 2\n";

			$retArr['updResponseKey'] = $responseRecord->Key;
			
		}

		//echo "processOneRecord:\n";
		//echo "retArr = \n";
		//print_r($retArr);

		return $retArr;
	}


	//###########################
	private function updateOneRecord($writeWebService, $responseRecord, $updRecord) {
		$rc = true;
		$retArr = array();

		$parameters = array($writeWebService => $updRecord);

		//print_r($parameters);
		//die();

		generalLib::mesg('Update for web service \'' . $writeWebService . '\' ' . print_r($updRecord, true));

		//die();

		try {
			$updResponse = $this->writeClient->Update($parameters, 'Update');
		}
	
		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			generalLib::error('Cannot update web service \'' . $writeWebService . '\' for . ' . print_r($updRecord, true) );
			$rc = false;
		}

		if ($rc) {
			$retArr = $this->chkUpdateResponse($updResponse, $writeWebService, $updRecord['Key']);

			if ( $retArr['rc'] ) {
				generalLib::mesg($writeWebService . ' web service record updated');
			}
		} else {
			$retArr['rc'] = $rc;
		}

		//var_dump($response);
		return $retArr;
	}


	//###########################
	public function chkUpdateResponse($updResponse, $webService, $oldKey) {
		$rc = true;

		$gotUpdResponse = count( (array)$updResponse->$webService);

		//echo 'gotResponse = ' . $gotResponse . "\n";

		if ($gotUpdResponse == 0) {
			generalLib::warn('No ' . $webService . ' response');
			$updResponseKey = '';
			$rc = false;
		} else {
			$updResponseKey = $updResponse->$webService->Key;

			if ($oldKey == $updResponseKey) {
				generalLib::mesg('Update Key has not changed from \'' . $oldKey . '\'');
				$rc = false;
			}

			/*
			foreach($updRecord as $updKey => $updVal) {

				if ($updKey == 'Key') {

					if ($response->$funcFeedWebService->$updKey == $updVal) {
						generalLib::warn('Update Key has not changed from \'' . $updVal . '\'');
						$rc = false;
					}

				} else {

					if ($response->$funcFeedWebService->$updKey != $updVal) {
						generalLib::warn('\'' . $updKey . '\' has not changed value - still \'' . $updVal . '\'');
						$rc = false;
					}

				}
			}
			*/
		}


		$retArr['updResponseKey'] = $updResponseKey;
		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function updWsRecField($wsNewFieldValue, $wsRec, $wsFieldName, &$retArr) {
		$updWsField = false;

		//echo 'fieldname = '  . $wsFieldName  . "\n";
		//echo 'wsrec = ' . $wsRec . "\n";
		//echo 'value = ' . $wsRec->$wsFieldName . "\n";

		if ( empty($wsRec->$wsFieldName)) {
			$updWsField = true;
		} else {

			if ($wsRec->$wsFieldName != $wsNewFieldValue) {
				$updWsField = true;
			}

		}

		if ($updWsField) {
			$retArr['updWS_' . $wsFieldName] = $wsNewFieldValue;
		}
	}

/*
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sal="urn:microsoft-dynamics-schemas/page/salesprice">
   <soapenv:Header/>
   <soapenv:Body>
      <sal:Delete>
         <sal:Key>92;WhsAAACJ/0MwMzc1MgAAiwAAAAAAif9XSDAwMQAALgAAAAAAiQAAAIkAAACJAAAAMgAAAAAAAAAAAAAAAACFAAAAAACF9;7950463310;</sal:Key>
      </sal:Delete>
   </soapenv:Body>
</soapenv:Envelope>

<Soap:Envelope xmlns:Soap="http://schemas.xmlsoap.org/soap/envelope/">
   <Soap:Body>
      <Delete_Result xmlns="urn:microsoft-dynamics-schemas/page/salesprice">
         <Delete_Result>true</Delete_Result>
      </Delete_Result>
   </Soap:Body>
</Soap:Envelope>
*/

	//###########################
	private function deleteOneRecord($writeWebService, $responseRecord, $delRecord) {
		$rc = true;
		$retArr = array();

		//print_r($delRecord);
		//die();

		generalLib::mesg('Delete for web service \'' . $writeWebService . '\' ' . print_r($delRecord, true));

		//die();

		$delResponse = $this->writeClient->Delete($delRecord);
		//$delResponse = $this->writeClient->Delete($parameters);

		//print_r($delResponse);

		/*	
		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			generalLib::error('Cannot update action type to \'' . $actionTypeProcessed . '\' for table \'' . $funcFeedTable . '\' where \'' . $updWhere . '\'');
			continue;
		}
		*/

		$retArr = $this->chkDeleteResponse($delResponse, $writeWebService, $delRecord->Key);

		if ( $retArr['rc'] ) {
			generalLib::mesg($writeWebService . ' web service record deleted');
		}

		//print_r($retArr);
		//die();

		//var_dump($response);
		return $retArr;
	}


	//###########################
	public function chkDeleteResponse($delResponse, $webService, $delKey) {
		$rc = true;

		$gotDelResponse = count( (array)$delResponse->Delete_Result);

		//echo 'gotDelResponse = ' . $gotDelResponse . "\n";

		if ($gotDelResponse == 0) {

			generalLib::warn('No ' . $webService . ' response');
			$rc = false;

		} else {

			$delResult = $delResponse->Delete_Result;

			if (!$delResult) {
				generalLib::warn('Deletion failed for key \'' . $delKey . '\'');
				$rc = false;
			}

		}


		$retArr['updResponseKey'] = $delKey;
		$retArr['rc'] = $rc;

		return $retArr;
	}
}

?>
