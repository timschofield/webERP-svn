<?php
if ($PageNumber>1){
	$PDF->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$PDF->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,50);

$FontSize=15;

Switch ($_POST['TransType']) {
	case 10:
		$TransType=_('Customer Invoices');
		break;
	case 11:
		$TransType=_('Customer Credit Notes');
		break;
	case 12:
		$TransType=_('Customer Payments');
}

$XPos = $Left_Margin;
$YPos -= 40;
$PDF->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize=12;
$PDF->addText($XPos, $YPos-20,$FontSize, $TransType . ' ' ._('input on') . ' ' . $_POST['Date']);

$XPos = $Page_Width-$Right_Margin-50;
$YPos -=30;
$PDF->addText($XPos, $YPos,$FontSize, _('Page') . ': ' . $PageNumber);

/*Now print out the company name and address */
$XPos = $Left_Margin;
$YPos -= $line_height;

/*draw a square grid for entering line items */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$PDF->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $Bottom_Margin);
$PDF->line($Page_Width-$Right_Margin, $Bottom_Margin,$XPos, $Bottom_Margin);
$PDF->line($XPos, $Bottom_Margin,$XPos, $YPos);

$YPos -= $line_height;
/*Set up headings */
$FontSize=8;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,160,$FontSize,_('Customer'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+162,$YPos,80,$FontSize,_('Reference'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+242,$YPos,70,$FontSize,_('Trans Date'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+312,$YPos,70,$FontSize,_('Net Amount'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+382,$YPos,70,$FontSize,_('Tax Amount'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+452,$YPos,70,$FontSize,_('Total Amount'), 'right');
$YPos-=$line_height;

/*draw a line */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$YPos -= ($line_height);
?>