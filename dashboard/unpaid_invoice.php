<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
	}

	$ScriptTitle = _('Latest unpaid customer invoices');

	$SQL = "SELECT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
	$DashboardResult = DB_query($SQL);
	$DashboardRow = DB_fetch_array($DashboardResult);

	echo '<table class="DashboardTable">
		<tr>
			<th colspan="5">
				<div class="CanvasTitle">', $ScriptTitle, '
						<a class="CloseButton" href="', $DashBoardURL, '?Remove=', urlencode($DashboardRow['id']), '" target="_parent" title="', _('Remove this applet from dashboard'), '" id="CloseButton" href="#">X</a>
				</div>
			</th>
		</tr>';

	$SQL = "SELECT salesorders.orderno,
				debtorsmaster.name,
				custbranch.brname,
				salesorders.customerref,
				salesorders.orddate,
				salesorders.deliverydate,
				salesorders.deliverto,
				salesorders.printedpackingslip,
				salesorders.poplaced,
				currencies.decimalplaces AS currdecimalplaces,
				SUM(salesorderdetails.unitprice*salesorderdetails.quantity*(1-salesorderdetails.discountpercent)/currencies.rate) AS ordervalue
			FROM salesorders
			INNER JOIN salesorderdetails
				ON salesorders.orderno = salesorderdetails.orderno
			INNER JOIN debtorsmaster
				ON salesorders.debtorno = debtorsmaster.debtorno
			INNER JOIN custbranch
				ON debtorsmaster.debtorno = custbranch.debtorno
				AND salesorders.branchcode = custbranch.branchcode
			INNER JOIN currencies
				ON debtorsmaster.currcode = currencies.currabrev
			WHERE salesorderdetails.completed=0
			GROUP BY salesorders.orderno,
					debtorsmaster.name,
					custbranch.brname,
					salesorders.customerref,
					salesorders.orddate,
					salesorders.deliverydate,
					salesorders.deliverto,
					salesorders.printedpackingslip,
					salesorders.poplaced
			ORDER BY salesorders.orderno";
	$SalesOrdersResult1 = DB_query($SQL);

	echo '<tr>
		<th>', _('Customer'), '</th>
		<th>', _('Order Date'), '</th>
		<th>', _('Delivery Date'), '</th>
		<th>', _('Delivery To'), '</th>
		<th>', _('Order Total'), '</th>
	</tr> ';

	$TotalOrderValue = 0;
	while ($row = DB_fetch_array($SalesOrdersResult1)) {
		$fo = locale_number_format($row['ordervalue'], $row['currdecimalplaces']);
		$TotalOrderValue+= $row['ordervalue'];
		$DecimalPlaces = $row['currdecimalplaces'];

		$FormatedOrderDate = ConvertSQLDate($row['orddate']);
		$FormatedDelDate = ConvertSQLDate($row['deliverydate']);

		echo '<tr class="striped_row">
			<td>', $row['name'], '</td>
			<td>', $FormatedOrderDate, '</td>
			<td>', $FormatedDelDate, '</td>
			<td> ', $row['deliverto'], ' </td>
			<td class="number">', $fo, '</td>
		</tr>';

	}

	if (DB_num_rows($SalesOrdersResult1) > 0) {
		echo '<tr class="total_row">
			<td colspan="4">', _('Total'), '</td>
			<td class="number">', locale_number_format($TotalOrderValue, $DecimalPlaces), '</td>
		</tr>
	</tbody>';
	}

	echo '</table>';

?>