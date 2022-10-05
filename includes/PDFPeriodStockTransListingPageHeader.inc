<?php

if ($PageNumber>1){
	$PDF->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$PDF->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,50);

$FontSize=15;


$XPos = $Page_Width/2;
$YPos += 30;
$PDF->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize=12;
$YPos -=30;
$PDF->addText($XPos, $YPos,$FontSize, $TransType . ' ' ._('dated from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate']);


$XPos = $Page_Width-$Right_Margin-50;
$YPos -=30;
$PDF->addText($XPos, $YPos+10,$FontSize, _('Page') . ': ' . $PageNumber);

/*Now print out the company name and address */
$XPos = $Left_Margin;
$YPos -= $line_height;

/*draw a square grid for entering line items */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$PDF->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $Bottom_Margin);
$PDF->line($Page_Width-$Right_Margin, $Bottom_Margin,$XPos, $Bottom_Margin);
$PDF->line($XPos, $Bottom_Margin,$XPos, $YPos);

$PDF->line($Left_Margin+160, $YPos,$Left_Margin+160, $Bottom_Margin);
$PDF->line($Left_Margin+240, $YPos,$Left_Margin+240, $Bottom_Margin);
$PDF->line($Left_Margin+310, $YPos,$Left_Margin+310, $Bottom_Margin);
$PDF->line($Left_Margin+384, $YPos,$Left_Margin+384, $Bottom_Margin);
$PDF->line($Left_Margin+454, $YPos,$Left_Margin+454, $Bottom_Margin);

$YPos -= $line_height;
/*Set up headings */
$FontSize=8;

$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,160,$FontSize,_('Stock Item'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+162,$YPos,80,$FontSize,_('Reference'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+242,$YPos,70,$FontSize,_('Trans Date'), 'left');
$LeftOvers = $PDF->addTextWrap($Left_Margin+312,$YPos,70,$FontSize,_('Quantity'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+382,$YPos,70,$FontSize,_('Location'), 'right');
$LeftOvers = $PDF->addTextWrap($Left_Margin+452,$YPos,70,$FontSize,_('Reference'), 'right');
$YPos-=$line_height;

/*draw a line */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$YPos -= ($line_height);
?>