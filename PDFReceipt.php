<?php

include('includes/session.php');

include('includes/PDFStarter.php');

$FontSize=10;
$PDF->addInfo('Title', _('Sales Receipt') );

$PageNumber=1;
$line_height=12;
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;
$XPos=0;

/* Prints company logo */
$PDF->addJpegFromFile($_SESSION['LogoFile'], $XPos+20, $YPos-50, 0, 60);

/* Prints company info */
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*6),300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*7),300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*8),300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*9),300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*10),300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*11),300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$LeftOvers = $PDF->addTextWrap(50,$YPos-($line_height*12),300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);

$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-180,$YPos-($line_height*3),550,$FontSize, _('Customer Receipt Number ').'  : ' . $_GET['BatchNumber'] .'/'.$_GET['ReceiptNumber'] );
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-180,$YPos-($line_height*4.5),140,$FontSize, _('Printed').': ' . Date($_SESSION['DefaultDateFormat']) . '   '. _('Page'). ' ' . $PageNumber);

$YPos -= 150;

$YPos -=$line_height;
//Note, this is ok for multilang as this is the value of a Select, text in option is different

$YPos -=(2*$line_height);

/*Draw a rectangle to put the headings in     */

$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);

$FontSize=10;
$YPos -= (1.5 * $line_height);

$PageNumber++;

$sql="SELECT MIN(id) as start FROM debtortrans WHERE type=12 AND transno='". $_GET['BatchNumber']. "'";
$result=DB_query($sql);
$myrow=DB_fetch_array($result);
$StartReceiptNumber=$myrow['start'];

$sql="SELECT debtorno,
			ovamount,
			invtext
		FROM debtortrans
		WHERE type=12
		AND transno='" . $_GET['BatchNumber'] . "'
		AND id='". ($StartReceiptNumber-1+$_GET['ReceiptNumber']) ."'";
$result = DB_query($sql);
$myrow = DB_fetch_array($result);
$DebtorNo = $myrow['debtorno'];
$Amount = $myrow['ovamount'];
$Narrative = $myrow['invtext'];

$sql = "SELECT 	currabrev,
				decimalplaces
			FROM currencies
			WHERE currabrev=(SELECT currcode
				FROM banktrans
				WHERE type=12
				AND transno='" . $_GET['BatchNumber']."')";
$result=DB_query($sql);
$myrow=DB_fetch_array($result);
$CurrencyCode=$myrow['currabrev'];
$DecimalPlaces=$myrow['decimalplaces'];

$sql="SELECT name,
             address1,
			 address2,
			 address3,
			 address4,
			 address5,
			 address6
		FROM debtorsmaster
		WHERE debtorno='".$DebtorNo."'";

$result=DB_query($sql);
$myrow=DB_fetch_array($result);

/* Prints customer info */
$LeftOvers = $PDF->addTextWrap(50,$YPos,300,$FontSize,_('Received From').' :');
$LeftOvers = $PDF->addTextWrap(150,$YPos,300,$FontSize, htmlspecialchars_decode($myrow['name']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*1),300,$FontSize, htmlspecialchars_decode($myrow['address1']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*2),300,$FontSize, htmlspecialchars_decode($myrow['address2']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*3),300,$FontSize, htmlspecialchars_decode($myrow['address3']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*4),300,$FontSize, htmlspecialchars_decode($myrow['address4']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*5),300,$FontSize, htmlspecialchars_decode($myrow['address5']));
$LeftOvers = $PDF->addTextWrap(150,$YPos-($line_height*6),300,$FontSize, htmlspecialchars_decode($myrow['address6']));

$YPos=$YPos-($line_height*8);

$LeftOvers = $PDF->addTextWrap(50,$YPos,300,$FontSize, _('The Sum Of').' :');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$LeftOvers = $PDF->addTextWrap(150,$YPos,300,$FontSize, locale_number_format(-$Amount,$DecimalPlaces).' '. $CurrencyCode . '-' . $CurrencyName[$CurrencyCode]);

$YPos=$YPos-($line_height*2);

$LeftOvers = $PDF->addTextWrap(50,$YPos,500,$FontSize, _('Details').' :');
$LeftOvers = $PDF->addTextWrap(150,$YPos,500,$FontSize, $Narrative);

$YPos=$YPos-($line_height*8);

$LeftOvers = $PDF->addTextWrap(50,$YPos,500,$FontSize,_('Signed On Behalf Of').' :     '.$_SESSION['CompanyRecord']['coyname']);

$YPos=$YPos-($line_height*10);

$LeftOvers = $PDF->addTextWrap(50,$YPos,300,$FontSize,'______________________________________________________________________________');

$PDF->Output('Receipt-'.$_GET['ReceiptNumber'], 'I');
?>