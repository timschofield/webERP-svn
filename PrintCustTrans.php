<?php


include('includes/session.php');

$ViewTopic = 'ARReports';
$BookMark = 'PrintInvoicesCredits';

if (isset($_GET['FromTransNo'])) {
	$FromTransNo = filter_number_format($_GET['FromTransNo']);
} elseif (isset($_POST['FromTransNo'])) {
	$FromTransNo = filter_number_format($_POST['FromTransNo']);
} else {
	$FromTransNo = '';
}

if (isset($_GET['InvOrCredit'])) {
	$InvOrCredit = $_GET['InvOrCredit'];
} elseif (isset($_POST['InvOrCredit'])) {
	$InvOrCredit = $_POST['InvOrCredit'];
}

if (isset($_GET['PrintPDF'])) {
	$PrintPDF = $_GET['PrintPDF'];
} elseif (isset($_POST['PrintPDF'])) {
	$PrintPDF = $_POST['PrintPDF'];
}

if (!isset($_POST['ToTransNo'])
	OR trim($_POST['ToTransNo'])==''
	OR filter_number_format($_POST['ToTransNo']) < $FromTransNo) {

	$_POST['ToTransNo'] = $FromTransNo;
}

$FirstTrans = $FromTransNo; /* Need to start a new page only on subsequent transactions */

