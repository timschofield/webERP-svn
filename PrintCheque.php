<?php


/*Hard coded for currencies with 2 decimal places */

include('includes/DefinePaymentClass.php');
include('includes/session.php');

if (isset($_GET['identifier'])){
	$identifier = $_GET['identifier'];
}else{
	prnMsg(_('Something was wrong without an identifier, please ask administrator for help'),'error');
	include('includes/footer.php');
	exit;
}
include('includes/PDFStarter.php');
$PDF->addInfo('Title', _('Print Cheque'));
$PDF->addInfo('Subject', _('Print Cheque'));
$FontSize=10;
$PageNumber=1;
$line_height=12;

$result = DB_query("SELECT hundredsname,
                           decimalplaces,
                           currency
                    FROM currencies
                    WHERE currabrev='" . $_SESSION['PaymentDetail' . $identifier]->Currency . "'");

If (DB_num_rows($result) == 0){
	include ('includes/header.php');
	prnMsg(_('Can not get hundreds name'), 'warn');
	include ('includes/footer.php');
	exit;
}

$CurrencyRow = DB_fetch_array($result);
$HundredsName = $CurrencyRow['hundredsname'];
$CurrDecimalPlaces = $CurrencyRow['decimalplaces'];
$CurrencyName = mb_strtolower($CurrencyRow['currency']);

// cheque
$YPos= $Page_Height-5*$line_height;
$LeftOvers = $PDF->addTextWrap($Page_Width-75,$YPos,100,$FontSize,$_GET['ChequeNum'], 'left');
$YPos -= 3*$line_height;

$AmountWords = number_to_words($_SESSION['PaymentDetail' . $identifier]->Amount) . ' ' . $CurrencyName;
$Cents = intval(round(($_SESSION['PaymentDetail' . $identifier]->Amount - intval($_SESSION['PaymentDetail' . $identifier]->Amount))*100,0));
if ($Cents > 0){
	$AmountWords .= ' ' . _('and') . ' ' .  strval($Cents) . ' ' . $HundredsName;
} else {
	$AmountWords .= ' ' . _('only');
}

$LeftOvers = $PDF->addTextWrap(75,$YPos,475,$FontSize,$AmountWords, 'left');
$YPos -= 1*$line_height;
$LeftOvers = $PDF->addTextWrap($Page_Width-225,$YPos,100,$FontSize,$_SESSION['PaymentDetail' . $identifier]->DatePaid, 'left');
$LeftOvers = $PDF->addTextWrap($Page_Width-75,$YPos,75,$FontSize,locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount,$CurrDecimalPlaces), 'left');

$YPos -= 1*$line_height;
$LeftOvers = $PDF->addTextWrap(75,$YPos,300,$FontSize,$_SESSION['PaymentDetail' . $identifier]->SuppName, 'left');
$YPos -= 1*$line_height;
$LeftOvers = $PDF->addTextWrap(75,$YPos,300,$FontSize,$_SESSION['PaymentDetail' . $identifier]->Address1, 'left');
$YPos -= 1*$line_height;
$LeftOvers = $PDF->addTextWrap(75,$YPos,300,$FontSize,$_SESSION['PaymentDetail' . $identifier]->Address2, 'left');
$YPos -= 1*$line_height;
$Address3 = $_SESSION['PaymentDetail' . $identifier]->Address3 . ' ' . $_SESSION['PaymentDetail' . $identifier]->Address4 . ' ' . $_SESSION['PaymentDetail' . $identifier]->Address5 . ' ' . $_SESSION['PaymentDetail' . $identifier]->Address6;
$LeftOvers = $PDF->addTextWrap(75,$YPos,300,$FontSize, $Address3, 'left');


$YPos -= 2*$line_height;
$LeftOvers = $PDF->addTextWrap(75,$YPos,300,$FontSize, $AmountWords, 'left');
$LeftOvers = $PDF->addTextWrap(375,$YPos,100,$FontSize, locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount,$CurrDecimalPlaces), 'right');


// remittance advice 1
$YPos -= 14*$line_height;
$LeftOvers = $PDF->addTextWrap(0,$YPos,$Page_Width,$FontSize,_('Remittance Advice'), 'center');
$YPos -= 2*$line_height;
$LeftOvers = $PDF->addTextWrap(25,$YPos,75,$FontSize,_('DatePaid'), 'left');
$LeftOvers = $PDF->addTextWrap(100,$YPos,100,$FontSize,_('Vendor No.'), 'left');
$LeftOvers = $PDF->addTextWrap(250,$YPos,75,$FontSize,_('Cheque No.'), 'left');
$LeftOvers = $PDF->addTextWrap(350,$YPos,75,$FontSize,_('Amount'), 'left');
$YPos -= 2*$line_height;
$LeftOvers = $PDF->addTextWrap(25,$YPos,75,$FontSize,$_SESSION['PaymentDetail' . $identifier]->DatePaid, 'left');
$LeftOvers = $PDF->addTextWrap(100,$YPos,100,$FontSize,$_SESSION['PaymentDetail' . $identifier]->SupplierID, 'left');
$LeftOvers = $PDF->addTextWrap(250,$YPos,75,$FontSize,$_GET['ChequeNum'], 'left');
$LeftOvers = $PDF->addTextWrap(350,$YPos,75,$FontSize,locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount,$CurrDecimalPlaces), 'left');

