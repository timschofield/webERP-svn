<?php
include ('includes/session.php');
$Title = _('Customer Account'); // Screen identification.
$ViewTopic = 'ARInquiries'; // Filename in ManualContents.php's TOC.
$BookMark = 'CustomerAccount'; // Anchor's id in the manual's html document.
include ('includes/header.php');

// always figure out the SQL required from the inputs available
if (!isset($_GET['CustomerID']) and !isset($_SESSION['CustomerID'])) {
	prnMsg(_('To display the account a customer must first be selected from the customer selection screen'), 'info');
	echo '<br /><div class="centre"><a href="', $RootPath, '/SelectCustomer.php">', _('Select a Customer Account to Display'), '</a></div>';
	include ('includes/footer.php');
	exit;
} else {
	if (isset($_GET['CustomerID'])) {
		$_SESSION['CustomerID'] = stripslashes($_GET['CustomerID']);
	}
	$CustomerID = $_SESSION['CustomerID'];
}
//Check if the users have proper authority
if ($_SESSION['SalesmanLogin'] != '') {
	$ViewAllowed = false;
	$SQL = "SELECT salesman FROM custbranch WHERE debtorno = '" . $CustomerID . "'";
	$ErrMsg = _('Failed to retrieve sales data');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result) > 0) {
		while ($MyRow = DB_fetch_array($Result)) {
			if ($_SESSION['SalesmanLogin'] == $MyRow['salesman']) {
				$ViewAllowed = true;
			}
		}
	} else {
		prnMsg(_('There is no salesman data set for this customer'), 'error');
		include ('includes/footer.php');
		exit;
	}
	if (!$ViewAllowed) {
		prnMsg(_('You have no authority to review this customer account'), 'error');
		include ('includes/footer.php');
		exit;
	}
}

if (!isset($_POST['TransAfterDate'])) {
	$_POST['TransAfterDate'] = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m') - $_SESSION['NumberOfMonthMustBeShown'], Date('d'), Date('Y')));
}

$Transactions = array();

/*now get all the settled transactions which were allocated this month */
$ErrMsg = _('There was a problem retrieving the transactions that were settled over the course of the last month for') . ' ' . $CustomerID . ' ' . _('from the database');
if ($_SESSION['Show_Settled_LastMonth'] == 1) {
	$SQL = "SELECT DISTINCT debtortrans.id,
						debtortrans.type,
						systypes.typename,
						debtortrans.branchcode,
						debtortrans.reference,
						debtortrans.invtext,
						debtortrans.order_,
						debtortrans.transno,
						debtortrans.trandate,
						debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst AS totalamount,
						debtortrans.alloc,
						debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst-debtortrans.alloc AS balance,
						debtortrans.settled
				FROM debtortrans INNER JOIN systypes
					ON debtortrans.type=systypes.typeid
				INNER JOIN custallocns
					ON (debtortrans.id=custallocns.transid_allocfrom
						OR debtortrans.id=custallocns.transid_allocto)
				WHERE custallocns.datealloc >='" . FormatDateForSQL($_POST['TransAfterDate']) . "'
				AND debtortrans.debtorno='" . $CustomerID . "'
				AND debtortrans.settled=1
				ORDER BY debtortrans.id";

	$SetldTrans = DB_query($SQL, $ErrMsg);
	$NumberOfRecordsReturned = DB_num_rows($SetldTrans);
	while ($MyRow = DB_fetch_array($SetldTrans)) {
		$Transactions[] = $MyRow;
	}
} else {
	$NumberOfRecordsReturned = 0;
}

/*now get all the outstanding transaction ie Settled=0 */
$ErrMsg = _('There was a problem retrieving the outstanding transactions for') . ' ' . $CustomerID . ' ' . _('from the database') . '.';
$SQL = "SELECT debtortrans.id,
			debtortrans.type,
			systypes.typename,
			debtortrans.branchcode,
			debtortrans.reference,
			debtortrans.invtext,
			debtortrans.order_,
			debtortrans.transno,
			debtortrans.trandate,
			debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst as totalamount,
			debtortrans.alloc,
			debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst-debtortrans.alloc as balance,
			debtortrans.settled
		FROM debtortrans INNER JOIN systypes
			ON debtortrans.type=systypes.typeid
		WHERE debtortrans.debtorno='" . $CustomerID . "'
		AND debtortrans.settled=0";

