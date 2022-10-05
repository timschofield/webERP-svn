<?php
$PageNumber++;
if ($PageNumber>1){
	$PDF->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$PDF->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,60);

$FontSize=15;

$XPos = $Page_Width/2 - 80;

$YPos -= 40;
$PDF->addText($XPos, $YPos,$FontSize, _('Banking Summary'));
$FontSize=12;
$PDF->addText($XPos-50, $YPos-20,$FontSize, _('for Receipt Batch') . ' # ' . $_POST['BatchNo'] . ' ' . _('of') . ' ' . $BankTransType);

$XPos = $Page_Width-$Right_Margin-50;
$YPos -=30;
$PDF->addText($XPos, $YPos,$FontSize, _('Page') .': ' . $PageNumber);

/*Now print out the company name and address */
$XPos = $Left_Margin;
$YPos -= $line_height;

$PDF->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize=10;

$YPos -=$line_height;
$XPos = $Left_Margin;


$PDF->addText($XPos, $YPos,$FontSize, _('Date of Banking') .': ' . ConvertSQLDate($myrow['transdate']));
$YPos -= $line_height;
$PDF->addText($XPos, $YPos,$FontSize, _('Banked into') . ': ' . $BankActName . ' - ' . _('Account Number') . ': ' . $BankActNumber);
$YPos -= $line_height;
$PDF->addText($XPos, $YPos,$FontSize, _('Reference') . ': ' . $BankingReference);
$YPos -= $line_height;
$PDF->addText($XPos, $YPos,$FontSize, _('Currency') . ': ' . $Currency);
$YPos -= $line_height;

/*draw a square grid for entering line items */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$PDF->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $Bottom_Margin);
$PDF->line($Page_Width-$Right_Margin, $Bottom_Margin,$XPos, $Bottom_Margin);
$PDF->line($XPos, $Bottom_Margin,$XPos, $YPos);

$YPos -= $line_height;
/*Set up headings */
$FontSize=8;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Amount'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+62,$YPos,150,$FontSize,_('Customer'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+212,$YPos,100,$FontSize,_('Bank Details'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+312,$YPos,100,$FontSize,_('Narrative'), 'centre');
$YPos-=$line_height;

/*draw a line */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$YPos -= ($line_height);
?>