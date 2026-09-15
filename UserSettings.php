<?php

// user parameters that the user is allowed to change themselves
// SUBSET of parameters in WWW_Users.php

require(__DIR__ . '/includes/session.php');
require_once(__DIR__ . '/includes/PasswordValidations.php');

$Title = __('User Settings');
$ViewTopic = 'GettingStarted';
$BookMark = 'UserSettings';

include(__DIR__ . '/includes/header.php');

echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme,
	'/images/user.png" title="', // Icon image.
	$Title, '" /> ', // Icon title.
	$Title, '</p>'; // Page title.

$PDFLanguages = array(
	__('Latin Western Languages - Times'),
	__('Eastern European Russian Japanese Korean Hebrew Arabic Thai'),
	__('Chinese'),
	__('Free Serif')
);

if (isset($_POST['Modify'])) {
	// no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if ($_POST['DisplayRecordsMax'] <= 0) {
		$InputError = 1;
		prnMsg(__('The Maximum Number of Records on Display entered must not be negative') . '. ' . __('0 will default to system setting'),'error');
	}

	//!!!for the demo only - enable this check so password is not changed
	if ($AllowDemoMode AND $_POST['Password'] != '') {
		$InputError = 1;
		prnMsg(__('Cannot change password in the demo or others would be locked out!'),'warn');
	}

 	$UpdatePassword = 'N';

	if ($_POST['PasswordCheck'] != '') {
		if (($PasswordValidationError = ValidatePasswordQuality($_POST['Password'], $_SESSION['UserID'])) != '') {
			$InputError = 1;
			prnMsg($PasswordValidationError, 'error');
		}
		if ($_POST['Password'] != $_POST['PasswordCheck']) {
			$InputError = 1;
			prnMsg(__('The password and password confirmation fields entered do not match'), 'error');
		} elseif ($PasswordValidationError == '') {
			$UpdatePassword = 'Y';
		}
	}

	if ($InputError != 1) {
		// no errors
		if (isset($_POST['Language']) && !checkLanguageChoice($_POST['Language'])) {
			$_POST['Language'] = $DefaultLanguage;
		}

		$SQL = "UPDATE www_users
				SET displayrecordsmax='" . $_POST['DisplayRecordsMax'] . "',
					theme='" . $_POST['Theme'] . "',
                    fontsize='" . $_POST['FontSize'] . "',
					language='" . $_POST['Language'] . "',
					email='" . $_POST['email'] . "',
					pagesize='" . $_POST['PageSize'] . "',
					showpagehelp='" . $_POST['ShowPageHelp'] . "',
					showfieldhelp='" . $_POST['ShowFieldHelp'] . "',
					pdflanguage='" . $_POST['PDFLanguage'] . "'";

		if ($UpdatePassword == 'Y') {
			$SQL .= ",
					password='" . CryptPass($_POST['Password']) . "',
					forcepasswordchange=0";
		}

		$SQL .= " WHERE userid = '" . $_SESSION['UserID'] . "'";

		$ErrMsg = __('The user alterations could not be processed because');
		$Result = DB_query($SQL, $ErrMsg);

		if ($UpdatePassword != 'Y') {
			prnMsg( __('The user settings have been updated') . '. ' . __('Be sure to remember your password for the next time you login'),'success');
		} else {
			prnMsg(__('The user settings have been updated'),'success');
			// update the flag in this session, so user does not get bothered to change password again
			$_SESSION['ForcePasswordChange'] = 0;
		}
		// Update the session variables to reflect user changes on-the-fly:
		$_SESSION['DisplayRecordsMax'] = $_POST['DisplayRecordsMax'];
		$_SESSION['Theme'] = trim($_POST['Theme']); /*already set by session.php but for completeness */
		$Theme = $_SESSION['Theme'];
		$_SESSION['Language'] = trim($_POST['Language']);
		$_SESSION['PageSize'] = $_POST['PageSize'];
		$_SESSION['ShowPageHelp'] = $_POST['ShowPageHelp'];
		$_SESSION['ShowFieldHelp'] = $_POST['ShowFieldHelp'];
		$_SESSION['PDFLanguage'] = $_POST['PDFLanguage'];
		include($PathPrefix . 'includes/LanguageSetup.php'); // After last changes in LanguageSetup.php, is it required to update?
	}
}

$SQL = "SELECT
			email,
			pagesize,
			showpagehelp,
			showfieldhelp,
            fontsize,
			language
		FROM www_users
		WHERE userid = '" . $_SESSION['UserID'] . "'";
