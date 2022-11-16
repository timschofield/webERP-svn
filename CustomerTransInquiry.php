<?php
include ('includes/session.php');
$Title = _('Customer Transactions Inquiry');
/* Manual links before header.php */
$ViewTopic = 'ARInquiries';
$BookMark = 'ARTransInquiry';
include ('includes/header.php');

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('Transaction Inquiry'), '" alt="" />', ' ', _('Transaction Inquiry'), '
	</p>';

echo '<div class="page_help_text">', _('Choose which type of transaction to report on.'), '</div>';

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

$SQL = "SELECT typeid,
				typename
		FROM systypes
		WHERE typeid >= 10
		AND typeid <= 14";
$ResultTypes = DB_query($SQL);
echo '<fieldset>
		<legend>', _('Inquiry Criteria'), '</legend>';

echo '<field>
		<label for="TransType">', _('Type'), ':</label>
		<select name="TransType">
			<option value="All">', _('All'), '</option>';
while ($MyRow = DB_fetch_array($ResultTypes)) {
	if (isset($_POST['TransType'])) {
		if ($MyRow['typeid'] == $_POST['TransType']) {
			echo '<option selected="selected" value="', $MyRow['typeid'], '">', _($MyRow['typename']), '</option>';
		} else {
			echo '<option value="', $MyRow['typeid'], '">', _($MyRow['typename']), '</option>';
		}
	} else {
		echo '<option value="', $MyRow['typeid'], '">', _($MyRow['typename']), '</option>';
	}
}
echo '</select>
	</field>';

if (!isset($_POST['FromDate'])) {
	$_POST['FromDate'] = Date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, Date('m'), 1, Date('Y')));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = Date($_SESSION['DefaultDateFormat']);
}
echo '<field>
		<label for="FromDate">', _('From'), ':</label>
		<input class="date" type="text" name="FromDate" required="required" maxlength="10" size="11" value="', $_POST['FromDate'], '" />
	</field>';

echo '<field>
		<label for="ToDate">', _('To'), ':</label>
		<input class="date" type="text" name="ToDate" required="required" maxlength="10" size="11" value="', $_POST['ToDate'], '" />
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="ShowResults" value="', _('Show transactions'), '" />
	</div>
</form>';

if (isset($_POST['ShowResults']) and $_POST['TransType'] != '') {
	$SQL_FromDate = FormatDateForSQL($_POST['FromDate']);
	$SQL_ToDate = FormatDateForSQL($_POST['ToDate']);
	$SQL = "SELECT transno,
		   		trandate,
				debtortrans.debtorno,
				branchcode,
				reference,
				invtext,
				order_,
				debtortrans.rate,
				ovamount+ovgst+ovfreight+ovdiscount as totalamt,
				currcode,
				typename,
				decimalplaces AS currdecimalplaces
			FROM debtortrans
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			INNER JOIN systypes
				ON debtortrans.type = systypes.typeid
			WHERE ";

	$SQL = $SQL . "trandate >='" . $SQL_FromDate . "' AND trandate <= '" . $SQL_ToDate . "'";
	if ($_POST['TransType'] != 'All') {
		$SQL.= " AND type = '" . $_POST['TransType'] . "'";
	}
	$SQL.= " ORDER BY id";

	$ErrMsg = _('The customer transactions for the selected criteria could not be retrieved because') . ' - ' . DB_error_msg();
	$DbgMsg = _('The SQL that failed was');
	$TransResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($TransResult) > 0) {
		echo '<table>
				<tr>
					<th>', _('Type'), '</th>
					<th>', _('Number'), '</th>
					<th>', _('Date'), '</th>
					<th>', _('Customer'), '</th>
					<th>', _('Branch'), '</th>
					<th>', _('Reference'), '</th>
					<th>', _('Comments'), '</th>
					<th>', _('Order'), '</th>
					<th>', _('Ex Rate'), '</th>
					<th>', _('Amount'), '</th>
					<th>', _('Currency'), '</th>
				</tr>';

		while ($MyRow = DB_fetch_array($TransResult)) {

			if ($_POST['TransType'] == 10) {
				/* invoices */

				echo '<tr class="striped_row">
						<td>', _($MyRow['typename']), '</td>
						<td>', $MyRow['transno'], '</td>
						<td>', ConvertSQLDate($MyRow['trandate']), '</td>
						<td>', $MyRow['debtorno'], '</td>
						<td>', $MyRow['branchcode'], '</td>
						<td>', $MyRow['reference'], '</td>
						<td style="width:200px">', $MyRow['invtext'], '</td>
						<td>', $MyRow['order_'], '</td>
						<td class="number">', locale_number_format($MyRow['rate'], 6), '</td>
						<td class="number">', locale_number_format($MyRow['totalamt'], $MyRow['currdecimalplaces']), '</td>
						<td>', $MyRow['currcode'], '</td>
						<td><a target="_blank" href="', $RootPath, '/PrintCustTrans.php?FromTransNo=', urlencode($MyRow['transno']), '&InvOrCredit=Invoice">
							<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/preview.png" title="', _('Click to preview the invoice'), '" /></a>
						</td>
					</tr>';

			} elseif ($_POST['TransType'] == 11) {
				/* credit notes */
				echo '<tr class="striped_row">
						<td>', _($MyRow['typename']), '</td>
						<td>', $MyRow['transno'], '</td>
						<td>', ConvertSQLDate($MyRow['trandate']), '</td>
						<td>', $MyRow['debtorno'], '</td>
						<td>', $MyRow['branchcode'], '</td>
						<td>', $MyRow['reference'], '</td>
						<td style="width:200px">', $MyRow['invtext'], '</td>
						<td>', $MyRow['order_'], '</td>
						<td class="number">', locale_number_format($MyRow['rate'], 6), '</td>
						<td class="number">', locale_number_format($MyRow['totalamt'], $MyRow['currdecimalplaces']), '</td>
						<td>', $MyRow['currcode'], '</td>
						<td><a target="_blank" href="', $RootPath, '/PrintCustTrans.php?FromTransNo=', $MyRow['transno'], '&InvOrCredit=Credit">
								<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/preview.png" title="', _('Click to preview the credit'), '" /></a>
						</td>
					</tr>';
			} else {
				/* otherwise */
				echo '<tr class="striped_row">
						<td>', _($MyRow['typename']), '</td>
						<td>', $MyRow['transno'], '</td>
						<td>', ConvertSQLDate($MyRow['trandate']), '</td>
						<td>', $MyRow['debtorno'], '</td>
						<td>', $MyRow['branchcode'], '</td>
						<td>', $MyRow['reference'], '</td>
						<td style="width:200px">', $MyRow['invtext'], '</td>
						<td>', $MyRow['order_'], '</td>
						<td class="number">', locale_number_format($MyRow['rate'], 6), '</td>
						<td class="number">', locale_number_format($MyRow['totalamt'], $MyRow['currdecimalplaces']), '</td>
						<td>', $MyRow['currcode'], '</td>
					</tr>';
			}

		}
		//end of while loop
		echo '</table>';
	} else {
		prnMsg(_('There are no transactions meeting this criteria'), 'info');
	}
}

include ('includes/footer.php');

?>