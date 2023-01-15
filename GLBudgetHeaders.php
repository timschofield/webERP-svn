<?php
include ('includes/session.php');

$Title = _('Create GL Budgets');

$ViewTopic = 'GeneralLedger';
$BookMark = 'GLBudgets';
include ('includes/header.php');

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" id="createbudget">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Budgets'), '" alt="', _('Budgets'), '" />', ' ', $Title, '
	</p>';

if (isset($_POST['Submit']) or isset($_POST['Update'])) {
	$InputError = 0;

	if ($_POST['StartPeriod'] > $_POST['EndPeriod']) {
		prnMsg(_('The end period cannot be before the start period'), 'error');
		$InputError = 1;
	}

	if ($InputError == 0) {
		if ($_POST['Primary'] == 1) {
			$SQL = "UPDATE glbudgetheaders SET `current`=0";
			$Result = DB_query($SQL);
		}
		if (isset($_POST['Submit'])) {
			$InsertSQL = "INSERT INTO glbudgetheaders (`id`,
														`owner`,
														`name`,
														`description`,
														`startperiod`,
														`endperiod`,
														`current`
													) VALUES (
														NULL,
														'" . $_POST['Owner'] . "',
														'" . $_POST['Name'] . "',
														'" . $_POST['Description'] . "',
														'" . $_POST['StartPeriod'] . "',
														'" . $_POST['EndPeriod'] . "',
														'" . $_POST['Primary'] . "'
													)";
			$InsertResult = DB_query($InsertSQL);
		} elseif (isset($_POST['Update'])) {
			$UpdateSQL = "UPDATE glbudgetheaders SET `owner`='" . $_POST['Owner'] . "',
													`name`='" . $_POST['Name'] . "',
													`description`='" . $_POST['Description'] . "',
													`startperiod`='" . $_POST['StartPeriod'] . "',
													`endperiod`='" . $_POST['EndPeriod'] . "',
													`current`='" . $_POST['Primary'] . "'
												WHERE `id`='" . $_POST['ID'] . "'";
			$UpdateResult = DB_query($UpdateSQL);
		}

		if (DB_error_no() == 0) {
			prnMsg(_('The budget header information has been successfully saved'), 'success');
		} else {
			prnMsg(_('There was a problem saving the budget header information'), 'error');
		}
	}

}

if (isset($_GET['Edit'])) {
	$SQL = "SELECT `owner`,
					`name`,
					`description`,
					`startperiod`,
					`endperiod`,
					`current`
				FROM glbudgetheaders
				WHERE id='" . $_GET['Edit'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['Owner'] = $MyRow['owner'];
	$_POST['Name'] = $MyRow['name'];
	$_POST['Description'] = $MyRow['description'];
	$_POST['StartPeriod'] = $MyRow['startperiod'];
	$_POST['EndPeriod'] = $MyRow['endperiod'];
	$_POST['Primary'] = $MyRow['current'];
	echo '<fieldset>
			<legend>', _('Edit Budget Details'), '</legend>
			<field>
				<label for="ID">', _('ID'), '</label>
				<div class="fieldtext">', $_GET['Edit'], '</div>
			</field>';
	echo '<input type="hidden" name="ID" value="', $_GET['Edit'], '" />';
} else {
	$_POST['Owner'] = $_SESSION['UserID'];
	$_POST['Name'] = '';
	$_POST['Description'] = '';
	$_POST['StartPeriod'] = ReportPeriod(_('This Financial Year'), 'From');
	$_POST['EndPeriod'] = ReportPeriod(_('This Financial Year'), 'To');
	$_POST['Primary'] = 0;

	echo '<fieldset>
			<legend>', _('Create New Budget'), '</legend>';
}

$SQL = "SELECT userid, realname FROM www_users";
$Result = DB_query($SQL);

echo '<field>
		<label for="Owner">', _('Budget Owner'), '</label>
		<select name="Owner">';
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['userid'] == $_POST['Owner']) {
		echo '<option selected="selected" value="', $MyRow['userid'], '">', $MyRow['realname'], ' (', $MyRow['userid'], ')</option>';
	} else {
		echo '<option value="', $MyRow['userid'], '">', $MyRow['realname'], ' (', $MyRow['userid'], ')</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="Name">', _('Name of budget'), '</label>
		<input type="text" name="Name" size="100" value="', $_POST['Name'], '" />
	</field>';

echo '<field>
		<label for="Description">', _('Budget Description'), '</label>
		<textarea cols="100" rows="10" name="Description">', $_POST['Description'], '</textarea>
	</field>';

$SQL = "SELECT periodno, lastdate_in_period FROM periods";
$Result = DB_query($SQL);

echo '<field>
		<label for="StartPeriod">', _('Start Period'), '</label>
		<select name="StartPeriod">';
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['periodno'] == $_POST['StartPeriod']) {
		echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
	} else {
		echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
	}
}
echo '</select>
	</field>';

$SQL = "SELECT periodno, lastdate_in_period FROM periods";
$Result = DB_query($SQL);

echo '<field>
		<label for="EndPeriod">', _('End Period'), '</label>
		<select name="EndPeriod">';
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['periodno'] == $_POST['EndPeriod']) {
		echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
	} else {
		echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="Primary">', _('Default Budget for reports'), '</label>
		<select name="Primary">';
if ($_POST['Primary'] == 1) {
	echo '<option selected="selected" value="1">', _('Yes'), '</option>';
	echo '<option value="0">', _('No'), '</option>';
} else {
	echo '<option value="1">', _('Yes'), '</option>';
	echo '<option selected="selected" value="0">', _('No'), '</option>';
}
echo '</select>
	</field>';

echo '</fieldset>';

if (isset($_GET['Edit'])) {
	echo '<div class="centre">
			<input type="submit" name="Update" value="', _('Update Details'), '" />
		</div>';
} else {
	echo '<div class="centre">
			<input type="submit" name="Submit" value="', _('Save Details'), '" />
		</div>';
}

echo '</form>';

$SQL = "SELECT `id`,
				`owner`,
				`name`,
				`startperiod`,
				`endperiod`,
				`current`
			FROM glbudgetheaders";
$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<table>
			<thead>
				<tr>
					<th>', _('ID'), '</th>
					<th>', _('Owner'), '</th>
					<th>', _('Name'), '</th>
					<th>', _('Start From Period'), '</th>
					<th>', _('End In Period'), '</th>
					<th>', _('Is Primary?'), '</th>
					<th></th>
				</tr>
			</thead>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['current'] == 0) {
			$Current = _('No');
		} else {
			$Current = _('Yes');
		}
		echo '<tr class="striped_row">
				<td>', $MyRow['id'], '</td>
				<td>', $MyRow['owner'], '</td>
				<td>', $MyRow['name'], '</td>
				<td>', MonthAndYearFromPeriodNo($MyRow['startperiod']), '</td>
				<td>', MonthAndYearFromPeriodNo($MyRow['endperiod']), '</td>
				<td>', $Current, '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Edit=', $MyRow['id'], '">', _('Edit'), '</a></td>
			</tr>';
	}
	echo '</tbody>
		</table>';
}

include ('includes/footer.php');

?>