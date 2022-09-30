<?php
include ('includes/session.php');
$Title = _('Bank Account Users');; // Screen identificator.
$ViewTopic = 'GeneralLedger'; // Filename's id in ManualContents.php's TOC.
/* To do this section in the manual.
 $BookMark = 'BankAccountUsers';// Anchor's id in the manual's html document.*/
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/money_add.png" title="', _('Bank Account Authorised Users'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_POST['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_POST['SelectedUser']);
} elseif (isset($_GET['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_GET['SelectedUser']);
} else {
	$SelectedUser = '';
}

if (isset($_POST['SelectedBankAccount'])) {
	$SelectedBankAccount = mb_strtoupper($_POST['SelectedBankAccount']);
} elseif (isset($_GET['SelectedBankAccount'])) {
	$SelectedBankAccount = mb_strtoupper($_GET['SelectedBankAccount']);
}

if (isset($_POST['Cancel'])) {
	unset($SelectedBankAccount);
	unset($SelectedUser);
}

if (isset($_POST['Process'])) {
	if ($_POST['SelectedBankAccount'] == '') {
		echo prnMsg(_('You have not selected any bank account'), 'error');
		echo '<br />';
		unset($SelectedBankAccount);
		unset($_POST['SelectedBankAccount']);
	}
}

if (isset($_POST['Accept']) or isset($_POST['SelectedUser'])) {

	$InputError = 0;

	if ($_POST['SelectedUser'] == '') {
		$InputError = 1;
		echo prnMsg(_('You have not selected an user to be authorised to use this bank account'), 'error');
		echo '<br />';
		unset($SelectedBankAccount);
	}

	if ($InputError != 1) {

		// First check the user is not being duplicated
		$CheckSql = "SELECT count(*)
			     FROM bankaccountusers
			     WHERE accountcode= '" . $_POST['SelectedBankAccount'] . "'
				 AND userid = '" . $_POST['SelectedUser'] . "'";

		$CheckResult = DB_query($CheckSql);
		$CheckRow = DB_fetch_row($CheckResult);

		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The user') . ' ' . $_POST['SelectedUser'] . ' ' . _('already authorised to use this bank account'), 'error');
		} else {
			// Add new record on submit
			$SQL = "INSERT INTO bankaccountusers (accountcode,
												userid)
										VALUES ('" . $_POST['SelectedBankAccount'] . "',
												'" . $_POST['SelectedUser'] . "')";

			$Msg = _('User') . ': ' . $_POST['SelectedUser'] . ' ' . _('has been authorised to use') . ' ' . $_POST['SelectedBankAccount'] . ' ' . _('bank account');
			$Result = DB_query($SQL);
			prnMsg($Msg, 'success');
			unset($_POST['SelectedUser']);
		}
	}
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM bankaccountusers
		WHERE accountcode='" . $SelectedBankAccount . "'
		AND userid='" . $SelectedUser . "'";

	$ErrMsg = _('The bank account user record could not be deleted because');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(_('User') . ' ' . $SelectedUser . ' ' . _('has been un-authorised to use') . ' ' . $SelectedBankAccount . ' ' . _('bank account'), 'success');
	unset($_GET['delete']);
}

if (!isset($SelectedBankAccount)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedUser will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	 then none of the above are true. These will call the same page again and allow update/input or deletion of the records*/
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" id="SelectAccount">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<fieldset>
			<legend>', _('Select the Bank Account'), '</legend>
			<field>
				<label for="SelectedBankAccount">', _('Manage Users For'), ':</label>
				<select name="SelectedBankAccount" autofocus="autofocus" onChange="return SelectAccount.submit();">';

	$SQL = "SELECT accountcode,
					bankaccountname
			FROM bankaccounts
			ORDER BY bankaccounts.bankaccountname";

	$Result = DB_query($SQL);
	echo '<option value="">', _('Not Yet Selected'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($SelectedBankAccount) and $MyRow['accountcode'] == $SelectedBankAccount) {
			echo '<option selected="selected" value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', $MyRow['bankaccountname'], '</option>';
		} else {
			echo '<option value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', $MyRow['bankaccountname'], '</option>';
		}
	} //end while loop
	echo '</select>
		</field>';

	echo '</fieldset>'; // close main table
	echo '<div class="centre">
			<input type="submit" name="Process" value="', _('Accept'), '" />
			<input type="submit" name="Cancel" value="', _('Cancel'), '" />
		</div>';

	echo '</form>';

}

