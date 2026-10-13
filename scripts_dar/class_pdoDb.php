<?php

class pdoDb extends absDb {
	
	public function __construct($dbConfig) {
		parent::__construct($dbConfig);
	}

	//###########################
	public function dbConnect() {

		return new PDO($this->dbConfig->dbType . ':host=' . $this->dbConfig->host . ';dbname=' . $this->dbConfig->db . ';charset=utf8', $this->dbConfig->user, $this->dbConfig->pword, 
							array( PDO::ATTR_PERSISTENT => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	}
	
	
	//###########################
	public function dbSelect($dbName) {
		//NOT APPLICABLE FOR PDO, AS THE DATABASE NAME IS SPECIFIED AT THE CONNECTION LEVEL
	}


	//###########################
	public function dbQuote($str) {
		$result = $this->dbConfig->conn->quote($str);

		return $result;
	}
	
	
	//###########################
	public function dbQuery($query, $batchSize = 0) {
		try {
			$result = $this->dbConfig->conn->query($query);
		}

		catch(PDOException $e) {
			generalLib::error($e->getMessage());
			generalLib::error('dbQuery error');
		}

		if ($result == false) {
			generalLib::error('dbQuery: last error = ' . $this->dbConfig->conn->errorcode());
		}
		
		//echo 'db result: ' . "\n";
		//print_r($result);

		return $result;
	}
	
	
	//###########################
	public function dbFetchArray($result, $resultType = PDO::FETCH_BOTH) {
		//echo 'result = ' . $result . "\n";
				
		$row = $result->fetch($resultType);
		
		//print_r($row);
		
		return $row;
	}
	
	//###########################
	public function dbPrepare($query, $driver_options = null) {
		try {
			$result = $this->dbConfig->conn->prepare($query);
		}

		catch(PDOException $e) {
			generalLib::error($e->getMessage());
			generalLib::error('dbPrepare error');
		}
	
		return $result;
	}

	//###########################
	public function dbExecute($stmt, $params) {
		try {
			$result = $stmt->execute($params);
		}

		catch(PDOException $e) {
			generalLib::error($e->getMessage());
			generalLib::error('dbExecute error');
		}

		return $result;
	}
	
	//###########################
	public function dbFreeResult($result) {
		return $result->closeCursor();
	}
	
	
	//###########################
	public function dbLastInsertId() {
		return $this->dbConfig->conn->lastInsertId();
	}
	
	
	//###########################
	public function dbBeginTransaction() {
		return $this->dbConfig->conn->beginTransaction();
	}
	
	
	//###########################
	public function dbCommitTransaction() {
		return $this->dbConfig->conn->commit();
	}
	
	
	//###########################
	public function dbRollbackTransaction() {
		return $this->dbConfig->conn->rollback();
	}
	
	
	//###########################
	public function dbClose() {
        	$this->dbConfig->conn = null;	
		return true;
	}
}

?>
