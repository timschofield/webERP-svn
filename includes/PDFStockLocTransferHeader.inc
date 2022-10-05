<?php

/*PDF page header for inventory check report */
if ($PageNumber>1){
	$PDF->newPage();
}

$YPos= $Page_Height-$Top_Margin;
$YPos -=15; // Downs one line height (coordinates are from bottom left page to bottom left text).
$FontSize=12;
$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

$LeftOvers = $PDF->addTextWrap(	$Page_Width-$Right_Margin-550,
								$Page_Height-$Top_Margin-$FontSize*1,
								550,
								$FontSize,
								_('Transfer No').': ' . $_GET['TransferNo'],
								'right'); // Prints inventory location transfer reference number.

$LeftOvers = $PDF->addTextWrap(	$Page_Width-$Right_Margin-120,
								$Page_Height-$Top_Margin-$FontSize*2,
								120,
								$FontSize,
								_('Printed'). ': ' . Date($_SESSION['DefaultDateFormat']),
								'right'); // Prints current date.

$LeftOvers = $PDF->addTextWrap(	$Page_Width-$Right_Margin-120,
								$Page_Height-$Top_Margin-$FontSize*3,
								120,
								$FontSize,
								_('Page'). ' ' . $PageNumber,
								'right'); // Prints current page number.

$YPos -=15;
$FontSize=12;
$LeftOvers2 = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize,
_('Shipped'). ' ' . ConvertSQLDateTime($TransferRow['shipdate']));

$YPos -=15;
$FontSize=12;
$LeftOvers2 = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize,
_('From').' ' . $TransferRow['shiplocname']);

$YPos -=15;
$FontSize=12;
$LeftOvers2 = $PDF->addTextWrap($Left_Margin,$YPos,550,$FontSize,
_('to').' ' . $TransferRow['reclocname']);

/*Draw a rectangle to put the headings in     */
$BoxHeight =20;
$FontSize=12;
$YPos-=20;
$PDF->line($Left_Margin, $YPos,$Page_Width-$Right_Margin, $YPos);
$PDF->line($Left_Margin, $YPos,$Left_Margin, $YPos-$BoxHeight);
$PDF->line($Left_Margin, $YPos-$BoxHeight,$Page_Width-$Right_Margin, $YPos-$BoxHeight);
$PDF->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $YPos-$BoxHeight);
$YPos -=20;

$YPos +=5;

/*set up the headings */
$Xpos = $Left_Margin+1;
$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 100, $FontSize, _('Item Code'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+100, $YPos, 250, $FontSize, _('Description'), 'left');
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-100-100, $YPos, 100, $FontSize, _('Qty Shipped'), 'right');
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-100, $YPos, 100, $FontSize, _('Qty Received'), 'right');

$FontSize=10;
$YPos -= 30;

?>
