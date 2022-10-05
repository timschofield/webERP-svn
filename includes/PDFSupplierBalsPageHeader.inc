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
$PDF->addText($Left_Margin, $YPos, $FontSize, _('Supplier Balances For Suppliers between') . ' ' . $_POST['FromCriteria'] . ' ' . _('and') . ' ' . $_POST['ToCriteria'] . ' ' . _('as at') . ' ' . ConvertSQLDate($_POST['PeriodEnd']));

$FontSize = 8;
$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '    ' . _('Page') . ' ' . $PageNumber);

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
$LeftOvers = $PDF->addTextWrap(280,$YPos,60,$FontSize,_('FX'),'centre');
$LeftOvers = $PDF->addTextWrap(340,$YPos,60,$FontSize,_('Currency'),'centre');

$YPos =$YPos - (2*$line_height);

?>