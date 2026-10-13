<?php
class excelLib {
	private $sheetNames = array();
	private $spreadSheet;
	private $sheet;
	private $sheetInfo;

	//###########################	
	public function __construct() {
	}

	//###########################
	public function loadExcelSpreadSheet($inputFileName) {
		$rc = true;
		echo 'filename = ' . $inputFileName . "\n";
		//  Read your Excel workbook
		try {
		    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
		    $excelReader = PHPExcel_IOFactory::createReader($inputFileType);
		    $this->spreadSheet = $excelReader->load($inputFileName);
		    $this->sheetNames = $this->spreadSheet->getSheetNames();
		} catch(Exception $e) {
		    generalLib::error('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
		    $rc = false;
		}

		return $rc;
	}


	//###########################
	public function getExcelSheetNames() {
		return $this->sheetNames;
	}


	//###########################
	public function getExcelSheet($sheetName, $highestColumn = null) {
		$rc = true;

		$this->sheetInfo = new stdClass();

		//print_r($this->sheetNames);

		//echo 'sheet name = ' . $sheetName . "\n";
		

		if (  ( $this->sheetInfo->sheetNum = array_search($sheetName, $this->sheetNames) ) !== false ) {
			//$excelSpreadSheet->setActiveSheetIndex( $sheetNum );
			$this->sheet = $this->spreadSheet->getSheet( $this->sheetInfo->sheetNum );

			$this->sheetInfo->highestRow = $this->sheet->getHighestRow();

			//echo 'highestRow = ' . $highestRow . "\n";

			if (is_null($highestColumn)) {
				//GET HIGHEST COLUMN NUMBER FOR ROW 1
				$this->sheetInfo->highestColumn = $this->sheet->getHighestColumn('1');
			}
	
		 } else {
			generalLib::error('Sheet \'' . $sheetName . '\' not found');
			$rc = false;
		}

		//print_r($this->sheetInfo);

		$this->getHighestNonEmptyColumnForFirstRow();

		$this->getHighestNonEmptyRow();

		//print_r($this->sheetInfo);

		return $rc;
	}


	//###########################
	public function getHighestNonEmptyColumnForFirstRow() {

		if ( $this->sheetInfo->highestRow >= 1) {

			$rowData = $this->readExcelSheetRow(1);

			//print_r($rowData);
			$lastNonEmptyColumn = 0;

			foreach($rowData as $colNum => $colData) {
				
				if (!empty( $colData ) ) {

					$lastNonEmptyColumn = $colNum;

				}
			}

			$this->sheetInfo->highestColumn = PHPExcel_Cell::stringFromColumnIndex( $lastNonEmptyColumn );
		}
	}


	//###########################
	public function getHighestNonEmptyRow() {

		for( $rowNum = $this->sheetInfo->highestRow;  $rowNum >= 1; $rowNum--) {

			$rowData = $this->readExcelSheetRow( $rowNum );

			$gotColData = false;

			foreach($rowData as $colNum => $colData) {

				if ( !empty( $colData ) ) {

					$gotColData = true;
					break;

				}

			}

			if ( $gotColData ) {
				
				$this->sheetInfo->highestRow = $rowNum;
				break;

			}
		}

	}

	//###########################
	public function gotExcelRows($row) {

		if ($row <= $this->sheetInfo->highestRow) {
			return true;
		} else {
			return false;
		}

	}


	//###########################
	public function gotExcelColumns($col) {

		if ($col <= $this->sheetInfo->highestColumn) {
			return true;
		} else {
			return false;
		}

	}


	//###########################
	public function readExcelSheetRow($row) {
		$rowData = array();

		try {
	    	$rowData = $this->sheet->rangeToArray('A' . $row . ':' . $this->sheetInfo->highestColumn . $row,
	                                    NULL,
	                                    TRUE,
	                                    FALSE);

	    	//print_r($rowData);
		}

		catch(Exception $e) {
			generalLib::error($e->getMessage());
			generalLib::error('Unable to load row ' . $row . ' in  \'' . $this->sheetNames[ $this->sheetInfo->sheetNum ] . '\' sheet');
			$rowData = false;
		}

		return $rowData[0];
	}


	//###########################
	public function createExcelSpreadSheet() {
		$this->spreadSheet = new PHPExcel();

		$this->spreadSheet->getDefaultStyle()->getAlignment()->setWrapText(true);
	}


	//###########################
	public function createExcelSheet($idx, $sheetName) {

		if ($idx > 0) {
			$this->spreadSheet->createSheet();
		}

		$this->spreadSheet->setActiveSheetIndex($idx);
		$this->spreadSheet->getActiveSheet()->setTitle($sheetName);
		$this->sheet = $this->spreadSheet->getSheet( $idx );
	}


	//###########################
	public function updExcelCell($cellRef, $rowNum, $updValue) {
		$this->sheet->setCellValue($cellRef . $rowNum, $updValue);
	}
	

	//###########################
	public function autoSizeSheets() {
		// Auto size columns for each worksheet
		foreach ($this->spreadSheet->getWorksheetIterator() as $worksheet) {

			$this->spreadSheet->setActiveSheetIndex( $this->spreadSheet->getIndex($worksheet) );

			$sheet = $this->spreadSheet->getActiveSheet();
			$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(true);
		    /** @var PHPExcel_Cell $cell */
			foreach ($cellIterator as $cell) {
				$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			}
		}
	}


	//###########################
	public function saveSpreadSheet($outputFilename, $outputFileType) {
		$objWriter = PHPExcel_IOFactory::createWriter($this->spreadSheet, $outputFileType);
		$objWriter->save($outputFilename);
	}

}
