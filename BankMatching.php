<?php
include ('includes/session.php');
$Title = _('Bank Matching'); // Screen identificator.
$ViewTopic = 'GeneralLedger'; // Filename's id in ManualContents.php's TOC.
$BookMark = 'BankMatching'; // Filename's id in ManualContents.php's TOC.
include ('includes/header.php');

if ((isset($_GET['Type']) and $_GET['Type'] == 'Receipts') or (isset($_POST['Type']) and $_POST['Type'] == 'Receipts')) {

	$Type = 'Receipts';
	$TypeName = _('Receipts');
	echo '<p class="page_title_text" >
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Bank Matching'), '" alt="', _('Bank Matching'), '" />', ' ', _('Bank Account Matching - Receipts'), '
		</p>';

} elseif ((isset($_GET['Type']) and $_GET['Type'] == 'Payments') or (isset($_POST['Type']) and $_POST['Type'] == 'Payments')) {

	$Type = 'Payments';
	$TypeName = _('Payments');
	echo '<p class="page_title_text" >
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_delete.png" title="', _('Bank Matching'), '" alt="', _('Bank Matching'), '" />', ' ', _('Bank Account Matching - Payments'), '
		</p>';

} else {

	prnMsg(_('This page must be called with a bank transaction type') . '. ' . _('It should not be called directly'), 'error');
	include ('includes/footer.php');
	exit;
}

if (isset($_GET['Account'])) {
	$_POST['BankAccount'] = $_GET['Account'];
	$_POST['ShowTransactions'] = true;
	$_POST['Ostg_or_All'] = 'Ostg';
	$_POST['First20_or_All'] = 'All';
}

if (isset($_POST['Update']) and $_POST['RowCounter'] > 1) {
	for ($Counter = 1;$Counter <= $_POST['RowCounter'];$Counter++) {
		if (isset($_POST['Clear_' . $Counter]) and $_POST['Clear_' . $Counter] == True) {
			/*Get amount to be cleared */
			$SQL = "SELECT amount,
							exrate
						FROM banktrans
						WHERE banktransid='" . $_POST['BankTrans_' . $Counter] . "'";
			$ErrMsg = _('Could not retrieve transaction information');
			$Result = DB_query($SQL, $ErrMsg);
			$MyRow = DB_fetch_array($Result);
			$AmountCleared = round($MyRow[0] / $MyRow[1], 2);
			/*Update the banktrans recoord to match it off */
			$SQL = "UPDATE banktrans SET amountcleared= " . $AmountCleared . "
									WHERE banktransid='" . $_POST['BankTrans_' . $Counter] . "'";
			$ErrMsg = _('Could not match off this payment because');
			$Result = DB_query($SQL, $ErrMsg);

		} elseif ((isset($_POST['AmtClear_' . $Counter]) and filter_number_format($_POST['AmtClear_' . $Counter]) < 0 and $Type == 'Payments') or ($Type == 'Receipts' and isset($_POST['AmtClear_' . $Counter]) and filter_number_format($_POST['AmtClear_' . $Counter]) > 0)) {

			/*if the amount entered was numeric and negative for a payment or positive for a receipt */

			$SQL = "UPDATE banktrans SET amountcleared=" . filter_number_format($_POST['AmtClear_' . $Counter]) . "
					 WHERE banktransid='" . $_POST['BankTrans_' . $Counter] . "'";

			$ErrMsg = _('Could not update the amount matched off this bank transaction because');
			$Result = DB_query($SQL, $ErrMsg);

		} elseif (isset($_POST['Unclear_' . $Counter]) and $_POST['Unclear_' . $Counter] == True) {

			$SQL = "UPDATE banktrans SET amountcleared = 0
					 WHERE banktransid='" . $_POST['BankTrans_' . $Counter] . "'";
			$ErrMsg = _('Could not unclear this bank transaction because');
			$Result = DB_query($SQL, $ErrMsg);
		}
	}
	/*Show the updated position with the same criteria as previously entered*/
	$_POST['ShowTransactions'] = True;
}

echo '<div class="page_help_text">', _('Use this screen to match Receipts and Payments to your Bank Statement.  Check your bank statement and click the check-box when you find the matching transaction.'), '</div>';

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<input type="hidden" name="Type" value="', $Type, '" />';

echo '<fieldset>
		<legend>', _('Selection Criteria for inquiry'), '</legend>
		<field>
			<label for="BankAccount">', _('Bank Account'), ':</label>
			<select autofocus="autofocus" name="BankAccount">';

$SQL = "SELECT bankaccounts.accountcode,
				bankaccounts.bankaccountname
			FROM bankaccounts
			INNER JOIN bankaccountusers
				ON bankaccounts.accountcode=bankaccountusers.accountcode
			WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "'
			ORDER BY bankaccounts.bankaccountname";
$ResultBankActs = DB_query($SQL);
while ($MyRow = DB_fetch_array($ResultBankActs)) {
	if (isset($_POST['BankAccount']) and $MyRow['accountcode'] == $_POST['BankAccount']) {

		echo '<option selected="selected" value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], '</option>';
	} else {
		echo '<option value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], '</option>';
	}
}

