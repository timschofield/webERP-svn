<?php
include ('includes/session.php');

if (isset($_POST['PrintPDF']) and isset($_POST['FromCriteria']) and mb_strlen($_POST['FromCriteria']) >= 1 and isset($_POST['ToCriteria']) and mb_strlen($_POST['ToCriteria']) >= 1) {

	include ('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Customer Balance Listing'));
	$PDF->addInfo('Subject', _('Customer Balances'));
	$FontSize = 12;
	$PageNumber = 0;
	$line_height = 12;

	$SQL = "SELECT min(debtorno) AS fromcriteria,
					max(debtorno) AS tocriteria
				FROM debtorsmaster";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	if ($_POST['FromCriteria'] == '') {
		$_POST['FromCriteria'] = $MyRow['fromcriteria'];
	}
	if ($_POST['ToCriteria'] == '') {
		$_POST['Toriteria'] = $MyRow['tocriteria'];
	}

	/*Get the date of the last day in the period selected */

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno = '" . $_POST['PeriodEnd'] . "'";
	$PeriodEndResult = DB_query($SQL, _('Could not get the date of the last day in the period selected'));
	$PeriodRow = DB_fetch_row($PeriodEndResult);
	$PeriodEndDate = ConvertSQLDate($PeriodRow[0]);

	/*Now figure out the aged analysis for the customer range under review */

	$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
		  			currencies.currency,
		  			currencies.decimalplaces,
					SUM((debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc)/debtortrans.rate) AS balance,
					SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) AS fxbalance,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "' THEN
					(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount)/debtortrans.rate ELSE 0 END) AS afterdatetrans,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "'
						AND (debtortrans.type=11 OR debtortrans.type=12) THEN
						debtortrans.diffonexch ELSE 0 END) AS afterdatediffonexch,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "' THEN
					debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount ELSE 0 END
					) AS fxafterdatetrans
			FROM debtorsmaster INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
			INNER JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
			WHERE debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
			AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
			GROUP BY debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces";

	$CustomerResult = DB_query($SQL, '', '', false, false);

	if (DB_error_no() != 0) {
		$Title = _('Customer Balances') . ' - ' . _('Problem Report');
		include ('includes/header.php');
		prnMsg(_('The customer details could not be retrieved by the SQL because') . DB_error_msg(), 'error');
		echo '<a href="', $RootPath, '/index.php">', _('Back to the menu'), '</a>';
		if ($Debug == 1) {
			echo '<br />' . $SQL;
		}
		include ('includes/footer.php');
		exit;
	}

	if (DB_num_rows($CustomerResult) == 0) {
		$Title = _('Customer Balances') . ' - ' . _('Problem Report');
		include ('includes/header.php');
		prnMsg(_('The customer details listing has no clients to report on'), 'warn');
		echo '<a href="', $RootPath, '/index.php">', _('Back to the menu'), '</a>';
		include ('includes/footer.php');
		exit;
	}

	include ('includes/PDFDebtorBalsPageHeader.php');

	$TotBal = 0;

	while ($DebtorBalances = DB_fetch_array($CustomerResult)) {

		$Balance = $DebtorBalances['balance'] - $DebtorBalances['afterdatetrans'] + $DebtorBalances['afterdatediffonexch'];
		$FXBalance = $DebtorBalances['fxbalance'] - $DebtorBalances['fxafterdatetrans'];

		if (abs($Balance) > 0.009 or ABS($FXBalance) > 0.009) {

			$DisplayBalance = locale_number_format($DebtorBalances['balance'] - $DebtorBalances['afterdatetrans'], $DebtorBalances['decimalplaces']);
			$DisplayFXBalance = locale_number_format($DebtorBalances['fxbalance'] - $DebtorBalances['fxafterdatetrans'], $DebtorBalances['decimalplaces']);

			$TotBal+= $Balance;

			$LeftOvers = $PDF->addTextWrap($Left_Margin + 3, $YPos, 220 - $Left_Margin, $FontSize, $DebtorBalances['debtorno'] . ' - ' . html_entity_decode($DebtorBalances['name'], ENT_QUOTES, 'UTF-8'), 'left');
			$LeftOvers = $PDF->addTextWrap(220, $YPos, 60, $FontSize, $DisplayBalance, 'right');
			$LeftOvers = $PDF->addTextWrap(280, $YPos, 60, $FontSize, $DisplayFXBalance, 'right');
			$LeftOvers = $PDF->addTextWrap(350, $YPos, 100, $FontSize, $DebtorBalances['currency'], 'left');

			$YPos-= $line_height;
			if ($YPos < $Bottom_Margin + $line_height) {
				include ('includes/PDFDebtorBalsPageHeader.php');
			}
		}
	}
	/*end customer aged analysis while loop */

	$YPos-= $line_height;
	if ($YPos < $Bottom_Margin + (2 * $line_height)) {
		$PageNumber++;
		include ('includes/PDFDebtorBalsPageHeader.php');
	}

	$DisplayTotBalance = locale_number_format($TotBal, $_SESSION['CompanyRecord']['decimalplaces']);

	$LeftOvers = $PDF->addTextWrap(50, $YPos, 160, $FontSize, _('Total balances'), 'left');
	$LeftOvers = $PDF->addTextWrap(220, $YPos, 60, $FontSize, $DisplayTotBalance, 'right');

	$PDF->OutputD($_SESSION['DatabaseName'] . '_DebtorBals_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();

} else {
	/*The option to print PDF was not hit */

	$Title = _('Debtor Balances');
	/* Manual links before header.php */
	$ViewTopic = 'ARReports';
	$BookMark = 'PriorMonthDebtors';
	include ('includes/header.php');
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Debtor Balances'), '" alt="', _('Debtor Balances'), '" />', ' ', $Title, '
		</p>';

	$SQL = "SELECT min(debtorno) AS fromcriteria,
					max(debtorno) AS tocriteria
				FROM debtorsmaster";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	if (!isset($_POST['FromCriteria']) or !isset($_POST['ToCriteria'])) {

		/*if $FromCriteria is not set then show a form to allow input	*/

		echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		echo '<fieldset>
				<legend>', _('Input criteria for report'), '</legend>';

		echo '<field>
				<label for="FromCriteria">', _('From Customer Code'), ':</label>
				<input type="text" autofocus="autofocus" required="required" maxlength="10" size="7" name="FromCriteria" value="', $MyRow['fromcriteria'], '" />
			</field>';

		echo '<field>
				<label for="ToCriteria">', _('To Customer Code'), ':</label>
				<input type="text" required="required" maxlength="10" size="7" name="ToCriteria" value="', $MyRow['tocriteria'], '" />
			</field>';

		echo '<field>
				<label for="PeriodEnd">', _('Balances As At'), ':</label>
				<select name="PeriodEnd">';

		$SQL = "SELECT periodno, lastdate_in_period FROM periods WHERE lastdate_in_period <= CURRENT_DATE ORDER BY periodno DESC";
		$Periods = DB_query($SQL, _('Could not retrieve period data because'), _('The SQL that failed to get the period data was'));

		while ($MyRow = DB_fetch_array($Periods)) {

			echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';

		}
	}

	echo '</select>
		</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="PrintPDF" value="', _('Print PDF'), '" />
		</div>
	</form>';

	include ('includes/footer.php');
}
/*end of else not PrintPDF */

?>