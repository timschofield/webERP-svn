<?php
include ('includes/session.php');
$Title = _('Customer Login Configuration');
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.inc');
include ('includes/LanguagesArray.php');

if (!isset($_SESSION['CustomerID'])) {
	prnMsg(_('A customer must first be selected before logins can be defined for it') . '<br /><br /><a href="' . $RootPath . '/SelectCustomer.php">' . _('Select A Customer') . '</a>', 'info');
	include ('includes/footer.php');
	exit;
}

echo '<div class="toplink">
		<a href="', $RootPath, '/SelectCustomer.php">', _('Back to Customers'), '</a>
	</div>';

$SQL = "SELECT name
		FROM debtorsmaster
		WHERE debtorno='" . $_SESSION['CustomerID'] . "'";

$Result = DB_query($SQL);
$MyRow = DB_fetch_array($Result);
$CustomerName = $MyRow['name'];

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" />', ' ', _('Customer'), ' : ', $_SESSION['CustomerID'], ' - ', $CustomerName, _(' has been selected'), '
	</p>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (mb_strlen($_POST['UserID']) < 4) {
		$InputError = 1;
		prnMsg(_('The user ID entered must be at least 4 characters long'), 'error');
	} elseif (ContainsIllegalCharacters($_POST['UserID']) or mb_strstr($_POST['UserID'], ' ')) {
		$InputError = 1;
		prnMsg(_('User names cannot contain any of the following characters') . " - ' &amp; + \" \\ " . _('or a space'), 'error');
	} elseif (mb_strlen($_POST['Password']) < 5) {
		if (!$SelectedUser) {
			$InputError = 1;
			prnMsg(_('The password entered must be at least 5 characters long'), 'error');
		}
	} elseif (mb_strstr($_POST['Password'], $_POST['UserID']) != false) {
		$InputError = 1;
		prnMsg(_('The password cannot contain the user id'), 'error');
	} elseif ((mb_strlen($_POST['Cust']) > 0) and (mb_strlen($_POST['BranchCode']) == 0)) {
		$InputError = 1;
		prnMsg(_('If you enter a Customer Code you must also enter a Branch Code valid for this Customer'), 'error');
	}

	if ((mb_strlen($_POST['BranchCode']) > 0) and ($InputError != 1)) {
		// check that the entered branch is valid for the customer code
		$SQL = "SELECT defaultlocation
				FROM custbranch
				WHERE debtorno='" . $_SESSION['CustomerID'] . "'
				AND branchcode='" . $_POST['BranchCode'] . "'";

		$ErrMsg = _('The check on validity of the customer code and branch failed because');
		$DbgMsg = _('The SQL that was used to check the customer code and branch was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		if (DB_num_rows($Result) == 0) {
			prnMsg(_('The entered Branch Code is not valid for the entered Customer Code'), 'error');
			$InputError = 1;
		} else {
			$MyRow = DB_fetch_row($Result);
			$InventoryLocation = $MyRow[0];
		}

		if ($InputError != 1) {

			$SQL = "INSERT INTO www_users (userid,
										realname,
										customerid,
										branchcode,
										password,
										phone,
										email,
										pagesize,
										fullaccess,
										defaultlocation,
										modulesallowed,
										displayrecordsmax,
										theme,
										language)
									VALUES ('" . $_POST['UserID'] . "',
											'" . $_POST['RealName'] . "',
											'" . $_SESSION['CustomerID'] . "',
											'" . $_POST['BranchCode'] . "',
											'" . CryptPass($_POST['Password']) . "',
											'" . $_POST['Phone'] . "',
											'" . $_POST['Email'] . "',
											'" . $_POST['PageSize'] . "',
											'7',
											'" . $InventoryLocation . "',
											'1,1,0,0,0,0,0,0',
											'" . $_SESSION['DefaultDisplayRecordsMax'] . "',
											'" . $_POST['Theme'] . "',
											'" . $_POST['UserLanguage'] . "')";

			$ErrMsg = _('The user could not be added because');
			$DbgMsg = _('The SQL that was used to insert the new user and failed was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
			prnMsg(_('A new customer login has been created'), 'success');
			include ('includes/footer.php');
			exit;
		}
	}

}

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<fieldset>
		<legend>', _('Customer Login Information'), '</legend>
		<field>
			<label for="UserID">', _('User Login'), ':</label>
			<input type="text" name="UserID" size="22" required="required" maxlength="20" />
		</field>';

if (!isset($_POST['Password'])) {
	$_POST['Password'] = '';
}
if (!isset($_POST['RealName'])) {
	$_POST['RealName'] = '';
}
if (!isset($_POST['Phone'])) {
	$_POST['Phone'] = '';
}
if (!isset($_POST['Email'])) {
	$_POST['Email'] = '';
}

