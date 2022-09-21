<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
	}

	$ScriptTitle = _('Latest purchase orders to authorise');

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

	$SQL = "SELECT purchorders.*,
			suppliers.suppname,
			suppliers.currcode,
			www_users.realname,
			www_users.email,
			currencies.decimalplaces AS currdecimalplaces
		FROM purchorders INNER JOIN suppliers
			ON suppliers.supplierid=purchorders.supplierno
		INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
		INNER JOIN www_users
			ON www_users.userid=purchorders.initiator
		WHERE status='Pending' LIMIT 10";
	$DashboardResult = DB_query($SQL);
	echo '<tbody>
		<tr>
			<th>', _('Supplier'), '</th>
			<th>', _('Order Date'), '</th>
			<th>', _('Delivery Date'), '</th>
			<th>', _('Total Amount'), '</th>
			<th>', _('Status'), '</th>
		</tr>';
	$k = 0;
	while ($row = DB_fetch_array($DashboardResult)) {
		$AuthSQL = "SELECT authlevel
					FROM purchorderauth
					WHERE currabrev='" . $row['currcode'] . "'
						AND userid='" . $_SESSION['UserID'] . "'";

		$AuthResult = DB_query($AuthSQL);
		$myauthrow = DB_fetch_array($AuthResult);
		$AuthLevel = $myauthrow['authlevel'];

		$OrderValueSQL = "SELECT sum(unitprice*quantityord) as ordervalue,
							sum(unitprice*quantityord) as total
						FROM purchorderdetails
						GROUP BY orderno";

		$OrderValueResult = DB_query($OrderValueSQL);
		$MyOrderValueRow = DB_fetch_array($OrderValueResult);
		$OrderValue = $MyOrderValueRow['ordervalue'];
		$totalOV = $MyOrderValueRow['total'];

		$FormatedOrderDate2 = ConvertSQLDate($row['orddate']);
		$FormatedDelDate2 = ConvertSQLDate($row['deliverydate']);

		echo '<tr class="striped_row">
			<td>', $row['suppname'], '</td>
			<td>', $FormatedOrderDate2, '</td>
			<td>', $FormatedDelDate2, '</td>
			<td class="number">', locale_number_format($totalOV, $row['currdecimalplaces']), '</td>
			<td>', $row['status'], '</td>
		</tr>';

	}
	echo '</tbody>
	</table>';

?>