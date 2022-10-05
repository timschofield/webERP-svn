<?php

require_once($PathPrefix .'/includes/class.pdf.php');

class PDF extends Cpdf {

var $y0; // current y position
var $x0; // current x position
var $pageY; // y value of bottom of page less bottom margin

	function PDF() {
		global $Prefs;
		$PaperSize = explode(':',$Prefs['papersize']);
		parent::__construct($Prefs['paperorientation'], 'mm', $PaperSize[0]);
		if ($Prefs['paperorientation']=='P') { // Portrait - calculate max page height
			$this->pageY = $PaperSize[2]-$Prefs['marginbottom'];
		} else { // Landscape
			$this->pageY = $PaperSize[1]-$Prefs['marginbottom'];
		}
		$this->SetMargins($Prefs['marginleft'], $Prefs['margintop'], $Prefs['marginright']);
		$this->SetAutoPageBreak(0, $Prefs['marginbottom']);

		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.35); // 1 point
		$this->AddPage();

	}

	function Header() {
		global $Prefs, $Heading, $Seq;
		define(RowSpace,2); // define separation between the heading rows
		$this->SetTopMargin($Prefs['margintop']);
		if ($Prefs['coynameshow']) { // Show the company name
			$this->SetFont($Prefs['coynamefont']);
			$this->SetFontSize($Prefs['coynamefontsize']);
			$Colors = explode(':',$Prefs['coynamefontcolor']);
			$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
			$CellHeight = ($Prefs['coynamefontsize']+RowSpace)*0.35;
			$this->Cell(0,$CellHeight,$_SESSION['CompanyRecord']['coyname'],0,1,$Prefs['coynamealign']);
		}
		if ($Prefs['title1show']) { // Set title 1 heading
			$this->SetFont($Prefs['title1font']);
			$this->SetFontSize($Prefs['title1fontsize']);
			$Colors = explode(':',$Prefs['title1fontcolor']);
			$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
			$CellHeight = ($Prefs['title1fontsize']+RowSpace)*0.35;
			$this->Cell(0,$CellHeight,$this->SubTitle($Prefs['title1desc']),0,1,$Prefs['title1fontalign']);
		}
		if ($Prefs['title2show']) { // Set Title 2 heading
			$this->SetFont($Prefs['title2font']);
			$this->SetFontSize($Prefs['title2fontsize']);
			$Colors = explode(':',$Prefs['title2fontcolor']);
			$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
			$CellHeight = ($Prefs['title2fontsize']+RowSpace)*0.35;
			$this->Cell(0,$CellHeight,$this->SubTitle($Prefs['title2desc']),0,1,$Prefs['title2fontalign']);
		}
		// Set the filter heading
		$this->SetFont($Prefs['filterfont']);
		$this->SetFontSize($Prefs['filterfontsize']);
		$Colors = explode(':',$Prefs['filterfontcolor']);
		$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
		$CellHeight = ($Prefs['filterfontsize']+RowSpace)*0.35*2;
		$this->Cell(0,$CellHeight,$Prefs['filterdesc'],'B',1,$Prefs['filterfontalign']);
		$this->y0=$this->GetY(); // set y position after report headings before column titles
		// Set the table header
		if (substr($Prefs['datafont'], -2) <> 'BI') {
			$this->SetFont($Prefs['datafont'].'BI');
		}
		$this->SetFontSize($Prefs['datafontsize']);
		$Colors = explode(':',$Prefs['datafontcolor']);
		$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.35); // 1 point
		$CellHeight = ($Prefs['datafontsize']+RowSpace)*0.35;
		// fetch the column widths and put into array to match the columns of data
		$CellXPos[0] = $Prefs['marginleft'];
		for ($x=1; $x<=20; $x++) {
			$CellXPos[$x] = $CellXPos[$x-1] + $Prefs['col'.$x.'width'];
		}
		// Fetch the column break array
		foreach ($Seq as $Temp) {
			if ($Temp['break']) {
				$ColBreak[] = true;
			} else {
				$ColBreak[] = false;
			}
		}
		// See if we need to truncate the data
		if ($Prefs['TruncListings']['params']=='1') {
			$trunc=true;
		} else {
			$trunc=false;
		}
		// Ready to draw the column titles in the header
		$maxY = $this->y0; // set to track the tallest column
		$col = 1;
		$LastY = $this->y0;
		foreach ($Heading as $key=>$value) {
			$this->SetLeftMargin($CellXPos[$col-1]);
			$this->SetX($CellXPos[$col-1]);
			$this->SetY($LastY);
			// truncate data if selected
			if ($trunc) {
				$value=$this->TruncData($value, $Prefs['col'.$col.'width']);
			}
			$this->MultiCell($CellXPos[$col]-$CellXPos[$col-1],$CellHeight,$value,0,$Prefs['datafontalign']);
			if ($ColBreak[$key]) {
				$col++;
				$LastY = $this->y0;
			} else $LastY = $this->GetY();
			if ($this->GetY()>$maxY) $maxY = $this->GetY(); // check for new col max height
		}
		// Draw a bottom line for the end of the heading
		$this->SetLeftMargin($CellXPos[0]);
		$this->SetX($CellXPos[0]);
		$this->SetY($this->y0);
		$this->Cell(0,$maxY-$this->y0,'','B');
		$this->y0=$maxY+0.35;
	}

	function SubTitle($Title) {
		global $Prefs;
		// substitutes a command string with current information
		$Title=preg_replace('/%time%/', date('g:i A',time()), $Title);
		$Title=preg_replace('/%date%/', date('Y-m-d',time()), $Title);
		$Title=preg_replace('/%reportname%/', $Prefs['reportname'], $Title);
		return $Title;
	}

	function Footer() {
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
		$this->SetFontSize(8);
		$this->SetTextColor(0);
		//Page number
		$this->Cell(0,(8+RowSpace)*0.35,'Page '.$this->PageNo().' / '.$this->getNumPages(),0,0,'C');
	}

	function ReportTable($Data) {
		global $Prefs, $Seq;

		$this->SetFont($Prefs['datafont']);
		$this->SetFontSize($Prefs['datafontsize']);
		$FillColor = array(224, 235, 255);
		$this->SetFillColor($FillColor[0],$FillColor[1],$FillColor[2]);
		$Colors = explode(':',$Prefs['datafontcolor']);
		$this->SetTextColor($Colors[0], $Colors[1], $Colors[2]);
		$CellHeight = ($Prefs['datafontsize']+RowSpace)*0.35;
		// Fetch the column widths and put into array to match the columns of data
		$CellXPos[0] = $Prefs['marginleft'];
		for ($x=1; $x<=20; $x++) $CellXPos[$x] = $CellXPos[$x-1] + $Prefs['col'.$x.'width'];
		// Fetch the column break array
		foreach ($Seq as $Temp) {
			if ($Temp['break']){
				 $ColBreak[] = true;
			} else {
				$ColBreak[] = false;
			}
		}
		// See if we need to truncate the data
		if ($Prefs['TruncListings']['params']=='1') {
			$trunc=true;
		} else {
			$trunc=false;
		}
		// Ready to draw the column data
		$fill=false;
		$NeedTop='No';
		$MaxRowHt = 0; //track the tallest row to estimate page breaks
		foreach($Data as $myrow) {
			$Action = array_shift($myrow);
			$todo = explode(':',$Action); // contains a letter of the date type and title/groupname
			$this->SetFontSize($Prefs['datafontsize']);
			$this->SetFont($Prefs['datafont']);
			switch ($todo[0]) {
				case "r": // Report Total
				case "g": // Group Total
					// Draw a fill box
					$this->SetFont($Prefs['totalsfont']);
					$this->SetFontSize($Prefs['totalsfontsize']);
					if ($this->y0+(2*$MaxRowHt)>$this->pageY) {
						// Fill the end of the report with white space
						$this->SetLeftMargin($CellXPos[0]);
						$this->SetX($CellXPos[0]);
						$this->SetY($this->y0);
						$this->SetFillColor(255);
						$this->Cell(0,$this->pageY-$this->y0,'','',0,'L',1);
						$this->AddPage();
						$MaxRowHt=0;
					}
					$this->SetLeftMargin($CellXPos[0]);
					$this->SetX($CellXPos[0]);
					$this->SetY($this->y0);
					$this->SetFillColor(240);
					$this->Cell(0,$CellHeight,'',$brdr,0,'L',1);
					// Add total heading
					$this->SetLeftMargin($CellXPos[0]);
					$this->SetX($CellXPos[0]);
					$this->SetY($this->y0);
					if ($todo[0]=='g') $Desc = 'Group'; else $Desc = 'Report';
					$this->Cell(0,$CellHeight,$Desc.' Total For: '.$todo[1],1,1,'C');
					$this->y0=$this->GetY()+0.35;
					$NeedTop = 'Next';
					$fill=false; // set so totals data will not be filled
					// now fall into the 'd' case to show the data
				case "d": // data element
				default:
					// figure out if a border needs to be drawn for total separation
					// and fill color (draws an empty box over the row just written with the fill color)
					$brdr = 0;
					if ($NeedTop=='Yes') {
						$brdr='T';
						$fill=false; // set so first data after total will not be filled
						$NeedTop='No';
					} elseif ($NeedTop=='Next') {
						$brdr='LR';
						$NeedTop='Yes';
					}
					// Draw a fill box
					if (($this->y0+$MaxRowHt)>$this->pageY) {
						// Fill the end of the report with white space
						$this->SetLeftMargin($CellXPos[0]);
						$this->SetX($CellXPos[0]);
						$this->SetY($this->y0);
						$this->SetFillColor(255);
						$this->Cell(0,$this->pageY-$this->y0,'','',0,'L',1);
						$this->AddPage();
						$MaxRowHt=0;
					}
					$this->SetLeftMargin($CellXPos[0]);
					$this->SetX($CellXPos[0]);
					$this->SetY($this->y0);
					if ($fill) $this->SetFillColor($FillColor[0],$FillColor[1],$FillColor[2]); else $this->SetFillColor(255);
					$this->Cell(0,$CellHeight,'',$brdr,0,'L',1);
					// fill in the data
					$maxY = $this->y0; // set to current top of row
					$col = 1;
					$LastY = $this->y0;
					foreach ($myrow as $key=>$value) {
						$this->SetLeftMargin($CellXPos[$col-1]);
						$this->SetX($CellXPos[$col-1]);
						$this->SetY($LastY);
						// truncate data if necessary
						if ($trunc) $value=$this->TruncData($value, $Prefs['col'.$col.'width']);
						$this->MultiCell($CellXPos[$col]-$CellXPos[$col-1],$CellHeight,$value,1,$Prefs['datafontalign']);
						if ($ColBreak[$key]) {
							$col++;
							$LastY = $this->y0;
						} else $LastY = $this->GetY();
						if ($this->GetY()>$maxY) $maxY = $this->GetY();
					}
					$this->SetLeftMargin($CellXPos[0]); // restore left margin
					break;
			}
			$ThisRowHt=$maxY-$this->y0; // seee how tall this row was
			if ($ThisRowHt>$MaxRowHt) $MaxRowHt = $ThisRowHt; // keep that largest row so far to track pagination
			$this->y0=$maxY; // set y position to largest value for next row
	        $fill=!$fill;
	    }
		// Fill the end of the report with white space
		$this->SetLeftMargin($CellXPos[0]);
		$this->SetX($CellXPos[0]);
		$this->SetY($this->y0);
		$this->SetFillColor(255);
		$this->Cell(0,$this->pageY-$this->y0,'','T',0,'L',1);
	}

	function TruncData($strData, $ColWidth) {
		$percent=0.90; //percent to truncate from max to account for proportional spacing
		$CurWidth = $this->GetStringWidth($strData);
		if ($CurWidth>($ColWidth*.90)) { // then it needs to be truncated
			// for now we'll do an approximation based on averages and scale to 90% of the width to allow for variance
			// A better aproach would be an recursive call to this function until the string just fits.
			$NumChars = mb_strlen($strData);
			// Reduce the string by 1-$percent and retest
			$strData = $this->TruncData(mb_substr($strData, 0, ($ColWidth/$CurWidth)*$NumChars*$percent), $ColWidth);
		}
		return $strData;
	}

	function Stream($FileName) {
	  $this->Output($FileName,'D');
	}

} // end class