//end of ifs and buts!
if (isset($_POST['process']) or isset($SelectedBankAccount)) {
	$SQLName = "SELECT bankaccountname
			FROM bankaccounts
			WHERE accountcode='" . $SelectedBankAccount . "'";
	$Result = DB_query($SQLName);
	$MyRow = DB_fetch_array($Result);
	$SelectedBankName = $MyRow['bankaccountname'];

	echo '<div class="centre"><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">' . _('Authorised users for') . ' ' . $SelectedBankName . ' ' . _('bank account') . '</a></div>';

	$SQL = "SELECT bankaccountusers.userid,
					www_users.realname
			FROM bankaccountusers INNER JOIN www_users
			ON bankaccountusers.userid=www_users.userid
			WHERE bankaccountusers.accountcode='" . $SelectedBankAccount . "'
			ORDER BY bankaccountusers.userid ASC";

	$Result = DB_query($SQL);

	echo '<table>';
	echo '<tr>
			<th colspan="3"><h3>', _('Authorised users for bank account'), ' ', $SelectedBankName, '</h3></th>
		</tr>';
	echo '<tr>
			<th>', _('User Code'), '</th>
			<th>', _('User Name'), '</th>
			<th></th>
		</tr>';

	while ($MyRow = DB_fetch_array($Result)) {

		echo '<tr class="striped_row">
				<td>', $MyRow['userid'], '</td>
				<td>', $MyRow['realname'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedUser=', urlencode($MyRow['userid']), '&amp;delete=yes&amp;SelectedBankAccount=', urlencode($SelectedBankAccount), '" onclick="return MakeConfirm(\'', _('Are you sure you wish to un-authorise this user?'), '\', \'Confirm Delete\', this);">', _('Un-authorise'), '</a></td>
			</tr>';
	}
	//END WHILE LIST LOOP
	echo '</table>';

	if (!isset($_GET['delete'])) {

		echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" id="UserSelect">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

		echo '<input type="hidden" name="SelectedBankAccount" value="', $SelectedBankAccount, '" />';

		echo '<fieldset>'; //Main table
		echo '<field>
				<label for="SelectedUser">', _('Select User'), ':</label>
				<select name="SelectedUser" autofocus="autofocus" onChange="UserSelect.submit();">';

		$SQL = "SELECT userid,
						realname
				FROM www_users";

		$Result = DB_query($SQL);
		if (!isset($_POST['SelectedUser'])) {
			echo '<option selected="selected" value="">', _('Not Yet Selected'), '</option>';
		}
		while ($MyRow = DB_fetch_array($Result)) {
			$CheckSQL = "SELECT accountcode
							FROM bankaccountusers
							WHERE userid='" . $MyRow['userid'] . "'
							 AND accountcode='" . $SelectedBankAccount . "'";
			$CheckResult = DB_query($CheckSQL);
			if (DB_num_rows($CheckResult) == 0) {
				if (isset($_POST['SelectedUser']) and $MyRow['userid'] == $_POST['SelectedUser']) {
					echo '<option selected="selected" value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
				} else {
					echo '<option value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
				}
			}
		} //end while loop
		echo '</select>
			</field>';

		echo '</fieldset>'; // close main table
		echo '<div class="centre">
				<input type="submit" name="Accept" value="', _('Accept'), '" />
				<input type="reset" name="Cancel" value="', _('Cancel'), '" />
			</div>';

		echo '</form>';

	} // end if user wish to delete

}

include ('includes/footer.php');
?>