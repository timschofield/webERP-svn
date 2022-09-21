<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
	}

	$ScriptTitle = _('Latest work orders');

	$SQL = "SELECT DISTINCT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
	$DashboardResult = DB_query($SQL);
	$DashboardRow = DB_fetch_array($DashboardResult);

	echo '<table class="DashboardTable">
			<tr>
				<th colspan="4">
					<div class="CanvasTitle">', $ScriptTitle, '
						<a class="CloseButton" href="', $DashBoardURL, '?Remove=', urlencode($DashboardRow['id']), '" target="_parent" title="', _('Remove this applet from dashboard'), '" id="CloseButton" href="#">X</a>
					</div>
				</th>
			</tr>';

	$SQL = "SELECT workorders.wo,
				woitems.stockid,
				stockmaster.
				description,
				stockmaster.decimalplaces,
				woitems.qtyreqd,
				woitems.qtyrecd,
				workorders.requiredby,
				workorders.startdate
			FROM workorders
			INNER JOIN woitems
				ON workorders.wo = woitems.wo
			INNER JOIN stockmaster
				ON woitems.stockid = stockmaster.stockid
			ORDER BY workorders.requiredby DESC LIMIT 7";
	$WorkOrdersResult = DB_query($SQL);

	echo '<tbody>
		<tr>
			<th>', _('Item'), '</th>
			<th>', _('Required By'), '</th>
			<th>', _('Quantity Required'), '</th>
			<th>', _('Quantity Outstanding'), '</th>
		</tr>';

	while ($row = DB_fetch_array($WorkOrdersResult)) {
		$StockId = $row['stockid'];
		$FormatedRequiredByDate = ConvertSQLDate($row['requiredby']);
		$FormatedStartDate = ConvertSQLDate($row['startdate']);
		$qreq = locale_number_format($row['qtyreqd'], $row['decimalplaces']);
		$qout = locale_number_format($row['qtyreqd'] - $row['qtyrecd'], $row['decimalplaces']);

		echo '<tr class="striped_row">
			<td><a href="', $RootPath, '/StockStatus.php?StockID=', urlencode($StockId), '" target="_blank">', $row['stockid'], ' -', $row['description'], '</td>
			<td class="number">', ConvertSQLDate($row['requiredby']), '</td>
			<td class="number">', $qreq, '</td>
			<td class="number">', $qout, '</td>
		</tr>';

	}

	echo '</tbody>
	</table>';

?>