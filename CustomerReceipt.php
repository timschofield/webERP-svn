<?php
include ('includes/DefineReceiptClass.php');
include ('includes/session.php');

$Title = _('Receipt Entry');

/* Manual links before header.php */
if ($_GET['Type'] == 'GL') {
	$ViewTopic = 'GeneralLedger';
	$BookMark = 'GLReceipts';
} else {
	$ViewTopic = 'ARTransactions';
	$BookMark = 'CustomerReceipts';
}
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.inc');

if (empty($_GET['identifier'])) {
	$Identifier = date('U');
} else {
	$Identifier = $_GET['identifier'];
}

$Msg = '';

if (isset($_GET['NewReceipt'])) {
	unset($_SESSION['ReceiptBatch' . $Identifier]->Items);
	unset($_SESSION['ReceiptBatch' . $Identifier]);
	unset($_SESSION['CustomerRecord' . $Identifier]);
}

if (isset($_POST['Cancel'])) {
	$Cancel = 1;
}

if (isset($_GET['Type']) and $_GET['Type'] == 'GL') {
	$_POST['GLEntry'] = 1;
}

if (isset($_GET['Currency'])) {
	$_POST['Currency'] = $_GET['Currency'];
}

if (isset($_GET['PreviousCurrency'])) {
	$_POST['PreviousCurrency'] = $_GET['PreviousCurrency'];
}

if (isset($_GET['CustomerID'])) {
	$_POST['CustomerID'] = $_GET['CustomerID'];
	$SQL = "SELECT currcode FROM debtorsmaster WHERE debtorno='" . $_POST['CustomerID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['Currency'] = $MyRow['currcode'];
	$_POST['DateBanked'] = Date($_SESSION['DefaultDateFormat']);
	$_POST['BankTransRef'] = '';
	$_POST['BatchNarrative'] = '';
	$_SESSION['ReceiptBatch' . $Identifier] = new Receipt_Batch;
}

if ((isset($_POST['BatchInput']) and $_POST['BankAccount'] == '') or (isset($_POST['Process']) and $_POST['BankAccount'] == '')) {

	echo '<br />';
	prnMsg(_('A bank account must be selected for this receipt'), 'warn');
	$BankAccountEmpty = true;
} else if (isset($_GET['NewReceipt']) or isset($_GET['CustomerID'])) {
	$BankAccountEmpty = true;
} else {
	$BankAccountEmpty = false;
}

include ('includes/GetPaymentMethods.php');

if (isset($_POST['Search']) and $_POST['BankAccount'] == '') {
	unset($_POST['Search']);
	unset($_SESSION['ReceiptBatch' . $Identifier]);
	prnMsg(_('You must select the bank account to use before searching for the customer'), 'warn');
}

if (!isset($_GET['Delete']) and isset($_SESSION['ReceiptBatch' . $Identifier]) or isset($_GET['CustomerID'])) {
	//always process a header update unless deleting an item
	if (isset($_POST['BankAccount'])) {
		$_SESSION['ReceiptBatch' . $Identifier]->Account = $_POST['BankAccount'];
		/*Get the bank account currency and set that too */

		$SQL = "SELECT bankaccountname,
						currcode,
						decimalplaces
				FROM bankaccounts
				INNER JOIN currencies
					ON bankaccounts.currcode=currencies.currabrev
				WHERE accountcode='" . $_POST['BankAccount'] . "'";

		$ErrMsg = _('The bank account name cannot be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($Result) == 1) {
			$MyRow = DB_fetch_array($Result);
			$_SESSION['ReceiptBatch' . $Identifier]->BankAccountName = $MyRow['bankaccountname'];
			$_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency = $MyRow['currcode'];
			$_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
			unset($Result);
		} elseif (DB_num_rows($Result) == 0 and !$BankAccountEmpty) {
			prnMsg(_('The bank account number') . ' ' . $_POST['BankAccount'] . ' ' . _('is not set up as a bank account'), 'error');
			include ('includes/footer.php');
			exit;
		}
	}
	if (!is_date($_POST['DateBanked'])) {
		$_POST['DateBanked'] = Date($_SESSION['DefaultDateFormat']);
	}
	$_SESSION['ReceiptBatch' . $Identifier]->DateBanked = $_POST['DateBanked'];
	if (isset($_POST['ExRate']) and $_POST['ExRate'] != '') {
		if (is_numeric(filter_number_format($_POST['ExRate']))) {
			$_SESSION['ReceiptBatch' . $Identifier]->ExRate = filter_number_format($_POST['ExRate']);
		} else {
			prnMsg(_('The exchange rate entered should be numeric'), 'warn');
		}
	}
	if (isset($_POST['FunctionalExRate']) and $_POST['FunctionalExRate'] != '') {
		if (is_numeric(filter_number_format($_POST['FunctionalExRate']))) {
			$_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate = filter_number_format($_POST['FunctionalExRate']); //ex rate between receipt currency and account currency

		} else {
			prnMsg(_('The functional exchange rate entered should be numeric'), 'warn');
		}
	}

	if (!isset($_POST['ReceiptType'])) {
		$_POST['ReceiptType'] = '';
	}

	$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType = $_POST['ReceiptType'];

	if (!isset($_POST['Currency'])) {
		$_POST['Currency'] = $_SESSION['CompanyRecord']['currencydefault'];
	}

	if ($_SESSION['ReceiptBatch' . $Identifier]->Currency != $_POST['Currency']) {

		$_SESSION['ReceiptBatch' . $Identifier]->Currency = $_POST['Currency']; //receipt currency
		/*Now customer receipts entered using the previous currency need to be ditched
		 and a warning message displayed if there were some customer receipted entered */
		if (count($_SESSION['ReceiptBatch' . $Identifier]->Items) > 0) {
			unset($_SESSION['ReceiptBatch' . $Identifier]->Items);
			prnMsg(_('Changing the currency of the receipt means that existing entries need to be re-done - only customers trading in the selected currency can be selected'), 'warn');
		}

	}

	if ($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency == $_SESSION['CompanyRecord']['currencydefault']) {
		$_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate = 1;
		$SuggestedFunctionalExRate = 1;
	} elseif (!$BankAccountEmpty) {
		/*To illustrate the rates required
		Take an example functional currency NZD receipt in USD from an AUD bank account
		1 NZD = 0.80 USD
		1 NZD = 0.90 AUD
		The FunctionalExRate = 0.90 - the rate between the functional currency and the bank account currency
		The receipt ex rate is the rate at which one can sell the received currency and purchase the bank account currency
		or 0.8/0.9 = 0.88889
		*/

		/*Get suggested FunctionalExRate between the bank account currency and the home (functional) currency */
		$Result = DB_query("SELECT rate, decimalplaces FROM currencies WHERE currabrev='" . $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency . "'");
		$MyRow = DB_fetch_array($Result);
		$SuggestedFunctionalExRate = $MyRow['rate'];
		$_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];

	} //end else account currency != functional currency
	if ($_POST['Currency'] == $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency) {
		$_SESSION['ReceiptBatch' . $Identifier]->ExRate = 1; //ex rate between receipt currency and account currency
		$SuggestedExRate = 1;
	} elseif (isset($_POST['Currency']) and !isset($_GET['CustomerID'])) {
		/*Get the exchange rate between the functional currency and the receipt currency*/
		$Result = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'");
		$MyRow = DB_fetch_array($Result);
		$TableExRate = $MyRow['rate']; //this is the rate of exchange between the functional currency and the receipt currency
		/*Calculate cross rate to suggest appropriate exchange rate between receipt currency and account currency */
		$SuggestedExRate = $TableExRate / $SuggestedFunctionalExRate;
	}

	$_SESSION['ReceiptBatch' . $Identifier]->BankTransRef = $_POST['BankTransRef'];
	$_SESSION['ReceiptBatch' . $Identifier]->Narrative = $_POST['BatchNarrative'];

} elseif (isset($_GET['Delete'])) {
	/* User hit delete the receipt entry from the batch */
	$_SESSION['ReceiptBatch' . $Identifier]->remove_receipt_item($_GET['Delete']);
} else { //it must be a new receipt batch
	$_SESSION['ReceiptBatch' . $Identifier] = new Receipt_Batch;
}
if (!isset($_SESSION['ReceiptBatch' . $Identifier]->Currency) or $_SESSION['ReceiptBatch' . $Identifier]->Currency == '') {
	$_SESSION['ReceiptBatch' . $Identifier]->Currency = $_SESSION['CompanyRecord']['currencydefault'];
}

