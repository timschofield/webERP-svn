<?php
if ($PageNumber>1){
	$PDF->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$PDF->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,50);

$FontSize=15;

$XPos = $Page_Width/2;
$YPos = $Page_Height - $Top_Margin;
$PDF->addText($XPos, $YPos,$FontSize, _('Order Status Listing'));
$FontSize=12;

if ($_POST['CategoryID']!='All') {
	$PDF->addText($XPos, $YPos-20,$FontSize, _('For Stock Category') . ' ' . $_POST['CategoryID'] . ' ' . _('From') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' .  $_POST['ToDate']);
} else {
	$PDF->addText($XPos, $YPos-20,$FontSize, _('From') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' .  $_POST['ToDate']);
}
if ($_POST['Location']!='All'){
	$PDF->addText($XPos+300, $YPos-20, $FontSize, ' ' . _('for delivery ex') . ' ' . $_POST['Location'] . ' ' ._('only'));
}

$XPos = $Page_Width-$Right_Margin-50;
$YPos = $Page_Height - $Top_Margin - 50;
$PDF->addText($XPos, $YPos,$FontSize, _('Page') . ': ' . $PageNumber);

/*Now print out the company name and address */
$XPos = $Left_Margin;
$YPos -= $line_height*2;
$FontSize=8;

$NewPage=true;
?>