if (isset($PrintPDF)
		and $PrintPDF!=''
		and isset($FromTransNo)
		and isset($InvOrCredit)
		and $FromTransNo!='') {

	$PaperSize = 'A4_Landscape';
	include ('includes/PDFStarter.php');

	if ($InvOrCredit=='Invoice') {
		$pdf->addInfo('Title',_('Sales Invoice') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
		$pdf->addInfo('Subject',_('Invoices from') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	} else {
		$pdf->addInfo('Title',_('Sales Credit Note') );
		$pdf->addInfo('Subject',_('Credit Notes from') . ' ' . $FromTransNo . ' ' . _('to') . ' ' . $_POST['ToTransNo']);
	}

	$FirstPage = true;
	$line_height=16;

	//Keep a record of the user's language
	$UserLanguage = $_SESSION['Language'];

	while ($FromTransNo <= filter_number_format($_POST['ToTransNo'])){

	/* retrieve the invoice details from the database to print
	notice that salesorder record must be present to print the invoice purging of sales orders will
	nobble the invoice reprints */

	// check if the user has set a default bank account for invoices, if not leave it blank
		$sql = "SELECT bankaccounts.invoice,
					bankaccounts.bankaccountnumber,
					bankaccounts.bankaccountcode
				FROM bankaccounts
				WHERE bankaccounts.invoice = '1'";
		$result=DB_query($sql,'','',false,false);
		if(DB_error_no()!=1) {
			if(DB_num_rows($result)==1) {
				$myrow = DB_fetch_array($result);
				$DefaultBankAccountNumber = _('Account') .': ' .$myrow['bankaccountnumber'];
				$DefaultBankAccountCode = _('Bank Code:') .' ' .$myrow['bankaccountcode'];
			} else {
				$DefaultBankAccountNumber = '';
				$DefaultBankAccountCode = '';
			}
		} else {
			$DefaultBankAccountNumber = '';
			$DefaultBankAccountCode = '';
		}
// gather the invoice data

		if ($InvOrCredit=='Invoice') {
			$sql = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtortrans.consignment,
							debtortrans.packages,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.invaddrbranch,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							paymentterms.terms,
							paymentterms.dayinfollowingmonth,
							paymentterms.daysbeforedue,
							salesorders.deliverto,
							salesorders.deladd1,
							salesorders.deladd2,
							salesorders.deladd3,
							salesorders.deladd4,
							salesorders.deladd5,
							salesorders.deladd6,
							salesorders.customerref,
							salesorders.orderno,
							salesorders.orddate,
							locations.locationname,
							shippers.shippername,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesorders
						ON debtortrans.order_ = salesorders.orderno
						INNER JOIN shippers
						ON debtortrans.shipvia=shippers.shipper_id
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN locations
						ON salesorders.fromstkloc=locations.loccode
						INNER JOIN locationusers
						ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=10
						AND debtortrans.transno='" . $FromTransNo . "'";

			if(isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No') {
				$sql = $sql . ' AND debtorsmaster.ediinvoices=0';
			}
		} else {/* then its a credit note */
			$sql = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtorsmaster.invaddrbranch,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=11
						AND debtortrans.transno='" . $FromTransNo . "'";


			if(isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No') {
				$sql = $sql . ' AND debtorsmaster.ediinvoices=0';
			}
		} // end else

		$result=DB_query($sql,'','',false, false);

		if (DB_error_no()!=0) {
			$Title = _('Transaction Print Error Report');
			include ('includes/header.php');
			prnMsg( _('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $InvoiceToPrint . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available'),'error');
			if ($debug==1) {
				prnMsg (_('The SQL used to get this information that failed was') . '<br />' . $sql,'error');
			}
			include ('includes/footer.php');
			exit;
		}
		if (DB_num_rows($result)==1) {
			$myrow = DB_fetch_array($result);

			if ( $_SESSION['SalesmanLogin'] != '' AND $_SESSION['SalesmanLogin'] != $myrow['salesman'] ){
				$Title=_('Select Invoices/Credit Notes To Print');
				include('includes/header.php');
					prnMsg(_('Your account is set up to see only a specific salespersons orders. You are not authorised to view transaction for this order'),'error');
				include('includes/footer.php');
					exit;
			}
			if ( $CustomerLogin == 1 AND $myrow['debtorno'] != $_SESSION['CustomerID'] ){
				$Title=_('Select Invoices/Credit Notes To Print');
				include('includes/header.php');
					echo '<p class="bad">' . _('This transaction is addressed to another customer and cannot be displayed for privacy reasons') . '. ' . _('Please select only transactions relevant to your company').'</p>';
				include('includes/footer.php');
					exit;
				}

			$ExchRate = $myrow['rate'];

			//Change the language to the customer's language
			$_SESSION['Language'] = $myrow['language_id'];
			include('includes/LanguageSetup.php');


			if ($InvOrCredit=='Invoice') {

				$sql = "SELECT stockmoves.stockid,
								stockmaster.description,
								-stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								stockmaster.decimalplaces
							FROM stockmoves INNER JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=10
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1";
			} else {
		/* only credit notes to be retrieved */
				$sql = "SELECT stockmoves.stockid,
								stockmaster.description,
								stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								stockmaster.decimalplaces
							FROM stockmoves INNER JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=11
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1";
			} // end else

			$result=DB_query($sql);
			if(DB_error_no()!=0) {

				$Title = _('Transaction Print Error Report');
				include ('includes/header.php');
				echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $FromTransNo . ' ' . _('from the database');
				if ($debug==1) {
					echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' . $sql;
				}
				include('includes/footer.php');
				exit;
			}

			if ($InvOrCredit=='Invoice') {
				/* Calculate Due Date info. This reference is used in the PDFTransPageHeader.inc file. */
				$DisplayDueDate = CalcDueDate(ConvertSQLDate($myrow['trandate']), $myrow['dayinfollowingmonth'], $myrow['daysbeforedue']);
			}

			if (DB_num_rows($result)>0) {

				$FontSize = 10;
				$PageNumber = 1;

				include('includes/PDFTransPageHeader.inc');
				$FirstPage = False;


				while ($myrow2=DB_fetch_array($result)) {
					if ($myrow2['discountpercent']==0) {
						$DisplayDiscount ='';
					} else {
						$DisplayDiscount = locale_number_format($myrow2['discountpercent']*100,2) . '%';
						$DiscountPrice=$myrow2['fxprice']*(1-$myrow2['discountpercent']);
					}
					$DisplayNet=locale_number_format($myrow2['fxnet'],$myrow['decimalplaces']);
					$DisplayPrice=locale_number_format($myrow2['fxprice'],$myrow['decimalplaces']);
					$DisplayQty=locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);

					$LeftOvers = $pdf->addTextWrap($Left_Margin+3,$YPos,95,$FontSize,$myrow2['stockid']);
					//Get translation if it exists
					$TranslationResult = DB_query("SELECT descriptiontranslation
													FROM stockdescriptiontranslations
													WHERE stockid='" . $myrow2['stockid'] . "'
													AND language_id='" . $myrow['language_id'] ."'");

					if (DB_num_rows($TranslationResult)==1){ //there is a translation
						$TranslationRow = DB_fetch_array($TranslationResult);
						$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,251,$FontSize,$TranslationRow['descriptiontranslation']);
					} else {
						$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,251,$FontSize,$myrow2['description']);
					}

					$lines=1;
					while($LeftOvers!='') {
						$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,251,$FontSize,$LeftOvers);
						$lines++;
					}

					$LeftOvers = $pdf->addTextWrap($Left_Margin+353,$YPos,96,$FontSize,$DisplayPrice,'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+453,$YPos,95,$FontSize,$DisplayQty,'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+553,$YPos,35,$FontSize,$myrow2['units'],'centre');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+590,$YPos,50,$FontSize,$DisplayDiscount,'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+642,$YPos,120,$FontSize,$DisplayNet,'right');

					if($myrow2['controlled']==1) {

						$GetControlMovts = DB_query("
								SELECT
									moveqty,
									serialno
								FROM stockserialmoves
								WHERE stockmoveno='" . $myrow2['stkmoveno'] . "'");

						if($myrow2['serialised']==1) {
							while($ControlledMovtRow = DB_fetch_array($GetControlMovts)) {
								$YPos -= ($line_height);
								$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,100,$FontSize,$ControlledMovtRow['serialno'],'left');
								if($YPos-$line_height <= $Bottom_Margin) {
									/* head up a new invoice/credit note page */
									/*draw the vertical column lines right to the bottom */
									PrintLinesToBottom ();
									include ('includes/PDFTransPageHeader.inc');
								} //end if need a new page headed up
							}
						} else {
							while($ControlledMovtRow = DB_fetch_array($GetControlMovts)) {
								$YPos -= ($line_height);
								$LeftOvers = $pdf->addTextWrap($Left_Margin+100,$YPos,100,$FontSize,(-$ControlledMovtRow['moveqty']) . ' x ' . $ControlledMovtRow['serialno'], 'left');
								if($YPos-$line_height <= $Bottom_Margin) {
									/* head up a new invoice/credit note page */
									/*draw the vertical column lines right to the bottom */
									PrintLinesToBottom ();
									include ('includes/PDFTransPageHeader.inc');
								} //end if need a new page headed up
							}
						}
					}

					PrintDetail($pdf,$myrow2['narrative'],$Bottom_Margin,function(){
							PrintLinesToBottom ();
							include ('includes/PDFTransPageHeader.inc');
						},null,$Left_Margin+100,$YPos,245,$FontSize);

					$YPos -= ($line_height);

					if ($YPos <= $Bottom_Margin) {
						/* head up a new invoice/credit note page */
						/*draw the vertical column lines right to the bottom */
						PrintLinesToBottom ();
						include ('includes/PDFTransPageHeader.inc');
					} //end if need a new page headed up

				} //end while there are line items to print out
			} /*end if there are stock movements to show on the invoice or credit note*/

			$YPos -= $line_height;

			/* check to see enough space left to print the 4 lines for the totals/footer */
			if (($YPos-$Bottom_Margin)<(2*$line_height)) {
				PrintLinesToBottom ();
				include ('includes/PDFTransPageHeader.inc');
			}
			/* Print a column vertical line  with enough space for the footer */
			/* draw the vertical column lines to 4 lines shy of the bottom
			to leave space for invoice footer info ie totals etc */
			$pdf->line($Left_Margin+97, $TopOfColHeadings+12,$Left_Margin+97,$Bottom_Margin+(4*$line_height));

			/* Print a column vertical line */
			$pdf->line($Left_Margin+350, $TopOfColHeadings+12,$Left_Margin+350,$Bottom_Margin+(4*$line_height));

			/* Print a column vertical line */
			$pdf->line($Left_Margin+450, $TopOfColHeadings+12,$Left_Margin+450,$Bottom_Margin+(4*$line_height));

			/* Print a column vertical line */
			$pdf->line($Left_Margin+550, $TopOfColHeadings+12,$Left_Margin+550,$Bottom_Margin+(4*$line_height));

			/* Print a column vertical line */
			$pdf->line($Left_Margin+587, $TopOfColHeadings+12,$Left_Margin+587,$Bottom_Margin+(4*$line_height));

			$pdf->line($Left_Margin+640, $TopOfColHeadings+12,$Left_Margin+640,$Bottom_Margin+(4*$line_height));

			/* Rule off at bottom of the vertical lines */
			$pdf->line($Left_Margin, $Bottom_Margin+(4*$line_height),$Page_Width-$Right_Margin,$Bottom_Margin+(4*$line_height));

			/* Now print out the footer and totals */

			if ($InvOrCredit=='Invoice') {

				$DisplaySubTot = locale_number_format($myrow['ovamount'],$myrow['decimalplaces']);
				$DisplayFreight = locale_number_format($myrow['ovfreight'],$myrow['decimalplaces']);
				$DisplayTax = locale_number_format($myrow['ovgst'],$myrow['decimalplaces']);
				$DisplayTotal = locale_number_format($myrow['ovfreight']+$myrow['ovgst']+$myrow['ovamount'],$myrow['decimalplaces']);

			} else {

				$DisplaySubTot = locale_number_format(-$myrow['ovamount'],$myrow['decimalplaces']);
				$DisplayFreight = locale_number_format(-$myrow['ovfreight'],$myrow['decimalplaces']);
				$DisplayTax = locale_number_format(-$myrow['ovgst'],$myrow['decimalplaces']);
				$DisplayTotal = locale_number_format(-$myrow['ovfreight']-$myrow['ovgst']-$myrow['ovamount'],$myrow['decimalplaces']);
			}

			$YPos = $Bottom_Margin+(3*$line_height);

			/* Print out the invoice text entered */
			$FontSize =8;
			$LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos-12,270,$FontSize,$myrow['invtext']);
			if (mb_strlen($LeftOvers)>0) {
				$LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos-24,270,$FontSize,$LeftOvers);
				if (mb_strlen($LeftOvers)>0) {
					$LeftOvers = $pdf->addTextWrap($Left_Margin+5,$YPos-36,270,$FontSize,$LeftOvers);
					/*If there is some of the InvText leftover after 3 lines 200 wide then it is not printed :( */
				}
			}
			$FontSize = 10;

			$pdf->addText($Page_Width-$Right_Margin-220, $YPos+15,$FontSize, _('Sub Total'));
			$LeftOvers = $pdf->addTextWrap($Left_Margin+642,$YPos+5,120,$FontSize,$DisplaySubTot, 'right');

			$pdf->addText($Page_Width-$Right_Margin-220, $YPos+2,$FontSize, _('Freight'));
			$LeftOvers = $pdf->addTextWrap($Left_Margin+642,$YPos-8,120,$FontSize,$DisplayFreight, 'right');

			$pdf->addText($Page_Width-$Right_Margin-220, $YPos-10,$FontSize, _('Tax'));
			$LeftOvers = $pdf->addTextWrap($Left_Margin+642,$YPos-($line_height)-5,120, $FontSize,$DisplayTax, 'right');

			/*rule off for total */
			$pdf->line($Page_Width-$Right_Margin-222, $YPos-(2*$line_height),$Page_Width-$Right_Margin,$YPos-(2*$line_height));

			/*vertical to separate totals from comments and ROMALPA */
			$pdf->line($Page_Width-$Right_Margin-222, $YPos+$line_height,$Page_Width-$Right_Margin-222,$Bottom_Margin);

			$YPos+=10;
			if ($InvOrCredit=='Invoice') {
				/* Print out the payment terms */
				$pdf->addTextWrap($Left_Margin+5,$YPos-5,280,$FontSize,_('Payment Terms') . ': ' . $myrow['terms']);

				$pdf->addText($Page_Width-$Right_Margin-220, $YPos - ($line_height*2)-10,$FontSize, _('TOTAL INVOICE'));
				$FontSize=9;
				$YPos-=4;

				$LeftOvers = $pdf->addTextWrap($Left_Margin+280,$YPos,260,$FontSize,$_SESSION['RomalpaClause']);
				while (mb_strlen($LeftOvers)>0 AND $YPos > $Bottom_Margin) {
					$YPos-=12;
					$LeftOvers = $pdf->addTextWrap($Left_Margin+280,$YPos,260,$FontSize,$LeftOvers);
				}

				/* Add Images for Visa / Mastercard / Paypal */
				if (file_exists('companies/' . $_SESSION['DatabaseName'] . '/payment.jpg')) {
					$pdf->addJpegFromFile('companies/' . $_SESSION['DatabaseName'] . '/payment.jpg',$Page_Width/2 -280,$YPos-20,0,40);
				}

				// Print Bank acount details if available and default for invoices is selected
				$pdf->addText($Page_Width-$Right_Margin-490, $YPos - ($line_height*3)+32,$FontSize, $DefaultBankAccountCode . ' ' . $DefaultBankAccountNumber);
				$FontSize=10;
			} else {
				$pdf->addText($Page_Width-$Right_Margin-220, $YPos-($line_height*2)-10,$FontSize, _('TOTAL CREDIT'));
 			}
			$LeftOvers = $pdf->addTextWrap($Left_Margin+642,35,120, $FontSize,$DisplayTotal, 'right');
		} /* end of check to see that there was an invoice record to print */

		$FromTransNo++;
	} /* end loop to print invoices */

	/* Put the transaction number back as would have been incremented by one after last pass */
	$FromTransNo--;

	if (isset($_GET['Email'])){ //email the invoice to address supplied


		include ('includes/htmlMimeMail.php');
		$FileName = $_SESSION['reports_dir'] . '/' . $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $FromTransNo . '.pdf';
		$pdf->Output($FileName,'F');
		$mail = new htmlMimeMail();

		$Attachment = $mail->getFile($FileName);
		$mail->setText(_('Please find attached') . ' ' . $InvOrCredit . ' ' . $FromTransNo );
		$mail->SetSubject($InvOrCredit . ' ' . $FromTransNo);
		$mail->addAttachment($Attachment, $FileName, 'application/pdf');
		if($_SESSION['SmtpSetting'] == 0){
			$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
			$result = $mail->send(array($_GET['Email']));
		}else{
			$result = SendmailBySmtp($mail,array($_GET['Email']));
		}

		unlink($FileName); //delete the temporary file

		$Title = _('Emailing') . ' ' .$InvOrCredit . ' ' . _('Number') . ' ' . $FromTransNo;
		include('includes/header.php');
		echo '<p>' . $InvOrCredit . ' '  . _('number') . ' ' . $FromTransNo . ' ' . _('has been emailed to') . ' ' . $_GET['Email'];
		include('includes/footer.php');
		exit;

	} else { //its not an email just print the invoice to PDF
		$pdf->OutputD($_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . $FromTransNo . '.pdf');

	}
	$pdf->__destruct();
	//Change the language back to the user's language
	$_SESSION['Language'] = $UserLanguage;
	include('includes/LanguageSetup.php');


} else { /*The option to print PDF was not hit */

	$Title=_('Select Invoices/Credit Notes To Print');
	include('includes/header.php');

	if (!isset($FromTransNo) OR $FromTransNo=='') {

		/* if FromTransNo is not set then show a form to allow input of either a single invoice number or a range of invoices to be printed. Also get the last invoice number created to show the user where the current range is up to */
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">';
        echo '<div>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . _('Print') . '" alt="" />' . ' ' . _('Print Invoices or Credit Notes (Landscape Mode)') . '</p>';

		echo '<table class="selection">
				<tr>
					<td>' . _('Print Invoices or Credit Notes') . '</td>
					<td><select name="InvOrCredit">';

		if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {

			echo '<option selected="selected" value="Invoice">' . _('Invoices') . '</option>';
			echo '<option value="Credit">' . _('Credit Notes') . '</option>';
		} else {
			echo '<option selected="selected" value="Credit">' . _('Credit Notes') . '</option>';
			echo '<option value="Invoice">' . _('Invoices') . '</option>';
		}

		echo '</select></td>
				</tr>
				<tr>
					<td>', _('Print EDI Transactions'), '</td>
					<td><select name="PrintEDI">';

		if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {

			echo '<option selected="selected" value="No">' . _('Do not Print PDF EDI Transactions') . '</option>';
			echo '<option value="Yes">' . _('Print PDF EDI Transactions Too') . '</option>';

		} else {

			echo '<option value="No">' . _('Do not Print PDF EDI Transactions') . '</option>';
			echo '<option selected="selected" value="Yes">' . _('Print PDF EDI Transactions Too') . '</option>';
		}

		echo '</select></td>
				</tr>';
		echo '<tr>
				<td>' . _('Start invoice/credit note number to print') . '</td>
				<td><input class="number" type="text" maxlength="6" size="7" name="FromTransNo" required="required" /></td>
			</tr>';
		echo '<tr>
				<td>' . _('End invoice/credit note number to print') . '</td>
				<td><input class="number" type="text" maxlength="6" size="7" name="ToTransNo" /></td>
			</tr>
			</table>';
		echo '<div class="centre">
				<br />
				<input type="submit" name="Print" value="' . _('Print Preview') . '" />
				<br />
				<input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
				</div><br>';

		$sql = "SELECT typeno FROM systypes WHERE typeid=10";

		$result = DB_query($sql);
		$myrow = DB_fetch_row($result);

		echo '<div class="page_help_text"><b>' . _('The last invoice created was number') . ' ' . $myrow[0] . '</b><br />' . _('If only a single invoice is required') . ', ' . _('enter the invoice number to print in the Start transaction number to print field and leave the End transaction number to print field blank') . '. ' . _('Only use the end invoice to print field if you wish to print a sequential range of invoices') . '';

		$sql = "SELECT typeno FROM systypes WHERE typeid=11";

		$result = DB_query($sql);
		$myrow = DB_fetch_row($result);

		echo '<br /><b>' . _('The last credit note created was number') . ' ' . $myrow[0] . '</b><br />' . _('A sequential range can be printed using the same method as for invoices above') . '. ' . _('A single credit note can be printed by only entering a start transaction number') . '</div>';

		echo '</div>
			</form>';
	} else { // A FromTransNo number IS set

		while($FromTransNo <= filter_number_format($_POST['ToTransNo'])) {

			/*retrieve the invoice details from the database to print
			notice that salesorder record must be present to print the invoice purging of sales orders will
			nobble the invoice reprints */

			if ($InvOrCredit=='Invoice') {

				$sql = "SELECT debtortrans.trandate,
								debtortrans.ovamount,
								debtortrans.ovdiscount,
								debtortrans.ovfreight,
								debtortrans.ovgst,
								debtortrans.rate,
								debtortrans.invtext,
								debtortrans.consignment,
								debtorsmaster.name,
								debtorsmaster.address1,
								debtorsmaster.address2,
								debtorsmaster.address3,
								debtorsmaster.address4,
								debtorsmaster.address5,
								debtorsmaster.address6,
								debtorsmaster.currcode,
								salesorders.deliverto,
								salesorders.deladd1,
								salesorders.deladd2,
								salesorders.deladd3,
								salesorders.deladd4,
								salesorders.deladd5,
								salesorders.deladd6,
								salesorders.customerref,
								salesorders.orderno,
								salesorders.orddate,
								shippers.shippername,
								custbranch.brname,
								custbranch.braddress1,
								custbranch.braddress2,
								custbranch.braddress3,
								custbranch.braddress4,
								custbranch.braddress5,
								custbranch.braddress6,
								custbranch.salesman,
								salesman.salesmanname,
								debtortrans.debtorno,
								currencies.decimalplaces,
								paymentterms.dayinfollowingmonth,
								paymentterms.daysbeforedue,
								paymentterms.terms
							FROM debtortrans INNER JOIN debtorsmaster
							ON debtortrans.debtorno=debtorsmaster.debtorno
							INNER JOIN custbranch
							ON debtortrans.debtorno=custbranch.debtorno
							AND debtortrans.branchcode=custbranch.branchcode
							INNER JOIN salesorders
							ON debtortrans.order_ = salesorders.orderno
							INNER JOIN shippers
							ON debtortrans.shipvia=shippers.shipper_id
							INNER JOIN salesman
							ON custbranch.salesman=salesman.salesmancode
							INNER JOIN locations
							ON salesorders.fromstkloc=locations.loccode
							INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
							INNER JOIN paymentterms
							ON debtorsmaster.paymentterms=paymentterms.termsindicator
							INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
							WHERE debtortrans.type=10
							AND debtortrans.transno='" . $FromTransNo . "'";
			} else { /* then its a credit note */

				$sql = "SELECT debtortrans.trandate,
								debtortrans.ovamount,
								debtortrans.ovdiscount,
								debtortrans.ovfreight,
								debtortrans.ovgst,
								debtortrans.rate,
								debtortrans.invtext,
								debtorsmaster.name,
								debtorsmaster.address1,
								debtorsmaster.address2,
								debtorsmaster.address3,
								debtorsmaster.address4,
								debtorsmaster.address5,
								debtorsmaster.address6,
								debtorsmaster.currcode,
								custbranch.brname,
								custbranch.braddress1,
								custbranch.braddress2,
								custbranch.braddress3,
								custbranch.braddress4,
								custbranch.braddress5,
								custbranch.braddress6,
								custbranch.salesman,
								salesman.salesmanname,
								debtortrans.debtorno,
								currencies.decimalplaces
							FROM debtortrans INNER JOIN debtorsmaster
							ON debtortrans.debtorno=debtorsmaster.debtorno
							INNER JOIN custbranch
							ON debtortrans.debtorno=custbranch.debtorno
							AND debtortrans.branchcode=custbranch.branchcode
							INNER JOIN salesman
							ON custbranch.salesman=salesman.salesmancode
							INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
							WHERE debtortrans.type=11
							AND debtortrans.transno='" . $FromTransNo . "'";
			}

			$result=DB_query($sql);
			if(DB_num_rows($result)==0 OR DB_error_no()!=0) {
				echo '<p>' . _('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $FromTransNo . ' ' . _('from the database') . '. ' . _('To print an invoice, the sales order record, the customer transaction record and the branch record for the customer must not have been purged') . '. ' . _('To print a credit note only requires the customer, transaction, salesman and branch records be available');
				if ($debug==1) {
					prnMsg( _('The SQL used to get this information that failed was') . '<br />' . $sql, 'warn');
				}
				break;
				include('includes/footer.php');
				exit;
			} elseif (DB_num_rows($result)==1) {
	/* Then there's an invoice (or credit note) to print. So print out the invoice header and GST Number from the company record */

				$myrow = DB_fetch_array($result);

				if ($_SESSION['SalesmanLogin']!='' AND $_SESSION['SalesmanLogin']!=$myrow['salesman']){
					prnMsg(_('Your account is set up to see only a specific salespersons orders. You are not authorised to view transaction for this order'),'error');
					include('includes/footer.php');
					exit;
				}
				if( $CustomerLogin == 1 AND $myrow['debtorno'] != $_SESSION['CustomerID']) {
					echo '<p class="bad">' . _('This transaction is addressed to another customer and cannot be displayed for privacy reasons') . '. ' . _('Please select only transactions relevant to your company');
					include('includes/footer.php');
					exit;
				}

				$ExchRate = $myrow['rate'];
				$PageNumber = 1;

				echo '<table class="table1">
						<tr>
							<td valign="top" style="width:10%"><img src="' . $_SESSION['LogoFile'] . '" alt="" /></td>
							<td style="background-color:#bbbbbb">';

				if ($InvOrCredit=='Invoice') {
					echo '<h2>' . _('TAX INVOICE') . ' ';
				} else {
					echo '<h2 style="color:red">' . _('TAX CREDIT NOTE') . ' ';
				}
				echo _('Number') . ' ' . $FromTransNo . '</h2>
						<br />' . _('Tax Authority Ref') . '. ' . $_SESSION['CompanyRecord']['gstno'];

				if ( $InvOrCredit == 'Invoice' ) {
					/* Print payment terms and due date */
					$DisplayDueDate = CalcDueDate(ConvertSQLDate($myrow['trandate']), $myrow['dayinfollowingmonth'], $myrow['daysbeforedue']);
					echo '<br />' . _('Payment Terms') . ': '. $myrow['terms'] . '<br />' . _('Due Date') . ': ' . $DisplayDueDate;
				}

				echo '</td>
						</tr>
						</table>';

	/* Main table with customer name and charge to info. */
				echo '<table class="table1">
						<tr>
							<td><h2>' . $_SESSION['CompanyRecord']['coyname'] . '</h2>
							<br />';
                echo $_SESSION['CompanyRecord']['regoffice1'] . '<br />';
                echo $_SESSION['CompanyRecord']['regoffice2'] . '<br />';
                echo $_SESSION['CompanyRecord']['regoffice3'] . '<br />';
                echo $_SESSION['CompanyRecord']['regoffice4'] . '<br />';
                echo $_SESSION['CompanyRecord']['regoffice5'] . '<br />';
                echo $_SESSION['CompanyRecord']['regoffice6'] . '<br />';
                echo _('Telephone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . '<br />';
                echo _('Facsimile') . ': ' . $_SESSION['CompanyRecord']['fax'] . '<br />';
                echo _('Email') . ': ' . $_SESSION['CompanyRecord']['email'] . '<br />';

				echo '</td>
						<td style="width:50%" class="number">';

	/* Put the customer charged to details in a sub table within a cell of the main table*/

				echo '<table class="table1">
						<tr>
							<th style="background-color:#bbbbbb">' . _('Charge To') . ':</th>
						</tr>
						<tr>
							<td>';
				echo $myrow['name'] .
						'<br />' . $myrow['address1'] .
						'<br />' . $myrow['address2'] .
						'<br />' . $myrow['address3'] .
						'<br />' . $myrow['address4'] .
						'<br />' . $myrow['address5'] .
						'<br />' . $myrow['address6'];

				echo '</td>
						</tr>
						</table>';
	/*end of the small table showing charge to account details */
				echo _('Page') . ': ' . $PageNumber;
				echo '</td>
						</tr>
						</table>';
	/*end of the main table showing the company name and charge to details */

                if ($InvOrCredit=='Invoice') {
	/* Table with Charge Branch and Delivered To info. */
					echo '<table class="table1">
							<tr>
								<th style="background-color:#bbbbbb">' . _('Charge Branch') . ':</th>
								<th style="background-color:#bbbbbb">' . _('Delivered To') . ':</th>
                    </tr>';
					echo '<tr>
							<td>' . $myrow['brname'] .
                               '<br />' . $myrow['braddress1'] .
                               '<br />' . $myrow['braddress2'] .
                               '<br />' . $myrow['braddress3'] .
                               '<br />' . $myrow['braddress4'] .
                               '<br />' . $myrow['braddress5'] .
								'<br />' . $myrow['braddress6'] .
							'</td>';

					echo '<td>' . $myrow['deliverto'] .
                            '<br />' . $myrow['deladd1'] .
                            '<br />' . $myrow['deladd2'] .
                            '<br />' . $myrow['deladd3'] .
                            '<br />' . $myrow['deladd4'] .
                            '<br />' . $myrow['deladd5'] .
							'<br />' . $myrow['deladd6'] .
						'</td>';
					echo '</tr>
						</table><hr />';
	/* End Charge Branch and Delivered To table */
	/* Table with order details */
					echo '<table class="table1">
						<tr>
							<td style="background-color:#bbbbbb"><b>' . _('Your Order Ref') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Our Order No') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Order Date') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Invoice Date') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Sales Person') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Shipper') . '</b></td>
							<td style="background-color:#bbbbbb"><b>' . _('Consignment Ref') . '</b></td>
						</tr>';
				   	echo '<tr>
							<td>' . $myrow['customerref'] . '</td>
							<td>' . $myrow['orderno'] . '</td>
							<td>' . ConvertSQLDate($myrow['orddate']) . '</td>
							<td>' . ConvertSQLDate($myrow['trandate']) . '</td>
							<td>' . $myrow['salesmanname'] . '</td>
							<td>' . $myrow['shippername'] . '</td>
							<td>' . $myrow['consignment'] . '</td>
						</tr>
					</table>';
	/* End order details table */
				   $sql ="SELECT stockmoves.stockid,
						   		stockmaster.description,
								-stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.units,
								stockmaster.decimalplaces
							FROM stockmoves
							INNER JOIN stockmaster
								ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=10
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1";

				} else { /* then its a credit note */
	/* Table for Branch info */
					echo '<table width="50%">
						<tr>
							<th style="background-color:#bbbbbb">' . _('Branch') . ':</th>
						</tr>';
					echo '<tr>
							<td style="background-color:#EEEEEE">' . $myrow['brname'] .
							'<br />' . $myrow['braddress1'] .
							'<br />' . $myrow['braddress2'] .
							'<br />' . $myrow['braddress3'] .
							'<br />' . $myrow['braddress4'] .
							'<br />' . $myrow['braddress5'] .
							'<br />' . $myrow['braddress6'] .
							'</td>
						</tr></table>';
	/* End Branch info table */
	/* Table for Sales Person info. */
					echo '<hr />
							<table class="table1">
							<tr>
								<th style="background-color:#bbbbbb">' . _('Date') . '</th>
								<th style="background-color:#bbbbbb">' . _('Sales Person') . '</th>
					</tr>';
				   echo '<tr>
				   		<td>' . ConvertSQLDate($myrow['trandate']) . '</td>
						<td>' . $myrow['salesmanname'] . '</td>
						</tr>
						</table>';
	/* End Sales Person table */
				   $sql ="SELECT stockmoves.stockid,
						   		stockmaster.description,
								stockmoves.qty as quantity,
								stockmoves.discountpercent, ((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmaster.units,
								stockmoves.narrative,
								stockmaster.decimalplaces
							FROM stockmoves
							INNER JOIN stockmaster
								ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=11
							AND stockmoves.transno='" . $FromTransNo . "'
							AND stockmoves.show_on_inv_crds=1";
				}

				echo '<hr />';
				echo '<div class="centre"><h4>' . _('All amounts stated in') . ' ' . $myrow['currcode'] . '</h4></div>';

				$result=DB_query($sql);
				if (DB_error_no()!=0) {
					echo '<br />' . _('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $FromTransNo . ' ' . _('from the database');
					if ($debug==1){
						 echo '<br />' . _('The SQL used to get this information that failed was') . '<br />' .$sql;
					}
					exit;
				}

				if (DB_num_rows($result)>0){
	/* Table for stock details */
					echo '<table class="table1">
						<tr>
							<th>' . _('Item Code') . '</th>
							<th>' . _('Item Description') . '</th>
							<th>' . _('Quantity') . '</th>
							<th>' . _('Unit') . '</th>
							<th>' . _('Price') . '</th>
							<th>' . _('Discount') . '</th>
							<th>' . _('Net') . '</th>
						</tr>';

					$LineCounter =17;

					while ($myrow2=DB_fetch_array($result)){

					      $DisplayPrice = locale_number_format($myrow2['fxprice'],$myrow['decimalplaces']);
					      $DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);
					      $DisplayNet = locale_number_format($myrow2['fxnet'],$myrow['decimalplaces']);

					      if ($myrow2['discountpercent']==0){
						   $DisplayDiscount ='';
					      } else {
						   $DisplayDiscount = locale_number_format($myrow2['discountpercent']*100,2) . '%';
					      }

						printf ('<tr class="striped_row">
									<td>%s</td>
									<td>%s</td>
									<td class="number">%s</td>
									<td class="number">%s</td>
									<td class="number">%s</td>
									<td class="number">%s</td>
									<td class="number">%s</td>
									</tr>',
									$myrow2['stockid'],
									$myrow2['description'],
									$DisplayQty,
									$myrow2['units'],
									$DisplayPrice,
									$DisplayDiscount,
									$DisplayNet);

					      if (mb_strlen($myrow2['narrative'])>1){
                                $narrative = str_replace(array("\r\n", "\n", "\r", "\\r\\n"), '<br />', $myrow2['narrative']);
							echo '<tr class="striped_row">
								<td></td>
								<td colspan="6">' . $narrative . '</td>
								</tr>';
							$LineCounter++;
					      }

					      $LineCounter++;

						if($LineCounter == ($_SESSION['PageLength'] - 2)) {

							/* head up a new invoice/credit note page */

							$PageNumber++;
	/* End the stock table before the new page */
							echo '</table>
								<table class="table1">
								<tr>
									<td valign="top"><img src="' . $_SESSION['LogoFile'] . '" alt="" /></td>
									<td style="background-color:#bbbbbb">';

								if ($InvOrCredit=='Invoice') {
									echo '<h2>' . _('TAX INVOICE') . ' ';
								} else {
									echo '<h2 style="color:red">' . _('TAX CREDIT NOTE') . ' ';
								}
								echo _('Number') . ' ' . $FromTransNo . '</h2><br />' . _('GST Number') . ' - ' . $_SESSION['CompanyRecord']['gstno'] . '</td>
								</tr>
								</table>';

	/*Print the company name and address */
								echo '<table class="table1">
										<tr>
											<td><h2>' . $_SESSION['CompanyRecord']['coyname'] . '</h2><br />';
												echo $_SESSION['CompanyRecord']['regoffice1'] . '<br />';
												echo $_SESSION['CompanyRecord']['regoffice2'] . '<br />';
												echo $_SESSION['CompanyRecord']['regoffice3'] . '<br />';
												echo $_SESSION['CompanyRecord']['regoffice4'] . '<br />';
												echo $_SESSION['CompanyRecord']['regoffice5'] . '<br />';
												echo $_SESSION['CompanyRecord']['regoffice6'] . '<br />';
												echo _('Telephone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . '<br />';
												echo _('Facsimile') . ': ' . $_SESSION['CompanyRecord']['fax'] . '<br />';
												echo _('Email') . ': ' . $_SESSION['CompanyRecord']['email'] . '<br />';
												echo '</td><td class="number">' . _('Page') . ': ' . $PageNumber . '</td>
														</tr>
													</table>';
												echo '<table class="table1">
														<tr>
															<th>' . _('Item Code') . '</th>
															<th>' . _('Item Description') . '</th>
															<th>' . _('Quantity') . '</th>
															<th>' . _('Unit') . '</th>
															<th>' . _('Price') . '</th>
															<th>' . _('Discount') . '</th>
															<th>' . _('Net') . '</th>
														</tr>';

												$LineCounter = 10;

						} //end if need a new page headed up
					} //end while there are line items to print out
					echo '</table>';
				} /*end if there are stock movements to show on the invoice or credit note*/

				/* check to see enough space left to print the totals/footer */
				$LinesRequiredForText = floor(mb_strlen($myrow['invtext'])/140);

				if($LineCounter >= ($_SESSION['PageLength'] - 8 - $LinesRequiredForText)) {

					/* head up a new invoice/credit note page */
					$PageNumber++;
					echo '<table class="table1">
						<tr>
							<td valign="top"><img src="' . $_SESSION['LogoFile'] . '" alt="" /></td>
							<td style="background-color:#bbbbbb">';

				if ($InvOrCredit=='Invoice') {
						echo '<h2>' . _('TAX INVOICE') . ' ';
					} else {
						echo '<h2 style="color:red">' . _('TAX CREDIT NOTE') . ' ';
					}
					echo _('Number') . ' ' . $FromTransNo . '</h2>
							<br />' . _('GST Number') . ' - ' . $_SESSION['CompanyRecord']['gstno'] . '</td>
							</tr>
							</table>';

	/*Print the company name and address */
					echo '<table class="table1">
							<tr>
								<td><h2>' . $_SESSION['CompanyRecord']['coyname'] . '</h2><br />';
					echo $_SESSION['CompanyRecord']['regoffice1'] . '<br />';
					echo $_SESSION['CompanyRecord']['regoffice2'] . '<br />';
					echo $_SESSION['CompanyRecord']['regoffice3'] . '<br />';
					echo $_SESSION['CompanyRecord']['regoffice4'] . '<br />';
					echo $_SESSION['CompanyRecord']['regoffice5'] . '<br />';
					echo $_SESSION['CompanyRecord']['regoffice6'] . '<br />';
					echo _('Telephone') . ': ' . $_SESSION['CompanyRecord']['telephone'] . '<br />';
					echo _('Facsimile') . ': ' . $_SESSION['CompanyRecord']['fax'] . '<br />';
					echo _('Email') . ': ' . $_SESSION['CompanyRecord']['email'] . '<br />';
					echo '</td><td class="number">' . _('Page') . ': ' . $PageNumber . '</td>
							</tr>
							</table>';
					echo '<table class="table1">
							<tr>
								<th>' . _('Item Code') . '</th>
								<th>' . _('Item Description') . '</th>
								<th>' . _('Quantity') . '</th>
								<th>' . _('Unit') . '</th>
								<th>' . _('Price') . '</th>
								<th>' . _('Discount') . '</th>
								<th>' . _('Net') . '</th>
							</tr>
						</table>';

					$LineCounter = 10;
				}

	/*Print out the invoice text entered */
				echo '<br /><br />' . $myrow['invtext'];

	/*Space out the footer to the bottom of the page */
				$LineCounter=$LineCounter+2+$LinesRequiredForText;
				while($LineCounter < ($_SESSION['PageLength'] - 6)) {
					echo '<br />';
					$LineCounter++;
				}

	/* Footer table with totals */

				if ($InvOrCredit=='Invoice') {
				   $DisplaySubTot = locale_number_format($myrow['ovamount'],$myrow['decimalplaces']);
				   $DisplayFreight = locale_number_format($myrow['ovfreight'],$myrow['decimalplaces']);
				   $DisplayTax = locale_number_format($myrow['ovgst'],$myrow['decimalplaces']);
				   $DisplayTotal = locale_number_format($myrow['ovfreight']+$myrow['ovgst']+$myrow['ovamount'],$myrow['decimalplaces']);
				} else {
				   $DisplaySubTot = locale_number_format(-$myrow['ovamount'],$myrow['decimalplaces']);
				   $DisplayFreight = locale_number_format(-$myrow['ovfreight'],$myrow['decimalplaces']);
				   $DisplayTax = locale_number_format(-$myrow['ovgst'],$myrow['decimalplaces']);
				   $DisplayTotal = locale_number_format(-$myrow['ovfreight']-$myrow['ovgst']-$myrow['ovamount'],$myrow['decimalplaces']);
				}

				echo '<table class="table1"><tr>
						<td class="number">' . _('Sub Total') . '</td>
						<td class="number" style="width:15%">' . $DisplaySubTot . '</td></tr>';
				echo '<tr><td class="number">' . _('Freight') . '</td>
						<td class="number">' . $DisplayFreight . '</td></tr>';
				echo '<tr><td class="number">' . _('Tax') . '</td>
						<td class="number">' . $DisplayTax . '</td></tr>';
				if ($InvOrCredit=='Invoice'){
					echo '<tr><td class="number"><b>' . _('TOTAL INVOICE') . '</b></td>
							<td class="number"><b>' . $DisplayTotal . '</b></td></tr>';
				} else {
					echo '<tr><td class="number" style="color:red"><b>' . _('TOTAL CREDIT') . '</b></td>
							<td class="number" style="color:red"><b>' . $DisplayTotal . '</b></td></tr>';
				}
				echo '</table>';
	/* End footer totals table */
			} /* end of check to see that there was an invoice record to print */
			$FromTransNo++;
		} /* end loop to print invoices */
	} /*end of if FromTransNo exists */
	include('includes/footer.php');
} /*end of else not PrintPDF */


function PrintLinesToBottom () {

	global $Bottom_Margin;
	global $Left_Margin;
	global $line_height;
	global $PageNumber;
	global $pdf;
	global $TopOfColHeadings;

	/* draw the vertical column lines right to the bottom */
	$pdf->line($Left_Margin+97, $TopOfColHeadings+12,$Left_Margin+97,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+350, $TopOfColHeadings+12,$Left_Margin+350,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+450, $TopOfColHeadings+12,$Left_Margin+450,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+550, $TopOfColHeadings+12,$Left_Margin+550,$Bottom_Margin);

	/* Print a column vertical line */
	$pdf->line($Left_Margin+587, $TopOfColHeadings+12,$Left_Margin+587,$Bottom_Margin);

	$pdf->line($Left_Margin+640, $TopOfColHeadings+12,$Left_Margin+640,$Bottom_Margin);

	$PageNumber++;

}

?>