$Result = DB_query($SQL);
$MyRow = DB_fetch_array($Result);

if (!isset($_POST['email'])) {
	$_POST['email'] = $MyRow['email'];
}
$_POST['PageSize'] = $MyRow['pagesize'];
$_POST['ShowPageHelp'] = $MyRow['showpagehelp'];
$_POST['ShowFieldHelp'] = $MyRow['showfieldhelp'];
$_POST['Language'] = $MyRow['language'];
$_POST['FontSize'] = $MyRow['fontsize'];

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">',
	'<input name="FormID" value="', $_SESSION['FormID'], '" type="hidden" />';

echo '<fieldset>
		<legend>', __('Edit User Settings'), '</legend>
		<field>
			<label for="UserID">', __('User ID'), ':</label>
			<fieldtext>', $_SESSION['UserID'], '</fieldtext>
		</field>';

echo '<field>
		<label for="UsersRealName">', __('User Name'), ':</label>
		<fieldtext>', $_SESSION['UsersRealName'], '<input name="RealName" type="hidden" value="', $_SESSION['UsersRealName'], '" /></fieldtext>
	</field>';

echo '<field>
		<label for="DisplayRecordsMax">', __('Maximum Number of Records to Display'), ':</label>
		<input class="integer" maxlength="3" name="DisplayRecordsMax" required="required" size="3" title="', __('The input must be positive integer'), '" type="text" value="', $_SESSION['DisplayRecordsMax'], '" />
	</field>';

// Select language:
echo '<field>
		<label for="Language">', __('Language'), ':</label>
		<select name="Language">';
if (!isset($_POST['Language'])) {
	$_POST['Language'] = $_SESSION['Language'];
}
foreach($LanguagesArray as $LanguageEntry => $LanguageName) {
	echo '<option ';
	if (isset($_POST['Language']) AND $_POST['Language'] == $LanguageEntry) {
		echo 'selected="selected" ';
	}
	echo 'value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
}
echo '</select>
	</field>';

// Select theme:
echo '<field>
		<label for="Theme">' . __('Theme') . ':</label>
		<select name="Theme">';

$ThemeDirectories = scandir($PathPrefix . 'css/');

foreach ($ThemeDirectories as $ThemeName) {
	if (is_dir('css/' . $ThemeName) AND $ThemeName != '.' AND $ThemeName != '..' AND $ThemeName != '.svn') {

		if ($_SESSION['Theme'] == $ThemeName) {
			echo '<option selected="selected" value="' . $ThemeName . '">' . $ThemeName . '</option>';
		} else {
			echo '<option value="' . $ThemeName . '">' . $ThemeName . '</option>';
		}
	}
}

if (!isset($_POST['PasswordCheck'])) {
	$_POST['PasswordCheck']='';
}
if (!isset($_POST['Password'])) {
	$_POST['Password']='';
}
echo '</select>
	</field>';

echo '<field>
        <label for="FontSize">', __('Display Font'), '</label>
        <select name="FontSize">';
switch ($_POST['FontSize']) {
    case 0:
        echo '<option selected value="0">', __('Small'), '</option>
            <option value="1">', __('Medium'), '</option>
            <option value="2">', __('Large'), '</option>';
        break;
    case 1:
        echo '<option value="0">', __('Small'), '</option>
            <option selected value="1">', __('Medium'), '</option>
            <option value="2">', __('Large'), '</option>';
        break;
    case 2:
        echo '<option value="0">', __('Small'), '</option>
            <option value="1">', __('Medium'), '</option>
            <option selected value="2">', __('Large'), '</option>';
        break;                                                                                          
    default:
        echo '<option value="0">', __('Small'), '</option>
            <option value="1">', __('Medium'), '</option>
            <option value="2">', __('Large'), '</option>';
        break;
}
echo '</select>
        <fieldhelp>', __('Select the size of the screen font') , '</fieldhelp>
    </field>';