if ($_SESSION['SalesmanLogin'] != '') {
	$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$SQL.= " ORDER BY debtortrans.id";

$OstdgTrans = DB_query($SQL, $ErrMsg);
while ($MyRow = DB_fetch_array($OstdgTrans)) {
	$Transactions[] = $MyRow;
}

$NumberOfRecordsReturned+= DB_num_rows($OstdgTrans);

$SQL = "SELECT debtorsmaster.name,
			debtorsmaster.address1,
			debtorsmaster.address2,
			debtorsmaster.address3,
			debtorsmaster.address4,
			debtorsmaster.address5,
			debtorsmaster.address6,
			currencies.currency,
			currencies.decimalplaces,
			paymentterms.terms,
			debtorsmaster.creditlimit,
			holdreasons.dissallowinvoices,
			holdreasons.reasondescription,
			SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
			debtortrans.ovdiscount - debtortrans.alloc) AS balance,
			SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >=
				paymentterms.daysbeforedue
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			ELSE
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1', 'MONTH') . "), " . interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))', 'DAY') . ")) >= 0
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			END) AS due,
			Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
				AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >=
				(paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			ELSE
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1', 'MONTH') . "), " . interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))', 'DAY') . ")) >= " . $_SESSION['PastDueDays1'] . ")
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			END) AS overdue1,
			Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
				AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue +
				" . $_SESSION['PastDueDays2'] . ")
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			ELSE
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1', 'MONTH') . "), " . interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))', 'DAY') . "))
				>= " . $_SESSION['PastDueDays2'] . ")
				THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
				debtortrans.ovdiscount - debtortrans.alloc
				ELSE 0 END
			END) AS overdue2
		FROM debtorsmaster INNER JOIN paymentterms
			ON debtorsmaster.paymentterms = paymentterms.termsindicator
		INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
		INNER JOIN holdreasons
			ON debtorsmaster.holdreason = holdreasons.reasoncode
		INNER JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
		WHERE
			debtorsmaster.debtorno = '" . $CustomerID . "'";

