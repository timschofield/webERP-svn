<?php
/*PDF page header for aged analysis reports */
$PageNumber++;
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=8;
$YPos= $Page_Height-$Top_Margin;

$PDF->addText($Left_Margin, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$FontSize =10;
$numHeads=2;
$HeadingLine1 = _('Aged Customer Balances For Customers from') . ' ' . $_POST['FromCriteria'] . ' ' .  _('to') . ' ' . $_POST['ToCriteria'];
$HeadingLine2 = _('And Trading in') . ' ' . $_POST['Currency'];
if (trim($_POST['Salesman'])!=''){
	$SQL = "SELECT salesmanname FROM salesman WHERE salesmancode='".$_POST['Salesman']."'";
	$rs = DB_query($SQL,'','',False,False);
	$row = DB_fetch_array($rs);
	$HeadingLine3 = _('And Has at Least 1 Branch Serviced By Sales Person #'). ' '. $_POST['Salesman'] . ' - ' . $row['salesmanname'];
	$numHeads++;
}
$PDF->addText($Left_Margin, $YPos,$FontSize, $HeadingLine1);
$PDF->addText($Left_Margin, $YPos-$line_height,$FontSize, $HeadingLine2);
if (isset($HeadingLine3) and $HeadingLine3 != ''){
	$PDF->addText($Left_Margin, $YPos-$line_height*2,$FontSize, $HeadingLine3);
}
$FontSize = 8;

$DatePrintedString = _('Printed') . ': ' . Date("d M Y") . '   ' . _('Page') . ' ' . $PageNumber;
$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, $DatePrintedString);

$YPos -=(($numHeads+1)*$line_height);

/*Draw a rectangle to put the headings in     */
$PDF->line($Page_Width-$Right_Margin, $YPos-5,$Left_Margin, $YPos-5);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Left_Margin, $YPos+$line_height);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos-5);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos-5);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,220 - $Left_Margin,$FontSize,_('Customer'),'centre');
$LeftOvers = $PDF->addTextWrap(220,$YPos,60,$FontSize,_('Balance'),'centre');
$LeftOvers = $PDF->addTextWrap(280,$YPos,60,$FontSize,_('Current'),'centre');
$LeftOvers = $PDF->addTextWrap(340,$YPos,60,$FontSize,_('Due Now'),'centre');
$LeftOvers = $PDF->addTextWrap(400,$YPos,60,$FontSize,'> ' . $_SESSION['PastDueDays1'] . ' ' . _('Days Over'),'centre');
$LeftOvers = $PDF->addTextWrap(460,$YPos,60,$FontSize,'> ' . $_SESSION['PastDueDays2'] . ' ' . _('Days Over'),'centre');

$YPos =$YPos - (2*$line_height);

?>
