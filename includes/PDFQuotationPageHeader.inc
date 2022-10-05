<?php
/* pdf-php by R&OS code to set up a new sales order page */

/*	Please note that addTextWrap prints a font-size-height further down than
	addText and other functions.*/

// $PageNumber is initialised in 0 by includes/PDFStarter.php.
$PageNumber ++;// Increments $PageNumber before printing.
if($PageNumber>1) {// Inserts a page break if it is not the first page.
	$PDF->newPage();
}

// Prints company logo:
/*$PDF->addJpegFromFile($_SESSION['LogoFile'], 301, 520, 0, 60);// Old function. See ~/includes/class.pdf.php.*/
$PDF->Image(
	$_SESSION['LogoFile'],// Name of the file containing the image.
	301,// Abscissa from left border to the upper-left corner (LTR).
	$Page_Height -(520) -(60),// Ordinate from top border to the upper-left corner (LTR).
	0,// Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	60,// Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	''// Image format. If not specified, the type is inferred from the file extension.
);// Public function Image() in ~/includes/tcpdf/tcpdf.php.

// Prints 'Quotation' title:
$PDF->addTextWrap(0, $Page_Height-$Top_Margin-18, $Page_Width, 18, _('Quotation'), 'center');

// Prints quotation info:
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*1, 200, $FontSize, _('Number'). ': '.$_GET['QuotationNo'], 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, _('Your Ref'). ': '.$myrow['customerref'], 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date'). ': '.ConvertSQLDate($myrow['quotedate']), 'right');
$PDF->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Prints company info:
$XPos = $Page_Width/2+$Left_Margin;
$YPos = 512;
$FontSize = 14;
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
$YPos = 566;
$FontSize=14;
$myrow = array_map('html_entity_decode', $myrow);
$line_height = 15;
$PDF->addText($XPos, $YPos, $FontSize, _('Delivery To').':' );
$PDF->addText($XPos, $YPos-$line_height*1, $FontSize, $myrow['deliverto']);
$PDF->addText($XPos, $YPos-$line_height*2, $FontSize, $myrow['deladd1']);
$PDF->addText($XPos, $YPos-$line_height*3, $FontSize, $myrow['deladd2']);
$PDF->addText($XPos, $YPos-$line_height*4, $FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5']);

// Prints 'Quotation For' info:
$YPos -= 80;
$line_height = 15;
$PDF->addText($XPos, $YPos, $FontSize, _('Quotation For').':');
$PDF->addText($XPos, $YPos-$line_height*1, $FontSize, $myrow['name']);
$PDF->addText($XPos, $YPos-$line_height*2, $FontSize, $myrow['address1']);
$PDF->addText($XPos, $YPos-$line_height*3, $FontSize, $myrow['address2']);
$PDF->addText($XPos, $YPos-$line_height*4, $FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']);

// Draws a box with round corners around 'Delivery To' info:
$XPos= 50;
$YPos += 12;
$PDF->RoundRectangle(
	$XPos-10,// RoundRectangle $XPos.
	$YPos+70,// RoundRectangle $YPos.
	245,// RoundRectangle $Width.
	80,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

// Draws a box with round corners around around 'Quotation For' info:
$YPos -= 82;
$PDF->RoundRectangle(
	$XPos-10,// RoundRectangle $XPos.
	$YPos+70,// RoundRectangle $YPos.
	245,// RoundRectangle $Width.
	80,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

$FontSize=10;

// Prints the currency name:
require_once('includes/CurrenciesArray.php');// To get the currency name from the currency code.
$PDF->addText($Page_Width/2+$Left_Margin, $YPos-5, $FontSize,
	_('All amounts stated in') . ' ' . $myrow['currcode'] . ' - ' . $CurrencyName[$myrow['currcode']]);

// Prints table header:
$XPos = 40;
$YPos -= 37;
$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos,103, $FontSize, _('Item Code'));
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos-$FontSize, 65, $FontSize, $LeftOvers);
	}
$LeftOvers = $PDF->addTextWrap(145, $YPos,250, $FontSize, _('Item Description'));
$LeftOvers = $PDF->addTextWrap(420, $YPos, 85, $FontSize, _('Quantity'),'right');
$LeftOvers = $PDF->addTextWrap(485, $YPos, 85, $FontSize, _('Price'),'right');
$LeftOvers = $PDF->addTextWrap(535, $YPos, 85, $FontSize, _('Discount'),'right');
$LeftOvers = $PDF->addTextWrap(615, $YPos, 55, $FontSize, _('Tax Class'),'right');
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 55, prints remainder
		$LeftOvers = $PDF->addTextWrap(615,$YPos-$FontSize,55, $FontSize, $LeftOvers,'right');
	}
$LeftOvers = $PDF->addTextWrap(665, $YPos, 70, $FontSize, _('Tax Amount'),'right');
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 70, prints remainder
		$LeftOvers = $PDF->addTextWrap(665, $YPos-$FontSize, 70, $FontSize, $LeftOvers,'right');
	}
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
