<?php
include ('includes/session.php');

$Title = _('Sales Commission Calculation Methods');

include ('includes/header.php');
echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_GET['SelectedTypeID'])) {
	$SelectedTypeID = $_GET['SelectedTypeID'];
} elseif (isset($_POST['SelectedTypeID'])) {
	$SelectedTypeID = $_POST['SelectedTypeID'];
}

if (isset($_POST['Submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (trim($_POST['CommissionTypeName']) == '') {
		$InputError = 1;
		prnMsg(_('The commission type name may not be empty'), 'error');
	}

	if (isset($_POST['SelectedTypeID']) and $_POST['SelectedTypeID'] != '' and $InputError != 1) {

		/*SelectedTypeID could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		// Check the name does not clash
		$SQL = "SELECT count(*) FROM salescommissiontypes
				WHERE commissiontypeid <> '" . $SelectedTypeID . "'
				AND commissiontypename='" . $_POST['CommissionTypeName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The commission type can not be renamed because another with the same name already exist.'), 'error');
		} else {
			// Get the old name and check that the record still exist neet to be very carefull here
			// idealy this is one of those sets that should be in a stored procedure simce even the checks are
			// relavant
			$SQL = "SELECT commissiontypename FROM salescommissiontypes
				WHERE commissiontypeid = '" . $SelectedTypeID . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) != 0) {
				// This is probably the safest way there is
				$MyRow = DB_fetch_row($Result);
				$OldCommissionTypeName = $MyRow[0];
				$SQL = array();
				$SQL[] = "UPDATE salescommissiontypes
					SET commissiontypename='" . $_POST['CommissionTypeName'] . "'
					WHERE commissiontypename='" . DB_escape_string($OldCommissionTypeName) . "'";
			} else {
				$InputError = 1;
				prnMsg(_('The commission type no longer exist.'), 'error');
			}
		}
		$Msg = _('Commision Type changed');
	} elseif ($InputError != 1) {
		/*SelectedTypeID is null cos no item selected on first time round so must be adding a record*/
		$SQL = "SELECT count(*) FROM salescommissiontypes
				WHERE commissiontypename='" . $_POST['CommissionTypeName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The commission type can not be created because another with the same name already exists.'), 'error');
		} else {
			$SQL = "INSERT INTO salescommissiontypes (commissiontypename )
					VALUES ('" . $_POST['CommissionTypeName'] . "')";
		}
		$Msg = _('New sales commission type added');
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		if (is_array($SQL)) {
			$Result = DB_Txn_Begin();
			$DbErr = _('Could not update sales commission type');
			$DbDbg = _('The sql that failed was') . ':';
			foreach ($SQL as $Statement) {
				$Result = DB_query($Statement, $DbErr, $DbDbg, true);
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
	}
	unset($SelectedTypeID);
	unset($_POST['SelectedTypeID']);
	unset($_POST['CommissionTypeName']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'salesman'
	$SQL = "SELECT commissiontypename FROM salescommissiontypes
			WHERE commissiontypeid= '" . $SelectedTypeID . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		// This is probably the safest way there is
		prnMsg(_('Cannot delete this sales commission calculation method because it no longer exist'), 'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldTypeName = $MyRow[0];
		$SQL = "SELECT COUNT(*) FROM salesman WHERE commissiontypeid='" . $_GET['SelectedTypeID'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(_('Cannot delete this sales commission type because sales people items have been created using this type'), 'warn');
			echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('sales people that use this commission type') . '</font>';
		} else {
			$SQL = "DELETE FROM salescommissiontypes WHERE commissiontypeid= '" . $SelectedTypeID . "'";
			$Result = DB_query($SQL);
			prnMsg($OldTypeName . ' ' . _('commision type has been deleted') . '!', 'success');
		}
	} //end if account group used in GL accounts
	unset($SelectedTypeID);
	unset($_GET['SelectedTypeID']);
	unset($_GET['delete']);
	unset($_POST['SelectedTypeID']);
	unset($_POST['CommissionTypeID']);
	unset($_POST['CommissionTypeName']);
}

if (!isset($SelectedTypeID)) {

	$SQL = "SELECT commissiontypeid,
					commissiontypename
			FROM salescommissiontypes
			ORDER BY commissiontypeid";

	$ErrMsg = _('Could not get commission types because');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) > 0) {

		echo '<table>
				<thead>
					<tr>
						<th class="SortedColumn">', _('Calculation Method'), '</th>
						<th colspan="2"></th>
					</tr>
				</thead>';
		echo '<tbody>';

		while ($MyRow = DB_fetch_row($Result)) {

			echo '<tr class="striped_row">
					<td>', $MyRow[1], '</td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedTypeID=', urlencode($MyRow[0]), '">', _('Edit'), '</a></td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedTypeID=', urlencode($MyRow[0]), '&amp;delete=1" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this calculation method?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
				</tr>';

		} //END WHILE LIST LOOP
		echo '</tbody>';
		echo '</table>';
	}
} //end of ifs and buts!


if (isset($SelectedTypeID)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Review Commission Calculation Methods'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedTypeID)) {
		//editing an existing section
		$SQL = "SELECT commissiontypeid,
						commissiontypename
				FROM salescommissiontypes
				WHERE commissiontypeid='" . $SelectedTypeID . "'";

		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			prnMsg(_('Could not retrieve the requested commission type, please try again.'), 'warn');
			unset($SelectedTypeID);
		} else {
			$MyRow = DB_fetch_array($Result);

			$_POST['CommissionTypeID'] = $MyRow['commissiontypeid'];
			$_POST['CommissionTypeName'] = $MyRow['commissiontypename'];

			echo '<input type="hidden" name="SelectedTypeID" value="', $_POST['CommissionTypeID'], '" />';
			echo '<fieldset>
					<legend>', _('Edit Calculation Method'), ' - ', $_POST['CommissionTypeName'], '</legend>';
		}

	} else {
		$_POST['CommissionTypeName'] = '';
		echo '<fieldset>
				<legend>', _('Create New Calculation Method'), '</legend>';
	}
	echo '<field>
			<label for="CommissionTypeName">', _('Commission Calculation Method'), ':</label>
			<input type="text" name="CommissionTypeName" size="35" required="required" autofocus="autofocus" maxlength="55" value="', $_POST['CommissionTypeName'], '" />
			<fieldhelp>', _('The name of the commission calculation algorithm'), '</fieldhelp>
		</field>';
	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Submit" value="', _('Enter Information'), '" />
		</div>';

	echo '</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>