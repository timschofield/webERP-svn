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
$PDF->addText($Left_Margin, $YPos,$FontSize, _('Aged Supplier Balances For Suppliers from') . ' ' . $_POST['FromCriteria'] . ' ' . _('to') . ' ' . $_POST['ToCriteria']);
$PDF->addText($Left_Margin, $YPos-$line_height,$FontSize, _('And Trading in') . ' ' . $_POST['Currency']);

$FontSize = 8;
$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed') . ': ' . Date("d M Y") . '  ' ._('Page') . ' ' . $PageNumber);

$YPos -=(3*$line_height);

/*Draw a rectangle to put the headings in     */
$PDF->line($Page_Width-$Right_Margin, $YPos-5,$Left_Margin, $YPos-5);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Left_Margin, $YPos+$line_height);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos-5);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos-5);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,220 - $Left_Margin,$FontSize,_('Supplier'),'centre');
$LeftOvers = $PDF->addTextWrap(220,$YPos,60,$FontSize,_('Balance'),'centre');
$LeftOvers = $PDF->addTextWrap(280,$YPos,60,$FontSize,_('Current'),'centre');
$LeftOvers = $PDF->addTextWrap(340,$YPos,60,$FontSize,_('Due Now'),'centre');
$LeftOvers = $PDF->addTextWrap(400,$YPos,60,$FontSize,"> " . $_SESSION['PastDueDays1'] . ' ' . _('Days Over'),'centre');
$LeftOvers = $PDF->addTextWrap(460,$YPos,60,$FontSize,"> " . $_SESSION['PastDueDays2'] . ' ' . _('Days Over'),'centre');

$YPos =$YPos - (2*$line_height);

?>