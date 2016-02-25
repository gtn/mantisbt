<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display Mantis Billing Page
 *
 * @package MantisBT
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses access_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses html_api.php
 * @uses lang_api.php
 */

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




// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

$result = get_gtn_time_tracking();

fputcsv($output, [
	'mitarbeiter',
	'datum',
	'stunden',
	'projekt',
	'kategorie',
	'bugid',
	'bugname',
	'kommentar',
], ';');
while ($row = db_fetch_array( $result )) {
	// output the column headings
	fputcsv($output, [
		$row["realname"],
		date('Y-m-d H:i', $row["date_worked"]),
		round($row["time_tracking"]/60, 3), // str_replace('.', ',', $row["time_tracking"]/60),
		$row["project_name"],
		$row["category_name"],
		'#'.bug_format_id($row["bug_id"]),
		$row["summary"],
		$row["note"],
	], ';');
}