if (isset($_POST['Process'])) { //user hit submit a new entry to the receipt batch
	if (!isset($_POST['GLCode'])) {
		$_POST['GLCode'] = '';
	}
	if (!isset($_POST['tag'])) {
		$_POST['tag'] = '';
	}
	if (!isset($_POST['CustomerID'])) {
		$_POST['CustomerID'] = '';
	}
	if (!isset($_POST['CustomerName'])) {
		$_POST['CustomerName'] = '';
	}

	if ($_POST['Discount'] == 0 and $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['percentdiscount'] > 0) {
		if (isset($_GET['Type']) and $_GET['Type'] == 'Customer') {
			$_POST['Discount'] = $_POST['Amount'] * $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['percentdiscount'];
		}
	}

	if ($_POST['GLCode'] == '' and $_GET['Type'] == 'GL') {
		prnMsg(_('No General Ledger code has been chosen') . ' - ' . _('so this GL analysis item could not be added'), 'warn');

	} else {
		$AllowThisPosting = true;
		if ($_SESSION['ProhibitJournalsToControlAccounts'] == 1) {
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == '1' and $_POST['GLCode'] == $_SESSION['CompanyRecord']['debtorsact']) {
				prnMsg(_('Payments involving the debtors control account cannot be entered. The general ledger debtors ledger (AR) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration'), 'warn');
				$AllowThisPosting = false;
			}
			if ($_SESSION['CompanyRecord']['gllink_creditors'] == '1' and ($_POST['GLCode'] == $_SESSION['CompanyRecord']['creditorsact'] or $_POST['GLCode'] == $_SESSION['CompanyRecord']['grnact'])) {
				prnMsg(_('Payments involving the creditors control account or the GRN suspense account cannot be entered. The general ledger creditors ledger (AP) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration'), 'warn');
				$AllowThisPosting = false;
			}
			if ($_POST['GLCode'] == $_SESSION['CompanyRecord']['retainedearnings']) {
				prnMsg(_('Payments involving the retained earnings control account cannot be entered. This account is automtically maintained.'), 'warn');
				$AllowThisPosting = false;
			}
		}
		if ($AllowThisPosting) {
			$_SESSION['ReceiptBatch' . $Identifier]->add_to_batch(filter_number_format($_POST['Amount']), $_POST['CustomerID'], filter_number_format($_POST['Discount']), $_POST['Narrative'], $_POST['GLCode'], $_POST['PayeeBankDetail'], $_POST['CustomerName'], $_POST['tag']);
			/*Make sure the same receipt is not double processed by a page refresh */
			$Cancel = 1;
		}
	}
}

if (isset($Cancel)) {
	unset($_SESSION['CustomerRecord' . $Identifier]);
	unset($_POST['CustomerID']);
	unset($_POST['CustomerName']);
	unset($_POST['Amount']);
	unset($_POST['Discount']);
	unset($_POST['Narrative']);
	unset($_POST['PayeeBankDetail']);
}

