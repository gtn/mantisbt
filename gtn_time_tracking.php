<?php
# MantisBT - a php based bugtracking system

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
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2014  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */
/**
 * MantisBT Core API's
 */
require_once( 'core.php' );

html_page_top('GTN timetracking');


$query = "SELECT bugnote.*, bug.summary, bug_text.note
 	FROM mantis_bugnote_table bugnote
 	LEFT JOIN mantis_bug_table bug ON bugnote.bug_id = bug.id
 	LEFT JOIN mantis_bugnote_text_table bug_text ON bug_text.id = bugnote.bugnote_text_id
 	WHERE bugnote.time_tracking > 0 AND bugnote.reporter_id=".auth_get_current_user_id()."
 	ORDER BY bugnote.date_submitted DESC";
/*
			SELECT f.name, f.type, f.access_level_r, f.default_value, f.type, s.value
			FROM $t_custom_field_project_table p
				INNER JOIN $t_custom_field_table f ON f.id = p.field_id
				LEFT JOIN $t_custom_field_string_table s
					ON s.field_id = p.field_id AND s.bug_id = " . db_param() . "
			WHERE p.project_id = " . db_param() . "
			ORDER BY p.sequence ASC, f.name ASC";

$t_params = array(
	(int)$p_bug_id,
	bug_get_field( $p_bug_id, 'project_id' )
);

$result = db_query_bound( $query, $t_params );
$t_row_count = db_num_rows( $result );

$t_custom_fields = array();

for( $i = 0;$i < $t_row_count;++$i ) {
	$row = db_fetch_array( $result );

*/

$result = db_query_bound( $query, $t_params );

$values = [];

while ($row = db_fetch_array( $result )) {
	$values[date('Y-m-d', $row["date_submitted"]).$row["date_submitted"]] = $row;
	$values[date('Y-m', $row["date_submitted"])] += $row["time_tracking"];
	// $values[date('Y-m-d', $row["date_submitted"])] += $row["time_tracking"];

	// var_dump($row);
}

ksort($values);

echo '&nbsp;<table cellspacing="0" cellpadding="2" border="1" align="center">';

foreach ($values as $date=>$row) {
	if (!is_array($row)) {
		echo '<tr style="background: lightgrey;">';
		echo '<td>'.$date.'</td>';
		echo '<td align="right">'.number_format($row / 60, 2).'</td>';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td></td>';
		continue;
	}
	echo '<tr>';
	echo '<td>'.date('Y-m-d', $row["date_submitted"]).'</td>';
	echo '<td align="right">'.number_format($row["time_tracking"] / 60, 2).'</td>';
	echo '<td><a href="view.php?id='.$row["bug_id"].'#c'.$row["id"].'">'.$row["summary"].'</td>';
	echo '<td><a href="view.php?id='.$row["bug_id"].'">'.(trim($row["note"])?$row["note"]:'empty').'</td>';
	echo '<td><a href="bugnote_edit_page.php?bugnote_id='.$row["id"].'">edit</td>';

	// echo '<td>'.$date.'</td>';
	// echo '<td>'.number_format($value / 60, 2).'</td>';
}
echo '</table>';

html_page_bottom();
