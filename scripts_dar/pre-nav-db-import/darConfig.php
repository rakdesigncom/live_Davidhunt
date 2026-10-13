<?php
/*
 * Name:		progConfig.php
 * Company: 		Star Digital
 * Author:		John Little
 * Function:		Dar config file
 */

class progConfig {
	public $progConfig;


	//###########################
	public function __construct() {
		global $argv;

		//print_r($argv);

		$this->progConfig = new stdClass;

		$this->progConfig->environment = isset($argv[1]) ? $argv[1] : 'TST';

		switch($this->progConfig->environment) {
			case 'TST':
						$this->progConfig->rootCatID = '2';
						$this->progConfig->baseDir = '/home/mtwodarl/staging.darlighting.co.uk';
						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';
						
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/db_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->dbConfig = new stdClass();
						$this->progConfig->dbConfig->host = 'sipuk5-16.nexcess.net';
						$this->progConfig->dbConfig->user = 'darlight_exchang';
						$this->progConfig->dbConfig->pword = 'D@rExch@ng3';
						$this->progConfig->dbConfig->db = 'darlight_dev_exchange';
						$this->progConfig->dbConfig->absDb = 'pdo';
						$this->progConfig->dbConfig->dbType = 'mysql';
						
						$this->progConfig->errorMailSubject = 'DAR STG DB Processor Error';
						$this->progConfig->errorMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'DAR STG DB Processor Warning';
						$this->progConfig->warnMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');

						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/db_proc_logs';
						break;
						
			case 'UAT':
						$this->progConfig->rootCatID = '2';
						$this->progConfig->baseDir = '/home/mtwodarl/m2.darlighting.co.uk';
						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/db_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images_dev';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->dbConfig = new stdClass();
						$this->progConfig->dbConfig->host = 'sipuk5-16.nexcess.net';
						$this->progConfig->dbConfig->user = 'darlight_exchang';
						$this->progConfig->dbConfig->pword = 'D@rExch@ng3';
						$this->progConfig->dbConfig->db = 'darlight_dev_exchange';
						$this->progConfig->dbConfig->absDb = 'pdo';
						$this->progConfig->dbConfig->dbType = 'mysql';
						
						$this->progConfig->errorMailSubject = 'DAR DEV DB Processor Error';
						$this->progConfig->errorMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'DAR DEV DB Processor Warning';
						$this->progConfig->warnMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');

						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/db_proc_logs';
						break;
						
			case 'LIV':	
						$this->progConfig->rootCatID = '2';
						$this->progConfig->baseDir = '/home/mtwodarl/live.darlighting.co.uk';
						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/db_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->dbConfig = new stdClass();
						$this->progConfig->dbConfig->host = 'sipuk5-16.nexcess.net';
						$this->progConfig->dbConfig->user = 'darlight_exchang';
						$this->progConfig->dbConfig->pword = 'D@rExch@ng3';
						$this->progConfig->dbConfig->db = 'darlight_exchange';
						$this->progConfig->dbConfig->absDb = 'pdo';
						$this->progConfig->dbConfig->dbType = 'mysql';
						
						$this->progConfig->errorMailSubject = 'DAR DB Processor Error';
						$this->progConfig->errorMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'DAR DB Processor Warning';
						$this->progConfig->warnMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little', 'steven.samols@gmail.com' => 'Steven Samols');

						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/db_proc_logs';
						break;
		}
	}

}

?>
