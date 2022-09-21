<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
	}

	$ScriptTitle = _('Latest purchase orders');

	$SQL = "SELECT DISTINCT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
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

	$SQL = "SELECT purchorders.orderno,
				suppliers.suppname,
				purchorders.orddate,
				purchorders.deliverydate,
				purchorders.initiator,
				purchorders.requisitionno,
				purchorders.allowprint,
				purchorders.status,
				suppliers.currcode,
				currencies.decimalplaces AS currdecimalplaces,
				SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
			FROM purchorders
			INNER JOIN purchorderdetails
				ON purchorders.orderno = purchorderdetails.orderno
			INNER JOIN suppliers
				ON purchorders.supplierno = suppliers.supplierid
			INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
			WHERE purchorders.orderno=purchorderdetails.orderno
			GROUP BY purchorders.orderno,
					suppliers.suppname,
					purchorders.orddate,
					purchorders.initiator,
					purchorders.requisitionno,
					purchorders.allowprint,
					purchorders.status,
					suppliers.currcode,
					currencies.decimalplaces
			ORDER BY orddate DESC LIMIT 12";
	$SalesOrdersResult2 = DB_query($SQL);
	$Total = 0;

	echo '<tbody>
		<tr>
			<th>', _('Supplier'), '</th>
			<th>', _('Order Date'), '</th>
			<th>', _('Delivery Date'), '</th>
			<th>', _('Order Total'), '</th>
			<th>', _('Status'), '</th>
		</tr>';
	$k = 0;
	while ($row = DB_fetch_array($SalesOrdersResult2)) {
		$FormatedOrderValue2 = locale_number_format($row['ordervalue'], $row['currdecimalplaces']);
		$Total+= $row['ordervalue'];

		$FormatedOrderDate1 = ConvertSQLDate($row['orddate']);
		$FormatedDelDate1 = ConvertSQLDate($row['deliverydate']);

		echo '<tr class="striped_row">
			<td> ', $row['suppname'], ' </td>
			<td>', $FormatedOrderDate1, '</td>
			<td>', $FormatedDelDate1, '</td>
			<td class="number">', $FormatedOrderValue2, '</td>
			<td> ', $row['status'], ' </td> ';

	}
	echo '</tbody>
	</table>';

?>