function BuildSQL($Prefs) {
	//fetch the listing fields (must have at least one) to build select field
	$strField = '';
	if (is_array($Prefs['FieldListings'])) while ($FieldValues = array_shift($Prefs['FieldListings'])) {
		if ($FieldValues['visible']) $strField .= $FieldValues['fieldname'].', ';
	}
	// check for at least one field selected to show
	if (!$strField) { // No fields are checked to show, that's bad
		$usrMsg['message'] = RPT_NOROWS;
		$usrMsg['level'] = 'error';
		return $usrMsg;
	}
	$strField = mb_substr($strField,0,-2); // strip the last comma

	$Prefs['filterdesc'] = RPT_RPTBASIS.' | '.RPT_RPTFILTER; // Initialize the filter display string
	//fetch the groupings and build first level of SORT BY string (for sub totals)
	$strGroup = '';
	if (is_array($Prefs['GroupListings'])) while ($FieldValues = array_shift($Prefs['GroupListings'])) {
		if ($FieldValues['params']=='1') {  // then it's the group by field match
			$strGroup .= $FieldValues['fieldname'];
			$Prefs['filterdesc'] .= ' '.RPT_GROUPBY.' '.$FieldValues['displaydesc'].';';
			break;
		}
	}
	// fetch the sort order and add to group by string to finish ORDER BY string
	$strSort = $strGroup;
	if (is_array($Prefs['SortListings'])) while ($FieldValues = array_shift($Prefs['SortListings'])) {
		if ($FieldValues['params']=='1') {  // then it's the sort by field match
			if ($strSort=='') $strSort=$FieldValues['fieldname']; else $strSort.=', '.$FieldValues['fieldname'];
			$Prefs['filterdesc'] .= ' '.RPT_SORTBY.' '.$FieldValues['displaydesc'].';';
			break;
		}
	}
	// fetch date filter info
	$df = $Prefs['DateListings']['fieldname'];
	$Today = date('Y-m-d', time());
	$ThisMonth = date('m');
	$ThisYear = date('Y');
	$Yesterday = date('Y-m-d', time()-86400);
	$LastMonth = $ThisMonth-1;
	$LastYear = $ThisYear-1;
	$NextMonth = $ThisMonth+1;
	$NextYear = $ThisYear+1;
	// Find total number of days in this month:
	$TotalDays = cal_days_in_month(CAL_GREGORIAN, $ThisMonth, $ThisYear);
	// Find total number of days in last month:
	$TotalDaysLast = cal_days_in_month(CAL_GREGORIAN, $LastMonth, $ThisYear);
	// Find total number of days in next month:
	$TotalDaysNext = cal_days_in_month(CAL_GREGORIAN, $NextMonth, $ThisYear);
	// Calculate date range
	$DateArray=explode(':',$Prefs['DateListings']['params']);
	switch ($DateArray[0]) { // based on the date choice selected
		default:
		case "a": // RPT_GROUP_ALL, skip the date addition to the where statement, all dates in db
			$d = '';
			$fildesc = '';
			break;
		case "b": // RPT_GROUP_RANGE
			$d='';
			$fildesc = ' '.RPT_DATERANGE;
			if ($DateArray[1]<>'') {
				$d .= $df.">='".FormatDateForSQL($DateArray[1])."'";
				$fildesc .= ' '.RPT_FROM.' '.$DateArray[1];
			}
			if ($DateArray[2]<>'') { // a value entered, check
				if (mb_strlen($d)>0) $d .= ' AND ';
				$d .= $df."<='".FormatDateForSQL($DateArray[2])."'";
				$fildesc .= ' '.RPT_TO.' '.$DateArray[1];
			}
			$fildesc .= ';';
			break;
		case "c": // RPT_GROUP_TODAY
			$d = $df."='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.'='.ConvertSQLDate($Today).';';
			break;
		case "d": // RPT_GROUP_WEEK
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')+0, $ThisYear));
			$we = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')+6, $ThisYear));
			$d = $df.">='".$ws."'";
			$d .= " AND ".$df."<='".$we."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ws).' '.RPT_TO.' '.ConvertSQLDate($we).';';
			break;
		case "e": // RPT_GROUP_WTD
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w'), $ThisYear));
			$d = $df.">='".$ws."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ws).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "f": // RPT_GROUP_MONTH
			$ms = date('Y-m-d', mktime(0,0,0, $ThisMonth, 1, $ThisYear));
			$me = date('Y-m-d', mktime(0,0,0, $ThisMonth, $TotalDays, $ThisYear));
			$d = $df.">='".$ms."'";
			$d .= " AND ".$df."<='".$me."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ms).' '.RPT_TO.' '.ConvertSQLDate($me).';';
			break;
		case "g": // RPT_GROUP_MTD
			$ms = date('Y-m-d', mktime(0,0,0, $ThisMonth, 1, $ThisYear));
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, $ThisMonth, 1, $ThisYear))."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ms).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "h": // RPT_GROUP_QUARTER
			$QtrStrt = intval(($ThisMonth-1)/3)*3+1;
			$QtrEnd = intval(($ThisMonth-1)/3)*3+3;
			$TotalDays = cal_days_in_month(CAL_GREGORIAN, $QtrEnd, $ThisYear);
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$qe = date('Y-m-d', mktime(0,0,0, $QtrEnd, $TotalDays, $ThisYear));
			$d = $df.">='".$qs."'";
			$d .= " AND ".$df."<='".$qe."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($qs).' '.RPT_TO.' '.ConvertSQLDate($qe).';';
			break;
		case "i": // RPT_GROUP_QTD
			$QtrStrt = intval(($ThisMonth-1)/3)*3+1;
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$d = $df.">='".$qs."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($qs).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "j": // RPT_GROUP_YEAR
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $ThisYear));
			$ye = date('Y-m-d', mktime(0,0,0, 12, 31, $ThisYear));
			$d = $df.">='".$ys."'";
			$d .= " AND ".$df."<='".$ye."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ys).' '.RPT_TO.' '.ConvertSQLDate($ye).';';
			break;
		case "k": // RPT_GROUP_YTD
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $ThisYear));
			$d = $df.">='".$ys."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ys).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "l": // RPT_GROUP_YESTERDAY
			$d = $df."='".$Yesterday."'";
			$fildesc = ' '.RPT_DATERANGE.'='.ConvertSQLDate($Yesterday).';';
			break;
		case "m": // RPT_GROUP_LASTWEEK
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')-7, $ThisYear));
			$we = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')-1, $ThisYear));
			$d = $df.">='".$ws."'";
			$d .= " AND ".$df."<='".$we."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ws).' '.RPT_TO.' '.ConvertSQLDate($we).';';
			break;
		case "n": // RPT_GROUP_LASTWTD
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')-7, $ThisYear));
			$d = $df.">='".$ws."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ws).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "o": // RPT_GROUP_LASTMONTH
			$ms = date('Y-m-d', mktime(0,0,0, $LastMonth, 1, $ThisYear));
			$me = date('Y-m-d', mktime(0,0,0, $LastMonth, $TotalDaysLast, $ThisYear));
			$d = $df.">='".$ms."'";
			$d .= " AND ".$df."<='".$me."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ms).' '.RPT_TO.' '.ConvertSQLDate($me).';';
			break;
		case "p": // RPT_GROUP_LASTMTD
			$ms = date('Y-m-d', mktime(0,0,0, $LastMonth, 1, $ThisYear));
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, $LastMonth, 1, $ThisYear))."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ms).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "q": // RPT_GROUP_LASTQUARTER
			$QtrStrt = intval(($ThisMonth-1)/3)*3-2;
			$QtrEnd = intval(($ThisMonth-1)/3)*3+0;
			$TotalDays = cal_days_in_month(CAL_GREGORIAN, $QtrEnd, $ThisYear);
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$qe = date('Y-m-d', mktime(0,0,0, $QtrEnd, $TotalDays, $ThisYear));
			$d = $df.">='".$qs."'";
			$d .= " AND ".$df."<='".$qe."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($qs).' '.RPT_TO.' '.ConvertSQLDate($qe).';';
			break;
		case "r": // RPT_GROUP_LASTQTD
			$QtrStrt = intval(($ThisMonth-1)/3)*3-2;
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$d = $df.">='".$qs."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($qs).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "s": // RPT_GROUP_LASTYEAR
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $LastYear));
			$ye = date('Y-m-d', mktime(0,0,0, 12, 31, $LastYear));
			$d = $df.">='".$ys."'";
			$d .= " AND ".$df."<='".$ye."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ys).' '.RPT_TO.' '.ConvertSQLDate($ye).';';
			break;
		case "t": // RPT_GROUP_LASTYTD
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $LastYear));
			$d = $df.">='".$ys."'";
			$d .= " AND ".$df."<='".$Today."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ys).' '.RPT_TO.' '.ConvertSQLDate($Today).';';
			break;
		case "u": // RPT_GROUP_NEXTWEEK
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')+7, $ThisYear));
			$we = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')+13, $ThisYear));
			$d = $df.">='".$ws."'";
			$d .= " AND ".$df."<='".$we."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ws).' '.RPT_TO.' '.ConvertSQLDate($we).';';
			break;
		case "v": // RPT_GROUP_NEXTMONTH
			$ms = date('Y-m-d', mktime(0,0,0, $NextMonth, 1, $ThisYear));
			$me = date('Y-m-d', mktime(0,0,0, $NextMonth, $TotalDaysNext, $ThisYear));
			$d = $df.">='".$ms."'";
			$d .= " AND ".$df."<='".$me."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ms).' '.RPT_TO.' '.ConvertSQLDate($me).';';
			break;
		case "w": // RPT_GROUP_NEXTQUARTER
			$QtrStrt = intval(($ThisMonth-1)/3)*3+4;
			$QtrEnd = intval(($ThisMonth-1)/3)*3+6;
			$TotalDays = cal_days_in_month(CAL_GREGORIAN, $QtrEnd, $ThisYear);
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$qe = date('Y-m-d', mktime(0,0,0, $QtrEnd, $TotalDays, $ThisYear));
			$d = $df.">='".$qs."'";
			$d .= " AND ".$df."<='".$qe."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($qs).' '.RPT_TO.' '.ConvertSQLDate($qe).';';
			break;
		case "x": // RPT_GROUP_NEXTYEAR
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $NextYear));
			$ye = date('Y-m-d', mktime(0,0,0, 12, 31, $NextYear));
			$d = $df.">='".$ys."'";
			$d .= " AND ".$df."<='".$ye."'";
			$fildesc = ' '.RPT_DATERANGE.' '.RPT_FROM.' '.ConvertSQLDate($ys).' '.RPT_TO.' '.ConvertSQLDate($ye).';';
			break;
	}
	$strDate = $d;
	// update the filter description string
	if ($fildesc<>'') {
		$Prefs['filterdesc'] .= $fildesc;
	} else {
		$Prefs['filterdesc'] .= ' '.RPT_DATERANGE.' All Transactions'.';';
	}

	// Fetch the Criteria
	$strCrit = '';
	$filCrit = '';
	if (is_array($Prefs['CritListings'])) while ($FieldValues = array_shift($Prefs['CritListings'])) {
		$Params = explode(':',$FieldValues['params']);
		switch ($Params[1]) {
			case RPT_RANGE:
				if (mb_strlen($strCrit)>0) { $strCrit .= ' AND '; $filCrit .= ' AND '; }
				$t='';
				$f='';
				if (isset($Params[2])) { // a from value entered, check
					$t .= $FieldValues['fieldname'].">='".($Params[2]."'");
					$f .= $FieldValues['displaydesc'].">=".($Params[2]);
				}
				if (isset($Params[3])) { // a to value entered, check
					if (mb_strlen($t)>0) { $t .= ' AND '; $f .= ' AND '; }
					$t .= $FieldValues['fieldname']."<='".($Params[3]."'");
					$f .= $FieldValues['displaydesc']."<=".($Params[3]);
				}
				$strCrit .= $t;
				$filCrit .= $f;
				break;
			case RPT_YES:
			case RPT_TRUE:
			case RPT_ACTIVE:
			case RPT_PRINTED:
				if (mb_strlen($strCrit)>0) $strCrit .= ' AND ';
				$strCrit .= $FieldValues['fieldname'].'=1';
				if (mb_strlen($filCrit)>0) $filCrit .= ' AND ';
				$filCrit .= $FieldValues['displaydesc'].'='.$Params[1];
				break;
			case RPT_NO:
			case RPT_FALSE:
			case RPT_INACTIVE:
			case RPT_UNPRINTED:
				if (mb_strlen($strCrit)>0) $strCrit .= ' AND ';
				$strCrit .= $FieldValues['fieldname'].'=0';
				if (mb_strlen($filCrit)>0) $filCrit .= ' AND ';
				$filCrit .= $FieldValues['displaydesc'].'='.$Params[1];
				break;
			case RPT_STOCK: // TBD field to compare so default to nothing
			case RPT_ASSEMBLY: // TBD field to compare so default to nothing
			case RPT_ALL: // sql default anyway
			default:
		}
	}
	if ($filCrit<>'') $Prefs['filterdesc'] .= ' '.RPT_CRITBY.' '.$filCrit.';';
	// fetch the tables to query
	$strTable = $Prefs['table1'];
	if ($Prefs['table2']) $strTable .= ' INNER JOIN '.$Prefs['table2']. ' ON '.$Prefs['table2criteria'];
	if ($Prefs['table3']) $strTable .= ' INNER JOIN '.$Prefs['table3']. ' ON '.$Prefs['table3criteria'];
	if ($Prefs['table4']) $strTable .= ' INNER JOIN '.$Prefs['table4']. ' ON '.$Prefs['table4criteria'];
	if ($Prefs['table5']) $strTable .= ' INNER JOIN '.$Prefs['table5']. ' ON '.$Prefs['table5criteria'];
	if ($Prefs['table6']) $strTable .= ' INNER JOIN '.$Prefs['table6']. ' ON '.$Prefs['table6criteria'];
	// Build query string and execute
	$sql = "SELECT ".$strField." FROM ".$strTable;
	if ($strCrit AND $strDate) $sql .= ' WHERE '.$strDate.' AND '.$strCrit;
	if (!$strCrit AND $strDate) $sql .= ' WHERE '.$strDate;
	if ($strCrit AND !$strDate) $sql .= ' WHERE '.$strCrit;
	if ($strSort) $sql .= ' ORDER BY '.$strSort;
	$usrMsg['level'] = 'success';
	$usrMsg['data'] = $sql;
	$usrMsg['filterdesc'] = $Prefs['filterdesc'];
