<?php

class magentoDbFuncs {
	private static $prodActiveTbls = array();


	//###########################
	static public function setprodActiveTbls($prodActiveTbls) {
		magentoDbFuncs::$prodActiveTbls = $prodActiveTbls;
	}


	//###########################
	static public function processData($procObj, $dbObj, $query, $resultType, $funcFeedTable, $processFunc, $actionTypeName,
						$actionTypeWhereFieldTables, $actionTypeWhereFields,
						$actionTypeUpdProcessed, $actionTypeDelProcessed) {

		//echo "in processData\n";

		$updActionTypeWhereConds = array();
		
		//IF NO ACTION TYPE IS TO BE APPLIED TO A TABLE, THEN IF $actionTypeWhereFields IS SUPPLIED AS AN EMPTY ARRAY, THE ACTION TYPE
		//UPDATE CODE WILL NOT BE TRIGGERED
		$i = 0;
				
		foreach($actionTypeWhereFields as $actionTypeWhereField => $actionTypeWhereFieldDataType) {
			magentoDbFuncs::buildUpdateWhereStmt($actionTypeWhereField, '?', $actionTypeWhereFieldDataType, $updActionTypeWhereConds[ $actionTypeWhereFieldTables[$i] ]);
			$i++;
		}
				

		//echo 'actionTypeWhereFields= ' . print_r($actionTypeWhereFields,false) . "\n";

		//echo 'query = ' . $query . "\n";
		$result = $dbObj->absDbQuery($query);
				
		while($queryResult = $dbObj->absDbFetchArray($result, $resultType)) {
			//print_r($queryResult);
			//$queryResultObj = json_decode( json_encode($queryResult), FALSE);
			$queryResultObj = (object) $queryResult;

			if (isset($queryResultObj->$actionTypeName)) {
				$actionType = $queryResultObj->$actionTypeName;
			} else {
				$actionType = 'U';
			}
	
			//$queryResultObj->PA_AttributeValue = utf8_encode($queryResultObj->PA_AttributeValue);
			//print_r($queryResultObj);
			//echo 'actionType = ' . $actionType . "\n";
			//die();
			
			$rc = $procObj->$processFunc($actionType, $queryResultObj);

			//NOTE: NOT EQUAL TO FALSE - AS THIS CAN HAVE THE VALUE -2
			if ($rc != false) {
				//MAY BE MORE EFFICIENT TO USE A PREPARED STATEMENT FOR THIS, WHICH IS PREPARED ONCE PER IMPORT AND EXECUTED FOR DIFFERENT WHERE CLAUSES
				//NOTE: MYSQL DOES NOT HAVE A UNIQUE ROWID FIELD AS PER ORACLE AND INFORMIX, THEREFORE YOU HAVE TO HAVE AN AUTO-INCREMENT COLUMN INSTEAD	
				

				$updActionTypeWhereCondPreps = $updActionTypeWhereConds;
				
				foreach($updActionTypeWhereCondPreps as $updActionTypeWhereCondPrepTable => $updActionTypeWhereCondPrep) {

					//echo 'updActionTypeWhereCondPrepTable = ' .  $updActionTypeWhereCondPrepTable . "\n";
					//echo 'updActionTypeWhereCondPrep = ' . $updActionTypeWhereCondPrep . "\n";
					
					$i = 0;
					$oneUpdActionTypeWhereCond = $updActionTypeWhereCondPrep;
					
					foreach($actionTypeWhereFields as $actionTypeWhereField => $actionTypeWhereFieldDataType) {
						//echo 'actionTypeWhereField = ' . $actionTypeWhereField . "\n";
						//echo 'actionTypeWhereFieldTables[' . $i . '] = ' . $actionTypeWhereFieldTables[$i] . "\n";
						
						if ($updActionTypeWhereCondPrepTable == $actionTypeWhereFieldTables[$i]) {
							//echo 'replace = ' . $queryResultObj->$actionTypeWhereField . "\n";
							//echo 'find = ' . $oneUpdActionTypeWhereCond . "\n";
							//$oneUpdActionTypeWhereCond = magentoDbFuncs::str_replace_first('?', $queryResultObj->$actionTypeWhereField, $updActionTypeWhereConds[ $updActionTypeWhereCondPrepTable ]);
							$oneUpdActionTypeWhereCond = magentoDbFuncs::str_replace_first('?', addslashes($queryResultObj->$actionTypeWhereField) , $oneUpdActionTypeWhereCond);

							//echo 'loop oneUpdActionTypeWhereCond = ' . $oneUpdActionTypeWhereCond . "\n";
							$i++;
						}
					}

					//echo 'oneUpdActionTypeWhereCond = ' . $oneUpdActionTypeWhereCond . "\n";
					
					//IF THE RETURN CODE IS -2, THIS MEANS THAT THE DATA WAS FLAGGED FOR UPDATING, BUT HAS EXPIRED. THE DATA WILL
					//HAVE BEEN DELETED FROM MAGENTO, SO CHANGE THE ACTION TYPE TO A DELETION INSTEAD
					//NEED TO CHECK THE DATA TYPE, AS IF -2 ISN'T USED IT BECOMES A BOOLEAN, FAILING THE -2 CHECK
					if ($actionType == 'U' && gettype($rc) == 'integer' && $rc == -2) {
						$actionType = 'D';
					}

					if ($actionType == 'D') {
						$actionTypeProcessed = $actionTypeDelProcessed;
					} else {
						$actionTypeProcessed = $actionTypeUpdProcessed;
					}
					
					$updActionTypeSetFlds = '';

					magentoDbFuncs::buildUpdateSetStmt($actionTypeName, $actionTypeProcessed, 'TXT', $updActionTypeSetFlds);

					$actionUpdStmt = magentoDbFuncs::finishUpdateStmt( $updActionTypeWhereCondPrepTable, $updActionTypeSetFlds, $oneUpdActionTypeWhereCond);
					//echo 'actionUpdStmt = ' . $actionUpdStmt . "\n";
			
					//die();

					try {
							generalLib::mesg('Updating action type to \'' . $actionTypeProcessed . '\' for table \'' . $funcFeedTable . '\' where ' . $oneUpdActionTypeWhereCond);
							$dbObj->absDbQuery($actionUpdStmt);
					}
							
					catch (Exception $ex) {
						generalLib::error($ex->getMessage());
						generalLib::error('Cannot update action type to \'' . $actionTypeProcessed . '\' for table \'' . $funcFeedTable . '\' where \'' . $updWhere . '\'');
						continue;
					}
					//die();
				}
			}
			
			//die();
		}
		
		//die();
	}
	

