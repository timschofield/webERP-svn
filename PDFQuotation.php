<?php
/*	Please note that addTextWrap prints a font-size-height further down than
	addText and other functions.*/

include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

//Get Out if we have no order number to work with
If (!isset($_GET['QuotationNo']) || $_GET['QuotationNo']==""){
	$Title = _('Select Quotation To Print');
	include('includes/header.php');
	echo '<div class="centre">
			<br />
			<br />
			<br />';
	prnMsg( _('Select a Quotation to Print before calling this page') , 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
				<tr>
					<td class="menu_group_item">
						<a href="'. $RootPath . '/SelectSalesOrder.php?Quotations=Quotes_Only">' . _('Quotations') . '</a></td>
				</tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
	include('includes/footer.php');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the quotation header details for Order Number') . ' ' . $_GET['QuotationNo'] . ' ' . _('from the database');

$sql = "SELECT salesorders.customerref,
				salesorders.comments,
				salesorders.orddate,
				salesorders.deliverto,
				salesorders.deladd1,
				salesorders.deladd2,
				salesorders.deladd3,
				salesorders.deladd4,
				salesorders.deladd5,
				salesorders.deladd6,
				debtorsmaster.name,
				debtorsmaster.currcode,
				debtorsmaster.address1,
				debtorsmaster.address2,
				debtorsmaster.address3,
				debtorsmaster.address4,
				debtorsmaster.address5,
				debtorsmaster.address6,
				shippers.shippername,
				salesorders.printedpackingslip,
				salesorders.datepackingslipprinted,
				salesorders.quotedate,
				salesorders.branchcode,
				locations.taxprovinceid,
				locations.locationname,
				currencies.decimalplaces AS currdecimalplaces
			FROM salesorders INNER JOIN debtorsmaster
			ON salesorders.debtorno=debtorsmaster.debtorno
			INNER JOIN shippers
			ON salesorders.shipvia=shippers.shipper_id
			INNER JOIN locations
			ON salesorders.fromstkloc=locations.loccode
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE salesorders.quotation=1
			AND salesorders.orderno='" . $_GET['QuotationNo'] ."'";

$result=DB_query($sql, $ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){
        $Title = _('Print Quotation Error');
        include('includes/header.php');
         echo '<div class="centre">
				<br />
				<br />
				<br />';
        prnMsg( _('Unable to Locate Quotation Number') . ' : ' . $_GET['QuotationNo'] . ' ', 'error');
        echo '<br />
				<br />
				<br />
				<table class="table_index">
				<tr>
					<td class="menu_group_item">
						<a href="'. $RootPath . '/SelectSalesOrder.php?Quotations=Quotes_Only">' . _('Outstanding Quotations') . '</a>
					</td>
				</tr>
				</table>
				</div>
				<br />
				<br />
				<br />';
        include('includes/footer.php');
        exit;
} elseif (DB_num_rows($result)==1){ /*There is only one order header returned - thats good! */

        $myrow = DB_fetch_array($result);
}

/*retrieve the order details from the database to print */

/* Then there's an order to print and its not been printed already (or its been flagged for reprinting/ge_Width=807;
)
LETS GO */
$PaperSize = 'A4_Landscape';// PDFStarter.php: $Page_Width=842; $Page_Height=595; $Top_Margin=30; $Bottom_Margin=30; $Left_Margin=40; $Right_Margin=30;
include('includes/PDFStarter.php');
$pdf->addInfo('Title', _('Customer Quotation') );
$pdf->addInfo('Subject', _('Quotation') . ' ' . $_GET['QuotationNo']);
$FontSize = 12;
$line_height = 12;// Recommended: $line_height = $x * $FontSize.

/* Now ... Has the order got any line items still outstanding to be invoiced */

$ErrMsg = _('There was a problem retrieving the quotation line details for quotation Number') . ' ' .
	$_GET['QuotationNo'] . ' ' . _('from the database');

$sql = "SELECT salesorderdetails.stkcode,
		stockmaster.description,
		salesorderdetails.quantity,
		salesorderdetails.qtyinvoiced,
		salesorderdetails.unitprice,
		salesorderdetails.discountpercent,
		stockmaster.taxcatid,
		salesorderdetails.narrative,
		stockmaster.decimalplaces
	FROM salesorderdetails INNER JOIN stockmaster
		ON salesorderdetails.stkcode=stockmaster.stockid
	WHERE salesorderdetails.orderno='" . $_GET['QuotationNo'] . "'";

$result=DB_query($sql, $ErrMsg);

$ListCount = 0;

if (DB_num_rows($result)>0){
	/*Yes there are line items to start the ball rolling with a page header */
	include('includes/PDFQuotationPageHeader.inc');

	$QuotationTotal = 0;
	$QuotationTotalEx = 0;
	$TaxTotal = 0;

	while ($myrow2=DB_fetch_array($result)){
	$line_height=15;
        $ListCount ++;

		$YPos -= $line_height;// Increment a line down for the next line item.

		if ((mb_strlen($myrow2['narrative']) >200 AND $YPos-$line_height <= 75)
			OR (mb_strlen($myrow2['narrative']) >1 AND $YPos-$line_height <= 62)
			OR $YPos-$line_height <= 50){
		/* We reached the end of the page so finsih off the page and start a newy */
			include ('includes/PDFQuotationPageHeader.inc');
		} //end if need a new page headed up

		$DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);
		$DisplayPrevDel = locale_number_format($myrow2['qtyinvoiced'],$myrow2['decimalplaces']);
		$DisplayPrice = locale_number_format($myrow2['unitprice'],$myrow['currdecimalplaces']);
		$DisplayDiscount = locale_number_format($myrow2['discountpercent']*100,2) . '%';
		$SubTot =  $myrow2['unitprice']*$myrow2['quantity']*(1-$myrow2['discountpercent']);
		$TaxProv = $myrow['taxprovinceid'];
		$TaxCat = $myrow2['taxcatid'];
		$Branch = $myrow['branchcode'];
		$sql3 = "SELECT taxgrouptaxes.taxauthid
					FROM taxgrouptaxes INNER JOIN custbranch
					ON taxgrouptaxes.taxgroupid=custbranch.taxgroupid
					WHERE custbranch.branchcode='" .$Branch ."'";
		$result3=DB_query($sql3, $ErrMsg);
		while ($myrow3=DB_fetch_array($result3)){
			$TaxAuth = $myrow3['taxauthid'];
		}

		$sql4 = "SELECT * FROM taxauthrates
					WHERE dispatchtaxprovince='" .$TaxProv ."'
					AND taxcatid='" .$TaxCat ."'
					AND taxauthority='" .$TaxAuth ."'";
		$result4=DB_query($sql4, $ErrMsg);
		while ($myrow4=DB_fetch_array($result4)){
			$TaxClass = 100 * $myrow4['taxrate'];
		}

		$DisplayTaxClass = $TaxClass . '%';
		$TaxAmount =  (($SubTot/100)*(100+$TaxClass))-$SubTot;
		$DisplayTaxAmount = locale_number_format($TaxAmount,$myrow['currdecimalplaces']);

		$LineTotal = $SubTot + $TaxAmount;
		$DisplayTotal = locale_number_format($LineTotal,$myrow['currdecimalplaces']);

		$FontSize = 10;// Font size for the line item.

		$LeftOvers = $pdf->addText($Left_Margin, $YPos+$FontSize, $FontSize, $myrow2['stkcode']);
		$LeftOvers = $pdf->addText(145, $YPos+$FontSize, $FontSize, $myrow2['description']);
		$LeftOvers = $pdf->addTextWrap(420, $YPos,85,$FontSize,$DisplayQty,'right');
		$LeftOvers = $pdf->addTextWrap(485, $YPos,85,$FontSize,$DisplayPrice,'right');
		if ($DisplayDiscount > 0) {
			$LeftOvers = $pdf->addTextWrap(535, $YPos,85,$FontSize,$DisplayDiscount,'right');
		}
		$LeftOvers = $pdf->addTextWrap(585, $YPos,85,$FontSize,$DisplayTaxClass,'right');
		$LeftOvers = $pdf->addTextWrap(650, $YPos,85,$FontSize,$DisplayTaxAmount,'right');
		$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, $DisplayTotal,'right');

		// Prints salesorderdetails.narrative:
		$FontSize2 = $FontSize*0.8;// Font size to print salesorderdetails.narrative.
		$Width2 = $Page_Width-$Left_Margin-$Right_Margin-145;// Width to print salesorderdetails.narrative.

		//XPos was 145, same as Description. Move it +10, slight tab in to improve readability
		PrintDetail($pdf, $myrow2['narrative'], $Bottom_Margin, null, 'includes/PDFQuotationPageHeader.inc', 155, $YPos, $Width2, $FontSize2);

		$QuotationTotal += $LineTotal;
		$QuotationTotalEx += $SubTot;
		$TaxTotal += $TaxAmount;

	}// Ends while there are line items to print out.

	if ((mb_strlen($myrow['comments']) >200 AND $YPos-$line_height <= 75)
			OR (mb_strlen($myrow['comments']) >1 AND $YPos-$line_height <= 62)
			OR $YPos-$line_height <= 50){
		/* We reached the end of the page so finish off the page and start a newy */
			include ('includes/PDFQuotationPageHeader.inc');
	} //end if need a new page headed up

	$FontSize = 10;
	$YPos -= $line_height;
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90-655, $YPos, 655, $FontSize, _('Quotation Excluding Tax'),'right');
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, locale_number_format($QuotationTotalEx,$myrow['currdecimalplaces']), 'right');
	$YPos -= $FontSize;
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90-655, $YPos, 655, $FontSize, _('Total Tax'), 'right');
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, locale_number_format($TaxTotal,$myrow['currdecimalplaces']), 'right');
	$YPos -= $FontSize;
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90-655, $YPos, 655, $FontSize, _('Quotation Including Tax'),'right');
	$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, locale_number_format($QuotationTotal,$myrow['currdecimalplaces']), 'right');

	// Print salesorders.comments:
	$YPos -= $FontSize*2;
	$pdf->addText($XPos, $YPos+$FontSize, $FontSize, _('Notes').':');
	$Width2 = $Page_Width-$Right_Margin-120;// Width to print salesorders.comments.
	$LeftOvers = trim($myrow['comments']);
	//**********
	$LeftOvers = str_replace('\n', ' ', $LeftOvers);// Replaces line feed character.
	$LeftOvers = str_replace('\r', '', $LeftOvers);// Delete carriage return character
	$LeftOvers = str_replace('\t', '', $LeftOvers);// Delete tabulator character
	//**********
	while(mb_strlen($LeftOvers) > 1) {
		$YPos -= $FontSize;
		if ($YPos < ($Bottom_Margin)) {// Begins new page.
			include ('includes/PDFQuotationPageHeader.inc');
		}
		$LeftOvers = $pdf->addTextWrap(40, $YPos, $Width2, $FontSize, $LeftOvers);
	}

} /*end if there are line details to show on the quotation*/


if ($ListCount == 0){
	$Title = _('Print Quotation Error');
	include('includes/header.php');
	prnMsg(_('There were no items on the quotation') . '. ' . _('The quotation cannot be printed'),'info');
	echo '<br /><a href="' . $RootPath . '/SelectSalesOrder.php?Quotation=Quotes_only">' .  _('Print Another Quotation'). '</a>
			<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
	include('includes/footer.php');
	exit;
} else {
    $pdf->OutputI($_SESSION['DatabaseName'] . '_Quotation_' . $_GET['QuotationNo'] . '_' . date('Y-m-d') . '.pdf');
    $pdf->__destruct();
}
?>
