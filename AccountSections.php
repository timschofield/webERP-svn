<?php
include ('includes/session.php');

$Title = _('Account Sections');

$ViewTopic = 'GeneralLedger';
$BookMark = 'AccountSections';
include ('includes/header.php');

if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (isset($_POST['SectionID'])) {
		$SQL = "SELECT sectionid,
						language
					FROM accountsection
					WHERE sectionid='" . $_POST['SectionID'] . "'
						AND language='" . $_POST['SelectedLanguage'] . "'";
		$Result = DB_query($SQL);

		if ((DB_num_rows($Result) != 0 and !isset($_POST['SelectedSectionID']))) {
			$InputError = 1;
			prnMsg(_('The account section already exists in the database'), 'error');
		}
	}

	if (mb_strlen($_POST['SectionName']) < 3) {
		$InputError = 1;
		prnMsg(_('The account section name must contain at least three characters'), 'error');
	}

	if (isset($_POST['SectionID']) and (!is_numeric($_POST['SectionID']))) {
		$InputError = 1;
		prnMsg(_('The section number must be an integer'), 'error');
	}
	if (isset($_POST['SectionID']) and mb_strpos($_POST['SectionID'], ".") > 0) {
		$InputError = 1;
		prnMsg(_('The section number must be an integer'), 'error');
	}

	if (isset($_POST['SelectedSectionID']) and $_POST['SelectedSectionID'] != '' and $InputError != 1) {
		$SQL = "UPDATE accountsection SET sectionname='" . $_POST['SectionName'] . "'
					WHERE sectionid = '" . $_POST['SelectedSectionID'] . "'
						AND language='" . $_POST['SelectedLanguage'] . "'";

		$Result = DB_query($SQL);
		if (DB_error_no($Result) === 0) {
			prnMsg(_('Account Section has been updated for language') . ' ' . $_POST['SelectedLanguage'], 'success');
		} else {
			prnMsg(_('Account Section could not be updated for language') . ' ' . $_POST['SelectedLanguage'], 'error');
		}

	} elseif ($InputError != 1) {

		/*SelectedSectionID is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new account section form */
		$SQL = "INSERT INTO accountsection (sectionid,
											language,
											sectionname
										) VALUES (
											'" . $_POST['SectionID'] . "',
											'" . $_POST['SelectedLanguage'] . "',
											'" . $_POST['SectionName'] . "')";
		$Result = DB_query($SQL);
		if (DB_error_no($Result) === 0) {
			prnMsg(_('Account Section has been inserted for language') . ' ' . $_POST['SelectedLanguage'], 'success');
		} else {
			prnMsg(_('Account Section could not be inserted for language') . ' ' . $_POST['SelectedLanguage'], 'error');
		}
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		unset($_POST['SelectedSectionID']);
		unset($_POST['SectionID']);
		unset($SectionName);
	}

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'accountgroups'
	$SQL = "SELECT COUNT(sectioninaccounts) AS sections FROM accountgroups WHERE sectioninaccounts='" . $_GET['SelectedSectionID'] . "' AND language='" . $_GET['SelectedLanguage'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['sections'] > 0) {
		prnMsg(_('Cannot delete this account section because general ledger accounts groups have been created using this section'), 'warn');
		echo '<div class="centre">';
		echo '<br />' . _('There are') . ' ' . $MyRow['sections'] . ' ' . _('general ledger accounts groups that refer to this account section');
		echo '</div>';

	} else {
		//Fetch section name
		$SQL = "SELECT sectionname FROM accountsection WHERE sectionid='" . $_GET['SelectedSectionID'] . "' AND language='" . $_GET['SelectedLanguage'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$SectionName = $MyRow['sectionname'];

		$SQL = "DELETE FROM accountsection WHERE sectionid='" . $_GET['SelectedSectionID'] . "' AND language='" . $_GET['SelectedLanguage'] . "'";
		$Result = DB_query($SQL);
		prnMsg($SectionName . ' ' . _('section has been deleted') . '!', 'success');

	} //end if account group used in GL accounts
	unset($_GET['SelectedSectionID']);
	unset($_GET['delete']);
	unset($_POST['SelectedSectionID']);
	unset($_POST['SectionID']);
	unset($SectionName);
}

