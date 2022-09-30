<?php
/* Shows bank accounts authorised for with balances */

include ('includes/session.php');

$Title = _('List of bank account balances');
/* Manual links before header.php */
$ViewTopic = 'GeneralLedger';
$BookMark = 'BankAccountBalances';
include ('includes/header.php');

echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/bank.png" title="', _('Bank Account Balances'), '" alt="" /> ', _('Bank Account Balances'), '
	</p>';

echo '<table>
		<tr>
			<th class="SortedColumn">', _('Bank Account'), '</th>
			<th class="SortedColumn">', _('Account Name'), '</th>
			<th>', _('Balance in account currency'), '</th>
			<th>', _('Balance in functional currency'), '</th>
			<th></th>
		</tr>';

$SQL = "SELECT bankaccounts.accountcode,
				currcode,
				bankaccountname
			FROM bankaccounts
			INNER JOIN bankaccountusers
				ON bankaccounts.accountcode=bankaccountusers.accountcode
				AND userid='" . $_SESSION['UserID'] . "'
			ORDER BY bankaccounts.bankaccountname";
$Result = DB_query($SQL);

while ($MyBankRow = DB_fetch_array($Result)) {
	$CurrBalanceSQL = "SELECT SUM(amount) AS balance FROM banktrans WHERE bankact='" . $MyBankRow['accountcode'] . "'";
	$CurrBalanceResult = DB_query($CurrBalanceSQL);
	$CurrBalanceRow = DB_fetch_array($CurrBalanceResult);

	$FuncBalanceSQL = "SELECT SUM(amount) AS balance FROM gltrans WHERE account='" . $MyBankRow['accountcode'] . "'";
	$FuncBalanceResult = DB_query($FuncBalanceSQL);
	$FuncBalanceRow = DB_fetch_array($FuncBalanceResult);

	$DecimalPlacesSQL = "SELECT decimalplaces FROM currencies WHERE currabrev='" . $MyBankRow['currcode'] . "'";
	$DecimalPlacesResult = DB_query($DecimalPlacesSQL);
	$DecimalPlacesRow = DB_fetch_array($DecimalPlacesResult);

	echo '<tr class="striped_row">
			<td>', $MyBankRow['accountcode'], '</td>
			<td>', $MyBankRow['bankaccountname'], '</td>
			<td class="number">', locale_number_format($CurrBalanceRow['balance'], $DecimalPlacesRow['decimalplaces']), ' ', $MyBankRow['currcode'], '</td>
			<td class="number">', locale_number_format($FuncBalanceRow['balance'], $_SESSION['CompanyRecord']['decimalplaces']), ' ', $_SESSION['CompanyRecord']['currencydefault'], '</td>
			<td><a href="', $RootPath, '/DailyBankTransactions.php?BankAccount=', $MyBankRow['accountcode'], '&FromTransDate=', DateAdd(date($_SESSION['DefaultDateFormat']), 'm', -1), '&ToTransDate=', date($_SESSION['DefaultDateFormat']), '" />', _('Show Transactions'), '</a>
		</tr>';
}

echo '</table>';

include ('includes/footer.php');

?>