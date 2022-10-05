<?php

/*PDF page header for inventory planning report */

if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$FontSize=10;

$ReportTitle = _('Inventory Planning for Product Categories');
foreach ($_POST['Categories'] as $Category) {
	$ReportTitle .= ' ' . $Category;
}

if ($_POST['Location']=='All'){

	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos,600,$FontSize, $ReportTitle . ' ' . _('for all stock locations'));

} else {

	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos,600,$FontSize, $ReportTitle . ' ' . _('for stock at') . ' ' . $_POST['Location']);

}

$FontSize=8;
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,120,$FontSize,_('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber);

$YPos -=(2*$line_height);

/*Draw a rectangle to put the headings in     */

$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos- $line_height);
$PDF->line($Left_Margin, $YPos- $line_height,$Page_Width-$Right_Margin, $YPos- $line_height);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos- $line_height);

/*set up the headings */
$XPos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($XPos,$YPos,180,$FontSize,_('Item'),'centre');
$LeftOvers = $PDF->addTextWrap(160,$YPos,45,$FontSize,_('Description'),'centre');
$LeftOvers = $PDF->addTextWrap(270,$YPos,40,$FontSize,$Period_5_Name . ' ' . _('Qty'),'centre');
$LeftOvers = $PDF->addTextWrap(307,$YPos,40,$FontSize,$Period_4_Name . ' ' . _('Qty'),'centre');
$LeftOvers = $PDF->addTextWrap(348,$YPos,40,$FontSize,$Period_3_Name . ' ' . _('Qty'),'centre');
$LeftOvers = $PDF->addTextWrap(389,$YPos,40,$FontSize,$Period_2_Name . ' ' . _('Qty'),'centre');
$LeftOvers = $PDF->addTextWrap(430,$YPos,40,$FontSize,$Period_1_Name . ' ' . _('Qty'),'centre');
$LeftOvers = $PDF->addTextWrap(471,$YPos,40,$FontSize,$Period_0_Name . ' ' . _('MTD'),'centre');

$stat=$_POST['NumberMonthsHolding'];
	if ($_POST['NumberMonthsHolding']>10){
		$NumberMonthsHolding=$_POST['NumberMonthsHolding']-10;
	}
	else{
		$NumberMonthsHolding=$_POST['NumberMonthsHolding'];

	}
$LeftOvers = $PDF->addTextWrap(512,$YPos,40,$FontSize,$NumberMonthsHolding . ' ' . _('ms stk'),'centre');
$LeftOvers = $PDF->addTextWrap(617,$YPos,40,$FontSize,_('QOH'),'centre');
$LeftOvers = $PDF->addTextWrap(648,$YPos,40,$FontSize,_('Cust Ords'),'centre');
$LeftOvers = $PDF->addTextWrap(694,$YPos,40,$FontSize,_('Splr Ords'),'centre');
$LeftOvers = $PDF->addTextWrap(735,$YPos,40,$FontSize,_('Sugg Ord'),'centre');

$YPos =$YPos - (2*$line_height);
$FontSize=8;
?>