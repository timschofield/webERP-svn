<?php
include ('includes/session.php');

if (isset($_POST['PrintPDF']) and isset($_POST['FromCriteria']) and mb_strlen($_POST['FromCriteria']) >= 1 and isset($_POST['ToCriteria']) and mb_strlen($_POST['ToCriteria']) >= 1) {

	include ('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Aged Customer Balance Listing'));
	$PDF->addInfo('Subject', _('Aged Customer Balances'));
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

	/*Now figure out the aged analysis for the customer range under review */
	if ($_SESSION['SalesmanLogin'] != '') {
		$_POST['Salesman'] = $_SESSION['SalesmanLogin'];
	}
	if (trim($_POST['Salesman']) != '') {
		$SalesLimit = " AND debtorsmaster.debtorno IN (SELECT DISTINCT debtorno FROM custbranch WHERE salesman = '" . $_POST['Salesman'] . "') ";
	} else {
		$SalesLimit = "";
	}
	if ($_POST['All_Or_Overdues'] == 'All') {
		$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
				SUM(
					debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
				) AS balance,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
						ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
						ELSE 0 END
					END
				) AS due,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
						ELSE 0 END
					END
				) AS overdue1,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
						ELSE 0 END
					END
				) AS overdue2
				FROM debtorsmaster,
					paymentterms,
					holdreasons,
					currencies,
					debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
					AND debtorsmaster.currcode = currencies.currabrev
					AND debtorsmaster.holdreason = holdreasons.reasoncode
					AND debtorsmaster.debtorno = debtortrans.debtorno
					AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
					AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
					AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
					" . $SalesLimit . "
				GROUP BY debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription
				HAVING
					ROUND(ABS(SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc)),currencies.decimalplaces) > 0";

	} elseif ($_POST['All_Or_Overdues'] == 'OverduesOnly') {

		$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
			SUM(
					debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
				) AS balance,
			SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= paymentterms.daysbeforedue
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
						THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
					END
				) AS due,
			SUM(
			  		CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
					END
				) AS overdue1,
			SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
					END
				) AS overdue2
			FROM debtorsmaster,
					paymentterms,
					holdreasons,
					currencies,
					debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
				AND debtorsmaster.currcode = currencies.currabrev
				AND debtorsmaster.holdreason = holdreasons.reasoncode
				AND debtorsmaster.debtorno = debtortrans.debtorno
				AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
				AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
				AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
				" . $SalesLimit . "
			GROUP BY debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription
			HAVING SUM(
				CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
							THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
					END
					) > 0.01";

	} elseif ($_POST['All_Or_Overdues'] == 'HeldOnly') {

		$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					currencies.decimalplaces,
					paymentterms.terms,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription,
					SUM(debtortrans.ovamount +
						debtortrans.ovgst +
						debtortrans.ovfreight +
						debtortrans.ovdiscount -
						debtortrans.alloc) AS balance,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= paymentterms.daysbeforedue
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
						END
					) AS due,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						END
					) AS overdue1,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
							ELSE 0 END
						END
					) AS overdue2
				FROM debtorsmaster,
				paymentterms,
				holdreasons,
				currencies,
				debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
				AND debtorsmaster.currcode = currencies.currabrev
				AND debtorsmaster.holdreason = holdreasons.reasoncode
				AND debtorsmaster.debtorno = debtortrans.debtorno
				AND holdreasons.dissallowinvoices=1
				AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
				AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
				AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
				" . $SalesLimit . "
				GROUP BY debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				paymentterms.terms,
				paymentterms.daysbeforedue,
				paymentterms.dayinfollowingmonth,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription
				HAVING ABS(SUM(
					debtortrans.ovamount +
					debtortrans.ovgst +
					debtortrans.ovfreight +
					debtortrans.ovdiscount -
					debtortrans.alloc)) >0.005";
	}
	$CustomerResult = DB_query($SQL, '', '', False, False);
	/*dont trap errors handled below*/

	if (DB_error_no() != 0) {
		$Title = _('Aged Customer Account Analysis') . ' - ' . _('Problem Report') . '.... ';
		include ('includes/header.php');
		prnMsg(_('The customer details could not be retrieved by the SQL because') . ' ' . DB_error_msg(), 'error');
		echo '<br /><a href="', $RootPath, '/index.php">', _('Back to the menu'), '</a>';
		if ($Debug == 1) {
			echo '<br />', $SQL;
		}
		include ('includes/footer.php');
		exit;
	}

	include ('includes/PDFAgedDebtorsPageHeader.php');

	$TotBal = 0;
	$TotCurr = 0;
	$TotDue = 0;
	$TotOD1 = 0;
	$TotOD2 = 0;

	$ListCount = DB_num_rows($CustomerResult);
	$CurrDecimalPlaces = 2; //by default
	while ($AgedAnalysis = DB_fetch_array($CustomerResult)) {
		$CurrDecimalPlaces = $AgedAnalysis['decimalplaces'];
		$DisplayDue = locale_number_format($AgedAnalysis['due'] - $AgedAnalysis['overdue1'], $CurrDecimalPlaces);
		$DisplayCurrent = locale_number_format($AgedAnalysis['balance'] - $AgedAnalysis['due'], $CurrDecimalPlaces);
		$DisplayBalance = locale_number_format($AgedAnalysis['balance'], $CurrDecimalPlaces);
		$DisplayOverdue1 = locale_number_format($AgedAnalysis['overdue1'] - $AgedAnalysis['overdue2'], $CurrDecimalPlaces);
		$DisplayOverdue2 = locale_number_format($AgedAnalysis['overdue2'], $CurrDecimalPlaces);

		$TotBal+= $AgedAnalysis['balance'];
		$TotDue+= ($AgedAnalysis['due'] - $AgedAnalysis['overdue1']);
		$TotCurr+= ($AgedAnalysis['balance'] - $AgedAnalysis['due']);
		$TotOD1+= ($AgedAnalysis['overdue1'] - $AgedAnalysis['overdue2']);
		$TotOD2+= $AgedAnalysis['overdue2'];

		$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 220 - $Left_Margin, $FontSize, $AgedAnalysis['debtorno'] . ' - ' . $AgedAnalysis['name'], 'left');
		$LeftOvers = $PDF->addTextWrap(220, $YPos, 60, $FontSize, $DisplayBalance, 'right');
		$LeftOvers = $PDF->addTextWrap(280, $YPos, 60, $FontSize, $DisplayCurrent, 'right');
		$LeftOvers = $PDF->addTextWrap(340, $YPos, 60, $FontSize, $DisplayDue, 'right');
		$LeftOvers = $PDF->addTextWrap(400, $YPos, 60, $FontSize, $DisplayOverdue1, 'right');
		$LeftOvers = $PDF->addTextWrap(460, $YPos, 60, $FontSize, $DisplayOverdue2, 'right');

		$YPos-= $line_height;
		if ($YPos < $Bottom_Margin + $line_height) {
			include ('includes/PDFAgedDebtorsPageHeader.inc');
		}

		if ($_POST['DetailedReport'] == 'Yes') {

			/*draw a line under the customer aged analysis*/
			$PDF->line($Page_Width - $Right_Margin, $YPos + 10, $Left_Margin, $YPos + 10);

			$SQL = "SELECT systypes.typename,
						debtortrans.transno,
						debtortrans.trandate,
						(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) as balance,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
						END) AS due,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
						END) AS overdue1,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
								THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc
								ELSE 0 END
						END) AS overdue2
				   FROM debtorsmaster,
						paymentterms,
						debtortrans,
						systypes
				   WHERE systypes.typeid = debtortrans.type
						AND debtorsmaster.paymentterms = paymentterms.termsindicator
						AND debtorsmaster.debtorno = debtortrans.debtorno
						AND debtortrans.debtorno = '" . $AgedAnalysis['debtorno'] . "'
						AND ABS(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc)>0.004";

			if ($_SESSION['SalesmanLogin'] != '') {
				$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
			}

			$DetailResult = DB_query($SQL, '', '', False, False);
			/*Dont trap errors */
			if (DB_error_no() != 0) {
				$Title = _('Aged Customer Account Analysis') . ' - ' . _('Problem Report') . '....';
				include ('includes/header.php');
				prnMsg(_('The details of outstanding transactions for customer') . ' - ' . $AgedAnalysis['debtorno'] . ' ' . _('could not be retrieved because') . ' - ' . DB_error_msg(), 'error');
				echo '<br /><a href="', $RootPath, '/index.php">', _('Back to the menu'), '</a>';
				if ($Debug == 1) {
					echo '<br />', _('The SQL that failed was'), '<br />', $SQL;
				}
				include ('includes/footer.php');
				exit;
			}

			while ($DetailTrans = DB_fetch_array($DetailResult)) {

				$LeftOvers = $PDF->addTextWrap($Left_Margin + 5, $YPos, 60, $FontSize, $DetailTrans['typename'], 'left');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 65, $YPos, 60, $FontSize, $DetailTrans['transno'], 'left');
				$DisplayTranDate = ConvertSQLDate($DetailTrans['trandate']);
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 125, $YPos, 75, $FontSize, $DisplayTranDate, 'left');

				$DisplayDue = locale_number_format($DetailTrans['due'] - $DetailTrans['overdue1'], $CurrDecimalPlaces);
				$DisplayCurrent = locale_number_format($DetailTrans['balance'] - $DetailTrans['due'], $CurrDecimalPlaces);
				$DisplayBalance = locale_number_format($DetailTrans['balance'], $CurrDecimalPlaces);
				$DisplayOverdue1 = locale_number_format($DetailTrans['overdue1'] - $DetailTrans['overdue2'], $CurrDecimalPlaces);
				$DisplayOverdue2 = locale_number_format($DetailTrans['overdue2'], $CurrDecimalPlaces);

				$LeftOvers = $PDF->addTextWrap(220, $YPos, 60, $FontSize, $DisplayBalance, 'right');
				$LeftOvers = $PDF->addTextWrap(280, $YPos, 60, $FontSize, $DisplayCurrent, 'right');
				$LeftOvers = $PDF->addTextWrap(340, $YPos, 60, $FontSize, $DisplayDue, 'right');
				$LeftOvers = $PDF->addTextWrap(400, $YPos, 60, $FontSize, $DisplayOverdue1, 'right');
				$LeftOvers = $PDF->addTextWrap(460, $YPos, 60, $FontSize, $DisplayOverdue2, 'right');

				$YPos-= $line_height;
				if ($YPos < $Bottom_Margin + $line_height) {
					$PageNumber++;
					include ('includes/PDFAgedDebtorsPageHeader.php');
				}

			}
			/*end while there are detail transactions to show */
			$FontSize = 8;
			/*draw a line under the detailed transactions before the next customer aged analysis*/
			$PDF->line($Page_Width - $Right_Margin, $YPos + 10, $Left_Margin, $YPos + 10);
		}
		/*Its a detailed report */
	}
	/*end customer aged analysis while loop */

	$YPos-= $line_height;
	if ($YPos < $Bottom_Margin + (2 * $line_height)) {
		$PageNumber++;
		include ('includes/PDFAgedDebtorsPageHeader.php');
	} elseif ($_POST['DetailedReport'] == 'Yes') {
		//dont do a line if the totals have to go on a new page
		$PDF->line($Page_Width - $Right_Margin, $YPos + 10, 220, $YPos + 10);
	}

	$DisplayTotBalance = locale_number_format($TotBal, $CurrDecimalPlaces);
	$DisplayTotDue = locale_number_format($TotDue, $CurrDecimalPlaces);
	$DisplayTotCurrent = locale_number_format($TotCurr, $CurrDecimalPlaces);
	$DisplayTotOverdue1 = locale_number_format($TotOD1, $CurrDecimalPlaces);
	$DisplayTotOverdue2 = locale_number_format($TotOD2, $CurrDecimalPlaces);

	$LeftOvers = $PDF->addTextWrap(220, $YPos, 60, $FontSize, $DisplayTotBalance, 'right');
	$LeftOvers = $PDF->addTextWrap(280, $YPos, 60, $FontSize, $DisplayTotCurrent, 'right');
	$LeftOvers = $PDF->addTextWrap(340, $YPos, 60, $FontSize, $DisplayTotDue, 'right');
	$LeftOvers = $PDF->addTextWrap(400, $YPos, 60, $FontSize, $DisplayTotOverdue1, 'right');
	$LeftOvers = $PDF->addTextWrap(460, $YPos, 60, $FontSize, $DisplayTotOverdue2, 'right');

	if ($ListCount == 0) {
		$Title = _('Aged Customer Account Analysis') . ' - ' . _('Problem Report') . '....';
		include ('includes/header.php');
		prnMsg(_('There are no customers with balances meeting the criteria specified to list'), 'info');
		echo '<br /><a href="', $RootPath, '/index.php">', _('Back to the menu'), '</a>';
		include ('includes/footer.php');
		exit;
	} else {
		$PDF->OutputD($_SESSION['DatabaseName'] . '_' . 'AgedDebtors_' . date('Y-m-d') . '.pdf');
		$PDF->__destruct();
	}

} else {
	/*The option to print PDF was not hit */

	$Title = _('Aged Debtor Analysis');
	/* Manual links before header.php */
	$ViewTopic = 'ARReports';
	$BookMark = 'AgedDebtors';
	include ('includes/header.php');

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	if ((!isset($_POST['FromCriteria']) or !isset($_POST['ToCriteria']))) {

		/*if $FromCriteria is not set then show a form to allow input	*/

		$SQL = "SELECT min(debtorno) AS fromcriteria,
						max(debtorno) AS tocriteria
					FROM debtorsmaster";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">
			<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
			<fieldset>
			<legend>', _('Select Report Criteria'), '</legend>
			<field>
				<label for="FromCriteria">', _('From Customer Code'), ':</label>
				<input type="text" autofocus="autofocus" required="required" maxlength="6" size="7" name="FromCriteria" value="', $MyRow['fromcriteria'], '" />
				<fieldhelp>', _('The debtor code to start the report with.'), '</fieldhelp>
			</field>
			<field>
				<label for="ToCriteria">' . _('To Customer Code') . ':</label>
				<input type="text" required="required" maxlength="6" size="7" name="ToCriteria" value="', $MyRow['tocriteria'], '" />
				<fieldhelp>', _('The debtor code to end the report with.'), '</fieldhelp>
			</field>
			<field>
				<label for="All_Or_Overdues">', _('All balances or overdues only'), ':</label>
				<select name="All_Or_Overdues">
					<option selected="selected" value="All">', _('All customers with balances'), '</option>
					<option value="OverduesOnly">', _('Overdue accounts only'), '</option>
					<option value="HeldOnly">', _('Held accounts only'), '</option>
				</select>
				<fieldhelp>', _('Show all accounts with balances, or just those customers who have overdue amounts.'), '</fieldhelp>
			</field>
			<field>
				<label for="Salesman">', _('Only Show Customers Of'), ':</label>';
		if ($_SESSION['SalesmanLogin'] != '') {
			echo '<div class="fieldtext">', $_SESSION['UsersRealName'], '</div>';
		} else {
			$SQL = "SELECT salesmancode, salesmanname FROM salesman";
			$Result = DB_query($SQL);

			echo '<select name="Salesman">
					<option value="">' . _('All Salespeople') . '</option>';
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<option value="', $MyRow['salesmancode'], '">', $MyRow['salesmanname'], '</option>';
			}
			echo '</select>
				<fieldhelp>', _('Show accounts for all sale people, or just individuals.'), '</fieldhelp>
			</field>';
		}

		$SQL = "SELECT currency, currabrev FROM currencies";
		$Result = DB_query($SQL);

		echo '<field>
				<label for="Currency">', _('Only show customers trading in'), ':</label>
				<select name="Currency">';
		while ($MyRow = DB_fetch_array($Result)) {
			if ($MyRow['currabrev'] == $_SESSION['CompanyRecord']['currencydefault']) {
				echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $MyRow['currency'], '</option>';
			} else {
				echo '<option value="', $MyRow['currabrev'], '">', $MyRow['currency'], '</option>';
			}
		}
		echo '</select>
			<fieldhelp>', _('Show accounts for customers trading in this currency.'), '</fieldhelp>
		</field>';

		echo '<field>
				<label for="DetailedReport">', _('Summary or detailed report'), ':</label>
				<select name="DetailedReport">
					<option selected="selected" value="No">', _('Summary Report'), '</option>
					<option value="Yes">', _('Detailed Report'), '</option>
				</select>
				<fieldhelp>', _('Show a summary report, or include transaction details in the report.'), '</fieldhelp>
			</field>
			</fieldset>
			<div class="centre">
				<input type="submit" name="PrintPDF" value="', _('Print PDF'), '" />
			</div>
			</form>';
	}
	include ('includes/footer.php');
}
/*end of else not PrintPDF */
?>