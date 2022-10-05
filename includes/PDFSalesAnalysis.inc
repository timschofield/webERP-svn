<?php


include ('includes/class.pdf.php');


/* A4_Landscape */
$DocumentPaper = 'A4';
$DocumentOrientation ='L';
$Page_Width=842;
$Page_Height=595;
$Top_Margin=30;
$Bottom_Margin=30;
$Left_Margin=40;
$Right_Margin=30;

$PDF = new Cpdf($DocumentOrientation, 'pt', $DocumentPaper);
$PDF->SetAutoPageBreak(true, 0);
$PDF->SetPrintHeader(false);
$PDF->AddPage();
$PDF->cMargin = 0;

/* Standard PDF file creation header stuff */

$PDF->addInfo('Author','webERP ' . $Version);
$PDF->addInfo('Creator','webERP http://www.weberp.org');
$PDF->addInfo('Title',$ReportSpec['reportheading']);
$PDF->addInfo('Subject',_('Sales Analysis Report') . ' ' . $ReportSpec['reportheading']);

$PageNumber = 0;
$line_height=12;

include ('includes/PDFSalesAnalPageHeader.inc');

$GrpData1='';
$GrpData2='';
$GrpData3='';

$Counter=0;

/*Make an array to hold accumulators for */
$AccumLvl1 = array();
$AccumLvl2 = array();
$AccumLvl3 = array();
$AccumLvl4 = array();

for ($i=0;$i<=10;$i++){
	$AccumLvl1[$i]=0;
	$AccumLvl2[$i]=0;
	$AccumLvl3[$i]=0;
	$AccumLvl4[$i]=0;
}