if (isset($_POST['CommitBatch'])) {

	/* once all receipts items entered, process all the data in the
	session cookie into the DB creating a single banktrans for the whole amount
	of all receipts in the batch and DebtorTrans records for each receipt item
	all DebtorTrans will refer to a single banktrans. A GL entry is created for
	each GL receipt entry and one for the debtors entry and one for the bank
	account debit

	NB allocations against debtor receipts are a separate exercice

	first off run through the array of receipt items $_SESSION['ReceiptBatch' . $Identifier]->Items and
	if GL integrated then create GL Entries for the GL Receipt items
	and add up the non-GL ones for posting to debtors later,
	also add the total discount total receipts*/

	$PeriodNo = GetPeriod($_SESSION['ReceiptBatch' . $Identifier]->DateBanked);

	if ($_SESSION['CompanyRecord'] == 0) {
		prnMsg(_('The company has not yet been set up properly') . ' - ' . _('this information is needed to process the batch') . '. ' . _('Processing has been cancelled'), 'error');
		include ('includes/footer.php');
		exit;
	}

	/*Make an array of the defined bank accounts */
	$SQL = "SELECT accountcode FROM bankaccounts";
	$Result = DB_query($SQL);
	$BankAccounts = array();
	$i = 0;
	while ($Act = DB_fetch_row($Result)) {
		$BankAccounts[$i] = $Act[0];
		++$i;
	}

	/*Start a transaction to do the whole lot inside */
	$Result = DB_Txn_Begin();
	$_SESSION['ReceiptBatch' . $Identifier]->BatchNo = GetNextTransNo(12);

	$BatchReceiptsTotal = 0; //in functional currency
	$BatchDiscount = 0; //in functional currency
	$BatchDebtorTotal = 0; //in functional currency
	$k = 0; //Table row counter for row styles
	$CustomerReceiptCounter = 1; //Count lines of customer receipts in this batch
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Allocate'), '" alt="" />', ' ', _('Summary of Receipt Batch'), '
		</p>
		<table>
			<tr>
				<th>', _('Batch Number'), '</th>
				<th>', _('Date Banked'), '</th>
				<th>', _('Customer Name'), '</th>
				<th>', _('GL Code'), '</th>
				<th>', _('Amount of Receipt in'), ' ', $_SESSION['ReceiptBatch' . $Identifier]->Currency, '</th>
				<th>', _('Amount of Receipt in'), ' ', $_SESSION['CompanyRecord']['currencydefault'], '</th>
			</tr>';

	foreach ($_SESSION['ReceiptBatch' . $Identifier]->Items as $ReceiptItem) {

		$SQL = "SELECT accountname
					FROM chartmaster
					WHERE accountcode='" . $ReceiptItem->GLCode . "'
						AND language='" . $_SESSION['ChartLanguage'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		echo '<tr class="striped_row">
				<td>', $_SESSION['ReceiptBatch' . $Identifier]->BatchNo, '</td>
				<td>', $_SESSION['ReceiptBatch' . $Identifier]->DateBanked, '</td>
				<td>', $ReceiptItem->CustomerName, '</td>
				<td>', $ReceiptItem->GLCode, ' - ', $MyRow['accountname'], '</td>
				<td class="number">', locale_number_format($ReceiptItem->Amount, $_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces), '</td>
				<td class="number">', locale_number_format($ReceiptItem->Amount / $_SESSION['ReceiptBatch' . $Identifier]->ExRate / $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate, $_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces), '</td>';

		if ($ReceiptItem->GLCode == '') {
			echo '<td><a target="_blank" href="', $RootPath, '/PDFReceipt.php?BatchNumber=', urlencode($_SESSION['ReceiptBatch' . $Identifier]->BatchNo), '&ReceiptNumber=', urlencode($CustomerReceiptCounter), '">', _('Print a Customer Receipt'), '</a></td></tr>';
			$CustomerReceiptCounter+= 1;
		}

		if ($ReceiptItem->GLCode != '') { //so its a GL receipt
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
				/* then enter a GLTrans record */
				$SQL = "INSERT INTO gltrans (type,
								 			typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											12,
											'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
											'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
											'" . $PeriodNo . "',
											'" . $ReceiptItem->GLCode . "',
											'" . $ReceiptItem->Narrative . "',
											'" . -($ReceiptItem->Amount / $_SESSION['ReceiptBatch' . $Identifier]->ExRate / $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate) . "'
										)";
				$ErrMsg = _('Cannot insert a GL entry for the receipt because');
				$DbgMsg = _('The SQL that failed to insert the receipt GL entry was');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				foreach ($ReceiptItem->tag as $Tag) {
					$SQL = "INSERT INTO gltags VALUES ( LAST_INSERT_ID(),
														'" . $Tag . "')";
					$ErrMsg = _('Cannot insert a GL tag for the journal line because');
					$DbgMsg = _('The SQL that failed to insert the GL tag record was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				}
			}

			/*check to see if this is a GL posting to another bank account (or the same one)
			 if it is then a matching payment needs to be created for this account too */

			if (in_array($ReceiptItem->GLCode, $BankAccounts)) {

				/*Need to deal with the case where the payment from one bank account could be to a bank account in another currency */

				/*Get the currency and rate of the bank account transferring to*/
				$SQL = "SELECT currcode, rate
							FROM bankaccounts INNER JOIN currencies
							ON bankaccounts.currcode = currencies.currabrev
							WHERE accountcode='" . $ReceiptItem->GLCode . "'";
				$TrfFromAccountResult = DB_query($SQL);
				$TrfFromBankRow = DB_fetch_array($TrfFromAccountResult);
				$TrfFromBankCurrCode = $TrfFromBankRow['currcode'];
				$TrfFromBankExRate = $TrfFromBankRow['rate'];

				if ($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency == $TrfFromBankCurrCode) {
					/*Make sure to use the same rate if the transfer is between two bank accounts in the same currency */
					$TrfFromBankExRate = $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate;
				}

				/*Consider an example - had to be currencies I am familar with sorry so I could figure it out!!
				functional currency NZD
				bank account in AUD - 1 NZD = 0.90 AUD (FunctionalExRate)
				receiving USD - 1 AUD = 0.85 USD  (ExRate)
				from a bank account in EUR - 1 NZD = 0.52 EUR

				oh yeah - now we are getting tricky!
				Lets say we received USD 100 to the AUD bank account from the EUR bank account

				To get the ExRate for the bank account we are transferring money from
				we need to use the cross rate between the NZD-AUD/NZD-EUR
				and apply this to the

				the receipt record will read
				exrate = 0.85 (1 AUD = USD 0.85)
				amount = 100 (USD)
				functionalexrate = 0.90 (1 NZD = AUD 0.90)

				the payment record will read

				amount 100 (USD)
				exrate    (1 EUR =  (0.85 x 0.90)/0.52 USD  ~ 1.47
				(ExRate x FunctionalExRate) / USD Functional ExRate
				Check this is 1 EUR = 1.47 USD
				functionalexrate =  (1NZD = EUR 0.52)

				*/

				$PaymentTransNo = GetNextTransNo(1);
				$SQL = "INSERT INTO banktrans (transno,
											type,
											bankact,
											ref,
											exrate,
											functionalexrate,
											transdate,
											banktranstype,
											amount,
											currcode)
						VALUES (
							'" . $PaymentTransNo . "',
							1,
							'" . $ReceiptItem->GLCode . "',
							'" . _('Act Transfer') . " - " . $ReceiptItem->Narrative . "',
							'" . (($_SESSION['ReceiptBatch' . $Identifier]->ExRate * $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate) / $TrfFromBankExRate) . "',
							'" . $TrfFromBankExRate . "',
							'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
							'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['paymentname'] . "',
							'" . -$ReceiptItem->Amount . "',
							'" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'
						)";

				$DbgMsg = _('The SQL that failed to insert the bank transaction was');
				$ErrMsg = _('Cannot insert a bank transaction using the SQL');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			} //end if an item is a transfer between bank accounts

		} else { //its not a GL item - its a customer receipt then
			/*Accumulate the total debtors credit including discount */
			$BatchDebtorTotal+= (($ReceiptItem->Discount + $ReceiptItem->Amount) / $_SESSION['ReceiptBatch' . $Identifier]->ExRate / $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate);
			/*Create a DebtorTrans entry for each customer deposit */

			/*The rate of exchange required here is the rate between the functional (home) currency and the customer receipt currency
			 * We have the exchange rate between the bank account and the functional home currency  $_SESSION['ReceiptBatch' . $Identifier]->ExRate
			 * and the exchange rate betwen the currency being paid and the bank account */

			$SQL = "INSERT INTO debtortrans (transno,
											type,
											debtorno,
											branchcode,
											trandate,
											inputdate,
											prd,
											reference,
											tpe,
											rate,
											ovamount,
											ovdiscount,
											invtext,
											salesperson)
					VALUES (
						'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
						12,
						'" . $ReceiptItem->Customer . "',
						'',
						'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
						'" . date('Y-m-d H-i-s') . "',
						'" . $PeriodNo . "',
						'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['paymentname'] . ' ' . $ReceiptItem->PayeeBankDetail . "',
						'',
						'" . ($_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate * $_SESSION['ReceiptBatch' . $Identifier]->ExRate) . "',
						'" . -$ReceiptItem->Amount . "',
						'" . -$ReceiptItem->Discount . "',
						'" . $ReceiptItem->Narrative . "',
						'" . $_SESSION['SalesmanLogin'] . "'
					)";
			$DbgMsg = _('The SQL that failed to insert the customer receipt transaction was');
			$ErrMsg = _('Cannot insert a receipt transaction against the customer because');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			$SQL = "UPDATE debtorsmaster
						SET lastpaiddate = '" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
						lastpaid='" . $ReceiptItem->Amount . "'
					WHERE debtorsmaster.debtorno='" . $ReceiptItem->Customer . "'";

			$DbgMsg = _('The SQL that failed to update the date of the last payment received was');
			$ErrMsg = _('Cannot update the customer record for the date of the last payment received because');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		} //end of if its a customer receipt
		$BatchDiscount+= ($ReceiptItem->Discount / $_SESSION['ReceiptBatch' . $Identifier]->ExRate / $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate);
		$BatchReceiptsTotal+= ($ReceiptItem->Amount / $_SESSION['ReceiptBatch' . $Identifier]->ExRate / $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate);

	}
	/*end foreach $ReceiptItem */
	echo '</table>';

	/*now enter the BankTrans entry */

	$SQL = "INSERT INTO banktrans (type,
								transno,
								bankact,
								ref,
								exrate,
								functionalexrate,
								transdate,
								banktranstype,
								amount,
								currcode)
		VALUES (
			12,
			'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
			'" . $_SESSION['ReceiptBatch' . $Identifier]->Account . "',
			'" . $_SESSION['ReceiptBatch' . $Identifier]->BankTransRef . "',
			'" . $_SESSION['ReceiptBatch' . $Identifier]->ExRate . "',
			'" . $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate . "',
			'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
			'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['paymentname'] . "',
			'" . ($BatchReceiptsTotal * $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate * $_SESSION['ReceiptBatch' . $Identifier]->ExRate) . "',
			'" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'
		)";
	$DbgMsg = _('The SQL that failed to insert the bank account transaction was');
	$ErrMsg = _('Cannot insert a bank transaction');
	$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

	if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
		/* then enter GLTrans records for discount, bank and debtors */

		if ($BatchReceiptsTotal != 0) {
			/* Bank account entry first */
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
				VALUES (
					12,
					'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
					'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['ReceiptBatch' . $Identifier]->Account . "',
					'" . $_SESSION['ReceiptBatch' . $Identifier]->Narrative . "',
					'" . $BatchReceiptsTotal . "'
				)";
			$DbgMsg = _('The SQL that failed to insert the GL transaction fro the bank account debit was');
			$ErrMsg = _('Cannot insert a GL transaction for the bank account debit');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		}
		if ($BatchDebtorTotal != 0) {
			/* Now Credit Debtors account with receipts + discounts */
			$SQL = "INSERT INTO gltrans ( type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES (
							12,
							'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
							'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
							'" . $PeriodNo . "',
							'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
							'" . $_SESSION['ReceiptBatch' . $Identifier]->Narrative . "',
							'" . -$BatchDebtorTotal . "'
							)";
			$DbgMsg = _('The SQL that failed to insert the GL transaction for the debtors account credit was');
			$ErrMsg = _('Cannot insert a GL transaction for the debtors account credit');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		} //end if there are some customer deposits in this batch
		if ($BatchDiscount != 0) {
			/* Now Debit Discount account with discounts allowed*/
			$SQL = "INSERT INTO gltrans ( type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES (
								12,
								'" . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . "',
								'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $Identifier]->DateBanked) . "',
								'" . $PeriodNo . "',
								'" . $_SESSION['CompanyRecord']['pytdiscountact'] . "',
								'" . $_SESSION['ReceiptBatch' . $Identifier]->Narrative . "',
								'" . $BatchDiscount . "'
							)";
			$DbgMsg = _('The SQL that failed to insert the GL transaction for the payment discount debit was');
			$ErrMsg = _('Cannot insert a GL transaction for the payment discount debit');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		} //end if there is some discount

	} //end if there is GL work to be done - ie config is to link to GL
	EnsureGLEntriesBalance(12, $_SESSION['ReceiptBatch' . $Identifier]->BatchNo);

	$ErrMsg = _('Cannot commit the changes');
	$DbgMsg = _('The SQL that failed was');
	$Result = DB_Txn_Commit();
	echo '<br />';
	prnMsg(_('Receipt batch') . ' ' . $_SESSION['ReceiptBatch' . $Identifier]->BatchNo . ' ' . _('has been successfully entered into the database'), 'success');

	echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print'), '" alt="" />', ' ', '<a href="', $RootPath, '/PDFBankingSummary.php?BatchNo=', urlencode($_SESSION['ReceiptBatch' . $Identifier]->BatchNo), '">', _('Print PDF Batch Summary'), '</a></p>';
	echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/allocation.png" title="', _('Allocate'), '" alt="" />', ' ', '<a href="', $RootPath, '/CustomerAllocations.php">', _('Allocate Receipts'), '</a></p>';
	echo '<p class="page_title_text"><a href="', $RootPath, '/CustomerReceipt.php?NewReceipt=Yes&Type=', urlencode($_GET['Type']), '">', _('Enter Receipts'), '</a></p>';

	unset($_SESSION['ReceiptBatch' . $Identifier]);
	include ('includes/footer.php');
	exit;

}
/* End of commit batch */

