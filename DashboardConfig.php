<?php
include ('includes/session.php');
$Title = _('Configure Dashboard Scripts');
$ViewTopic = 'Dashboard';
$BookMark = 'Configure';
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Page Security Levels'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_GET['Delete'])) {
	$SQL = "SELECT scripts FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$SQL = "DELETE FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$SQL = "DELETE FROM scripts WHERE script='" . $MyRow['scripts'] . "'";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(_('The script was successfully removed'), 'success');
	} else {
		prnMsg(_('There was a peoblem removing the script'), 'error');
	}
}

if (isset($_POST['Update'])) {
	$SQL = "SELECT scripts FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$SQL = "UPDATE dashboard_scripts SET pagesecurity='" . $_POST['PageSecurity'] . "',
										description='" . $_POST['Description'] . "'
									WHERE id='" . $_POST['ID'] . "'";
	$Result = DB_query($SQL);
	$SQL = "UPDATE scripts SET pagesecurity='" . $_POST['PageSecurity'] . "',
								description='" . $_POST['Description'] . "'
							WHERE script='" . $MyRow['scripts'] . "'";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(_('The script was successfully updated'), 'success');
	} else {
		prnMsg(_('There was a peoblem updating the script'), 'error');
	}
}

if (isset($_POST['Insert'])) {
	$SQL = "INSERT INTO dashboard_scripts (id,
											scripts,
											pagesecurity,
											description
										) VALUES (
											NULL,
											'" . $_POST['Script'] . "',
											'" . $_POST['PageSecurity'] . "',
											'" . $_POST['Description'] . "'
										)";
	$Result = DB_query($SQL);
	$SQL = "INSERT INTO scripts (script,
								pagesecurity,
								description
							) VALUES (
								'" . $_POST['Script'] . "',
								'" . $_POST['PageSecurity'] . "',
								'" . $_POST['Description'] . "'
							)";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(_('The script was successfully inserted'), 'success');
	} else {
		prnMsg(_('There was a peoblem inserting the script'), 'error');
	}
}

$SQL = "SELECT id,
				scripts,
				tokenname,
				description
			FROM dashboard_scripts
			INNER JOIN securitytokens
				ON dashboard_scripts.pagesecurity=securitytokens.tokenid
			ORDER BY scripts";
$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0 and !isset($_GET['Edit'])) {
	echo '<table>
			<thead>
				<tr>
					<th colspan="6">', _('Configured Dashboard Scripts'), '</th>
				</tr>
				<tr>
					<th class="SortedColumn">', _('Script'), '</th>
					<th>', _('Description'), '</th>
					<th class="SortedColumn">', _('Security Level'), '</th>
					<th></th>
					<th></th>
				</tr>
			</thead>
			<tbody>';
	$ScriptArray = array();
	while ($MyRow = DB_fetch_array($Result)) {
		$ScriptArray[] = $MyRow['scripts'];
		echo '<tr class="striped_row">
				<td>', $MyRow['scripts'], '</td>
				<td>', _($MyRow['description']), '</td>
				<td>', _($MyRow['tokenname']), '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedScript=', urlencode($MyRow['id']), '&amp;Edit=1">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedScript=', urlencode($MyRow['id']), '&amp;Delete=1">', _('Remove'), '</a></td>
			</tr>';
	}
	echo '</tbody>
		</table>';
}

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if (isset($_GET['Edit'])) {
	$SQL = "SELECT id,
					scripts,
					pagesecurity,
					description
				FROM dashboard_scripts
				WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['Script'] = $MyRow['scripts'];
	$_POST['PageSecurity'] = $MyRow['pagesecurity'];
	$_POST['Description'] = $MyRow['description'];

	echo '<fieldset>
			<legend>', _('Edit dashboard script details'), '</legend>
			<field>
				<label for="ID">', _('Script ID'), '</label>
				<div class="fieldtext">', $MyRow['id'], '</div>
			</field>
			<field>
				<label for="Script">', _('Script Name'), '</label>
				<div class="fieldtext">', $MyRow['scripts'], '</div>
			</field>';
	echo '<input type="hidden" name="ID" value="', $MyRow['id'], '" />';
} else {

	$_POST['Script'] = '';
	$_POST['PageSecurity'] = 1;
	$_POST['Description'] = '';

	echo '<fieldset>
			<legend>', _('New dashboard script details'), '</legend>
			<field>
				<label for="Script">', _('Script Name'), '</label>
				<select name="Script">';
	$Scripts = glob('dashboard/*.php');
	foreach ($Scripts as $ScriptName) {
		$ScriptName = basename($ScriptName);
		if ($ScriptName != 'template.php' and !in_array($ScriptName, $ScriptArray)) {
			if ($_POST['Script'] == $ScriptName) {
				echo '<option selected="selected" value="', $ScriptName, '">', $ScriptName, '</option>';
			} else {
				echo '<option value="', $ScriptName, '">', $ScriptName, '</option>';
			}
		}
	}
	echo '</select>
		</field>';
}

$TokenSQL = "SELECT tokenid,
					tokenname
			FROM securitytokens
			WHERE tokenid<1000
			ORDER BY tokenid";
$TokenResult = DB_query($TokenSQL);
echo '<field>
		<label for="PageSecurity">', _('Security Token'), '</label>
		<select name="PageSecurity">';
while ($MyTokenRow = DB_fetch_array($TokenResult)) {
	if ($MyTokenRow['tokenid'] == $_POST['PageSecurity']) {
		echo '<option selected="selected" value="', $MyTokenRow['tokenid'], '">', $MyTokenRow['tokenname'], '</option>';
	} else {
		echo '<option value="', $MyTokenRow['tokenid'], '">', $MyTokenRow['tokenname'], '</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="Description">', _('Description'), '</label>
		<input type="text" size="50" name="Description" value="', $_POST['Description'], '" />
	</field>';

echo '</fieldset>';

if (isset($_GET['Edit'])) {
	echo '<div class="centre">
			<input type="submit" name="Update" value="', _('Update Configuration'), '" />
		</div';
} else {
	echo '<div class="centre">
			<input type="submit" name="Insert" value="', _('Insert New Script'), '" />
		</div';
}

echo '</form>';

include ('includes/footer.php');
?>