While ($myrow = DB_fetch_array($result)){

/*First off check that at least one of the columns of data has some none zero amounts */
	DB_data_seek($ColsResult,0); /*go back to the beginning */
	$ThisLineHasOutput=False;   /*assume no output to start with */
	while ($Cols = DB_fetch_array($ColsResult)){
		$ColumnNo ='col' . ((int) $Cols['colno'] + 8);
		if (abs($myrow[$ColumnNo])>0.5){
			$ThisLineHasOutput = True;
		}
	}
	if ($ThisLineHasOutput==True){

		if ($myrow['col5']!=$GrpData3 && $myrow['col5']!='0' && $myrow['col7']!='0'){
			/*Totals only relevant to GrpByLevel 3 if GrpByLevel 4 also used */
			if ($Counter > 0){ /*Dont want to print totals if this is the first record */
				$TotalText = mb_substr(_('TOTAL') . ' ' . $LastLine['col5'] . ' - ' . $LastLine['col6'],0,33);
				$LeftOvers = $PDF->addTextWrap(40,$Ypos,180,$FontSize,$TotalText);

				DB_data_seek($ColsResult,0);
				while ($Cols = DB_fetch_array($ColsResult)){
					$Xpos = 160 + $Cols['colno']*60;
					if ($Cols['calculation']==0){
						$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, locale_number_format($AccumLvl3[$Cols['colno']],0),'right');
					} else { /* its a calculation need to re-perform on the totals*/

						switch ($Cols['calcoperator']) {
							case '/':
								if ($AccumLvl3[$Cols['coldenominator']]==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] / $AccumLvl3[$Cols['coldenominator']];
								}
								break;
							case '+':
								$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] + $AccumLvl3[$Cols['coldenominator']];
								break;
							case '-':
								$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] + $AccumLvl3[$Cols['coldenominator']];
								break;
							case '*':
								$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] * $Cols['constant'];
								break;
							case 'C':
								if ($Cols['constant']==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] / $Cols['constant'];
								}
								break;
						} /*end of switch stmt block*/
						if ($Cols['valformat']=='P'){
							$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
						} else {
							$TotalCalculation = locale_number_format($TotalCalculation,0);
						}
						$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
					}
				}
				$Ypos -=(2*$line_height);
				/*reset the accumulators to 0 */
				for ($i=0;$i<=10;$i++){
					$AccumLvl3[$i]=0;
				}
			}
		}

		if ($myrow['col3']!=$GrpData2 AND $myrow['col3']!='0' AND $myrow['col5']!='0'){
		/*Totals only relevant to GrpByLevel 2 if GrpByLevel 3 also used */
			if ($Counter > 0){ /*Dont want to print totals if this is the first record */
				$TotalText = mb_substr(_('TOTAL') . ' ' . $LastLine['col3'] . ' - ' . $LastLine['col4'],0,43);
				$LeftOvers = $PDF->addTextWrap(30,$Ypos,190,$FontSize,$TotalText);
				DB_data_seek($ColsResult,0);
				while ($Cols = DB_fetch_array($ColsResult)){
					$Xpos = 160 + $Cols['colno']*60;
					if ($Cols['calculation']==0){
						$LeftOvers = $PDF->addTextWrap($Xpos, $Ypos,60,$FontSize, locale_number_format($AccumLvl2[$Cols['colno']],0),'right');
					} else { /* its a calculation need to re-perform on the totals*/

						switch ($Cols['calcoperator']) {
							case '/':
								if ($AccumLvl2[$Cols['coldenominator']]==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] / $AccumLvl2[$Cols['coldenominator']];
								}
								break;
							case '+':
								$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] + $AccumLvl2[$Cols['coldenominator']];
								break;
							case '-':
								$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] + $AccumLvl2[$Cols['coldenominator']];
								break;
							case '*':
								$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] * $Cols['constant'];
								break;
							case 'C':
								if ($Cols['constant']==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] / $Cols['constant'];
								}
								break;
						} /*end of switch stmt block*/
						if ($Cols['valformat']=='P'){
							$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
						} else {
							$TotalCalculation = locale_number_format($TotalCalculation,0);
						}
						$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
					}/*end if not a calculation column*/
				}/*end while loop through column definitions */
				$Ypos -=(2*$line_height);
				/*reset the accumulators to 0 */
				for ($i=0;$i<=10;$i++){
					$AccumLvl2[$i]=0;
				}
			}/*end if Counter > 0 */
		}

		if ($myrow['col1']!=$GrpData1  && $myrow['col3']!='0'){
			/*Totals only relevant to GrpByLevel 1 if GrpByLevel 2 also used */
			if ($Counter > 0){ /*Dont want to print totals if this is the first record */
				$TotalText = mb_substr(_('TOTAL') . ' ' . $LastLine['col1'] . ' - ' . $LastLine['col2'],0,46);
				$LeftOvers = $PDF->addTextWrap(15,$Ypos,205,$FontSize,$TotalText);
				DB_data_seek($ColsResult,0);
				while ($Cols = DB_fetch_array($ColsResult)){
					$Xpos = 160 + $Cols['colno']*60;
					if ($Cols['calculation']==0){
						$LeftOvers = $PDF->addTextWrap($Xpos, $Ypos,60,$FontSize, locale_number_format($AccumLvl1[$Cols['colno']],0),'right');
					} else { /* its a calculation need to re-perform on the totals*/

						switch ($Cols['calcoperator']) {
							Case '/':
								if ($AccumLvl1[$Cols['coldenominator']]==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] / $AccumLvl1[$Cols['coldenominator']];
								}
								break;
							Case '+':
								$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] + $AccumLvl1[$Cols['coldenominator']];
								break;
							Case '-':
								$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] + $AccumLvl1[$Cols['coldenominator']];
								break;
							Case '*':
								$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] * $Cols['constant'];
								break;
							Case 'C':
								if ($Cols['constant']==0){
									$TotalCalculation = 0;
								} else {
									$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] / $Cols['constant'];
								}
								break;
						} /*end of switch stmt block*/
						if ($Cols['valformat']=='P'){
							$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
						} else {
							$TotalCalculation = locale_number_format($TotalCalculation,0);
						}
						$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
					}/*end if its not a calculation */
				} /* end loop around column defintions */
				$Ypos -=(2*$line_height);

				/*reset the accumulators to 0 */
				for ($i=0;$i<=10;$i++){
					$AccumLvl1[$i]=0;
				}
			}/* end if counter>0 */
		}

		$NewHeading =0;

		if ($myrow['col1']!=$GrpData1){ /*Need a new heading for Level 1 */
			$NewHeading = 1;
			if ($ReportSpec['newpageafter1']==1){
				include ('includes/PDFSalesAnalPageHeader.inc');
			}
			$GroupHeadingText = mb_substr($myrow['col1'] . ' - ' . $myrow['col2'],0,50);
			$LeftOvers = $PDF->addTextWrap(15,$Ypos,205,$FontSize,$GroupHeadingText);

			if ($myrow['col3']!='0'){
				$Ypos-=$line_height;
			}
		}

		if (($myrow['col3']!=$GrpData2  OR $NewHeading ==1) AND $myrow['col3']!='0'){
			/*Need a new heading for Level 2 */
			$NewHeading = 1;
			if ($ReportSpec['newpageafter2']==1){
				include ('includes/PDFSalesAnalPageHeader.inc');
			}
			$GroupHeadingText = mb_substr($myrow['col3'] . ' - ' . $myrow['col4'],0,46);
			$LeftOvers = $PDF->addTextWrap(30,$Ypos,190,$FontSize,$GroupHeadingText);

			if ($myrow['col5']!='0'){
				$Ypos-=$line_height;
			}
		}
		if (($myrow['col5']!=$GrpData3  OR $NewHeading ==1) AND $myrow['col5']!='0'){
			/*Need a new heading for Level 3 */

			if ($ReportSpec['newpageafter3']==1){
				include ('includes/PDFSalesAnalPageHeader.inc');
			}
			$GroupHeadingText = mb_substr($myrow['col5'] . ' - ' . $myrow['col6'],0,46);
			$LeftOvers = $PDF->addTextWrap(30,$Ypos,190,$FontSize,$GroupHeadingText);

			if ($myrow['col7']!='0'){
				$Ypos-=$line_height;
			}
		}

		if ($myrow['col7']!='0'){
			/*show titles */
			$GroupHeadingText = mb_substr($myrow['col7'] . ' - ' . $myrow['col8'], 0, 40);
			$LeftOvers = $PDF->addTextWrap(55,$Ypos,135,$FontSize,$GroupHeadingText);

		}

		/*NOW SHOW THE LINE OF DATA */
		DB_data_seek($ColsResult,0);
		while ($Cols = DB_fetch_array($ColsResult)){
			$Xpos = 160 + ($Cols['colno']*60);
			$ColumnNo = 'col' . (string) (($Cols['colno']) +8);
			if ($Cols['valformat']=='P'){
				$DisplayValue = locale_number_format($myrow[$ColumnNo] *100,1) . '%';
			} else {
				$DisplayValue = locale_number_format($myrow[$ColumnNo],0);
			}
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize,$DisplayValue, 'right');

			$AccumLvl1[$Cols['colno']] += $myrow[$ColumnNo];
			$AccumLvl2[$Cols['colno']] += $myrow[$ColumnNo];
			$AccumLvl3[$Cols['colno']] += $myrow[$ColumnNo];
			$AccumLvl4[$Cols['colno']] += $myrow[$ColumnNo];
		}

		$Ypos -=$line_height;

		if ($Ypos - (2*$line_height) < $Bottom_Margin){
			include ('includes/PDFSalesAnalPageHeader.inc');
		}//end if need a new page headed up
		$GrpData1 = $myrow['col1'];
		$GrpData2 = $myrow['col3'];
		$GrpData3 = $myrow['col5'];
		$Counter++;
		$LastLine = $myrow; /*remember the last line that had some output in an array called last line*/
	} /*The line has some positive amount on it */
} /*end of the data loop to print lines */

