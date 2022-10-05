<?php
/*PDF page header for inventory check report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=12;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-180,$YPos,180,$FontSize,_('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber);

$YPos -=15;
sort($_POST['Categories']);
$q = count($_POST['Categories'])-1;
$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize,_('Check Sheets for Categories between') . ' ' . $_POST['Categories'][0] . ' ' . _('and') . ' ' . $_POST['Categories'][$q] . ' ' . _('for stock at') . ' ' . $_POST['Location']);

$YPos -=20;
/*Draw a rectangle to put the headings in     */
$BoxHeight =15;

$PDF->line($Left_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos+$BoxHeight);
$PDF->line($Left_Margin, $YPos+$BoxHeight,$Left_Margin, $YPos- $BoxHeight);
$PDF->line($Left_Margin, $YPos-$BoxHeight,$Page_Width-$Right_Margin, $YPos-$BoxHeight);
$PDF->line($Page_Width-$Right_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos-$BoxHeight);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,300-$Left_Margin,$FontSize,_('Item'), 'centre');
if (isset($_POST['ShowInfo']) and $_POST['ShowInfo']==true){
	$LeftOvers = $PDF->addTextWrap(341,$YPos,60,$FontSize,_('QOH'), 'centre');
	$LeftOvers = $PDF->addTextWrap(341+61,$YPos,80,$FontSize,_('Cust Ords'), 'centre');
	$LeftOvers = $PDF->addTextWrap(341+61+61,$YPos,80,$FontSize,_('Available'), 'centre');
} else {
	$LeftOvers = $PDF->addTextWrap(371,$YPos,60,$FontSize,_('Quantity'), 'centre');
	$LeftOvers = $PDF->addTextWrap(341+61+61,$YPos,80,$FontSize,_('Remarks'), 'centre');
}
$FontSize=10;
$YPos -=($line_height);
?>
