<?php


$AllowAnyone = true;

$FromCriteria ='1'; /*Category From */
$ToCriteria ='zzzzzzzz'; /*Category To */
$Location =  'All';  /* Location to report on */
$DetailedReport = 'Yes';  /* Total by category or complete listing */

$_POST['DetailedReport'] = $DetailedReport; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['FromCriteria']=$FromCriteria; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['ToCriteria']=$ToCriteria; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['Location'] = $Location; /* so PDFInventoryValnPageHeader.inc works too */

include('includes/session.php');
include ('includes/class.pdf.php');
$Recipients = GetMailList('InventoryValuationRecipients');

if (sizeOf($Recipients) == 0) {
	$Title = _('Inventory Valuation') . ' - ' . _('Problem Report');
      	include('includes/header.php');
	prnMsg( _('There are no members of the Inventory Valuation Recipients email group'), 'warn');
	include('includes/footer.php');
	exit;
}
/* A4_Portrait */

$Page_Width=595;
$Page_Height=842;
$Top_Margin=30;
$Bottom_Margin=30;
$Left_Margin=40;
$Right_Margin=30;

// Javier: now I use the native constructor
// Javier: better to not use references
// $PageSize = array(0,0,$Page_Width,$Page_Height);
// $PDF = & new Cpdf($PageSize);
$PDF = new Cpdf('P', 'pt', 'A4');

// $PageNumber = 0;

/* Standard PDF file creation header stuff */

$PDF->addInfo('Creator','WebERP http://www.weberp.org');
$PDF->addInfo('Author','WebERP ' . $Version);


// $FontSize=10;
$PDF->addInfo('Title', _('Inventory Valuation Report'));
$PDF->addInfo('Subject', _('Inventory Valuation'));

/* Javier: I have brought this piece from the pdf class constructor to get it closer to the admin/user,
	I corrected it to match TCPDF, but it still needs check, after which,
	I think it should be moved to each report to provide flexible Document Header and Margins in a per-report basis. */
	$PDF->setAutoPageBreak(0);	// Javier: needs check.
	$PDF->setPrintHeader(false);	// Javier: I added this must be called before Add Page
	$PDF->AddPage();
//	$this->SetLineWidth(1); 	   Javier: It was ok for FPDF but now is too gross with TCPDF. TCPDF defaults to 0'57 pt (0'2 mm) which is ok.
	$PDF->cMargin = 0;		// Javier: needs check.
/* END Brought from class.pdf.php constructor */

$PageNumber = 1;
$line_height = 12;

/*Now figure out the inventory data to report for the category range under review */
if ($Location=='All'){

	$SQL = "SELECT stockmaster.categoryid,
				stockcategory.categorydescription,
				stockmaster.stockid,
				stockmaster.description,
				SUM(locstock.quantity) as qtyonhand,
				stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost AS unitcost,
				SUM(locstock.quantity) *(stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost) AS itemtotal
			FROM stockmaster,
				stockcategory,
				locstock
			WHERE stockmaster.stockid=locstock.stockid
			AND stockmaster.categoryid=stockcategory.categoryid
			GROUP BY stockmaster.categoryid,
				stockcategory.categorydescription,
				unitcost,
				stockmaster.stockid,
				stockmaster.description
			HAVING SUM(locstock.quantity)!=0
			AND stockmaster.categoryid >= '" . $FromCriteria . "'
			AND stockmaster.categoryid <= '" . $ToCriteria . "'
			ORDER BY stockmaster.categoryid,
				stockmaster.stockid";

} else {

	$SQL = "SELECT stockmaster.categoryid,
				stockcategory.categorydescription,
				stockmaster.stockid,
				stockmaster.description,
				locstock.quantity as qtyonhand,
				stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost AS unitcost,
				locstock.quantity *(stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost) AS itemtotal
			FROM stockmaster,
				stockcategory,
				locstock
			WHERE stockmaster.stockid=locstock.stockid
			AND stockmaster.categoryid=stockcategory.categoryid
			AND locstock.quantity!=0
			AND stockmaster.categoryid >= '" . $FromCriteria . "'
			AND stockmaster.categoryid <= '" . $ToCriteria . "'
			AND locstock.loccode = '" . $Location . "'
			ORDER BY stockmaster.categoryid,
				stockmaster.stockid";

}
$InventoryResult = DB_query($SQL,'','',false,true);
$ListCount = DB_num_rows($InventoryResult);

if (DB_error_no() !=0) {
	$Title = _('Inventory Valuation') . ' - ' . _('Problem Report');
	include('includes/header.php');
	echo _('The inventory valuation could not be retrieved by the SQL because') . ' - ' . DB_error_msg();
	echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	if ($debug==1){
		echo '<br />' . $SQL;
	}

include('includes/footer.php');
exit;
}

