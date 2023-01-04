<?php
include ('includes/session.php');

$Title = _('Fixed Asset Category Maintenance');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetCategories';
include ('includes/header.php');

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Fixed Asset Categories'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_GET['SelectedCategory'])) {
	$SelectedCategory = mb_strtoupper($_GET['SelectedCategory']);
} else if (isset($_POST['SelectedCategory'])) {
	$SelectedCategory = mb_strtoupper($_POST['SelectedCategory']);
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$_POST['CategoryID'] = mb_strtoupper($_POST['CategoryID']);

	if (mb_strlen($_POST['CategoryID']) > 6) {
		$InputError = 1;
		prnMsg(_('The Fixed Asset Category code must be six characters or less long'), 'error');
	} elseif (mb_strlen($_POST['CategoryID']) == 0) {
		$InputError = 1;
		prnMsg(_('The Fixed Asset Category code must be at least 1 character but less than six characters long'), 'error');
	} elseif (mb_strlen($_POST['CategoryDescription']) > 20) {
		$InputError = 1;
		prnMsg(_('The Fixed Asset Category description must be twenty characters or less long'), 'error');
	}

	if ($_POST['CostAct'] == $_SESSION['CompanyRecord']['debtorsact'] or $_POST['CostAct'] == $_SESSION['CompanyRecord']['creditorsact'] or $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['debtorsact'] or $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['creditorsact'] or $_POST['CostAct'] == $_SESSION['CompanyRecord']['grnact'] or $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['grnact']) {

		prnMsg(_('The accounts selected to post cost or accumulated depreciation to cannot be either of the debtors control account, creditors control account or GRN suspense accounts'), 'error');
		$InputError = 1;
	}
	/*Make an array of the defined bank accounts */
	$SQL = "SELECT bankaccounts.accountcode
			FROM bankaccounts INNER JOIN chartmaster
			ON bankaccounts.accountcode=chartmaster.accountcode";
	$Result = DB_query($SQL);
	$BankAccounts = array();
	$i = 0;

	while ($Act = DB_fetch_row($Result)) {
		$BankAccounts[$i] = $Act[0];
		++$i;
	}
	if (in_array($_POST['CostAct'], $BankAccounts)) {
		prnMsg(_('The asset cost account selected is a bank account - bank accounts are protected from having any other postings made to them. Select another balance sheet account for the asset cost'), 'error');
		$InputError = 1;
	}
	if (in_array($_POST['AccumDepnAct'], $BankAccounts)) {
		prnMsg(_('The accumulated depreciation account selected is a bank account - bank accounts are protected from having any other postings made to them. Select another balance sheet account for the asset accumulated depreciation'), 'error');
		$InputError = 1;
	}

	if (isset($SelectedCategory) and $InputError != 1) {

		/*SelectedCategory could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$SQL = "UPDATE fixedassetcategories
					SET categorydescription = '" . $_POST['CategoryDescription'] . "',
						costact = '" . $_POST['CostAct'] . "',
						depnact = '" . $_POST['DepnAct'] . "',
						disposalact = '" . $_POST['DisposalAct'] . "',
						accumdepnact = '" . $_POST['AccumDepnAct'] . "'
				WHERE categoryid = '" . $SelectedCategory . "'";

		$ErrMsg = _('Could not update the fixed asset category') . $_POST['CategoryDescription'] . _('because');
		$Result = DB_query($SQL, $ErrMsg);

		prnMsg(_('Updated the fixed asset category record for') . ' ' . $_POST['CategoryDescription'], 'success');

	} elseif ($InputError != 1) {

		$SQL = "INSERT INTO fixedassetcategories (categoryid,
												categorydescription,
												costact,
												depnact,
												disposalact,
												accumdepnact)
								VALUES ('" . $_POST['CategoryID'] . "',
										'" . $_POST['CategoryDescription'] . "',
										'" . $_POST['CostAct'] . "',
										'" . $_POST['DepnAct'] . "',
										'" . $_POST['DisposalAct'] . "',
										'" . $_POST['AccumDepnAct'] . "')";
		$ErrMsg = _('Could not insert the new fixed asset category') . $_POST['CategoryDescription'] . _('because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(_('A new fixed asset category record has been added for') . ' ' . $_POST['CategoryDescription'], 'success');

	}
	//run the SQL from either of the above possibilites
	unset($_POST['CategoryID']);
	unset($_POST['CategoryDescription']);
	unset($_POST['CostAct']);
	unset($_POST['DepnAct']);
	unset($_POST['DisposalAct']);
	unset($_POST['AccumDepnAct']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'fixedassets'
	$SQL = "SELECT COUNT(*) FROM fixedassets WHERE fixedassets.assetcategoryid='" . $SelectedCategory . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(_('Cannot delete this fixed asset category because fixed assets have been created using this category') . '<br /> ' . _('There are') . ' ' . $MyRow[0] . ' ' . _('fixed assets referring to this category code'), 'warn');

	} else {
		$SQL = "DELETE FROM fixedassetcategories WHERE categoryid='" . $SelectedCategory . "'";
		$Result = DB_query($SQL);
		prnMsg(_('The fixed asset category') . ' ' . $SelectedCategory . ' ' . _('has been deleted'), 'success');
		unset($SelectedCategory);
	} //end if stock category used in debtor transactions
	
}

if (!isset($SelectedCategory) or isset($_POST['submit'])) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedCategory will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of stock categorys will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT categoryid,
				categorydescription,
				costact,
				depnact,
				disposalact,
				accumdepnact
			FROM fixedassetcategories";
	$Result = DB_query($SQL);

	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Cat Code'), '</th>
					<th class="SortedColumn">', _('Description'), '</th>
					<th>', _('Cost GL'), '</th>
					<th>', _('P and L'), '<br />', _('Depreciation GL'), '</th>
					<th>', _('Disposal GL'), '</th>
					<th>', _('Accum Depn GL'), '</th>
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td>', $MyRow['categoryid'], '</td>
				<td>', $MyRow['categorydescription'], '</td>
				<td class="number">', $MyRow['costact'], '</td>
				<td class="number">', $MyRow['depnact'], '</td>
				<td class="number">', $MyRow['disposalact'], '</td>
				<td class="number">', $MyRow['accumdepnact'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedCategory=', urlencode($MyRow['categoryid']), '">' . _('Edit') . '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedCategory=', urlencode($MyRow['categoryid']), '&amp;delete=yes" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this fixed asset category? Additional checks will be performed before actual deletion to ensure data integrity is not compromised.') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>
			</tr>';
	}
	//END WHILE LIST LOOP
	echo '</tbody>
		</table>';
}

//end of ifs and buts!
if (isset($SelectedCategory)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Show All Fixed Asset Categories'), '</a>
		</div>';
}

echo '<form id="CategoryForm" method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($SelectedCategory) and !isset($_POST['submit'])) {
	//editing an existing fixed asset category
	$SQL = "SELECT categoryid,
					categorydescription,
					costact,
					depnact,
					disposalact,
					accumdepnact
				FROM fixedassetcategories
				WHERE categoryid='" . $SelectedCategory . "'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['CategoryID'] = $MyRow['categoryid'];
	$_POST['CategoryDescription'] = $MyRow['categorydescription'];
	$_POST['CostAct'] = $MyRow['costact'];
	$_POST['DepnAct'] = $MyRow['depnact'];
	$_POST['DisposalAct'] = $MyRow['disposalact'];
	$_POST['AccumDepnAct'] = $MyRow['accumdepnact'];

	echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';
	echo '<input type="hidden" name="CategoryID" value="' . $_POST['CategoryID'] . '" />';
	echo '<fieldset>
			<legend>', _('Edit Category Details'), '</legend>
			<field>
				<label>', _('Category Code'), ':</label>
				<div class="fieldtext">', $_POST['CategoryID'], '</div>
			</field>';

} else { //end of if $SelectedCategory only do the else when a new record is being entered
	if (!isset($_POST['CategoryID'])) {
		$_POST['CategoryID'] = '';
	}
	echo '<fieldset>
			<legend>', _('Create New Category'), '</legend>
			<field>
				<label for="CategoryID">', _('Category Code'), ':</label>
				<input type="text" name="CategoryID" size="7" required="required" maxlength="6" value="', $_POST['CategoryID'], '" />
			</field>';
}

//SQL to poulate account selection boxes
$SQL = "SELECT chartmaster.accountcode,
				chartmaster.accountname
		FROM chartmaster
		INNER JOIN accountgroups
			ON chartmaster.groupcode=accountgroups.groupcode
			AND chartmaster.language=accountgroups.language
		LEFT JOIN bankaccounts
			ON chartmaster.accountcode=bankaccounts.accountcode
		WHERE accountgroups.pandl=0
			AND bankaccounts.currcode IS NULL
			AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
		ORDER BY accountcode";

$BSAccountsResult = DB_query($SQL);

$SQL = "SELECT accountcode,
				 accountname
		FROM chartmaster
		INNER JOIN accountgroups
			ON chartmaster.groupcode=accountgroups.groupcode
			AND chartmaster.language=accountgroups.language
		WHERE accountgroups.pandl!=0
			AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
		ORDER BY accountcode";

$PnLAccountsResult = DB_query($SQL);

if (!isset($_POST['CategoryDescription'])) {
	$_POST['CategoryDescription'] = '';
}

echo '<field>
		<label for="CategoryDescription">', _('Category Description'), ':</label>
		<input type="text" name="CategoryDescription" size="22" required="required" maxlength="20" value="', $_POST['CategoryDescription'], '" />
	</field>';

echo '<field>
		<label for="CostAct">', _('Fixed Asset Cost GL Code'), ':</label>
		<select required="required" name="CostAct">';
while ($MyRow = DB_fetch_array($BSAccountsResult)) {
	if (isset($_POST['CostAct']) and $MyRow['accountcode'] == $_POST['CostAct']) {
		echo '<option selected="selected" value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')</option>';
	} else {
		echo '<option value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')</option>';
	}
} //end while loop
echo '</select>
	</field>';

echo '<field>
		<label for="DepnAct">', _('Profit and Loss Depreciation GL Code'), ':</label>
		<select required="required" name="DepnAct">';
while ($MyRow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['DepnAct']) and $MyRow['accountcode'] == $_POST['DepnAct']) {
		echo '<option selected="selected" value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')</option>';
	} else {
		echo '<option value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')</option>';
	}
} //end while loop
echo '</select>
	</field>';

DB_data_seek($PnLAccountsResult, 0);
echo '<field>
		<label for="DisposalAct">', _('Profit or Loss on Disposal GL Code'), ':</label>
		<select required="required" name="DisposalAct">';
while ($MyRow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['DisposalAct']) and $MyRow['accountcode'] == $_POST['DisposalAct']) {
		echo '<option selected="selected" value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')', '</option>';
	} else {
		echo '<option value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')', '</option>';
	}
} //end while loop
echo '</select>
	</field>';

DB_data_seek($BSAccountsResult, 0);
echo '<field>
		<label for="AccumDepnAct">', _('Balance Sheet Accumulated Depreciation GL Code'), ':</label>
		<select required="required" name="AccumDepnAct">';
while ($MyRow = DB_fetch_array($BSAccountsResult)) {
	if (isset($_POST['AccumDepnAct']) and $MyRow['accountcode'] == $_POST['AccumDepnAct']) {
		echo '<option selected="selected" value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')', '</option>';
	} else {
		echo '<option value="', $MyRow['accountcode'], '">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), ' (', $MyRow['accountcode'], ')', '</option>';
	}
} //end while loop
echo '</select>
	</field>
</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="', _('Enter Information'), '" />
	</div>
</form>';

include ('includes/footer.php');
?>