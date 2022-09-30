<?php
include ('includes/session.php');

$Title = _('Bank Accounts'); // Screen identificator.
$ViewTopic = 'GeneralLedger'; // Filename's id in ManualContents.php's TOC.
$BookMark = 'BankAccounts'; // Anchor's id in the manual's html document.
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/bank.png" title="', _('Bank'), '" alt="" />', ' ', $Title, '
	</p>';

echo '<div class="page_help_text">
		', _('Update Bank Account details.  Account Code is for SWIFT or BSB type Bank Codes.  Set Default for Invoices to Currency Default  or Fallback Default to print Account details on Invoices (only one account should be set to Fall Back Default).') . '.
	</div>';

if (isset($_GET['SelectedBankAccount'])) {
	$SelectedBankAccount = $_GET['SelectedBankAccount'];
} elseif (isset($_POST['SelectedBankAccount'])) {
	$SelectedBankAccount = $_POST['SelectedBankAccount'];
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i = 1;

	$SQL = "SELECT count(accountcode)
			FROM bankaccounts WHERE accountcode='" . $_POST['AccountCode'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0] != 0 and !isset($SelectedBankAccount)) {
		$InputError = 1;
		prnMsg(_('The bank account code already exists in the database'), 'error');
	}
	if (mb_strlen($_POST['BankAccountName']) > 50) {
		$InputError = 1;
		prnMsg(_('The bank account name must be fifty characters or less long'), 'error');
	}
	if (trim($_POST['BankAccountName']) == '') {
		$InputError = 1;
		prnMsg(_('The bank account name may not be empty.'), 'error');
	}
	if (mb_strlen($_POST['BankAccountNumber']) > 50) {
		$InputError = 1;
		prnMsg(_('The bank account number must be fifty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['BankAddress']) > 50) {
		$InputError = 1;
		prnMsg(_('The bank address must be fifty characters or less long'), 'error');
	}

	if (isset($SelectedBankAccount) and $InputError != 1) {

		/*Check if there are already transactions against this account - cant allow change currency if there are*/

		$SQL = "SELECT banktransid FROM banktrans WHERE bankact='" . $SelectedBankAccount . "'";
		$BankTransResult = DB_query($SQL);
		if (DB_num_rows($BankTransResult) > 0) {
			$SQL = "UPDATE bankaccounts SET bankaccountname='" . $_POST['BankAccountName'] . "',
											bankaccountcode='" . $_POST['BankAccountCode'] . "',
											bankaccountnumber='" . $_POST['BankAccountNumber'] . "',
											bankaddress='" . $_POST['BankAddress'] . "',
											pettycash='" . $_POST['PettyCash'] . "',
											invoice ='" . $_POST['DefAccount'] . "',
											importformat='" . $_POST['ImportFormat'] . "'
										WHERE accountcode = '" . $SelectedBankAccount . "'";
			prnMsg(_('Note that it is not possible to change the currency of the account once there are transactions against it'), 'warn');
			echo '<br />';
		} else {
			$SQL = "UPDATE bankaccounts SET bankaccountname='" . $_POST['BankAccountName'] . "',
											bankaccountcode='" . $_POST['BankAccountCode'] . "',
											bankaccountnumber='" . $_POST['BankAccountNumber'] . "',
											bankaddress='" . $_POST['BankAddress'] . "',
											pettycash='" . $_POST['PettyCash'] . "',
											currcode ='" . $_POST['CurrCode'] . "',
											invoice ='" . $_POST['DefAccount'] . "',
											importformat='" . $_POST['ImportFormat'] . "'
										WHERE accountcode = '" . $SelectedBankAccount . "'";
		}

		$Msg = _('The bank account details have been updated');
	} elseif ($InputError != 1) {

		/*Selectedbank account is null cos no item selected on first time round so must be adding a    record must be submitting new entries in the new bank account form */

		$SQL = "INSERT INTO bankaccounts (accountcode,
										bankaccountname,
										bankaccountcode,
										bankaccountnumber,
										bankaddress,
										currcode,
										invoice,
										pettycash,
										importformat
									) VALUES ('" . $_POST['AccountCode'] . "',
										'" . $_POST['BankAccountName'] . "',
										'" . $_POST['BankAccountCode'] . "',
										'" . $_POST['BankAccountNumber'] . "',
										'" . $_POST['BankAddress'] . "',
										'" . $_POST['CurrCode'] . "',
										'" . $_POST['DefAccount'] . "',
										'" . $_POST['PettyCash'] . "',
										'" . $_POST['ImportFormat'] . "'
									)";
		$Msg = _('The new bank account has been entered');
	}

	//run the SQL from either of the above possibilites
	if ($InputError != 1) {
		$ErrMsg = _('The bank account could not be inserted or modified because');
		$DbgMsg = _('The SQL used to insert/modify the bank account details was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		prnMsg($Msg, 'success');
		echo '<br />';
		unset($_POST['AccountCode']);
		unset($_POST['BankAccountName']);
		unset($_POST['BankAccountCode']);
		unset($_POST['BankAccountNumber']);
		unset($_POST['BankAddress']);
		unset($_POST['CurrCode']);
		unset($_POST['DefAccount']);
		unset($_POST['PettyCash']);
		unset($_POST['ImportFormat']);
		unset($SelectedBankAccount);
	}

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'BankTrans'
	$SQL = "SELECT COUNT(bankact) AS accounts FROM banktrans WHERE banktrans.bankact='" . $SelectedBankAccount . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['accounts'] > 0) {
		$CancelDelete = 1;
		prnMsg(_('Cannot delete this bank account because transactions have been created using this account'), 'warn');
		echo '<br /> ' . _('There are') . ' ' . $MyRow['accounts'] . ' ' . _('transactions with this bank account code');

	}
	if (!$CancelDelete) {
		$SQL = "DELETE FROM bankaccounts WHERE accountcode='" . $SelectedBankAccount . "'";
		$Result = DB_query($SQL);
		prnMsg(_('Bank account deleted'), 'success');
	} //end if Delete bank account
	unset($_GET['delete']);
	unset($SelectedBankAccount);
}