if (isset($_POST['Search'])) {
	/*Will only be true if clicked to search for a customer code */

	if ($_POST['Keywords'] and $_POST['CustCode']) {
		$Msg = _('Customer name keywords have been used in preference to the customer code extract entered');
	}
	if ($_POST['Keywords'] == '' and $_POST['CustCode'] == '' and $_POST['CustInvNo'] == '') {
		$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'";
	} else {
		if (mb_strlen($_POST['Keywords']) > 0) {
			//insert wildcard characters in spaces
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.name " . LIKE . " '" . $SearchString . "'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'";

		} elseif (mb_strlen($_POST['CustCode']) > 0) {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.debtorno " . LIKE . " '%" . $_POST['CustCode'] . "%'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'";
		} elseif (mb_strlen($_POST['CustInvNo']) > 0) {
			$SQL = "SELECT debtortrans.debtorno,
						debtorsmaster.name
					FROM debtorsmaster LEFT JOIN debtortrans
					ON debtorsmaster.debtorno=debtortrans.debtorno
					WHERE debtortrans.transno " . LIKE . " '%" . $_POST['CustInvNo'] . "%'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $Identifier]->Currency . "'";
		}
	}
	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND EXISTS (
						SELECT *
						FROM 	custbranch
						WHERE 	custbranch.debtorno = debtorsmaster.debtorno
							AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "')";
	}

	$CustomerSearchResult = DB_query($SQL, '', '', false, false);

	if (DB_error_no() != 0) {
		prnMsg(_('The searched customer records requested cannot be retrieved because') . ' - ' . DB_error_msg(), 'error');
		if ($debug == 1) {
			prnMsg(_('SQL used to retrieve the customer details was') . '<br />' . $SQL, 'error');
		}
	} elseif (DB_num_rows($CustomerSearchResult) == 1) {
		$MyRow = DB_fetch_array($CustomerSearchResult);
		$Select = $MyRow['debtorno'];
		$_POST['CustomerID'] = $MyRow['debtorno'];
	} elseif (DB_num_rows($CustomerSearchResult) == 0) {
		prnMsg(_('No customer records contain the selected text') . ' - ' . _('please alter your search criteria and try again'), 'info');
	}
	//one of keywords or custcode was more than a zero length string

} //end of if search
if (isset($_POST['CustomerID'])) {
	/*will only be true if a customer has just been selected by clicking on the customer or only one
	 customer record returned by the search - this record is then auto selected */

	/*need to get currency sales type - payment discount percent and GL code
	as well as payment terms and credit status and hold the lot as session variables
	the receipt held entirely as session variables until the button clicked to process*/

	if (isset($_SESSION['CustomerRecord' . $Identifier])) {
		unset($_SESSION['CustomerRecord' . $Identifier]);
	}

	$SQL = "SELECT debtorsmaster.name,
				debtorsmaster.pymtdiscount,
				debtorsmaster.currcode,
				currencies.currency,
				currencies.rate,
				currencies.decimalplaces AS currdecimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
				SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) AS balance,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0  THEN
					CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue  THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= 0 THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				END) AS due,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight - debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				END) AS overdue1,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				END) AS overdue2
			FROM debtorsmaster INNER JOIN paymentterms
			ON debtorsmaster.paymentterms = paymentterms.termsindicator
			INNER JOIN holdreasons
			ON debtorsmaster.holdreason = holdreasons.reasoncode
			INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
			INNER JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
			WHERE debtorsmaster.debtorno = '" . $_POST['CustomerID'] . "'";
	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL.= " GROUP BY debtorsmaster.name,
				debtorsmaster.pymtdiscount,
				debtorsmaster.currcode,
				currencies.currency,
				currencies.rate,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				paymentterms.daysbeforedue,
				paymentterms.dayinfollowingmonth,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription";

	$ErrMsg = _('The customer details could not be retrieved because');
	$DbgMsg = _('The SQL that failed was');
	$CustomerResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($CustomerResult) == 0) {

		/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

		$NIL_BALANCE = True;

		$SQL = "SELECT debtorsmaster.name,
						debtorsmaster.pymtdiscount,
						currencies.currency,
						currencies.rate,
						currencies.decimalplaces AS currdecimalplaces,
						paymentterms.terms,
						debtorsmaster.creditlimit,
						debtorsmaster.currcode,
						holdreasons.dissallowinvoices,
						holdreasons.reasondescription
					FROM debtorsmaster INNER JOIN paymentterms
					ON debtorsmaster.paymentterms = paymentterms.termsindicator
					INNER JOIN holdreasons
					ON debtorsmaster.holdreason = holdreasons.reasoncode
					INNER JOIN currencies
					ON debtorsmaster.currcode = currencies.currabrev
					WHERE debtorsmaster.debtorno = '" . $_POST['CustomerID'] . "'";

		$ErrMsg = _('The customer details could not be retrieved because');
		$DbgMsg = _('The SQL that failed was');
		$CustomerResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		$CustomerRow = DB_fetch_array($CustomerResult);

	} else {
		$NIL_BALANCE = False;
	}

	$_SESSION['CustomerRecord' . $Identifier] = DB_fetch_array($CustomerResult);
	$_SESSION['CustomerRecord' . $Identifier]['currcode'] = $CustomerRow['currcode'];
	$_SESSION['CustomerRecord' . $Identifier]['dissallowinvoices'] = $CustomerRow['dissallowinvoices'];
	$_SESSION['CustomerRecord' . $Identifier]['name'] = $CustomerRow['name'];
	$_SESSION['CustomerRecord' . $Identifier]['currency'] = $CustomerRow['currency'];
	$_SESSION['CustomerRecord' . $Identifier]['terms'] = $CustomerRow['terms'];
	$_SESSION['CustomerRecord' . $Identifier]['creditlimit'] = $CustomerRow['creditlimit'];
	$_SESSION['CustomerRecord' . $Identifier]['reasondescription'] = $CustomerRow['reasondescription'];
	$_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces'] = $CustomerRow['currdecimalplaces'];
	$_SESSION['CustomerRecord' . $Identifier]['pymtdiscount'] = $CustomerRow['pymtdiscount'];

	if ($NIL_BALANCE == True) {
		$_SESSION['CustomerRecord' . $Identifier]['balance'] = 0;
		$_SESSION['CustomerRecord' . $Identifier]['due'] = 0;
		$_SESSION['CustomerRecord' . $Identifier]['overdue1'] = 0;
		$_SESSION['CustomerRecord' . $Identifier]['overdue2'] = 0;
	}
}
/*end of if customer has just been selected  all info required read into $_SESSION['CustomerRecord' . $Identifier]*/