if ($_SESSION['SalesmanLogin'] != '') {
	$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$SQL.= " GROUP BY
			debtorsmaster.name,
			debtorsmaster.address1,
			debtorsmaster.address2,
			debtorsmaster.address3,
			debtorsmaster.address4,
			debtorsmaster.address5,
			debtorsmaster.address6,
			currencies.decimalplaces,
			currencies.currency,
			paymentterms.terms,
			paymentterms.daysbeforedue,
			paymentterms.dayinfollowingmonth,
			debtorsmaster.creditlimit,
			holdreasons.dissallowinvoices,
			holdreasons.reasondescription";

$ErrMsg = _('The customer details could not be retrieved by the SQL because');
$CustomerResult = DB_query($SQL, $ErrMsg);

$CustomerRecord = DB_fetch_array($CustomerResult);

echo '<div class="noPrint toplink">
		<a href="', $RootPath, '/SelectCustomer.php">', _('Back to Customer Screen'), '</a>
	</div>';

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" />', ' ', _('Customer Statement For'), ': ', stripslashes($CustomerID), ' - ', $CustomerRecord['name'], '
	</p>';

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" class="centre noPrint">';
echo '<fieldset>
		<input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />', _('Show all transactions after'), ':<input class="date" id="datepicker" maxlength="10" minlength="0" name="TransAfterDate" required="required" size="12" type="text" value="', $_POST['TransAfterDate'], '" />', '<input name="Refresh Inquiry" type="submit" value="', _('Refresh Inquiry'), '" />
	</fieldset><br />';

echo '<fieldset>
		<legend>', _('Address'), '</legend>';
$CustomerAddress =  $CustomerRecord['address1'] . '<br />';
if ($CustomerRecord['address2'] != '') { // If not empty, output this line.
	$CustomerAddress = $CustomerAddress . $CustomerRecord['address2'] . '<br />';
}
if ($CustomerRecord['address3'] != '') { // If not empty, output this line.
	$CustomerAddress = $CustomerAddress . $CustomerRecord['address3'] . '<br />';
}
$CustomerAddress = $CustomerAddress . $CustomerRecord['address4'] . '<br />';
$CustomerAddress = $CustomerAddress . $CustomerRecord['address5'] . '<br />';
echo '<field>
		<div class="fieldtext">', $CustomerAddress, '</div>
	</field>
</fieldset>';

echo '<fieldset style="margin-left:4px">
		<legend>', _('Customer Details'), '</legend>';
echo '<field>
		<label>', _('All amounts stated in'), ':</label>
		<td>', $CustomerRecord['currency'], '</td>
	</field>
	<field>
		<label>', _('Terms'), ':</label>
		<td>', $CustomerRecord['terms'], '</label>
	</field>
	<field>
		<label>', _('Credit Limit'), ':</label>
		<td>', locale_number_format($CustomerRecord['creditlimit'], 0), '</td>
	</field>
	<field>
		<label>', _('Credit Status'), ':</label>
		<td>', $CustomerRecord['reasondescription'], '</td>
	</field>
</fieldset>
</form>';

if ($CustomerRecord['dissallowinvoices'] != 0) {
	echo '<br /><b><font color="red" size="4">', _('ACCOUNT ON HOLD'), '</font></b><br />';
}

/* Show a table of the invoices returned by the SQL. */

echo '<table>
		<thead>
			<tr>
				<th class="SortedColumn">', _('Type'), '</th>
				<th class="SortedColumn">', _('Number'), '</th>
				<th class="SortedColumn">', _('Date'), '</th>
				<th>', _('Branch'), '</th>
				<th class="SortedColumn">', _('Reference'), '</th>
				<th>', _('Comments'), '</th>
				<th>', _('Order'), '</th>
				<th>', _('Charges'), '</th>
				<th>', _('Credits'), '</th>
				<th>', _('Allocated'), '</th>
				<th>', _('Balance'), '</th>
				<th class="noPrint" colspan="4">&nbsp;</th>
			</tr>
		</thead>';

echo '<tbody>';

$OutstandingOrSettled = '';
$PrintCustomerTransactionScript = 'PrintInvoice.php';

foreach ($Transactions as $MyRow) {

	if ($MyRow['settled'] == 1 and $OutstandingOrSettled == '') {
		echo '<tr>
				<th colspan="11">', _('TRANSACTIONS SETTLED SINCE'), ' ', $_POST['TransAfterDate'], '</th>
				<th class="noPrint" colspan="4">&nbsp;</th>
			</tr>';
		$OutstandingOrSettled = 'Settled';
	} elseif (($OutstandingOrSettled == 'Settled' or $OutstandingOrSettled == '') and $MyRow['settled'] == 0) {
		echo '<tr>
				<th colspan="11">', _('OUTSTANDING TRANSACTIONS'), ' ', $_POST['TransAfterDate'], '</th>
				<th class="noPrint" colspan="4">&nbsp;</th>
			</tr>';
		$OutstandingOrSettled = 'Outstanding';
	}

	$FormatedTranDate = ConvertSQLDate($MyRow['trandate']);

	if ($MyRow['type'] == 10) { //its an invoice
		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td class="number">', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td>', $MyRow['branchcode'], '</td>
				<td>', $MyRow['reference'], '</td>
				<td style="width:200px">', $MyRow['invtext'], '</td>
				<td class="number">', $MyRow['order_'], '</td>
				<td class="number">', locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']), '</td>
				<td>&nbsp;</td>
				<td class="number">', locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']), '</td>
				<td class="noPrint">
					<a href="', $RootPath, '/PrintCustTrans.php?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Invoice">', _('HTML '), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/preview.png" title="', _('Click to preview the invoice'), '" alt="" />
					</a>
				</td>
				<td class="noPrint">
					<a href="', $RootPath, '/', $PrintCustomerTransactionScript, '?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Invoice&amp;PrintPDF=True">' . _('PDF ') . '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/pdf.png" title="', _('Click for PDF'), '" alt="" />
					</a>
				</td>
				<td class="noPrint">
					<a href="', $RootPath, '/EmailCustTrans.php?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Invoice">', _('Email ') . '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/email.png" title="', _('Click to email the invoice'), '" alt="" />
					</a>
				</td>
				<td></td>
			</tr>';

	} elseif ($MyRow['type'] == 11) {
		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td>', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td>', $MyRow['branchcode'], '</td>
				<td>', $MyRow['reference'], '</td>
				<td style="width:200px">', $MyRow['invtext'], '</td>
				<td>', $MyRow['order_'], '</td>
				<td></td>
				<td class="number">', locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']), '</td>
				<td class="noPrint">
					<a href="', $RootPath, '/PrintCustTrans.php?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Credit">', _('HTML '), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/preview.png" title="', _('Click to preview the credit note'), '" />
					</a>
				</td>
				<td class="noPrint">
					<a href="', $RootPath, '/', $PrintCustomerTransactionScript, '?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Credit&amp;PrintPDF=True">', _('PDF '), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/pdf.png" title="', _('Click for PDF'), '" alt="" />
					</a>
				</td>
				<td class="noPrint">
					<a href="', $RootPath, '/EmailCustTrans.php?FromTransNo=', urlencode($MyRow['transno']), '&amp;InvOrCredit=Credit">', _('Email'), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/email.png" title="', _('Click to email the credit note'), '" alt="" />
					</a>
				</td>
				<td>
					<a href="', $RootPath, '/CustomerAllocations.php?AllocTrans=', $MyRow['id'], '">', _('Allocation'), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/allocation.png" title="', _('Click to allocate funds'), '" alt="" />
					</a>
				</td>
			</tr>';

	} elseif ($MyRow['type'] == 12 and $MyRow['totalamount'] < 0) {
		/* Show transactions where:
		 * - Is receipt
		*/
		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td>', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td>', $MyRow['branchcode'], '</td>
				<td>', $MyRow['reference'], '</td>
				<td style="width:200px">', $MyRow['invtext'], '</td>
				<td>', $MyRow['order_'], '</td>
				<td></td>
				<td class="number">', locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']), '</td>
				<td class="noPrint">
					<a href="', $RootPath, '/CustomerAllocations.php?AllocTrans=', urlencode($MyRow['id']), '">', _('Allocation'), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/allocation.png" title="', _('Click to allocate funds'), '" alt="" />
					</a>
				</td>
				<td></td>
				<td></td>
				<td></td>
			</tr>';

	} elseif ($MyRow['type'] == 12 and $MyRow['totalamount'] > 0) {
		/* Show transactions where:
		 * - Is a negative receipt
		 * - User cannot view GL transactions
		*/
		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td>', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td>', $MyRow['branchcode'], '</td>
				<td>', $MyRow['reference'], '</td>
				<td style="width:200px">', $MyRow['invtext'], '</td>
				<td>', $MyRow['order_'], '</td>
				<td class="number">', locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']), '</td>
				<td></td>
				<td class="number">', locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']), '</td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>';
	}
}
//end of while loop
echo '</tbody>';
echo '</table>';