/* Always show the list of accounts */
if (!isset($SelectedBankAccount)) {
	$SQL = "SELECT bankaccounts.accountcode,
					bankaccounts.bankaccountcode,
					chartmaster.accountname,
					bankaccountname,
					bankaccountnumber,
					bankaddress,
					currcode,
					invoice,
					pettycash,
					importformat
				FROM bankaccounts
				INNER JOIN chartmaster
					ON bankaccounts.accountcode = chartmaster.accountcode
				WHERE chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
				ORDER BY bankaccounts.bankaccountname";

	$ErrMsg = _('The bank accounts set up could not be retrieved because');
	$DbgMsg = _('The SQL used to retrieve the bank account details was') . '<br />' . $SQL;
	$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

	echo '<table>
			<tr>
				<th>', _('GL Account Code'), '</th>
				<th>', _('Bank Account Name'), '</th>
				<th>', _('Bank Account Code'), '</th>
				<th>', _('Bank Account Number'), '</th>
				<th>', _('Bank Address'), '</th>
				<th>', _('Import Format'), '</th>
				<th>', _('Currency'), '</th>
				<th>', _('Default for Invoices'), '</th>
				<th>', _('Bank or Cash Account'), '</th>
				<th colspan="2"></th>
			</tr>';

	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['invoice'] == 0) {
			$DefaultBankAccount = _('No');
		} elseif ($MyRow['invoice'] == 1) {
			$DefaultBankAccount = _('Fall Back Default');
		} elseif ($MyRow['invoice'] == 2) {
			$DefaultBankAccount = _('Currency Default');
		}
		if ($MyRow['pettycash'] == 0) {
			$PettyCash = _('Bank');
		} else {
			$PettyCash = _('Cash');
		}
		switch ($MyRow['importformat']) {
			case 'MT940-ING':
				$ImportFormat = 'ING MT940';
			break;
			case 'MT940-SCB':
				$ImportFormat = 'SCB MT940';
			break;
			default:
				$ImportFormat = '';
		}

		echo '<tr class="striped_row">
				<td>', $MyRow['accountcode'], '<br />', $MyRow['accountname'], '</td>
				<td>', $MyRow['bankaccountname'], 's</td>
				<td>', $MyRow['bankaccountcode'], '</td>
				<td>', $MyRow['bankaccountnumber'], '</td>
				<td>', $MyRow['bankaddress'], '</td>
				<td>', $ImportFormat, '</td>
				<td>', $MyRow['currcode'], '</td>
				<td>', $DefaultBankAccount, '</td>
				<td>', $PettyCash, '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedBankAccount=', urlencode($MyRow['accountcode']), '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedBankAccount=', urlencode($MyRow['accountcode']), '&amp;delete=1" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this bank account?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';
	}
	//END WHILE LIST LOOP
	echo '</table>';
}