if ($LastLine['col5']!='0' && $LastLine['col7']!='0'){
/* if GrpBY3 and GrpBy4 are both set need to show totals for GrpBy3 */
	if ($Counter>0){ /*Dont want to print totals if this is the first record */
		$TotalText = mb_substr(_('TOTAL') . ' ' . $LastLine['col5'] . ' - ' . $LastLine['col6'],0,33);
		$LeftOvers = $PDF->addTextWrap(30,$Ypos,190,$FontSize,$TotalText);

		DB_data_seek($ColsResult,0);
		while ($Cols = DB_fetch_array($ColsResult)){
			$Xpos = 160 + $Cols['colno']*60;
			if ($Cols['calculation']==0){
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, locale_number_format($AccumLvl3[$Cols['colno']],0),'right');

			} else { /* its a calculation need to re-perform on the totals*/

				switch ($Cols['calcoperator']) {
					Case '/':
						if ($AccumLvl3[$Cols['coldenominator']]==0){
							$TotalCalculation = 0;
						} else {
							$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] / $AccumLvl3[$Cols['coldenominator']];
						}
						break;
					Case '+':
						$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] + $AccumLvl3[$Cols['coldenominator']];
						break;
					Case '-':
						$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] + $AccumLvl3[$Cols['coldenominator']];
						break;
					Case '*':
						$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] * $Cols['constant'];
						break;
					Case 'C':
						if ($Cols['constant']==0){
							$TotalCalculation = 0;
						} else {
							$TotalCalculation = $AccumLvl3[$Cols['colnumerator']] / $Cols['constant'];
						}
						break;
				} /*end of switch stmt block*/
				if ($Cols['valformat']=='P'){
					$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
				} else {
					$TotalCalculation = locale_number_format($TotalCalculation,0);
				}
				$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
			}

		}
		$Ypos -=$line_height;
	}
}

