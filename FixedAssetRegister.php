<?php
include ('includes/session.php');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetRegister';
$Title = _('Fixed Asset Register');
$csv_output = '';
// Reports being generated in HTML, PDF and CSV/EXCEL format
if (isset($_POST['submit']) or isset($_POST['pdf']) or isset($_POST['csv'])) {
	if (isset($_POST['pdf'])) {
		$PaperSize = 'A4_Landscape';
		include ('includes/PDFStarter.php');
	} else if (empty($_POST['csv'])) {
		include ('includes/header.php');
		echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
			</p>';
	}
	$DateFrom = FormatDateForSQL($_POST['FromDate']);
	$DateTo = FormatDateForSQL($_POST['ToDate']);
	$SQL = "SELECT fixedassets.assetid,
					fixedassets.description,
					fixedassets.longdescription,
					fixedassets.assetcategoryid,
					fixedassets.serialno,
					fixedassetlocations.locationdescription,
					fixedassets.datepurchased,
					fixedassetlocations.parentlocationid,
					fixedassets.assetlocation,
					fixedassets.disposaldate,
					SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS costbfwd,
					SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='depn') THEN fixedassettrans.amount ELSE 0 END) AS depnbfwd,
					SUM(CASE WHEN (fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS periodadditions,
					SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='depn' THEN fixedassettrans.amount ELSE 0 END) AS perioddepn,
					SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='disposal' THEN fixedassettrans.amount ELSE 0 END) AS perioddisposal
			FROM fixedassets
			INNER JOIN fixedassetcategories ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
			INNER JOIN fixedassetlocations ON fixedassets.assetlocation=fixedassetlocations.locationid
			INNER JOIN fixedassettrans ON fixedassets.assetid=fixedassettrans.assetid
			WHERE fixedassets.assetcategoryid " . LIKE . "'" . $_POST['AssetCategory'] . "'
			AND fixedassets.assetid " . LIKE . "'" . $_POST['AssetID'] . "'
			AND fixedassets.assetlocation " . LIKE . "'" . $_POST['AssetLocation'] . "'
			GROUP BY fixedassets.assetid,
					fixedassets.description,
					fixedassets.longdescription,
					fixedassets.assetcategoryid,
					fixedassets.serialno,
					fixedassetlocations.locationdescription,
					fixedassets.datepurchased,
					fixedassetlocations.parentlocationid,
					fixedassets.assetlocation";
	$Result = DB_query($SQL);
	if (isset($_POST['pdf'])) {
		$FontSize = 10;
		$PDF->addInfo('Title', _('Fixed Asset Register'));
		$PDF->addInfo('Subject', _('Fixed Asset Register'));
		$PageNumber = 1;
		$line_height = 12;
		if ($_POST['AssetCategory'] == '%') {
			$AssetCategory = _('All');
		} else {
			$CategorySQL = "SELECT categorydescription FROM fixedassetcategories WHERE categoryid='" . $_POST['AssetCategory'] . "'";
			$CategoryResult = DB_query($CategorySQL);
			$CategoryRow = DB_fetch_array($CategoryResult);
			$AssetCategory = $CategoryRow['categorydescription'];
		}

		if ($_POST['AssetID'] == '%') {
			$AssetDescription = _('All');
		} else {
			$AssetSQL = "SELECT description FROM fixedassets WHERE assetid='" . $_POST['AssetID'] . "'";
			$AssetResult = DB_query($AssetSQL);
			$AssetRow = DB_fetch_array($AssetResult);
			$AssetDescription = $AssetRow['description'];
		}
		PDFPageHeader();
	} elseif (isset($_POST['csv'])) {
		$csv_output = "'Asset ID','Description','Serial Number','Location','Date Acquired','Cost B/Fwd','Period Additions','Depn B/Fwd','Period Depreciation','Cost C/Fwd', 'Accum Depn C/Fwd','NBV','Disposal Value'\n";
	} else {
		echo '<form id="RegisterForm" method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

		echo '';
		echo '<table width="80%" cellspacing="1">
				<tr>
					<th colspan="13">', _('From'), ':', $_POST['FromDate'], ' ', _('to'), ' ', $_POST['ToDate'], '</th>
				<tr>
				<tr>
					<th>', _('Asset ID'), '</th>
					<th>', _('Description'), '</th>
					<th>', _('Serial Number'), '</th>
					<th>', _('Location'), '</th>
					<th>', _('Date Acquired'), '</th>
					<th>', _('Cost B/fwd'), '</th>
					<th>', _('Depn B/fwd'), '</th>
					<th>', _('Additions'), '</th>
					<th>', _('Depn'), '</th>
					<th>', _('Cost C/fwd'), '</th>
					<th>', _('Depn C/fwd'), '</th>
					<th>', _('NBV'), '</th>
					<th>', _('Disposal Value'), '</th>
				</tr>';
	}
	$TotalCostBfwd = 0;
	$TotalCostCfwd = 0;
	$TotalDepnBfwd = 0;
	$TotalDepnCfwd = 0;
	$TotalAdditions = 0;
	$TotalDepn = 0;
	$TotalDisposals = 0;
	$TotalNBV = 0;

	while ($MyRow = DB_fetch_array($Result)) {
		/*
		 * $Ancestors = array();
		 $Ancestors[0] = $MyRow['locationdescription'];
		 $i = 0;
		 while ($Ancestors[$i] != '') {
		 $LocationSQL = "SELECT parentlocationid from fixedassetlocations where locationdescription='" . $Ancestors[$i] . "'";
		 $LocationResult = DB_query($LocationSQL);
		 $LocationRow = DB_fetch_array($LocationResult);
		 $ParentSQL = "SELECT locationdescription from fixedassetlocations where locationid='" . $LocationRow['parentlocationid'] . "'";
		 $ParentResult = DB_query($ParentSQL);
		 $ParentRow = DB_fetch_array($ParentResult);
		 ++$i;
		 $Ancestors[$i] = $ParentRow['locationdescription'];
		 }
		*/
		if (Date1GreaterThanDate2(ConvertSQLDate($MyRow['disposaldate']), $_POST['FromDate']) or $MyRow['disposaldate'] = '0000-00-00') {

			if ($MyRow['disposaldate'] != '0000-00-00' and Date1GreaterThanDate2($_POST['ToDate'], ConvertSQLDate($MyRow['disposaldate']))) {
				/*The asset was disposed during the period */
				$CostCfwd = 0;
				$AccumDepnCfwd = 0;
			} else {
				$CostCfwd = $MyRow['periodadditions'] + $MyRow['costbfwd'];
				$AccumDepnCfwd = $MyRow['perioddepn'] + $MyRow['depnbfwd'];
			}

			if (isset($_POST['pdf'])) {

				$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 30 - $Left_Margin, $FontSize, $MyRow['assetid']);
				$LeftOvers = $PDF->addTextWrap($XPos + 30, $YPos, 150 - $Left_Margin, $FontSize, $MyRow['description']);
				$LeftOvers = $PDF->addTextWrap($XPos + 180, $YPos, 40 - $Left_Margin, $FontSize, $MyRow['serialno']);

				$LeftOvers = $PDF->addTextWrap($XPos + 220, $YPos, 50 - $Left_Margin, $FontSize, ConvertSQLDate($MyRow['datepurchased']));
				$LeftOvers = $PDF->addTextWrap($XPos + 270, $YPos, 70, $FontSize, locale_number_format($MyRow['costbfwd'], 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 340, $YPos, 70, $FontSize, locale_number_format($MyRow['depnbfwd'], 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 410, $YPos, 70, $FontSize, locale_number_format($MyRow['periodadditions'], 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 480, $YPos, 70, $FontSize, locale_number_format($MyRow['perioddepn'], 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 550, $YPos, 70, $FontSize, locale_number_format($CostCfwd, 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 620, $YPos, 70, $FontSize, locale_number_format($AccumDepnCfwd, 0), 'right');
				$LeftOvers = $PDF->addTextWrap($XPos + 690, $YPos, 70, $FontSize, locale_number_format($CostCfwd - $AccumDepnCfwd, 0), 'right');

				$YPos = $YPos - (0.8 * $line_height);
				if ($YPos < $Bottom_Margin + $line_height) {
					PDFPageHeader();
				}
			} elseif (isset($_POST['csv'])) {
				$csv_output.= $MyRow['assetid'] . ',' . $MyRow['longdescription'] . ',' . $MyRow['serialno'] . ',' . $MyRow['locationdescription'] . ',' . $MyRow['datepurchased'] . ',' . $MyRow['costbfwd'] . ',' . $MyRow['periodadditions'] . ',' . $MyRow['depnbfwd'] . ',' . $MyRow['perioddepn'] . ',' . $CostCfwd . ',' . $AccumDepnCfwd . ',' . ($CostCfwd - $AccumDepnCfwd) . ',' . $MyRow['perioddisposal'] . "\n";

			} else {
				echo '<tr class="striped_row">
						<td class="top">', $MyRow['assetid'], '</td>
						<td class="top">', $MyRow['longdescription'], '</td>
						<td class="top">', $MyRow['serialno'], '</td>
						<td>', $MyRow['locationdescription'], '<br /></td>
						<td class="top">', ConvertSQLDate($MyRow['datepurchased']), '</td>
						<td class="number top">', locale_number_format($MyRow['costbfwd'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($MyRow['depnbfwd'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($MyRow['periodadditions'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($MyRow['perioddepn'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($CostCfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($CostCfwd - $AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number top">', locale_number_format($MyRow['perioddisposal'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					</tr>';
			}
		} // end of if the asset was either not disposed yet or disposed after the start date
		$TotalCostBfwd+= $MyRow['costbfwd'];
		$TotalCostCfwd+= ($MyRow['costbfwd'] + $MyRow['periodadditions']);
		$TotalDepnBfwd+= $MyRow['depnbfwd'];
		$TotalDepnCfwd+= ($MyRow['depnbfwd'] + $MyRow['perioddepn']);
		$TotalAdditions+= $MyRow['periodadditions'];
		$TotalDepn+= $MyRow['perioddepn'];
		$TotalDisposals+= $MyRow['perioddisposal'];

		$TotalNBV+= ($CostCfwd - $AccumDepnCfwd);
	}

	if (isset($_POST['pdf'])) {
		$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 300 - $Left_Margin, $FontSize, _('TOTAL'));
		$LeftOvers = $PDF->addTextWrap($XPos + 270, $YPos, 70, $FontSize, locale_number_format($TotalCostBfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 340, $YPos, 70, $FontSize, locale_number_format($TotalDepnBfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 410, $YPos, 70, $FontSize, locale_number_format($TotalAdditions, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 480, $YPos, 70, $FontSize, locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 550, $YPos, 70, $FontSize, locale_number_format($TotalCostCfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 620, $YPos, 70, $FontSize, locale_number_format($TotalDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($XPos + 690, $YPos, 70, $FontSize, locale_number_format($TotalNBV, $_SESSION['CompanyRecord']['decimalplaces']), 'right');

		$PDF->Output($_SESSION['DatabaseName'] . '_Asset Register_' . date('Y-m-d') . '.pdf', 'I');
		exit;
	} elseif (isset($_POST['csv'])) {
		$FileName = $_SESSION['reports_dir'] . '/FixedAssetRegister_' . Date('Y-m-d') . '.csv';
		$csvFile = fopen($FileName, 'w');
		$i = fwrite($csvFile, $csv_output);
		header('Location: ' . $_SESSION['reports_dir'] . '/FixedAssetRegister_' . Date('Y-m-d') . '.csv');

	} else {
		//Total Values
		echo '<tr class="total_row">
				<td colspan="5">', _('TOTAL'), '</td>
				<td class="number">', locale_number_format($TotalCostBfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalDepnBfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalAdditions, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalCostCfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalNBV, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($TotalDisposals, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>
		</table>';

		echo '<input type="hidden" name="FromDate" value="', $_POST['FromDate'], '" />';
		echo '<input type="hidden" name="ToDate" value="', $_POST['ToDate'], '" />';
		echo '<input type="hidden" name="AssetCategory" value="', $_POST['AssetCategory'], '" />';
		echo '<input type="hidden" name="AssetID" value="', $_POST['AssetID'], '" />';
		echo '<input type="hidden" name="AssetLocation" value="', $_POST['AssetLocation'], '" />';

		echo '<div class="centre">
				<input type="submit" name="pdf" value="', _('Print as a pdf'), '" />
				<input type="submit" name="csv" value="', _('Print as CSV'), '" />
			</div>
		</form>';
	}
} else {
	include ('includes/header.php');
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<form id="RegisterForm" method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<fieldset>
			<legend>', _('Report Criteria'), '</legend>';

	$Result = DB_query("SELECT categoryid,categorydescription FROM fixedassetcategories");
	echo '<field>
			<label for="AssetCategory">', _('Asset Category'), '</label>
			<select name="AssetCategory">
				<option value="%">', _('ALL'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetCategory']) and $MyRow['categoryid'] == $_POST['AssetCategory']) {
			echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		}
	}
	echo '</select>
		</field>';

	$SQL = "SELECT  locationid, locationdescription FROM fixedassetlocations";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="AssetLocation">', _('Asset Location'), '</label>
			<select name="AssetLocation">
				<option value="%">', _('ALL'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetLocation']) and $MyRow['locationid'] == $_POST['AssetLocation']) {
			echo '<option selected="selected" value="', $MyRow['locationid'], '">', $MyRow['locationdescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['locationid'], '">', $MyRow['locationdescription'], '</option>';
		}
	}
	echo '</select>
		</field>';

	$SQL = "SELECT assetid, description FROM fixedassets";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="AssetID">', _('Asset'), '</label>
			<select name="AssetID">
				<option value="%">', _('ALL'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetID']) and $MyRow['assetid'] == $_POST['AssetID']) {
			echo '<option selected="selected" value="', $MyRow['assetid'], '">', $MyRow['assetid'], ' - ', $MyRow['description'], '</option>';
		} else {
			echo '<option value="', $MyRow['assetid'], '">', $MyRow['assetid'], ' - ', $MyRow['description'], '</option>';
		}
	}
	echo '</select>
		</field>';

	if (empty($_POST['FromDate'])) {
		$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d'), date('Y') - 1));
	}
	if (empty($_POST['ToDate'])) {
		$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
	}

	echo '<field>
			<label for="FromDate">', _(' From Date'), '</label>
			<input type="text" class="date" name="FromDate" required="required" maxlength="10" size="11" value="', $_POST['FromDate'], '" />
		</field>';

	echo '<field>
			<label for="ToDate">', _('To Date '), '</label>
			<input type="text" class="date" name="ToDate" required="required" maxlength="10" size="11" value="', $_POST['ToDate'], '" />
		</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Show Assets'), '" />
			<input type="submit" name="pdf" value="', _('Print as a pdf'), '" />
			<input type="submit" name = "csv" value="', _('Print as CSV'), '" />
		</div>
	</form>';
}
include ('includes/footer.php');

