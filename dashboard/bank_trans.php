<?php
$PathPrefix = '../';

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
	}

	$ScriptTitle = _('Latest bank transactions');

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

	$SQL = "SELECT banktrans.currcode,
				banktrans.amount,
				banktrans.functionalexrate,
				banktrans.exrate,
				banktrans.banktranstype,
				banktrans.transdate,
				bankaccounts.bankaccountname,
				systypes.typename,
				currencies.decimalplaces
			FROM banktrans
			INNER JOIN bankaccounts
				ON banktrans.bankact=bankaccounts.accountcode
			INNER JOIN systypes
				ON banktrans.type=systypes.typeid
			INNER JOIN currencies
				ON banktrans.currcode=currencies.currabrev
			ORDER BY banktrans.transdate DESC, ABS(banktrans.amount) DESC LIMIT 10";

	$DashboardResult = DB_query($SQL);
	$AccountCurrTotal = 0;
	$LocalCurrTotal = 0;

	echo '<tbody>
		<tr>
			<th>', _('Currency'), '</th>
			<th>', _('Amount'), '</th>
			<th>', _('Transaction Type'), '</th>
			<th>', _('Transaction Date'), '</th>
			<th>', _('Account Name'), '</th>
		</tr>';

	$k = 0;

	while ($row = DB_fetch_array($DashboardResult)) {

		$AccountCurrTotal+= $row['amount'];
		$LocalCurrTotal+= $row['amount'] / $row['functionalexrate'] / $row['exrate'];
		echo '<tr class="striped_row">
			<td>', $row['currcode'], '</td>
			<td class="number">', locale_number_format($row['amount'], $row['decimalplaces']), '</td>
			<td>', $row['typename'], '</td>
			<td>', ConvertSQLDate($row['transdate']), '</td>
			<td class="number">', $row['bankaccountname'], '</td>
		</tr>';
	}
	echo '</tbody>
	</table>';

?>