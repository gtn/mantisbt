<?php

// always update date_worked
db_query('UPDATE {bugnote} SET date_worked=date_submitted WHERE date_worked=0');

function gtn_get_user_wochenstunden($userid, $currentTime) {
	$month = date('m', $currentTime);
	$year = date('Y', $currentTime);

	if ($userid == 35) {
		// hannah
		if ($year == 2016 && $month == 12) {
			return 8; // erstes gehalt 600 euro brutto sind gerundet 8 wochenstunden im dezember
		}
		if ($year <= 2016) {
			return 0;
		}
		if ($year == 2017 && $month <= 2) {
			return 20;
		}

		return 38.5;
	}

	$wochenstunden_pro_user = [
		7 => 38.5, // Daniel Prieler
		5 => 38.5, // Dominik Brandtner
		2 => 7, // Florian Jungwirth
		3 => 7, // Michaela Murauer
		19 => 20, // Doris Szewieczek
	];

	return $wochenstunden_pro_user[$userid];

	/*
	1 // Andreas Riepl
	6 // Dietmar Angerer
	8 // Martin Kattner
	21 // Philipp Rezanka
	9 // Sergey Zavarzin
	*/
}

function gtn_get_user_start_date($userid) {
	if ($userid == 35) {
		return mktime(0,0,0,11,1,2016);
	} else {
		return mktime(0,0,0,1,1,2016);
	}
}

function gtn_get_user_ueberstunden_start($userid) {
	if ($userid == 7) {
		return (72.5 + 14.75) * 60;
	} else {
		return 0;
	}
}
