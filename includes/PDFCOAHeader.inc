<?php

if ($PageNumber>1){
	$PDF->newPage();
}
$SectionHeading=0;
$PDF->setFont('Helvetica','');

$XPos = 65;
$YPos=50;
$FontSize=8;
$line_height=$FontSize*1.25;
$PDF->SetLineWidth(1);
$PDF->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
$LeftOvers = $PDF->addTextWrap($XPos,$YPos,500,$FontSize,$_SESSION['CompanyRecord']['coyname']. ' | ' .$_SESSION['CompanyRecord']['regoffice4'] .' | '.$_SESSION['CompanyRecord']['telephone'] ,'center');
$YPos -= $line_height;
$LeftOvers = $PDF->addTextWrap($XPos,$YPos,500,$FontSize,$_SESSION['CompanyRecord']['regoffice1'].$_SESSION['CompanyRecord']['regoffice2'],'center');
$PDF->SetLineWidth(.2);

$YPos = 720;
$YPos -= $line_height;
$YPos -= $line_height;
$PDF->addJpegFromFile($_SESSION['LogoFile'],$XPos,$YPos,0,70);
$FontSize=14;
$line_height=$FontSize*1.50;
$YPos += $line_height;
$LeftOvers = $PDF->addTextWrap($XPos+330,$YPos,140,$FontSize,_('Certificate of Analysis'));
$YPos -= $line_height;
$YPos -= $line_height;
$PDF->setFont('','B');
$LeftOvers = $PDF->addTextWrap($XPos+1,$YPos,210,$FontSize,$Spec);
$PDF->setFont('','');
$YPos -= $line_height;
$YPos -= $line_height;
$LeftOvers = $PDF->addTextWrap($XPos+1,$YPos,500,$FontSize,'Certificate of Analysis for Lot' .': '.$SelectedCOA . '        ' . 'Date' .': '. $SampleDate,'center');
$FontSize=12;
$line_height=$FontSize*1.25;
$YPos -= $line_height;
$YPos -= $line_height;
?>