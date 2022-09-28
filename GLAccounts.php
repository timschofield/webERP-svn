<?php
// BEGIN: Functions division ---------------------------------------------------
function CashFlowsActivityName($Activity) {
	// Converts the cash flow activity number to an activity text.
	switch ($Activity) {
		case -1:
			return '<b>' . _('Not set up') . '</b>';
		case 0:
			return _('No effect on cash flow');
		case 1:
			return _('Operating activity');
		case 2:
			return _('Investing activity');
		case 3:
			return _('Financing activity');
		case 4:
			return _('Cash or cash equivalent');
		default:
			return '<b>' . _('Unknown') . '</b>';
	}
}
// END: Functions division -----------------------------------------------------
// BEGIN: Procedure division ---------------------------------------------------
include ('includes/session.php');
$Title = _('Chart of Accounts Maintenance');

$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccounts';

include ('includes/header.php');

if (isset($_POST['SelectedAccount'])) {
	$SelectedAccount = $_POST['SelectedAccount'];
} elseif (isset($_GET['SelectedAccount'])) {
	$SelectedAccount = $_GET['SelectedAccount'];
}

if (isset($_POST['SelectedLanguage'])) {
	$SelectedLanguage = $_POST['SelectedLanguage'];
} elseif (isset($_GET['SelectedLanguage'])) {
	$SelectedLanguage = $_GET['SelectedLanguage'];
} else {
	$SelectedLanguage = $_SESSION['ChartLanguage'];
}