function PDFPageHeader() {
	global $PageNumber, $PDF, $XPos, $YPos, $Page_Height, $Page_Width, $Top_Margin, $Bottom_Margin, $FontSize, $Left_Margin, $Right_Margin, $line_height, $AssetDescription, $AssetCategory;

	if ($PageNumber > 1) {
		$PDF->newPage();
	}

	$FontSize = 10;
	$YPos = $Page_Height - $Top_Margin;
	$XPos = 0;
	$PDF->addJpegFromFile($_SESSION['LogoFile'], $XPos + 20, $YPos - 50, 0, 60);

	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos, 240, $FontSize, $_SESSION['CompanyRecord']['coyname']);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 1), 240, $FontSize, _('Asset Category') . ' ' . $AssetCategory);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 2), 240, $FontSize, _('Asset Location') . ' ' . $_POST['AssetLocation']);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 3), 240, $FontSize, _('Asset ID') . ': ' . $AssetDescription);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 4), 240, $FontSize, _('From') . ': ' . $_POST['FromDate']);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 5), 240, $FontSize, _('To') . ': ' . $_POST['ToDate']);
	$LeftOvers = $PDF->addTextWrap($Page_Width - $Right_Margin - 240, $YPos - ($line_height * 7), 240, $FontSize, _('Page') . ' ' . $PageNumber);

	$YPos-= 60;

	$YPos-= 2 * $line_height;
	//Note, this is ok for multilang as this is the value of a Select, text in option is different
	$YPos-= (2 * $line_height);

	/*Draw a rectangle to put the headings in     */
	$YTopLeft = $YPos + $line_height;
	$PDF->line($Left_Margin, $YPos + $line_height, $Page_Width - $Right_Margin, $YPos + $line_height);
	$PDF->line($Left_Margin, $YPos + $line_height, $Left_Margin, $YPos - $line_height);
	$PDF->line($Left_Margin, $YPos - $line_height, $Page_Width - $Right_Margin, $YPos - $line_height);
	$PDF->line($Page_Width - $Right_Margin, $YPos + $line_height, $Page_Width - $Right_Margin, $YPos - $line_height);

	/*set up the headings */
	$FontSize = 10;
	$XPos = $Left_Margin + 1;
	$YPos-= (0.8 * $line_height);
	$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 30, $FontSize, _('Asset'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 30, $YPos, 150, $FontSize, _('Description'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 180, $YPos, 40, $FontSize, _('Serial No.'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 220, $YPos, 50, $FontSize, _('Purchased'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 270, $YPos, 70, $FontSize, _('Cost B/Fwd'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 340, $YPos, 70, $FontSize, _('Depn B/Fwd'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 410, $YPos, 70, $FontSize, _('Additions'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 480, $YPos, 70, $FontSize, _('Depreciation'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 550, $YPos, 70, $FontSize, _('Cost C/Fwd'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 620, $YPos, 70, $FontSize, _('Depn C/Fwd'), 'centre');
	$LeftOvers = $PDF->addTextWrap($XPos + 690, $YPos, 70, $FontSize, _('Net Book Value'), 'centre');
	//$LeftOvers = $PDF->addTextWrap($XPos+760,$YPos,70,$FontSize,  _('Disposal Proceeds'), 'centre');
	$PDF->line($Left_Margin, $YTopLeft, $Page_Width - $Right_Margin, $YTopLeft);
	$PDF->line($Left_Margin, $YTopLeft, $Left_Margin, $Bottom_Margin);
	$PDF->line($Left_Margin, $Bottom_Margin, $Page_Width - $Right_Margin, $Bottom_Margin);
	$PDF->line($Page_Width - $Right_Margin, $Bottom_Margin, $Page_Width - $Right_Margin, $YTopLeft);

	$FontSize = 8;
	$YPos-= (1.5 * $line_height);

	$PageNumber++;
}

?>