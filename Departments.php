<?php
include ('includes/session.php');
include ('includes/SQL_CommonFunctions.inc');

$Title = _('Departments');

include ('includes/header.php');
echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Departments'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_GET['SelectedDepartmentID'])) {
	$SelectedDepartmentID = $_GET['SelectedDepartmentID'];
} elseif (isset($_POST['SelectedDepartmentID'])) {
	$SelectedDepartmentID = $_POST['SelectedDepartmentID'];
}

if (isset($_POST['Submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (trim($_POST['DepartmentName']) == '') {
		$InputError = 1;
		prnMsg(_('The Name of the Department should not be empty'), 'error');
	}

	if (RecordExists('departments', 'description', $_POST['DepartmentName']) and !isset($SelectedDepartmentID)) {
		$InputError = 1;
		prnMsg(_('A department with this name already exists. Please choose a new name'), 'error');
	}

	if (isset($SelectedDepartmentID) and $SelectedDepartmentID != '' and $InputError != 1) {

		/*SelectedDepartmentID could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		// Check the name does not clash
		$SQL = "SELECT count(*) FROM departments
				WHERE departmentid <> '" . $SelectedDepartmentID . "'
				AND description " . LIKE . " '" . $_POST['DepartmentName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if (RecordExists('departments', 'description', $_POST['DepartmentName'])) {
			$InputError = 1;
			prnMsg(_('This department name already exists.'), 'error');
		} else {
			// Get the old name and check that the record still exist neet to be very careful here
			$SQL = "SELECT description
					FROM departments
					WHERE departmentid = '" . $SelectedDepartmentID . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) != 0) {
				// This is probably the safest way there is
				$MyRow = DB_fetch_array($Result);
				$OldDepartmentName = $MyRow['description'];
				$SQL = array();
				$SQL[] = "UPDATE departments
							SET description='" . $_POST['DepartmentName'] . "',
								authoriser='" . $_POST['Authoriser'] . "'
							WHERE description " . LIKE . " '" . $OldDepartmentName . "'";
			} else {
				$InputError = 1;
				prnMsg(_('The department does not exist.'), 'error');
			}
		}
		$Msg = _('The department has been modified');
	} elseif ($InputError != 1) {
		/*SelectedDepartmentID is null cos no item selected on first time round so must be adding a record*/
		$SQL = "SELECT count(*) FROM departments
				WHERE description " . LIKE . " '" . $_POST['DepartmentName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('There is already a department with the specified name.'), 'error');
		} else {
			$SQL = "INSERT INTO departments (description,
											 authoriser )
					VALUES ('" . $_POST['DepartmentName'] . "',
							'" . $_POST['Authoriser'] . "'
							)";
		}
		$Msg = _('The new department has been created');
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		if (is_array($SQL)) {
			$Result = DB_Txn_Begin();
			$ErrMsg = _('The department could not be inserted');
			$DbgMsg = _('The sql that failed was') . ':';
			foreach ($SQL as $SQLStatement) {
				$Result = DB_query($SQLStatement, $ErrMsg, $DbgMsg, true);
				if (!$Result) {
					$InputError = 1;
					break;
				}
			}
			if ($InputError != 1) {
				$Result = DB_Txn_Commit();
			} else {
				$Result = DB_Txn_Rollback();
			}
		} else {
			$Result = DB_query($SQL);
		}
		prnMsg($Msg, 'success');
		echo '<br />';
	}
	unset($SelectedDepartmentID);
	unset($_POST['SelectedDepartmentID']);
	unset($_POST['DepartmentName']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button


	$SQL = "SELECT description
			FROM departments
			WHERE departmentid = '" . $SelectedDepartmentID . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		prnMsg(_('You cannot delete this Department'), 'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldDepartmentName = $MyRow[0];
		$SQL = "SELECT COUNT(*)
				FROM stockrequest INNER JOIN departments
				ON stockrequest.departmentid=departments.departmentid
				WHERE description " . LIKE . " '" . $OldDepartmentName . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(_('You cannot delete this Department'), 'warn');
			echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('There are items related to this department');
		} else {
			$SQL = "DELETE FROM departments WHERE description " . LIKE . "'" . $OldDepartmentName . "'";
			$Result = DB_query($SQL);
			prnMsg($OldDepartmentName . ' ' . _('The department has been removed') . '!', 'success');
		}
	} //end if account group used in GL accounts
	unset($SelectedDepartmentID);
	unset($_GET['SelectedDepartmentID']);
	unset($_GET['delete']);
	unset($_POST['SelectedDepartmentID']);
	unset($_POST['DepartmentID']);
	unset($_POST['DepartmentName']);
}

if (!isset($SelectedDepartmentID)) {

	$SQL = "SELECT departmentid,
					description,
					authoriser
			FROM departments
			ORDER BY description";

	$ErrMsg = _('There are no departments created');
	$Result = DB_query($SQL, $ErrMsg);

	echo '<table>
			<tr>
				<th>', _('Department Name'), '</th>
				<th>', _('Authoriser'), '</th>
				<th colspan="2"></th>
			</tr>';

	while ($MyRow = DB_fetch_array($Result)) {

		echo '<tr class="striped_row">
				<td>', $MyRow['description'], '</td>
				<td>', $MyRow['authoriser'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedDepartmentID=', urlencode($MyRow['departmentid']), '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedDepartmentID=', urlencode($MyRow['departmentid']), '&amp;delete=1" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this department?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';

	} //END WHILE LIST LOOP
	echo '</table>';
} //end of ifs and buts!


if (isset($SelectedDepartmentID)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('View all Departments'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedDepartmentID)) {
		//editing an existing section
		$SQL = "SELECT departmentid,
						description,
						authoriser
				FROM departments
				WHERE departmentid='" . $SelectedDepartmentID . "'";

		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			prnMsg(_('The selected departemnt could not be found.'), 'warn');
			unset($SelectedDepartmentID);
		} else {
			$MyRow = DB_fetch_array($Result);

			$_POST['DepartmentID'] = $MyRow['departmentid'];
			$_POST['DepartmentName'] = $MyRow['description'];
			$AuthoriserID = $MyRow['authoriser'];

			echo '<input type="hidden" name="SelectedDepartmentID" value="', $_POST['DepartmentID'], '" />';
			echo '<fieldset>
					<legend>', _('Edit details for department'), ' - ', $_POST['DepartmentName'], '  (', $_POST['DepartmentID'], ')</legend>';
		}

	} else {
		$_POST['DepartmentName'] = '';
		$AuthoriserID = '';
		echo '<fieldset>
				<legend>', _('Create new department'), '</legend>';
	}
	echo '<field>
			<label for="DepartmentName">', _('Department Name'), ':</label>
			<input type="text" name="DepartmentName" size="50" required="required" autofocus="autofocus" maxlength="100" value="', $_POST['DepartmentName'], '" />
			<fieldhelp>', _('The unique name by which this department is known'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="Authoriser">', _('Authoriser'), '</label>
			<select name="Authoriser">';
	$UserSQL = "SELECT userid FROM www_users";
	$UserResult = DB_query($UserSQL);
	while ($MyRow = DB_fetch_array($UserResult)) {
		if ($MyRow['userid'] == $AuthoriserID) {
			echo '<option selected="True" value="', $MyRow['userid'], '">', $MyRow['userid'], '</option>';
		} else {
			echo '<option value="', $MyRow['userid'], '">', $MyRow['userid'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('The user who has permission to authorise transactions for this department'), '</fieldhelp>
	</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Submit" value="', _('Enter Information'), '" />
		</div>
	</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>