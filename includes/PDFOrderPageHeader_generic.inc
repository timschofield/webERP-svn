<?php
/* pdf-php by R&OS code to set up a new sales order page */
if ($PageNumber>1){
	$PDF->newPage();
}

$XPos = $Page_Width/2 - 60;
/* if the deliver blind flag is set on the order, we do not want to output
the company logo */
if ($DeliverBlind < 2) {
    $PDF->addJpegFromFile($_SESSION['LogoFile'],$XPos,490,0,60);
}
$FontSize=18;

if ($Copy=='Customer'){
	$PDF->addText($XPos-40, 585,$FontSize, _('Packing Slip') . ' - ' . _('Customer Copy') );
} else {
	$PDF->addText($XPos-40, 585,$FontSize, _('Packing Slip') . ' - ' . _('Office Copy') );
}

/* if the deliver blind flag is set on the order, we do not want to output
the company contact info */
if ($DeliverBlind < 2) {
    $FontSize=12;
    $YPos = 480;
    $PDF->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
    $FontSize=10;
    $PDF->addText($XPos, $YPos-14,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
    $PDF->addText($XPos, $YPos-26,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
    $PDF->addText($XPos, $YPos-38,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . '  ' . $_SESSION['CompanyRecord']['regoffice4'] . '  ' . $_SESSION['CompanyRecord']['regoffice5']);
    $PDF->addText($XPos, $YPos-50,$FontSize, $_SESSION['CompanyRecord']['regoffice6']);
    $PDF->addText($XPos, $YPos-62,$FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
    $PDF->addText($XPos, $YPos-74,$FontSize, $_SESSION['CompanyRecord']['email']);
}

$XPos = 46;
$YPos = 566;

$FontSize=14;
$PDF->addText($XPos, $YPos,$FontSize, _('Delivered To').':' );
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['deliverto']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['deladd1']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['deladd2']);
$PDF->addText($XPos, $YPos-60,$FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5'] . ' ' . $myrow['deladd6']);

$YPos -= 80;

$PDF->addText($XPos, $YPos,$FontSize, _('Customer').':');
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['name']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['address1']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['address2']);
$PDF->addText($XPos, $YPos-60,$FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']. ' ' . $myrow['address6']);


$PDF->addText($XPos, $YPos-82,$FontSize, _('Customer No.'). ': ' . $myrow['debtorno']);
$PDF->addText($XPos, $YPos-100,$FontSize, _('Shipped by'). ': ' . $myrow['shippername']);

$FontSize=12;
$LeftOvers = $PDF->addTextWrap($XPos,$YPos-130,250,$FontSize,_('Comments').': '.stripcslashes($myrow['comments']));

if (mb_strlen($LeftOvers)>1){
	$LeftOvers = $PDF->addTextWrap($XPos,$YPos-145,250,$FontSize,$LeftOvers);
	if (mb_strlen($LeftOvers)>1){
		$LeftOvers = $PDF->addTextWrap($XPos,$YPos-160,250,$FontSize,$LeftOvers);
		if (mb_strlen($LeftOvers)>1){
			$LeftOvers = $PDF->addTextWrap($XPos,$YPos-175,250,$FontSize,$LeftOvers);
			if (mb_strlen($LeftOvers)>1){
				$LeftOvers = $PDF->addTextWrap($XPos,$YPos-180,250,$FontSize,$LeftOvers);
			}
		}
	}
}

$FontSize=14;
$PDF->addText(620, 560,$FontSize, _('Order No'). ':');
$PDF->addText(700, 560,$FontSize, $_GET['TransNo']);
$PDF->addText(620, 560-15,$FontSize, _('Your Ref'). ':');
$PDF->addText(700, 560-15,$FontSize, $myrow['customerref']);
$PDF->addText(620, 560-45,$FontSize,  _('Order Date'). ':');
$PDF->addText(700, 560-45,$FontSize,  ConvertSQLDate($myrow['orddate']));
$PDF->addText(620, 560-60,$FontSize,  _('Printed') . ': ');
$PDF->addText(700, 560-60,$FontSize,  Date($_SESSION['DefaultDateFormat']));
$PDF->addText(620, 560-75,$FontSize,  _('From').': ');
$PDF->addText(700, 560-75,$FontSize,  $myrow['locationname']);
$PDF->addText(620, 560-90,$FontSize,  _('Page'). ':');
$PDF->addText(700, 560-90,$FontSize,  $PageNumber);

$YPos -= 170;
$XPos = 15;

$header_line_height = $line_height+25;

$LeftOvers = $PDF->addTextWrap($XPos,$YPos,127,$FontSize, _('Item Code'),'left');
$LeftOvers = $PDF->addTextWrap(147,$YPos,255,$FontSize, _('Item Description'),'left');
$LeftOvers = $PDF->addTextWrap(400,$YPos,85,$FontSize, _('Quantity'),'right');
$LeftOvers = $PDF->addTextWrap(487,$YPos,85,$FontSize, _('Units'),'left');
$LeftOvers = $PDF->addTextWrap(527,$YPos,70,$FontSize, _('Bin Locn'),'left');
$LeftOvers = $PDF->addTextWrap(593,$YPos,85,$FontSize,_('This Del'),'right');
$LeftOvers = $PDF->addTextWrap(692,$YPos,85,$FontSize, _('Prev Dels'),'right');

$YPos -= $line_height;

$FontSize =12;

?>