echo '<table width="70%">
	<tr>
		<th style="width:20%">', _('Total Balance'), '</th>
		<th style="width:20%">', _('Current'), '</th>
		<th style="width:20%">', _('Now Due'), '</th>
		<th style="width:20%">', $_SESSION['PastDueDays1'], '-', $_SESSION['PastDueDays2'], ' ' . _('Days Overdue'), '</th>
		<th style="width:20%">', _('Over'), ' ', $_SESSION['PastDueDays2'], ' ', _('Days Overdue'), '</th>
	</tr>';

echo '<tr class="striped_row">
		<td class="number">', locale_number_format($CustomerRecord['balance'], $CustomerRecord['decimalplaces']), '</td>
		<td class="number">', locale_number_format(($CustomerRecord['balance'] - $CustomerRecord['due']), $CustomerRecord['decimalplaces']), '</td>
		<td class="number">', locale_number_format(($CustomerRecord['due'] - $CustomerRecord['overdue1']), $CustomerRecord['decimalplaces']), '</td>
		<td class="number">', locale_number_format(($CustomerRecord['overdue1'] - $CustomerRecord['overdue2']), $CustomerRecord['decimalplaces']), '</td>
		<td class="number">', locale_number_format($CustomerRecord['overdue2'], $CustomerRecord['decimalplaces']), '</td>
	</tr>
</table>';

include ('includes/footer.php');
?>