<?php
/*PDF page header for Top Items report */
if ($PageNumber>1){
	$PDF->newPage();
}

$FontSize=10;
$YPos= $Page_Height-$Top_Margin;
$XPos=0;
$PDF->addJpegFromFile('companies/' . $_SESSION['DatabaseName'] . '/logo.jpg',$XPos+20,$YPos-50,0,60);

if ($_GET['Customers']!='All'){
	$SQL="SELECT typename
		  FROM `debtortype`
		  WHERE typeid='".$_GET['Customers']."'";

	$result = DB_query($SQL);
	$myrow=DB_fetch_array($result);
	$Customers=$myrow["0"];
}else{
	$Customers='All';
}

//Display the searching condition
$PDF->addTextWrap($XPos+40,$YPos-70,500,9,_('Search On Location').' : '.$_GET['Location']);
$PDF->addTextWrap($XPos+240,$YPos-70,500,9,_('Customers'). ' : '.$Customers);
$PDF->addTextWrap($XPos+40,$YPos-90,500,9,_('Number Of Days')." : ".$_GET['NumberOfDays']." ");
$PDF->addTextWrap($XPos+240,$YPos-90,500,9,_('Number Of Items')." : ".$_GET['NumberOfTopItems']);
$PDF->addTextWrap($XPos+40,$YPos-110,500,9,_('Order By')." : ".$_GET['Sequence']);

$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-140,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-140,$YPos-($line_height*1.5),550,$FontSize, _('Top Items Sales Search Result') );
$LeftOvers = $PDF->addTextWrap($Page_Width-$Right_Margin-140,$YPos-($line_height*3),140,$FontSize, _('Printed').': ' . Date($_SESSION['DefaultDateFormat']) . '   '. _('Page'). ' ' . $PageNumber);

$YPos -= 100;

$YPos -=$line_height;
//Note, this is ok for multilang as this is the value of a Select, text in option is different

$YPos -=(2*$line_height);

/*Draw a rectangle to put the headings in     */
$PDF->Rectangle($Left_Margin, $YPos+$line_height,$Page_Width-$Left_Margin-$Right_Margin,$line_height*2);

/*set up the headings */
$Xpos = $Left_Margin+1;

$LeftOvers = $PDF->addTextWrap($Xpos,$YPos,300-$Left_Margin,$FontSize,  _('Code'), 'centre');
$LeftOvers = $PDF->addTextWrap($Xpos+100,$YPos,300-$Left_Margin,$FontSize,  _('Description'), 'centre');
$LeftOvers = $PDF->addTextWrap($Xpos+320,$YPos,300-$Left_Margin,$FontSize,  _('Total Inv'), 'centre');
$LeftOvers = $PDF->addTextWrap($Xpos+370,$YPos,300-$Left_Margin,$FontSize,  _('Unit'), 'centre');
$LeftOvers = $PDF->addTextWrap($Xpos+410,$YPos,300-$Left_Margin,$FontSize,  _('Value Sales'), 'centre');
$LeftOvers = $PDF->addTextWrap($Xpos+480,$YPos,300-$Left_Margin,$FontSize,  _('On Hand'), 'centre');


$FontSize=8;
$PDF->Rectangle($Left_Margin, $YPos-$line_height,$Page_Width-$Left_Margin-$Right_Margin,$YPos-$Bottom_Margin);
$YPos -= (1.5 * $line_height);

?>