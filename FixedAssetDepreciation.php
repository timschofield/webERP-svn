<?php
include ('includes/session.php');
$Title = _('Depreciation Journal Entry');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetDepreciation';
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

/*Get the last period depreciation (depn is transtype =44) was posted for */
$Result = DB_query("SELECT periods.lastdate_in_period,
							max(fixedassettrans.periodno)
					FROM fixedassettrans INNER JOIN periods
					ON fixedassettrans.periodno=periods.periodno
					WHERE transtype=44
					GROUP BY periods.lastdate_in_period
					ORDER BY periods.lastdate_in_period DESC");

$LastDepnRun = DB_fetch_row($Result);

$AllowUserEnteredProcessDate = true;

if (DB_num_rows($Result) == 0) { //then depn has never been run yet?
	/*in this case default depreciation calc to the last day of last month - and allow user to select a period */
	if (!isset($_POST['ProcessDate'])) {
		$_POST['ProcessDate'] = Date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 0, date('Y')));
	} else { //ProcessDate is set - make sure it is on the last day of the month selected
		if (!is_date($_POST['ProcessDate'])) {
			prnMsg(_('The date is expected to be in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
			$InputError = true;
		} else {
			$_POST['ProcessDate'] = LastDayOfMonth($_POST['ProcessDate']);
		}
	}

} else { //depn calc has been run previously
	$AllowUserEnteredProcessDate = false;
	$_POST['ProcessDate'] = LastDayOfMonth(DateAdd(ConvertSQLDate($LastDepnRun[0]), 'd', 28));
}

/* Get list of assets for journal */
$SQL = "SELECT fixedassets.assetid,
			fixedassets.description,
			fixedassets.depntype,
			fixedassets.depnrate,
			fixedassets.datepurchased,
			fixedassetcategories.accumdepnact,
			fixedassetcategories.depnact,
			fixedassetcategories.categorydescription,
			SUM(CASE WHEN fixedassettrans.fixedassettranstype='cost' THEN fixedassettrans.amount ELSE 0 END) AS costtotal,
			SUM(CASE WHEN fixedassettrans.fixedassettranstype='depn' THEN fixedassettrans.amount ELSE 0 END) AS depnbfwd
		FROM fixedassets
		INNER JOIN fixedassetcategories
			ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
		INNER JOIN fixedassettrans
			ON fixedassets.assetid=fixedassettrans.assetid
		WHERE fixedassettrans.transdate<='" . FormatDateForSQL($_POST['ProcessDate']) . "'
			AND fixedassets.datepurchased<='" . FormatDateForSQL($_POST['ProcessDate']) . "'
			AND fixedassets.disposaldate = '0000-00-00'
		GROUP BY fixedassets.assetid,
			fixedassets.description,
			fixedassets.depntype,
			fixedassets.depnrate,
			fixedassets.datepurchased,
			fixedassetcategories.accumdepnact,
			fixedassetcategories.depnact,
			fixedassetcategories.categorydescription
		ORDER BY assetcategoryid, assetid";

$AssetsResult = DB_query($SQL);

$InputError = false; //always hope for the best
if (Date1GreaterThanDate2($_POST['ProcessDate'], Date($_SESSION['DefaultDateFormat']))) {
	prnMsg(_('No depreciation will be committed as the processing date is beyond the current date. The depreciation run can only be run for periods prior to today'), 'warn');
	$InputError = true;
}
if (isset($_POST['CommitDepreciation']) and $InputError == false) {
	$Result = DB_Txn_Begin();
	$TransNo = GetNextTransNo(44);
	$PeriodNo = GetPeriod($_POST['ProcessDate']);
}

echo '<table>
		<tr>
			<th>' . _('Asset ID') . '</th>
			<th>' . _('Description') . '</th>
			<th>' . _('Date Purchased') . '</th>
			<th>' . _('Cost') . '</th>
			<th>' . _('Accum Depn') . '</th>
			<th>' . _('B/fwd Book Value') . '</th>
			<th>' . _('Depn Type') . '</th>
			<th>' . _('Depn Rate') . '</th>
			<th>' . _('New Depn') . '</th>
		</tr>';

$AssetCategoryDescription = '0';

$TotalCost = 0;
$TotalAccumDepn = 0;
$TotalDepn = 0;
$TotalCategoryCost = 0;
$TotalCategoryAccumDepn = 0;
$TotalCategoryDepn = 0;

while ($AssetRow = DB_fetch_array($AssetsResult)) {
	if ($AssetCategoryDescription != $AssetRow['categorydescription'] or $AssetCategoryDescription == '0') {
		if ($AssetCategoryDescription != '0') { //then print totals
			echo '<tr class="total_row">
					<td colspan="3" align="right">', _('Total for'), ' ', $AssetCategoryDescription, ' </td>
					<td class="number">', locale_number_format($TotalCategoryCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', locale_number_format($TotalCategoryAccumDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', locale_number_format(($TotalCategoryCost - $TotalCategoryAccumDepn), $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td colspan="2"></td>
					<td class="number">', locale_number_format($TotalCategoryDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				</tr>';
		}
		echo '<tr class="total_row">
				<td colspan="9" align="left">', $AssetRow['categorydescription'], '</td>
			</tr>';
		$AssetCategoryDescription = $AssetRow['categorydescription'];
		$TotalCategoryCost = 0;
		$TotalCategoryAccumDepn = 0;
		$TotalCategoryDepn = 0;
	}
	$BookValueBfwd = $AssetRow['costtotal'] - $AssetRow['depnbfwd'];
	if ($AssetRow['depntype'] == 0) { //striaght line depreciation
		$DepreciationType = _('SL');
		$NewDepreciation = $AssetRow['costtotal'] * $AssetRow['depnrate'] / 100 / 12;
		if ($NewDepreciation > $BookValueBfwd) {
			$NewDepreciation = $BookValueBfwd;
		}
	} else { //Diminishing value depreciation
		$DepreciationType = _('DV');
		$NewDepreciation = $BookValueBfwd * $AssetRow['depnrate'] / 100 / 12;
	}
	if (Date1GreaterThanDate2(ConvertSQLDate($AssetRow['datepurchased']), $_POST['ProcessDate'])) {
		/*Over-ride calculations as the asset was not purchased at the date of the calculation!! */
		$NewDepreciation = 0;
	}
	echo '<tr class="striped_row">
			<td>', $AssetRow['assetid'], '</td>
			<td>', $AssetRow['description'], '</td>
			<td>', ConvertSQLDate($AssetRow['datepurchased']), '</td>
			<td class="number">', locale_number_format($AssetRow['costtotal'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format($AssetRow['depnbfwd'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format($AssetRow['costtotal'] - $AssetRow['depnbfwd'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td align="center">', $DepreciationType, '</td>
			<td class="number">', $AssetRow['depnrate'], '</td>
			<td class="number">', locale_number_format($NewDepreciation, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		</tr>';
	$TotalCategoryCost+= $AssetRow['costtotal'];
	$TotalCategoryAccumDepn+= $AssetRow['depnbfwd'];
	$TotalCategoryDepn+= $NewDepreciation;
	$TotalCost+= $AssetRow['costtotal'];
	$TotalAccumDepn+= $AssetRow['depnbfwd'];
	$TotalDepn+= $NewDepreciation;

	if (isset($_POST['CommitDepreciation']) and $NewDepreciation != 0 and $InputError == false) {

		//debit depreciation expense
		$SQL = "INSERT INTO gltrans (type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount)
						VALUES (44,
								'" . $TransNo . "',
								'" . FormatDateForSQL($_POST['ProcessDate']) . "',
								'" . $PeriodNo . "',
								'" . $AssetRow['depnact'] . "',
								'" . _('Monthly depreciation for asset') . ' ' . $AssetRow['assetid'] . "',
								'" . $NewDepreciation . "')";

		$ErrMsg = _('Cannot insert a depreciation GL entry for the depreciation because');
		$DbgMsg = _('The SQL that failed to insert the GL Trans record was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		$SQL = "INSERT INTO gltrans (type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount)
						VALUES (44,
								'" . $TransNo . "',
								'" . FormatDateForSQL($_POST['ProcessDate']) . "',
								'" . $PeriodNo . "',
								'" . $AssetRow['accumdepnact'] . "',
								'" . _('Monthly depreciation for asset') . ' ' . $AssetRow['assetid'] . "',
								'" . -$NewDepreciation . "')";
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		//insert the fixedassettrans record
		$SQL = "INSERT INTO fixedassettrans (assetid,
											transtype,
											transno,
											transdate,
											periodno,
											inputdate,
											fixedassettranstype,
											amount)
							VALUES ('" . $AssetRow['assetid'] . "',
											'44',
											'" . $TransNo . "',
											'" . FormatDateForSQL($_POST['ProcessDate']) . "',
											'" . $PeriodNo . "',
											CURRENT_DATE,
											'depn',
											'" . $NewDepreciation . "')";
		$ErrMsg = _('Cannot insert a fixed asset transaction entry for the depreciation because');
		$DbgMsg = _('The SQL that failed to insert the fixed asset transaction record was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		/*now update the accum depn in fixedassets */
		$SQL = "UPDATE fixedassets SET accumdepn = accumdepn + " . $NewDepreciation . "
				WHERE assetid = '" . $AssetRow['assetid'] . "'";
		$ErrMsg = _('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset accumulated depreciation could not be updated') . ': ';
		$DbgMsg = _('The following SQL was used to attempt the update the accumulated depreciation of the asset was') . ': ';
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
	} //end if Committing the depreciation to DB
	
} //end loop around the assets to calculate depreciation for
echo '<tr class="total_row">
		<td colspan="3" align="right">', _('Total for'), ' ', $AssetCategoryDescription, ' </td>
		<td class="number">', locale_number_format($TotalCategoryCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td class="number">', locale_number_format($TotalCategoryAccumDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td class="number">', locale_number_format(($TotalCategoryCost - $TotalCategoryAccumDepn), $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td colspan="2"></td>
		<td class="number">', locale_number_format($TotalCategoryDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
	</tr>
	<tr class="total_row">
		<td colspan="3" align="right">', _('GRAND Total'), ' </td>
		<td class="number">', locale_number_format($TotalCost, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td class="number">', locale_number_format($TotalAccumDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td class="number">', locale_number_format(($TotalCost - $TotalAccumDepn), $_SESSION['CompanyRecord']['decimalplaces']), '</td>
		<td colspan="2"></td>
		<td class="number">', locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
	</tr>';

echo '</table>';

if (isset($_POST['CommitDepreciation']) and $InputError == false) {
	$Result = DB_Txn_Commit();
	prnMsg(_('Depreciation') . ' ' . $TransNo . ' ' . _('has been successfully entered'), 'success');
	unset($_POST['ProcessDate']);
	echo '<a href="index.php">', _('Return to main menu'), '</a>';

} else {
	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" id="form">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<fieldset>
			<legend>', _('Process Journal'), '</legend>';

	if ($AllowUserEnteredProcessDate) {
		echo '<field>
				<label for="ProcessDate">', _('Date to Process Depreciation'), ':</label>
				<input type="text" class="date" name="ProcessDate" required="required" maxlength="10" size="11" value="', $_POST['ProcessDate'], '" />
			</field>';
	} else {
		echo '<field>
				<label for="ProcessDate">', _('Date to Process Depreciation'), ':</label>
				<div class="fieldtext">', $_POST['ProcessDate'], '</div>
			</field>';
	}
	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="CommitDepreciation" value="', _('Commit Depreciation'), '" />
		</div>
	</form>';
}
include ('includes/footer.php');
?>