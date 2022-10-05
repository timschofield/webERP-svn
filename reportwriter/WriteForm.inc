<?php
// for compatability with extended char sets
if ($_SESSION['Language']=='zh_CN'){
	include($PathPrefix . 'includes/FPDF_Chinese.php');
} elseif ($_SESSION['Language']=='ja_JP'){
	include($PathPrefix . 'includes/FPDF_Japanese.php');
} elseif ($_SESSION['Language']=='ko_KR'){
	include($PathPrefix . 'includes/FPDF_Korean.php');
} else {
	class PDF_Language extends FPDF { }
}

class PDF extends PDF_Language {
	var $y0; // current y position
	var $x0; // current x position
	var $pageY; // y value of bottom of page less bottom margin
	var $PageCnt; // tracks the page count for correct page numbering for multipage and multiform printouts

	function PDF() {
		global $Prefs;
		define('RowSpace',2); // define separation between the heading rows
		$PaperSize = explode(':',$Prefs['papersize']);
		$this->PDF_Language($Prefs['paperorientation'], 'mm', $PaperSize[0]);
		if ($Prefs['paperorientation']=='P') { // Portrait - calculate max page height
			$this->pageY = $PaperSize[2]-$Prefs['marginbottom'];
		} else { // Landscape
			$this->pageY = $PaperSize[1]-$Prefs['marginbottom'];
		}
		$this->SetMargins($Prefs['marginleft'], $Prefs['margintop'], $Prefs['marginright']);
		$this->SetAutoPageBreak(0, $Prefs['marginbottom']);
		$this->SetFont('Helvetica');
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.35); // 1 point

