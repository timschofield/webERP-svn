<?php
if ($PageNumber>1){
	$PDF->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$PDF->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,50);

$FontSize=15;

$XPos = $Left_Margin;
$YPos -= 40;
$PDF->addText($XPos, $YPos,$FontSize, $BankAccountName . ' ' . _('Payments Summary'));
$FontSize=12;
$PDF->addText($XPos, $YPos-20,$FontSize, _('From') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' .  $_POST['ToDate']);

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

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Amount'), 'centre');
$LeftOvers = $PDF->addTextWrap($Left_Margin+62,$YPos,180,$FontSize,_('Reference / General Ledger Posting Details'), 'centre');
$YPos-=$line_height;

/*draw a line */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$YPos -= ($line_height);
?>