/*set up the form whatever */

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', $Identifier, '&Type=', urlencode($_GET['Type']), '" method="post" id="form1">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

/*show the batch header details and the entries in the batch so far */

if (isset($_SESSION['CustomerRecord' . $Identifier]['currcode'])) {
	$BankCurr = $_SESSION['CustomerRecord' . $Identifier]['currcode'];
} else {
	$BankCurr = '%';
}

$SQL = "SELECT bankaccountname,
				bankaccounts.accountcode,
				bankaccounts.currcode
		FROM bankaccounts
		INNER JOIN chartmaster
			ON bankaccounts.accountcode=chartmaster.accountcode
		INNER JOIN bankaccountusers
			ON bankaccounts.accountcode=bankaccountusers.accountcode
		WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "'
			AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
			AND currcode LIKE '" . $BankCurr . "'
		ORDER BY bankaccountname";

$ErrMsg = _('The bank accounts could not be retrieved because');
$DbgMsg = _('The SQL used to retrieve the bank accounts was');
$AccountsResults = DB_query($SQL, $ErrMsg, $DbgMsg);

if (isset($_POST['GLEntry'])) {
	echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('Bank Account Receipts Entry'), '" alt="" />', ' ', _('Bank Account Receipts Entry'), '</p>';
} else {
	echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('Enter Receipt'), '" alt="" />', ' ', _('Enter Customer Receipt'), '</p>';
	echo '<div class="page_help_text">', _('To enter a payment TO a customer (ie. to pay out a credit note), enter a negative payment amount.'), '</div>';
}

if (!is_Date($_SESSION['ReceiptBatch' . $Identifier]->DateBanked)) {
	$_SESSION['ReceiptBatch' . $Identifier]->DateBanked = Date($_SESSION['DefaultDateFormat']);
}

echo '<fieldset>
		<legend>', _('Batch header information'), '</legend>
		<field>
			<label for="BankAccount">', _('Bank Account'), ':</label>
			<select autofocus="autofocus" name="BankAccount" onchange="ReloadForm(form1.BatchInput)">';

