<?php
/**********************************************************/
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
}

$ScriptTitle = _('Enter the script title here');

$SQL = "SELECT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
$DashboardResult = DB_query($SQL);
$DashboardRow = DB_fetch_array($DashboardResult);

echo '<table class="DashboardTable">
		<thead>
			<tr>
				<th colspan="5">
					<div class="CanvasTitle">', $ScriptTitle, '
					<a class="CloseButton" href="', $DashBoardURL, '?Remove=', urlencode($DashboardRow['id']), '" target="_parent" title="', _('Remove this applet from dashboard'), '" id="CloseButton" href="#">X</a>
					</div>
				</th>
			</tr>';
/* The section above must be left as is, apart from changing the script title.
 * Making other changes could stop the dashboard from functioning
*/

/**********************************************************************/
$SQL = "";
$DashboardResult = DB_query($SQL);
/* Create an SQL SELECT query to produce the data you want to show
 * and store the result in $DashboardResult
*/

/**********************************************************************/
echo '<tr>
		<th>Column 1</th>
		<th>Column 2</th>
		.................
		.................
		<th>Final Column</th>
	</tr>
</thead>';

echo '<tbody>';
/* Create the table/column headings for the output that you want to show
*/

/**********************************************************************/
while ($MyRow = DB_fetch_array($DashboardResult)) {
	echo '<tr class="striped_row">
			<td>Text Data 1</td>
			<td class="number">Numerical Data 2</td>
			....................
			....................
			<td>Final Data</td>
		</tr>';
}
/* Iterate through the rows of data returned by our SQL and create table
 * rows for each record
*/

/**********************************************************************/
echo '</tbody>
	</table>';
/* Don't forget to close off the table */

?>