if (isset($SelectedBankAccount)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Show All Bank Accounts Defined'), '</a>
		</div>';
}

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if (isset($SelectedBankAccount) and !isset($_GET['delete'])) {
	//editing an existing bank account  - not deleting
	$SQL = "SELECT accountcode,
					bankaccountname,
					bankaccountcode,
					bankaccountnumber,
					bankaddress,
					currcode,
					invoice,
					pettycash,
					importformat
			FROM bankaccounts
			WHERE bankaccounts.accountcode='" . $SelectedBankAccount . "'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['AccountCode'] = $MyRow['accountcode'];
	$_POST['BankAccountName'] = $MyRow['bankaccountname'];
	$_POST['BankAccountCode'] = $MyRow['bankaccountcode'];
	$_POST['BankAccountNumber'] = $MyRow['bankaccountnumber'];
	$_POST['BankAddress'] = $MyRow['bankaddress'];
	$_POST['CurrCode'] = $MyRow['currcode'];
	$_POST['DefAccount'] = $MyRow['invoice'];
	$_POST['PettyCash'] = $MyRow['pettycash'];
	$_POST['ImportFormat'] = $MyRow['importformat'];

	echo '<input type="hidden" name="SelectedBankAccount" value="', $SelectedBankAccount, '" />';
	echo '<input type="hidden" name="AccountCode" value="', $_POST['AccountCode'], '" />';
	echo '<fieldset>
			<legend>', _('Edit Bank Account Details'), '</legend>
			<field>
				<label for="AccountCode">', _('Bank Account GL Code'), ':</label>
				<div class="fieldtext">', $_POST['AccountCode'], '</div>
			</field>';
} else { //end of if $Selectedbank account only do the else when a new record is being entered
	echo '<fieldset>
			<legend>', _('New Bank Account Details'), '</legend>
			<field>
				<label for="AccountCode">', _('Bank Account GL Code'), ':</label>';
	GLSelect(0, 'AccountCode');
	echo '<fieldhelp>', _('Select the General Ledger code to use for this bank account.'), '</fieldhelp>
		</field>';
}

// Check if details exist, if not set some defaults
if (!isset($_POST['BankAccountName'])) {
	$_POST['BankAccountName'] = '';
}
if (!isset($_POST['BankAccountNumber'])) {
	$_POST['BankAccountNumber'] = '';
}
if (!isset($_POST['BankAccountCode'])) {
	$_POST['BankAccountCode'] = '';
}
if (!isset($_POST['BankAddress'])) {
	$_POST['BankAddress'] = '';
}
if (!isset($_POST['ImportFormat'])) {
	$_POST['ImportFormat'] = '';
}
echo '<field>
		<label for="BankAccountName">', _('Bank Account Name'), ': </label>
		<input type="text" name="BankAccountName" value="', $_POST['BankAccountName'], '" autofocus="autofocus" size="40" required="required" maxlength="50" />
		<fieldhelp>', _('The name that this bank account will be called. Does not have to be the same as the General Ledger description.'), '</fieldhelp>
	</field>
	<field>
		<label for="BankAccountCode">', _('Bank Account Code'), ': </label>
		<input type="text" name="BankAccountCode" value="', $_POST['BankAccountCode'], '" size="40" maxlength="50" />
		<fieldhelp>', _('The code that the account is known by at the bank.'), '</fieldhelp>
	</field>
	<field>
		<label  for="BankAccountNumber">', _('Bank Account Number'), ': </label>
		<input type="text" name="BankAccountNumber" value="', $_POST['BankAccountNumber'], '" size="40" maxlength="50" />
		<fieldhelp>', _('The number that the account is known by at the bank.'), '</fieldhelp>
	</field>
	<field>
		<label for="BankAddress">', _('Bank Address'), ': </label>
		<input type="text" name="BankAddress" value="', $_POST['BankAddress'], '" size="40" maxlength="50" />
		<fieldhelp>', _('The address of the bank where the account is held.'), '</fieldhelp>
	</field>
 	<field>
		<label for="ImportFormat">', _('Transaction Import File Format'), ': </label>
		<select name="ImportFormat">
			<option ', ($_POST['ImportFormat'] == '' ? 'selected="selected"' : '') . ' value="">' . _('N/A') . '</option>
			<option ', ($_POST['ImportFormat'] == 'MT940-SCB' ? 'selected="selected"' : '') . ' value="MT940-SCB">' . _('MT940 - Siam Comercial Bank Thailand') . '</option>
			<option ', ($_POST['ImportFormat'] == 'MT940-ING' ? 'selected="selected"' : '') . ' value="MT940-ING">' . _('MT940 - ING Bank Netherlands') . '</option>
			<option ', ($_POST['ImportFormat'] == 'GIFTS' ? 'selected="selected"' : '') . ' value="GIFTS">' . _('GIFTS - Bank of New Zealand') . '</option>
		</select>
		<fieldhelp>', _('The electronic format the bank uses (if any) to export your statement.'), '</fieldhelp>
	</field>
	<field>
		<label for="CurrCode">' . _('Currency Of Account') . ': </label>
		<select name="CurrCode">';

