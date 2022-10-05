<?php
	/*
	 * PDF page header for the trial balance report.
	 * Suren Naidu 18/08/2005
	 *
	 */

	$PageNumber++;
	if ($PageNumber>1){
		$PDF->newPage();
	}

	$FontSize = 8;
	$YPos = $Page_Height - $Top_Margin;
	$PDF->setFont('','');
	$PDF->addText($Left_Margin,$YPos,$FontSize,$_SESSION['CompanyRecord']['coyname']);
	$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize, _('Printed'). ': ' . Date($_SESSION['DefaultDateFormat'])  . '   ' . _('Page'). ' ' . $PageNumber);

	$YPos -= $line_height;
	$FontSize =10;
	$PDF->setFont('','B');
	$Heading = _('Trial Balance for the month of ') . $PeriodToDate . _(' and for the ') . $NumberOfMonths . _(' months to ') . $PeriodToDate;
	$PDF->addText($Left_Margin, $YPos, $FontSize, $Heading);

	$YPos -= (2 * $line_height);
	$FontSize = 8;
	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Account'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin+60,$YPos,100,$FontSize,_('Account Name'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,_('Month Actual'),'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,_('Month Budget'),'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,_('Period Actual'),'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,_('Period Budget'),'right');
	$PDF->setFont('','');
	$YPos -= (2 * $line_height);
?>