<?php

/*	Please note that addTextWrap() prints a font-size-height further down than
	addText() and other functions. Use addText() instead of addTextWrap() to
	print left aligned elements.*/

if(!$FirstPage) { /* only initiate a new page if its not the first */
	$PDF->newPage();
}

$YPos = $Page_Height-$Top_Margin;

// Company Logo:
/*$PDF->addJpegFromFile($_SESSION['LogoFile'], $Page_Width/2-118, $YPos-60, 0, 35);*/
$PDF->Image(
	$_SESSION['LogoFile'],// Name of the file containing the image.
	$Page_Width/2-118,// Abscissa from left border to the upper-left corner (LTR).
	$Page_Height -($YPos-60) -(35),// Ordinate from top border to the upper-left corner (LTR).
	0,// Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	35,// Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	''// Image format. If not specified, the type is inferred from the file extension.
);// Public function Image() in ~/includes/tcpdf/tcpdf.php.

$FontSize =15;
if($InvOrCredit=='Invoice') {

	$PDF->addText($Page_Width/2 - 60, $YPos, $FontSize, _('TAX INVOICE') . ' ');
} else {
	$PDF->addText($Page_Width/2 - 60, $YPos, $FontSize, _('TAX CREDIT NOTE') . ' ');
}

// Prints page number:
$FontSize = 10;
$YPos -= $FontSize; //Downs one line height mesure (addText position is from left-bottom).
$PDF->addTextWrap($Page_Width-$Left_Margin-72, $YPos, 72, $FontSize, _('Page') . ' ' . $PageNumber, 'right');


$XPos = $Page_Width - 265;
$YPos -= 85;
// Draws a rounded rectangle around billing details:
$PDF->RoundRectangle(
	$XPos-10,// RoundRectangle $XPos.
	$YPos+77,// RoundRectangle $YPos.
	245,// RoundRectangle $Width.
	97,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

$YPos = $Page_Height - $Top_Margin - 10;

$FontSize = 10;
$LineHeight = 13;
$LineCount = 1;
$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Number'));
$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, $FromTransNo);
$LineCount += 1;
$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Customer Code'));
$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, $myrow['debtorno'] . ' ' . _('Branch') . ' ' . $myrow['branchcode']);
$LineCount += 1;
$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Date'));
$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, ConvertSQLDate($myrow['trandate']));

if($InvOrCredit=='Invoice') {
	$LineCount += 1;
	$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Order No'));
	$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, $myrow['orderno']);
	$LineCount += 1;
	$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Order Date'));
	$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, ConvertSQLDate($myrow['orddate']));
	$LineCount += 1;
	$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Dispatch Detail'));
	$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, $myrow['shippername'] . '-' . $myrow['consignment']);
	$LineCount += 1;
	$PDF->addText($Page_Width-268, $YPos-$LineCount*$LineHeight, $FontSize, _('Dispatched From'));
	$PDF->addText($Page_Width-180, $YPos-$LineCount*$LineHeight, $FontSize, $myrow['locationname']);
}

/*End of the text in the right side box */

/*Now print out company info at the top left */

$XPos = $Left_Margin;
$YPos = $Page_Height - $Top_Margin - 20;

$FontSize = 10;
$LineHeight = 13;
$LineCount = 0;

$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $_SESSION['CompanyRecord']['coyname']);

$FontSize = 8;
$LineHeight = 10;

if($_SESSION['CompanyRecord']['regoffice1'] <> '') {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
}
if($_SESSION['CompanyRecord']['regoffice2'] <> '') {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
}
if(($_SESSION['CompanyRecord']['regoffice3'] <> '') OR ($_SESSION['CompanyRecord']['regoffice4'] <> '') OR ($_SESSION['CompanyRecord']['regoffice5'] <> '')) {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . '  ' . $_SESSION['CompanyRecord']['regoffice4'] . '  ' . $_SESSION['CompanyRecord']['regoffice5']);  // country in 6 not printed
}
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Phone') . ':' . $_SESSION['CompanyRecord']['telephone']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, _('Fax') . ': ' . $_SESSION['CompanyRecord']['fax']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Email') . ': ' . $_SESSION['CompanyRecord']['email']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $_SESSION['TaxAuthorityReferenceName'] . ': ' . $_SESSION['CompanyRecord']['gstno']);

/*Now the customer company info */

$XPos = $Left_Margin;
$YPos = $Page_Height - $Top_Margin - 120;

$XPos += 20;
$FontSize = 10;
$LineHeight = 13;
$LineCount = 0;

if($myrow['invaddrbranch']==0) {
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['name']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['address1']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['address2']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['address3']) . '  ' . html_entity_decode($myrow['address4'])  . '  ' . html_entity_decode($myrow['address5'])  . ' ' . html_entity_decode($myrow['address6']));
} else {
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['name']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['brpostaddr1']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['brpostaddr2']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['brpostaddr3']) . '  ' . html_entity_decode($myrow['brpostaddr4']) . '  ' . html_entity_decode($myrow['brpostaddr5']) . ' ' . html_entity_decode($myrow['brpostaddr6']));
}

