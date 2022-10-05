<?php
/*PDF page header for inventory check report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=12;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,120,$FontSize, _('Printed'). ': ' . Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page').' ' . $PageNumber);

$YPos -=15;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize, _('Stock Check Comparison at') . ' ' . $LocationName );


$YPos -=15;
/*Draw a rectangle to put the headings in     */
$BoxHeight =10;

$PDF->line($Left_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos+$BoxHeight);
$PDF->line($Left_Margin, $YPos+$BoxHeight,$Left_Margin, $YPos- $BoxHeight);
$PDF->line($Left_Margin, $YPos-$BoxHeight,$Page_Width-$Right_Margin, $YPos-$BoxHeight);
$PDF->line($Page_Width-$Right_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos-$BoxHeight);

/*set up the headings */
$Xpos = $Left_Margin+1;

$YPos -=3;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,300-$Left_Margin,$FontSize, _('Item'), 'centre');
$LeftOvers = $PDF->addTextWrap(330,$YPos,60,$FontSize, _('QOH'), 'centre');
$LeftOvers = $PDF->addTextWrap(330+41,$YPos,60,$FontSize, _('Counted'), 'centre');
$LeftOvers = $PDF->addTextWrap(330+41+61,$YPos,60,$FontSize, _('Reference'), 'centre');
$LeftOvers = $PDF->addTextWrap(330+41+61+60,$YPos,70,$FontSize, _('Adjustment'), 'centre');

$FontSize=10;
$YPos -=(2*$line_height);
?>