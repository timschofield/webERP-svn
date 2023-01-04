<?php
include ('includes/session.php');

$Title = _('Change Asset Location');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetTransfer';
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

foreach ($_POST as $AssetToMove => $Value) { //Value is not used?
	if (mb_substr($AssetToMove, 0, 4) == 'Move') { // the form variable is of the format MoveAssetID so need to strip the move bit off
		$AssetID = mb_substr($AssetToMove, 4);
		if (isset($_POST['Location' . $AssetID]) and $_POST['Location' . $AssetID] != '') {
			$SQL = "UPDATE fixedassets
						SET assetlocation='" . $_POST['Location' . $AssetID] . "'
						WHERE assetid='" . $AssetID . "'";

			$Result = DB_query($SQL);
			prnMsg(_('The Fixed Asset has been moved successfully'), 'success');
			echo '<br />';
		}
	}
}

if (isset($_GET['AssetID'])) {
	$AssetID = $_GET['AssetID'];
} else if (isset($_POST['AssetID'])) {
	$AssetID = $_POST['AssetID'];
} else if (!isset($_POST['Search'])) {
	$SQL = "SELECT categoryid, categorydescription FROM fixedassetcategories";
	$Result = DB_query($SQL);
	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<legend>', _('Selection Criteria'), '</legend>';

	if (!isset($_POST['AssetCat'])) {
		$_POST['AssetCat'] = 'ALL';
	}
	echo '<field>
			<label for="AssetCat">', _('In Asset Category'), ':</label>
			<select name="AssetCat">';
	if ($_POST['AssetCat'] == 'All') {
		echo '<option selected="selected" value="All">', _('Any asset category'), '</option>';
	} else {
		echo '<option value="All">', _('Any asset category'), '</option>';
	}
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['categoryid'] == $_POST['AssetCategory']) {
			echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		}
	}
	echo '</select>
		</field>';

	echo '<field>
			<label for="AssetLocation">', _('Asset Location'), ':</label>
			<select name="AssetLocation">';
	if (!isset($_POST['AssetLocation'])) {
		$_POST['AssetLocation'] = 'ALL';
	}
	if ($_POST['AssetLocation'] == 'ALL') {
		echo '<option selected="selected" value="ALL">', _('Any asset location'), '</option>';
	} else {
		echo '<option value="ALL">', _('Any asset location'), '</option>';
	}
	$Result = DB_query("SELECT locationid, locationdescription FROM fixedassetlocations");

	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['locationid'] == $_POST['AssetLocation']) {
			echo '<option selected="selected" value="', $MyRow['locationid'], '">', $MyRow['locationdescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['locationid'], '">', $MyRow['locationdescription'], '</option>';
		}
	}
	echo '</select>
		</field>';

	echo '<field>
			<label for "Keywords">', _('Enter partial description'), ':</label>';
	if (isset($_POST['Keywords'])) {
		echo '<input type="text" autofocus="autofocus" name="Keywords" value="', $_POST['Keywords'], '" size="20" maxlength="25" />';
	} else {
		echo '<input type="text" autofocus="autofocus" name="Keywords" size="20" maxlength="25" />';
	}
	echo '</field>';

	echo '<h1>', _('OR'), '</h1>';

	echo '<field>
			<label for="AssetCode">', _('Enter partial asset code'), ':</label>';
	if (isset($_POST['AssetCode'])) {
		echo '<input type="text" class="number" name="AssetCode" value="', $_POST['AssetCode'], '" size="15" maxlength="13" />';
	} else {
		echo '<input type="text" name="AssetCode" size="15" maxlength="13" />';
	}
	echo '</field>
		</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Search" value="', _('Search Now'), '" />
		</div>';

	echo '</form>';
}

