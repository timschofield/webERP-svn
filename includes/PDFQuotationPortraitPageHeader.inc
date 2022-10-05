<?php
/*	Please note that addTextWrap prints a font-size-height further down than
	addText and other functions.*/

// $PageNumber is initialised in 0 by includes/PDFStarter.php.
$PageNumber ++;// Increments $PageNumber before printing.
if ($PageNumber>1) {// Inserts a page break if it is not the first page.
	$PDF->newPage();
}

// Prints company logo:
$XPos = $Page_Width/2 - 140;
$PDF->addJpegFromFile($_SESSION['LogoFile'],$XPos+90,720,0,60);

// Prints 'Quotation' title:
$PDF->addTextWrap(0, $Page_Height-$Top_Margin-18, $Page_Width, 18, _('Quotation'), 'center');

// Prints company info:
$XPos = $Page_Width/2+$Left_Margin;
$YPos = 720;
$FontSize = 12;
$PDF->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['coyname']);
$YPos -= $FontSize;
$FontSize = 10;
$PDF->addText($XPos, $YPos, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$PDF->addText($XPos, $YPos-$FontSize*1, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$PDF->addText($XPos, $YPos-$FontSize*2, $FontSize, $_SESSION['CompanyRecord']['regoffice3']);
$PDF->addText($XPos, $YPos-$FontSize*3, $FontSize, $_SESSION['CompanyRecord']['regoffice4']);
$PDF->addText($XPos, $YPos-$FontSize*4, $FontSize, $_SESSION['CompanyRecord']['regoffice5'] .
	' ' . $_SESSION['CompanyRecord']['regoffice6']);
$PDF->addText($XPos, $YPos-$FontSize*5, $FontSize,  _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] .
	' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$PDF->addText($XPos, $YPos-$FontSize*6, $FontSize, $_SESSION['CompanyRecord']['email']);

// Prints 'Delivery To' info:
$XPos = 46;
$YPos = 770;
$FontSize=12;
$myrow = array_map(html_entity_decode, $myrow);
$PDF->addText($XPos, $YPos+10,$FontSize, _('Delivery To').':' );
$PDF->addText($XPos, $YPos- 3,$FontSize, $myrow['deliverto']);
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['deladd1']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['deladd2']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5']);

// Prints 'Quotation For' info:
$YPos -= 80;
$PDF->addText($XPos, $YPos,$FontSize, _('Quotation For').':');
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['name']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['address1']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['address2']);
$PDF->addText($XPos, $YPos-60,$FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']);

// Draws a box with round corners around 'Delivery To' info:
$XPos = 50;
$YPos += 25;
$PDF->RoundRectangle(
	$XPos-10,// RoundRectangle $XPos.
	$YPos+60+10,// RoundRectangle $YPos.
	200+10+10,// RoundRectangle $Width.
	60+10+10,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

// Draws a box with round corners around around 'Quotation For' info:
$YPos -= 90;
$PDF->RoundRectangle(
	$XPos-10,// RoundRectangle $XPos.
	$YPos+60+10,// RoundRectangle $YPos.
	200+10+10,// RoundRectangle $Width.
	60+10+10,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

// Prints quotation info:
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*1, 200, $FontSize, _('Number'). ': '.$_GET['QuotationNo'], 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, _('Your Ref'). ': '.$myrow['customerref'], 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date'). ': '.ConvertSQLDate($myrow['quotedate']), 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

$FontSize=10;

// Prints the currency name:
require_once('includes/CurrenciesArray.php');// To get the currency name from the currency code.
$PDF->addText($Page_Width/2+$Left_Margin, $YPos+5, $FontSize,
	_('All amounts stated in') . ' ' . $myrow['currcode'] . ' - ' . $CurrencyName[$myrow['currcode']]);

// Prints table header:
$YPos -= 45;
$XPos = 40;
$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,100,$FontSize, _('Item Code'));
$LeftOvers = $PDF->addTextWrap(120,$YPos,235,$FontSize, _('Item Description'));
$LeftOvers = $PDF->addTextWrap(180,$YPos,85,$FontSize, _('Quantity'),'right');
$LeftOvers = $PDF->addTextWrap(230,$YPos,85,$FontSize,_('Price'),'right');
$LeftOvers = $PDF->addTextWrap(280,$YPos,85,$FontSize, _('Discount'),'right');
$LeftOvers = $PDF->addTextWrap(330,$YPos,85,$FontSize, _('Tax Class'),'right');
$LeftOvers = $PDF->addTextWrap(400,$YPos,85,$FontSize, _('Tax Amount'),'right');
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, _('Total'),'right');

// Draws a box with round corners around line items:
$PDF->RoundRectangle(
	$Left_Margin,// RoundRectangle $XPos.
	$YPos+$FontSize+5,// RoundRectangle $YPos.
	$Page_Width-$Left_Margin-$Right_Margin,// RoundRectangle $Width.
	$YPos+$FontSize-$Bottom_Margin+5,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

// Line under table headings:
$LineYPos = $YPos - $FontSize -1;
$PDF->line($Page_Width-$Right_Margin, $LineYPos, $Left_Margin, $LineYPos);

$YPos -= $FontSize;// This is to use addTextWrap's $YPos instead of normal $YPos.

?>