$XPos = $Page_Width - 265;
$YPos = $Page_Height - $Top_Margin - 120;

$FontSize = 8;
$LineHeight = 10;
$LineCount = 0;

$PDF->addText($Left_Margin, $YPos, $FontSize, _('Sold To') . ':');

if($InvOrCredit=='Invoice') {
	$PDF->addText($XPos, $YPos, $FontSize, _('Delivered To (check Dispatch Detail)') . ':');
	$FontSize = 10;
	$LineHeight = 13;
	$XPos += 20;
	$LineCount += 1;

    // Before trying to call htmlspecialchars_decode, check that its supported, if not substitute a compatible version
    if(!function_exists('htmlspecialchars_decode')) {
        function htmlspecialchars_decode($str) {
                $trans = get_html_translation_table(HTML_SPECIALCHARS);

                $decode = ARRAY();
                foreach ($trans AS $char=>$entity) {
                        $decode[$entity] = $char;
                }

                $str = strtr($str, $decode);

                return $str;
        }
    }

	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['deliverto']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['deladd1']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['deladd2']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['deladd3']) . '  ' . html_entity_decode($myrow['deladd4']) . '  ' . html_entity_decode($myrow['deladd5']) . ' ' . html_entity_decode($myrow['deladd6']));
}
else {
/* then its a credit note */
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Charge Branch') . ':');
	$FontSize = 10;
	$LineHeight = 13;
	$XPos +=20;
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['brname']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['braddress1']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['braddress2']));
	$LineCount += 1;
	$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, html_entity_decode($myrow['braddress3']) . '  ' . html_entity_decode($myrow['braddress4']) . '  ' . html_entity_decode($myrow['braddress5']) . ' ' . html_entity_decode($myrow['braddress6']));
}

$XPos = $Left_Margin;
$YPos = $Page_Height - $Top_Margin - 190;
$FontSize = 8;

require_once('includes/CurrenciesArray.php');// To get the currency name from the currency code.
$PDF->addText($Left_Margin, $YPos-8, $FontSize, _('All amounts stated in') . ': ' . $myrow['currcode'] . ' - ' . $CurrencyName[$myrow['currcode']]);

if ($InvOrCredit=='Invoice') {
	$PDF->addText($Page_Width-$Left_Margin-70, $YPos-8, $FontSize, _('Due Date') . ': ' . $DisplayDueDate);
}

$BoxHeight = $Page_Height-282;

// Draws a rounded rectangle around line items:
$PDF->RoundRectangle(
	$Left_Margin,// RoundRectangle $XPos.
	$Bottom_Margin+$BoxHeight+10,// RoundRectangle $YPos.
	$Page_Width-$Right_Margin-$Left_Margin,// RoundRectangle $Width.
	$BoxHeight+10,// RoundRectangle $Height.
	10,// RoundRectangle $RadiusX.
	10);// RoundRectangle $RadiusY.

$YPos -= 35;
/*Set up headings */
$FontSize=10;
$LineHeight = 12;

$PDF->addText($Left_Margin+2, $YPos+$LineHeight, $FontSize, _('Cust. Tax Ref') . ':');
/*Print a vertical line */
$PDF->line($Left_Margin+178, $YPos+$LineHeight,$Left_Margin+178, $YPos-$LineHeight*2+4);
$PDF->addText($Left_Margin+180, $YPos+$LineHeight, $FontSize, _('Cust. Reference No.') . ':');
/*Print a vertical line */
$PDF->line($Left_Margin+358, $YPos+$LineHeight,$Left_Margin+358, $YPos-$LineHeight*2+4);
$PDF->addText($Left_Margin+360, $YPos+$LineHeight, $FontSize, _('Sales Person') . ':');
$PDF->addText($Left_Margin+12, $YPos, $FontSize, $myrow['taxref']);
if($InvOrCredit=='Invoice') {
	$PDF->addText($Left_Margin+190, $YPos, $FontSize, $myrow['customerref']);
}
$PDF->addText($Left_Margin+370, $YPos, $FontSize, $myrow['salesmanname']);

$YPos -= 20;

/*draw a line */
$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$TopOfColHeadings = $YPos;

$PDF->addText($Left_Margin, $YPos, $FontSize, _('Item Code'));
$PDF->addText($Left_Margin+80, $YPos, $FontSize, _('Description'));
$PDF->addText($Left_Margin+270, $YPos, $FontSize, _('Unit Price'));
$PDF->addText($Left_Margin+350, $YPos, $FontSize, _('Qty'));
$PDF->addText($Left_Margin+390, $YPos, $FontSize, _('UOM'));
$PDF->addText($Left_Margin+420, $YPos, $FontSize, _('Disc.'));
$PDF->addText($Left_Margin+450, $YPos, $FontSize, _('Price'));

$YPos -= 12;

/*draw a line */
$PDF->line($XPos, $YPos-1,$Page_Width-$Right_Margin, $YPos-1);

$YPos -= ($line_height);

?>