if (DB_num_rows($AccountsResults) == 0) {
	echo '</select>
		</field>
	</fieldset>';
	prnMsg(_('Bank Accounts have not yet been defined') . '. ' . _('You must first') . ' ' . '<a href="' . $RootPath . '/BankAccounts.php">' . _('define the bank accounts') . '</a>' . _('and general ledger accounts to be affected'), 'info');
	include ('includes/footer.php');
	exit;
} else {
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($AccountsResults)) {
		/*list the bank account names */
		if ($_SESSION['ReceiptBatch' . $Identifier]->Account == $MyRow['accountcode']) {
			echo '<option selected="selected" value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], ' - ', $MyRow['currcode'], '</option>';
		} else {
			echo '<option value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], ' - ', $MyRow['currcode'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('The bank account that the receipts will be paid into.'), '<fieldhelp>
	</field>';
}

echo '<field>
		<label for="DateBanked">', _('Date Banked'), ':</label>
		<input type="text" required="required" class="date" name="DateBanked" maxlength="10" size="10" onchange="isDate(this, this.value, ', "'", $_SESSION['DefaultDateFormat'], "'", ')" value="', $_SESSION['ReceiptBatch' . $Identifier]->DateBanked, '" />
		<fieldhelp>', _('The date when the receipts are banked.'), '<fieldhelp>
	</field>';

echo '<field>
		<label for="Currency">', _('Currency'), ':</label>
		<select name="Currency" onchange="ReloadForm(form1.BatchInput)">';

if (!isset($_SESSION['ReceiptBatch' . $Identifier]->Currency)) {
	$_SESSION['ReceiptBatch' . $Identifier]->Currency = $_SESSION['CompanyRecord']['currencydefault'];
}

$SQL = "SELECT currency, currabrev, rate FROM currencies";
$Result = DB_query($SQL);
if (DB_num_rows($Result) == 0) {
	echo '</select>
		</field>';
	prnMsg(_('No currencies are defined yet') . '. ' . _('Receipts cannot be entered until a currency is defined'), 'warn');

} else {
	include ('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_SESSION['ReceiptBatch' . $Identifier]->Currency == $MyRow['currabrev']) {
			echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], '</option>';
		} else {
			echo '<option value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('The currency of the receipts. Does not have to be the same as the bank account currency.'), '<fieldhelp>
	</field>';
}

if (!isset($_SESSION['ReceiptBatch' . $Identifier]->ExRate)) {
	$_SESSION['ReceiptBatch' . $Identifier]->ExRate = 1;
}

if (!isset($_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate)) {
	$_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate = 1;
}
if ($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency != $_SESSION['ReceiptBatch' . $Identifier]->Currency and isset($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency)) {
	if ($_SESSION['ReceiptBatch' . $Identifier]->ExRate == 1 and isset($SuggestedExRate)) {
		$_SESSION['ReceiptBatch' . $Identifier]->ExRate = $SuggestedExRate;
	} elseif ($_POST['Currency'] != $_POST['PreviousCurrency'] and isset($SuggestedExRate)) { //the user has changed the currency, then we should revise suggested rate
		$_SESSION['ReceiptBatch' . $Identifier]->ExRate = $SuggestedExRate;
	}

	if (isset($SuggestedExRate)) {
		$SuggestedExRateText = '<b>' . _('Suggested rate:') . ' 1 ' . $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency . ' = ' . locale_number_format($SuggestedExRate, 8) . ' ' . $_SESSION['ReceiptBatch' . $Identifier]->Currency . '</b>';
	} else {
		$SuggestedExRateText = '<b>1 ' . $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency . ' = ? ' . $_SESSION['ReceiptBatch' . $Identifier]->Currency . '</b>';
	}
	echo '<field>
			<label for="ExRate">', _('Receipt Exchange Rate'), ':</label>
			<input class="number" maxlength="12" name="ExRate" required="required" size="14" type="text" value="', locale_number_format($_SESSION['ReceiptBatch' . $Identifier]->ExRate, 8), '" />
			<fieldhelp>', $SuggestedExRateText, _('The exchange rate between the currency of the bank account currency and the currency of the receipt'), '</fieldhelp>
		</field>';
}

if ($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency != $_SESSION['CompanyRecord']['currencydefault'] and isset($_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency)) {
	if ($_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate == 1 and isset($SuggestedFunctionalExRate)) {
		$_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate = $SuggestedFunctionalExRate;
	}
	if (isset($SuggestedFunctionalExRate)) {
		$SuggestedFunctionalExRateText = '<b>' . _('Suggested rate:') . ' 1 ' . $_SESSION['CompanyRecord']['currencydefault'] . ' = ' . $SuggestedFunctionalExRate . ' ' . $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency . '</b>';
	} else {
		$SuggestedFunctionalExRateText = '<b>1 ' . $_SESSION['CompanyRecord']['currencydefault'] . ' = ? ' . $_SESSION['ReceiptBatch' . $Identifier]->AccountCurrency . '</b>';
	}
	echo '<field>
			<label for="FunctionalExRate">', _('Functional Exchange Rate'), ':</label>
				<input class="number" maxlength="12" name="FunctionalExRate" required="required" size="14" type="text" value="', $_SESSION['ReceiptBatch' . $Identifier]->FunctionalExRate, '" />
				<fieldhelp>', $SuggestedFunctionalExRateText, _('The exchange rate between the currency of the business (the functional currency) and the currency of the bank account'), '.</fieldhelp>
		</field>';
}

if (isset($_GET['CustomerID'])) {
	$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType = $ReceiptTypes[2]['paymentid'];
}
echo '<field>
		<label for="ReceiptType">', _('Receipt Type'), ':</label>
		<select name="ReceiptType" onchange="ReloadForm(form1.BatchInput)">';
/* The array ReceiptTypes is defined from the setup tab of the main menu under
 payment methods - the array is populated from the include file GetPaymentMethods.php */

foreach ($ReceiptTypes as $RcptType) {
	if (isset($_POST['ReceiptType']) and $_POST['ReceiptType'] == $RcptType['paymentid']) {
		echo '<option selected="selected" value="', $RcptType['paymentid'], '">', $RcptType['paymentname'], '</option>';
	} else {
		echo '<option value="', $RcptType['paymentid'], '">', $RcptType['paymentname'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('The payemnt method for this batch, cheque, direct debit, BACS, etc'), '</fieldhelp>
</field>';

/* Receipt (Bank Account) info to be inserted on banktrans.ref, varchar(50). */
if (!isset($_SESSION['ReceiptBatch' . $Identifier]->BankTransRef)) {
	$_SESSION['ReceiptBatch' . $Identifier]->BankTransRef = '';
}
echo '<field>
		<label for="BankTransRef">', _('Reference'), ':</label>
		<input maxlength="50" name="BankTransRef" size="52" type="text" value="', $_SESSION['ReceiptBatch' . $Identifier]->BankTransRef, '" />
		<fieldhelp>', _('Reference on Bank Transactions Inquiry'), '.</fieldhelp>
	</field>';

/* Receipt (Bank Account) info to be inserted on gltrans.narrative, varchar(200). */
if (!isset($_SESSION['ReceiptBatch' . $Identifier]->Narrative)) {
	$_SESSION['ReceiptBatch' . $Identifier]->Narrative = '';
}
if (!isset($_POST['Currency'])) {
	$_POST['Currency'] = $_SESSION['CompanyRecord']['currencydefault'];
}
echo '<field>
		<label for="BatchNarrative">', _('Narrative'), ':</label>
		<input type="text" name="BatchNarrative" maxlength="200" size="52" value="', $_SESSION['ReceiptBatch' . $Identifier]->Narrative, '" />
		<fieldhelp>', _('Narrative on General Ledger Account Inquiry'), '</fielhelp>
	</field>
</fieldset>';

echo '<input name="PreviousCurrency" type="hidden" value="', $_POST['Currency'], '" />';

echo '<div class="centre">
		<input name="BatchInput" type="submit" value="', _('Accept'), '" />
	</div>';

if (count($_SESSION['ReceiptBatch' . $Identifier]->Items) > 0) {
	/* Now show the entries made so far */
	if (!$BankAccountEmpty) {
		if (!isset($ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['paymentname'])) {
			$PaymentTypeString = '';
		} else {
			$PaymentTypeString = $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['paymentname'];

		}
		echo '<p class="page_title_text">
							</p>';
	} else {
		$PaymentTypeString = '';
	}

	echo '<table width="90%">
			<tr>
				<th colspan="6">
					<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('Banked'), '" alt="" />
				', ' ', $PaymentTypeString, ' - ', _('Banked into the'), ' ', $_SESSION['ReceiptBatch' . $Identifier]->BankAccountName, ' ', _('on'), ' ', $_SESSION['ReceiptBatch' . $Identifier]->DateBanked, '
				</th>
			<tr>
				<th>', _('Amount'), ' ', _('Received'), '</th>
				<th>', _('Discount'), '</th>
				<th>', _('Customer'), '</th>
				<th>', _('GL Code'), '</th>
				<th>', _('Narrative'), '</th>
				<th>', _('Tag'), '</th>
			</tr>';

	$BatchTotal = 0;

	foreach ($_SESSION['ReceiptBatch' . $Identifier]->Items as $ReceiptItem) {

		$TagDescriptions = '';
		foreach ($ReceiptItem->tag as $Tag) {
			$TagSql = "SELECT tagdescription FROM tags WHERE tagref='" . $Tag . "'";
			$TagResult = DB_query($TagSql);
			$TagRow = DB_fetch_array($TagResult);
			if ($Tag == 0) {
				$TagRow['tagdescription'] = _('None');
			}
			$TagDescriptions.= $Tag . ' - ' . $TagRow['tagdescription'] . '<br />';
		}

		$SQL = "SELECT accountname
					FROM chartmaster
					WHERE accountcode='" . $ReceiptItem->GLCode . "'
						AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		echo '<tr class="striped_row">
				<td class="number">', locale_number_format($ReceiptItem->Amount, $_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces), '</td>
				<td class="number">', locale_number_format($ReceiptItem->Discount, $_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces), '</td>
				<td>', stripslashes($ReceiptItem->CustomerName), '</td>
				<td>', $ReceiptItem->GLCode, ' - ', $MyRow['accountname'], '</td>
				<td>', stripslashes($ReceiptItem->Narrative), '</td>
				<td>', $TagDescriptions, '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', $Identifier, '&Delete=', urlencode($ReceiptItem->ID), '&Type=', urlencode($_GET['Type']), '&Currency=', urlencode($_POST['Currency']), '&PreviousCurrency=', urlencode($_POST['PreviousCurrency']), '">', _('Delete'), '</a></td>
			</tr>';
		$BatchTotal = $BatchTotal + $ReceiptItem->Amount;
	}

	echo '<tr>
			<td class="number"><b>', locale_number_format($BatchTotal, $_SESSION['ReceiptBatch' . $Identifier]->CurrDecimalPlaces), '</b></td>
		</tr>
	</table>';

	if (isset($_SESSION['ReceiptBatch' . $Identifier]->Items) and count($_SESSION['ReceiptBatch' . $Identifier]->Items) > 0) {
		echo '<div class="centre">
				<input type="submit" name="CommitBatch" value="', _('Accept and Process Batch'), '" />
			</div>';
	}
}

/*this next block of ifs deals with what information to display for input into the form
the info depends on where the user is up to ie the first stage is to select a bank
account, currency being banked and a batch number - or start a new batch by leaving the batch no blank
and a date for the banking. The second stage is to select a customer or GL account.
Finally enter the amount */

/*if a customer has been selected (and a receipt batch is underway)
 then set out the customers account summary */

if (isset($_SESSION['CustomerRecord' . $Identifier]) and $_SESSION['CustomerRecord' . $Identifier]['currcode'] != $_SESSION['ReceiptBatch' . $Identifier]->Currency) {
	prnMsg(_('The selected customer does not trade in the currency of the receipt being entered - either the currency of the receipt needs to be changed or a different customer selected'), 'warn');
	unset($_SESSION['CustomerRecord' . $Identifier]);
}

if (isset($_SESSION['CustomerRecord' . $Identifier]) and isset($_POST['CustomerID']) and $_POST['CustomerID'] != '' and isset($_SESSION['ReceiptBatch' . $Identifier])) {
	/*a customer is selected  */

	echo '<table  style="width:50%">';
	echo '<tr>
			<th colspan="6">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" />', ' ', $_SESSION['CustomerRecord' . $Identifier]['name'], ' - (', _('All amounts stated in'), ' ', $_SESSION['CustomerRecord' . $Identifier]['currency'], ')', _('Terms'), ': ', $_SESSION['CustomerRecord' . $Identifier]['terms'], '<br/>', _('Credit Limit'), ': ', locale_number_format($_SESSION['CustomerRecord' . $Identifier]['creditlimit'], 0), '  ', _('Credit Status'), ': ', $_SESSION['CustomerRecord' . $Identifier]['reasondescription'], '
			</th>
		</tr>';

	if ($_SESSION['CustomerRecord' . $Identifier]['dissallowinvoices'] != 0) {
		echo '<br />
			<font color="red" size="4"><b>', _('ACCOUNT ON HOLD'), '</font></b>
			<br/>';
	}

	echo '<tr>
			<th width="10%">', _('Total Balance'), '</th>
			<th width="10%">', _('Current'), '</th>
			<th width="10%">', _('Now Due'), '</th>
			<th width="10%">', $_SESSION['PastDueDays1'], '-', $_SESSION['PastDueDays2'], ' ', _('Days Overdue'), '</th>
			<th width="10%">', _('Over'), ' ', $_SESSION['PastDueDays2'], ' ', _('Days Overdue'), '</th>
			<th width="10%">', _('Customer Transaction Inquiry'), '</th>
		</tr>';

	echo '<tr class="striped_row">
			<td class="number">', locale_number_format($_SESSION['CustomerRecord' . $Identifier]['balance'], $_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces']), '</td>
			<td class="number">', locale_number_format(($_SESSION['CustomerRecord' . $Identifier]['balance'] - $_SESSION['CustomerRecord' . $Identifier]['due']), $_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces']), '</td>
			<td class="number">', locale_number_format(($_SESSION['CustomerRecord' . $Identifier]['due'] - $_SESSION['CustomerRecord' . $Identifier]['overdue1']), $_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces']), '</td>
			<td class="number">', locale_number_format(($_SESSION['CustomerRecord' . $Identifier]['overdue1'] - $_SESSION['CustomerRecord' . $Identifier]['overdue2']), $_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces']), '</td>
			<td class="number">', locale_number_format($_SESSION['CustomerRecord' . $Identifier]['overdue2'], $_SESSION['CustomerRecord' . $Identifier]['currdecimalplaces']), '</td>
			<td><a href="CustomerInquiry.php?CustomerID=', urlencode($_POST['CustomerID']), '&Status=0" target="_blank">', _('Inquiry'), '</td>
		</tr>
	</table>';

	echo '<fieldset>
			<legend>', _('Details of Receipt.'), '</legend>';

	if ($_SESSION['CustomerRecord' . $Identifier]['pymtdiscount'] > $ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['percentdiscount']) {
		$DisplayDiscountPercent = locale_number_format($_SESSION['CustomerRecord' . $Identifier]['pymtdiscount'] * 100, 2) . '%';
	} else {
		$DisplayDiscountPercent = locale_number_format($ReceiptTypes[$_SESSION['ReceiptBatch' . $Identifier]->ReceiptType]['percentdiscount'] * 100, 2) . '%';
	}

	echo '<input type="hidden" name="CustomerID" value="', $_POST['CustomerID'], '" />';
	echo '<input type="hidden" name="CustomerName" value="', $_SESSION['CustomerRecord' . $Identifier]['name'], '" />';

}

