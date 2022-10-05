<?php
/*Code to print footer details for each supplier being paid and process payment total for each supplier
as necessary an include file used since the same code is used twice */
$YPos -= (0.5*$line_height);
$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);

$LeftOvers = $PDF->addTextWrap($Left_Margin+10,$YPos,340-$Left_Margin,$FontSize,_('Total Due For') . ' ' . $SupplierName, 'left');

$TotalPayments += $AccumBalance;
$TotalAccumDiffOnExch += $AccumDiffOnExch;

$LeftOvers = $PDF->addTextWrap(340,$YPos,60,$FontSize,locale_number_format($AccumBalance,$CurrDecimalPlaces), 'right');
$LeftOvers = $PDF->addTextWrap(405,$YPos,60,$FontSize,locale_number_format($AccumDiffOnExch,$CurrDecimalPlaces), 'right');


if (isset($_POST['PrintPDFAndProcess'])){

	if (is_numeric(filter_number_format($_POST['Ref']))) {
		$PaytReference = filter_number_format($_POST['Ref']) + $RefCounter;
	} else {
		$PaytReference = $_POST['Ref'] . ($RefCounter + 1);
	}
	$RefCounter++;

	/*Do the inserts for the payment transaction into the Supp Trans table*/

	$SQL = "INSERT INTO supptrans (type,
					transno,
					suppreference,
					supplierno,
					trandate,
					duedate,
					inputdate,
					settled,
					rate,
					ovamount,
					diffonexch,
					alloc)
			VALUES (22,
				'" . $SuppPaymentNo . "',
				'" . $PaytReference . "',
				'" . $SupplierID . "',
				'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
				'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
				'" . date('Y-m-d H-i-s') . "',
				1,
				'" . filter_number_format($_POST['ExRate']) . "',
				'" . -$AccumBalance . "',
				'" . -$AccumDiffOnExch . "',
				'" . -$AccumBalance . "')";

	$ProcessResult = DB_query($SQL,'','',false,false);
	if (DB_error_no() !=0) {
		$Title = _('Payment Processing - Problem Report');
		include('header.php');
		prnMsg(_('None of the payments will be processed because the payment record for') . ' ' . $SupplierName . ' ' . _('could not be inserted because') . ' - ' . DB_error_msg(),'error');
		echo '<br>
				<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
			prnMsg(_('The SQL that failed was') . ' ' . $SQL,'error');
		}
		$ProcessResult = DB_Txn_Rollback();
		include('footer.php');
		exit;
	}

	$PaymentTransID = DB_Last_Insert_ID('supptrans','id');

	/*Do the inserts for the allocation record against the payment for this charge */

	foreach ($Allocs AS $AllocTrans){ /*loop through the array of allocations */

		$SQL = "INSERT INTO suppallocs (amt,
						datealloc,
						transid_allocfrom,
						transid_allocto)
				VALUES (
						'" . $AllocTrans->Amount . "',
						'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
						'" . $PaymentTransID . "',
						'" . $AllocTrans->TransID . "')";

		$ProcessResult = DB_query($SQL);
		if (DB_error_no() !=0) {
			$Title = _('Payment Processing - Problem Report') . '.... ';
			include('header.php');
			prnMsg(_('None of the payments will be processed since an allocation record for') . $SupplierName . _('could not be inserted because') . ' - ' . DB_error_msg(),'error');
			echo '<br><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
			if ($debug==1){
				prnMsg(_('The SQL that failed was') . $SQL,'error');
			}
			$ProcessResult = DB_Txn_Rollback();
			include('footer.php');
			exit;
		}
	} /*end of the loop to insert the allocation records */


	/*Do the inserts for the payment transaction into the BankTrans table*/
	$SQL="INSERT INTO banktrans (bankact,
					ref,
					exrate,
					transdate,
					banktranstype,
					amount) ";
   	$SQL = $SQL .  "VALUES ( " . $_POST['BankAccount'] . ",
				'" . $PaytReference . " " . $SupplierID . "',
				" . filter_number_format($_POST['ExRate']) . ",
				'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
				'" . $_POST['PaytType'] . "',
				" .  -$AccumBalance . ")";
	$ProcessResult = DB_query($SQL,'','',false,false);
	if (DB_error_no() !=0) {
		$Title = _('Payment Processing - Problem Report');
		include('header.php');
		prnMsg(_('None of the payments will be processed because the bank account payment record for') . ' ' . $SupplierName . ' ' . _('could not be inserted because') . ' - ' . DB_error_msg(),'error');
		echo '<br />
				<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
			prnMsg(_('The SQL that failed was') . ' ' . $SQL,'error');
		}
		$ProcessResult = DB_Txn_Rollback();
		include('footer.php');
		exit;
	}

	/*If the General Ledger Link is activated */
	if ($_SESSION['CompanyRecord']['gllink_creditors']==1){

		$PeriodNo = GetPeriod($_POST['AmountsDueBy']);

		/*Do the GL trans for the payment CR bank */

		$SQL = "INSERT INTO gltrans (type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount )
				VALUES (22,
					'" . $SuppPaymentNo . "',
					'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
					'" . $PeriodNo . "',
					'" . $_POST['BankAccount'] . "',
					'" . $SupplierID . " - " . $SupplierName . ' ' . _('payment run on') . ' ' . Date($_SESSION['DefaultDateFormat']) . ' - ' . $PaytReference . "',
					'" . (-$AccumBalance/ filter_number_format($_POST['ExRate'])) . "')";

		$ProcessResult = DB_query($SQL,'','',false,false);
		if (DB_error_no() !=0) {
			$Title = _('Payment Processing') . ' - ' . _('Problem Report') . '.... ';
			include('header.php');
			prnMsg(_('None of the payments will be processed since the general ledger posting for the payment to') . ' ' . $SupplierName . ' ' . _('could not be inserted because') . ' - ' . DB_error_msg(),'error');
			echo '<br />
					<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
			if ($debug==1){
				prnMsg(_('The SQL that failed was') . ':<br />' . $SQL, 'error');
			}
			$ProcessResult = DB_Txn_Rollback();
			include('footer.php');
			exit;
		}

		/*Do the GL trans for the payment DR creditors */

		$SQL = "INSERT INTO gltrans (type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount )
				VALUES (22,
					'" . $SuppPaymentNo . "',
					'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['CompanyRecord']['creditorsact'] . "',
					'" . $SupplierID . ' - ' . $SupplierName . ' ' . _('payment run on') . ' ' . Date($_SESSION['DefaultDateFormat']) . ' - ' . $PaytReference . "',
					'" . ($AccumBalance/ filter_number_format($_POST['ExRate'])  + $AccumDiffOnExch) . "')";

		$ProcessResult = DB_query($SQL,'','',false,false);
		if (DB_error_no() !=0) {
			$Title = _('Payment Processing - Problem Report');
			include('header.php');
			prnMsg(_('None of the payments will be processed since the general ledger posting for the payment to') . ' ' . $SupplierName . ' ' . _('could not be inserted because') . ' - ' . DB_error_msg(),'error');
			echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
			if ($debug==1){
				prnMsg(_('The SQL that failed was') . ':<BR>' . $SQL,'error');
			}
			$ProcessResult = DB_Txn_Rollback();
			include('footer.php');
			exit;
		}

		/*Do the GL trans for the exch diff */
		if ($AccumDiffOnExch != 0){
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount )
						VALUES (22,
							'" . $SuppPaymentNo . "',
							'" . FormatDateForSQL($_POST['AmountsDueBy']) . "',
							'" . $PeriodNo . "',
							'" . $_SESSION['CompanyRecord']['purchasesexchangediffact'] . "',
							'" . $SupplierID . ' - ' . $SupplierName . ' ' . _('payment run on') . ' ' . Date($_SESSION['DefaultDateFormat']) . " - " . $PaytReference . "',
							'" . (-$AccumDiffOnExch) . "')";

			$ProcessResult = DB_query($SQL,'','',false,false);
			if (DB_error_no() !=0) {
				$Title = _('Payment Processing - Problem Report');
				include('header.php');
				prnMsg(_('None of the payments will be processed since the general ledger posting for the exchange difference on') . ' ' . $SupplierName . ' ' . _('could not be inserted because') .' - ' . DB_error_msg(),'error');
				echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
				if ($debug==1){
					prnMsg(_('The SQL that failed was: ') . '<br />' . $SQL,'error');
				}
				$ProcessResult = DB_Txn_Rollback();
				include('footer.php');
				exit;
			}
		}
		EnsureGLEntriesBalance(22,$SuppPaymentNo);
	} /*end if GL linked to creditors */


}

$YPos -= (1.5*$line_height);

$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);

$YPos -= $line_height;

if ($YPos < $Bottom_Margin + $line_height){
	$PageNumber++;
	include('PDFPaymentRunPageHeader.inc');
}

?>