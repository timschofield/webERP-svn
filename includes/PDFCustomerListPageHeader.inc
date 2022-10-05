<?php
/*PDF page header for aged analysis reports */
$PageNumber++;
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=8;
$YPos= $Page_Height-$Top_Margin;

$PDF->addText($Left_Margin, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);

$YPos -=$line_height;

$FontSize =10;

$Heading = _('Customers List for'). ' ';

if (in_array('All', $_POST['Areas'])){
	$Heading .= _('All Territories'). ' ';
} else {
	if (count($_POST['Areas'])==1){
		$Heading .= _('Territory') . ' ' . $_POST['Areas'][0];
	} else {
		$Heading .= _('Territories'). ' ';
		$NoOfAreas = count($_POST['Areas']);
		$i=1;
		foreach ($_POST['Areas'] as $Area){
			if ($i==$NoOfAreas){
				$Heading .= _('and') . ' ' . $Area . ' ';
			} elseif ($i==($NoOfAreas-1)) {
				$Heading .= $Area . ' ';
			} else {
				$Heading .= $Area . ', ';
			}
		}
	}
}

$Heading .= ' '. _('and for').' ';
if (in_array('All', $_POST['SalesPeople'])){
	$Heading .= _('All Salespeople');
} else {
	if (count($_POST['SalesPeople'])==1){
		$Heading .= _('only') .' ' . $_POST['SalesPeople'][0];
	} else {
		$Heading .= _('Salespeople') .' ';
		$NoOfSalesfolk = count($_POST['SalesPeople']);
		$i=1;
		foreach ($_POST['SalesPeople'] as $Salesperson){
			if ($i==$NoOfSalesfolk){
				$Heading .= _('and') . ' ' . $Salesperson . " ";
			} elseif ($i==($NoOfSalesfolk-1)) {
				$Heading .= $Salesperson . " ";
			} else {
				$Heading .= $Salesperson . ", ";
			}
		}
	}
}

$PDF->setFont('','B');

$PDF->addText($Left_Margin, $YPos, $FontSize, $Heading);

$PDF->setFont('','');

$FontSize = 8;
$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed'). ': ' . Date($_SESSION['DefaultDateFormat']) . '   '. _('Page'). ' ' . $PageNumber);

$YPos -=(3*$line_height);

/*Draw a rectangle to put the headings in     */
$PDF->line($Page_Width-$Right_Margin, $YPos-5,$Left_Margin, $YPos-5);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Left_Margin, $YPos+$line_height);
$PDF->line($Page_Width-$Right_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos-5);
$PDF->line($Left_Margin, $YPos+$line_height,$Left_Margin, $YPos-5);

/*set up the headings */

$LeftOvers = $PDF->addTextWrap(40,$YPos,40,$FontSize, _('Act Code'),'left');
$LeftOvers = $PDF->addTextWrap(80,$YPos,150,$FontSize, _('Postal Address'),'left');
$LeftOvers = $PDF->addTextWrap(230,$YPos,60,$FontSize,_('Branch Code'),'left');
$LeftOvers = $PDF->addTextWrap(290,$YPos,150,$FontSize,_('Branch Contact Information'),'left');
$LeftOvers = $PDF->addTextWrap(440,$YPos,150,$FontSize,_('Branch Delivery Address'),'left');

$YPos =$YPos - (2*$line_height);
?>