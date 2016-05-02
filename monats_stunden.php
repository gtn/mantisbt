<?php

require_once( 'core.php' );
require_api( 'access_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'html_api.php' );
require_api( 'lang_api.php' );

if( !config_get( 'time_tracking_enabled' ) ) {
	trigger_error( ERROR_ACCESS_DENIED, ERROR );
}

access_ensure_project_level( config_get( 'time_tracking_reporting_threshold' ) );

define( 'BILLING_CSV_EXPORT', true );

# Work break-down
define( 'BILLING_INC_ALLOW', true );
include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'billing_inc.php' );


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
$plus_minus_jahr = (72.5 + 14.75) * 60;

html_page_top( lang_get( 'time_tracking_billing_link' ) );

$output = '';

for ($month = 1; $month <= date('m'); $month++) {
	$year = 2016;
	$days_in_month = date('d', mktime(0, 0, 0, $month+1, 0, $year));

	// vor mai 38 wstd, nach mai normalanstellung 38.5
	$wochenstunden = $year == 2016 && $month < 5 ? 38 : 38.5;
	$wochenstunden = $wochenstunden * 60;
	$urlaubsstunden_pro_monat = $wochenstunden*5/12;
	$workday_num = 0;

	$stunden_gearbeitet_total = 0;

	ob_start();
	echo '<h2>'.date('M', mktime(0, 0, 0, $month+1, 0, $year)).'</h2>';
	echo '<table class="width100">';
	echo '<tr class="row-1">';
	echo '<th>'.'Tag';
	echo '<th>'.'Datum';
	echo '<th>'.'Feiertag';
	echo '<th>'.'Gearbeitet';
	echo '<th>'.'Soll';
	echo '<th>'.'Diff';

	for ($day = 1; $day <= $days_in_month; $day++) {
		$timestamp = mktime(0, 0, 0, $month, $day, $year);
		$timestamp_tomorrow = mktime(0, 0, 0, $month, $day+1, $year);
		$date = date('Y-m-d', $timestamp); // sprintf('%04d-%02d-%02d', $year, $month, $day);
		$weekday = date('N', $timestamp);
		$feiertag = is_feiertag($date);
		$is_workday = $weekday <= 5 && !$feiertag;
		$is_today = $date == date('Y-m-d');
		$is_future = $timestamp > time();
		$workday_num += $is_workday ? 1 : 0;

		if (!$is_future) {
			$stunden_soll = $workday_num/5*$wochenstunden;

			$query = "
				SELECT SUM(bn.time_tracking)
				FROM {bugnote} bn
				WHERE
					bn.reporter_id = ".auth_get_current_user_id()."
					AND bn.time_tracking > 0
					AND bn.date_worked >= " . $timestamp ." AND bn.date_worked < ".$timestamp_tomorrow;
			$stunden_gearbeitet_today = reset(db_fetch_array(db_query( $query)));

			$stunden_gearbeitet_total += $stunden_gearbeitet_today;
		}

		echo '<tr '.($is_today?'class="row-1"':'').'>';
		echo '<td>'.($is_workday ? $workday_num : '');
		echo '<td>'.date('Y-m-d, D', $timestamp);
		echo '<td>'.$feiertag;
		echo '<td class="right">'.($is_future || (!$is_workday && !$stunden_gearbeitet_today) ? '' : exa_db_minutes_to_hhmm($stunden_gearbeitet_today));
		echo '<td class="right">'.($is_future || (!$is_workday && !$stunden_gearbeitet_today) ? '' : exa_db_minutes_to_hhmm($stunden_soll));
		echo '<td class="right">'.($is_future || (!$is_workday && !$stunden_gearbeitet_today) ? '' : exa_db_minutes_to_hhmm($stunden_gearbeitet_total-$stunden_soll));
	}

	$stunden_gearbeitet_total = round($stunden_gearbeitet_total * 4 / 60) / 4 * 60;
	$stunden_soll_exkl_urlaub = round(($stunden_soll-$urlaubsstunden_pro_monat) * 4 / 60) / 4 * 60;
	$letztes_monat = $plus_minus_jahr;
	$plus_minus_jahr += $stunden_gearbeitet_total - $stunden_soll_exkl_urlaub;

	echo '<tr class="row-2">';
	echo '<td>';
	echo '<td>';
	echo '<td>'.'total';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_gearbeitet_total);
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_soll);
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_gearbeitet_total-$stunden_soll);
	echo '</table>';

	echo '<br />';

	echo '<table class="width50">';

	echo '<tr class="row-2">';
	echo '<td>'.'Letztes Monat';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($letztes_monat);

	echo '<tr class="row-2">';
	echo '<td>'.'Urlaub';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($urlaubsstunden_pro_monat);

	echo '<tr class="row-2">';
	echo '<td>'.'Plusstunden';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($stunden_gearbeitet_total-$stunden_soll);

	echo '<tr class="row-1">';
	echo '<td>'.'total';
	echo '<td class="right">'.exa_db_minutes_to_hhmm($plus_minus_jahr);

	echo '</table>';

	$output = ob_get_clean().$output;
}

echo '<div id="monats-stunden">';
echo $output;
echo '</div>';

html_page_bottom();
