<?php

if ($PageNumber > 1) {
	$PDF->newPage();
}

$XPos = $Page_Width / 2 - 140;
$PDF->addJpegFromFile($_SESSION['LogoFile'], $XPos + 127, 720, 0, 35);

$XPos = $XPos + 130;

$FontSize = 18;
$PDF->addText($XPos, 780, $FontSize, _('Acknowledgement'));
$FontSize = 12;
$YPos = 720;
$PDF->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize = 10;
$PDF->addText($XPos, $YPos - 12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$PDF->addText($XPos, $YPos - 21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$PDF->addText($XPos, $YPos - 30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ', ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$PDF->addText($XPos, $YPos - 48, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone']);
$PDF->addText($XPos, $YPos - 57, $FontSize, _('Fax') . ': ' . $_SESSION['CompanyRecord']['fax']);
$PDF->addText($XPos, $YPos - 66, $FontSize, $_SESSION['CompanyRecord']['email']);


$XPos = 46;
$YPos = 760;

$FontSize = 12;
$MyRow = array_map(html_entity_decode, $MyRow);
$PDF->addText($XPos, $YPos + 10, $FontSize, _('Delivery To') . ':');
$PDF->addText($XPos, $YPos - 3, $FontSize, $MyRow['deliverto']);
$PDF->addText($XPos, $YPos - 15, $FontSize, $MyRow['deladd1']);
$PDF->addText($XPos, $YPos - 30, $FontSize, $MyRow['deladd2']);
$PDF->addText($XPos, $YPos - 45, $FontSize, $MyRow['deladd3'] . ', ' . $MyRow['deladd4'] . ' ' . $MyRow['deladd5']);

$YPos -= 80;

$PDF->addText($XPos, $YPos, $FontSize, _('Bill To') . ':');
$PDF->addText($XPos, $YPos - 15, $FontSize, $MyRow['name']);
$PDF->addText($XPos, $YPos - 30, $FontSize, $MyRow['address1']);
$PDF->addText($XPos, $YPos - 45, $FontSize, $MyRow['address2']);
$PDF->addText($XPos, $YPos - 60, $FontSize, $MyRow['address3'] . ', ' . $MyRow['address4'] . ' ' . $MyRow['address5']);


$XPos = 50;
$YPos += 25;
/*draw a nice curved corner box around the delivery details */
/*from the top right */
$PDF->partEllipse($XPos + 200, $YPos + 60, 0, 90, 10, 10);
/*line to the top left */
$PDF->line($XPos + 200, $YPos + 70, $XPos, $YPos + 70);
/*Dow top left corner */
$PDF->partEllipse($XPos, $YPos + 60, 90, 180, 10, 10);
/*Do a line to the bottom left corner */
$PDF->line($XPos - 10, $YPos + 60, $XPos - 10, $YPos);
/*Now do the bottom left corner 180 - 270 coming back west*/
$PDF->partEllipse($XPos, $YPos, 180, 270, 10, 10);
/*Now a line to the bottom right */
$PDF->line($XPos, $YPos - 10, $XPos + 200, $YPos - 10);
/*Now do the bottom right corner */
$PDF->partEllipse($XPos + 200, $YPos, 270, 360, 10, 10);
/*Finally join up to the top right corner where started */
$PDF->line($XPos + 210, $YPos, $XPos + 210, $YPos + 60);


$YPos -= 90;
/*draw a nice curved corner box around the billing details */
/*from the top right */
$PDF->partEllipse($XPos + 200, $YPos + 60, 0, 90, 10, 10);
/*line to the top left */
$PDF->line($XPos + 200, $YPos + 70, $XPos, $YPos + 70);
/*Dow top left corner */
$PDF->partEllipse($XPos, $YPos + 60, 90, 180, 10, 10);
/*Do a line to the bottom left corner */
$PDF->line($XPos - 10, $YPos + 60, $XPos - 10, $YPos);
/*Now do the bottom left corner 180 - 270 coming back west*/
$PDF->partEllipse($XPos, $YPos, 180, 270, 10, 10);
/*Now a line to the bottom right */
$PDF->line($XPos, $YPos - 10, $XPos + 200, $YPos - 10);
/*Now do the bottom right corner */
$PDF->partEllipse($XPos + 200, $YPos, 270, 360, 10, 10);
/*Finally join up to the top right corner where started */
$PDF->line($XPos + 210, $YPos, $XPos + 210, $YPos + 60);

$PDF->addTextWrap($Page_Width - $Right_Margin - 200, $Page_Height - $Top_Margin - $FontSize * 1, 200, $FontSize, _('Order Number') . ': ' . $_GET['AcknowledgementNo'], 'right');
$PDF->addTextWrap($Page_Width - $Right_Margin - 200, $Page_Height - $Top_Margin - $FontSize * 2, 200, $FontSize, _('Customer P/O') . ': ' . $MyRow['customerref'], 'right');
$PDF->addTextWrap($Page_Width - $Right_Margin - 200, $Page_Height - $Top_Margin - $FontSize * 3, 200, $FontSize, _('Date') . ': ' . ConvertSQLDate($MyRow['orddate']), 'right');

$PDF->addText($Page_Width / 2 - 10, $YPos + 15, $FontSize, _('All amounts stated in') . ' - ' . $MyRow['currcode']);

$YPos -= 45;
$XPos = 40;

$FontSize = 10;
$LeftOvers = $PDF->addTextWrap($XPos + 2, $YPos, 100, $FontSize, _('Item Code'), 'left');
$LeftOvers = $PDF->addTextWrap(120, $YPos, 235, $FontSize, _('Item Description'), 'left');
$LeftOvers = $PDF->addTextWrap(270, $YPos, 85, $FontSize, _('Ship Date'), 'right');
$LeftOvers = $PDF->addTextWrap(340, $YPos, 85, $FontSize, _('Quantity'), 'right');
$LeftOvers = $PDF->addTextWrap(420, $YPos, 85, $FontSize, _('Units'), 'left');
$LeftOvers = $PDF->addTextWrap(420, $YPos, 85, $FontSize, _('Price'), 'right');
$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 90, $YPos, 90, $FontSize, _('Total'), 'right');


