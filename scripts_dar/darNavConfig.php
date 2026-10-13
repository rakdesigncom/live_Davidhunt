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
						$this->progConfig->baseDir = '/Applications/XAMPP/htdocs/magento2';
						//$this->progConfig->siteDir = $this->progConfig->baseDir;
						$this->progConfig->siteDir = '/Applications/XAMPP/magento2';

						$this->progConfig->scriptGenLibDir = $this->progConfig->baseDir . '/../connector_plus_general_classes';
						$this->progConfig->scriptMagLibDir = $this->progConfig->baseDir . '/../connector_plus_magento2_classes';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts/dar';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/nav_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->errorMailSubject = 'DAR DEV NAV Processor Error';
						$this->progConfig->errorMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');

						$this->progConfig->warnMailSubject = 'DAR DEV NAV Processor Warning';
						$this->progConfig->warnMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');

						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/nav_proc_logs';

						$this->progConfig->baseWsUrl = 'http://nav.darlighting.co.uk:7143/DarUAT/WS/Dar Lighting UAT/Page';
						//$this->progConfig->baseWsUrl = 'http://nav.darlighting.co.uk:7147/Dar/WS/Dar Lighting UAT/Page';
						break;

			case 'UAT':
						$this->progConfig->rootCatID = '2';
//						$this->progConfig->baseDir = '/home/mtwodarl/nav.m2.darlighting.co.uk';
						$this->progConfig->baseDir = dirname(__FILE__, 2);
						$this->progConfig->siteDir = $this->progConfig->baseDir;
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts_dar';
						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts_dar';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/scripts_dar/nav_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->errorMailSubject = 'DAR UAT NAV Processor Error';
						$this->progConfig->errorMailFrom = array('daniel@fyb.ro' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('daniel@fyb.rodaniel@fyb.ro' => 'John Little');

						$this->progConfig->warnMailSubject = 'DAR UAT NAV Processor Warning';
						$this->progConfig->warnMailFrom = array('daniel@fyb.ro' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('daniel@fyb.ro' => 'John Little');

						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/nav_proc_logs';

						$this->progConfig->baseWsUrl = 'http://nav.darlighting.co.uk:7143/DarUAT/WS/Dar Lighting UAT/Page';
						//$this->progConfig->baseWsUrl = 'http://nav.darlighting.co.uk:7147/Dar/WS/Dar Lighting/Page';
						break;

			case 'LIV':
						$this->progConfig->rootCatID = '2';
						$this->progConfig->baseDir = '/home/ae4a9cb4/live.darlighting.co.uk';
						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/nav_proc_logs/' . date('Y_m');

						$this->progConfig->imageBaseWatchedDir = $this->progConfig->baseDir . '/images';
						$this->progConfig->mainImagesDir = 'in';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						$this->progConfig->imgSizelimit = '3145728'; //3MB

						$this->progConfig->errorMailSubject = 'DAR NAV Processor Error';
						$this->progConfig->errorMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');

						$this->progConfig->warnMailSubject = 'DAR NAV Processor Warning';
						$this->progConfig->warnMailFrom = array('darlight@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');
						//$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little', 'steven.samols@gmail.com' => 'Steven Samols');


						$this->progConfig->maxIdxRetries = 5;
						$this->progConfig->retryIdxDelay = 600; //10 minutes
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/nav_proc_logs';

						$this->progConfig->baseWsUrl = 'http://nav.darlighting.co.uk:7147/Dar/WS/Dar Lighting/Page';
						break;
		}
	}

}

?>
