<?php
abstract class absDb {
	protected $dbConfig;
    
	//###########################
	public function __construct($dbConfig) {
		$this->dbConfig = $dbConfig;
	}
	
	
	//###########################
	public function absInit() {
		$this->dbConfig->connObj = $this->dbInit();
		
		if ($this->dbConfig->connObj) {
			generalLib::mesg($this->dbConfig->db . 'initialised');
		} else {
			generalLib::error($this->dbConfig->db . 'failed to initialise');
		}
	}
	
	
	//###########################
	public function absDbConnect() {
		$this->dbConfig->conn = $this->dbConnect();
		//print_r($this->dbConfig);
		
		if ($this->dbConfig->conn) {
			generalLib::mesg('Connected to ' . $this->dbConfig->host . ' for user ' . $this->dbConfig->user);
		} else {
			generalLib::error('Cannot connect to ' . $this->dbConfig->host . ' for user ' . $this->dbConfig->user);
		}
		
		return $this->dbConfig->conn;
	}
	
	
	//###########################
	public function absDbSelect($dbName) {
		if (($rc = $this->dbSelect($dbName))) {
			generalLib::mesg('Selected database ' . $dbName);
		} else {
			generalLib::error('Cannot select database ' . $dbName);
		}
		
		return $rc;
	}
	
	//###########################
	public function absDbQuote($str) {
		return $this->dbQuote($str);
	}
	
	//###########################
	public function absDbQuery($query, $batchSize = 0) {
		return $this->dbQuery($query, $batchSize);
	}


	//###########################
	public function absDbPrepare($query, $driverOpts = null) {

		if ($driverOpts == null) {
			return $this->dbPrepare($query);
		} else {
			return $this->dbPrepare($query, $driverOpts);
		}

	}


	//###########################
	public function absDbExecute($stmt, $params = null) {

		if ($params == null) {
			return $this->dbExecute($stmt);
		} else {
			return $this->dbExecute($stmt, $params);
		}

	}
	
	
	//###########################
	public function absDbFetchArray($result, $resultType = null) {

		if ($resultType == null) {
			return $this->dbFetchArray($result);
		} else {
			return $this->dbFetchArray($result, $resultType);
		}

	}
	
	
	//###########################
	public function absDbFreeResult($result) {
		if (!($rc = $this->dbFreeResult($result))) {
			generalLib::error('Cannot free result for ' . $result);
		}
		
		return $rc;
	}
	
	
	//###########################
	public function absDbBeginTransaction() {
		
		if (!($rc = $this->dbBeginTransaction())) {
			generalLib::error('Cannot begin transaction');
		}
		
		return $rc;
	}
	
	
	//###########################
	public function absDbCommitTransaction() {
		
		if (!($rc = $this->dbCommitTransaction())) {
			generalLib::error('Cannot commit transaction');
		}
		
		return $rc;
	}
	
	
	//###########################
	public function absDbRollbackTransaction() {
		
		if (!($rc = $this->dbRollbackTransaction())) {
			generalLib::error('Cannot rollback transaction');
		}
		
		return $rc;
	}
	
	
	//###########################
	public function absLastInsertId() {
		return $this->dbLastInsertId();
	}
	
	
	//###########################
	public function absDbclose() {
		if ($this->dbClose()) {
			generalLib::mesg('Connection closed to ' . $this->dbConfig->host . ' for user ' . $this->dbConfig->user);
		} else {
			generalLib::error('Cannot close connection to ' . $this->dbConfig->host . ' for user ' . $this->dbConfig->user);
		}
	}
	
	abstract protected function dbConnect();
	abstract protected function dbSelect($dbName);
	abstract protected function dbQuery($query, $batchSize = 0);
	abstract protected function dbFetchArray($result, $resultType);
	abstract protected function dbFreeResult($result);
	abstract protected function dbBeginTransaction();
	abstract protected function dbCommitTransaction();
	abstract protected function dbRollbackTransaction();
	abstract protected function dbClose();
}

?>
