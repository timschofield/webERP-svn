<?php
include ('includes/session.php');
$Title = _('Costed Bill Of Material');
include ('includes/header.php');

if (isset($_GET['StockID'])) {
	$StockId = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockId = trim(mb_strtoupper($_POST['StockID']));
}

if (!isset($_POST['StockID'])) {
	echo '<p class="page_title_text" >
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="page_help_text">
			', _('Select a manufactured part'), ' (', _('or Assembly or Kit part'), ') ', _('to view the costed bill of materials'), '<br />', _('Parts must be defined in the stock item entry'), '/', _('modification screen as manufactured'), ', ', _('kits or assemblies to be available for construction of a bill of material'), '
		</div>';

	echo '<fieldset>
			<legend>', _('Enter Search Criteria'), '</legend>
			<field>
				<label for="Keywords">', _('Enter text extracts in the'), ' ', _('description'), ':</label>
				<input type="text" autofocus="autofocus" name="Keywords" size="20" maxlength="25" />
				<fieldhelp>', _('Enter part of an items description'), '</fiedhelp>
			</field>
			<h3>', _('OR'), '</h3>
			<field>
				<label for="StockCode">', _('Enter extract of the'), ' ', _('Stock Code'), ':</label>
				<input type="text" name="StockCode" size="15" maxlength="20" />
				<fieldhelp>', _('Or enter part of an items code'), '</fiedhelp>
			</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="Search" value="', _('Search Now'), '" />
		</div>
	</form>';

}

if (isset($_POST['Search'])) {
	// Work around to auto select
	if ($_POST['Keywords'] == '' and $_POST['StockCode'] == '') {
		$_POST['StockCode'] = '%';
	}
	if ($_POST['Keywords'] and $_POST['StockCode']) {
		prnMsg(_('Stock description keywords have been used in preference to the Stock code extract entered'), 'info');
	}
	if ($_POST['Keywords'] == '' and $_POST['StockCode'] == '') {
		prnMsg(_('At least one stock description keyword or an extract of a stock code must be entered for the search'), 'info');
	} else {
		if (mb_strlen($_POST['Keywords']) > 0) {
			//insert wildcard characters in spaces
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units,
							stockmaster.mbflag,
							SUM(locstock.quantity) as totalonhand
					FROM stockmaster INNER JOIN locstock
					ON stockmaster.stockid = locstock.stockid
					WHERE stockmaster.description " . LIKE . "'" . $SearchString . "'
					AND (stockmaster.mbflag='M'
						OR stockmaster.mbflag='K'
						OR stockmaster.mbflag='A'
						OR stockmaster.mbflag='G')
					GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.mbflag
					ORDER BY stockmaster.stockid";

		} elseif (mb_strlen($_POST['StockCode']) > 0) {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units,
							stockmaster.mbflag,
							sum(locstock.quantity) as totalonhand
					FROM stockmaster INNER JOIN locstock
					ON stockmaster.stockid = locstock.stockid
					WHERE stockmaster.stockid " . LIKE . "'%" . $_POST['StockCode'] . "%'
					AND (stockmaster.mbflag='M'
						OR stockmaster.mbflag='K'
						OR stockmaster.mbflag='G'
						OR stockmaster.mbflag='A')
					GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.mbflag
					ORDER BY stockmaster.stockid";

		}

		$ErrMsg = _('The SQL to find the parts selected failed with the message');
		$Result = DB_query($SQL, $ErrMsg);

	} //one of keywords or StockCode was more than a zero length string

} //end of if search
if (isset($_POST['Search']) and isset($Result) and !isset($SelectedParent)) {

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table>
			<tr>
				<th>', _('Code'), '</th>
				<th>', _('Description'), '</th>
				<th>', _('On Hand'), '</th>
				<th>', _('Units'), '</th>
			</tr>';

	$j = 0;
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['mbflag'] == 'A' or $MyRow['mbflag'] == 'K') {
			$StockOnHand = 'N/A';
		} else {
			$StockOnHand = locale_number_format($MyRow['totalonhand'], 2);
		}
		echo '<tr class="striped_row">
				<td><input type="submit" name="StockID" value="', $MyRow['stockid'], '" /></td>
				<td>', $MyRow['description'], 's</td>
				<td class="number">', $StockOnHand, '</td>
				<td>', $MyRow['units'], '</td>
			</tr>';
		//end of page full new headings if

	}
	//end of while loop
	echo '</table>
	</form>';
}