if (isset($_POST['GLEntry']) and isset($_SESSION['ReceiptBatch' . $Identifier])) {
	/* Set up a heading for the transaction entry for a GL Receipt */
	echo '<fieldset>
			<legend>', _('General Ledger Receipt Entry'), '</legend>';

	/*now set up a GLCode field to select from avaialble GL accounts */
	echo '<field>
			<label for="GLCode">', _('GL Account'), ':</label>';
	GLSelect(2, 'GLCode');
	echo '<fieldhelp>', _('Select the General Ledger account to post this receipt to.'), '</fieldhelp>
		</field>';
}

/*if either a customer is selected or its a GL Entry then set out
 the fields for entry of receipt amt, disc, payee details, narrative */

if (((isset($_SESSION['CustomerRecord' . $Identifier]) and isset($_POST['CustomerID']) and $_POST['CustomerID'] != '') or isset($_POST['GLEntry'])) and isset($_SESSION['ReceiptBatch' . $Identifier])) {

	if (!isset($_POST['Amount'])) {
		$_POST['Amount'] = 0;
	}
	if (!isset($_POST['Discount'])) {
		$_POST['Discount'] = 0;
	}
	if (!isset($_POST['PayeeBankDetail'])) {
		$_POST['PayeeBankDetail'] = '';
	}
	if (!isset($_POST['Narrative'])) {
		$_POST['Narrative'] = '';
	}
	echo '<field>
			<label for="Amount">', _('Amount of Receipt'), ':</label>
			<input type="text" name="Amount" required="required" maxlength="12" size="13" class="number" value="', $_POST['Amount'], '" />
			<fieldhelp>', _('The amount of the receipt in'), ' ', $_SESSION['ReceiptBatch' . $Identifier]->Currency, '</fieldhelp>
		</field>';

	if (!isset($_POST['GLEntry'])) {
		echo '<field>
				<label for="Discount">', _('Amount of Discount'), ':</label>
					<input type="text" name="Discount" maxlength="12" size="13" class="number" value="', $_POST['Discount'] . '" />
					<fieldhelp>', _('agreed prompt payment discount is'), ' ', $DisplayDiscountPercent, '</fieldhelp>
			</field>';
	} else {
		echo '<input type="hidden" name="Discount" value="0" />';
	}

	echo '<field>
			<label for="PayeeBankDetail">', _('Payee Bank Details'), ':</label>
			<input type="text" name="PayeeBankDetail" maxlength="22" size="20" value="', $_POST['PayeeBankDetail'], '" />
			<fieldhelp>', _('The bank details of the payee.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="Narrative">' . _('Narrative') . ':</label>
			<textarea name="Narrative"  cols="40" rows="1"></textarea>
			<fieldhelp>', _('Any narrative associated with this receipt.'), '</fieldhelp>
		</field>';

	//Select the tag
	echo '<field>
			<label for="tag[]">', _('Select Tag(s)'), ':</label>
			<select multiple="multiple" name="tag[]">';

	$SQL = "SELECT tagref,
					tagdescription
				FROM tags
				ORDER BY tagref";

	$Result = DB_query($SQL);
	echo '<option value="0" selected="selected">0 - ', _('None'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['tag']) and $_POST['tag'] == $MyRow['tagref']) {
			echo '<option selected="selected" value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select any number of tags to associate this receipt with - use the ctrl button to pick multiple tags.'), '</fieldhelp>
	</field>';
	// End select tag
	echo '</fieldset>
			<div class="centre">
				<input type="submit" name="Process" value="', _('Accept'), '" />
				<input type="submit" name="Cancel" value="', _('Cancel'), '" />
			</div>';

} elseif (!isset($_POST['Search']) and isset($_SESSION['ReceiptBatch' . $Identifier]) and !isset($_POST['GLEntry'])) {

	echo '<fieldset>
			<legend class="search">', _('Select a Customer'), '</legend>
		<field>
			', _('Text in the Customer'), ' ', '<b>', _('name'), '</b>:
			<input type="text" name="Keywords" size="15" maxlength="25" />
			<b>', _('OR'), '</b>
			', _('Text extract in the Customer'), ' ', '<b>', _('code'), '</b>:
			<input type="text" name="CustCode" data-type="no-illegal-chars" title="', _('Enter an extract of the customer code to search for. Customer codes can contain any alpha-numeric character or underscore'), '" size="10" maxlength="18" />
			<b>', _('OR'), '</b>
			', _('Customer invoice number'), ':
			<input type="text" name="CustInvNo" class="number" size="8" maxlength="8" />
		</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="Search" value="', _('Search Now'), '" />
			<input type="submit" name="GLEntry" value="', _('Enter A GL Receipt'), '" />
		</div>';
}

