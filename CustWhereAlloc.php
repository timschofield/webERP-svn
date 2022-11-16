<?php
include ('includes/session.php');
$Title = _('Customer How Paid Inquiry');
/* Manual links before header.php */
$ViewTopic = 'ARInquiries';
$BookMark = 'WhereAllocated';
include ('includes/header.php');

if (isset($_GET['TransNo']) and isset($_GET['TransType'])) {
	$_POST['TransNo'] = (int)$_GET['TransNo'];
	$_POST['TransType'] = (int)$_GET['TransType'];
	$_POST['ShowResults'] = true;
}

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<p class="page_title_text noPrint" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Customer Where Allocated'), '" alt="', _('Customer Where Allocated'), '" />', $Title, '
	</p>';

echo '<fieldset>
		<legend>', _('Select criteria for the where used inquiry'), '</legend>
		<field>
			<label for="TransType">', _('Type'), ':</label>
			<select name="TransType"> ';

if (!isset($_POST['TransType'])) {
	$_POST['TransType'] = '10';
}

if ($_POST['TransType'] == 10) {
	echo '<option selected="selected" value="10">', _('Invoice'), '</option>
			<option value="12">', _('Receipt'), '</option>
			<option value="11">', _('Credit Note'), '</option>';
} elseif ($_POST['TransType'] == 12) {
	echo '<option selected="selected" value="12">', _('Receipt'), '</option>
			<option value="10">', _('Invoice'), '</option>
			<option value="11">', _('Credit Note'), '</option>';
} elseif ($_POST['TransType'] == 11) {
	echo '<option selected="selected" value="11">', _('Credit Note'), '</option>
		<option value="10">', _('Invoice'), '</option>
		<option value="12">', _('Receipt'), '</option>';
}

echo '</select>
	</field>';

if (!isset($_POST['TransNo'])) {
	$_POST['TransNo'] = '';
}
echo '<field>
		<label for="TransNo">', _('Transaction Number'), ':</label>
		<input class="number" type="text" name="TransNo" required="required" maxlength="10" size="10" value="', $_POST['TransNo'], '" />
	</field>
</fieldset>';

echo '<div class="centre noPrint">
		<input type="submit" name="ShowResults" value="', _('Show How Allocated'), '" />
	</div>
</form>';

if (isset($_POST['ShowResults']) and $_POST['TransNo'] == '') {
	prnMsg(_('The transaction number to be queried must be entered first'), 'warn');
}

