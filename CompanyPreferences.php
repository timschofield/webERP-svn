<?php
include ('includes/session.php');

$Title = _('Company Preferences');
/* Manual links before header.php */
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'CompanyParameters';
include ('includes/header.php');

//initialise no input errors assumed initially before we test
$InputError = 0;

if (isset($_POST['submit'])) {

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (mb_strlen($_POST['CoyName']) > 50 or mb_strlen($_POST['CoyName']) == 0) {
		$InputError = 1;
		prnMsg(_('The company name must be entered and be fifty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice1']) > 40) {
		$InputError = 1;
		prnMsg(_('The Line 1 of the address must be forty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice2']) > 40) {
		$InputError = 1;
		prnMsg(_('The Line 2 of the address must be forty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice3']) > 40) {
		$InputError = 1;
		prnMsg(_('The Line 3 of the address must be forty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice4']) > 40) {
		$InputError = 1;
		prnMsg(_('The Line 4 of the address must be forty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice5']) > 20) {
		$InputError = 1;
		prnMsg(_('The Line 5 of the address must be twenty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['RegOffice6']) > 15) {
		$InputError = 1;
		prnMsg(_('The Line 6 of the address must be fifteen characters or less long'), 'error');
	}
	if (mb_strlen($_POST['Telephone']) > 25) {
		$InputError = 1;
		prnMsg(_('The telephone number must be 25 characters or less long'), 'error');
	}
	if (mb_strlen($_POST['Fax']) > 25) {
		$InputError = 1;
		prnMsg(_('The fax number must be 25 characters or less long'), 'error');
	}
	if (mb_strlen($_POST['Email']) > 55) {
		$InputError = 1;
		prnMsg(_('The email address must be 55 characters or less long'), 'error');
	}
	if (mb_strlen($_POST['Email']) > 0 and !IsEmailAddress($_POST['Email'])) {
		$InputError = 1;
		prnMsg(_('The email address is not correctly formed'), 'error');
	}

	if ($InputError != 1) {

		$CompanySQL = "SELECT coycode FROM companies";
		$CompanyResult = DB_query($CompanySQL);
		if (DB_num_rows($CompanyResult) == 0) {
			$SQL = "INSERT INTO companies (coycode,
											coyname,
											companynumber,
											gstno,
											regoffice1,
											regoffice2,
											regoffice3,
											regoffice4,
											regoffice5,
											regoffice6,
											telephone,
											fax,
											email,
											currencydefault,
											npo,
											debtorsact,
											pytdiscountact,
											creditorsact,
											payrollact,
											grnact,
											commissionsact,
											exchangediffact,
											purchasesexchangediffact,
											retainedearnings,
											gllink_debtors,
											gllink_creditors,
											gllink_stock,
											freightact
										) VALUES (
											1,
											'" . $_POST['CoyName'] . "',
											'" . $_POST['CompanyNumber'] . "',
											'" . $_POST['GSTNo'] . "',
											'" . $_POST['RegOffice1'] . "',
											'" . $_POST['RegOffice2'] . "',
											'" . $_POST['RegOffice3'] . "',
											'" . $_POST['RegOffice4'] . "',
											'" . $_POST['RegOffice5'] . "',
											'" . $_POST['RegOffice6'] . "',
											'" . $_POST['Telephone'] . "',
											'" . $_POST['Fax'] . "',
											'" . $_POST['Email'] . "',
											'" . $_POST['CurrencyDefault'] . "',
											'" . $_POST['IsNPO'] . "',
											'" . $_POST['DebtorsAct'] . "',
											'" . $_POST['PytDiscountAct'] . "',
											'" . $_POST['CreditorsAct'] . "',
											'" . $_POST['PayrollAct'] . "',
											'" . $_POST['GRNAct'] . "',
											'" . $_POST['CommAct'] . "',
											'" . $_POST['ExchangeDiffAct'] . "',
											'" . $_POST['PurchasesExchangeDiffAct'] . "',
											'" . $_POST['RetainedEarnings'] . "',
											'" . $_POST['GLLink_Debtors'] . "',
											'" . $_POST['GLLink_Creditors'] . "',
											'" . $_POST['GLLink_Stock'] . "',
											'" . $_POST['FreightAct'] . "'
										)";
		} else {

			$SQL = "UPDATE companies SET coyname='" . $_POST['CoyName'] . "',
										companynumber = '" . $_POST['CompanyNumber'] . "',
										gstno='" . $_POST['GSTNo'] . "',
										regoffice1='" . $_POST['RegOffice1'] . "',
										regoffice2='" . $_POST['RegOffice2'] . "',
										regoffice3='" . $_POST['RegOffice3'] . "',
										regoffice4='" . $_POST['RegOffice4'] . "',
										regoffice5='" . $_POST['RegOffice5'] . "',
										regoffice6='" . $_POST['RegOffice6'] . "',
										telephone='" . $_POST['Telephone'] . "',
										fax='" . $_POST['Fax'] . "',
										email='" . $_POST['Email'] . "',
										currencydefault='" . $_POST['CurrencyDefault'] . "',
										npo='" . $_POST['IsNPO'] . "',
										debtorsact='" . $_POST['DebtorsAct'] . "',
										pytdiscountact='" . $_POST['PytDiscountAct'] . "',
										creditorsact='" . $_POST['CreditorsAct'] . "',
										payrollact='" . $_POST['PayrollAct'] . "',
										grnact='" . $_POST['GRNAct'] . "',
										commissionsact='" . $_POST['CommAct'] . "',
										exchangediffact='" . $_POST['ExchangeDiffAct'] . "',
										purchasesexchangediffact='" . $_POST['PurchasesExchangeDiffAct'] . "',
										retainedearnings='" . $_POST['RetainedEarnings'] . "',
										gllink_debtors='" . $_POST['GLLink_Debtors'] . "',
										gllink_creditors='" . $_POST['GLLink_Creditors'] . "',
										gllink_stock='" . $_POST['GLLink_Stock'] . "',
										freightact='" . $_POST['FreightAct'] . "'
									WHERE coycode=1";
		}

		$ErrMsg = _('The company preferences could not be updated because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(_('Company preferences updated'), 'success');

		/* Alter the exchange rates in the currencies table */

		/* Get default currency rate */
		$SQL = "SELECT rate from currencies WHERE currabrev='" . $_POST['CurrencyDefault'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		$NewCurrencyRate = $MyRow[0];

		/* Set new rates */
		$SQL = "UPDATE currencies SET rate=rate/" . $NewCurrencyRate;
		$ErrMsg = _('Could not update the currency rates');
		$Result = DB_query($SQL, $ErrMsg);

		/* End of update currencies */

		$ForceConfigReload = True; // Required to force a load even if stored in the session vars
		include ('includes/GetConfig.php');
		$ForceConfigReload = False;

	} else {
		prnMsg(_('Validation failed') . ', ' . _('no updates or deletes took place'), 'warn');
	}

}
/* end of if submit */

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if ($InputError != 1) {
	$SQL = "SELECT coyname,
					gstno,
					companynumber,
					regoffice1,
					regoffice2,
					regoffice3,
					regoffice4,
					regoffice5,
					regoffice6,
					telephone,
					fax,
					email,
					currencydefault,
					npo,
					debtorsact,
					pytdiscountact,
					creditorsact,
					payrollact,
					grnact,
					commissionsact,
					exchangediffact,
					purchasesexchangediffact,
					retainedearnings,
					gllink_debtors,
					gllink_creditors,
					gllink_stock,
					freightact
				FROM companies
				WHERE coycode=1";

	$ErrMsg = _('The company preferences could not be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($Result);

	$_POST['CoyName'] = $MyRow['coyname'];
	$_POST['GSTNo'] = $MyRow['gstno'];
	$_POST['CompanyNumber'] = $MyRow['companynumber'];
	$_POST['RegOffice1'] = $MyRow['regoffice1'];
	$_POST['RegOffice2'] = $MyRow['regoffice2'];
	$_POST['RegOffice3'] = $MyRow['regoffice3'];
	$_POST['RegOffice4'] = $MyRow['regoffice4'];
	$_POST['RegOffice5'] = $MyRow['regoffice5'];
	$_POST['RegOffice6'] = $MyRow['regoffice6'];
	$_POST['Telephone'] = $MyRow['telephone'];
	$_POST['Fax'] = $MyRow['fax'];
	$_POST['Email'] = $MyRow['email'];
	$_POST['CurrencyDefault'] = $MyRow['currencydefault'];
	$_POST['IsNPO'] = $MyRow['npo'];
	$_POST['DebtorsAct'] = $MyRow['debtorsact'];
	$_POST['PytDiscountAct'] = $MyRow['pytdiscountact'];
	$_POST['CreditorsAct'] = $MyRow['creditorsact'];
	$_POST['PayrollAct'] = $MyRow['payrollact'];
	$_POST['GRNAct'] = $MyRow['grnact'];
	$_POST['CommAct'] = $MyRow['commissionsact'];
	$_POST['ExchangeDiffAct'] = $MyRow['exchangediffact'];
	$_POST['PurchasesExchangeDiffAct'] = $MyRow['purchasesexchangediffact'];
	$_POST['RetainedEarnings'] = $MyRow['retainedearnings'];
	$_POST['GLLink_Debtors'] = $MyRow['gllink_debtors'];
	$_POST['GLLink_Creditors'] = $MyRow['gllink_creditors'];
	$_POST['GLLink_Stock'] = $MyRow['gllink_stock'];
	$_POST['FreightAct'] = $MyRow['freightact'];
}

if (DB_num_rows($Result) == 0) {
	echo '<div class="page_help_text">', _('As this is the first time that the system has been used, you must first fill out the company details.'), '<br />', _('Once you have filled in all the details, click on the button at the bottom of the screen'), '</div>';
	include ('companies/' . $_SESSION['DatabaseName'] . '/Companies.php');
	$_POST['CoyName'] = $CompanyName[$_SESSION['DatabaseName']];
}
echo '<fieldset>
		<legend>', _('Edit Company Details'), '</legend>';

echo '<field>
		<label for="CoyName">', _('Name'), ' (', _('to appear on reports'), '):</label>
		<input type="text" name="CoyName" value="', stripslashes($_POST['CoyName']), '" size="52" required="required" autofocus="autofocus" maxlength="50" />
		<fieldhelp>', _('The official name of the company that will appear throughout KwaMoja, and on all reports.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="CompanyNumber">', _('Official Company Number'), ':</label>
		<input type="text" name="CompanyNumber" value="', $_POST['CompanyNumber'], '" size="22" maxlength="20" />
		<fieldhelp>', _('The official government registration number for the company, allocated on incorporation.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="GSTNo">', _('Tax Authority Reference'), ':</label>
		<input type="text" name="GSTNo" value="', stripslashes($_POST['GSTNo']), '" size="22" maxlength="20" />
		<fieldhelp>', _('The official number allocated by the tax authority of the country where the company is based.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RegOffice1">', _('Address Line 1'), ':</label>
		<input type="text" name="RegOffice1" size="42" maxlength="40" value="', stripslashes($_POST['RegOffice1']), '" />
		<fieldhelp>', _('The first line of the address for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RegOffice2">', _('Address Line 2'), ':</label>
		<input type="text" name="RegOffice2" size="42" maxlength="40" value="', stripslashes($_POST['RegOffice2']), '" />
		<fieldhelp>', _('The second line of the address for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RegOffice3">', _('Address Line 3'), ':</label>
		<input type="text" name="RegOffice3" size="42" maxlength="40" value="', stripslashes($_POST['RegOffice3']), '" />
		<fieldhelp>', _('The third line of the address for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RegOffice4">', _('Address Line 4'), ':</label>
		<input type="text" name="RegOffice4" size="42" maxlength="40" value="', stripslashes($_POST['RegOffice4']), '" />
		<fieldhelp>', _('The fourth line of the address for the registered office of the company.'), '</fieldhelp>
</field>';

echo '<field>
		<label for="RegOffice5">', _('Address Line 5'), ':</label>
		<input type="text" name="RegOffice5" size="22" maxlength="20" value="', stripslashes($_POST['RegOffice5']), '" />
		<fieldhelp>', _('The fifth line of the address for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RegOffice6">', _('Address Line 6'), ':</label>
		<input type="text" name="RegOffice6" size="17" maxlength="15" value="', stripslashes($_POST['RegOffice6']), '" />
		<fieldhelp>', _('The sixth line of the address for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Telephone">', _('Telephone Number'), ':</label>
		<input type="tel" name="Telephone" size="26" maxlength="25" value="', $_POST['Telephone'], '" />
		<fieldhelp>', _('The telephone number for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Fax">', _('Facsimile Number'), ':</label>
		<input type="tel" name="Fax" size="26" maxlength="25" value="', $_POST['Fax'], '" />
		<fieldhelp>', _('The fax number for the registered office of the company.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="Email">', _('Email Address'), ':</label>
		<input type="email" name="Email" size="50" maxlength="55" value="', $_POST['Email'], '" />
		<fieldhelp>', _('The email address for the registered office of the company.'), '</fieldhelp>
	</field>';

$Result = DB_query("SELECT currabrev, currency FROM currencies");

echo '<field>
		<label for="CurrencyDefault">', _('Home Currency'), ':</label>
		<select id="CurrencyDefault" name="CurrencyDefault">';

while ($MyRow = DB_fetch_array($Result)) {
	if ($_POST['CurrencyDefault'] == $MyRow['currabrev']) {
		echo '<option selected="selected" value="', $MyRow['currabrev'], '">', _($MyRow['currency']), '</option>';
	} else {
		echo '<option value="', $MyRow['currabrev'], '">', _($MyRow['currency']), '</option>';
	}
} //end while loop
echo '</select>
	<fieldhelp>', _('The base currency that the company will use for the general ledger.'), '</fieldhelp>
</field>';

echo '<field>
		<label for="IsNPO">', _('Is the organisation an NPO?'), ':</label>
		<select name="IsNPO">';

if ($_POST['IsNPO'] == '0') {
	echo '<option selected="selected" value="0">', _('No'), '</option>';
	echo '<option value="1">', _('Yes'), '</option>';
} else {
	echo '<option selected="selected" value="1">', _('Yes'), '</option>';
	echo '<option value="0">', _('No'), '</option>';
}

echo '</select>
	<fieldhelp>', _('Is the organisation a not for profit organisation.'), '</fieldhelp>
</field>';

echo '<field>
		<label for="DebtorsAct">', _('Debtors Control GL Account'), ':</label>';
GLSelect(0, 'DebtorsAct');
echo '<fieldhelp>', _('The general ledger account to act as the control for the accounts receivable transactions. This account should agree with the total aged debtors report.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="CreditorsAct">', _('Creditors Control GL Account'), ':</label>';
GLSelect(0, 'CreditorsAct');
echo '<fieldhelp>', _('The general ledger account to act as the control for the accounts payable transactions. This account should agree with the total aged creditors report.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="PayrollAct">', _('Payroll Net Pay Clearing GL Account'), ':</label>';
GLSelect(0, 'PayrollAct');
echo '<fieldhelp>', _('The general ledger account to act as the control for the payroll transactions.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="GRNAct">', _('Goods Received Clearing GL Account'), ':</label>';
GLSelect(0, 'GRNAct');
echo '<fieldhelp>', _('The general ledger account to act as the clearing account for Goods Received. When the GRN is raised an entry is posted here, and when the supplier invoice is posted, it will contra off this entry. This account should always reconcile back to zero.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="CommAct">', _('Sales Commission Accruals Account'), ':</label>';
GLSelect(0, 'CommAct');
echo '<fieldhelp>', _('The general ledger account to act as the sales commission accruals account. Commission earned but not paid will be posted here, and cleared when the commission is paid.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="RetainedEarnings">', _('Retained Earning Clearing GL Account'), ':</label>';
GLSelect(0, 'RetainedEarnings');
echo '<fieldhelp>', _('The general ledger account to act as the retained earnings account with the accumulated Profit/Loss. This account is managed by KwaMoja and once set up should not be accessed directly.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="FreightAct">', _('Freight Re-charged GL Account'), ':</label>';
GLSelect(1, 'FreightAct');
echo '<fieldhelp>', _('The general ledger account where the freight charges will get posted to.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="ExchangeDiffAct">', _('Sales Exchange Variances GL Account'), ':</label>';
GLSelect(1, 'ExchangeDiffAct');
echo '<fieldhelp>', _('The general ledger account where the profit/loss on currency exchange for sales transactions will be posted.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="PurchasesExchangeDiffAct">', _('Purchases Exchange Variances GL Account'), ':</label>';
GLSelect(1, 'PurchasesExchangeDiffAct');
echo '<fieldhelp>', _('The general ledger account where the profit/loss on currency exchange for purchase transactions will be posted.'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="PytDiscountAct">', _('Payment Discount GL Account'), ':</label>';
GLSelect(1, 'PytDiscountAct');
echo '<fieldhelp>', _('The general ledger account where the discount on purchase transactions will be posted.'), '</fieldhelp>
	</field>';

DB_data_seek($Result, 0);

echo '<field>
		<label for="GLLink_Debtors">', _('Create GL entries for AR transactions'), ':</label>
		<select name="GLLink_Debtors">';

if ($_POST['GLLink_Debtors'] == 0) {
	echo '<option selected="selected" value="0">', _('No'), '</option>';
	echo '<option value="1">', _('Yes'), '</option>';
} else {
	echo '<option selected="selected" value="1">', _('Yes'), '</option>';
	echo '<option value="0">', _('No'), '</option>';
}

echo '</select>
	<fieldhelp>', _('When an accounts receivable transaction is done, should KwaMoja create the required General Ledger entries'), '</fieldhelp>
</field>';

echo '<field>
		<label for="GLLink_Creditors">', _('Create GL entries for AP transactions'), ':</label>
		<select name="GLLink_Creditors">';

if ($_POST['GLLink_Creditors'] == 0) {
	echo '<option selected="selected" value="0">', _('No'), '</option>';
	echo '<option value="1">', _('Yes'), '</option>';
} else {
	echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	echo '<option value="0">', _('No'), '</option>';
}

echo '</select>
	<fieldhelp>', _('When an accounts payable transaction is done, should KwaMoja create the required General Ledger entries'), '</fieldhelp>
</field>';

echo '<field>
		<label for="GLLink_Stock">', _('Create GL entries for stock transactions'), ':</label>
		<select name="GLLink_Stock">';

if ($_POST['GLLink_Stock'] == '0') {
	echo '<option selected="selected" value="0">', _('No'), '</option>';
	echo '<option value="1">', _('Yes'), '</option>';
} else {
	echo '<option selected="selected" value="1">', _('Yes'), '</option>';
	echo '<option value="0">', _('No'), '</option>';
}

echo '</select>
	<fieldhelp>', _('When an inventory transaction is done, should KwaMoja create the required General Ledger entries'), '</fieldhelp>
</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="', _('Update'), '" />
	</div>';

echo '</form>';

include ('includes/footer.php');
?>