<?php
$PageSecurity = 15;

$Title = _('Database Upgrade');

	echo '<!DOCTYPE html>';
	echo '<html>
		<head>
			<title>', $Title, '</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="shortcut icon" href="favicon.ico?v=2" type="image/x-icon" />
			<script async type="text/javascript" src = "javascripts/DBUpgrade.js"></script>';


echo '<title>', $Title, '</title>';
echo '<link rel="stylesheet" href="css/dbupgrade.css" type="text/css" />';

include ('includes/session.php');

//ob_start(); /*what is this for? */
if (!isset($_SESSION['DBVersion'])) {
	header('Location: index.php');
}
echo '<div class="title_bar" id="title_bar">', $Title, ' - ', stripslashes($_SESSION['CompanyRecord']['coyname']), '
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/user.png" class="TitleIcon" id="TitleIcon" title="" alt="" /></div>';

//include ('includes/header.php');

function executeSQL($SQL, $TrapErrors = False) {
	global $SQLFile;
	/* Run an sql statement and return an error code */
	if (!isset($SQLFile)) {
		DB_IgnoreForeignKeys();
		$Result = DB_query($SQL, '', '', false, $TrapErrors);
		$ErrorNumber = DB_error_no();
		DB_ReinstateForeignKeys();
		return $ErrorNumber;
	} else {
		fwrite($SQLFile, $SQL . ";\n");
	}
}

function updateDBNo($NewNumber, $Description='') {
	global $SQLFile;
	if (!isset($SQLFile)) {
		$SQL = "UPDATE config SET confvalue='" . $NewNumber . "' WHERE confname='DBUpdateNumber'";
		executeSQL($SQL);
		$_SESSION['DBUpdateNumber'] = $NewNumber;
	}
}

include ('includes/UpgradeDB_' . $DBType . '.php');

echo '<div class="page_title_text">
		<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title, '
	</div>';

if (!isset($_POST['continue'])) {
	echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="page_help_text">' . _('You have the following database updates which are required.') . '<br />' . _('Please ensure that you have taken a backup of your current database before continuing.') . '</div><br />';
	echo '<table>
			<tr>
				<th></th>
				<th>', _('Update Number'), '</th>
				<th>', _('Update Description'), '</th>
			</tr>';

	$StartingUpdate = $_SESSION['DBUpdateNumber'] + 1;
	$EndingUpdate = $_SESSION['DBVersion'];
	$x = 0;
	for ($UpdateNumber = $StartingUpdate;$UpdateNumber <= $EndingUpdate;$UpdateNumber++) {
		$File = 'sql/updates/' . $UpdateNumber . '.php';
		if (file_exists($File)) {
			$Lines = file($File, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$Contents = file_get_contents($File);
			$pattern = 'UpdateDBNo';
			$pattern = "/^.*$pattern.*\$/m";
			if(preg_match_all($pattern, $Contents, $Matches) and (implode("\n", $Matches[0])!="UpdateDBNo(basename(__FILE__, '.php'));")){
				echo '<tr>
						<td><span class="expand_icon" id="expand_icon', $x, '"></span></td>
						<td>', $UpdateNumber, '</td>
						<td>', substr(substr(implode("\n", $Matches[0]), 42), 0, -4), '</td>
					</tr>';
			} else {
				echo '<tr>
						<td><div class="expand_icon" id="expand_icon', $x, '"></div></td>
						<td>', $UpdateNumber, '</td>
						<td>', _('No descriptrion can be found for this update'), '</td>
					</tr>';
			}
			echo '<tr>
					<td class="collapsed_row" id="collapsed_row', $x, '" colspan="3">';
			foreach ($Lines as $Line) {
				if ($Line != '?>' and substr($Line, 0, 8) != 'UpdateDB' and $Line != '<?php') {
					echo $Line, '<br />';
				}
			}
			echo '</td>
				</tr>';
			$x++;
		}
	}

	echo '</table>';

	echo '<div class="centre">
			<button type="submit" name="continue">' . _('Continue With Updates') . '</button>
		</div>';
	echo '</form></div>';
} else {
	$StartingUpdate = $_SESSION['DBUpdateNumber'] + 1;
	$EndingUpdate = $_SESSION['DBVersion'];
	unset($_SESSION['Updates']);
	$_SESSION['Updates']['Errors'] = 0;
	$_SESSION['Updates']['Successes'] = 0;
	$_SESSION['Updates']['Warnings'] = 0;
	for ($UpdateNumber = $StartingUpdate;$UpdateNumber <= $EndingUpdate;$UpdateNumber++) {
		if (file_exists('sql/updates/' . $UpdateNumber . '.php')) {
			$SQL = "SET FOREIGN_KEY_CHECKS=0";
			$Result = DB_query($SQL);
			include ('sql/updates/' . $UpdateNumber . '.php');
			$SQL = "SET FOREIGN_KEY_CHECKS=1";
			$Result = DB_query($SQL);
		}
	}
	echo '<table>
			<tr>
				<th colspan="4" class="header"><b>', _('Database Updates Have Been Run'), '</b></th>
			</tr>
			<tr>
				<td class="fail_line">', $_SESSION['Updates']['Errors'], ' ', _('updates have errors in them'), '</td>
			</tr>
			<tr>
				<td class="warn_line">', $_SESSION['Updates']['Warnings'], ' ', _('updates have not been done as the update was unnecessary on this database'), '</td>
			</tr>
			<tr>
				<td class="success_line">', $_SESSION['Updates']['Successes'], ' ', _('updates have succeeded'), '</td>
			</tr>';
	if ($_SESSION['Updates']['Errors'] > 0) {
		$SizeOfErrorMessages = sizeOf($_SESSION['Updates']['Messages']);
		for ($i = 0;$i < $SizeOfErrorMessages;$i++) {
			echo '<tr><td>' . $_SESSION['Updates']['Messages'][$i] . '</td></tr>';
		}
	}
	echo '</table><br />';
	$ForceConfigReload = True;

	echo '<div class="centre">
			<a href="' . $RootPath . '/Logout.php" title="' . _('Log out of KwaMoja') . '" alt="">
				', _('You need to logout and log back in for these changes to take affect'), '
			</a>
		</div>';
}

include ('includes/footer.php');
?>