//echo '<br />sql='.$sql.'<br /><br />'; exit();
	return $usrMsg;
}

function BuildDataArray($ReportID, $sql, $Prefs) {
	global $Heading, $Seq;
	// first see if we have data
	$Result=DB_query($sql,'','',false,true);
	if (DB_num_rows($Result)==0) return false; // No data so bail now

	// See if we need to group, fetch the group fieldname
	$GrpFieldName = '';
	if (is_array($Prefs['GroupListings'])) while ($Temp = array_shift($Prefs['GroupListings'])) {
		if ($Temp['params']=='1') $GrpFieldName = $Temp['fieldname'];
	}
	// Build the sequence map of retrieved fields, order is as user wants it
	$i=0;
	$GrpField='';
	foreach($Prefs['FieldListings'] as $DataFields) {
		if ($DataFields['visible']) { // match the group fieldname with fetched data fieldname for group totals
			if ($DataFields['fieldname']==$GrpFieldName) $GrpField = $i;
			$Seq[$i]['break'] = $DataFields['columnbreak'];
			$Heading[] = $DataFields['displaydesc']; // fill the heading array
			$Seq[$i]['total'] = $DataFields['params'];
			$Seq[$i]['grptotal'] = '';
			$Seq[$i]['rpttotal'] = '';
			$i++;
		}
	}

	// Generate the output data array
	$RowCnt = 0; // Row counter for output data
	$ColCnt = 1;
	$GrpWorking = false;
	while ($myrow = DB_fetch_row($Result)) {
		// Check to see if a total row needs to be displayed
		if (isset($GrpField)) { // we're checking for group totals, see if this group is complete
			if ($myrow[$GrpField]<>$GrpWorking AND $GrpWorking<>false) { // it's a new group so print totals
				$OutputArray[$RowCnt][0] = 'g:'.$GrpWorking;
				foreach($Seq as $offset=>$TotalCtl) {
					$OutputArray[$RowCnt][$offset+1] = $TotalCtl['grptotal'];
					$Seq[$offset]['grptotal'] = ''; // reset the total
				}
				$RowCnt++; // go to next row
			}
			$GrpWorking = $myrow[$GrpField]; // set to new grouping value
		}
		$OutputArray[$RowCnt][0] = 'd'; // let the display class know its a data element
		foreach($Seq as $key=>$TableCtl) { //
			// insert data into output array and set to next column
			$OutputArray[$RowCnt][$ColCnt] = $myrow[$key];
			$ColCnt++;
			if ($TableCtl['total']) { // add to the running total if need be
				$Seq[$key]['grptotal'] += $myrow[$key];
				$Seq[$key]['rpttotal'] += $myrow[$key];
			}
		}
		$RowCnt++;
		$ColCnt = 1;
	}
	if ($GrpWorking) { // if we collected group data show the final group total
		$OutputArray[$RowCnt][0] = 'g:'.$GrpWorking;
		foreach($Seq as $TotalCtl) {
			if ($TotalCtl['total']=='1') $OutputArray[$RowCnt][$ColCnt] = $TotalCtl['grptotal'];
				else $OutputArray[$RowCnt][$ColCnt] = ' ';
			$ColCnt++;
		}
		$RowCnt++;
		$ColCnt = 1;
	}
	// see if we have a total to send
	$ShowTotals = false;
	foreach($Seq as $TotalCtl) if ($TotalCtl['total']=='1') $ShowTotals = true;
	if ($ShowTotals) {
		$OutputArray[$RowCnt][0] = 'r:'.$Prefs['reportname'];
		foreach($Seq as $TotalCtl) {
			if ($TotalCtl['total']) $OutputArray[$RowCnt][$ColCnt] = $TotalCtl['rpttotal'];
				else $OutputArray[$RowCnt][$ColCnt] = ' ';
			$ColCnt++;
		}
	}
	return $OutputArray;
}

