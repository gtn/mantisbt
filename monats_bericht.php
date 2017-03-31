<?php

require_once('core.php');
require_api('access_api.php');
require_api('config_api.php');
require_api('constant_inc.php');
require_api('html_api.php');
require_api('lang_api.php');

if (!config_get('time_tracking_enabled')) {
	trigger_error(ERROR_ACCESS_DENIED, ERROR);
}

access_ensure_project_level(config_get('time_tracking_reporting_threshold'));

define('BILLING_CSV_EXPORT', true);

# Work break-down
define('BILLING_INC_ALLOW', true);
include(dirname(__FILE__).DIRECTORY_SEPARATOR.'billing_inc.php');

$user_id = gpc_get_int('user_id', 0);

$wochenstunden_pro_user = [
	7 => 38.5, // Daniel Prieler
	5 => 38.5, // Dominik Brandtner
	2 => 7, // Florian Jungwirth
	3 => 7, // Michaela Murauer
	19 => 20, // Doris Szewieczek
];
/*
1 // Andreas Riepl
6 // Dietmar Angerer
8 // Martin Kattner
21 // Philipp Rezanka
9 // Sergey Zavarzin
*/

$feiertage = [
	'2016-01-01' => 'Neujahr',
	'2016-01-06' => 'Heilige Drei Könige',
	'2016-03-28' => 'Ostermontag',
	'2016-05-01' => 'Staatsfeiertag',
	'2016-05-01' => 'Staatsfeiertag',
	'2016-05-05' => 'Feiertag',
	'2016-05-16' => 'Feiertag',
	'2016-05-26' => 'Feiertag',
	'2016-08-15' => 'Feiertag',
	'2016-10-26' => 'Feiertag',
	'2016-11-01' => 'Feiertag',
	'2016-12-08' => 'Feiertag',
	'2016-12-26' => 'Feiertag',

	'2017-01-01' => 'Feiertag',
	'2017-01-06' => 'Feiertag',
	'2016-04-17' => 'Ostermontag',
	'2017-05-01' => 'Staatsfeiertag',
	'2017-05-25' => 'Feiertag',
	'2017-06-05' => 'Feiertag',
	'2017-06-15' => 'Feiertag',
	'2017-08-15' => 'Feiertag',
	'2017-10-26' => 'Feiertag',
	'2017-11-01' => 'Feiertag',
	'2017-12-08' => 'Feiertag',
	'2017-12-25' => 'Feiertag',
	'2017-12-26' => 'Feiertag',
];

function is_feiertag($date) {
	global $feiertage;

	return isset($feiertage[$date]) ? $feiertage[$date] : null;
}

function exa_db_minutes_to_hhmm($minutes) {
	if ($minutes < 0) {
		return '-'.db_minutes_to_hhmm(-$minutes);
	} else {
		return db_minutes_to_hhmm($minutes);
	}
}

// plusstunden jahr 2015
$plus_minus_jahr =
	$user_id == 7
		? (72.5 + 14.75) * 60
		: 0;

html_page_top(lang_get('time_tracking_billing_link'));

$query = "
	SELECT u.id, realname
	FROM {user} u
	WHERE u.id IN (".join(',', array_keys($wochenstunden_pro_user)).")
	ORDER BY realname
";

$result = db_query_bound($query, $t_params);
while ($row = db_fetch_array($result)) {
	?>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?user_id='.$row['id']; ?>"><?php echo ($user_id == $row['id'] ? '<b>' : '').$row['realname']; ?></b></a>&nbsp;
	<?php
}


if (!$user_id) {
	html_page_bottom();
	exit;
}


$output = '';

