<?php
/* pdf-php by R&OS code to set up a new sales order page */

/*	Please note that addTextWrap prints a font-size-height further down than
	addText and other functions.*/

// $PageNumber is initialised in 0 by includes/PDFStarter.php.
$PageNumber ++;// Increments $PageNumber before printing.
if($PageNumber>1) {// Inserts a page break if it is not the first page.
	$pdf->newPage();
}

// Prints company logo:
/*$pdf->addJpegFromFile($_SESSION['LogoFile'], 301, 520, 0, 60);// Old function. See ~/includes/class.pdf.php.*/
$pdf->Image(
	$_SESSION['LogoFile'],// Name of the file containing the image.
	301,// Abscissa from left border to the upper-left corner (LTR).
	$Page_Height -(520) -(60),// Ordinate from top border to the upper-left corner (LTR).
	0,// Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	60,// Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	''// Image format. If not specified, the type is inferred from the file extension.
);// Public function Image() in ~/includes/tcpdf/tcpdf.php.

// Prints 'Quotation' title:
$pdf->addTextWrap(0, $Page_Height-$Top_Margin-18, $Page_Width, 18, _('Quotation'), 'center');

// Prints quotation info:
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*1, 200, $FontSize, _('Number'). ': '.$_GET['QuotationNo'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*2, 200, $FontSize, _('Your Ref'). ': '.$myrow['customerref'], 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*3, 200, $FontSize, _('Date'). ': '.ConvertSQLDate($myrow['quotedate']), 'right');
$pdf->addTextWrap($Page_Width-$Right_Margin-200, $Page_Height-$Top_Margin-$FontSize*4, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Prints company info:
$XPos = $Page_Width/2+$Left_Margin;
$YPos = 512;
PrintOurCompanyInfo($pdf,$_SESSION['CompanyRecord'],$XPos,$YPos);


// Prints 'Delivery To' info:
$XPos = 46;
$YPos = 566;
PrintDeliverTo($pdf,$myrow,_('Delivery To'),$XPos,$YPos);


// Prints 'Quotation For' info:
$YPos -= 82;
PrintCompanyTo($pdf,$myrow,_('Quotation For'),$XPos,$YPos);


$YPos -= 75;
$FontSize=10;

// Prints the currency name:
require_once('includes/CurrenciesArray.php');// To get the currency name from the currency code.
$pdf->addText($Page_Width/2+$Left_Margin, $YPos-5, $FontSize,
	_('All amounts stated in') . ' ' . $myrow['currcode'] . ' - ' . $CurrencyName[$myrow['currcode']]);

// Prints table header:
$XPos = 40;
$YPos -= 37;
$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos,103, $FontSize, _('Item Code'));
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 103, prints remainder
		$LeftOvers = $pdf->addTextWrap($Left_Margin, $YPos-$FontSize, 65, $FontSize, $LeftOvers);
	}
$LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, _('Item Description'));
$LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, _('Quantity'),'right');
$LeftOvers = $pdf->addTextWrap(485, $YPos, 85, $FontSize, _('Price'),'right');
$LeftOvers = $pdf->addTextWrap(535, $YPos, 85, $FontSize, _('Discount'),'right');
$LeftOvers = $pdf->addTextWrap(615, $YPos, 55, $FontSize, _('Tax Class'),'right');
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 55, prints remainder
		$LeftOvers = $pdf->addTextWrap(615,$YPos-$FontSize,55, $FontSize, $LeftOvers,'right');
	}
$LeftOvers = $pdf->addTextWrap(665, $YPos, 70, $FontSize, _('Tax Amount'),'right');
	if(strlen($LeftOvers) > 0) { // If translated text is greater than 70, prints remainder
		$LeftOvers = $pdf->addTextWrap(665, $YPos-$FontSize, 70, $FontSize, $LeftOvers,'right');
	}
$LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-90, $YPos, 90, $FontSize, _('Total'),'right');

// Draws a box with round corners around line items:
$pdf->RoundRectangle(
	$Left_Margin,// RoundRectangle $XPos.
	$YPos+$FontSize+5,// RoundRectangle $YPos.
	$Page_Width-$Left_Margin-$Right_Margin,// RoundRectangle $Width.
	$YPos+$FontSize-$Bottom_Margin+5,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

// Line under table headings:
$LineYPos = $YPos - $FontSize -1;
$pdf->line($Page_Width-$Right_Margin, $LineYPos, $Left_Margin, $LineYPos);

$YPos -= $FontSize;// This is to use addTextWrap's $YPos instead of normal $YPos.

?>
