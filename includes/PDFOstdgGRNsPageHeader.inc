<?php
/*PDF page header for outstanding GRNs report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,260,$FontSize,$_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,_('Outstanding GRNs Valuation for Suppliers between') . ' ' . $_POST['FromCriteria'] . ' ' . _('and') . ' ' . $_POST['ToCriteria']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,220,$FontSize,_('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber);

$YPos -=(2*$line_height);

/*Draw a rectangle to put the headings in     */

$PDF->line($Left_Margin, $YPos-$line_height,$Page_Width-$Right_Margin, $YPos-$line_height);
$PDF->line($Left_Margin, $YPos-$line_height,$Left_Margin, $YPos+10);
$PDF->line($Left_Margin, $YPos+10,$Page_Width-$Right_Margin, $YPos+10);
$PDF->line($Page_Width-$Right_Margin, $YPos-$line_height,$Page_Width-$Right_Margin, $YPos+10);

/*Draw a rectangle to put the details in     */

$PDF->line($Left_Margin, $Bottom_Margin, $Page_Width-$Right_Margin, $Bottom_Margin);
$PDF->line($Left_Margin, $Bottom_Margin, $Left_Margin, $YPos+10);
$PDF->line($Page_Width-$Right_Margin, $Bottom_Margin, $Page_Width-$Right_Margin, $YPos+10);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap(32,$YPos,40,$FontSize,_('GRN'), 'centre');
$LeftOvers = $PDF->addTextWrap(70,$YPos,40,$FontSize,_('Order') . ' #', 'centre');
$LeftOvers = $PDF->addTextWrap(110,$YPos,200,$FontSize,_('Item') . ' / ' . _('Description'), 'centre');
$LeftOvers = $PDF->addTextWrap(310,$YPos,50,$FontSize,_('Qty Recd'), 'centre');
$LeftOvers = $PDF->addTextWrap(360,$YPos,50,$FontSize,_('Qty Inv'), 'centre');
$LeftOvers = $PDF->addTextWrap(410,$YPos,50,$FontSize,_('Balance'), 'centre');
$LeftOvers = $PDF->addTextWrap(460,$YPos,50,$FontSize,_('Std Cost'), 'centre');
$LeftOvers = $PDF->addTextWrap(510,$YPos,50,$FontSize,_('Value'), 'centre');

$YPos =$YPos - (2*$line_height);

$PageNumber++;
$FontSize=8;
?>