/*draw a box with nice round corner for entering line items */
/*90 degree arc at top right of box 0 degrees starts a bottom */
$PDF->partEllipse($Page_Width - $Right_Margin - 0, $Bottom_Margin + 540, 0, 90, 10, 10);
/*line to the top left */
$PDF->line($Page_Width - $Right_Margin - 0, $Bottom_Margin + 550, $Left_Margin + 10, $Bottom_Margin + 550);

/*line under headings to top left */
$PDF->line($Page_Width - $Right_Margin + 10, $Bottom_Margin + 525, $Left_Margin, $Bottom_Margin + 525);


/*Dow top left corner */
$PDF->partEllipse($Left_Margin + 10, $Bottom_Margin + 540, 90, 180, 10, 10);
/*Do a line to the bottom left corner */
$PDF->line($Left_Margin, $Bottom_Margin + 540, $Left_Margin, $Bottom_Margin + 10);
/*Now do the bottom left corner 180 - 270 coming back west*/
$PDF->partEllipse($Left_Margin + 10, $Bottom_Margin + 10, 180, 270, 10, 10);
/*Now a line to the bottom right */
$PDF->line($Left_Margin + 10, $Bottom_Margin, $Page_Width - $Right_Margin - 0, $Bottom_Margin);
/*Now do the bottom right corner */
$PDF->partEllipse($Page_Width - $Right_Margin - 0, $Bottom_Margin + 10, 270, 360, 10, 10);
/*Finally join up to the top right corner where started */
$PDF->line($Page_Width - $Right_Margin + 10, $Bottom_Margin + 10, $Page_Width - $Right_Margin + 10, $Bottom_Margin + 540);

$YPos -= $line_height * 2;

$FontSize = 12;

?>