echo '</select>
	<fieldhelp>', _('Select the bank account to match payments on.'), '</fieldhelp>
</field>';

if (!isset($_POST['BeforeDate']) or !is_date($_POST['BeforeDate'])) {
	$_POST['BeforeDate'] = Date($_SESSION['DefaultDateFormat']);
}
if (!isset($_POST['AfterDate']) or !is_date($_POST['AfterDate'])) {
	$_POST['AfterDate'] = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m') - 3, Date('d'), Date('y')));
}

// Change to allow input of FROM DATE and then TO DATE, instead of previous back-to-front method, add datepicker
echo '<field>
		<label for="AfterDate">', _('Show'), ' ', $TypeName, ' ', _('from'), ':</label>
		<input type="text" name="AfterDate" class="date" size="12" required="required" maxlength="10" onchange="isDate(this, this.value, ', "'", $_SESSION['DefaultDateFormat'], "'", ')" value="', $_POST['AfterDate'], '" /></td>
		<fieldhelp>', _('Show transactions that have a date greater than or equal to this'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="BeforeDate">', _('to'), ':</label>
		<input type="text" name="BeforeDate" class="date" size="12" required="required" maxlength="10" onchange="isDate(this, this.value, ', "'", $_SESSION['DefaultDateFormat'], "'", ')" value="', $_POST['BeforeDate'], '" /></td>
		<fieldhelp>', _('Show transactions that have a date less than this'), '</fieldhelp>
	</field>';
echo '<field>
		<label for="Ostg_or_All">', _('All transaction, or outstanding'), ':</label>
		<td><select name="Ostg_or_All">';

if (isset($_POST['Ostg_or_All']) and $_POST['Ostg_or_All'] == 'All') {
	echo '<option selected="selected" value="All">', _('Show all'), ' ', $TypeName, ' ', _('in the date range'), '</option>';
	echo '<option value="Ostdg">', _('Show unmatched'), ' ', $TypeName, ' ', _('only'), '</option>';
} else {
	echo '<option value="All">', _('Show all'), ' ', $TypeName, ' ', _('in the date range'), '</option>';
	echo '<option selected="selected" value="Ostdg">', _('Show unmatched'), ' ', $TypeName, ' ', _('only'), '</option>';
}
echo '</select>
	<fieldhelp>', _('Choose outstanding'), ' ', $TypeName, ' ', _('only or all'), ' ', $TypeName, ' ', _('in the date range'), '</fieldhelp
</field>';

echo '<field>
		<label for="First20_or_All">', _('Show all or just first 20'), ':</label>
		<select name="First20_or_All">';
if (isset($_POST['First20_or_All']) and $_POST['First20_or_All'] == 'All') {
	echo '<option selected="selected" value="All">', _('Show all'), ' ', $TypeName, ' ', _('in the date range'), '</option>';
	echo '<option value="First20">', _('Show only the first 20'), ' ', $TypeName, '</option>';
} else {
	echo '<option value="All">', _('Show all'), ' ', $TypeName, ' ', _('in the date range'), '</option>';
	echo '<option selected="selected" value="First20">', _('Show only the first 20'), ' ', $TypeName, '</option>';
}

echo '</select>
	<fieldhelp>', _('Choose to display only the first 20 matching'), ' ', $TypeName, ' ', _('or all'), ' ', $TypeName, ' ', _('meeting the criteria'), '</fieldhelp>
</field>';

echo '</fieldset>
	<div class="centre">
		<input type="submit" name="ShowTransactions" value="', _('Show selected'), ' ', $TypeName, '" />';
if (isset($_POST['BankAccount'])) {
	echo '<p>
			<a href="', $RootPath, '/BankReconciliation.php?Account=', urlencode($_POST['BankAccount']), '">', _('Show reconciliation'), '</a>
		</p>
	</div>';
} else {
	echo '</div>';
}