if ($LastLine['col3']!='0' AND $LastLine['col5']!='0'){
/* if GrpBY2 and GrpBy3 are both set need to show totals for GrpBy2 */
	if ($Counter>0){ /*Dont want to print totals if this is the first record */
		$TotalText = mb_substr(_('TOTAL') . ' ' . $LastLine['col3'] . ' - ' . $LastLine['col4'],0,33);
		$LeftOvers = $PDF->addTextWrap(30,$Ypos,190,$FontSize,$TotalText);
		DB_data_seek($ColsResult,0);
		while ($Cols = DB_fetch_array($ColsResult)){
			$Xpos = 160 + $Cols['colno']*60;
			if ($Cols['calculation']==0){
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, locale_number_format($AccumLvl2[$Cols['colno']],0),'right');

			} else { /* its a calculation need to re-perform on the totals*/

				switch ($Cols['calcoperator']) {
					Case '/':
						if ($AccumLvl2[$Cols['coldenominator']]==0){
							$TotalCalculation = 0;
						} else {
							$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] / $AccumLvl2[$Cols['coldenominator']];
						}
						break;
					Case '+':
						$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] + $AccumLvl2[$Cols['coldenominator']];
						break;
					Case '-':
						$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] + $AccumLvl2[$Cols['coldenominator']];
						break;
					Case '*':
						$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] * $Cols['constant'];
						break;
					Case 'C':
						if ($Cols['constant']==0){
							$TotalCalculation = 0;
						} else {
							$TotalCalculation = $AccumLvl2[$Cols['colnumerator']] / $Cols['constant'];
						}
						break;
				} /*end of switch stmt block*/
				if ($Cols['valformat']=='P'){
					$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
				} else {
					$TotalCalculation = locale_number_format($TotalCalculation,0);
				}
				$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
			}

		}
		$Ypos -=$line_height;
	}
}
if ($LastLine['col3']!='0'){
/* GrpBY1 must always be set but if GrpBy2 is also set need to show totals for GrpBy2 */
	if ($Counter>1){ /*Dont want to print totals if this is the first record */
		$TotalText = mb_substr(_('TOTAL') .  ' ' . $LastLine['col1'] . ' - ' . $LastLine['col2'],0,30);
		$LeftOvers = $PDF->addTextWrap(15,$Ypos,205,$FontSize,$TotalText);
		DB_data_seek($ColsResult,0);
		while ($Cols = DB_fetch_array($ColsResult)){
			$Xpos =160 + $Cols['colno']*60;
			if ($Cols['calculation']==0){
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, locale_number_format($AccumLvl1[$Cols['colno']],0),'right');
			} else { /* its a calculation need to re-perform on the totals*/

				switch ($Cols['calcoperator']) {
				Case '/':
					if ($AccumLvl1[$Cols['coldenominator']]==0){
						$TotalCalculation = 0;
					} else {
						$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] / $AccumLvl1[$Cols['coldenominator']];
					}
					break;
				Case '+':
					$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] + $AccumLvl1[$Cols['coldenominator']];
					break;
				Case '-':
					$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] + $AccumLvl1[$Cols['coldenominator']];
					break;
				Case '*':
					$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] * $Cols['constant'];
					break;
				Case 'C':
					if ($Cols['constant']==0){
						$TotalCalculation = 0;
					} else {
						$TotalCalculation = $AccumLvl1[$Cols['colnumerator']] / $Cols['constant'];
					}
					break;
				} /*end of switch stmt block*/
				if ($Cols['valformat']=='P'){
					$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
				} else {
					$TotalCalculation = locale_number_format($TotalCalculation,0);
				}
				$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
			}

		}
		$Ypos -=(2*$line_height);
	}
}
if ($Counter>0){
	$LeftOvers = $PDF->addTextWrap(15,$Ypos,205,$FontSize,_('GRAND TOTAL'));

	DB_data_seek($ColsResult,0);
	while ($Cols = DB_fetch_array($ColsResult)){
		$Xpos =160 + $Cols['colno']*60;
		if ($Cols['calculation']==0){
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, locale_number_format($AccumLvl4[$Cols['colno']],0),'right');
		} else { /* its a calculation need to re-perform on the totals*/

			switch ($Cols['calcoperator']) {
			Case '/':
				if ($AccumLvl4[$Cols['coldenominator']]==0){
					$TotalCalculation = 0;
				} else {
					$TotalCalculation = $AccumLvl4[$Cols['colnumerator']] / $AccumLvl4[$Cols['coldenominator']];
				}
				break;
			Case '+':
				$TotalCalculation = $AccumLvl4[$Cols['colnumerator']] + $AccumLvl4[$Cols['coldenominator']];
				break;
			Case '-':
				$TotalCalculation = $AccumLvl4[$Cols['colnumerator']] + $AccumLvl4[$Cols['coldenominator']];
				break;
			Case '*':
				$TotalCalculation = $AccumLvl4[$Cols['colnumerator']] * $Cols['constant'];
				break;
			Case 'C':
				if ($Cols['constant']==0){
					$TotalCalculation = 0;
				} else {
					$TotalCalculation = $AccumLvl4[$Cols['colnumerator']] / $Cols['constant'];
				}
				break;
			} /*end of switch stmt block*/
			if ($Cols['valformat']=='P'){
				$TotalCalculation = locale_number_format($TotalCalculation * 100,1) . '%';
			} else {
				$TotalCalculation = locale_number_format($TotalCalculation,0);
			}
			$LeftOvers = $PDF->addTextWrap($Xpos,$Ypos,60,$FontSize, $TotalCalculation,'right');
		}

	}
	$Ypos -=$line_height;
}


