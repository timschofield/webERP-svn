<?php
	/*
	 * PDF page header for the profit and loss report.
	 * Suren Naidu 28/08/2005
	 *
	 */

	$PageNumber++;
	if ($PageNumber>1){
		$PDF->newPage();
	}

	$FontSize = 8;
	$YPos = $Page_Height - $Top_Margin;
	$PDF->addText($Left_Margin,$YPos,$FontSize,$_SESSION['CompanyRecord']['coyname']);

	$YPos -= $line_height;
	$FontSize =10;
	$PDF->setFont('','B');
	$Heading = _('Profit and loss for the ') . $NumberOfMonths . _(' months to ') . _('and including ') . $PeriodToDate;
	$Heading1 = _('Transactions for tag') . ':     '.$_POST['tag'].' - '.$Tag;
	$PDF->addText($Left_Margin, $YPos, $FontSize, $Heading);
	$PDF->addText($Left_Margin, $YPos-$line_height, $FontSize, $Heading1);

	$FontSize = 8;
	$PDF->setFont('','');
	$PDF->addText($Page_Width-$Right_Margin-120,$YPos,$FontSize,
		_('Printed'). ': ' . Date($_SESSION['DefaultDateFormat'])
		. '   '. _('Page'). ' ' . $PageNumber);

	$YPos -= (3 * $line_height);
	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Account'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin+60,$YPos,100,$FontSize,_('Account Name'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,_('Period Actual'),'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,_('Period Budget'),'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,_('Last Year'),'right');
	$YPos -= (3 * $line_height);
?>