if (isset($_POST['Search'])) {

	if ($_POST['AssetLocation'] == 'ALL') {
		$AssetLocation = '%';
	} else {
		$AssetLocation = '%' . $_POST['AssetLocation'] . '%';
	}
	if ($_POST['AssetCat'] == 'All') {
		$AssetCat = '%';
	} else {
		$AssetCat = '%' . $_POST['AssetCat'] . '%';;
	}
	if (isset($_POST['Keywords'])) {
		$Keywords = '%' . $_POST['Keywords'] . '%';
	} else {
		$Keywords = '%';
	}
	if (isset($_POST['AssetID'])) {
		$AssetID = '%' . $_POST['AssetID'] . '%';
	} else {
		$AssetID = '%';
	}

	$SQL = "SELECT fixedassets.assetid,
				fixedassets.cost,
				fixedassets.accumdepn,
				fixedassets.description,
				fixedassets.depntype,
				fixedassets.serialno,
				fixedassets.barcode,
				fixedassets.assetlocation as ItemAssetLocation,
				fixedassetlocations.locationdescription
			FROM fixedassets
			INNER JOIN fixedassetlocations
			ON fixedassets.assetlocation=fixedassetlocations.locationid
			WHERE fixedassets.assetcategoryid " . LIKE . " '" . $AssetCat . "'
			AND fixedassets.description " . LIKE . " '" . $Keywords . "'
			AND fixedassets.assetid " . LIKE . " '" . $AssetID . "'
			AND fixedassets.assetlocation " . LIKE . " '" . $AssetLocation . "'
			ORDER BY fixedassets.assetid";

	$Result = DB_query($SQL);

	echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table>';
	echo '<tr>
			<th>', _('Asset ID'), '</th>
			<th>', _('Description'), '</th>
			<th>', _('Serial number'), '</th>
			<th>', _('Purchase Cost'), '</th>
			<th>', _('Total Depreciation'), '</th>
			<th>', _('Current Location'), '</th>
			<th colspan="2">', _('Move To'), '</th>
		</tr>';

	$locationsql = "SELECT locationid, locationdescription from fixedassetlocations";
	$LocationResult = DB_query($locationsql);

	while ($MyRow = DB_fetch_array($Result)) {

		echo '<tr>
				<td>', $MyRow['assetid'], '</td>
				<td>', $MyRow['description'], '</td>
				<td>', $MyRow['serialno'], '</td>
				<td class="number">', locale_number_format($MyRow['cost'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['accumdepn'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td>', $MyRow['ItemAssetLocation'], '</td>
				<td><select name="Location', $MyRow['assetid'], '" onchange="ReloadForm(Move', $MyRow['assetid'], ')">';
		$ThisDropDownName = 'Location' . $MyRow['assetid'];
		while ($LocationRow = DB_fetch_array($LocationResult)) {

			if (isset($_POST[$ThisDropDownName]) and ($_POST[$ThisDropDownName] == $LocationRow['locationid'])) {
				echo '<option selected="selected" value="', $LocationRow['locationid'], '">', $LocationRow['locationdescription'], '</option>';
			} elseif ($LocationRow['locationid'] == $MyRow['ItemAssetLocation']) {
				echo '<option selected="selected" value="', $LocationRow['locationid'], '">', $LocationRow['locationdescription'], '</option>';
			} else {
				echo '<option value="', $LocationRow['locationid'], '">', $LocationRow['locationdescription'], '</option>';
			}
		}
		DB_data_seek($LocationResult, 0);
		echo '</select>
			</td>';
		echo '<input type="hidden" name="AssetCat" value="', $_POST['AssetCat'], '" />';
		echo '<input type="hidden" name="AssetLocation" value="', $_POST['AssetLocation'], '" />';
		echo '<input type="hidden" name="Keywords" value="', $_POST['Keywords'], '" />';
		echo '<input type="hidden" name="Search" value="', $_POST['Search'], '" />';
		echo '<td><input type="submit" name="Move', $MyRow['assetid'], '" value="Move" /></td>
			</tr>';
	}
	echo '</table>
		  </form>';
}

include ('includes/footer.php');

?>