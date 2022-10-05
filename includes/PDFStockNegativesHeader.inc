<?php

/*PDF page header for inventory check report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=12;
$YPos= $Page_Height-$Top_Margin;
$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,120,$FontSize, _('Printed'). ': ' . Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page'). ' ' . $PageNumber);

$YPos -=15;
$FontSize=12;
$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize, _('Negative Stocks Listing'));

$YPos -=25;
/*Draw a rectangle to put the headings in     */
$BoxHeight =20;
$FontSize=12;
$PDF->line($Left_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos+$BoxHeight);
$PDF->line($Left_Margin, $YPos+$BoxHeight,$Left_Margin, $YPos);
$PDF->line($Left_Margin, $YPos,$Page_Width-$Right_Margin, $YPos);
$PDF->line($Page_Width-$Right_Margin, $YPos+$BoxHeight,$Page_Width-$Right_Margin, $YPos);

$YPos +=5;

/*set up the headings */
$Xpos = $Left_Margin+1;
$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,100-$Left_Margin, $FontSize,_('Location'), 'centre');
$LeftOvers = $PDF->addTextWrap(170,$YPos,150-$Left_Margin,$FontSize,_('Item Description'), 'centre');
$LeftOvers = $PDF->addTextWrap(361,$YPos,120,$FontSize,_('Quantity'), 'centre');

$FontSize=10;
$YPos -= 30;

?>