function GenerateCSVFile($Data, $Prefs) {
	global $Heading;
	$CSVOutput = '';
	// Write the column headings
	foreach ($Heading as $mycolumn) { // always enclose in quotes
		$CSVOutput .= '"'.$mycolumn.'",';
	}
	$CSVOutput = mb_substr($CSVOutput,0,-1).chr(10); // Strip the last comma off and add line feed
	// Now write each data line and totals
	foreach ($Data as $myrow) {
		$Action = array_shift($myrow);
		$todo = explode(':',$Action); // contains a letter of the date type and title/groupname
		switch ($todo[0]) {
			case "r": // Report Total
			case "g": // Group Total
				if ($todo[0]=='g') $Desc = 'Group Total for: '; else $Desc = 'Report Total for: ';
				$CSVOutput .= $Desc.$todo[1].chr(10);
				// Now write the total data like any other data row
			case "d": // Data
			default:
				$CSVLine = '';
				foreach ($myrow as $mycolumn) { // check for embedded commas and enclose in quotes
					//although we use mb_strpos elswehere after discussion with Exson this seems pragmatically the best solution
					//http://weberp-accounting.1478800.n4.nabble.com/What-s-the-real-meaning-of-Ports-td4657516.html#a4657518
					if (strpos($mycolumn,',')===false) {
						$CSVLine .= $mycolumn.',';
					} else {
						$CSVLine .= '"'.$mycolumn.'",';
					}
				}
				$CSVLine = mb_substr($CSVLine,0,-1); // Strip the last comma off
		}
		$CSVOutput .= $CSVLine.chr(10);
	}

	$FileSize = mb_strlen($CSVOutput);
	header('Content-type: application/csv');
	header('Content-disposition: attachment; filename="'.$Prefs['reportname'].'.csv"; size='.$FileSize);
	header('Pragma: public');
	header('Cache-Control: public, must-revalidate, max-age=0');
	header('Connection: close');
	header('Expires: '.date('r', time()+60*60));
	header('Last-Modified: '.date('r', time()));
	print $CSVOutput;
	exit();
}

function GeneratePDFFile($Data, $Prefs) {
	$PDF=new PDF();
	$PDF->ReportTable($Data);
	$ReportName = ReplaceNonAllowedCharacters($Prefs['reportname']) .'.pdf';
	$PDF->OutputD($ReportName);
	exit(); // needs to be here to properly render the pdf file.
}


function ReplaceNonAllowedCharacters ($String) {
	$DodgyCharactersArray = array('"',' ', '&',"'");
	$ContainsDodgyCharacters = true;
	while ($ContainsDodgyCharacters == true){

		$ContainsDodgyCharacters = false; //assume all dodgy characters are replaced on the last pass

		foreach ($DodgyCharactersArray as $DodgyCharacter){
			if (mb_strpos($String,$DodgyCharacter,0)){
				$StrPointer = mb_strpos($String,$DodgyCharacter,0);
				$String = mb_substr($String,0,$StrPointer) . '_' . mb_substr($String,$StrPointer+1);
				$ContainsDodgyCharacters=true;
			}
		}
	}
	return $String;
}
?>