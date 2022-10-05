<?php

if ($PageNumber>1){
	$PDF->newPage();
}

$XPos = 55;
$YPos = 575;

$PDF->addText($XPos, $YPos,$FontSize, $myrow['deliverto']);
$PDF->addText($XPos, $YPos-13,$FontSize, $myrow['deladd1']);
$PDF->addText($XPos, $YPos-26,$FontSize, $myrow['deladd2']);
$PDF->addText($XPos, $YPos-39,$FontSize, $myrow['deladd3'] . ' ' . $myrow['deladd4'] . ' ' . $myrow['deladd5'] . ' ' . $myrow['deladd6']);

$YPos = 510;

$PDF->addText($XPos, $YPos,$FontSize, $myrow['name']);
$PDF->addText($XPos, $YPos-13,$FontSize, $myrow['address1']);
$PDF->addText($XPos, $YPos-26,$FontSize, $myrow['address2']);
$PDF->addText($XPos, $YPos-39,$FontSize, $myrow['address3'] . ' ' . $myrow['address4'] . ' ' . $myrow['address5']. ' ' . $myrow['deladd6']);


/*Print Dispatch Date - as current date
$XPos=50;
$YPos=98;
$PDF->addText($XPos, $YPos,$FontSize, Date($_SESSION['DefaultDateFormat']));

*/


/*Print the freight company to be used */
$XPos=65;
$YPos=48;
$PDF->addText($XPos, $YPos,$FontSize, $myrow['shippername']);

$XPos=630;
$YPos=567;
$PDF->addText($XPos, $YPos,$FontSize, _('Order No') . ': ' . $_GET['TransNo']);
$PDF->addText($XPos, $YPos-14,$FontSize, _('Your Ref') . ': ' . $myrow['customerref']);


$XPos=687;
$YPos=539;
$PDF->addText($XPos, $YPos,$FontSize,  ConvertSQLDate($myrow['orddate']));

$XPos=630;
$YPos=525;
$PDF->addText($XPos, $YPos,$FontSize, _('Cust') . ': ' . $myrow['debtorno']);
$PDF->addText($XPos, $YPos-14,$FontSize, _('Branch') . ': ' . $myrow['branchcode']);

$PDF->addText($XPos, $YPos-32,$FontSize, _('Page') . ': ' . $PageNumber);

$PDF->addText($XPos, $YPos-46,$FontSize,  _('From') . ': ' . $myrow['locationname']);


/*Print the order number */
$XPos=510;
$YPos=96;
$PDF->addText($XPos, $YPos,$FontSize, $_GET['TransNo']);


$XPos=609;
$YPos=96;
$LeftOvers = $PDF->addTextWrap($XPos,$YPos,170,$FontSize,stripcslashes($myrow['comments']));

if (mb_strlen($LeftOvers)>1){
	$LeftOvers = $PDF->addTextWrap($XPos,$YPos-14,170,$FontSize,$LeftOvers);
	if (mb_strlen($LeftOvers)>1){
		$LeftOvers = $PDF->addTextWrap($XPos,$YPos-28,170,$FontSize,$LeftOvers);
		if (mb_strlen($LeftOvers)>1){
			$LeftOvers = $PDF->addTextWrap($XPos,$YPos-42,170,$FontSize,$LeftOvers);
			if (mb_strlen($LeftOvers)>1){
				$LeftOvers = $PDF->addTextWrap($XPos,$YPos-56,170,$FontSize,$LeftOvers);
			}
		}
	}
}

$YPos = 414;

?>