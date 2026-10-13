<?php

class generalDateFuncs {
	
	//###########################
	//DATE MUST BE BASED ON DD-MM-YYYY FORMAT
	static function checkADate($aDate, $showError = true) {
		$rc = true;
		//echo 'aDate = ' . $aDate . "\n";

		$tempDate = explode('-', $aDate);

		//print_r($tempDate);

		if (count($tempDate) != 3) {

			if ($showError) {
				generallib::warn('Date \'' . $aDate . '\' must be in DD-MM-YYYY format');
			}

			$rc = false;
		}

		if ($rc) {

			if (!checkdate($tempDate[1], $tempDate[0], $tempDate[2])) {//checkdate(month, day, year)

				if ($showError) {
					generallib::warn('Invalid date \'' . $aDate . '\'');
				}

				$rc = false;
			}

		}

		return $rc;
	}


	//###########################
	static function checkDateRange($date1, $date2, $showError = true) {
		$rc = true;

		//echo 'date1 = ' . $date1 . "\n";
		//echo 'date2 = ' . $date2 . "\n";

		$rc = self::checkADate($date1);

		if ($rc) {
			$rc = self::checkADate($date2);
		}

		if ($rc) {
			if (strtotime($date1) > strtotime($date2)) {

				if ($showError) {
					generalLib::warn('Invalid date range - \'' . $date1 . '\' is greater than \'' . $date2 . '\'');
				}

				$rc = false;
			}
		}

		return $rc;
	}


	//###########################
	static function checkATime($aTime) {
		$rc = true;

		if (strpos($aTime, ':') === false) {
        	$aTime .= ':00';
		}

		if (!strtotime($aTime)) {
			$rc = false;
		}

		return $rc;
	}

}

?>
