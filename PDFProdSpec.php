<?php


include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['KeyValue']))  {
	$SelectedProdSpec=$_GET['KeyValue'];
} elseif (isset($_POST['KeyValue'])) {
	$SelectedProdSpec=$_POST['KeyValue'];
} else {
	$SelectedProdSpec='';
}
//Get Out if we have no product specification
If (!isset($SelectedProdSpec) OR $SelectedProdSpec==''){
        $Title = _('Select Product Specification To Print');
        include('includes/header.php');
		echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . _('Print')  . '" alt="" />' . ' ' . $Title . '</p>';
        echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">
		<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Enter Specification Name') .':</td>
			<td><input type="text" name="KeyValue" size="25" maxlength="25" /></td>
		</tr>
		</table>
		</div>
		<div>
		<input type="submit" name="PickSpec" value="' . _('Submit') . '" />
		</div>
		</form>
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">
		<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Or Select Existing Specification') .':</td>';
	$SQLSpecSelect="SELECT DISTINCT(keyval),
							description
						FROM prodspecs LEFT OUTER JOIN stockmaster
						ON stockmaster.stockid=prodspecs.keyval";


	$ResultSelection=DB_query($SQLSpecSelect);
	echo '<td><select name="KeyValue">';

	while ($MyRowSelection=DB_fetch_array($ResultSelection)){
		echo '<option value="' . $MyRowSelection['keyval'] . '">' . $MyRowSelection['keyval'].' - ' .htmlspecialchars($MyRowSelection['description'], ENT_QUOTES,'UTF-8', false)  . '</option>';
	}
	echo '</select></td>';
	echo '</tr>
		</table>
		</div>
		<div>
		<input type="submit" name="PickSpec" value="' . _('Submit') . '" />
		</div>
		</form>';
    include('includes/footer.php');
    exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the Product Specification') . ' ' . $SelectedProdSpec . ' ' . _('from the database');

$sql = "SELECT keyval,
				description,
				longdescription,
				prodspecs.testid,
				name,
				method,
				qatests.units,
				type,
				numericvalue,
				prodspecs.targetvalue,
				prodspecs.rangemin,
				prodspecs.rangemax,
				groupby
			FROM prodspecs INNER JOIN qatests
			ON qatests.testid=prodspecs.testid
			LEFT OUTER JOIN stockmaster on stockmaster.stockid=prodspecs.keyval
			WHERE prodspecs.keyval='" .$SelectedProdSpec."'
			AND prodspecs.showonspec='1'
			ORDER by groupby, prodspecs.testid";

$result=DB_query($sql,$ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){
	$Title = _('Print Product Specification Error');
	include('includes/header.php');
	 echo '<div class="centre">
			<br />
			<br />
			<br />';
	prnMsg( _('Unable to Locate Specification') . ' : ' . $_SelectedProdSpec . ' ', 'error');
	echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
				<td class="menu_group_item">
					<ul><li><a href="'. $RootPath . '/PDFProdSpec.php">' . _('Product Specifications') . '</a></li></ul>
				</td>
			</tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
	include('includes/footer.php');
	exit;
}
$PaperSize = 'Letter';

include('includes/PDFStarter.php');
$PDF->addInfo('Title', _('Product Specification') );
$PDF->addInfo('Subject', _('Product Specification') . ' ' . $SelectedProdSpec);
$FontSize=12;
$PageNumber = 1;
$HeaderPrinted=0;
$line_height=$FontSize*1.25;
$RectHeight=12;
$SectionHeading=0;
$CurSection='NULL';
$SectionTitle='';
$SectionTrailer='';

$SectionsArray=array(array('PhysicalProperty',3, _('Technical Data Sheet Properties'), _('* Data herein is typical and not to be construed as specifications.'), array(260,110,135),array(_('Physical Property'),_('Value'),_('Test Method')),array('left','center','center')),
					 array('',3, _('Header'), _('* Trailer'), array(260,110,135), array(_('Physical Property'),_('Value'),_('Test Method')),array('left','center','center')),
					 array('Processing',2, _('Injection Molding Processing Guidelines'), _('* Desicant type dryer required.'), array(240,265),array(_('Setting'),_('Value')),array('left','center')),
					 array('RegulatoryCompliance',2, _('Regulatory Compliance'), '', array(240,265),array(_('Regulatory Compliance'),_('Value')),array('left','center')));

while ($myrow=DB_fetch_array($result)){
	if ($myrow['description']=='') {
		$myrow['description']=$myrow['keyval'];
	}
	$Spec=$myrow['description'];
	$SpecDesc=$myrow['longdescription'];
	foreach($SectionsArray as $row) {
		if ($myrow['groupby']==$row[0]) {
			$SectionColSizes=$row[4];
			$SectionColLabs=$row[5];
			$SectionAlign=$row[6];
		}
	}
	$TrailerPrinted=1;
	if ($HeaderPrinted==0) {
		include('includes/PDFProdSpecHeader.inc');
		$HeaderPrinted=1;
	}

	if ($CurSection!=$myrow['groupby']) {
		$SectionHeading=0;
		if ($CurSection!='NULL' AND $PrintTrailer==1) {
			$PDF->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
		}
		$PrevTrailer=$SectionTrailer;
		$CurSection=$myrow['groupby'];
		foreach($SectionsArray as $row) {
			if ($myrow['groupby']==$row[0]) {
				$SectionTitle=$row[2];
				$SectionTrailer=$row[3];
			}
		}
	}

	if ($SectionHeading==0) {
		$XPos=65;
		if ($PrevTrailer>'' AND $PrintTrailer==1) {
			$PrevFontSize=$FontSize;
			$FontSize=8;
			$line_height=$FontSize*1.25;
			$LeftOvers = $PDF->addTextWrap($XPos+5,$YPos,500,$FontSize,$PrevTrailer,'left');
			$FontSize=$PrevFontSize;
			$line_height=$FontSize*1.25;
			$YPos -= $line_height;
			$YPos -= $line_height;
		}
		if ($YPos < ($Bottom_Margin + 90)){ // Begins new page
			$PrintTrailer=0;
			$PageNumber++;
			include ('includes/PDFProdSpecHeader.inc');
		}
		$LeftOvers = $PDF->addTextWrap($XPos,$YPos,500,$FontSize,$SectionTitle,'center');
		$YPos -= $line_height;
		$PDF->setFont('','B');
		$PDF->SetFillColor(200,200,200);
		$x=0;
		foreach($SectionColLabs as $CurColLab) {
			$ColLabel=$CurColLab;
			$ColWidth=$SectionColSizes[$x];
			$x++;
			$LeftOvers = $PDF->addTextWrap($XPos+1,$YPos,$ColWidth,$FontSize,$ColLabel,'center',1,'fill');
			$XPos+=$ColWidth;
		}
		$SectionHeading=1;
		$YPos -= $line_height;
		$PDF->setFont('','');
	} //$SectionHeading==0
	$XPos=65;
	$Value='';
	if ($myrow['targetvalue'] > '') {
		$Value=$myrow['targetvalue'];
	} elseif ($myrow['rangemin'] > '' OR $myrow['rangemax'] > '') {
		if ($myrow['rangemin'] > '' AND $myrow['rangemax'] == '') {
			$Value='> ' . $myrow['rangemin'];
		} elseif ($myrow['rangemin']== '' AND $myrow['rangemax'] > '') {
			$Value='< ' . $myrow['rangemax'];
		} else {
			$Value=$myrow['rangemin'] . ' - ' . $myrow['rangemax'];
		}
	}
	if (strtoupper($Value) <> 'NB' AND strtoupper($Value) <> 'NO BREAK') {
		$Value.= ' ' . $myrow['units'];
	}
	$x=0;

	foreach($SectionColLabs as $CurColLab) {
		$ColLabel=$CurColLab;
		$ColWidth=$SectionColSizes[$x];
		$ColAlign=$SectionAlign[$x];
		switch ($x) {
			case 0;
				$DispValue=$myrow['name'];
				break;
			case 1;
				$DispValue=$Value;
				break;
			case 2;
				$DispValue=$myrow['method'];
				break;
		}
		$LeftOvers = $PDF->addTextWrap($XPos+1,$YPos,$ColWidth,$FontSize,$DispValue,$ColAlign,1);
		$XPos+=$ColWidth;
		$x++;
	}
	$YPos -= $line_height;
	$XPos=65;
	$PrintTrailer=1;
	if ($YPos < ($Bottom_Margin + 80)){ // Begins new page
		$PDF->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
		$PrintTrailer=0;
		$PageNumber++;
		include ('includes/PDFProdSpecHeader.inc');
	}
	//echo 'PrintTrailer'.$PrintTrailer.' '.$PrevTrailer.'<br>' ;
} //while loop

$PDF->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
if ($SectionTrailer>'') {
	$PrevFontSize=$FontSize;
	$FontSize=8;
	$line_height=$FontSize*1.25;
	$LeftOvers = $PDF->addTextWrap($XPos+5,$YPos,500,$FontSize,$SectionTrailer,'left');
	$FontSize=$PrevFontSize;
	$line_height=$FontSize*1.25;
	$YPos -= $line_height;
	$YPos -= $line_height;
}
if ($YPos < ($Bottom_Margin + 85)){ // Begins new page
	$PageNumber++;
	include ('includes/PDFProdSpecHeader.inc');
}
$Disclaimer= _('The information provided on this datasheet should only be used as a guideline. Actual lot to lot values will vary.');
$FontSize=8;
$line_height=$FontSize*1.25;
$YPos -= $line_height;
$LeftOvers = $PDF->addTextWrap($XPos+5,$YPos,500,$FontSize,$Disclaimer);
$YPos -= $line_height;
$YPos -= $line_height;
$sql = "SELECT confvalue
			FROM config
			WHERE confname='QualityProdSpecText'";

$result=DB_query($sql,$ErrMsg);
$myrow=DB_fetch_array($result);
$Disclaimer=$myrow[0];
$LeftOvers = $PDF->addTextWrap($XPos+5,$YPos,500,$FontSize,$Disclaimer);
while (mb_strlen($LeftOvers) > 1) {
	$YPos -= $line_height;
	$LeftOvers = $PDF->addTextWrap($XPos+5,$YPos,500,$FontSize, $LeftOvers, 'left');
}

$PDF->OutputI($_SESSION['DatabaseName'] . '_ProductSpecification_' . date('Y-m-d') . '.pdf');
$PDF->__destruct();

?>