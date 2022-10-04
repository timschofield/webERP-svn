<?php
include ('includes/session.php');

if (isset($_POST['PrintPDF']) and isset($_POST['FromCriteria']) and mb_strlen($_POST['FromCriteria']) >= 1 and isset($_POST['ToCriteria']) and mb_strlen($_POST['ToCriteria']) >= 1) {

	include ('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Bill Of Material Listing'));
	$PDF->addInfo('Subject', _('Bill Of Material Listing'));
	$FontSize = 12;
	$PageNumber = 0;
	$line_height = 12;

	/*Now figure out the bills to report for the part range under review */
	$SQL = "SELECT bom.parent,
				bom.component,
				stockmaster.description as compdescription,
				stockmaster.decimalplaces,
				stockmaster.units,
				bom.quantity,
				bom.loccode,
				bom.workcentreadded,
				bom.effectiveto,
				bom.effectiveafter
			FROM stockmaster
			INNER JOIN bom
				ON stockmaster.stockid=bom.component
			INNER JOIN locationusers
				ON locationusers.loccode=bom.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE bom.parent >= '" . $_POST['FromCriteria'] . "'
				AND bom.parent <= '" . $_POST['ToCriteria'] . "'
				AND bom.effectiveto > CURRENT_DATE
				AND bom.effectiveafter <= CURRENT_DATE
			ORDER BY bom.parent,
					bom.component";

	$BOMResult = DB_query($SQL, '', '', false, false); //dont do error trapping inside DB_query
	$Title = _('Bill Of Material Listing for Parts Between') . ' ' . $_POST['FromCriteria'] . ' ' . _('and') . ' ' . $_POST['ToCriteria'];
	include ('includes/header.php');
	echo '<div class="toplink">
			<a class="noPrint" href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Select different criteria'), '</a>
		</div>';

	echo '<table>
			<thead>
				<tr class="noPrint">
					<th colspan="9"><h2>', $Title, '</h2>
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print this report'), '" alt="', _('Print'), '" onclick="window.print();" />
					</th>
				</tr>
				<tr>
					<td colspan="7"><h3>
						', $_SESSION['CompanyRecord']['coyname'], '<br />
						', $Title, '<br />
					</td>
					<td style="float:right;vertical-align:top;text-align:right">
						', _('Printed On'), ' ', Date($_SESSION['DefaultDateFormat']) . '
					</td></h3>
				</tr>
				<tr>
					<th colspan="2">', _('Component/Part Description'), '</th>
					<th>', _('Effective After'), '</th>
					<th>', _('Effective To'), '</th>
					<th>', _('Location'), '</th>
					<th>', _('Work Centre'), '</th>
					<th colspan="2">', _('Quantity'), '</th>
				</tr>
			</thead>
			<tbody>';

	$ParentPart = '';
	while ($MyRow = DB_fetch_array($BOMResult)) {
		if ($ParentPart != $MyRow['parent']) {
			$SQL = "SELECT description FROM stockmaster WHERE stockmaster.stockid = '" . $MyRow['parent'] . "'";
			$ParentResult = DB_query($SQL);
			$ParentRow = DB_fetch_array($ParentResult);
			if ($ParentPart != '') {
				echo '<tr>
						<td colspan="8"><hr></hr></th>
					</tr>';
			}
			echo '<tr class="total_row">
					<td colspan="8"><h4>', $MyRow['parent'], ' - ', $ParentRow['description'], '</h4></td>
				</tr>';
			$ParentPart = $MyRow['parent'];
		}

		echo '<tr class="striped_row">
				<td>', $MyRow['component'], '</td>
				<td>', $MyRow['compdescription'], '</td>
				<td>', ConvertSQLDate($MyRow['effectiveafter']), '</td>
				<td>', ConvertSQLDate($MyRow['effectiveto']), '</td>
				<td>', $MyRow['loccode'], '</td>
				<td>', $MyRow['workcentreadded'], '</td>
				<td class="number">', locale_number_format($MyRow['quantity'], 'Variable'), '</td>
				<td>', $MyRow['units'], '</td>
			</tr>';
	}
	echo '<tr class="total_row">
			<td colspan="8"><hr></hr></th>
		</tr>';
	echo '</tbody>
		</table>';
	include ('includes/footer.php');

} else {
	/*The option to print PDF was not hit */

	$Title = _('Bill Of Material Listing');
	include ('includes/header.php');

	$SQL = "SELECT min(stockid) AS fromcriteria,
					max(stockid) AS tocriteria
				FROM stockmaster";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/reports.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	if (!isset($_POST['FromCriteria']) or !isset($_POST['ToCriteria'])) {
		/*if $FromCriteria is not set then show a form to allow input	*/

		echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

		echo '<fieldset>
				<legend>', _('Enter Report Criteria'), '</legend>
				<field>
					<label for="FromCriteria">', _('From Inventory Part Code'), ':</label>
					<input type="text" name="FromCriteria" size="20" autofocus="autofocus" required="required" maxlength="20" value="', $MyRow['fromcriteria'], '" />
					<fieldhelp>', _('Starting stock code'), '</fieldhelp>
				</field>
				<field>
					<label for="ToCriteria">', _('To Inventory Part Code'), ':</label>
					<input type="text" name="ToCriteria" size="20" required="required" maxlength="20" value="', $MyRow['tocriteria'], '" />
					<fieldhelp>', _('Final stock code'), '</fieldhelp>
				</field>
			</fieldset>';

		echo '<div class="centre">
				<input type="submit" name="PrintPDF" value="', _('View Report'), '" />
			</div>
		</form>';

	}
	include ('includes/footer.php');

}
/*end of else not PrintPDF */

?>