// remittance advice 2
$YPos -= 15*$line_height;
$LeftOvers = $PDF->addTextWrap(0,$YPos,$Page_Width,$FontSize,_('Remittance Advice'), 'center');
$YPos -= 2*$line_height;
$LeftOvers = $PDF->addTextWrap(25,$YPos,75,$FontSize,_('DatePaid'), 'left');
$LeftOvers = $PDF->addTextWrap(100,$YPos,100,$FontSize,_('Vendor No.'), 'left');
$LeftOvers = $PDF->addTextWrap(250,$YPos,75,$FontSize,_('Cheque No.'), 'left');
$LeftOvers = $PDF->addTextWrap(350,$YPos,75,$FontSize,_('Amount'), 'left');
$YPos -= 2*$line_height;
$LeftOvers = $PDF->addTextWrap(25,$YPos,75,$FontSize,$_SESSION['PaymentDetail' . $identifier]->DatePaid, 'left');
$LeftOvers = $PDF->addTextWrap(100,$YPos,100,$FontSize,$_SESSION['PaymentDetail' . $identifier]->SupplierID, 'left');
$LeftOvers = $PDF->addTextWrap(250,$YPos,75,$FontSize,$_GET['ChequeNum'], 'left');
$LeftOvers = $PDF->addTextWrap(350,$YPos,75,$FontSize,locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount,$CurrDecimalPlaces), 'left');

$PDF->OutputD($_SESSION['DatabaseName'] . '_Cheque_' . date('Y-m-d') . '_ChequeNum_' . $_GET['ChequeNum'] . '.pdf');
$PDF->__destruct();

exit;
/* ****************************************************************************************** */

function number_to_words($Number) {

    if (($Number < 0) OR ($Number > 999999999)) {
		prnMsg(_('Number is out of the range of numbers that can be expressed in words'),'error');
		return _('error');
    }

	$Millions = floor($Number / 1000000);
	$Number -= $Millions * 1000000;
	$Thousands = floor($Number / 1000);
	$Number -= $Thousands * 1000;
	$Hundreds = floor($Number / 100);
	$Number -= $Hundreds * 100;
	$NoOfTens = floor($Number / 10);
	$NoOfOnes = $Number % 10;

	$NumberInWords = '';

	if ($Millions) {
		$NumberInWords .= number_to_words($Millions) . ' ' . _('million');
	}

    if ($Thousands) {
		$NumberInWords .= (empty($NumberInWords) ? '' : ' ') . number_to_words($Thousands) . ' ' . _('thousand');
	}

    if ($Hundreds) {
		$NumberInWords .= (empty($NumberInWords) ? '' : ' ') . number_to_words($Hundreds) . ' ' . _('hundred');
	}

	$Ones = array(	0 => '',
					1 => _('one'),
					2 => _('two'),
					3 => _('three'),
					4 => _('four'),
					5 => _('five'),
					6 => _('six'),
					7 => _('seven'),
					8 => _('eight'),
					9 => _('nine'),
					10 => _('ten'),
					11 => _('eleven'),
					12 => _('twelve'),
					13 => _('thirteen'),
					14 => _('fourteen'),
					15 => _('fifteen'),
					16 => _('sixteen'),
					17 => _('seventeen'),
					18 => _('eighteen'),
					19 => _('nineteen')	);

	$Tens = array(	0 => '',
					1 => '',
					2 => _('twenty'),
					3 => _('thirty'),
					4 => _('forty'),
					5 => _('fifty'),
					6 => _('sixty'),
					7 => _('seventy'),
					8 => _('eighty'),
					9 => _('ninety') );


    if ($NoOfTens OR $NoOfOnes) {
		if (!empty($NumberInWords)) {
			$NumberInWords .= ' ' . _('and') . ' ';
		}

		if ($NoOfTens < 2){
			$NumberInWords .= $Ones[$NoOfTens * 10 + $NoOfOnes];
		}
		else {
			$NumberInWords .= $Tens[$NoOfTens];
			if ($NoOfOnes) {
				$NumberInWords .= '-' . $Ones[$NoOfOnes];
			}
		}
	}

	if (empty($NumberInWords)){
		$NumberInWords = _('zero');
	}

	return $NumberInWords;
}

?>