if (isset($_POST['ShowResults']) and $_POST['TransNo'] != '') {

	/*First off get the DebtorTransID of the transaction (invoice normally) selected */
	$SQL = "SELECT debtortrans.id,
				ovamount+ovgst AS totamt,
				currencies.decimalplaces AS currdecimalplaces,
				debtorsmaster.currcode,
				debtortrans.rate
			FROM debtortrans
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			WHERE type='" . $_POST['TransType'] . "'
				AND transno = '" . $_POST['TransNo'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$Result = DB_query($SQL);

	$GrandTotal = 0;
	$Rows = DB_num_rows($Result);
	if ($Rows >= 1) {
		while ($MyRow = DB_fetch_array($Result)) {
			$GrandTotal+= $MyRow['totamt'];
			$Rate = $MyRow['rate'];
			$AllocToID = $MyRow['id'];
			$CurrCode = $MyRow['currcode'];
			$CurrDecimalPlaces = $MyRow['currdecimalplaces'];

			$SQL = "SELECT type,
							transno,
							trandate,
							debtortrans.debtorno,
							reference,
							debtortrans.rate,
							ovamount+ovgst+ovfreight+ovdiscount as totalamt,
							custallocns.amt
						FROM debtortrans
						INNER JOIN custallocns";
			if ($_POST['TransType'] == 12 or $_POST['TransType'] == 11) {
				if ($_POST['TransType'] == 12) {
					$TitleInfo = _('Receipt');
				} else {
					$TitleInfo = _('Credit Note');
				}
				if ($MyRow['totamt'] < 0) {
					$SQL.= " ON debtortrans.id = custallocns.transid_allocto
						WHERE custallocns.transid_allocfrom = '" . $AllocToID . "'";
				} else {
					$SQL.= " ON debtortrans.id = custallocns.transid_allocfrom
						WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
				}

			} else {
				$TitleInfo = _('invoice');
				$SQL.= " ON debtortrans.id = custallocns.transid_allocfrom
					WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
			}
			$SQL.= " ORDER BY transno ";

			$ErrMsg = _('The customer transactions for the selected criteria could not be retrieved because');
			$TransResult = DB_query($SQL, $ErrMsg);

			if (DB_num_rows($TransResult) == 0) {
				prnMsg(_('There are no allocations made against this transaction'), 'info');

				if ($MyRow['totamt'] < 0 and ($_POST['TransType'] == 12 or $_POST['TransType'] == 11)) {
					prnMsg(_('This transaction was a receipt of funds and there can be no allocations of receipts or credits to a receipt. This inquiry is meant to be used to see how a payment which is entered as a negative receipt is settled against credit notes or receipts'), 'info');
				} else {
					prnMsg(_('There are no allocations made against this transaction'), 'info');
				}
			} else {
				$Printer = true;
				echo '<table summary="', _('Allocations made against invoice number'), ' ', $_POST['TransNo'], '">';

				echo '<tr>
						<th colspan="7">
							<div class="centre">
								<b>', _('Allocations made against'), ' ', $TitleInfo, ' ', _('number'), ' ', $_POST['TransNo'], '<br />', _('Transaction Total'), ': ', locale_number_format($MyRow['totamt'], $CurrDecimalPlaces), ' ', $CurrCode, '</b>
								<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print'), '" alt="', _('Print'), '" onclick="window.print();" />
							</div>
						</th>
					</tr>
					<tr>
						<th>', _('Date'), '</th>
						<th>', _('Type'), '</th>
						<th>', _('Number'), '</th>
						<th>', _('Reference'), '</th>
						<th>', _('Ex Rate'), '</th>
						<th>', _('Amount'), '</th>
						<th>', _('Alloc'), '</th>
					</tr>';

				$AllocsTotal = 0;

				while ($MyRow = DB_fetch_array($TransResult)) {

					if ($MyRow['type'] == 11) {
						$TransType = _('Credit Note');
					} elseif ($MyRow['type'] == 10) {
						$TransType = _('Invoice');
					} else {
						$TransType = _('Receipt');
					}
					echo '<tr class="striped_row">
							<td>', ConvertSQLDate($MyRow['trandate']), '</td>
							<td>', $TransType, '</td>
							<td>', $MyRow['transno'], '</td>
							<td>', $MyRow['reference'], '</td>
							<td>', $MyRow['rate'], '</td>
							<td class="number">', locale_number_format($MyRow['totalamt'], $CurrDecimalPlaces), '</td>
							<td class="number">', locale_number_format($MyRow['amt'], $CurrDecimalPlaces), '</td>
						</tr>';

					$AllocsTotal+= $MyRow['amt'];
				}
				//end of while loop
				echo '<tr class="total_row">
						<td colspan="6" class="number">', _('Total allocated'), '</td>
						<td class="number">', locale_number_format($AllocsTotal, $CurrDecimalPlaces), '</td>
					</tr>
				</table>';
			} // end if there are allocations against the transaction
			
		} //end of while loop;
		if ($Rows > 1) {
			echo '<div class="centre">', _('Transaction Total'), locale_number_format($GrandTotal, $CurrDecimalPlaces), '</div>';
		}
		if ($_POST['TransType'] == 12) {
			//retrieve transaction to see if there are any transaction fee,
			$SQL = "SELECT account,
							amount
						FROM gltrans
						LEFT JOIN bankaccounts
							ON account=accountcode
						WHERE type=12 AND typeno='" . $_POST['TransNo'] . "'
							AND account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "'
							AND accountcode IS NULL";
			$ErrMsg = _('Failed to retrieve charge data');
			$Result = DB_query($SQL, $ErrMsg);
			if (DB_num_rows($Result) > 0) {
				while ($MyRow = DB_fetch_array($Result)) {
					echo '<div class="centre">
							<strong>' . _('GL Account') . ' ' . $MyRow['account'] . '</strong>
							' . _('Amount') . locale_number_format($MyRow['amount'], $CurrDecimalPlaces) . '<br/> ' . _('To local currency') . ' ' . locale_number_format($MyRow['amount'] * $Rate, $CurrDecimalPlaces) . ' ' . _('at rate') . ' ' . $Rate . '</div>';
					$GrandTotal+= $MyRow['amount'] * $Rate;
				}
				echo '<div class="centre">', _('Grand Total'), ' ', locale_number_format($GrandTotal, $CurrDecimalPlaces), '
					</div>';
			}
		}
	} else {
		prnMsg(_('This transaction does not exist as yet'), 'info');
	}
}
include ('includes/footer.php');

?>