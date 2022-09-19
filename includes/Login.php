<?php
// Display demo user name and password within login form if $AllowDemoMode is true
include ($PathPrefix . 'includes/LanguageSetup.php');
include ('LanguagesArray.php');

if ((isset($AllowDemoMode)) and ($AllowDemoMode == True) and (!isset($demo_text))) {
	$demo_text = _('Login as user') . ': <i>' . _('admin') . '</i><br />' . _('with password') . ': <i>' . _('kwamoja') . '</i>';
	} elseif (!isset($demo_text)) {
		$demo_text = _('Please login here');
	}

foreach ($CompanyList as $Company) {
	$CompanyName[$Company['database']] = $Company['company'];
}

	echo '<!DOCTYPE html>';
	echo '<html>
		<head>
			<title>webERP ', _('Login screen'), '</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="shortcut icon" href="favicon.ico?v=2" type="image/x-icon" />
			<script async type="text/javascript" src = "', $PathPrefix, $RootPath, '/javascripts/Login.js"></script>';

	if ($LanguagesArray[$DefaultLanguage]['Direction'] == 'rtl') {
		echo '<link rel="stylesheet" href="css/login_rtl.css" type="text/css" />';
	} else {
		echo '<link rel="stylesheet" href="css/login.css" type="text/css" />';
	}
	echo '</head>';

	echo '<body>
		<div id="container">
			<div id="login_logo">
				<a href="http://www.web-erp.com" target="_blank"><img src="css/', $DefaultDatabase, '.png" style="width:100%" /></a>
			</div>
			<div id="login_box">
				<form action="index.php" name="LogIn" method="post" class="noPrint">
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($_COOKIE['Login'])) {
		$DefaultDatabase = $_COOKIE['Login'];
	}
	if ($AllowCompanySelectionBox === 'Hide') {
		// do not show input or selection box
		echo '<input type="hidden" name="CompanyNameField"  value="' . $DefaultDatabase . '" />';
	} else if ($AllowCompanySelectionBox === 'ShowInputBox') {
		// show input box
		echo '<input type="text" required="required" autofocus="autofocus" name="CompanyNameField"  value="' . $DefaultDatabase . '" />';
	} else {
		// Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
		echo '<select name="CompanyNameField" id="CompanyNameField">';

		$DirHandle = dir('companies/');

		while (false !== ($CompanyEntry = $DirHandle->read())) {
			if (is_dir('companies/' . $CompanyEntry) and $CompanyEntry != '..' and $CompanyEntry != '' and $CompanyEntry != '.' and $CompanyEntry != 'default') {
				if ($CompanyEntry == $DefaultDatabase) {
					echo '<option selected="selected" value="' . $CompanyEntry . '">' . $CompanyName[$CompanyEntry] . '</option>';
				} else {
					echo '<option value="' . $CompanyEntry . '">' . $CompanyName[$CompanyEntry] . '</option>';
				}
			}
		}

		$DirHandle->close();

		echo '</select>';
	}

	echo '<label for="dropdownlist">', _('Company'), ':</label>';

	echo '<input type="text" id="CompanySelect" readonly value="' . $CompanyName[$DefaultDatabase] . '" />';

	echo '<ol id="dropdownlist" class="dropdownlist">';

	$DirHandle = dir('companies/');

	while (false !== ($CompanyEntry = $DirHandle->read())) {
		if (is_dir('companies/' . $CompanyEntry) and $CompanyEntry != '..' and $CompanyEntry != '' and $CompanyEntry != '.' and $CompanyEntry != 'default') {
			if (file_exists('companies/' . $CompanyEntry . '/logo.jpg')) {
				echo '<li class="option" id="' . $CompanyEntry . '" ><img class="optionlogo" src="companies/' . $CompanyEntry . '/logo.jpg" /><span class="optionlabel">', $CompanyName[$CompanyEntry], '</span></li>';
			} else if (file_exists('companies/' . $CompanyEntry . '/logo.png')) {
				echo '<li class="option" id="' . $CompanyEntry . '" ><img class="optionlogo" src="companies/' . $CompanyEntry . '/logo.png" /><span class="optionlabel">', $CompanyName[$CompanyEntry], '</span></li>';
			} else {
				echo '<li class="option" id="' . $CompanyEntry . '" ><img class="optionlogo" src="css/', $DefaultDatabase, '.png" /><span class="optionlabel">', $CompanyName[$CompanyEntry], '</span></li>';
			}
		}
	}
	$DirHandle->close();

	echo '</ol>';

	echo '<label>', _('User name'), ':</label>
		<input type="text" autocomplete="username" autofocus="autofocus" required="required" name="UserNameEntryField" id="UserNameEntryField" placeholder="', _('User name'), '" maxlength="20" /><br />
		<label>', _('Password'), ':</label>
		<input type="password" autocomplete="current-password" id="password" required="required" name="Password" placeholder="', _('Password'), '" />
		<input type="text" id="eye" readonly title="', _('Show/Hide Password'), '" />
		<div id="demo_text">';

	if (isset($demo_text)) {
		echo $demo_text;
	}

	echo '</div>';

	echo '<button class="button" type="submit" value="', _('Login'), '" name="SubmitUser" onclick="ShowSpinner()">
			<img id="waiting_show" class="waiting_show" src="css/waiting.gif" />', _('Login'), ' ', '
			<img src="css/tick.png" title="', _('Login'), '" alt="" class="ButtonIcon" />
		</button>';

	echo '</form>
	</div>
</div>';

	echo '</body>
	</html>';
?>