if (!isset($_POST['CurrCode']) or $_POST['CurrCode'] == '') {
	$_POST['CurrCode'] = $_SESSION['CompanyRecord']['currencydefault'];
}
$Result = DB_query("SELECT currabrev,
							currency
					FROM currencies");

while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['currabrev'] == $_POST['CurrCode']) {
		echo '<option selected="selected" value="' . $MyRow['currabrev'] . '">' . $MyRow['currabrev'] . '</option>';
	} else {
		echo '<option value="' . $MyRow['currabrev'] . '">' . $MyRow['currabrev'] . '</option>';
	}
} //end while loop
echo '</select>
	<fieldhelp>', _('The currecy of the bank account.'), '</fieldhelp>
</field>';

echo '<field>
		<label for="DefAccount">' . _('Default for Invoices') . ': </label>
		<select name="DefAccount">';

if (!isset($_POST['DefAccount']) or $_POST['DefAccount'] == '') {
	$_POST['DefAccount'] = $_SESSION['CompanyRecord']['currencydefault'];
}

if (isset($SelectedBankAccount)) {
	$SQL = "SELECT invoice FROM bankaccounts where accountcode='" . $SelectedBankAccount . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['invoice'] == 1) {
			echo '<option selected="selected" value="1">' . _('Fall Back Default') . '</option>
					<option value="2">' . _('Currency Default') . '</option>
					<option value="0">' . _('No') . '</option>';
		} elseif ($MyRow['invoice'] == 2) {
			echo '<option value="0">' . _('No') . '</option>
					<option selected="selected" value="2">' . _('Currency Default') . '</option>
					<option value="1">' . _('Fall Back Default') . '</option>';
		} else {
			echo '<option selected="selected" value="0">' . _('No') . '</option>
					<option  value="2">' . _('Currency Default') . '</option>
					<option value="1">' . _('Fall Back Default') . '</option>';
		}
	} //end while loop

} else {
	echo '<option value="1">' . _('Fall Back Default') . '</option>
			<option  value="2">' . _('Currency Default') . '</option>
			<option value="0">' . _('No') . '</option>';
}

echo '</select>
	<fieldhelp>', _('Is this the account that will be printed on invoices.'), '</fieldhelp>
</field>';

if (!isset($_POST['PettyCash'])) {
	$_POST['PettyCash'] = 0;
}
echo '<field>
		<label  for="PettyCash">' . _('Is Account for Cash or Bank') . '</label>
		<select name="PettyCash">';
$BankOrCash[0] = _('Bank');
$BankOrCash[1] = _('Cash');
foreach ($BankOrCash as $Code => $Type) {
	if ($Code == $_POST['PettyCash']) {
		echo '<option value="' . $Code . '" selected="selected">' . $Type . '</option>';
	} else {
		echo '<option value="' . $Code . '">' . $Type . '</option>';
	}
}
echo '</select>
		<fieldhelp>', _('Is this a petty cash account or a bank account.'), '</fieldhelp>
	</field>
</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="' . _('Enter Information') . '" />
	</div>';
echo '</form>';
include ('includes/footer.php');
?>