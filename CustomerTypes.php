<?php
include ('includes/session.php');
$Title = _('Customer Types') . ' / ' . _('Maintenance');
include ('includes/header.php');

if (isset($_POST['SelectedType'])) {
	$SelectedType = mb_strtoupper($_POST['SelectedType']);
} elseif (isset($_GET['SelectedType'])) {
	$SelectedType = mb_strtoupper($_GET['SelectedType']);
}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Customer Types'), '" alt="" />', _('Customer Type Setup'), '
	</p>';
echo '<div class="page_help_text">', _('Add/edit/delete Customer Types'), '</div>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (mb_strlen($_POST['TypeName']) > 100) {
		$InputError = 1;
		prnMsg(_('The customer type name description must be 100 characters or less long'), 'error');
	}

	if (mb_strlen($_POST['TypeName']) == 0) {
		$InputError = 1;
		echo '<br />';
		prnMsg(_('The customer type name description must contain at least one character'), 'error');
	}

	$CheckSql = "SELECT count(*)
			 FROM debtortype
			 WHERE typename = '" . $_POST['TypeName'] . "'";
	$CheckResult = DB_query($CheckSql);
	$CheckRow = DB_fetch_row($CheckResult);
	if ($CheckRow[0] > 0 and !isset($SelectedType)) {
		$InputError = 1;
		echo '<br />';
		prnMsg(_('You already have a customer type called') . ' ' . $_POST['TypeName'], 'error');
	}

	if (isset($SelectedType) and $InputError != 1) {

		$SQL = "UPDATE debtortype
			SET typename = '" . $_POST['TypeName'] . "'
			WHERE typeid = '" . $SelectedType . "'";

		$Msg = _('The customer type') . ' ' . $SelectedType . ' ' . _('has been updated');
	} elseif ($InputError != 1) {

		// First check the type is not being duplicated
		$CheckSql = "SELECT count(*)
				 FROM debtortype
				 WHERE typename = '" . $_POST['TypeName'] . "'";

		$CheckResult = DB_query($CheckSql);
		$CheckRow = DB_fetch_row($CheckResult);

		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The customer type') . ' ' . $_POST['typeid'] . _(' already exist.'), 'error');
		} else {

			// Add new record on submit
			$SQL = "INSERT INTO debtortype
						(typename)
					VALUES ('" . $_POST['TypeName'] . "')";

			$Msg = _('Customer type') . ' ' . $_POST["TypeName"] . ' ' . _('has been created');
			$CheckSql = "SELECT count(typeid)
				 FROM debtortype";
			$Result = DB_query($CheckSql);
			$MyRow = DB_fetch_row($Result);

		}
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);

		// Fetch the default price list.
		$DefaultCustomerType = $_SESSION['DefaultCustomerType'];

		// Does it exist
		$checkSql = "SELECT count(*)
				 FROM debtortype
				 WHERE typeid = '" . $DefaultCustomerType . "'";
		$CheckResult = DB_query($checkSql);
		$CheckRow = DB_fetch_row($CheckResult);

		// If it doesnt then update config with newly created one.
		if ($CheckRow[0] == 0) {
			$SQL = "UPDATE config
					SET confvalue='" . $_POST['typeid'] . "'
					WHERE confname='DefaultCustomerType'";
			$Result = DB_query($SQL);
			$_SESSION['DefaultCustomerType'] = $_POST['typeid'];
		}
		echo '<br />';
		prnMsg($Msg, 'success');

		unset($SelectedType);
		unset($_POST['typeid']);
		unset($_POST['TypeName']);
	}

} elseif (isset($_GET['delete'])) {

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
	// Prevent delete if saletype exist in customer transactions
	$SQL = "SELECT COUNT(*)
			FROM debtortrans
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			WHERE debtorsmaster.typeid='" . $SelectedType . "'";

	$ErrMsg = _('The number of transactions using this customer type could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(_('Cannot delete this type because customer transactions have been created using this type') . '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('transactions using this type'), 'error');

	} else {

		$SQL = "SELECT COUNT(*) FROM debtorsmaster WHERE typeid='" . $SelectedType . "'";

		$ErrMsg = _('The number of transactions using this Type record could not be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(_('Cannot delete this type because customers are currently set up to use this type') . '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('customers with this type code'));
		} else {
			$Result = DB_query("SELECT TypeName FROM debtortype WHERE typeid='" . $SelectedType . "'");
			if (DB_Num_Rows($Result) > 0) {
				$TypeRow = DB_fetch_array($Result);
				$TypeName = $TypeRow['TypeName'];

				$SQL = "DELETE FROM debtortype WHERE typeid='" . $SelectedType . "'";
				$ErrMsg = _('The Type record could not be deleted because');
				$Result = DB_query($SQL, $ErrMsg);
				echo '<br />';
				prnMsg(_('Customer type') . ' ' . $TypeName . ' ' . _('has been deleted'), 'success');
			}
			unset($SelectedType);
			unset($_GET['delete']);

		}
	} //end if sales type used in debtor transactions or in customers set up
	
}

if (!isset($SelectedType)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedType will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of sales types will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT typeid,
					typename
				FROM debtortype";
	$Result = DB_query($SQL);

	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Type ID'), '</th>
					<th class="SortedColumn">', _('Type Name'), '</th>
					<th colspan="2"></th>
				</tr>
			</thead>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {

		echo '<tr class="striped_row">
				<td>', $MyRow['typeid'], '</td>
				<td>', $MyRow['typename'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedType=', urlencode($MyRow['typeid']), '">' . _('Edit') . '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedType=', urlencode($MyRow['typeid']), '&amp;delete=yes" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this Customer Type?') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>
			</tr>';
	}
	//END WHILE LIST LOOP
	echo '</tbody>';
	echo '</table>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	// The user wish to EDIT an existing type
	if (isset($SelectedType) and $SelectedType != '') {

		$SQL = "SELECT typeid,
				   typename
				FROM debtortype
				WHERE typeid='" . $SelectedType . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['TypeName'] = $MyRow['typename'];

		echo '<input type="hidden" name="SelectedType" value="', $SelectedType, '" />';
		echo '<fieldset>
				<legend>', _('Edit Customer Type Details'), '</legend>';

		// We dont allow the user to change an existing type code
		echo '<field>
				<label for="TypeID">', _('Type ID'), ': </label>
				<div class="fieldtext">', $SelectedType, '</div>
			</field>';

	} else {
		// This is a new type so the user may volunteer a type code
		echo '<fieldset>
				<legend>', _('New Customer Type Details'), '</legend>';
	}

	if (!isset($_POST['TypeName'])) {
		$_POST['TypeName'] = '';
	}
	echo '<field>
			<label for="TypeName">', _('Type Name'), ':</label>
			<input type="text" name="TypeName" required="required" autofocus="autofocus" maxlength="100" value="', $_POST['TypeName'], '" />
			<fieldhelp>', _('Description of this customer type.'), '</fieldhelp>
		</field>';

	echo '</fieldset>'; // close main table
	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Accept'), '" />
		</div>';
	echo '</form>';

} // end if user wish to delete
include ('includes/footer.php');
?>