if (!isset($_GET['SelectedSectionID']) and !isset($_POST['SelectedSectionID'])) {

	/*	An account section could be posted when one has been edited and is being updated
	or GOT when selected for modification
	SelectedSectionID will exist because it was sent with the page in a GET .
	If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of account groups will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT language,
					sectionid,
					sectionname
				FROM accountsection
				ORDER BY sectionid";

	$ErrMsg = _('Could not get account group sections because');
	$Result = DB_query($SQL, $ErrMsg);
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Section Number'), '</th>
					<th class="SortedColumn">', _('Language'), '</th>
					<th class="SortedColumn">', _('Section Description'), '</th>
					<th class="noPrint" colspan="2">&nbsp;</th>
				</tr>
			</thead>';

	echo '<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td class="number">', $MyRow['sectionid'], '</td>
				<td>', $MyRow['language'], '</td>
				<td>', $MyRow['sectionname'], '</td>
				<td class="noPrint"><a href="', basename(__FILE__), '?SelectedSectionID=', urlencode($MyRow['sectionid']), '&SelectedLanguage=', urlencode($MyRow['language']), '">', _('Edit'), '</a></td>
				<td class="noPrint"><a href="', basename(__FILE__), '?SelectedSectionID=', urlencode($MyRow['sectionid']), '&SelectedLanguage=', urlencode($MyRow['language']), '&delete=1', '" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this account section?') . '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';
	} //END WHILE LIST LOOP
	echo '</tbody>';
	echo '</table>';
} //end of ifs and buts!


if (isset($_POST['SelectedSectionID']) or isset($_GET['SelectedSectionID'])) {
	echo '<div class="toplink">
			<a href="', $RootPath, '/AccountSections.php">', _('Review Account Sections'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {
	include ('includes/LanguagesArray.php');

	echo '<form method="post" class="noPrint" id="AccountSections" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($_GET['SelectedSectionID'])) {
		//editing an existing section
		echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
			</p>';

		$SQL = "SELECT sectionname
					FROM accountsection
					WHERE sectionid='" . $_GET['SelectedSectionID'] . "'
						AND language='" . $_GET['SelectedLanguage'] . "'";

		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			prnMsg(_('Could not retrieve the requested section please try again.'), 'warn');
			unset($_GET['SelectedSectionID']);
		} else {
			$MyRow = DB_fetch_array($Result);
			$SectionName = $MyRow['sectionname'];
			echo '<input type="hidden" name="SelectedSectionID" value="', $_GET['SelectedSectionID'], '" />';
			echo '<fieldset>
					<legend>', _('Edit Account Section Details'), '</legend>
					<field>
						<label for="SectionID">', _('Section Number'), ':</label>
						<div class="fieldtext">', $_GET['SelectedSectionID'], '</div>
					</field>';
		}
		$_POST['SelectedLanguage'] = $_GET['SelectedLanguage'];

	} else {

		if (!isset($_POST['SelectedSectionID'])) {
			$_POST['SelectedSectionID'] = '';
		}
		if (!isset($_POST['SectionID'])) {
			$_POST['SectionID'] = '';
		}
		if (!isset($_POST['SelectedLanguage'])) {
			$_POST['SelectedLanguage'] = '';
		}
		$SectionName = '';
		echo '<fieldset>
				<legend>', _('New Account Section Details'), '</legend>
				<field>
					<label for="SectionID">', _('Section Number'), ':</label>
					<input type="text" name="SectionID" class="number" size="4" autofocus="autofocus" required="required" maxlength="4" value="', $_POST['SectionID'], '" />
					<fieldhelp>', _('The integer group code for this account section'), '</fieldhelp>
				</field>';
	}

	echo '<field>
			<label for="SelectedLanguage">', _('Language'), ':</label>
			<select name="SelectedLanguage">';

	foreach ($LanguagesArray as $LanguageEntry => $LanguageName) {
		if (isset($_POST['SelectedLanguage']) and $_POST['SelectedLanguage'] == $LanguageEntry) {
			echo '<option selected="selected" value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
		} elseif ($LanguageEntry == $_SESSION['ChartLanguage']) {
			echo '<option selected="selected" value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
		} else {
			echo '<option value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the language for this account section. Note each language must have all the same account sections setup.'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="SectionName">', _('Section Description'), ':</label>
			<input type="text" name="SectionName" autofocus="autofocus" required="required" size="50" maxlength="100" value="', $SectionName, '" />
			<fieldhelp>', _('The account section description in'), '</fieldhelp>
		</field>';

	echo '</fieldset>
			<div class="centre">
				<input type="submit" name="submit" value="', _('Enter Information'), '" />
			</div>
		</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>