if (isset($StockId) and $StockId != "") {

	$SQL = "SELECT description,
					units,
					labourcost,
					overheadcost
				FROM stockmaster
				WHERE stockmaster.stockid='" . $StockId . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$ParentLabourCost = $MyRow['labourcost'];
	$ParentOverheadCost = $MyRow['overheadcost'];

	$SQL = "SELECT bom.parent,
					bom.component,
					stockmaster.description,
					stockmaster.decimalplaces,
					materialcost+ labourcost+overheadcost as standardcost,
					bom.quantity,
					bom.quantity * (materialcost+ labourcost+ overheadcost) AS componentcost
				FROM bom
				INNER JOIN stockmaster
					ON bom.component = stockmaster.stockid
				WHERE bom.parent = '" . $StockId . "'
					AND bom.effectiveafter <= CURRENT_DATE
					AND bom.effectiveto > CURRENT_DATE";

	$ErrMsg = _('The bill of material could not be retrieved because');
	$BOMResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($BOMResult) == 0) {
		prnMsg(_('The bill of material for this part is not set up') . ' - ' . _('there are no components defined for it'), 'warn');
	} else {
		echo '<div class="toplink noPrint">
				<a href="', $RootPath, '/index.php">', _('Return to Main Menu'), '</a>
			</div>';

		echo '<p class="page_title_text" >
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
			</p>';

		echo '<table>
				<tr>
					<th colspan="5">
						', $MyRow['description'], ' : ', _('per'), ' ', $MyRow['units'], '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print this report'), '" alt="', _('Print'), '" onclick="window.print();" />
					</th>
				</tr>
				<tr>
					<th>', _('Component'), '</th>
					<th>', _('Description'), '</th>
					<th>', _('Quantity'), '</th>
					<th>', _('Unit Cost'), '</th>
					<th>', _('Total Cost'), '</th>
				</tr>';

		$TotalCost = 0;

		while ($MyRow = DB_fetch_array($BOMResult)) {

			/* Component Code  Description  Quantity Std Cost  Total Cost */
			echo '<tr class="striped_row">
					<td><a href="', $RootPath, '/SelectProduct.php?StockID=', urlencode($MyRow['component']), '">', $MyRow['component'], '</a></td>
					<td>', $MyRow['description'], '</td>
					<td class="number">', locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']), '</td>
					<td class="number">', locale_number_format($MyRow['standardcost'], $_SESSION['CompanyRecord']['decimalplaces'] + 2), '</td>
					<td class="number">', locale_number_format($MyRow['componentcost'], $_SESSION['CompanyRecord']['decimalplaces'] + 2), '</td>
				</tr>';

			$TotalCost+= $MyRow['componentcost'];
		}

		$TotalCost+= $ParentLabourCost;
		echo '<tr>
				<td colspan="4" class="number">', _('Labour Cost'), '</td>
				<td class="number">', locale_number_format($ParentLabourCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';
		$TotalCost+= $ParentOverheadCost;

		echo '<tr>
				<td colspan="4" class="number">', _('Overhead Cost'), '</td>
				<td class="number">', locale_number_format($ParentOverheadCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';

		echo '<tr>
				<td colspan="4" class="number">', _('Total Cost'), '</td>
				<td class="number">', locale_number_format($TotalCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';

		echo '</table>';
	}
} else { //no stock item entered
	prnMsg(_('Enter a stock item code above') . ', ' . _('to view the costed bill of material for'), 'info');
}

include ('includes/footer.php');
?>