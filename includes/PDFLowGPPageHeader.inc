<?php
/*PDF page header for inventory valuation report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,_('Low GP Sales Between') . ' ' . $_POST['FromDate'] . ' ' . _('and') . ' ' . $_POST['ToDate'] . ' ' . _('less than') . ' ' . $_POST['GPMin'] . '%');
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-120,$YPos,120,$FontSize,_('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '    ' . _('Page') . ' ' . $PageNumber);

$YPos -=(2*$line_height);

/*Draw a rectangle to put the headings in     */

$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos- $line_height);
$PDF->line($Left_Margin, $YPos- $line_height,$Page_Width-$Right_Margin, $YPos- $line_height);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos- $line_height);

$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $Bottom_Margin);
$PDF->line($Left_Margin, $Bottom_Margin, $Page_Width-$Right_Margin, $Bottom_Margin);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $Bottom_Margin);

$PDF->line(98, $YPos+$line_height, 98, $Bottom_Margin);
$PDF->line(128, $YPos+$line_height, 128, $Bottom_Margin);
$PDF->line(218, $YPos+$line_height, 218, $Bottom_Margin);
$PDF->line(338, $YPos+$line_height, 338, $Bottom_Margin);
$PDF->line(398, $YPos+$line_height, 398, $Bottom_Margin);
$PDF->line(448, $YPos+$line_height, 448, $Bottom_Margin);
$PDF->line(503, $YPos+$line_height, 503, $Bottom_Margin);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,100-$Left_Margin,$FontSize,_('Trans'), 'centre');
$LeftOvers = $PDF->addTextWrap(100,$YPos,50,$FontSize,_('No'), 'centre');
$LeftOvers = $PDF->addTextWrap(130,$YPos,50,$FontSize,_('Item'), 'centre');
$LeftOvers = $PDF->addTextWrap(220,$YPos,130,$FontSize,_('Customer'), 'centre');
$LeftOvers = $PDF->addTextWrap(340,$YPos,50,$FontSize,_('Sell Price'), 'right');
$LeftOvers = $PDF->addTextWrap(380,$YPos,62,$FontSize,_('Cost'), 'right');
$LeftOvers = $PDF->addTextWrap(440,$YPos,60,$FontSize,_('GP'), 'right');
$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,_('GP') . ' %', 'right');

$FontSize=8;
$YPos =$YPos - (2*$line_height);

$PageNumber++;

?>