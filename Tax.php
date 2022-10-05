<?php

include('includes/session.php');
if (isset($_POST['TaxAuthority']) and isset($_POST['PrintPDF']) and isset($_POST['NoOfPeriods']) and isset($_POST['ToPeriod'])) {
	$SQL = "SELECT lastdate_in_period
			FROM periods
			WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$ErrMsg = _('Could not determine the last date of the period selected') . '. ' . _('The sql returned the following error');
	$PeriodEndResult = DB_query($SQL, $ErrMsg);
	$PeriodEndRow = DB_fetch_row($PeriodEndResult);
	$PeriodEnd = ConvertSQLDate($PeriodEndRow[0]);
	$Result = DB_query("SELECT description FROM taxauthorities WHERE taxid='" . $_POST['TaxAuthority'] . "'");
	$TaxAuthDescription = DB_fetch_row($Result);
	$TaxAuthorityName = $TaxAuthDescription[0];
	include('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Tax Report') . ': ' . $TaxAuthorityName);
	$PDF->addInfo('Subject', $_POST['NoOfPeriods'] . ' ' . _('months to') . ' ' . $PeriodEnd);
	/*Now get the invoices for the tax report */
	/* The amounts of taxes are inserted into debtortranstaxes.taxamount in
	local currency and they are accumulated in debtortrans.ovgst in original
	currency. */
	$SQL = "SELECT debtortrans.trandate,
					debtortrans.type,
					systypes.typename,
					debtortrans.transno,
					debtortrans.debtorno,
					debtorsmaster.name,
					debtortrans.branchcode,
					(debtortrans.ovamount+debtortrans.ovfreight)/debtortrans.rate AS netamount,
					debtortranstaxes.taxamount AS tax
				FROM debtortrans
				INNER JOIN debtorsmaster
					ON debtortrans.debtorno=debtorsmaster.debtorno
				INNER JOIN systypes
					ON debtortrans.type=systypes.typeid
				INNER JOIN debtortranstaxes
					ON debtortrans.id = debtortranstaxes.debtortransid
				WHERE debtortrans.prd >= '" . ($_POST['ToPeriod'] - $_POST['NoOfPeriods'] + 1) . "'
					AND debtortrans.prd <= '" . $_POST['ToPeriod'] . "'
					AND (debtortrans.type=10 OR debtortrans.type=11)
					AND debtortranstaxes.taxauthid = '" . $_POST['TaxAuthority'] . "'
				ORDER BY debtortrans.id";
	$DebtorTransResult = DB_query($SQL, '', '', false, false); //don't trap errors in DB_query
	if (DB_error_no() != 0) {
		$Title = _('Taxation Reporting Error');
		include('includes/header.php');
		prnMsg(_('The accounts receivable transaction details could not be retrieved because') . ' ' . DB_error_msg(), 'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug == 1) {
			echo '<br />' . $SQL;
		}
		include('includes/footer.php');
		exit;
	}
	$SalesCount = 0;
	$SalesNet = 0;
	$SalesTax = 0;
	if ($_POST['DetailOrSummary'] == 'Detail') {
		$FontSize = 10;
		include('includes/PDFTaxPageHeader.inc');
		PageHeaderDetail();
		$YPos -= $FontSize; // Jumps additional line.
		$PDF->addText($Left_Margin, $YPos, $FontSize, _('Tax on Sales'));
		$YPos -= $FontSize;
		$YPos -= $FontSize;
		$FontSize = 8;
		while ($DebtorTransRow = DB_fetch_array($DebtorTransResult)) {
			$PDF->addText($Left_Margin, $YPos, $FontSize, ConvertSQLDate($DebtorTransRow['trandate']));
			$PDF->addText(82, $YPos, $FontSize, _($DebtorTransRow['typename']));
			$PDF->addTextWrap(140, $YPos - $FontSize, 40, $FontSize, $DebtorTransRow['transno'], 'right');
			$PDF->addText(180, $YPos, $FontSize, $DebtorTransRow['name']);
			$LeftOvers = $PDF->addTextWrap(380, $YPos - $FontSize, 60, $FontSize, $DebtorTransRow['branchcode'], 'left'); // RChacon: This data or debtor.reference ?
			$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, locale_number_format($DebtorTransRow['netamount'], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, locale_number_format($DebtorTransRow['tax'], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos -= $FontSize; // End-of-line line-feed.
			if ($YPos < $Bottom_Margin + $FontSize) {
				include('includes/PDFTaxPageHeader.inc');
				PageHeaderDetail();
			}
			$SalesCount++; // Counts sales transactions.
			$SalesNet += $DebtorTransRow['netamount']; // Accumulates sales net.
			$SalesTax += $DebtorTransRow['tax']; // Accumulates sales tax.
		}
		/*end listing while loop */
		// Prints out the sales totals:
		$FontSize = 10;
		if ($YPos < $Bottom_Margin + $FontSize * 4) {
			include('includes/PDFTaxPageHeader.inc');
			PageHeaderDetail();
		}
		$YPos -= $FontSize;
		$PDF->line(306, $YPos - $FontSize / 2, $Page_Width - $Right_Margin, $YPos - $FontSize / 2);
		$YPos -= $FontSize;
		$PDF->addText(306, $YPos, $FontSize, _('Total Outputs'));
		$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, locale_number_format($SalesNet, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, locale_number_format($SalesTax, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$YPos -= $FontSize;
		$PDF->line(306, $YPos - $FontSize / 2, $Page_Width - $Right_Margin, $YPos - $FontSize / 2); // Rule off under output totals.
		$YPos -= $FontSize;
	} else {
		while ($DebtorTransRow = DB_fetch_array($DebtorTransResult)) {
			$SalesCount++; // Counts sales transactions.
			$SalesNet += $DebtorTransRow['netamount']; // Accumulates sales net.
			$SalesTax += $DebtorTransRow['tax']; // Accumulates sales tax.
		}
		/*end listing while loop */
	}
	/*Now do the inputs from SuppTrans */
	/*Only have dates in SuppTrans no periods so need to get the starting date */
	if (mb_strpos($PeriodEnd, '/')) {
		$Date_Array = explode('/', $PeriodEnd);
	} elseif (mb_strpos($PeriodEnd, '.')) {
		$Date_Array = explode('.', $PeriodEnd);
	} elseif (mb_strpos($PeriodEnd, '-')) {
		$Date_Array = explode('-', $PeriodEnd);
	}
	if ($_SESSION['DefaultDateFormat'] == 'd/m/Y') {
		$StartDateSQL = Date('Y-m-d', mktime(0, 0, 0, (int) $Date_Array[1] - $_POST['NoOfPeriods'] + 1, 1, (int) $Date_Array[2]));
	} elseif ($_SESSION['DefaultDateFormat'] == 'm/d/Y') {
		$StartDateSQL = Date('Y-m-d', mktime(0, 0, 0, (int) $Date_Array[0] - $_POST['NoOfPeriods'] + 1, 1, (int) $Date_Array[2]));
	} elseif ($_SESSION['DefaultDateFormat'] == 'Y/m/d') {
		$StartDateSQL = Date('Y-m-d', mktime(0, 0, 0, (int) $Date_Array[1] - $_POST['NoOfPeriods'] + 1, 1, (int) $Date_Array[0]));
	} elseif ($_SESSION['DefaultDateFormat'] == 'd.m.Y') {
		$StartDateSQL = Date('Y-m-d', mktime(0, 0, 0, (int) $Date_Array[1] - $_POST['NoOfPeriods'] + 1, 1, (int) $Date_Array[2]));
	} elseif ($_SESSION['DefaultDateFormat'] == 'Y-m-d') {
		$StartDateSQL = Date('Y-m-d', mktime(0, 0, 0, (int) $Date_Array[1] - $_POST['NoOfPeriods'] + 1, 1, (int) $Date_Array[0]));
	}
	$SQL = "SELECT supptrans.trandate,
					supptrans.type,
					systypes.typename,
					supptrans.transno,
					suppliers.suppname,
					supptrans.suppreference,
					supptrans.ovamount/supptrans.rate AS netamount,
					supptranstaxes.taxamount/supptrans.rate AS taxamt
				FROM supptrans
				INNER JOIN suppliers
					ON supptrans.supplierno=suppliers.supplierid
				INNER JOIN systypes
					ON supptrans.type=systypes.typeid
				INNER JOIN supptranstaxes
					ON supptrans.id = supptranstaxes.supptransid
				WHERE supptrans.trandate >= '" . $StartDateSQL . "'
					AND supptrans.trandate <= '" . FormatDateForSQL($PeriodEnd) . "'
					AND (supptrans.type=20 OR supptrans.type=21)
					AND supptranstaxes.taxauthid = '" . $_POST['TaxAuthority'] . "'
				ORDER BY supptrans.id"; // ORDER BY supptrans.recno ?
	$SuppTransResult = DB_query($SQL, '', '', false, false); //doint trap errors in DB_query
	if (DB_error_no() != 0) {
		$Title = _('Taxation Reporting Error');
		include('includes/header.php');
		echo _('The accounts payable transaction details could not be retrieved because') . ' ' . DB_error_msg();
		echo '<br /><a href="' . $RootPath . '/index.php?">' . _('Back to the menu') . '</a>';
		if ($debug == 1) {
			echo '<br />' . $SQL;
		}
		include('includes/footer.php');
		exit;
	}
	$PettyCashSQL = "SELECT pcashdetails.date AS trandate,
							pcashdetailtaxes.pccashdetail AS transno,
							pcashdetailtaxes.description AS suppreference,
							pcashdetails.amount AS gross,
							pcashdetailtaxes.amount AS taxamt,
							www_users.realname AS suppname
						FROM pcashdetails
						INNER JOIN pcashdetailtaxes
							ON pcashdetails.counterindex=pcashdetailtaxes.pccashdetail
						INNER JOIN pctabs
							ON pcashdetails.tabcode = pctabs.tabcode
						INNER JOIN www_users
							ON pctabs.usercode=www_users.userid
						WHERE pcashdetails.date >= '" . $StartDateSQL . "'
							AND pcashdetails.date <= '" . FormatDateForSQL($PeriodEnd) . "'
							AND pcashdetailtaxes.taxauthid = '" . $_POST['TaxAuthority'] . "'
						ORDER BY pcashdetailtaxes.counterindex";
	$PettyCashResult = DB_query($PettyCashSQL, '', '', false, false); //doint trap errors in DB_query
	if (DB_error_no() != 0) {
		$Title = _('Taxation Reporting Error');
		include('includes/header.php');
		echo _('The petty cash transaction details could not be retrieved because') . ' ' . DB_error_msg();
		echo '<br /><a href="' . $RootPath . '/index.php?">' . _('Back to the menu') . '</a>';
		if ($debug == 1) {
			echo '<br />' . $SQL;
		}
		include('includes/footer.php');
		exit;
	}
	$PurchasesCount = 0;
	$PurchasesNet = 0;
	$PurchasesTax = 0;
	if ($_POST['DetailOrSummary'] == 'Detail') {
		$FontSize = 10;
		$YPos -= $FontSize; // Jumps additional line.
		$PDF->addText($Left_Margin, $YPos + $FontSize, $FontSize, _('Tax on Purchases'));
		$YPos -= $FontSize;
		// Prints out lines:
		$FontSize = 8;
		while ($SuppTransRow = DB_fetch_array($SuppTransResult)) {
			$PDF->addText($Left_Margin, $YPos, $FontSize, ConvertSQLDate($SuppTransRow['trandate']));
			$PDF->addText(82, $YPos, $FontSize, _($SuppTransRow['typename']));
			$PDF->addTextWrap(140, $YPos - $FontSize, 40, $FontSize, $SuppTransRow['transno'], 'right');
			$PDF->addText(180, $YPos, $FontSize, $SuppTransRow['suppname']);
			$PDF->addText(380, $YPos, $FontSize, $SuppTransRow['suppreference']); //****************NEW
			$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, locale_number_format($SuppTransRow['netamount'], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, locale_number_format($SuppTransRow['taxamt'], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos -= $FontSize; // End-of-line line-feed.
			if ($YPos < $Bottom_Margin + $FontSize) {
				include('includes/PDFTaxPageHeader.inc');
				PageHeaderDetail();
			}
			$PurchasesCount++; // Counts purchases transactions.
			$PurchasesNet += $SuppTransRow['netamount']; // Accumulates purchases net.
			$PurchasesTax += $SuppTransRow['taxamt']; // Accumulates purchases tax.
		}
		/*end listing while loop */
		// Print out the purchases totals:
		$FontSize = 10;
		if ($YPos < $Bottom_Margin + $FontSize * 4) {
			include('includes/PDFTaxPageHeader.inc');
			PageHeaderDetail();
		}
	} else {
		while ($SuppTransRow = DB_fetch_array($SuppTransResult)) {
			$PurchasesCount++; // Counts purchases transactions.
			$PurchasesNet += $SuppTransRow['netamount']; // Accumulates purchases net.
			$PurchasesTax += $SuppTransRow['taxamt']; // Accumulates purchases tax.
		}
		/*end listing while loop */
	}
	/* Now the petty cash transactions */
	$PettyCashCount = 0;
	$PettyCashNet = 0;
	$PettyCashTax = 0;
	if ($_POST['DetailOrSummary'] == 'Detail') {
		$FontSize = 10;
		$YPos -= $FontSize; // Jumps additional line.
		$YPos -= $FontSize;
		$PDF->addText($Left_Margin, $YPos + $FontSize, $FontSize, _('Tax on Petty Cash Expenses'));
		$YPos -= $FontSize;
		// Prints out lines:
		$FontSize = 8;
		while ($PettyCashRow = DB_fetch_array($PettyCashResult)) {
			$TotalTaxSQL = "SELECT SUM(-amount) totaltax FROM pcashdetailtaxes WHERE pccashdetail='" . $PettyCashRow['transno'] . "'";
			$TotalTaxResult = DB_query($TotalTaxSQL);
			$TotalTaxRow = DB_fetch_array($TotalTaxResult);
			$NetAmount = ((-$PettyCashRow['gross']) - $TotalTaxRow['totaltax']);
			$PDF->addText($Left_Margin, $YPos, $FontSize, ConvertSQLDate($PettyCashRow['trandate']));
			$PDF->addText(82, $YPos, $FontSize, _('Petty Cash Expense'));
			$PDF->addTextWrap(140, $YPos - $FontSize, 40, $FontSize, $PettyCashRow['transno'], 'right');
			$PDF->addText(180, $YPos, $FontSize, $PettyCashRow['suppname']);
			$PDF->addText(380, $YPos, $FontSize, $PettyCashRow['suppreference']); //****************NEW
			$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, locale_number_format($NetAmount, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, locale_number_format((-$PettyCashRow['taxamt']), $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos -= $FontSize; // End-of-line line-feed.
			if ($YPos < $Bottom_Margin + $FontSize) {
				include('includes/PDFTaxPageHeader.inc');
				PageHeaderDetail();
			}
			$PettyCashCount++; // Counts purchases transactions.
			$PettyCashNet += $NetAmount; // Accumulates purchases net.
			$PettyCashTax += (-$PettyCashRow['taxamt']); // Accumulates purchases tax.
		}
		/*end listing while loop */
		// Print out the purchases totals:
		$FontSize = 10;
		if ($YPos < $Bottom_Margin + $FontSize * 4) {
			include('includes/PDFTaxPageHeader.inc');
			PageHeaderDetail();
		}
		$YPos -= $FontSize;
		$PDF->line(306, $YPos - $FontSize / 2, $Page_Width - $Right_Margin, $YPos - $FontSize / 2);
		$YPos -= $FontSize;
		$PDF->addText(306, $YPos, $FontSize, _('Total Inputs'));
		$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, locale_number_format(($PurchasesNet + $PettyCashNet), $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, locale_number_format(($PurchasesTax + $PettyCashTax), $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$YPos -= $FontSize;
		$PDF->line(306, $YPos - $FontSize / 2, $Page_Width - $Right_Margin, $YPos - $FontSize / 2); // Rule off under output totals.
		$YPos -= $FontSize;
	} else {
		while ($PettyCashRow = DB_fetch_array($PettyCashResult)) {
			$TotalTaxSQL = "SELECT SUM(-amount) totaltax FROM pcashdetailtaxes WHERE pccashdetail='" . $PettyCashRow['transno'] . "'";
			$TotalTaxResult = DB_query($TotalTaxSQL);
			$TotalTaxRow = DB_fetch_array($TotalTaxResult);
			$NetAmount = ((-$PettyCashRow['gross']) - $TotalTaxRow['totaltax']);
			$PettyCashCount++; // Counts purchases transactions.
			$PettyCashNet += $NetAmount; // Accumulates purchases net.
			$PettyCashTax += (-$PettyCashRow['taxamt']); // Accumulates purchases tax.
		}
		/*end listing while loop */
	}
	/*OK and now the summary */
	include('includes/PDFTaxPageHeader.inc');
	PageHeaderSummary();
	$FontSize = 10;
	$YPos -= $FontSize; // Jumps additional line.
	// Table headings:
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Transactions'));
	$PDF->addTextWrap(150, $YPos - $FontSize, 100, $FontSize, _('Quantity'), 'right');
	$PDF->addTextWrap(250, $YPos - $FontSize, 100, $FontSize, _('Net'), 'right');
	$PDF->addTextWrap(350, $YPos - $FontSize, 100, $FontSize, _('Tax'), 'right');
	$PDF->addTextWrap(450, $YPos - $FontSize, 100, $FontSize, _('Total'), 'right');
	$YPos -= $FontSize;
	$YPos -= $FontSize; // Jumps additional line.
	// Sales totals:
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Outputs'));
	$PDF->addTextWrap(150, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesCount), 'right');
	$PDF->addTextWrap(250, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesNet, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$PDF->addTextWrap(350, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesTax, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$SalesTotal = $SalesNet + $SalesTax;
	$PDF->addTextWrap(450, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesTotal, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$YPos -= $FontSize;
	// Purchases totals:
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Inputs'));
	$PDF->addTextWrap(150, $YPos - $FontSize, 100, $FontSize, locale_number_format($PurchasesCount + $PettyCashCount), 'right');
	$PDF->addTextWrap(250, $YPos - $FontSize, 100, $FontSize, locale_number_format($PurchasesNet + $PettyCashNet, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$PDF->addTextWrap(350, $YPos - $FontSize, 100, $FontSize, locale_number_format($PurchasesTax + $PettyCashTax, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$PurchasesTotal = $PurchasesNet + $PettyCashNet + $PurchasesTax + $PettyCashTax;
	$PDF->addTextWrap(450, $YPos - $FontSize, 100, $FontSize, locale_number_format($PurchasesTotal, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$YPos -= $FontSize;
	$PDF->line(140, $YPos - $FontSize / 2, $Page_Width - $Right_Margin, $YPos - $FontSize / 2); // Rule off under output totals.
	$YPos -= $FontSize;
	// Sales minus Purchases:
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Difference'));
	$PDF->addTextWrap(150, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesCount - $PurchasesCount), 'right');
	$PDF->addTextWrap(250, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesNet - $PurchasesNet, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$PDF->addTextWrap(350, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesTax - $PurchasesTax, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$PurchasesTotal = $PurchasesNet + $PurchasesTax;
	$PDF->addTextWrap(450, $YPos - $FontSize, 100, $FontSize, locale_number_format($SalesTotal - $PurchasesTotal, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$YPos -= $FontSize;
	$YPos -= $FontSize * 4; // Jumps additional lines.
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Adjustments for Tax paid to Customs, FBT, entertainments etc must also be entered'));
	$YPos -= $FontSize;
	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos - $FontSize, $Page_Width - $Left_Margin - $Right_Margin, $FontSize, _('This information excludes tax on journal entries/payments/receipts. All tax should be entered through AR/AP.'));
	$YPos -= $FontSize;
	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos - $FontSize, $Page_Width - $Left_Margin - $Right_Margin, $FontSize, $LeftOvers);
	if ($SalesCount + $PurchasesCount + $PettyCashCount == 0) {
		$Title = _('Taxation Reporting Error');
		include('includes/header.php');
		prnMsg(_('There are no tax entries to list'), 'info');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	} else {
		$PDF->OutputD($_SESSION['DatabaseName'] . '_Tax_Report_' . Date('Y-m-d') . '.pdf');
	}
	$PDF->__destruct();
} else {
	/*The option to print PDF was not hit */
	$Title = _('Tax Reporting');
	$ViewTopic = 'Tax'; // Filename in ManualContents.php's TOC.
	$BookMark = 'Tax'; // Anchor's id in the manual's html document.
	include('includes/header.php');
	echo '<p class="page_title_text"><img alt="" src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/money_delete.png" title="' . _('Tax Report') . '" />' . ' ' . _('Tax Reporting') . '</p>';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';
	echo '<tr>
			<td>' . _('Tax Authority To Report On:') . ':</td>
			<td><select name="TaxAuthority">';
	$Result = DB_query("SELECT taxid, description FROM taxauthorities");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['taxid'] . '">' . $MyRow['description'] . '</option>';
	}
	echo '</select>
			</td>
		</tr>';
	echo '<tr>
			<td>' . _('Return Covering') . ':</td>
			<td>
				<select name="NoOfPeriods">' . '
					<option selected="selected" value="1">' . _('One Month') . '</option>' . '
					<option value="2">' . _('2 Months') . '</option>' . '
					<option value="3">' . _('3 Months') . '</option>' . '
					<option value="6">' . _('6 Months') . '</option>' . '
					<option value="12">' . _('12 Months') . '</option>' . '
					<option value="24">' . _('24 Months') . '</option>' . '
					<option value="48">' . _('48 Months') . '</option>' . '
				</select>
			</td>
		</tr>';
	echo '<tr>
			<td>' . _('Return To') . ':</td>
			<td><select name="ToPeriod">';
	$DefaultPeriod = GetPeriod(Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m'), 0, Date('Y'))));
	$SQL = "SELECT periodno,
					lastdate_in_period
				FROM periods";
	$ErrMsg = _('Could not retrieve the period data because');
	$Periods = DB_query($SQL, $ErrMsg);
	while ($MyRow = DB_fetch_array($Periods)) {
		if ($MyRow['periodno'] == $DefaultPeriod) {
			echo '<option selected="selected" value="' . $MyRow['periodno'] . '">' . ConvertSQLDate($MyRow['lastdate_in_period']) . '</option>';
		} else {
			echo '<option value="' . $MyRow['periodno'] . '">' . ConvertSQLDate($MyRow['lastdate_in_period']) . '</option>';
		}
	}
	echo '</select></td>
		</tr>
		<tr>
			<td>' . _('Detail Or Summary Only') . ':</td>
			<td>
				<select name="DetailOrSummary">
					<option value="Detail">' . _('Detail and Summary') . '</option>
					<option selected="selected" value="Summary">' . _('Summary Only') . '</option>
				</select>
			</td>
		</tr>
		</table>
		<div class="centre">
			<input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
		</div>
		</form>';
	include('includes/footer.php');
}
/*end of else not PrintPDF */
function PageHeaderDetail() {
	global $PDF;
	global $Page_Width;
	global $Left_Margin;
	global $Right_Margin;
	global $YPos;
	$FontSize = 8;
	// Draws a rectangle to put the headings in:
	$PDF->Rectangle($Left_Margin, // Rectangle $XPos.
		$YPos - $FontSize / 2, // Rectangle $YPos.
		$Page_Width - $Left_Margin - $Right_Margin, // Rectangle $Width.
		$FontSize * 2); // Rectangle $Height.
	$YPos -= $FontSize;
	// Prints the table headings:
	$PDF->addText($Left_Margin, $YPos, $FontSize, _('Date'));
	$PDF->addText(82, $YPos, $FontSize, _('Type'));
	$PDF->addTextWrap(140, $YPos - $FontSize, 40, $FontSize, _('Number'), 'right');
	$PDF->addText(180, $YPos, $FontSize, _('Name'));
	$PDF->addText(380, $YPos, $FontSize, _('Reference'));
	$PDF->addTextWrap(450, $YPos - $FontSize, 60, $FontSize, _('Net'), 'right');
	$PDF->addTextWrap($Page_Width - $Right_Margin - 60, $YPos - $FontSize, 60, $FontSize, _('Tax'), 'right');
	$YPos -= $FontSize * 2;
}
function PageHeaderSummary() {
	global $PDF;
	global $Page_Width;
	global $Left_Margin;
	global $Right_Margin;
	global $YPos;
	$FontSize = 10;
	// Draws a rectangle to put the headings in:
	$PDF->Rectangle($Left_Margin, // Rectangle $XPos.
		$YPos - $FontSize / 2, // Rectangle $YPos.
		$Page_Width - $Left_Margin - $Right_Margin, // Rectangle $Width.
		$FontSize * 2); // Rectangle $Height.
	$YPos -= $FontSize;
	// Prints the table headings:
	$PDF->addTextWrap($Left_Margin, $YPos - $FontSize, $Page_Width - $Left_Margin - $Right_Margin, $FontSize, _('Summary'), 'center');
	$YPos -= $FontSize * 2;
}
?>