echo '<field>
		<label for="PageSize">', __('Report Page Size'), ':</label>
		<select name="PageSize">';

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='A4') {
	echo '<option selected="selected" value="A4">', __('A4'), '</option>';
} else {
	echo '<option value="A4">', __('A4'), '</option>';
}

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='A3') {
	echo '<option selected="selected" value="A3">', __('A3'), '</option>';
} else {
	echo '<option value="A3">', __('A3'), '</option>';
}

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='A3_Landscape') {
	echo '<option selected="selected" value="A3_Landscape">', __('A3'), ' ', __('landscape'), '</option>';
} else {
	echo '<option value="A3_Landscape">', __('A3'), ' ', __('landscape'), '</option>';
}

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='Letter') {
	echo '<option selected="selected" value="Letter">', __('Letter'), '</option>';
} else {
	echo '<option value="Letter">', __('Letter'), '</option>';
}

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='Letter_Landscape') {
	echo '<option selected="selected" value="Letter_Landscape">', __('Letter'), ' ', __('landscape'), '</option>';
} else {
	echo '<option value="Letter_Landscape">', __('Letter'), ' ', __('landscape'), '</option>';
}

if (isset($_POST['PageSize']) AND $_POST['PageSize']=='Legal') {
	echo '<option selected="selected" value="Legal">', __('Legal'), '</option>';
} else {
	echo '<option value="Legal">', __('Legal'), '</option>';
}
if (isset($_POST['PageSize']) AND $_POST['PageSize']=='Legal_Landscape') {
	echo '<option selected="selected" value="Legal_Landscape">', __('Legal'), ' ', __('landscape'), '</option>';
} else {
	echo '<option value="Legal_Landscape">', __('Legal'), ' ', __('landscape'), '</option>';
}

echo '</select>
	</field>';

echo '<field>
		<label for="Password">', __('New Password'), ':</label>
		<input id="password" name="Password" placeholder="', __('At least'), ' ', (isset($_SESSION['PasswordMinLenght']) ? $_SESSION['PasswordMinLenght'] : 5), ' ', __('characters'), '" size="20" type="password" value="', $_POST['Password'], '" />
        <img class="eye" id="eye" alt="" src="', $RootPath, '/css/eye.png" title="' . __('Show Password') . '" />
	</field>';

echo '<field>
		<label for="PasswordCheck">', __('Confirm Password'), ':</label>
		<input id="confirmpassword" name="PasswordCheck" placeholder="', __('At least'), ' ', (isset($_SESSION['PasswordMinLenght']) ? $_SESSION['PasswordMinLenght'] : 5), ' ', __('characters'), '" size="20" type="password" value="', $_POST['PasswordCheck'], '" />
        <img class="eye" id="confirmeye" alt="" src="', $RootPath, '/css/eye.png" title="' . __('Show Password') . '" />
	</field>';

echo '<field>
		<label for="email">', __('Email'), ':</label>
		<input name="email" size="40" type="email" value="', $_POST['email'], '" />
	</field>';

// Turn off/on page help:
echo '<field>
		<label for="ShowPageHelp">', __('Display page help'), ':</label>
		<select id="ShowPageHelp" name="ShowPageHelp">';
if ($_POST['ShowPageHelp']==0) {
	echo '<option selected="selected" value="0">', __('No'), '</option>',
		 '<option value="1">', __('Yes'), '</option>';
} else {
	echo '<option value="0">', __('No'), '</option>',
 		 '<option selected="selected" value="1">', __('Yes'), '</option>';
}
echo '</select>
	<fieldhelp>', __('Show page help when available'), '</fieldhelp>
</field>';

// Turn off/on field help:
echo '<field>
		<label for="ShowFieldHelp">', __('Display field help'), ':</label>
		<select id="ShowFieldHelp" name="ShowFieldHelp">';
if ($_POST['ShowFieldHelp']==0) {
	echo '<option selected="selected" value="0">', __('No'), '</option>',
		 '<option value="1">', __('Yes'), '</option>';
} else {
	echo '<option value="0">', __('No'), '</option>',
 		 '<option selected="selected" value="1">', __('Yes'), '</option>';
}
echo '</select>
	<fieldhelp>', __('Show field help when available'), '</fieldhelp>
</field>';
// PDF Language Support:
if (!isset($_POST['PDFLanguage'])) {
	$_POST['PDFLanguage']=$_SESSION['PDFLanguage'];
}
echo '<field>
		<label for="PDFLanguage">', __('PDF Language Support'), ': </label>
		<select name="PDFLanguage">';
for($i=0; $i<count($PDFLanguages); $i++) {
	if ($_POST['PDFLanguage'] == $i) {
		echo '<option selected="selected" value="', $i, '">', $PDFLanguages[$i], '</option>';
	} else {
		echo '<option value="', $i, '">', $PDFLanguages[$i], '</option>';
	}
}
echo '</select>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input name="Modify" type="submit" value="', __('Modify'), '" /></div>
	</form>';

include(__DIR__ . '/includes/footer.php');
