<?php
/*PDF page header for inventory valuation report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,_('Sell Through Support Claims Between') . ' ' . $_POST['FromDate'] . ' ' . _('and') . ' ' . $_POST['ToDate']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,120,$FontSize,_('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '    ' . _('Page') . ' ' . $PageNumber);

$YPos -=(2*$line_height);

/*set up the headings */
$Xpos = $Left_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin + 2,$YPos,50,$FontSize,_('Transaction'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin + 90,$YPos,50,$FontSize,_('Item'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin + 230,$YPos,100,$FontSize,_('Customer'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin + 350,$YPos,50,$FontSize,_('Sell Price'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin + 400,$YPos,62,$FontSize,_('Quantity'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin + 480,$YPos,60,$FontSize,_('Claim'), 'right');

$FontSize=8;
$PageNumber++;
?>