include ('includes/PDFInventoryValnPageHeader.inc');

$Tot_Val=0;
$Category = '';
$CatTot_Val=0;
While ($InventoryValn = DB_fetch_array($InventoryResult)){

	if ($Category!=$InventoryValn['categoryid']){
		$FontSize=10;
		if ($Category!=''){ /*Then it's NOT the first time round */

		/* need to print the total of previous category */
			if ($_POST['DetailedReport']=='Yes'){
				$YPos -= (2*$line_height);
				$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,_('Total for') . ' ' . $Category . " - " . $CategoryName);
			}

			$DisplayCatTotVal = locale_number_format($CatTot_Val,2);
			$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,$DisplayCatTotVal, 'right');
			$YPos -=$line_height;

			If ($_POST['DetailedReport']=='Yes'){
			/*draw a line under the CATEGORY TOTAL*/
				$PDF->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
				$YPos -=(2*$line_height);
			}
			$CatTot_Val=0;
		}
		$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,$InventoryValn['categoryid'] . " - " . $InventoryValn['categorydescription']);
		$Category = $InventoryValn['categoryid'];
		$CategoryName = $InventoryValn['categorydescription'];
	}

	if ($_POST['DetailedReport']=='Yes'){
		$YPos -=$line_height;
		$FontSize=8;

		$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,$InventoryValn['stockid']);				$LeftOvers = $PDF->addTextWrap(120,$YPos,260,$FontSize,$InventoryValn['description']);
		$DisplayUnitCost = locale_number_format($InventoryValn['unitcost'],$_SESSION['CompanyRecord']['decimalplaces']);
		$DisplayQtyOnHand = locale_number_format($InventoryValn['qtyonhand'],0);
		$DisplayItemTotal = locale_number_format($InventoryValn['itemtotal'],$_SESSION['CompanyRecord']['decimalplaces']);

		$LeftOvers = $PDF->addTextWrap(380,$YPos,60,$FontSize,$DisplayQtyOnHand,'right');
		$LeftOvers = $PDF->addTextWrap(440,$YPos,60,$FontSize,$DisplayUnitCost, 'right');
		$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,$DisplayItemTotal, 'right');

	}
	$Tot_Val += $InventoryValn['itemtotal'];
	$CatTot_Val += $InventoryValn['itemtotal'];

	if ($YPos < $Bottom_Margin + $line_height){
		include('includes/PDFInventoryValnPageHeader.inc');
	}

} /*end inventory valn while loop */

$FontSize =10;
/*Print out the category totals */
if ($_POST['DetailedReport']=='Yes'){
	$YPos -=$line_height;
	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize, _('Total for') . ' ' . $Category . ' - ' . $CategoryName, 'left');
}

$DisplayCatTotVal = locale_number_format($CatTot_Val,2);
$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,$DisplayCatTotVal, 'right');

If ($_POST['DetailedReport']=='Yes'){
	/*draw a line under the CATEGORY TOTAL*/
	$PDF->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);
}

$YPos -= (2*$line_height);

/*Print out the grand totals */
$LeftOvers = $PDF->addTextWrap(80, $YPos,260-$Left_Margin,$FontSize, _('Grand Total Value'), 'right');
$DisplayTotalVal = locale_number_format($Tot_Val,2);
$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,$DisplayTotalVal, 'right');
If ($_POST['DetailedReport']=='Yes'){
	$PDF->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);
}

if ($ListCount == 0) {
	$Title = _('Print Inventory Valuation Error');
	include('includes/header.php');
	echo '<p>' . _('There were no items with any value to print out for the location specified');
	echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
	include('includes/footer.php');
	exit; // Javier: needs check
} else {
	include('includes/htmlMimeMail.php');

	$PDF->Output($_SESSION['reports_dir'] . '/InventoryReport.pdf', 'F');
	$PDF-> __destruct();

	$mail = new htmlMimeMail();
	$attachment = $mail->getFile( $_SESSION['reports_dir'] . '/InventoryReport.pdf');
	$mail->setText(_('Please find herewith the stock valuation report'));
	$mail->setSubject(_('Inventory Valuation Report'));
	$mail->addAttachment($attachment, 'InventoryReport.pdf', 'application/pdf');
	if($_SESSION['SmtpSetting']==0){
		$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] . '>');
		$result = $mail->send($Recipients);
	}else{
		$result = SendmailBySmtp($mail,$Recipients);
	}
	if($result){
			$Title = _('Print Inventory Valuation');
			include('includes/header.php');
			prnMsg(_('The Inventory valuation report has been mailed'),'success');
			echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
			include('includes/footer.php');
			exit;

	} else {
			$Title = _('Print Inventory Valuation Error');
			include('includes/header.php');
			prnMsg(_('There are errors lead to mails not sent'),'error');
			echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
			include('includes/footer.php');
			exit;

	}

}
?>