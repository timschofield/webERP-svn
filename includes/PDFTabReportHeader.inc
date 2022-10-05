<?php
	/*
	 * PDF page header for the Pc Tab report.
	 *
	 */

	$PageNumber++;
	if ($PageNumber>1){
		$PDF->newPage();
	}

	$FontSize = 8;
	$YPos = $Page_Height - $Top_Margin;
	$PDF->addText($Left_Margin,$YPos,$FontSize,$_SESSION['CompanyRecord']['coyname']);
	$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed'). ': ' . Date($_SESSION['DefaultDateFormat'])  . '   ' . _('Page'). ' ' . $PageNumber);



?>