if (isset($_GET['ProduceCVSFile'])){

	function stripcomma($str) { //because we're using comma as a delimiter
		return str_replace(',','',$str);
	}

	$fp = fopen( $_SESSION['reports_dir'] . '/SalesAnalysis.csv', 'w');


	While ($myrow = DB_fetch_row($result)){

	/*First off check that at least one of the columns of data has some none zero amounts */
	      $ThisLineHasOutput=False;   /*assume no output to start with */
	      $NumberOfFields = DB_num_rows($ColsResult);

	      for ($i=3; $i<=$NumberOfFields+7; $i++) {
		     if (abs($myrow[$i])>0.009){
			 $ThisLineHasOutput = True;
		     }
	      }
	      if ($ThisLineHasOutput==True){
	      		$line='';
			for ($i=0;$i<=$NumberOfFields+7;$i++){
				if (isset($myrow[$i])){
					if ($i>0){
						$line.=',';
					}
					$line.=stripcomma($myrow[$i]);
				}
			}
			fputs($fp, $line."\n");
	      }
	 }
	 $Title = _('Sales Analysis Comma Separated File (CSV) Generation');
	include('includes/header.php');

	 echo 'http://' . getenv(SERVER_NAME) . $RootPath . '/' . $_SESSION['reports_dir'] .  '/SalesAnalysis.csv';
	 echo "<meta http-equiv='Refresh' content='0; url=http://" . getenv(SERVER_NAME) . $RootPath . '/' . $_SESSION['reports_dir'] .  "/SalesAnalysis.csv'>";

	 echo '<p>' . _('You should automatically be forwarded to the CSV Sales Analysis file when it is ready') . '. ' . _('If this does not happen') . ' <a href="' . $RootPath . '/' . $_SESSION['reports_dir'] . '/SalesAnalysis.csv">' . _('click here') . '</a> ' . _('to continue')  . '<br />';
	 include('includes/footer.php');
}

?>