// Merges gets into posts:
if (isset($_GET['CashFlowsActivity'])) { // Select period from.
	$_POST['CashFlowsActivity'] = $_GET['CashFlowsActivity'];
}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('General Ledger Accounts'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_POST['submit']) and $_POST['submit'] == _('Enter Information')) {

	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 11) == 'AccountName') {
			$AccountNames[mb_substr($Key, -5) . '.utf8'] = $Value;
		}
	}

	$GroupSQL = "SELECT groupname
					FROM accountgroups
					WHERE groupcode='" . $_POST['Group'] . "'
					AND language='" . $SelectedLanguage . "'";
	$GroupResult = DB_query($GroupSQL);
	$GroupRow = DB_fetch_array($GroupResult);
	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 11) == 'AccountName') {
			if (mb_strlen($Value) > 150) {
				$InputError = 1;
				prnMsg(_('The account name must be one hundred and fifty characters or less long'), 'warn');
			}
		}
	}

	if (isset($SelectedAccount) and $InputError != 1) {

		foreach ($AccountNames as $AccountLanguage => $AccountName) {
			$SQL = "SELECT * FROM chartmaster WHERE accountcode ='" . $SelectedAccount . "' AND language='" . $AccountLanguage . "'";
			$CountResult = DB_query($SQL);
			if (DB_num_rows($CountResult) > 0) {
				$GroupNameSQL = "SELECT groupname
									FROM accountgroups
									WHERE language='" . $AccountLanguage . "'
										AND groupcode='" . $_POST['Group'] . "'";
				$GroupNameResult = DB_query($GroupNameSQL);
				$GroupNameRow = DB_fetch_array($GroupNameResult);
				$SQL = "UPDATE chartmaster SET accountname='" . htmlspecialchars($AccountName) . "',
												group_='" . htmlspecialchars($GroupNameRow['groupname']) . "',
												groupcode='" . $_POST['Group'] . "',
												cashflowsactivity='" . $_POST['CashFlowsActivity'] . "'
											WHERE accountcode ='" . $SelectedAccount . "'
												AND language='" . $AccountLanguage . "'";

				$ErrMsg = _('Could not update the account because');
				$Result = DB_query($SQL, $ErrMsg);
				prnMsg(_('The general ledger account has been updated'), 'success');
			} else {
				$GroupNameSQL = "SELECT groupname
									FROM accountgroups
									WHERE language='" . $AccountLanguage . "'
										AND groupcode='" . $_POST['Group'] . "'";
				$GroupNameResult = DB_query($GroupNameSQL);
				$GroupNameRow = DB_fetch_array($GroupNameResult);
				$ErrMsg = _('Could not add the new account code');
				$SQL = "INSERT INTO chartmaster (accountcode,
												language,
												accountname,
												group_,
												groupcode,
												cashflowsactivity)
											VALUES (
												'" . $_POST['AccountCode'] . "',
												'" . $AccountLanguage . "',
												'" . $AccountName . "',
												'" . htmlspecialchars($GroupNameRow['groupname']) . "',
												'" . $_POST['Group'] . "',
												'" . $_POST['CashFlowsActivity'] . "'
											)";
				$Result = DB_query($SQL, $ErrMsg);

				prnMsg(_('The new general ledger account has been added'), 'success');
			}
		}

		$TotalsSQL = "INSERT INTO gltotals (account, period, amount)
						SELECT '" . $_POST['AccountCode'] . "', periodno, 0 FROM periods";
		$ErrMsg = _('An error occurred in adding a new account number to the gltotals table');
		$TotalsResult = DB_query($TotalsSQL, $ErrMsg);

	} elseif ($InputError != 1) {

		/*SelectedAccount is null cos no item selected on first time round so must be adding a	record must be submitting new entries */

		foreach ($AccountNames as $AccountLanguage => $AccountName) {
			$GroupNameSQL = "SELECT groupname
								FROM accountgroups
								WHERE language='" . $AccountLanguage . "'
									AND groupcode='" . $_POST['Group'] . "'";
			$GroupNameResult = DB_query($GroupNameSQL);
			$GroupNameRow = DB_fetch_array($GroupNameResult);
			$ErrMsg = _('Could not add the new account code');
			$SQL = "INSERT INTO chartmaster (accountcode,
											language,
											accountname,
											group_,
											groupcode,
											cashflowsactivity)
										VALUES (
											'" . $_POST['AccountCode'] . "',
											'" . $AccountLanguage . "',
											'" . $AccountName . "',
											'" . htmlspecialchars($GroupNameRow['groupname']) . "',
											'" . $_POST['Group'] . "',
											'" . $_POST['CashFlowsActivity'] . "'
										)";
			$Result = DB_query($SQL, $ErrMsg);

			prnMsg(_('The new general ledger account has been added'), 'success');
		}

		$TotalsSQL = "INSERT INTO gltotals (account, period, amount)
						SELECT '" . $_POST['AccountCode'] . "', periodno, 0 FROM periods";
		$ErrMsg = _('An error occurred in adding a new account number to the gltotals table');
		$TotalsResult = DB_query($TotalsSQL, $ErrMsg);
	}

	unset($_POST['Group']);
	unset($_POST['AccountCode']);
	unset($_POST['AccountName']);
	unset($_POST['CashFlowsActivity']);
	unset($SelectedAccount);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'ChartDetails'
	$SQL = "SELECT COUNT(*)
			FROM chartdetails
			WHERE chartdetails.accountcode ='" . $SelectedAccount . "'
			AND chartdetails.actual <>0";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		$CancelDelete = 1;
		prnMsg(_('Cannot delete this account because chart details have been created using this account and at least one period has postings to it.') . ' ' . _('There are') . ' ' . $MyRow[0] . ' ' . _('chart details that require this account code'), 'warn');
	} else {
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'GLTrans'
		$SQL = "SELECT COUNT(*)
				FROM gltrans
				WHERE gltrans.account ='" . $SelectedAccount . "'";

		$ErrMsg = _('Could not test for existing transactions because');

		$Result = DB_query($SQL, $ErrMsg);

		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$CancelDelete = 1;
			prnMsg(_('Cannot delete this account because transactions have been created using this account.') . ' ' . '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('transactions that require this account code'), 'warn');
		} else {
			//PREVENT DELETES IF Company default accounts set up to this account
			$SQL = "SELECT COUNT(*) FROM companies
					WHERE debtorsact='" . $SelectedAccount . "'
					OR pytdiscountact='" . $SelectedAccount . "'
					OR creditorsact='" . $SelectedAccount . "'
					OR payrollact='" . $SelectedAccount . "'
					OR grnact='" . $SelectedAccount . "'
					OR exchangediffact='" . $SelectedAccount . "'
					OR purchasesexchangediffact='" . $SelectedAccount . "'
					OR retainedearnings='" . $SelectedAccount . "'";

			$ErrMsg = _('Could not test for default company GL codes because');

			$Result = DB_query($SQL, $ErrMsg);

			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				$CancelDelete = 1;
				prnMsg(_('Cannot delete this account because it is used as one of the company default accounts'), 'warn');

			} else {
				//PREVENT DELETES IF Company default accounts set up to this account
				$SQL = "SELECT COUNT(*) FROM taxauthorities
					WHERE taxglcode='" . $SelectedAccount . "'
					OR purchtaxglaccount ='" . $SelectedAccount . "'";

				$ErrMsg = _('Could not test for tax authority GL codes because');
				$Result = DB_query($SQL, $ErrMsg);

				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0] > 0) {
					$CancelDelete = 1;
					prnMsg(_('Cannot delete this account because it is used as one of the tax authority accounts'), 'warn');
				} else {
					//PREVENT DELETES IF SALES POSTINGS USE THE GL ACCOUNT
					$SQL = "SELECT COUNT(*) FROM salesglpostings
						WHERE salesglcode='" . $SelectedAccount . "'
						OR discountglcode='" . $SelectedAccount . "'";

					$ErrMsg = _('Could not test for existing sales interface GL codes because');

					$Result = DB_query($SQL, $ErrMsg);

					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0] > 0) {
						$CancelDelete = 1;
						prnMsg(_('Cannot delete this account because it is used by one of the sales GL posting interface records'), 'warn');
					} else {
						//PREVENT DELETES IF COGS POSTINGS USE THE GL ACCOUNT
						$SQL = "SELECT COUNT(*)
								FROM cogsglpostings
								WHERE glcode='" . $SelectedAccount . "'";

						$ErrMsg = _('Could not test for existing cost of sales interface codes because');

						$Result = DB_query($SQL, $ErrMsg);

						$MyRow = DB_fetch_row($Result);
						if ($MyRow[0] > 0) {
							$CancelDelete = 1;
							prnMsg(_('Cannot delete this account because it is used by one of the cost of sales GL posting interface records'), 'warn');

						} else {
							//PREVENT DELETES IF STOCK POSTINGS USE THE GL ACCOUNT
							$SQL = "SELECT COUNT(*) FROM stockcategory
									WHERE stockact='" . $SelectedAccount . "'
									OR adjglact='" . $SelectedAccount . "'
									OR purchpricevaract='" . $SelectedAccount . "'
									OR materialuseagevarac='" . $SelectedAccount . "'
									OR wipact='" . $SelectedAccount . "'";

							$Errmsg = _('Could not test for existing stock GL codes because');

							$Result = DB_query($SQL, $ErrMsg);

							$MyRow = DB_fetch_row($Result);
							if ($MyRow[0] > 0) {
								$CancelDelete = 1;
								prnMsg(_('Cannot delete this account because it is used by one of the stock GL posting interface records'), 'warn');
							} else {
								//PREVENT DELETES IF STOCK POSTINGS USE THE GL ACCOUNT
								$SQL = "SELECT COUNT(*) FROM bankaccounts
								WHERE accountcode='" . $SelectedAccount . "'";
								$ErrMsg = _('Could not test for existing bank account GL codes because');

								$Result = DB_query($SQL, $ErrMsg);

								$MyRow = DB_fetch_row($Result);
								if ($MyRow[0] > 0) {
									$CancelDelete = 1;
									prnMsg(_('Cannot delete this account because it is used by one the defined bank accounts'), 'warn');
								} else {

									$SQL = "DELETE FROM chartdetails WHERE accountcode='" . $SelectedAccount . "'";
									$Result = DB_query($SQL);
									$SQL = "DELETE FROM chartmaster WHERE accountcode= '" . $SelectedAccount . "'";
									$Result = DB_query($SQL);
									prnMsg(_('Account') . ' ' . $SelectedAccount . ' ' . _('has been deleted'), 'succes');
								}
							}
						}
					}
				}
			}
		}
	}
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" id="GLAccounts" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (!isset($_POST['CashFlowsActivity'])) {
		$_POST['CashFlowsActivity'] = 0;
	}

	if (isset($SelectedAccount)) {
		//editing an existing account
		$SQL = "SELECT accountcode,
						language,
						accountname,
						groupcode,
						cashflowsactivity
					FROM chartmaster
					WHERE accountcode='" . $SelectedAccount . "'";

		$Result = DB_query($SQL);
		while ($MyRow = DB_fetch_array($Result)) {
			$_POST['AccountCode'] = $MyRow['accountcode'];
			$AccountName[$MyRow['language']] = $MyRow['accountname'];
			$_POST['Group'] = $MyRow['groupcode'];
			$_POST['CashFlowsActivity'] = $MyRow['cashflowsactivity'];
		}

		echo '<input type="hidden" name="SelectedAccount" value="', $SelectedAccount, '" />';
		echo '<input type="hidden" name="AccountCode" value="', $_POST['AccountCode'], '" />';
		echo '<fieldset>
				<legend>', _('Edit Account Details'), '</legend>
				<field>
					<label for="AccountCode">', _('Account Code'), ':</label>
					<div class="fieldtext">', $_POST['AccountCode'], '</div>
				</field>';
		$SQL = "SELECT DISTINCT language FROM accountsection";
		$LanguageResult = DB_query($SQL);
		while ($LanguageRow = DB_fetch_array($LanguageResult)) {
			if (!isset($AccountName[$LanguageRow['language']])) {
				$AccountName[$LanguageRow['language']] = '';
			}
			echo '<field>
					<label for="AccountName">', _('Account Name'), ' (', $LanguagesArray[$LanguageRow['language']]['LanguageName'], ') :</label>
					<input type="text" size="51" autofocus="autofocus" required="required" maxlength="150" name="AccountName' . mb_substr($LanguageRow['language'], 0, 5) . '" value="', $AccountName[$LanguageRow['language']], '" />
					<fieldhelp>', _('The name of the general ledger account in'), ' ', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</fieldhelp>
				</field>';
		}
	} else {
		echo '<fieldset>
				<legend>', _('New Account Details'), '</legend>
				<field>
					<label for="AccountCode">', _('Account Code'), ':</label>
					<input type="text" name="AccountCode" size="11" autofocus="autofocus" required="required" maxlength="20" />
					<fieldhelp>', _('The code by which this general ledger code will be known.'), '</fieldhelp>
				</field>';
		$SQL = "SELECT DISTINCT language FROM accountsection";
		$LanguageResult = DB_query($SQL);
		while ($LanguageRow = DB_fetch_array($LanguageResult)) {
			echo '<field>
					<label for="AccountName">', _('Account Name'), ' (', $LanguagesArray[$LanguageRow['language']]['LanguageName'], ') :</label>
					<td><input type="text" size="51" autofocus="autofocus" required="required" maxlength="150" name="AccountName', mb_substr($LanguageRow['language'], 0, 5), '" value="" />
					<fieldhelp>', _('The name of the general ledger account in'), ' ', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</fieldhelp>
				</field>';
		}
	}

	if (!isset($_POST['AccountName'])) {
		$_POST['AccountName'] = '';
	}

	$SQL = "SELECT groupcode, groupname FROM accountgroups WHERE language='" . $SelectedLanguage . "' ORDER BY sequenceintb";
	$Result = DB_query($SQL);

	echo '<field>
			<label for="Group">', _('Account Group'), ':</label>
			<td><select required="required" name="Group">';

	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['Group']) and $MyRow['groupcode'] == $_POST['Group']) {
			echo '<option selected="selected" value="', $MyRow['groupcode'], '">', $MyRow['groupcode'], ' - ', $MyRow['groupname'], '</option>';
		} else {
			echo '<option value="', $MyRow['groupcode'], '">', $MyRow['groupcode'], ' - ', $MyRow['groupname'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the Account Group that this account belongs to.'), '</fieldhelp>
	</field>';

	echo '<field>
				<label for="CashFlowsActivity">', _('Cash Flows Activity'), ':</label>
				<select id="CashFlowsActivity" name="CashFlowsActivity" required="required">
					<option value="0"', ($_POST['CashFlowsActivity'] == 0 ? ' selected="selected"' : ''), '>', _('No effect on cash flow'), '</option>
					<option value="1"', ($_POST['CashFlowsActivity'] == 1 ? ' selected="selected"' : ''), '>', _('Operating activity'), '</option>
					<option value="2"', ($_POST['CashFlowsActivity'] == 2 ? ' selected="selected"' : ''), '>', _('Investing activity'), '</option>
					<option value="3"', ($_POST['CashFlowsActivity'] == 3 ? ' selected="selected"' : ''), '>', _('Financing activity'), '</option>
					<option value="4"', ($_POST['CashFlowsActivity'] == 4 ? ' selected="selected"' : ''), '>', _('Cash or cash equivalent'), '</option>
				</select>
				<fieldhelp>', _('Select the cash flow activity (if any) applicable to this account.'), '</fieldhelp>
			</field>
		</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Enter Information'), '" />
		</div>';
	echo '</form>';

} //end if record deleted no point displaying form to add record


if (!isset($SelectedAccount)) {
	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedAccount will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of ChartMaster will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT accountcode,
					accountname,
					group_,
					chartmaster.groupcode,
					CASE WHEN pandl=0
						THEN '" . _('Balance Sheet') . "'
						ELSE '" . _('Profit/Loss') . "'
					END AS acttype,
					cashflowsactivity
				FROM chartmaster
				INNER JOIN accountgroups
					ON chartmaster.groupcode=accountgroups.groupcode
					AND chartmaster.language=accountgroups.language
				WHERE chartmaster.language='" . $SelectedLanguage . "'
				ORDER BY chartmaster.accountcode";

	$ErrMsg = _('The chart accounts could not be retrieved because');

	$Result = DB_query($SQL, $ErrMsg);

	echo '<form method="post" id="GLAccounts" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<legend>', _('Language selection'), '</legend>';

	echo '<field>
			<label for="SelectedLanguage">', _('Language to show'), ':</label>
			<select name="SelectedLanguage">';

	$SQL = "SELECT DISTINCT language FROM accountsection";
	$LanguageResult = DB_query($SQL);
	while ($LanguageRow = DB_fetch_array($LanguageResult)) {
		if (isset($_POST['SelectedLanguage']) and $_POST['SelectedLanguage'] == $LanguageRow['language']) {
			echo '<option selected="selected" value="', $LanguageRow['language'], '">', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</option>';
		} elseif ($LanguageRow['language'] == $SelectedLanguage) {
			echo '<option selected="selected" value="', $LanguageRow['language'], '">', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</option>';
		} else {
			echo '<option value="', $LanguageRow['language'], '">', $LanguagesArray[$LanguageRow['language']]['LanguageName'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the language for this account section. Note each language must have all the same account sections setup.'), '</fieldhelp>
	</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Select Language'), '" />
		</div>';

	echo '</form>';

	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Account Code'), '</th>
					<th class="SortedColumn">', _('Account Name'), '</th>
					<th class="SortedColumn">', _('Account Group Code'), '</th>
					<th class="SortedColumn">', _('Account Group Name'), '</th>
					<th class="SortedColumn">', _('P/L or B/S'), '</th>
					<th class="SortedColumn">', _('Cash Flows Activity'), '</th>
					<th colspan="2"></th>
				</tr>
			</thead>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {

		echo '<tr class="striped_row">
				<td>', $MyRow['accountcode'], '</td>
				<td>', $MyRow['accountname'], '</td>
				<td>', $MyRow['groupcode'], '</td>
				<td>', $MyRow['group_'], '</td>
				<td>', $MyRow['acttype'], '</td>
				<td class="text">', CashFlowsActivityName($MyRow['cashflowsactivity']), '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?', '&amp;SelectedAccount=', urlencode($MyRow['accountcode']), '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?', '&amp;SelectedAccount=', urlencode($MyRow['accountcode']), '&amp;delete=1" onclick="return MakeConfirm("', _('Are you sure you wish to delete this account? Additional checks will be performed in any event to ensure data integrity is not compromised.'), '", \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';

	}
	//END WHILE LIST LOOP
	echo '</tbody>';
	echo '</table>';
} //END IF selected ACCOUNT
if (isset($SelectedAccount)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Show All Accounts'), '</a>
		</div>';
}

include ('includes/footer.php');
?>