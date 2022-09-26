<?php
include ('includes/session.php');

$Title = _('Account Groups');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AccountGroups';

include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

function CheckForRecursiveGroup($ParentGroupCode, $GroupCode) {
	/* returns true ie 1 if the group contains the parent group as a child group
	 ie the parent group results in a recursive group structure otherwise false ie 0 */

	$ErrMsg = _('An error occurred in retrieving the account groups of the parent account group during the check for recursion');
	$DbgMsg = _('The SQL that was used to retrieve the account groups of the parent account group and that failed in the process was');

	do {
		$SQL = "SELECT parentgroupcode
				FROM accountgroups
				WHERE groupcode='" . $GroupCode . "'
					AND language='" . $_SESSION['ChartLanguage'] . "'";

		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		$MyRow = DB_fetch_row($Result);
		if ($ParentGroupCode == $MyRow[0]) {
			return true;
		} //$ParentGroupName == $MyRow[0]
		$GroupCode = $MyRow[0];
	} while ($MyRow[0] != '');
	return false;
	} //end of function CheckForRecursiveGroupName
	if (isset($_POST['MoveGroup'])) {
		$SQL = "UPDATE chartmaster SET group_='" . $_POST['DestinyAccountGroup'] . "' WHERE group_='" . $_POST['OriginalAccountGroup'] . "'";
		$ErrMsg = _('An error occurred in moving the account group');
		$DbgMsg = _('The SQL that was used to move the account group was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		echo '<div class="toplink">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Review Account Groups'), '</a>
		</div>';
		prnMsg(_('All accounts in the account group') . ': ' . $_POST['OriginalAccountGroup'] . ' ' . _('have been changed to the account group') . ': ' . $_POST['DestinyAccountGroup'], 'success');
		echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', $Title, '
		</p>';
	} //isset($_POST['MoveGroup'])
	if (isset($_POST['submit'])) {

		foreach ($_POST as $Key => $Value) {
			if (mb_substr($Key, 0, 9) == 'GroupName') {
				$GroupNames[mb_substr($Key, -5) . '.utf8'] = $Value;
			}
		}
		//initialise no input errors assumed initially before we test
		$InputError = 0;

		/* actions to take once the user has clicked the submit button
		 ie the page has called itself with some user input */

		//first off validate inputs sensible
		$SQL = "SELECT count(groupname)
			FROM accountgroups
			WHERE groupcode='" . $_POST['GroupCode'] . "'
				AND language='" . $_SESSION['ChartLanguage'] . "'";

		$DbgMsg = _('The SQL that was used to retrieve the information was');
		$ErrMsg = _('Could not check whether the group exists because');

		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		$MyRow = DB_fetch_row($Result);

		if ($MyRow[0] != 0 and $_POST['SelectedAccountGroup'] == '') {
			$InputError = 1;
			prnMsg(_('The account group code already exists in the database'), 'error');
		} //$MyRow[0] != 0 and $_POST['SelectedAccountGroup'] == ''
		foreach ($GroupNames as $GroupName) {
			if (mb_strlen($GroupName) == 0) {
				$InputError = 1;
				prnMsg(_('All the account group names must be at least one character long'), 'error');
			} //mb_strlen($_POST['GroupName']) == 0
			

			
		}
		if ($_POST['ParentGroup'] != '') {
			if (CheckForRecursiveGroup($_POST['GroupCode'], $_POST['ParentGroup'])) {
				$InputError = 1;
				prnMsg(_('The parent account group selected appears to result in a recursive account structure - select an alternative parent account group or make this group a top level account group'), 'error');
			} else {
				$SQL = "SELECT pandl,
						sequenceintb,
						sectioninaccounts
					FROM accountgroups
					WHERE groupcode='" . $_POST['ParentGroup'] . "'
						AND language='" . $_SESSION['ChartLanguage'] . "'";

				$DbgMsg = _('The SQL that was used to retrieve the information was');
				$ErrMsg = _('Could not check whether the group is recursive because');

				$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

				$ParentGroupRow = DB_fetch_array($Result);
				$_POST['SequenceInTB'] = $ParentGroupRow['sequenceintb'];
				$_POST['PandL'] = $ParentGroupRow['pandl'];
				$_POST['SectionInAccounts'] = $ParentGroupRow['sectioninaccounts'];
				prnMsg(_('Since this account group is a child group, the sequence in the trial balance, the section in the accounts and whether or not the account group appears in the balance sheet or profit and loss account are all properties inherited from the parent account group. Any changes made to these fields will have no effect.'), 'warn');
			}
		} //$_POST['ParentGroupName'] != ''
		if (!ctype_digit($_POST['SectionInAccounts'])) {
			$InputError = 1;
			prnMsg(_('The section in accounts must be an integer'), 'error');
		} //!ctype_digit($_POST['SectionInAccounts'])
		if (!ctype_digit($_POST['SequenceInTB'])) {
			$InputError = 1;
			prnMsg(_('The sequence in the trial balance must be an integer'), 'error');
		} //!ctype_digit($_POST['SequenceInTB'])
		if (!ctype_digit($_POST['SequenceInTB']) or $_POST['SequenceInTB'] > 10000) {
			$InputError = 1;
			prnMsg(_('The sequence in the TB must be numeric and less than') . ' 10,000', 'error');
		} //!ctype_digit($_POST['SequenceInTB']) or $_POST['SequenceInTB'] > 10000
		$SQL = "SELECT COUNT(pandl) AS porl
				FROM accountgroups
				WHERE sectioninaccounts='" . $_POST['SectionInAccounts'] . "'
					AND pandl='" . ((int)($_POST['PandL'] xor 1)) . "'
					AND language='" . $_SESSION['ChartLanguage'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		if ($MyRow['porl'] > 0) {
			$InputError = 1;
			prnMsg(_('You are trying to mix Balance Sheet groups with P and L groups within the same Account Section'), 'error');
		}

		$ParentGroupSQL = "SELECT groupname FROM accountgroups WHERE groupcode='" . $_POST['ParentGroup'] . "' AND language='" . $_SESSION['ChartLanguage'] . "'";
		$ParentGroupResult = DB_query($ParentGroupSQL);
		if (DB_num_rows($ParentGroupResult) > 0) {
			$ParentGroupRow = DB_fetch_array($ParentGroupResult);
		} else {
			$ParentGroupRow['groupname'] = '';
		}

		if (isset($_POST['OldGroupCode']) and $InputError != 1) {
			foreach ($GroupNames as $GroupLanguage => $GroupName) {
				/*SelectedAccountGroup could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the delete code below*/
				if ($_POST['OldGroupCode'] !== $_POST['GroupCode']) {
					DB_IgnoreForeignKeys();
					$SQL = "UPDATE chartmaster SET group_='" . $GroupName . "',
												groupcode='" . $_POST['GroupCode'] . "'
											WHERE groupcode='" . $_POST['OldGroupCode'] . "'
												AND language='" . $GroupLanguage . "'";
					$ErrMsg = _('An error occurred in renaming the account group');
					$DbgMsg = _('The SQL that was used to rename the account group was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
					$SQL = "UPDATE accountgroups SET parentgroupname='" . $_POST['GroupName'] . "' WHERE parentgroupcode='" . $_POST['OldGroupCode'] . "'";
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
					DB_ReinstateForeignKeys();
				}
				$SQL = "SELECT * FROM accountgroups WHERE groupcode='" . $_POST['GroupCode'] . "' AND language='" . $GroupLanguage . "'";
				$CountResult = DB_query($SQL);
				if (DB_num_rows($CountResult) > 0) {
					$SQL = "UPDATE accountgroups SET groupname='" . $GroupName . "',
												groupcode='" . $_POST['GroupCode'] . "',
												sectioninaccounts='" . $_POST['SectionInAccounts'] . "',
												pandl='" . $_POST['PandL'] . "',
												sequenceintb='" . $_POST['SequenceInTB'] . "',
												parentgroupcode='" . $_POST['ParentGroup'] . "',
												parentgroupname='" . DB_escape_string($ParentGroupRow['groupname']) . "'
											WHERE groupcode = '" . $_POST['SelectedAccountGroup'] . "'
												AND language='" . $GroupLanguage . "'";
					$ErrMsg = _('An error occurred in updating the account group');
					$DbgMsg = _('The SQL that was used to update the account group was');
					$Result = DB_query($SQL);
					if (DB_error_no($Result) === 0) {
						prnMsg(_('Account Group has been updated for language') . ' ' . $GroupLanguage, 'success');
					} else {
						prnMsg(_('Account Group could not be updated for language') . ' ' . $GroupLanguage, 'error');
					}
				} else {
					$SQL = "INSERT INTO accountgroups ( groupname,
													groupcode,
													language,
													sectioninaccounts,
													sequenceintb,
													pandl,
													parentgroupcode,
													parentgroupname
												) VALUES (
													'" . $GroupName . "',
													'" . $_POST['GroupCode'] . "',
													'" . $GroupLanguage . "',
													'" . $_POST['SectionInAccounts'] . "',
													'" . $_POST['SequenceInTB'] . "',
													'" . $_POST['PandL'] . "',
													'" . $_POST['ParentGroup'] . "',
													'" . DB_escape_string($ParentGroupRow['groupname']) . "')";
					$ErrMsg = _('An error occurred in inserting the account group');
					$DbgMsg = _('The SQL that was used to insert the account group was');

					$Result = DB_query($SQL);
					if (DB_error_no($Result) === 0) {
						prnMsg(_('Account Group has been inserted for language') . ' ' . $GroupLanguage, 'success');
					} else {
						prnMsg(_('Account Group could not be inserted for language') . ' ' . $GroupLanguage, 'error');
					}
				}
			}
		} elseif ($InputError != 1) {
			/*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new account group form */
			foreach ($GroupNames as $GroupLanguage => $GroupName) {
				$SQL = "INSERT INTO accountgroups ( groupname,
												groupcode,
												language,
												sectioninaccounts,
												sequenceintb,
												pandl,
												parentgroupcode,
												parentgroupname
											) VALUES (
												'" . $GroupName . "',
												'" . $_POST['GroupCode'] . "',
												'" . $GroupLanguage . "',
												'" . $_POST['SectionInAccounts'] . "',
												'" . $_POST['SequenceInTB'] . "',
												'" . $_POST['PandL'] . "',
												'" . $_POST['ParentGroup'] . "',
												'" . DB_escape_string($ParentGroupRow['groupname']) . "')";
				$ErrMsg = _('An error occurred in inserting the account group');
				$DbgMsg = _('The SQL that was used to insert the account group was');

				$Result = DB_query($SQL);
				if (DB_error_no($Result) === 0) {
					prnMsg(_('Account Group has been inserted for language') . ' ' . $GroupLanguage, 'success');
				} else {
					prnMsg(_('Account Group could not be inserted for language') . ' ' . $GroupLanguage, 'error');
				}
			}
		} //$InputError != 1
		if ($InputError != 1) {
			//run the SQL from either of the above possibilites
			unset($_POST['SelectedAccountGroup']);
			unset($_POST['GroupCode']);
			unset($_POST['GroupName']);
			unset($_POST['SequenceInTB']);
			unset($GroupNames);
		} //$InputError != 1
		

		
	} elseif (isset($_GET['delete'])) {
		//the link to delete a selected record was clicked instead of the submit button
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'ChartMaster'
		$SQL = "SELECT COUNT(group_) AS groups FROM chartmaster WHERE chartmaster.groupcode='" . $_GET['SelectedAccountGroup'] . "'";
		$ErrMsg = _('An error occurred in retrieving the group information from chartmaster');
		$DbgMsg = _('The SQL that was used to retrieve the information was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		$MyRow = DB_fetch_array($Result);
		if ($MyRow['groups'] > 0) {
			prnMsg(_('Cannot delete this account group because general ledger accounts have been created using this group'), 'warn');
			echo '<br />', _('There are'), ' ', $MyRow['groups'], ' ', _('general ledger accounts that refer to this account group');
			echo '<form method="post" id="AccountGroups" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';

			echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
			echo '<input type="hidden" name="OriginalAccountGroup" value="', $_GET['SelectedAccountGroup'], '" />';
			echo '<fieldset>';
			echo '<field>
				<label for="DestinyAccountGroup">', _('Parent Group'), ':', '</label>
				<select name="DestinyAccountGroup">';

			$SQL = "SELECT groupcode, groupname FROM accountgroups";
			$GroupResult = DB_query($SQL, $ErrMsg, $DbgMsg);
			while ($GroupRow = DB_fetch_array($GroupResult)) {
				if (isset($_POST['ParentGroupName']) and $_POST['ParentGroupName'] == $GroupRow['groupname']) {
					echo '<option selected="selected" value="', htmlentities($GroupRow['groupcode'], ENT_QUOTES, 'UTF-8'), '">', $GroupRow['groupcode'] . ' - ' . htmlentities($GroupRow['groupname'], ENT_QUOTES, 'UTF-8'), '</option>';
				} else {
					echo '<option value="', htmlentities($GroupRow['groupcode'], ENT_QUOTES, 'UTF-8'), '">', $GroupRow['groupcode'] . ' - ' . htmlentities($GroupRow['groupname'], ENT_QUOTES, 'UTF-8'), '</option>';
				}
			} //$GroupRow = DB_fetch_array($GroupResult)
			echo '</select>
			</field>
		</fieldset>';
			echo '<div class="centre">
				<input type="submit" name="MoveGroup" value="', _('Move Group'), '" />
			</div>';

		} else {
			$SQL = "SELECT COUNT(groupname) groupnames FROM accountgroups WHERE parentgroupcode = '" . $_GET['SelectedAccountGroup'] . "'";
			$ErrMsg = _('An error occurred in retrieving the parent group information');
			$DbgMsg = _('The SQL that was used to retrieve the information was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
			$MyRow = DB_fetch_array($Result);
			if ($MyRow['groupnames'] > 0) {
				prnMsg(_('Cannot delete this account group because it is a parent account group of other account group(s)'), 'warn');
				echo '<br />', _('There are'), ' ', $MyRow['groupnames'], ' ', _('account groups that have this group as its/there parent account group');

			} else {
				$SQL = "DELETE FROM accountgroups WHERE groupcode='" . $_GET['SelectedAccountGroup'] . "'";
				$ErrMsg = _('An error occurred in deleting the account group');
				$DbgMsg = _('The SQL that was used to delete the account group was');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
				prnMsg($_GET['SelectedAccountGroup'] . ' ' . _('group has been deleted') . '!', 'success');
			}

		} //end if account group used in GL accounts
		

		
	} //isset($_GET['delete'])
	if (!isset($_GET['SelectedAccountGroup']) and !isset($_POST['SelectedAccountGroup'])) {
		/*	An account group could be posted when one has been edited and is being updated or GOT when selected for modification
		SelectedAccountGroup will exist because it was sent with the page in a GET .
		If its the first time the page has been displayed with no parameters
		then none of the above are true and the list of account groups will be displayed with
		links to delete or edit each. These will call the same page again and allow update/input
		or deletion of the records*/

		$SQL = "SELECT groupname,
					groupcode,
					sectionname,
					accountgroups.language,
					sequenceintb,
					pandl,
					parentgroupcode
			FROM accountgroups
			LEFT JOIN accountsection
				ON sectionid = sectioninaccounts
				AND accountgroups.language=accountsection.language
			ORDER BY language, groupcode";

		$DbgMsg = _('The sql that was used to retrieve the account group information was ');
		$ErrMsg = _('Could not get account groups because');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', $Title, '
		</p>';

		echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Group Code'), '</th>
					<th class="SortedColumn">', _('Group Name'), '</th>
					<th class="SortedColumn">', _('Language'), '</th>
					<th class="SortedColumn">', _('Section'), '</th>
					<th class="SortedColumn">', _('Sequence In TB'), '</th>
					<th>', _('Profit and Loss'), '</th>
					<th>', _('Parent Group Code'), '</th>
					<th>', _('Parent Group Name'), '</th>
					<th class="noPrint" colspan="2"></th>
				</tr>
			</thead>';

		echo '<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {

			switch ($MyRow['pandl']) {
				case -1:
					$PandLText = _('Yes');
				break;
				case 1:
					$PandLText = _('Yes');
				break;
				case 0:
					$PandLText = _('No');
				break;
			} //end of switch statement
			$SQL = "SELECT groupname
				FROM accountgroups
				WHERE accountgroups.language='" . $MyRow['language'] . "'
					AND groupcode='" . $MyRow['parentgroupcode'] . "'";
			$ParentResult = DB_query($SQL);
			$ParentRow = DB_fetch_array($ParentResult);

			if (DB_num_rows($ParentResult) == 0) {
				$ParentGroupName = '';
			} else {
				$ParentGroupName = $ParentRow['groupname'];
			}

			echo '<tr class="striped_row">
				<td class="number">', $MyRow['groupcode'], '</td>
				<td>', $MyRow['groupname'], '</td>
				<td>', $MyRow['language'], '</td>
				<td>', $MyRow['sectionname'], '</td>
				<td class="number">', $MyRow['sequenceintb'], '</td>
				<td>', $PandLText, '</td>
				<td>', $MyRow['parentgroupcode'], '</td>
				<td>', $ParentGroupName, '</td>
				<td class="noPrint"><a href="', htmlspecialchars(basename(__FILE__) . '?SelectedAccountGroup=' . urlencode($MyRow['groupcode']), ENT_QUOTES, 'UTF-8'), '">', _('Edit'), '</a></td>
				<td class="noPrint"><a href="', htmlspecialchars(basename(__FILE__) . '?SelectedAccountGroup=' . urlencode($MyRow['groupcode']), ENT_QUOTES, 'UTF-8'), '&amp;delete=1" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this account group?') . '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';

		} //END WHILE LIST LOOP
		echo '</tbody>';
		echo '</table>';
	} //end of ifs and buts!
	

	if (isset($_POST['SelectedAccountGroup']) or isset($_GET['SelectedAccountGroup'])) {
		echo '<div class="toplink">
			<a href="', $RootPath, '/AccountGroups.php">', _('Review Account Groups'), '</a>
		</div>';
	} //isset($_POST['SelectedAccountGroup']) or isset($_GET['SelectedAccountGroup'])
	if (!isset($_GET['delete'])) {
		echo '<form method="post" id="AccountGroups" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

		if (isset($_GET['SelectedAccountGroup'])) {
			//editing an existing account group
			$SQL = "SELECT groupname,
						groupcode,
						sectioninaccounts,
						sequenceintb,
						pandl,
						parentgroupcode,
						language
				FROM accountgroups
				WHERE groupcode='" . $_GET['SelectedAccountGroup'] . "'";

			$ErrMsg = _('An error occurred in retrieving the account group information');
			$DbgMsg = _('The SQL that was used to retrieve the account group and that failed in the process was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
			if (DB_num_rows($Result) == 0) {
				prnMsg(_('The account group name does not exist in the database'), 'error');
				include ('includes/footer.php');
				exit;
			} //DB_num_rows($Result) == 0
			while ($MyRow = DB_fetch_array($Result)) {

				$_POST['GroupCode'] = $MyRow['groupcode'];
				$_POST['GroupName'] = $MyRow['groupname'];
				$GroupNames[$MyRow['language']] = $MyRow['groupname'];
				$_POST['SectionInAccounts'] = $MyRow['sectioninaccounts'];
				$_POST['SequenceInTB'] = $MyRow['sequenceintb'];
				$_POST['PandL'] = $MyRow['pandl'];
				$_POST['ParentGroup'] = $MyRow['parentgroupcode'];
			}

			echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '<br />
			</p>';

			echo '<input type="hidden" name="SelectedAccountGroup" value="', stripslashes($_GET['SelectedAccountGroup']), '" /></td>
				<input type="hidden" name="OldGroupName" value="', $_POST['GroupName'], '" />
				<input type="hidden" name="OldGroupCode" value="', $_POST['GroupCode'], '" />';

			echo '<fieldset>';
			echo '<legend>', _('Edit Account Group Details'), '</legend>';
			echo '<field>
				<label for="GroupCode">', _('Account Group Code'), ':</label>
				<div class="fieldtext">', $_POST['GroupCode'], '</div>
			</field>
			<input type="hidden" name="GroupCode" value="', $_POST['GroupCode'], '" />';

		} elseif (!isset($_POST['MoveGroup'])) { //end of if $_POST['SelectedAccountGroup'] only do the else when a new record is being entered
			if (!isset($_POST['SelectedAccountGroup'])) {
				$_POST['SelectedAccountGroup'] = '';
			} //!isset($_POST['SelectedAccountGroup'])
			if (!isset($_POST['GroupName'])) {
				$_POST['GroupName'] = '';
			} //!isset($_POST['GroupName'])
			if (!isset($_POST['GroupCode'])) {
				$_POST['GroupCode'] = '';
			} //!isset($_POST['GroupCode'])
			if (!isset($_POST['SectionInAccounts'])) {
				$_POST['SectionInAccounts'] = '';
			} //!isset($_POST['SectionInAccounts'])
			if (!isset($_POST['SequenceInTB'])) {
				$_POST['SequenceInTB'] = '';
			} //!isset($_POST['SequenceInTB'])
			if (!isset($_POST['PandL'])) {
				$_POST['PandL'] = '';
			} //!isset($_POST['PandL'])
			echo '<input type="hidden" name="SelectedAccountGroup" value="', $_POST['SelectedAccountGroup'], '" />';
			echo '<fieldset>';
			echo '<legend>', _('New Account Group Details'), '</legend>';
			echo '<field>
				<label for="GroupCode">', _('Account Group Code'), ':</label>
				<input class="number" type="text" name="GroupCode" size="10" autofocus="autofocus" required="required" maxlength="10" value="', $_POST['GroupCode'], '" />
				<fieldhelp>', _('The integer group code for this account group'), '</fieldhelp>
			</field>';
		} //!isset($_POST['MoveGroup'])
		$SQL = "SELECT DISTINCT language FROM accountsection";
		$LanguageResult = DB_query($SQL);
		while ($LanguageRow = DB_fetch_array($LanguageResult)) {
			if (!isset($GroupNames[$LanguageRow['language']])) {
				$GroupNames[$LanguageRow['language']] = '';
			}
			echo '<field>
				<label for="ParentGroup">', _('Account Group Name'), ' (', $LanguagesArray[$LanguageRow['language']]['LanguageName'], ') :', '</label>
				<input type="text" autofocus="autofocus" name="GroupName', mb_substr($LanguageRow['language'], 0, 5), '" required="required" size="50" maxlength="150" value="', $GroupNames[$LanguageRow['language']], '" />
				<fieldhelp>', _('The account group description in'), ' ', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</fieldhelp>
			</field>';
		}

		$SQL = "SELECT groupcode, groupname FROM accountgroups WHERE language='" . $_SESSION['ChartLanguage'] . "' ORDER BY groupcode";
		$GroupResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		echo '<field>
			<label for="ParentGroup">', _('Parent Group'), ':</label>
			<select name="ParentGroup">';

		if (!isset($_POST['ParentGroup'])) {
			echo '<option selected="selected" value="">', _('Top Level Group'), '</option>';
		} else {
			echo '<option value="">', _('Top Level Group'), '</option>';
		}

		while ($GroupRow = DB_fetch_array($GroupResult)) {
			if (isset($_POST['ParentGroup']) and $_POST['ParentGroup'] == $GroupRow['groupcode']) {
				echo '<option selected="selected" value="', $GroupRow['groupcode'], '">', $GroupRow['groupcode'], ' - ', $GroupRow['groupname'], '</option>';
			} else {
				echo '<option value="', $GroupRow['groupcode'], '">', $GroupRow['groupcode'], ' - ', $GroupRow['groupname'], '</option>';
			}
		} //$GroupRow = DB_fetch_array($GroupResult)
		echo '</select>
		<fieldhelp>', _('The parent group that this group will belong to'), '</fieldhelp>
	</field>';

		$SQL = "SELECT sectionid, sectionname FROM accountsection WHERE language='" . $_SESSION['ChartLanguage'] . "' ORDER BY sectionid";
		$SecResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		echo '<field>
			<label for="SectionInAccounts">', _('Section In Accounts'), ':</label>
			<select required="required" name="SectionInAccounts">';
		echo '<option value="">', _('None Selected'), '</option>';
		while ($SecRow = DB_fetch_array($SecResult)) {
			if ($_POST['SectionInAccounts'] == $SecRow['sectionid']) {
				echo '<option selected="selected" value="', $SecRow['sectionid'], '">', $SecRow['sectionname'], ' (', $SecRow['sectionid'], ')</option>';
			} else {
				echo '<option value="', $SecRow['sectionid'], '">', $SecRow['sectionname'], ' (', $SecRow['sectionid'], ')</option>';
			}
		} //$SecRow = DB_fetch_array($SecResult)
		echo '</select>
		<fieldhelp>', _('The section in the trial balance where this group will appear.'), '</fieldhelp>
	</field>';

		echo '<field>
			<label for="PandL">', _('Profit and Loss'), ':</label>
			<select required="required" name="PandL">';

		echo '<option selected="selected" value=""></option>';
		if ($_POST['PandL'] == 1) {
			echo '<option selected="selected" value="1">', _('Yes'), '</option>';
		} else {
			echo '<option value="1">', _('Yes'), '</option>';
		}
		if ($_POST['PandL'] == 0) {
			echo '<option selected="selected" value="0">', _('No'), '</option>';
		} else {
			echo '<option value="0">', _('No'), '</option>';
		}

		echo '</select>
		<fieldhelp>', _('If the accounts in this group will be in the Profit and Loss account select Yes  here, if for the Balance Sheet then choose No.'), '</fieldhelp>
	</field>';

		echo '<field>
			<label for="SequenceInTB">', _('Sequence In TB'), ':</label>
			<input type="text" required="required" maxlength="4" name="SequenceInTB" class="number" value="', $_POST['SequenceInTB'], '" />
			<fieldhelp>', _('The sequence in the trial balance where this group will appear.'), '</fieldhelp>
		</field>';

		echo '</fieldset>';

		echo '<div class="centre noPrint">
			<input type="submit" name="submit" value="', _('Enter Information'), '" />
		</div>';

		echo '</form>';

	} //end if record deleted no point displaying form to add record
	include ('includes/footer.php');
?>