echo '<field>
		<label for="Password">', _('Password'), ':</label>
		<input type="password" name="Password" size="22" required="required" maxlength="20" value="', $_POST['Password'], '" />
	</field>
	<field>
		<label for="RealName">', _('Full Name'), ':</label>
		<input type="text" name="RealName" value="', $_POST['RealName'], '" size="36" required="required" maxlength="35" />
	</field>
	<field>
		<label for="Phone">', _('Telephone No'), ':</label>
		<input type="tel" name="Phone" value="', $_POST['Phone'], '" size="32" maxlength="30" />
	</field>
	<field>
		<label for="Email">', _('Email Address'), ':</label>
		<input type="email" name="Email" value="', $_POST['Email'], '" size="32" required="required" maxlength="55" />
	</field>
	<field>
		<input type="hidden" name="Access" value="1" />
		<label for="BranchCode">', _('Branch Code'), ':</label>
		<select name="BranchCode">';

$SQL = "SELECT branchcode FROM custbranch WHERE debtorno = '" . $_SESSION['CustomerID'] . "'";
$Result = DB_query($SQL);

while ($MyRow = DB_fetch_array($Result)) {

	//Set the first available branch as default value when nothing is selected
	if (!isset($_POST['BranchCode'])) {
		$_POST['BranchCode'] = $MyRow['branchcode'];
	}

	if (isset($_POST['BranchCode']) and $MyRow['branchcode'] == $_POST['BranchCode']) {
		echo '<option selected="selected" value="', $MyRow['branchcode'], '">', $MyRow['branchcode'], '</option>';
	} else {
		echo '<option value="', $MyRow['branchcode'], '">', $MyRow['branchcode'], '</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="PageSize">', _('Reports Page Size'), ':</label>
		<select name="PageSize">';

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'A4') {
	echo '<option selected="selected" value="A4">', _('A4'), '</option>';
} else {
	echo '<option value="A4">', _('A4'), '</option>';
}

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'A3') {
	echo '<option selected="selected" value="A3">', _('A3'), '</option>';
} else {
	echo '<option value="A3">', _('A3'), '</option>';
}

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'A3_landscape') {
	echo '<option selected="selected" value="A3_landscape">', _('A3'), ' ', _('landscape'), '</option>';
} else {
	echo '<option value="A3_landscape">', _('A3'), ' ', _('landscape'), '</option>';
}

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'letter') {
	echo '<option selected="selected" value="letter">', _('Letter'), '</option>';
} else {
	echo '<option value="letter">', _('Letter'), '</option>';
}

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'letter_landscape') {
	echo '<option selected="selected" value="letter_landscape">', _('Letter'), ' ', _('landscape'), '</option>';
} else {
	echo '<option value="letter_landscape">', _('Letter'), ' ', _('landscape'), '</option>';
}

if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'legal') {
	echo '<option selected="selected" value="legal">', _('Legal'), '</option>';
} else {
	echo '<option value="legal">', _('Legal'), '</option>';
}
if (isset($_POST['PageSize']) and $_POST['PageSize'] == 'legal_landscape') {
	echo '<option selected="selected" value="legal_landscape">', _('Legal'), ' ', _('landscape'), '</option>';
} else {
	echo '<option value="legal_landscape">', _('Legal'), ' ', _('landscape'), '</option>';
}

echo '</select>
	</field>';

echo '<field>
		<label for="Theme">', _('Theme'), ':</label>
		<select name="Theme">';

$Themes = glob('css/*', GLOB_ONLYDIR);
foreach ($Themes as $ThemeName) {
	$ThemeName = basename($ThemeName);
	if ($ThemeName != 'mobile' and mb_substr($ThemeName, -4) != '-rtl') {
		if ($_SESSION['Theme'] == $ThemeName) {
			echo '<option selected="selected" value="', $ThemeName, '">', ucfirst($ThemeName), '</option>';
		} else {
			echo '<option value="', $ThemeName, '">', ucfirst($ThemeName), '</option>';
		}
	}
}

echo '</select>
	</field>';

echo '<field>
		<label for="UserLanguage">', _('Language'), ':</label>
		<select name="UserLanguage">';

foreach ($LanguagesArray as $LanguageEntry => $LanguageName) {
	if (isset($_POST['UserLanguage']) and $_POST['UserLanguage'] == $LanguageEntry) {
		echo '<option selected="selected" value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
	} elseif (!isset($_POST['UserLanguage']) and $LanguageEntry == $_SESSION['DefaultLanguage']) {
		echo '<option selected="selected" value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
	} else {
		echo '<option value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
	}
}
echo '</select>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="', _('Enter Information'), '" />
	</div>
</form>';

include ('includes/footer.php');
?>