for ($month = 1; $month <= date('m'); $month++) {
	$year = 2016;
	$days_in_month = date('d', mktime(0, 0, 0, $month + 1, 0, $year));

	$wochenstunden = $wochenstunden_pro_user[$user_id];
	$wochenstunden = $wochenstunden * 60;
	$stunden_soll_tag = $wochenstunden/5;
	$workday_num = 0;
	$workday_num_not_future = 0;

	$days = [];

	for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
		$day = $days[$day_num] = (object)[];

		$day->timestamp = mktime(0, 0, 0, $month, $day_num, $year);
		$timestamp_tomorrow = mktime(0, 0, 0, $month, $day_num + 1, $year);

		$day->date = date('Y-m-d', $day->timestamp);
		$day->is_feiertag = is_feiertag($day->date);
		$day->weekday = date('N', $day->timestamp);
		$day->is_workday = $day->weekday <= 5 && !$day->is_feiertag;
		$day->is_today = $day->date == date('Y-m-d');
		$day->is_future = $day->timestamp > time();

		if (!$day->is_future) {

			$query = "
				SELECT SUM(bn.time_tracking) AS stunden_gearbeitet
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".$user_id."
					AND bn.time_tracking > 0
					AND bn.date_worked >= ".$day->timestamp." AND bn.date_worked < ".$timestamp_tomorrow."
			";
			$day->mins_aufgeschrieben = reset(db_fetch_array(db_query($query)));

			$query = "
				SELECT SUM(bn.time_tracking) AS stunden_gearbeitet
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".$user_id."
					AND bn.time_tracking > 0
					AND bn.date_worked >= ".$day->timestamp." AND bn.date_worked < ".$timestamp_tomorrow."
					AND bn.bug_id = 841
			";
			$day->mins_krank = reset(db_fetch_array(db_query($query)));

			$query = "
				SELECT SUM(bn.time_tracking) AS stunden_gearbeitet
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".$user_id."
					AND bn.time_tracking > 0
					AND bn.date_worked >= ".$day->timestamp." AND bn.date_worked < ".$timestamp_tomorrow."
					AND bn.bug_id = 1318
			";
			$day->mins_urlaub = reset(db_fetch_array(db_query($query)));

			$query = "
				SELECT MIN(bn.date_worked-(bn.time_tracking*60)) AS min_time
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".$user_id."
					AND bn.time_tracking > 0
					AND bn.date_worked-(bn.time_tracking*60) >= ".($day->timestamp + 60*60*7)." AND bn.date_worked < ".$timestamp_tomorrow."
			";
			//
			// + 60*60*6
			$day->start_time = reset(db_fetch_array(db_query($query)));

			/*
			$query = "
				SELECT MIN(DATE_FORMAT(FROM_UNIXTIME(bn.date_worked-bn.time_tracking*60),'%H'))
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".$user_id."
					AND bn.time_tracking > 0
					AND bn.date_worked >= ".$day->timestamp." AND bn.date_worked < ".$timestamp_tomorrow."
					AND DATE_FORMAT(FROM_UNIXTIME(bn.date_worked-bn.time_tracking*60),'%H') > 6
		  	";
			$day->start_time = reset(db_fetch_array(db_query($query)));
			var_dump($day->start_time);
			*/
		}
	}

	for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
		$day = $days[$day_num];

		if ($day->is_future) {
			break;
		}

		$mins_zum_aufteilen = 0;

		if (!$day->is_workday && $day->mins_aufgeschrieben) {
			// wochenende / feiertage, wo anders dazuzählen
			$mins_zum_aufteilen += $day->mins_aufgeschrieben;
			$day->mins_aufgeschrieben = 0;
		}

		if ($day->mins_urlaub || $day->mins_krank) {
			$mins_zum_aufteilen += $day->mins_aufgeschrieben - $stunden_soll_tag;
			$day->mins_aufgeschrieben = $stunden_soll_tag;
		}

		$max_mins_per_day = 9.5 * 60;
		if ($day->mins_aufgeschrieben > $max_mins_per_day) {
			// mehr als 11 stunden
			$mins_zum_aufteilen += $day->mins_aufgeschrieben - $max_mins_per_day;
			$day->mins_aufgeschrieben = $max_mins_per_day;
		}

		while ($mins_zum_aufteilen > 0) {
			// get next day
			$found_day = null;

			for ($day_num2 = 2; $day_num2 <= $days_in_month; $day_num2++) {
				$day = $days[($day_num-1+$day_num2)%$days_in_month+1];

				if ($day->is_workday && $day->mins_aufgeschrieben < 9 * 60 && !$day->is_future && !$day->mins_krank && !$day->mins_urlaub) {
					$found_day = $day;
					break;
				}
			}

			if (!$found_day) {
				echo("<h2>no days found, month: $month, still open: $mins_zum_aufteilen</h2>");
				break;
			}

			// $day = $days_allowed[array_rand($days_allowed, 1)];

			$mins_aufteilen_now = min($mins_zum_aufteilen, 2 * 60, 9.5 * 60 - $found_day->mins_aufgeschrieben);

			$found_day->mins_aufgeschrieben += $mins_aufteilen_now;
			$mins_zum_aufteilen -= $mins_aufteilen_now;
		}
	}


	ob_start();
	echo '<h2>'.date('M', mktime(0, 0, 0, $month + 1, 0, $year)).'</h2>';
	echo '<table class="width100">';
	echo '<tr class="row-1">';
	echo '<th>'.'Tag';
	echo '<th>'.'Datum';
	echo '<th>'.'Feiertag';
	echo '<th>'.'Arbeitszeit';
	echo '<th>'.'Gearbeitet';
	//echo '<th>'.'Soll';
	//echo '<th>'.'Diff';

	$stunden_gearbeitet_total = 0;
	$stunden_aufgeschrieben_total = 0;
	$stunden_urlaub_total = 0;

	for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
		$day = $days[$day_num];

		$stunden_gearbeitet_total += $day->mins_aufgeschrieben - $day->mins_urlaub;
		$stunden_aufgeschrieben_total += $day->mins_aufgeschrieben;
		$stunden_urlaub_total += $day->mins_urlaub;

		if ($day->mins_krank || $day->mins_urlaub) {
			$start = 8 * 60;
		} else {
			if ($day->start_time) {
				$start = date('H', $day->start_time)*60+date('i', $day->start_time);
				$start = min($start, 19*60 - $day->mins_aufgeschrieben);
			} else {
				$start = rand(7 * 60 + 45, 9 * 60 + 40);
			}
			$start = round($start / 15) * 15;
		}


		if (!$day->mins_aufgeschrieben) {
			$worktime = '';
		} elseif ($day->mins_aufgeschrieben == $day->mins_krank) {
			$worktime = 'krank';
		} elseif ($day->mins_aufgeschrieben == $day->mins_urlaub) {
			$worktime = 'urlaub';
		} elseif ($day->mins_aufgeschrieben > 5 * 60 + 30) {
			$break_start = round(rand(11 * 60 + 30, 13 * 60 + 5) / 15) * 15;
			if ($day->mins_aufgeschrieben >= 9 * 60) {
				// bei 9h immer nur 30 min pause
				$break_end = $break_start + 30;
			} else {
				$break_end = $break_start + round(rand(25, 75) / 15) * 15;
			}
			$xia_ban = $start + $day->mins_aufgeschrieben + ($break_end - $break_start);

			$worktime = exa_db_minutes_to_hhmm($start).'-'.exa_db_minutes_to_hhmm($break_start)
				.' '.exa_db_minutes_to_hhmm($break_end).'-'.exa_db_minutes_to_hhmm($xia_ban);
		} else {
			$worktime = exa_db_minutes_to_hhmm($start).'-'.exa_db_minutes_to_hhmm($start + $day->mins_aufgeschrieben);
		}
		// (!$day->mins_aufgeschrieben ? '' : exa_db_minutes_to_hhmm($day->mins_aufgeschrieben))

		$workday_num += $day->is_workday ? 1 : 0;
		$workday_num_not_future += $day->is_workday && $day->timestamp < time() ? 1 : 0;

		echo '<tr '.($day->is_today ? 'class="row-1"' : '').'>';
		echo '<td>'.($day->is_workday ? $workday_num : '');
		echo '<td>'.date('Y-m-d, D', $day->timestamp);
		echo '<td>'.$day->is_feiertag;
		echo '<td>'.$worktime;
		echo '<td class="right">'.(!$day->mins_aufgeschrieben ? '' : exa_db_minutes_to_hhmm($day->mins_aufgeschrieben));
		//echo '<td class="right">'.($day->is_future || (!$day->is_workday && !$day->mins_aufgeschrieben) ? '' : exa_db_minutes_to_hhmm($day->stunden_soll));
		//echo '<td class="right">'.($day->is_future || (!$day->is_workday && !$day->mins_aufgeschrieben) ? '' : exa_db_minutes_to_hhmm($stunden_aufgeschrieben_total - $stunden_soll));
	}

	$stunden_soll = $stunden_soll_tag * $workday_num_not_future;
	// $stunden_aufgeschrieben_total = round($stunden_aufgeschrieben_total * 4 / 60) / 4 * 60;
	$letztes_monat = $plus_minus_jahr;
	$plus_minus_jahr += $stunden_aufgeschrieben_total - $stunden_soll;

	$plus_minus_jahr = round($plus_minus_jahr * 4 / 60) / 4 * 60;

	echo '<tr class="row-2">';
	echo '<td>';
	echo '<td>';
	echo '<td>';
	echo '<td>'.'total';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_aufgeschrieben_total);
	//echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_soll);
	//echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_aufgeschrieben_total - $stunden_soll);
	echo '</table>';

	echo '<br />';

	echo '<table class="width50">';

	echo '<tr class="row-2">';
	echo '<td>'.'Letztes Monat';
	echo '<td>';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($letztes_monat);

	echo '<tr class="row-2">';
	echo '<td>'.'Urlaub';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_urlaub_total);
	echo '<td>';

	echo '<tr class="row-2">';
	echo '<td>'.'Gearbeitet';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_gearbeitet_total);
	echo '<td>';

	echo '<tr class="row-2">';
	echo '<td>'.'Soll';
	echo '<td class="right">'.exa_db_minutes_to_hhmm(-$stunden_soll);
	echo '<td>';

	echo '<tr class="row-2">';
	echo '<td>'.'Plusstunden';
	echo '<td>';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_aufgeschrieben_total-$stunden_soll);

	echo '<tr class="row-1">';
	echo '<td>'.'total';
	echo '<td>';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($plus_minus_jahr);

	echo '</table>';
	$output = ob_get_clean().$output;
}

echo '<div id="monats-stunden">';
echo $output;
echo '</div>';

html_page_bottom();