		if ($_SESSION['Language']=='zh_CN'){
			$this->AddBig5Font();
		} elseif ($_SESSION['Language']=='ja_JP'){
			$this->AddSJISFont();
		} elseif ($_SESSION['Language']=='ko_KR'){
			$this->AddUHCFont();
		}
	}

	function Header() { // prints all static information on the page
		global $FieldListings, $FieldValues;
		$i=0; // set sequence to pull data fields should be sorted by seqnum
		foreach ($FieldListings as $SingleObject) {
			switch ($SingleObject['params']['index']) {
				case "Data":
					$SingleObject['params']['TextField'] = $FieldValues[$i]; // fill the data to display
					$i++; // increment to the next field
				case "TBlk": // same operation as page number
				case "Text":
				case "CDta":
				case "CBlk":
				case "PgNum":
					$this->FormText($SingleObject['params']);
					break;

				case "Img":
					$this->FormImage($SingleObject['params']);
					break;
				case "Line":
					$this->FormLine($SingleObject['params']);
					break;
				case "Rect":
					$this->FormRect($SingleObject['params']);
					break;

				default: // do nothing
			}
		}
	}

	function Footer() { // Prints totals at end of last page
		global $FieldListings;
		foreach ($FieldListings as $SingleObject) {
			if ($SingleObject['params']['index']=='Ttl') $this->FormText($SingleObject['params']);
		}
	}

	function FormImage($Params) {
		if (is_file(DefRptPath.$Params['filename'])) {
			$this->Image(DefRptPath.$Params['filename'],$Params['LineXStrt'],$Params['LineYStrt'],$Params['BoxWidth'],$Params['BoxHeight']);
		} else { // no image was found at the specified path, draw a box
			// check for any data entered
			if (!isset($Params['LineXStrt'])) { // then no information was entered for this entry, set some defaults
				$Params['LineXStrt'] = '10';
				$Params['LineYStrt'] = '10';
				$Params['BoxWidth'] = '50';
				$Params['BoxHeight'] = '20';
			}
			$this->SetXY($Params['LineXStrt'],$Params['LineYStrt']);
			$this->SetFont('Helvetica','','10');
			$this->SetTextColor(255,0,0);
			$this->SetDrawColor(255,0,0);
			$this->SetLineWidth(0.35);
			$this->SetFillColor(255);
			$this->Cell($Params['BoxWidth'],$Params['BoxHeight'],FRM_NOIMAGE,1,0,'C');
		}
	}

	function FormLine($Params) {
		if (!isset($Params['LineXStrt'])) return;	// don't do anything if data array has not been set
		if ($Params['Line']=='2') $RGB=$Params['BrdrRed'].':'.$Params['BrdrGreen'].':'.$Params['BrdrBlue'];
			else $RGB=$Params['BrdrColor'];
		$FC = explode(':',$RGB);
		$this->SetDrawColor($FC[0],$FC[1],$FC[2]);
		$this->SetLineWidth($Params['LineSize']*0.35);
		if ($Params['LineType']=='1') { // Horizontal
			$XEnd = $Params['LineXStrt']+$Params['HLength'];
			$YEnd = $Params['LineYStrt'];
		} elseif ($Params['LineType']=='2') { // Vertical
			$XEnd = $Params['LineXStrt'];
			$YEnd = $Params['LineYStrt']+$Params['VLength'];
		}  elseif ($Params['LineType']=='3') { // Custom
			$XEnd = $Params['LineXEnd'];
			$YEnd = $Params['LineYEnd'];
		}
		$this->Line($Params['LineXStrt'],$Params['LineYStrt'],$XEnd,$YEnd);
	}

	function FormRect($Params) {
		if (!isset($Params['LineXStrt'])) return;	// don't do anything if data array has not been set
		$DrawFill = '';
		if ($Params['Line']=='0') {  // No Border
			$this->SetDrawColor(255);
			$this->SetLineWidth(0);
		} else {
			$DrawFill = 'D';
			if ($Params['Line']=='2') $RGB=$Params['BrdrRed'].':'.$Params['BrdrGreen'].':'.$Params['BrdrBlue'];
				else $RGB=$Params['BrdrColor'];
			$FC = explode(':',$RGB);
			$this->SetDrawColor($FC[0],$FC[1],$FC[2]);
			$this->SetLineWidth($Params['LineSize']*0.35);
		}
		if ($Params['Fill']=='0') {  // Set Fill Color
			$this->SetFillColor(255);
		} else {
			$DrawFill .= 'F';
			if ($Params['Fill']=='2') $RGB=$Params['FillRed'].':'.$Params['FillGreen'].':'.$Params['FillBlue'];
				else $RGB=$Params['FillColor'];
			$FC = explode(':',$RGB);
			$this->SetFillColor($FC[0],$FC[1],$FC[2]);
		}
		$this->Rect($Params['LineXStrt'],$Params['LineYStrt'],$Params['BoxWidth'],$Params['BoxHeight'],$DrawFill);
	}

	function FormText($Params) {
		if (!isset($Params['LineXStrt'])) return;	// don't do anything if data array has not been set
		$this->SetXY($Params['LineXStrt'],$Params['LineYStrt']);
		$this->SetFont($Params['Font'],'',$Params['FontSize']);
		if ($Params['Color']=='2') $RGB=$Params['FontRed'].':'.$Params['FontGreen'].':'.$Params['FontBlue'];
			else $RGB=$Params['FontColor'];
		$FC = explode(':',$RGB);
		$this->SetTextColor($FC[0],$FC[1],$FC[2]);
		if ($Params['Line']=='0') {  // No Border
			$Border = '0';
		} else {
			$Border = '1';
			if ($Params['Line']=='2') $RGB=$Params['BrdrRed'].':'.$Params['BrdrGreen'].':'.$Params['BrdrBlue'];
				else $RGB=$Params['BrdrColor'];
			$FC = explode(':',$RGB);
			$this->SetDrawColor($FC[0],$FC[1],$FC[2]);
			$this->SetLineWidth($Params['LineSize']*0.35);
		}
		if ($Params['Fill']=='0') {  // No Fill
			$Fill = '0';
		} else {
			$Fill = '1';
			if ($Params['Fill']=='2') $RGB=$Params['FillRed'].':'.$Params['FillGreen'].':'.$Params['FillBlue'];
				else $RGB=$Params['FillColor'];
			$FC = explode(':',$RGB);
			$this->SetFillColor($FC[0],$FC[1],$FC[2]);
		}
		if ($Params['index']<>'PgNum') $TextField = $Params['TextField'];
			else $TextField = $this->PageNo()-$this->PageCnt;
		if (isset($Params['Processing'])) $TextField = $this->ProcessData($TextField, $Params['Processing']);
		if ($Params['index']=='TBlk' OR $Params['index']=='CBlk') { // then it's multi-cell data
			$this->MultiCell($Params['BoxWidth'],$Params['BoxHeight'],$TextField,$Border,$Params['FontAlign'],$Fill);
		} else { // print a single data cell
			$this->Cell($Params['BoxWidth'],$Params['BoxHeight'],$TextField,$Border,0,$Params['FontAlign'],$Fill);
		}
	}

	function FormTable($Params) {
		if ($Params['Fill']=='2') $RGB=$Params['FillRed'].':'.$Params['FillGreen'].':'.$Params['FillBlue'];
			else $RGB=$Params['FillColor'];
		$FC = explode(':',$RGB);
		if ($Params['Line']=='2') $RGB=$Params['BrdrRed'].':'.$Params['BrdrGreen'].':'.$Params['BrdrBlue'];
			else $RGB=$Params['BrdrColor'];
		$DC = explode(':',$RGB);
		$this->SetDrawColor($DC[0],$DC[1],$DC[2]);
		$this->SetLineWidth($Params['LineSize']*0.35);
		$MaxBoxY = $Params['LineYStrt']+$Params['BoxHeight']; // figure the max y position on page
		$FillThisRow=false;
		$MaxRowHt = 0; //track the tallest row to estimate page breaks
		$this->y0 = $Params['LineYStrt'];
		foreach($Params['Data'] as $index=>$myrow) {
			// See if we are at or near the end of the table box size
			if (($this->y0+$MaxRowHt)>$MaxBoxY) { // need a new page
				$this->DrawTableLines($Params, $MaxBoxY); // Now draw the box and lines around the table
				$this->AddPage();
				$this->y0 = $Params['LineYStrt'];
				$MaxRowHt = $this->ShowTableRow($Params, $Params['Data'][0], $Heading=true);
			}
			$this->SetLeftMargin($Params['LineXStrt']);
			$this->SetXY($Params['LineXStrt'], $this->y0);
			// Fill with Fill color if it's time
			if ($FillThisRow AND $Params['Fill']) $this->SetFillColor($FC[0],$FC[1],$FC[2]);
				else $this->SetFillColor(255);
			$this->Cell($Params['BoxWidth'],$MaxBoxY-$this->y0,'',0,0,'L',1);
			// fill in the data
			if ($index==0) $MaxRowHt = $this->ShowTableRow($Params, $myrow, $Heading=true);
				else $MaxRowHt = $this->ShowTableRow($Params, $myrow, $Heading=false);
			$FillThisRow=!$FillThisRow;
		}
		$this->DrawTableLines($Params, $MaxBoxY); // Now draw the box and lines around the table
	}

	function ShowTableRow($Params, $myrow, $Heading) {
		$maxY = $this->y0; // set to current top of row
		$Col=0;
		$NextXPos = $Params['LineXStrt'];
		foreach ($myrow as $key=>$value) {
			if ($Params['Seq'][$Col]['TblShow']) {
				$this->SetLeftMargin($NextXPos);
				$this->SetXY($NextXPos, $this->y0);
				$this->SetFont($Params['Seq'][$Col]['Font'],'',$Params['Seq'][$Col]['FontSize']);
				$TC = explode(':',$Params['Seq'][$Col]['FontColor']);
				$this->SetTextColor($TC[0],$TC[1],$TC[2]);
				$CellHeight = ($Params['Seq'][$Col]['FontSize']+RowSpace)*0.35;
//				if ($trunc) $value=$this->TruncData($value, $Params['Seq'][$Col]['TblColWidth']);
				if ($Heading) { // center the heading
					$FA='C';
				} else {
					$FA=$Params['Seq'][$Col]['FontAlign'];
					if (isset($Params['Seq'][$Col]['Processing'])) $value = $this->ProcessData($value, $Params['Seq'][$Col]['Processing']);
				}
				$this->MultiCell($Params['Seq'][$Col]['TblColWidth'],$CellHeight,$value,0,$FA);
				if ($this->GetY()>$maxY) $maxY = $this->GetY();
				$NextXPos += $Params['Seq'][$Col]['TblColWidth'];
			}
			$Col++;
		}
		$ThisRowHt = $maxY-$this->y0; // seee how tall this row was
		if ($ThisRowHt>$MaxRowHt) $MaxRowHt = $ThisRowHt; // keep that largest row so far to track pagination
		$this->y0 = $maxY; // set y position to largest value for next row
		if ($Heading AND $Params['Line']) { // then it's the heading draw a line after if fill is set
			$this->Line($Params['LineXStrt'],$maxY,$Params['LineXStrt']+$Params['BoxWidth'],$maxY);
			$this->y0 = $this->y0+($Params['LineSize']*0.35);
		}
		return $MaxRowHt;
	}

	function DrawTableLines($Params, $MaxBoxY) {
		// Fill the remaining part of the table with white
		if ($this->y0<$MaxBoxY) {
			$this->SetLeftMargin($Params['LineXStrt']);
			$this->SetXY($Params['LineXStrt'], $this->y0);
			$this->SetFillColor(255);
			$this->Cell($Params['BoxWidth'],$MaxBoxY-$this->y0,'',0,0,'L',1);
		}
		if ($Params['Line']=='0') return;  // return if draw lines is turned off
		$this->Rect($Params['LineXStrt'],$Params['LineYStrt'],$Params['BoxWidth'],$Params['BoxHeight']);
		$NextXPos = $Params['LineXStrt'];
		foreach($Params['Seq'] as $index=>$value) { // Draw the vertical lines
			$this->Line($NextXPos,$Params['LineYStrt'],$NextXPos,$Params['LineYStrt']+$Params['BoxHeight']);
			$NextXPos += $value['TblColWidth'];
		}
		return;
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

	function ProcessData($strData, $Process) {
		switch ($Process) {
			case "uc": return mb_strtoupper($strData);
			case "lc": return strtolower($strData);
			case "neg": return -$strData;
			case "rnd2d": return locale_number_format($strData, $_SESSION['CompanyRecord']['decimalplaces']);
			case "dlr": return '$ '.locale_number_format($strData, $_SESSION['CompanyRecord']['decimalplaces']);
			case "euro": return chr(128).' '.locale_number_format($strData, $_SESSION['CompanyRecord']['decimalplaces']); // assumes standard FPDF fonts
			default: return $strData; // do nothing if Process not recognized
		}
	}

} // end class

