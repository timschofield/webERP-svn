<?php
/* pdf-php by R&OS code to set up a new sales order page */
if ($PageNumber>1){
	$pdf->newPage();
}

$XPos = $Page_Width/2 - 60;
/* if the deliver blind flag is set on the order, we do not want to output
the company logo */
if ($DeliverBlind < 2) {
    $pdf->addJpegFromFile($_SESSION['LogoFile'],$XPos,490,0,60);
}
$FontSize=18;

if ($Copy=='Customer'){
	$pdf->addText($XPos-40, 585,$FontSize, _('Packing Slip') . ' - ' . _('Customer Copy') );
} else {
	$pdf->addText($XPos-40, 585,$FontSize, _('Packing Slip') . ' - ' . _('Office Copy') );
}

/* if the deliver blind flag is set on the order, we do not want to output
the company contact info */
if ($DeliverBlind < 2) {
    $YPos = 480;
	PrintOurCompanyInfo($pdf,$_SESSION['CompanyRecord'],$XPos,$YPos);
}

$XPos = 46;
$YPos = 566;
PrintDeliverTo($pdf,$myrow,_('Delivered To'),$XPos,$YPos);

$YPos -= 82;
PrintCompanyTo($pdf,$myrow,_('Customer'),$XPos,$YPos);


$FontSize = 14;
$pdf->addText($XPos, $YPos-82,$FontSize, _('Customer No.'). ': ' . $myrow['debtorno']);
$pdf->addText($XPos, $YPos-100,$FontSize, _('Shipped by'). ': ' . $myrow['shippername']);

$FontSize=12;
$LeftOvers = $pdf->addTextWrap($XPos,$YPos-130,250,$FontSize,_('Comments').': '.stripcslashes($myrow['comments']));

if (mb_strlen($LeftOvers)>1){
	$LeftOvers = $pdf->addTextWrap($XPos,$YPos-145,250,$FontSize,$LeftOvers);
	if (mb_strlen($LeftOvers)>1){
		$LeftOvers = $pdf->addTextWrap($XPos,$YPos-160,250,$FontSize,$LeftOvers);
		if (mb_strlen($LeftOvers)>1){
			$LeftOvers = $pdf->addTextWrap($XPos,$YPos-175,250,$FontSize,$LeftOvers);
			if (mb_strlen($LeftOvers)>1){
				$LeftOvers = $pdf->addTextWrap($XPos,$YPos-180,250,$FontSize,$LeftOvers);
			}
		}
	}
}

$FontSize=14;
$pdf->addText(620, 560,$FontSize, _('Order No'). ':');
$pdf->addText(700, 560,$FontSize, $_GET['TransNo']);
$pdf->addText(620, 560-15,$FontSize, _('Your Ref'). ':');
$pdf->addText(700, 560-15,$FontSize, $myrow['customerref']);
$pdf->addText(620, 560-45,$FontSize,  _('Order Date'). ':');
$pdf->addText(700, 560-45,$FontSize,  ConvertSQLDate($myrow['orddate']));
$pdf->addText(620, 560-60,$FontSize,  _('Printed') . ': ');
$pdf->addText(700, 560-60,$FontSize,  Date($_SESSION['DefaultDateFormat']));
$pdf->addText(620, 560-75,$FontSize,  _('From').': ');
$pdf->addText(700, 560-75,$FontSize,  $myrow['locationname']);
$pdf->addText(620, 560-90,$FontSize,  _('Page'). ':');
$pdf->addText(700, 560-90,$FontSize,  $PageNumber);

$YPos -= 170;
$XPos = 15;

$header_line_height = $line_height+25;

$LeftOvers = $pdf->addTextWrap($XPos,$YPos,127,$FontSize, _('Item Code'),'left');
$LeftOvers = $pdf->addTextWrap(147,$YPos,255,$FontSize, _('Item Description'),'left');
$LeftOvers = $pdf->addTextWrap(400,$YPos,85,$FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(487,$YPos,85,$FontSize, _('Units'),'left');
$LeftOvers = $pdf->addTextWrap(527,$YPos,70,$FontSize, _('Bin Locn'),'left');
$LeftOvers = $pdf->addTextWrap(593,$YPos,85,$FontSize,_('This Del'),'right');
$LeftOvers = $pdf->addTextWrap(692,$YPos,85,$FontSize, _('Prev Dels'),'right');

$YPos -= $line_height;

$FontSize =12;

?>
