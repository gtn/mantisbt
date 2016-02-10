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
 * This include file prints out the bug bugnote_stats
 * $f_bug_id must already be defined
 *
 * @package MantisBT
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses bugnote_api.php
 * @uses collapse_api.php
 * @uses config_api.php
 * @uses database_api.php
 * @uses filter_api.php
 * @uses gpc_api.php
 * @uses helper_api.php
 * @uses lang_api.php
 * @uses string_api.php
 * @uses utility_api.php
 */

if( !defined( 'BILLING_INC_ALLOW' ) ) {
	return;
}

require_api( 'bugnote_api.php' );
require_api( 'collapse_api.php' );
require_api( 'config_api.php' );
require_api( 'database_api.php' );
require_api( 'filter_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'lang_api.php' );
require_api( 'string_api.php' );
require_api( 'utility_api.php' );

$t_today = date( 'd:m:Y' );
$t_first_day_of_month = date( '1:m:Y' );
$t_date_submitted = isset( $t_bug ) ? date( 'd:m:Y', $t_bug->date_submitted ) : $t_first_day_of_month;

$t_bugnote_stats_from_def = $t_date_submitted;
$t_bugnote_stats_from_def_ar = explode( ':', $t_bugnote_stats_from_def );
$t_bugnote_stats_from_def_d = $t_bugnote_stats_from_def_ar[0];
$t_bugnote_stats_from_def_m = $t_bugnote_stats_from_def_ar[1];
$t_bugnote_stats_from_def_y = $t_bugnote_stats_from_def_ar[2];

$t_bugnote_stats_from_d = gpc_get_int( 'start_day', $t_bugnote_stats_from_def_d );
$t_bugnote_stats_from_m = gpc_get_int( 'start_month', $t_bugnote_stats_from_def_m );
$t_bugnote_stats_from_y = gpc_get_int( 'start_year', $t_bugnote_stats_from_def_y );

$t_bugnote_stats_to_def = $t_today;
$t_bugnote_stats_to_def_ar = explode( ':', $t_bugnote_stats_to_def );
$t_bugnote_stats_to_def_d = $t_bugnote_stats_to_def_ar[0];
$t_bugnote_stats_to_def_m = $t_bugnote_stats_to_def_ar[1];
$t_bugnote_stats_to_def_y = $t_bugnote_stats_to_def_ar[2];

$t_bugnote_stats_to_d = gpc_get_int( 'end_day', $t_bugnote_stats_to_def_d );
$t_bugnote_stats_to_m = gpc_get_int( 'end_month', $t_bugnote_stats_to_def_m );
$t_bugnote_stats_to_y = gpc_get_int( 'end_year', $t_bugnote_stats_to_def_y );

$user_id = current_user_is_administrator() ? gpc_get_int('user_id', auth_get_current_user_id()) : auth_get_current_user_id();

// $sorting = gpc_get_string('sorting', 'bug');

$f_get_bugnote_stats_button = gpc_get_string( 'get_bugnote_stats_button', '' );

# Retrieve the cost as a string and convert to floating point
$f_bugnote_cost = floatval( gpc_get_string( 'bugnote_cost', '' ) );

$f_project_id = gpc_get_string('project_id', '');

if ($f_project_id === '') {
	$f_project_id = helper_get_current_project();
	$f_project_trace = join( ';', helper_get_current_project_trace() );
} else {
	$f_project_trace = $f_project_id;
	$f_project_id = explode(';', $f_project_id);
	$f_project_id = (int)end($f_project_id);
}

if( ON == config_get( 'time_tracking_with_billing' ) ) {
	$t_cost_col = true;
} else {
	$t_cost_col = false;
}

if (defined( 'BILLING_CSV_EXPORT' ) ) {
	return;
}

?>
<a id="bugnotestats"></a><br />
<?php
collapse_open( 'bugnotestats' );

# Time tracking date range input form
# CSRF protection not required here - form does not result in modifications
?>

<form method="post" action="" id="time_tracking">
	<input type="hidden" name="id" value="<?php echo isset( $f_bug_id ) ? $f_bug_id : 0 ?>" />
	<table class="width100" cellspacing="0">
		<tr>
			<td class="form-title" colspan="4"><?php
				collapse_icon( 'bugnotestats' );
				echo lang_get( 'time_tracking' ); ?>
			</td>
		</tr>
		<tr class="row-2">
			<td class="category" width="25%">
				<?php
					$g_filter = array();
					$g_filter[FILTER_PROPERTY_FILTER_BY_DATE] = 'on';
					$g_filter[FILTER_PROPERTY_START_DAY] = $t_bugnote_stats_from_d;
					$g_filter[FILTER_PROPERTY_START_MONTH] = $t_bugnote_stats_from_m;
					$g_filter[FILTER_PROPERTY_START_YEAR] = $t_bugnote_stats_from_y;
					$g_filter[FILTER_PROPERTY_END_DAY] = $t_bugnote_stats_to_d;
					$g_filter[FILTER_PROPERTY_END_MONTH] = $t_bugnote_stats_to_m;
					$g_filter[FILTER_PROPERTY_END_YEAR] = $t_bugnote_stats_to_y;

					ob_start();
					print_filter_do_filter_by_date( true );
					echo str_replace('</table>', '', ob_get_clean());
				?>
			</td>
		</tr>
		<?php if (current_user_is_administrator()) { ?>
		<tr>
			<td>User:</td><td>
			<select name="user_id">
			<?php
				/*
				echo '<option value="' . META_FILTER_MYSELF . '" ';
				check_selected( $user_id, META_FILTER_MYSELF );
				echo '>[' . lang_get( 'myself' ) . ']</option>';
				*/
			?>
			<option value="<?php echo META_FILTER_ANY?>"<?php check_selected( $user_id, META_FILTER_ANY );?>>[<?php echo lang_get( 'all_users' )?>]</option>
			<?php
				print_assign_to_option_list( $user_id, 0); // $f_project_id );
			?>
			</select>
			</td>
		</tr>
		<tr>
			<td>Project:</td><td>
				<select name="project_id">
				<?php print_project_option_list( $f_project_trace, true, null, true ); ?>
				</select>
			</td>
		</tr>

		<!-- tr class="row-2">
			<td class="category">
				Sortierung:
				<label><input type="radio" name="sorting" value="bug" <?php if ($sorting=='bug') echo 'checked="checked"'; ?> /> Issue</label>
				<label><input type="radio" name="sorting" value="date" <?php if ($sorting=='date') echo 'checked="checked"'; ?> /> Datum</label>
			</td>
		</tr -->
		<?php } ?>
<?php
	if( $t_cost_col ) {
?>
		<tr class="row-1">
			<td>
				<?php echo lang_get( 'time_tracking_cost_per_hour_label' ) ?>
				<input type="text" name="bugnote_cost" value="<?php echo $f_bugnote_cost ?>" />
			</td>
		</tr>
<?php
	}
?>
		</table></td></tr>
		<tr>
			<td class="" colspan="2">
				<input type="submit" class="button"
					name="get_bugnote_stats_button"
					value="<?php echo lang_get( 'time_tracking_get_info_button' ) ?>"
				/>
				<input type="button" class="button" id="csv_export_button"
					value="CSV Export"
				/>
			</td>
		</tr>
	</table>
</form>

<?php

	if( true ) { // || !is_blank( $f_get_bugnote_stats_button ) ) {
		# Retrieve time tracking information
		$t_from = $t_bugnote_stats_from_y . '-' . $t_bugnote_stats_from_m . '-' . $t_bugnote_stats_from_d;
		$t_to = $t_bugnote_stats_to_y . '-' . $t_bugnote_stats_to_m . '-' . $t_bugnote_stats_to_d;
		$t_bugnote_stats = bugnote_stats_get_project_array( $f_project_id, $t_from, $t_to, $f_bugnote_cost, $user_id );

		# Sort the array by bug_id, user/real name
		if( ON == config_get( 'show_realname' ) ) {
			$t_name_field = 'realname';
		} else {
			$t_name_field = 'username';
		}
		$t_sort_bug = $t_sort_name = array();
		foreach ( $t_bugnote_stats as $t_key => $t_item ) {
			$t_sort_bug[$t_key] = $t_item['bug_id'];
			$t_sort_name[$t_key] = $t_item[$t_name_field];
		}
		array_multisort( $t_sort_bug, SORT_NUMERIC, $t_sort_name, $t_bugnote_stats );
		unset( $t_sort_bug, $t_sort_name );

		if( is_blank( $f_bugnote_cost ) || ( (double)$f_bugnote_cost == 0 ) ) {
			$t_cost_col = false;
		}

		$t_prev_id = -1;

ob_start();
?>
<h3>By Issue</h3>
<table class="width100" cellspacing="0">
	<tr class="row-category2">
		<td class="small-caption bold">
			<?php echo lang_get( $t_name_field ) ?>
		</td>
		<td class="small-caption bold">
			<?php echo lang_get( 'time_tracking' ) ?>
		</td>
<?php	if( $t_cost_col ) { ?>
		<td class="small-caption bold right">
			<?php echo lang_get( 'time_tracking_cost' ) ?>
		</td>
<?php	} ?>

	</tr>
<?php
		$t_sum_in_minutes = 0;
		$t_user_summary = array();

		# Initialize the user summary array
		foreach ( $t_bugnote_stats as $t_item ) {
			$t_user_summary[$t_item[$t_name_field]] = 0;
		}

		# Calculate the totals
		foreach ( $t_bugnote_stats as $t_item ) {
			$t_sum_in_minutes += $t_item['sum_time_tracking'];
			$t_user_summary[$t_item[$t_name_field]] += $t_item['sum_time_tracking'];

			$t_item['sum_time_tracking'] = db_minutes_to_hhmm( $t_item['sum_time_tracking'] );
			if( $t_item['bug_id'] != $t_prev_id ) {
				$t_link = sprintf( lang_get( 'label' ), string_get_bug_view_link( $t_item['bug_id'] ) ) . lang_get( 'word_separator' ) . string_display( $t_item['summary'] );
				echo '<tr class="row-category-history"><td colspan="4">' . $t_link . '</td></tr>';
				$t_prev_id = $t_item['bug_id'];
			}
?>
	<tr>
		<td class="small-caption">
			<?php echo $t_item[$t_name_field] ?>
		</td>
		<td class="small-caption">
			<?php echo $t_item['sum_time_tracking'] ?>
		</td>
<?php		if( $t_cost_col ) { ?>
		<td class="small-caption right">
			<?php echo string_attribute( number_format( $t_item['cost'], 2 ) ); ?>
		</td>
<?php		} ?>
	</tr>

<?php	} # end for loop ?>

	<tr class="row-category2">
		<td class="small-caption bold">
			<?php echo lang_get( 'total_time' ); ?>
		</td>
		<td class="small-caption bold">
			<?php echo db_minutes_to_hhmm( $t_sum_in_minutes ); ?>
		</td>
<?php	if( $t_cost_col ) { ?>
		<td class="small-caption bold right">
			<?php echo string_attribute( number_format( $t_sum_in_minutes * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php 	} ?>
	</tr>
</table>
<?php
$by_issue = ob_get_clean();
?>

<h3>Totals</h3>
<table class="width100" cellspacing="0">
	<tr class="row-category2">
		<td class="small-caption bold">
			<?php echo lang_get( $t_name_field ) ?>
		</td>
		<td class="small-caption bold">
			<?php echo lang_get( 'time_tracking' ) ?>
		</td>
<?php	if( $t_cost_col ) { ?>
		<td class="small-caption bold right">
			<?php echo lang_get( 'time_tracking_cost' ) ?>
		</td>
<?php	} ?>
	</tr>

<?php
	foreach ( $t_user_summary as $t_username => $t_total_time ) {
?>
	<tr>
		<td class="small-caption">
			<?php echo $t_username; ?>
		</td>
		<td class="small-caption">
			<?php echo db_minutes_to_hhmm( $t_total_time ); ?>
		</td>
<?php		if( $t_cost_col ) { ?>
		<td class="small-caption right">
			<?php echo string_attribute( number_format( $t_total_time * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php		} ?>
	</tr>
<?php	} ?>
	<tr class="row-category2">
		<td class="small-caption bold">
			<?php echo lang_get( 'total_time' ); ?>
		</td>
		<td class="small-caption bold">
			<?php echo db_minutes_to_hhmm( $t_sum_in_minutes ); ?>
		</td>
<?php	if( $t_cost_col ) { ?>
		<td class="small-caption bold right">
			<?php echo string_attribute( number_format( $t_sum_in_minutes * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php	} ?>
	</tr>
</table>

<?php
	} # end if
?>


<?php
function get_gtn_time_tracking() {
	global $t_bugnote_stats_from_y, $t_bugnote_stats_from_m, $t_bugnote_stats_from_d,
		$t_bugnote_stats_to_y, $t_bugnote_stats_to_m, $t_bugnote_stats_to_d,
		$user_id, $f_project_id, $t_params;

	$p_from = $t_bugnote_stats_from_y . '-' . $t_bugnote_stats_from_m . '-' . $t_bugnote_stats_from_d;
	$p_to = $t_bugnote_stats_to_y . '-' . $t_bugnote_stats_to_m . '-' . $t_bugnote_stats_to_d;

	$c_to = strtotime( $p_to ) + SECONDS_PER_DAY - 1;
	$c_from = strtotime( $p_from );

	$query = "SELECT bn.*, realname, p.name AS project_name, bug.summary, bug_text.note, category.name AS category_name
		FROM {bugnote} bn
		JOIN {user} u ON u.id = bn.reporter_id
		LEFT JOIN {bug} bug ON bn.bug_id = bug.id
		LEFT JOIN {project} p ON p.id=bug.project_id
		LEFT JOIN {bugnote_text} bug_text ON bug_text.id = bn.bugnote_text_id
		LEFT JOIN {category} category ON category.id = bug.category_id
		WHERE bn.time_tracking > 0";
	if ($user_id) {
		$query .= ' AND bn.reporter_id = '.(int)$user_id;
	}
	if ($f_project_id) {
		$query .= ' AND p.id = '.(int)$f_project_id;
	}
	$query .= ' AND bn.date_submitted >= ' . $c_from.' AND bn.date_submitted <= ' . $c_to;
	$query .= ' ORDER BY bn.date_submitted DESC';

	return db_query_bound( $query, $t_params );
}

$result = get_gtn_time_tracking();

$values = [];

while ($row = db_fetch_array( $result )) {
	$values[date('Y-m', $row["date_submitted"])] += 	$row["time_tracking"];
	// $values[date('Y-m-d', $row["date_submitted"]).$row["date_submitted"]] = $row;
	$values[$row["id"]] = $row;
	// $values[date('Y-m-d', $row["date_submitted"])] += $row["time_tracking"];

	// var_dump($row);
}

// krsort($values);
?>
<h3>By Date</h3>
<table class="width100" cellspacing="0">
	<tr class="row-category2">
		<?php if (!$user_id) { ?>
		<td class="small-caption bold">
			<?php echo lang_get( 'username' ) ?>
		</td>
		<?php } ?>
		<td class="small-caption bold">
			<?php echo lang_get( 'timestamp' ) ?>
		</td>
		<td class="small-caption bold">
			<?php echo lang_get( 'time_tracking' ) ?>
		</td>
		<td class="small-caption bold">
			<?php echo lang_get( 'issues' ) ?>
		</td>
		<td class="small-caption bold">
			<?php echo lang_get( 'bug_notes_title' ) ?>
		</td>
		<td class="small-caption bold">
		</td>

	</tr>
<?php
foreach ($values as $date=>$row) {
	if (!is_array($row)) {
		echo '<tr class="row-category-history">';
		if (!$user_id) { echo '<td></td>'; }
		echo '<td>'.$date.'</td>';
		echo '<td align="right">'.db_minutes_to_hhmm($row).'</td>';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td></td>';
	} else {
		echo '<tr>';
		if (!$user_id) { echo '<td class="small-caption">'.$row["realname"].'</td>'; }
		echo '<td class="small-caption">'.date( config_get( 'normal_date_format' ), $row["date_submitted"]).'</td>';
		echo '<td class="small-caption" align="right">'.db_minutes_to_hhmm($row["time_tracking"]).'</td>';
		echo '<td class="small-caption">'.$row["project_name"].'<br />'.$row["category_name"].'<br />'.string_get_bug_view_link( $row['bug_id'] ).' '.$row["summary"].'</td>';
		echo '<td class="small-caption" width="50%"><a href="view.php?id='.$row["bug_id"].'#c'.$row["id"].'">'.string_display_line(trim($row["note"])?$row["note"]:'[empty]').'</td>';
		echo '<td class="small-caption"><a href="bugnote_edit_page.php?bugnote_id='.$row["id"].'">edit</td>';
	}

	// echo '<td>'.$date.'</td>';
	// echo '<td>'.number_format($value / 60, 2).'</td>';
}
?>
	</table>

<?php
	echo $by_issue;

	collapse_closed( 'bugnotestats' );
?>

<table class="width100" cellspacing="0">
	<tr>
		<td class="form-title" colspan="4"><?php
			collapse_icon( 'bugnotestats' );
			echo lang_get( 'time_tracking' ); ?>
		</td>
	</tr>
</table>

<?php
	collapse_end( 'bugnotestats' );
