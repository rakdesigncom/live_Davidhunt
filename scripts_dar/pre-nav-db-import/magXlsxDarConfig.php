<?php

/*
 * Name:		xxxConfig.php
 * Company: 		Star Digital
 * Author:		John Little
 * Function:	xxx config file
 */

class progConfig {
	public $progConfig;


	//###########################
	public function __construct() {
		global $argv;

		$this->progConfig = new stdClass;

		$this->progConfig->customer = isset($argv[1]) ? $argv[1] : 'UNKNOWN';

		$this->progConfig->environment = isset($argv[2]) ? $argv[2] : 'TST';

		switch( $this->progConfig->environment ) {

			case 'TST':
						$this->progConfig->rootCatID = '2';

						$this->progConfig->brandsActive = true;
						$this->progConfig->brandsBaseCatId = '73';
						$this->progConfig->brandsTmpCatId = '704';
						$this->progConfig->brandsProdAttr = 'manufacturer';

						//$this->progConfig->baseDir = '/Applications/XAMPP/htdocs/magento';
						$this->progConfig->baseDir = '/Applications/XAMPP/htdocs/mag_csv_import';

						//$this->progConfig->siteDir = $this->progConfig->baseDir;
						$this->progConfig->siteDir = '/Applications/XAMPP/htdocs/magento';

						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/../magento_custom_classes';
						$this->progConfig->scriptDir = '/Applications/XAMPP/htdocs/mag_csv_import/scripts';
						$this->progConfig->localLogDir = '/Applications/XAMPP/htdocs/mag_csv_import/xlsx_proc/logs/' . date('Y_m');

						$this->progConfig->imageBaseDir = $this->progConfig->baseDir . '/xlsx_proc/in/images/images_big';
						$this->progConfig->mainImagesDir = '';
						$this->progConfig->imgProdAttrToFilter = 'sku';
						
						$this->progConfig->errorMailSubject = 'Dance Gear Processor Error';
						$this->progConfig->errorMailFrom = array('info@dancegear.co.uk' => 'Dance Gear');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'Dance Gear Processor Warning';
						$this->progConfig->warnMailFrom = array('info@dancegear.co.uk' => 'Dance Gear');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						//$this->progConfig->lockDir = $this->progConfig->baseDir . '/csv_proc_logs';
						$this->progConfig->lockDir = '/Applications/XAMPP/htdocs/mag_csv_import/xlsx_proc/logs';

						//$this->progConfig->excelDir = $this->progConfig->baseDir . '/excel_sheets';
						$this->progConfig->excelDir = '/Applications/XAMPP/htdocs/mag_csv_import/excel_sheets';

						$this->progConfig->localXlsxInboxDir = $this->progConfig->baseDir . '/xlsx_proc/in';
						$this->progConfig->localXlsxInboxProcDir = $this->progConfig->baseDir . '/xlsx_proc/in/processed';

						$this->progConfig->gotTopLevelCat = false;

						$this->progConfig->excelReferenceDir = $this->progConfig->baseDir . '/xlsx_proc/excel_sheets';
						break;
						
			case 'UAT':
						$this->progConfig->rootCatID = '2';

						$this->progConfig->brandsActive = false;
						$this->progConfig->brandsBaseCatId = '8';
						$this->progConfig->brandsTmpCatId = '71';
						$this->progConfig->brandsProdAttr = 'brand';

						$this->progConfig->baseDir = '/home/mtwodarl/staging.darlighting.co.uk';

						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';

						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/magxlsxproc/logs';

						$this->progConfig->imageBaseDir = $this->progConfig->baseDir . '/magxlsxproc/in';
						$this->progConfig->mainImagesDir = 'images';
						$this->progConfig->imageMagRelDir = '/import/magxlsxproc/in/images';

						$this->progConfig->imgProdAttrToFilter = 'sku';
						
						$this->progConfig->errorMailSubject = 'Dar M2 Processor Error (UAT)';
						$this->progConfig->errorMailFrom = array('info@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'Dar M2 Processor Warning (UAT)';
						$this->progConfig->warnMailFrom = array('info@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/magxlsxproc/logs';

						//$this->progConfig->excelDir = $this->progConfig->baseDir . '/excel_sheets';
						$this->progConfig->excelDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets';

						$this->progConfig->localXlsxInboxDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets';
						$this->progConfig->localXlsxInboxProcDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets/processed';

						$this->progConfig->localXlsxOutboxDir = $this->progConfig->baseDir . '/magxlsxproc/out/spreadsheets';
						$this->progConfig->localXlsxOutboxProcDir = $this->progConfig->baseDir . '/magxlsxproc/out/spreadsheets/processed';

						$this->progConfig->gotTopLevelCat = false;

						$this->progConfig->excelReferenceDir = $this->progConfig->baseDir . '/magxlsxproc/excel_sheets';
						break;
						
			case 'LIV':	
						$this->progConfig->rootCatID = '2';

						$this->progConfig->brandsActive = false;
						$this->progConfig->brandsBaseCatId = '8';
						$this->progConfig->brandsTmpCatId = '71';
						$this->progConfig->brandsProdAttr = 'brand';

						$this->progConfig->baseDir = '/home/mtwodarl/live.darlighting.co.uk';

						//$this->progConfig->siteDir = $this->progConfig->baseDir;
						$this->progConfig->siteDir = $this->progConfig->baseDir . '/html';

						$this->progConfig->scriptLibDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->scriptDir = $this->progConfig->baseDir . '/scripts';
						$this->progConfig->localLogDir = $this->progConfig->baseDir . '/magxlsxproc/logs';

						$this->progConfig->imageBaseDir = $this->progConfig->baseDir . '/magxlsxproc/in';
						$this->progConfig->mainImagesDir = 'images';
						$this->progConfig->imageMagRelDir = '/import/magxlsxproc/in/images';

						$this->progConfig->imgProdAttrToFilter = 'sku';
						
						$this->progConfig->errorMailSubject = 'Dar M2 Processor Error (LIV)';
						$this->progConfig->errorMailFrom = array('info@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->errorMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->warnMailSubject = 'Dar M2 Processor Warning (LIV)';
						$this->progConfig->warnMailFrom = array('info@darlighting.co.uk' => 'DAR Lighting');
						$this->progConfig->warnMailTo = array('john@star-digital.co.uk' => 'John Little');
						
						$this->progConfig->lockDir = $this->progConfig->baseDir . '/magxlsxproc/logs';

						//$this->progConfig->excelDir = $this->progConfig->baseDir . '/excel_sheets';
						$this->progConfig->excelDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets';

						$this->progConfig->localXlsxInboxDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets';
						$this->progConfig->localXlsxInboxProcDir = $this->progConfig->baseDir . '/magxlsxproc/in/spreadsheets/processed';

						$this->progConfig->localXlsxOutboxDir = $this->progConfig->baseDir . '/magxlsxproc/out/spreadsheets';
						$this->progConfig->localXlsxOutboxProcDir = $this->progConfig->baseDir . '/magxlsxproc/out/spreadsheets/processed';
						
						$this->progConfig->gotTopLevelCat = false;

						$this->progConfig->excelReferenceDir = $this->progConfig->baseDir . '/magxlsxproc/excel_sheets';
						break;
		}

	}
}

?>
