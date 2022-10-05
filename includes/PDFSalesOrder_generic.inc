<?php
/* pdf-php by R&OS code to set up a new sales order page */
if ($PageNumber>1){
	$PDF->newPage();
}

$XPos = $Page_Width/2 - 60;
/* if the deliver blind flag is set on the order, we do not want to output
the company logo */
if ($DeliverBlind < 2) {
    $PDF->addJpegFromFile($_SESSION['LogoFile'],$XPos+20,520,0,60);
}
$FontSize=18;

if ($Copy=='Customer'){
	$PDF->addText($XPos-20, 500,$FontSize, _('Sales Order') . ' - ' . _('Customer Copy') );
	$PDF->addText($XPos-20, 490,10, _('This is not an invoice') );
} else {
	$PDF->addText($XPos-20, 500,$FontSize, _('Sales Order') . ' - ' . _('Office Copy') );
	$PDF->addText($XPos-20, 490,10, _('This is not an invoice') );
}

/* if the deliver blind flag is set on the order, we do not want to output
the company contact info */
if ($DeliverBlind < 2) {
    $FontSize=14;
    $YPos = 470;
    $PDF->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
    $FontSize =8;
    $PDF->addText($XPos, $YPos-12,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
    $PDF->addText($XPos, $YPos-21,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
    $PDF->addText($XPos, $YPos-30,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
    $PDF->addText($XPos, $YPos-39,$FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
    $PDF->addText($XPos, $YPos-48,$FontSize, $_SESSION['CompanyRecord']['email']);
}

$XPos = 46;
$YPos = 566;

$FontSize=14;
$PDF->addText($XPos, $YPos,$FontSize, _('Delivered To').':' );
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['deliverto']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['deladd1']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['deladd2']);
$PDF->addText($XPos, $YPos-60,$FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5']);

$YPos -= 80;

$PDF->addText($XPos, $YPos,$FontSize, _('Customer').':');
$PDF->addText($XPos, $YPos-15,$FontSize, $myrow['name']);
$PDF->addText($XPos, $YPos-30,$FontSize, $myrow['address1']);
$PDF->addText($XPos, $YPos-45,$FontSize, $myrow['address2']);
$PDF->addText($XPos, $YPos-60,$FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']);


$PDF->addText($XPos, $YPos-82,$FontSize, _('Customer No.'). ' : ' . $myrow['debtorno']);
$PDF->addText($XPos, $YPos-100,$FontSize, _('Shipped by'). ' : ' . $myrow['shippername']);

$LeftOvers = $PDF->addTextWrap($XPos,$YPos-115,170,$FontSize,stripcslashes($myrow['comments']));

if (mb_strlen($LeftOvers)>1){
	$LeftOvers = $PDF->addTextWrap($XPos,$YPos-130,170,$FontSize,$LeftOvers);
	if (mb_strlen($LeftOvers)>1){
		$LeftOvers = $PDF->addTextWrap($XPos,$YPos-145,170,$FontSize,$LeftOvers);
		if (mb_strlen($LeftOvers)>1){
			$LeftOvers = $PDF->addTextWrap($XPos,$YPos-160,170,$FontSize,$LeftOvers);
			if (mb_strlen($LeftOvers)>1){
				$LeftOvers = $PDF->addTextWrap($XPos,$YPos-175,170,$FontSize,$LeftOvers);
			}
		}
	}
}

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
$LeftOvers = $PDF->addTextWrap(503,$YPos,85,$FontSize,_('This Del'),'right');
$LeftOvers = $PDF->addTextWrap(602,$YPos,85,$FontSize, _('Prev Dels'),'right');

$YPos -= $line_height;

$FontSize =12;

?>