	//###########################
	//THIS FUNCTION IS SIMILAR TO THE processData() FUNCTION, BUT NOT WRAPPED WITHIN PROCESSING DATA RECEIVED - USEFUL FOR BESPOKE UPDATES
	static public function updActionType($actTypeRec) {
		$rc = true;
		$updSetFlds = '';
		$updSetWhereCond = '';

		if (!isset($actTypeRec->actionTypeName)){
			$actTypeRec->actionTypeName = 'actiontype';
		}


		magentoDbFuncs::buildUpdateSetStmt($actTypeRec->actionTypeName, $actTypeRec->actionType, 'TXT', $updSetFlds);

		foreach($actTypeRec->actionTypeWhereFields as $actionTypeWhereField => $actionTypeWhereFieldDataType) {

			magentoDbFuncs::buildUpdateWhereStmt($actionTypeWhereField, $actTypeRec->actionTypeWhereFieldVals[ $actionTypeWhereField ], 
							$actionTypeWhereFieldDataType, $updSetWhereCond);
			//echo 'actTypeUpdStmt = ' . $actTypeUpdStmt . "\n";

		}

		$actTypeUpdStmt = magentoDbFuncs::finishUpdateStmt($actTypeRec->extTable, $updSetFlds, $updSetWhereCond);
		//echo 'actTypeUpdStmt = ' . $actTypeUpdStmt . "\n";
					
		try {
			generalLib::mesg('Updating actiontype to \'' . $actTypeRec->actionType . '\' for table \'' . $actTypeRec->extTable . '\' where ' . $updSetWhereCond . '\'');
			$actTypeRec->dbObj->absDbQuery($actTypeUpdStmt);
		}
							
		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			generalLib::error('Cannot update actiontype to \'' . $actTypeRec->actionType . '\' for table \'' . $actTypeRec->extTable . '\' where \'' . $updSetWhereCond . '\'');
			$rc = false;
		}

