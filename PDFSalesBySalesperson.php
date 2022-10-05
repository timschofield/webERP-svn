<?php

$DatabaseName='weberp';
$AllowAnyone = true;

include ('includes/session.php');
include('includes/SQL_CommonFunctions.inc');
include ('includes/class.pdf.php');
$_POST['FromDate']=date('Y-m-01');
$_POST['ToDate']= FormatDateForSQL(Date($_SESSION['DefaultDateFormat']));
$WeekStartDate = Date(($_SESSION['DefaultDateFormat']), strtotime($WeekStartDate . ' - 7 days'));
$Recipients = GetMailList('salesbysalesperson');
if (sizeOf($Recipients) == 0) {
	$Title = _('Weekly Orders') . ' - ' . _('Problem Report');
      	include('includes/header.php');
	prnMsg( _('There are no members of the Weekly Orders Recipients email group'), 'warn');
	include('includes/footer.php');
	exit;
}

$sql= "SELECT salesorders.orderno,
			  salesorders.orddate,
			  salesorderdetails.stkcode,
			  salesorderdetails.unitprice,
			  stockmaster.description,
			  stockmaster.units,
			  stockmaster.decimalplaces,
			  salesorderdetails.quantity,
			  salesorderdetails.qtyinvoiced,
			  salesorderdetails.completed,
			  salesorderdetails.discountpercent,
			  stockmaster.materialcost+stockmaster.labourcost+stockmaster.overheadcost AS standardcost,
			  debtorsmaster.name,
			  salesman.salesmanname
		 FROM salesorders
			 INNER JOIN salesorderdetails
			 ON salesorders.orderno = salesorderdetails.orderno
			 INNER JOIN stockmaster
			 ON salesorderdetails.stkcode = stockmaster.stockid
			 INNER JOIN debtorsmaster
			 ON salesorders.debtorno=debtorsmaster.debtorno
			 INNER JOIN custbranch ON custbranch.debtorno=salesorders.debtorno
			 AND custbranch.branchcode=salesorders.branchcode
			 INNER JOIN salesman ON salesman.salesmancode=custbranch.salesman
		 WHERE salesorders.orddate >='" . FormatDateForSQL($WeekStartDate) . "'
			  AND salesorders.orddate <='" . $_POST['ToDate'] . "'
		 AND salesorders.quotation=0
		 ORDER BY custbranch.salesman, salesorders.orderno";

$Result=DB_query($sql,'','',false,false); //dont trap errors here

if (DB_error_no()!=0){
	include('includes/header.php');
	echo '<br />' . _('An error occurred getting the orders details');
	if ($debug==1){
		echo '<br />' . _('The SQL used to get the orders that failed was') . '<br />' . $sql;
	}
	include ('includes/footer.php');
	exit;
}
$PaperSize="Letter_Landscape";
include('includes/PDFStarter.php');
$PDF->addInfo('Title',_('Weekly Orders Report'));
$PDF->addInfo('Subject',_('Orders from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate']);
$line_height=12;
$PageNumber = 1;
$TotalDiffs = 0;
include ('includes/PDFWeeklyOrdersPageHeader.inc');
$Col1=2;
$Col2=40;
$Col3=160;
$Col4=210;
$Col5=260;
$Col6=390;
$Col7=450;
$Col8=510;
$Col9=570;
$Col10=650;
$Col11=660;

$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col1,$YPos,$Col2-$Col1-5,$FontSize,_('Order'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col2,$YPos,$Col3-$Col2-5,$FontSize,_('Customer'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col3,$YPos,$Col4-$Col3-5,$FontSize,_('Order Date'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col4,$YPos,$Col5-$Col4-5,$FontSize,_('Item'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col5,$YPos,$Col6-$Col5-5,$FontSize,_('Description'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col6,$YPos,$Col7-$Col6-5,$FontSize,_('Quantity'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col7,$YPos,$Col8-$Col7-5,$FontSize,_('Sales'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col8,$YPos,$Col9-$Col8-5,$FontSize,_('Status'), 'Left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col9,$YPos,$Col10-$Col9-5,$FontSize,_('Salesperson'), 'Left');

$YPos-=$line_height;
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$YPos-=$line_height;
$Salesman='';
while ($myrow=DB_fetch_array($Result)){

	if ($myrow['completed']==1) {
		$Status="Closed";
		$Qty=$myrow['qtyinvoiced'];
	} else {
		$Qty=$myrow['quantity'];
		if ($myrow['qtyinvoiced']==0) {
			$Status= _('Ordered');
		} else {
			$Status= _('Partial');
		}
	}
	$SalesValue=$Qty*$myrow['unitprice']*(1-$myrow['discountpercent']);
	$SalesCost=$Qty*$myrow['standardcost'];
	if ($SalesValue <> 0) {
		$GP=($SalesValue-$SalesCost)/$SalesValue *100;
	} else {
		$GP=0;
	}

	if ($Salesman > '' and $Salesman <> $myrow['salesmanname']){
		$PageNumber++;
		include ('includes/PDFWeeklyOrdersPageHeader.inc');
	} /*end of new page header  */
	$Salesman = $myrow['salesmanname'];

	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col1,$YPos,$Col2-$Col1-5,$FontSize,$myrow['orderno'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col2,$YPos,$Col3-$Col2-5,$FontSize,html_entity_decode($myrow['name'],ENT_QUOTES,'UTF-8'), 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col3,$YPos,$Col4-$Col3-5,$FontSize,ConvertSQLDate($myrow['orddate']), 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col4,$YPos,$Col5-$Col4-5,$FontSize,$myrow['stkcode'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col5,$YPos,$Col6-$Col5-5,$FontSize,$myrow['description'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col6,$YPos,$Col7-$Col6-5,$FontSize,locale_number_format($myrow['quantity'],$_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col7,$YPos,$Col8-$Col7-5,$FontSize,locale_number_format($SalesValue,$_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col8,$YPos,$Col9-$Col8-5,$FontSize,$Status, 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+$Col9,$YPos,$Col10-$Col9-5,$FontSize,$myrow['salesmanname'], 'left');
	if ($YPos - (2 *$line_height) < $Bottom_Margin){
		$PageNumber++;
		include ('includes/PDFWeeklyOrdersPageHeader.inc');
	} /*end of new page header  */
	$YPos -= $line_height;

} //while

include('includes/htmlMimeMail.php');
$filename=$_SESSION['reports_dir'] .  '/SalesBySalesperson.pdf';
$PDF->Output($filename, 'F');
$PDF->__destruct();
$mail = new htmlMimeMail();
$attachment = $mail->getFile($filename);
$mail->setText(_('Please find the Sales By Salesperson report'));
$mail->setSubject(_('Sales By Salesperson Report'));
$mail->addAttachment($attachment, $filename, 'application/pdf');
//echo '<br /><div class="centre"><a href="' . $RootPath . '/' . $filename . '">' . _('click here') . '</a> ' . _('to view the file') . '</div>';
if($_SESSION['SmtpSetting']==0){
	$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] . '>');
	$result = $mail->send($Recipients);
}else{
	$result = SendmailBySmtp($mail,$Recipients);
}
if($result){
		$Title = _('Print Weekly Orders');
		include('includes/header.php');
		prnMsg(_('The Weekly Orders report has been mailed'),'success');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;

}else{
		$Title = _('Print Weekly Orders Error');
		include('includes/header.php');
		prnMsg(_('There are errors lead to mails not sent'),'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;

}
?>