function BuildPDF($ReportID, $Prefs) {
	global $FieldListings, $FieldValues;
	// first fetch all the fields we need to display
	$FieldListings = '';
	$sql = "SELECT seqnum, params FROM ".DBRptFields."
		WHERE reportid=".$ReportID." AND entrytype='fieldlist' AND visible=1
		ORDER BY seqnum";
	$Result=DB_query($sql,'','',false,true);
	while ($FieldValues = DB_fetch_assoc($Result)) {
		$FieldValues['params'] = unserialize($FieldValues['params']);
		$FieldListings[] = $FieldValues;
	}
	// check for at least one field selected to show
	if (!$FieldListings) { // No fields are checked to show, that's bad
		$usrMsg['message'] = RPT_NOROWS;
		$usrMsg['level'] = 'error';
		return $usrMsg;
	}

	// Let's build the sql field list for the general data fields (not totals or tables)
	$strField='';
	foreach ($FieldListings as $OneField) { // check for a data field and build sql field list
		if ($OneField['params']['index']=='Data') { // then it's data field make sure it's not empty
			if ($OneField['params']['DataField']<>'') {
				$strField .= $OneField['params']['DataField'].', ';
			} else { // the field is empty, bad news, error and exit
				$usrMsg['message'] = RPT_EMPTYFIELD.$OneField['seqnum'];
				$usrMsg['level'] = 'error';
				return $usrMsg;
			}
		}
	}
	// strip the extra comma, space and continue
	$strField = mb_substr($strField,0,-2);

	// fetch date filter info
	$df = $Prefs['DateListings']['fieldname'];
	$Today = date('Y-m-d', time());
	$ThisDay = mb_substr($Today,8,2);
	$ThisMonth = mb_substr($Today,5,2);
	$ThisYear = mb_substr($Today,0,4);
	// find total number of days in this month
	if ($ThisMonth=='04' OR $ThisMonth=='06' OR $ThisMonth=='09' OR $ThisMonth=='11') {
		$TotalDays=30;
	} elseif ($ThisMonth=='02' AND date('L')) {
		$TotalDays=29;
	} // Leap year
	 elseif ($ThisMonth=='02' AND !date('L')) {
		$TotalDays=28;
	} else {
		$TotalDays=31;
	}
	// Calculate date range
	$DateArray=explode(':',$Prefs['DateListings']['params']);
	switch ($DateArray[0]) { // based on the date choice selected
		default:
		case "a": // RPT_GROUP_ALL, skip the date addition to the where statement, all dates in db
			$d = '';
			break;
		case "b": // RPT_GROUP_RANGE
			$d='';
			if ($DateArray[1]<>'') $d .= $df.">='".FormatDateForSQL($DateArray[1])."'";
			if ($DateArray[2]<>'') { // a value entered, check
				if (mb_strlen($d)>0) $d .= ' AND ';
				$d .= $df."<='".FormatDateForSQL($DateArray[2])."'";
			}
			break;
		case "c": // RPT_GROUP_TODAY
			$d = $df."='".$Today."'";
			break;
		case "d": // RPT_GROUP_WEEK
			$ws = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w'), $ThisYear));
			$we = date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w')+6, $ThisYear));
			$d = $df.">='".$ws."' AND ".$df."<='".$we."'";
			break;
		case "e": // RPT_GROUP_WTD
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, $ThisMonth, date('j')-date('w'), $ThisYear))."' AND ".$df."<='".$Today."'";
			break;
		case "f": // RPT_GROUP_MONTH
			$ms = date('Y-m-d', mktime(0,0,0, $ThisMonth, 1, $ThisYear));
			$me = date('Y-m-d', mktime(0,0,0, $ThisMonth, $TotalDays, $ThisYear));
			$d = $df.">='".$ms."' AND ".$df."<='".$me."'";
			break;
		case "g": // RPT_GROUP_MTD
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, $ThisMonth, 1, $ThisYear))."' AND ".$df."<='".$Today."'";
			break;
		case "h": // RPT_GROUP_QUARTER
			$QtrStrt = intval(($ThisMonth-1)/3)*3+1;
			$QtrEnd = intval(($ThisMonth-1)/3)*3+3;
			if ($QtrEnd==4 OR $QtrEnd==6 OR $QtrEnd==9 OR $QtrEnd==11) {
				$TotalDays=30;
			}
			$qs = date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear));
			$qe = date('Y-m-d', mktime(0,0,0, $QtrEnd, $TotalDays, $ThisYear));
			$d = $df.">='".$qs."' AND ".$df."<='".$qe."'";
			break;
		case "i": // RPT_GROUP_QTD
			$QtrStrt = intval(($ThisMonth-1)/3)*3+1;
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, $QtrStrt, 1, $ThisYear))."' AND ".$df."<='".$Today."'";
			break;
		case "j": // RPT_GROUP_YEAR
			$ys = date('Y-m-d', mktime(0,0,0, 1, 1, $ThisYear));
			$ye = date('Y-m-d', mktime(0,0,0, 12, 31, $ThisYear));
			$d = $df.">='".$ys."' AND ".$df."<='".$ye."'";
			break;
		case "k": // RPT_GROUP_YTD
			$d = $df.">='".date('Y-m-d', mktime(0,0,0, 1, 1, $ThisYear))."' AND ".$df."<='".$Today."'";
			break;
	}
	$strDate = $d;

	// Fetch the Criteria
	$strCrit = '';
	if (is_array($Prefs['CritListings'])) while ($FieldValues = array_shift($Prefs['CritListings'])) {
		$Params = explode(':',$FieldValues['params']);
		switch ($Params[1]) {
			case RPT_RANGE:
				if (mb_strlen($strCrit)>0) $strCrit .= ' AND ';
				$t='';
				if (isset($Params[2])) { // a from value entered, check
					$t .= $FieldValues['fieldname'].">='".($Params[2]."'");
				}
				if (isset($Params[3])) { // a to value entered, check
					if (mb_strlen($t)>0) $t .= ' AND ';
					$t .= $FieldValues['fieldname']."<='".($Params[3]."'");
				}
				$strCrit .= $t;
				break;
			case RPT_YES:
			case RPT_TRUE:
			case RPT_ACTIVE:
			case RPT_PRINTED:
				if (mb_strlen($strCrit)>0) $strCrit .= ' AND ';
				$strCrit .= $FieldValues['fieldname'].'=1';
				break;
			case RPT_NO:
			case RPT_FALSE:
			case RPT_INACTIVE:
			case RPT_UNPRINTED:
				if (mb_strlen($strCrit)>0) $strCrit .= ' AND ';
				$strCrit .= $FieldValues['fieldname'].'=0';
				break;
			case RPT_STOCK: // TBD field to compare so default to nothing
			case RPT_ASSEMBLY: // TBD field to compare so default to nothing
			case RPT_ALL: // sql default anyway
			default:
		}
	}

	// fetch the tables to query
	$sqlTable = $Prefs['table1'];
	if ($Prefs['table2']) $sqlTable .= ' INNER JOIN '.$Prefs['table2']. ' ON '.$Prefs['table2criteria'];
	if ($Prefs['table3']) $sqlTable .= ' INNER JOIN '.$Prefs['table3']. ' ON '.$Prefs['table3criteria'];
	if ($Prefs['table4']) $sqlTable .= ' INNER JOIN '.$Prefs['table4']. ' ON '.$Prefs['table4criteria'];
	if ($Prefs['table5']) $sqlTable .= ' INNER JOIN '.$Prefs['table5']. ' ON '.$Prefs['table5criteria'];
	if ($Prefs['table6']) $sqlTable .= ' INNER JOIN '.$Prefs['table6']. ' ON '.$Prefs['table6criteria'];

	// Build query string and execute
	$sqlCrit = '';
	if ($strCrit AND $strDate) $sqlCrit .= $strDate.' AND '.$strCrit;
	if (!$strCrit AND $strDate) $sqlCrit .= $strDate;
	if ($strCrit AND !$strDate) $sqlCrit .= $strCrit;

	// We now have the sql, find out how many groups in the query (to determine the number of forms)
	$PageBreakField = $Prefs['GroupListings'][0]['fieldname'];
	$sql = "SELECT ".$PageBreakField." FROM ".$sqlTable;
	if ($sqlCrit) $sql .=' WHERE '.$sqlCrit;
	$sql .=" GROUP BY ".$PageBreakField;
	// execute sql to see if we have data
	$Result=DB_query($sql,'','',false,true);
	if (DB_num_rows($Result)==0) {
		$usrMsg['message'] = RPT_NOROWS;
		$usrMsg['level'] = 'error';
		return $usrMsg;
	}
	// create an array for each form
	while ($Temp = DB_fetch_array($Result)) $FormPageID[] = $Temp[0];

	// retrieve the company information, first single fields
	$strTxtBlk=''; // Build the fieldlist
	foreach ($FieldListings as $OneField) { // check for a data field and build sql field list
		if ($OneField['params']['index']=='CDta') { // then it's data field make sure it's not empty
			if ($OneField['params']['DataField']<>'') {
				$strTxtBlk .= $OneField['params']['DataField'].', ';
			} else { // the field is empty, bad news, error and exit
				$usrMsg['message'] = RPT_EMPTYFIELD.$OneField['seqnum'];
				$usrMsg['level'] = 'error';
				return $usrMsg;
			}
		}
	}
	if ($strTxtBlk<>'') { // then we have data, fill in TextField value with data
		$strTxtBlk = mb_substr($strTxtBlk,0,-2);
		$sql = "SELECT ".$strTxtBlk." FROM ".CompanyDataBase." LIMIT 1;";
		$Result=DB_query($sql,'','',false,true);
		$CoyData = DB_fetch_row($Result);
		$i=0;
		foreach ($FieldListings as $key=>$SingleObject) {
			if ($SingleObject['params']['index']=='CDta') {
				$FieldListings[$key]['params']['TextField'] = $CoyData[$i];
				$i++;
			}
		}
	}
	// Now the Company Data Blocks
	foreach ($FieldListings as $key=>$SingleObject) {
		if ($SingleObject['params']['index']=='CBlk') {
			if (!$SingleObject['params']['Seq']) {
				$usrMsg['message'] = RPT_EMPTYFIELD.$SingleObject['seqnum'];
				$usrMsg['level'] = 'error';
				return $usrMsg;
			}
			$strTxtBlk=''; // Build the fieldlist
			foreach ($SingleObject['params']['Seq'] as $OneField) $strTxtBlk .= $OneField['TblField'].', ';
			$strTxtBlk = mb_substr($strTxtBlk,0,-2);
			$sql = "SELECT ".$strTxtBlk." FROM ".CompanyDataBase." LIMIT 1;";
			$Result=DB_query($sql,'','',false,true);
			$TxtBlkValues = DB_fetch_row($Result);
			$TextField = '';
			foreach($TxtBlkValues as $t=>$Temp) $TextField .= $Temp.AddSep($SingleObject['params']['Seq'][$t]['Processing']);
			$FieldListings[$key]['params']['TextField'] = $TextField;
		}
	}
	// Generate a form for each group element
	$PDF=new PDF();
	foreach ($FormPageID as $formNum=>$Fvalue) {
		// find the single line data from the query for the current form page
		if ($sqlCrit) $TrailingSQL=" FROM ".$sqlTable." WHERE ".$sqlCrit." AND ".$PageBreakField."='".$Fvalue."'";
			else $TrailingSQL =" FROM ".$sqlTable." WHERE ".$PageBreakField."='".$Fvalue."'";
		$sql = "SELECT " . $strField . $TrailingSQL;
		$Result=DB_query($sql,'','',false,true);
		$FieldValues = DB_fetch_row($Result);
		// Build the text block strings
		foreach ($FieldListings as $key=>$SingleObject) {
			if ($SingleObject['params']['index']=='TBlk') {
				if (!$SingleObject['params']['Seq']) {
					$usrMsg['message'] = RPT_EMPTYFIELD.$SingleObject['seqnum'];
					$usrMsg['level'] = 'error';
					return $usrMsg;
				}
				$strTxtBlk=''; // Build the fieldlist
				foreach ($SingleObject['params']['Seq'] as $OneField) $strTxtBlk .= $OneField['TblField'].', ';
				$strTxtBlk = mb_substr($strTxtBlk,0,-2);
				$sql = "SELECT ".$strTxtBlk.$TrailingSQL;
				$Result=DB_query($sql,'','',false,true);
				$TxtBlkValues = DB_fetch_row($Result);
				$TextField = '';
				foreach($TxtBlkValues as $t=>$Temp) $TextField .= $Temp.AddSep($SingleObject['params']['Seq'][$t]['Processing']);
				$FieldListings[$key]['params']['TextField'] = $TextField;
			}
		}
		$PDF->PageCnt = $PDF->PageNo(); // reset the current page numbering for this new form
		$PDF->AddPage();
		// Pre-load all total fields with 'Continued' label for multipage
		foreach ($FieldListings as $key=>$SingleObject) {
			if ($SingleObject['params']['index']=='Ttl')
				$FieldListings[$key]['params']['TextField'] = 'Continued';
		}
		// Send the table
		foreach ($FieldListings as $SingleObject) {
			if ($SingleObject['params']['index']=='Tbl') {
				if (!$SingleObject['params']['Seq']) {
					$usrMsg['message'] = RPT_EMPTYFIELD.$SingleObject['seqnum'];
					$usrMsg['level'] = 'error';
					return $usrMsg;
				}
				// Build the sql
				$tblField = '';
				$tblHeading = array();
				foreach ($SingleObject['params']['Seq'] as $TableField) {
					$tblField .= $TableField['TblField'].', ';
					$tblHeading[] = $TableField['TblDesc'];
				}
				$tblField = mb_substr($tblField,0,-2); // remove the last two chars (comma and space)
				$SingleObject['params']['Data'][] = $tblHeading; // set the first data element to the headings
				$sql = "SELECT ".$tblField.$TrailingSQL;
				$Result=DB_query($sql,'','',false,true);
				while ($Temp = DB_fetch_assoc($Result)) $SingleObject['params']['Data'][] = $Temp;
				$PDF->FormTable($SingleObject['params']);
			}
		}
		// Set the totals (need to be on last printed page) - Handled in the Footer function in FPDF
		foreach ($FieldListings as $key=>$SingleObject) {
			if ($SingleObject['params']['index']=='Ttl') {
				if (!$SingleObject['params']['Seq']) {
					$usrMsg['message'] = RPT_EMPTYFIELD.$SingleObject['seqnum'];
					$usrMsg['level'] = 'error';
					return $usrMsg;
				}
				$ttlField = '';
				foreach ($SingleObject['params']['Seq'] as $Temp) $ttlField .= $Temp.'+';
				$sql = "SELECT SUM(".mb_substr($ttlField,0,-1).")".$TrailingSQL;
				$Result=DB_query($sql,'','',false,true);
				$Temp = DB_fetch_row($Result);
				$FieldListings[$key]['params']['TextField'] = $Temp[0];
			}
		}
	}
	// Add additional headers needed for MSIE and send page
	header('Pragma: cache');
	header('Cache-Control: public, must-revalidate, max-age=0');
	$PDF->Output($Prefs['reportname'].'.pdf','D');
	exit(); // needs to be here to properly render the pdf file.
}

function AddSep($Process) {
	switch ($Process) {
		case "sp": return ' ';
		case "2sp": return '  ';
		case "comma": return ',';
		case "com-sp": return ', ';
		case "nl": return chr(10);
		case "semi-sp": return '; ';
		default:  // do nothing if Process not recognized
	}
}

?>