$InputError = 0;
if (!is_date($_POST['BeforeDate'])) {
	$InputError = 1;
	prnMsg(_('The date entered for the field to show') . ' ' . $TypeName . ' ' . _('before') . ', ' . _('is not entered in a recognised date format') . '. ' . _('Entry is expected in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
}
if (!is_date($_POST['AfterDate'])) {
	$InputError = 1;
	prnMsg(_('The date entered for the field to show') . ' ' . $Type . ' ' . _('after') . ', ' . _('is not entered in a recognised date format') . '. ' . _('Entry is expected in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
}

if ($InputError != 1 and isset($_POST['BankAccount']) and $_POST['BankAccount'] != '' and isset($_POST['ShowTransactions'])) {

	$SQLBeforeDate = FormatDateForSQL($_POST['BeforeDate']);
	$SQLAfterDate = FormatDateForSQL($_POST['AfterDate']);

	$BankResult = DB_query("SELECT decimalplaces,
									currcode
							FROM bankaccounts INNER JOIN currencies
							ON bankaccounts.currcode=currencies.currabrev
							WHERE accountcode='" . $_POST['BankAccount'] . "'");
	$BankRow = DB_fetch_array($BankResult);
	$CurrDecimalPlaces = $BankRow['decimalplaces'];
	$CurrCode = $BankRow['currcode'];

	if ($_POST['Ostg_or_All'] == 'All') {
		if ($Type == 'Payments') {
			$SQL = "SELECT banktransid,
							ref,
							amountcleared,
							transdate,
							amount/exrate as amt,
							banktranstype
					FROM banktrans
					WHERE amount < 0
						AND transdate >= '" . $SQLAfterDate . "'
						AND transdate <= '" . $SQLBeforeDate . "'
						AND bankact='" . $_POST['BankAccount'] . "'
					ORDER BY transdate";

		} else {
			/* Type must == Receipts */
			$SQL = "SELECT banktransid,
							ref,
							amountcleared,
							transdate,
							amount/exrate as amt,
							banktranstype
						FROM banktrans
						WHERE amount > 0
							AND transdate >= '" . $SQLAfterDate . "'
							AND transdate <= '" . $SQLBeforeDate . "'
							AND bankact='" . $_POST['BankAccount'] . "'
						ORDER BY transdate";
		}
	} else {
		/*it must be only the outstanding bank trans required */
		if ($Type == 'Payments') {
			$SQL = "SELECT banktransid,
							ref,
							amountcleared,
							transdate,
							amount/exrate as amt,
							banktranstype
						FROM banktrans
						WHERE amount < 0
							AND transdate >= '" . $SQLAfterDate . "'
							AND transdate <= '" . $SQLBeforeDate . "'
							AND bankact='" . $_POST['BankAccount'] . "'
							AND  ABS(amountcleared - (amount / exrate)) > 0.009
						ORDER BY transdate";
		} else {
			/* Type must == Receipts */
			$SQL = "SELECT banktransid,
							ref,
							amountcleared,
							transdate,
							amount/exrate as amt,
							banktranstype
						FROM banktrans
						WHERE amount > 0
							AND transdate >= '" . $SQLAfterDate . "'
							AND transdate <= '" . $SQLBeforeDate . "'
							AND bankact='" . $_POST['BankAccount'] . "'
							AND  ABS(amountcleared - (amount / exrate)) > 0.009
						ORDER BY transdate";
		}
	}
	if ($_POST['First20_or_All'] != 'All') {
		$SQL = $SQL . " LIMIT 20";
	}

	$ErrMsg = _('The payments with the selected criteria could not be retrieved because');
	$PaymentsResult = DB_query($SQL, $ErrMsg);

	echo '<table cellpadding="2" summary="', _('Payments to be matched'), '">
			<thead>
				<tr>
					<th class="SortedColumn">', _('Cheque No'), '</th>
					<th class="SortedColumn">', _('Ref'), '</th>
					<th class="SortedColumn">', _('Date'), '</th>
					<th>', _('Amount'), '</th>
					<th>', _('Outstanding'), '</th>
					<th colspan="3">', _('Clear'), ' / ', _('Unclear'), '</th>
				</tr>
			</thead>';

	$i = 1; //no of rows counter
	echo '<tbody>';
	while ($MyRow = DB_fetch_array($PaymentsResult)) {

		$DisplayTranDate = ConvertSQLDate($MyRow['transdate']);
		$Outstanding = $MyRow['amt'] - $MyRow['amountcleared'];
		if ($MyRow['ref'] == '') {
			$MyRow['ref'] = _('N/A');
		}
		if (ABS($Outstanding) < 0.009) {
			/*the payment is cleared dont show the check box*/

			echo '<tr class="info_row">
					<td>', $MyRow['ref'], '</td>
					<td>', $MyRow['banktranstype'], '</td>
					<td>', $DisplayTranDate, '</td>
					<td class="number">', locale_number_format($MyRow['amt'], $CurrDecimalPlaces), '</td>
					<td class="number">', locale_number_format($Outstanding, $CurrDecimalPlaces), '</td>
					<td colspan="2">', _('Unclear'), '</td>
					<td><input type="checkbox" name="Unclear_', $i, '" /><input type="hidden" name="BankTrans_', $i, '" value="', $MyRow['banktransid'], '" /></td>
				</tr>';

		} else {
			echo '<tr class="striped_row">
					<td>', $MyRow['ref'], '</td>
					<td>', $MyRow['banktranstype'], '</td>
					<td>', $DisplayTranDate, '</td>
					<td class="number">', locale_number_format($MyRow['amt'], $CurrDecimalPlaces), '</td>
					<td class="number">', locale_number_format($Outstanding, $CurrDecimalPlaces), '</td>
					<td><input type="checkbox" name="Clear_', $i, '" /><input type="hidden" name="BankTrans_', $i, '" value="', $MyRow['banktransid'], '" /></td>
					<td colspan="2"><input type="text" maxlength="15" size="15" class="number" name="AmtClear_', $i, '" /></td>
				</tr>';
		}
		++$i;
	}
	//end of while loop
	echo '</tbody>
		</table>';

	echo '<div class="centre">
			<input type="hidden" name="RowCounter" value="', $i, '" />
			<input type="submit" name="Update" value="', _('Update Matching'), '" />
		</div>';
}
echo '</form>';
include ('includes/footer.php');
?>