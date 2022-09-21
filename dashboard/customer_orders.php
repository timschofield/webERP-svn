<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
}

	$ScriptTitle = _('Latest Customer Orders');

	$SQL = "SELECT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
	$DashboardResult = DB_query($SQL);
	$DashboardRow = DB_fetch_array($DashboardResult);

	echo '<table class="DashboardTable">
		<tr>
			<th colspan="6">
				', $ScriptTitle, '
				<a class="CloseButton" href="', $DashBoardURL, '?Remove=', urlencode($DashboardRow['id']), '" target="_parent" title="', _('Remove this applet from dashboard'), '" id="CloseButton">X</a>
			</th>
		</tr>';

	$SQL = "SELECT salesorders.orderno,
				debtorsmaster.name,
				debtorsmaster.currcode,
				salesorders.orddate,
				salesorders.deliverydate,
				currencies.decimalplaces AS currdecimalplaces,
				SUM(salesorderdetails.unitprice*salesorderdetails.quantity*(1-salesorderdetails.discountpercent)) AS ordervalue
			FROM salesorders
			INNER JOIN salesorderdetails
				ON salesorders.orderno = salesorderdetails.orderno
			INNER JOIN debtorsmaster
				ON salesorders.debtorno = debtorsmaster.debtorno
			INNER JOIN custbranch
				ON salesorders.branchcode = custbranch.branchcode
				AND salesorders.debtorno = custbranch.debtorno
			INNER JOIN currencies
				ON debtorsmaster.currcode = currencies.currabrev
			WHERE salesorderdetails.completed = 0
			GROUP BY salesorders.orderno,
					debtorsmaster.name,
					currencies.decimalplaces,
					custbranch.brname,
					salesorders.customerref,
					salesorders.orddate
			ORDER BY salesorders.orderno LIMIT 5";

	$SalesOrdersResult = DB_query($SQL);

	$TotalSalesOrders = 0;
	echo '<tr>
		<th>', _('Order number'), '</th>
		<th>', _('Customer'), '</th>
		<th>', _('Order Date'), '</th>
		<th>', _('Delivery Date'), '</th>
		<th class="number">', _('Order Amount'), '</th>
		<th>', _('Currency'), '</th>
	</tr> ';
	$k = 0;

	while ($row = DB_fetch_array($SalesOrdersResult)) {
		$DecimalPlaces = $row['currdecimalplaces'];
		$FormatedOrderValue = locale_number_format($row['ordervalue'], $row['currdecimalplaces']);
		$OrderDate = ConvertSQLDate($row['orddate']);
		$DelDate = ConvertSQLDate($row['deliverydate']);
		$TotalSalesOrders+= $row['ordervalue'];
		echo '<tr class="striped_row">
			<td> ', $row['orderno'], ' </td>
			<td> ', $row['name'], ' </td>
			<td>', $OrderDate, '</td>
			<td>', $DelDate, '</td>
			<td class="number">', $FormatedOrderValue, '</td>
			<td>', $row['currcode'], '</td>
		</tr>';
	}

	if (DB_num_rows($SalesOrdersResult) > 0) {
		echo '<tr class="total_row">
			<td colspan=3>', _('Total'), '</td>
			<td colspan=2 class="number">', locale_number_format($TotalSalesOrders, $DecimalPlaces), '</td>
			<td></td>
		</tr>';
	}

	echo '</table>';

?>