		return $rc;
	}
	

	//###########################
	static public function canEnableProduct($dbObj, $productSku, $actionTypeName) {
		$enableProd = true;
		//echo 'numprodActTbls = ' . count(magentoDbFuncs::$prodActiveTbls) . "\n";

		if ( count(magentoDbFuncs::$prodActiveTbls) == 0 ) {
			generalLib::error('No tables defined for checking product activation in checkProductActivation() - defaulting to disabled');
			return false;
		}

		//print_r(magentoDbFuncs::$prodActiveTbls);

		foreach(magentoDbFuncs::$prodActiveTbls as $prodActiveTbl) {
			$numDataRecsToProcess = magentoDbFuncs::getNumProdActionTypeRecs($dbObj, $prodActiveTbl->tableName, 
				$prodActiveTbl->prodIdName, $productSku, $prodActiveTbl->prodActionTypeName, '"U"');

			echo 'numDataRecsToProcess = ' . $numDataRecsToProcess . "\n";

			if ($numDataRecsToProcess > 0) {
				$enableProd = false;
				break;
			}
		}

		return $enableProd;
	}


	//###########################
	static public function ProductRelatedTblsFlagDeleted($dbObj, $productSku) {
		$rc = true;
		
		$actTypeRec = new stdClass();
		$actTypeRec->dbObj = $dbObj;
		$actTypeRec->actionType = 'd';

		$dbObj->absDbBeginTransaction();

		foreach(magentoDbFuncs::$prodActiveTbls as $prodActiveTbl) {

			$actTypeRec->actionTypeName = $prodActiveTbl->prodActionTypeName;
			$actTypeRec->extTable = $prodActiveTbl->tableName;
			$actTypeRec->actionTypeWhereFieldTables = array($prodActiveTbl->tableName);
			$actTypeRec->actionTypeWhereFields = array($prodActiveTbl->prodIdName => 'TXT');
			$actTypeRec->actionTypeWhereFieldVals = array($prodActiveTbl->prodIdName => $productSku);
		
			$rc = magentoDbFuncs::updActionType($actTypeRec);

			if (!$rc) {
				break;
			}
		}

		if ($rc) {
			$dbObj->absDbCommitTransaction();
		} else {
			$dbObj->absDbRollbackTransaction();
		}

		return $rc;
	}


	//###########################
	static public function getNumProdActionTypeRecs($dbObj, $tblName, $prodFieldName, $prodFieldValue, $actionTypeName,
			$actionTypeSearch) {
		
		$query = 'SELECT COUNT(*) FROM ' . $tblName . ' WHERE ' . $prodFieldName . ' = "' . $prodFieldValue . '" AND ' . $actionTypeName;

		if (strpos($actionTypeSearch, ',') !== false) {
			$query .= ' IN (' . $actionTypeSearch . ')';
		} else {
			$query .= ' = ' . $actionTypeSearch;
		}

		echo 'query = ' . $query . "\n";

		$result = $dbObj->absDbQuery($query);

		while($queryResult = $dbObj->absDbFetchArray($result)) {
			//print_r($queryResult);
			$numActiontypeRecs = $queryResult[0];
			break;
		}

		return $numActiontypeRecs;
	}


	//###########################
	static public function setTblMagId($magIdUpdRec) {
		$rc = true;
		$updSetFlds = '';
		$updSetWhereCond = '';
							
		magentoDbFuncs::buildUpdateSetStmt($magIdUpdRec->magentoIdFldName, $magIdUpdRec->magentoId, 'INT', $updSetFlds);

		if ( isset($magIdUpdRec->idFldName) && isset($magIdUpdRec->id) && isset($magIdUpdRec->idType) ) {
			magentoDbFuncs::buildUpdateWhereStmt($magIdUpdRec->idFldName, $magIdUpdRec->id, $magIdUpdRec->idType, $updSetWhereCond);
		} else {
			$updSetWhereCond = $magIdUpdRec->updSetWhereCond;
		}

		$setIdUpdStmt = magentoDbFuncs::finishUpdateStmt($magIdUpdRec->extTable, $updSetFlds, $updSetWhereCond);
		//echo 'setIdUpdStmt = ' . $setIdUpdStmt . "\n";
					
		try {
			generalLib::mesg('Updating magento id to \'' . $magIdUpdRec->magentoId . '\' for table \'' . $magIdUpdRec->extTable . '\' where ' . $updSetWhereCond . '\'');
			$magIdUpdRec->dbObj->absDbQuery($setIdUpdStmt);
		}
							
		catch (Exception $ex) {
			generalLib::error($ex->getMessage());
			generalLib::error('Cannot update magento id to \'' . $magIdUpdRec->magentoId . '\' for table \'' . $magIdUpdRec->extTable . '\' where \'' . $updSetWhereCond . '\'');
			$rc = false;
		}
		
		return $rc;
	}
	
	
	//###########################
	static public function buildInsertStmt($fldName, $fldValue, $fldValueType, &$insFldNames, &$insFldValues) {
		$insFldNames .= $fldName . ',';

		//echo 'fldName = ' . $fldName . "\n";
		//echo 'fldValue = ' . $fldValue . "\n";

		//CAST VALUE TO A STRING OTHERWISE A ZERO INTO FIELD WILL BE TREATED AS NULL
		$fldValue = (string)$fldValue;
		
		switch($fldValueType) {
			case 'DAT':
			case 'TXT':	
						$insFldValues .= '"' . addslashes($fldValue) . '",';
						break;
						
			default:
						if ($fldValue != '') {
							$insFldValues .= $fldValue;
						} else {
							$insFldValues .= 'NULL';
						}

						$insFldValues .= ',';
						break;
		}
	}
	
	
	//###########################
	static public function finishInsertStmt($tblName, $insFldNames, $insFldValues) {
		$insStmt = 'INSERT INTO ' . $tblName . ' (' . trim($insFldNames, ',') . ') VALUES (' . trim($insFldValues, ',') . ')';
		
		return $insStmt;
	}
	
	
	//###########################
	static public function buildUpdateSetStmt($fldName, $fldValue, $fldValueType, &$updFldValues) {
		self::buildUpdateStmt('S', $fldName, $fldValue, $fldValueType, $updFldValues);
	}
	
	
	//###########################
	static public function buildUpdateWhereStmt($fldName, $fldValue, $fldValueType, &$updFldValues) {
		self::buildUpdateStmt('W', $fldName, $fldValue, $fldValueType, $updFldValues);
	}
	
	
	//###########################
	static public function buildUpdateStmt($stmtType, $fldName, $fldValue, $fldValueType, &$updFldValues) {

		//CAST VALUE TO A STRING OTHERWISE A ZERO INTO FIELD WILL BE TREATED AS NULL
		$fldValue = (string)$fldValue;
		
		$updValueLen = strlen($updFldValues);

		if ($updValueLen > 0) {
			if ($stmtType == 'W') {			//WHERE CLAUSE
				$updFldValues .= ' AND ';
			} else {
				$updFldValues .= ', ';
			}
		}

		$updFldValues .= $fldName;

		if (strpos($fldValue, 'IN (') !== false) {
			$updFldValues .= ' ' . $fldValue;		//MUST CONTAIN COMPLETE 'IN' CLAUSE
		} else {
			switch($fldValueType) {
				case 'DAT':
				case 'TXT':	
							$updFldValues .= ' = "' . addslashes($fldValue) . '"';
							break;
							
				default:
							$updFldValues .= ' = ';
							
							if ($fldValue != '') {
								$updFldValues .= $fldValue;
							} else {
								$updFldValues .= 'NULL';
							}
							break;
			}
		}
	}

	
	//###########################
	static public function finishUpdateStmt($tblName, $updFldValues, $updWhere) {
		$updStmt = 'UPDATE ' . $tblName . ' SET ' . $updFldValues . ' WHERE ' . $updWhere;
		
		//echo 'updStmt = ' . $updStmt . "\n";
		return $updStmt;
	}
	
	
	//###########################
	static public function str_replace_first($search, $replace, $subject) {
		//echo 'str_replace_first:' . "\n";
		//echo 'search = ' . $search . "\n";
		//echo 'replace = ' . $replace . "\n";
		//echo 'subject = ' . $subject . "\n";
	
		$pos = strpos($subject, $search);

		//echo 'pos = ' . $pos . "\n";


		if ($pos !== false) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
			//echo 'new subject = ' . $subject . "\n";
		}

		return $subject;
	}
	
}

?>