if (isset($CustomerSearchResult)) {

	echo '<table>';
	echo '<tr>
			<th colspan="3">
				<h3>', _('Select a customer'), '</h3>
			</th>
		</tr>';
	echo '<tr>
			<th>', _('Code'), '</th>
			<th>', _('Customer Name'), '</th>
			<th>', _('Balance Outstanding'), '</th>
		</tr>';

	$k = 0; //row counter to determine background colour
	while ($MyRow = DB_fetch_array($CustomerSearchResult)) {
		$SQL = "SELECT SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) AS balance,
						currencies.decimalplaces AS currdecimalplaces
					FROM debtorsmaster
					INNER JOIN currencies
						ON debtorsmaster.currcode = currencies.currabrev
					INNER JOIN debtortrans
						ON debtorsmaster.debtorno = debtortrans.debtorno
					WHERE debtortrans.debtorno='" . $MyRow['debtorno'] . "'";
		$Result = DB_query($SQL);
		$BalanceRow = DB_fetch_array($Result);
		echo '<tr class="striped_row">
				<td><input type="submit" name="CustomerID" value="', $MyRow['debtorno'], '" /></td>
				<td>', $MyRow['name'], '</td>
				<td class="number">', locale_number_format($BalanceRow['balance'], $BalanceRow['currdecimalplaces']), '</td>
			</tr>';

		//end of page full new headings if

	}
	//end of while loop
	echo '</table>';
	echo '<div class="centre">
			<input type="submit" name="Cancel" value="', _('Cancel'), '" />
		</div>';
}

echo '</form>';
include ('includes/footer.php');
?>