<?php
include ('includes/session.php');
$Title = _('Customer Type (Group) Notes');
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

if (isset($_GET['Id'])) {
	$Id = (int)$_GET['Id'];
} else if (isset($_POST['Id'])) {
	$Id = (int)$_POST['Id'];
}
if (isset($_POST['DebtorType'])) {
	$DebtorType = $_POST['DebtorType'];
} elseif (isset($_GET['DebtorType'])) {
	$DebtorType = $_GET['DebtorType'];
}
echo '<div class="toplink">
		<a href="', $RootPath, '/SelectCustomer.php?DebtorType=', urlencode($DebtorType), '">', _('Back to Select Customer'), '</a>
	</div>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (!is_long((integer)$_POST['Priority'])) {
		$InputError = 1;
		prnMsg(_('The Contact priority must be an integer.'), 'error');
	} elseif (mb_strlen($_POST['Note']) > 200) {
		$InputError = 1;
		prnMsg(_('The contacts notes must be two hundred characters or less long'), 'error');
	} elseif (trim($_POST['Note']) == '') {
		$InputError = 1;
		prnMsg(_('The contacts notes may not be empty'), 'error');
	}

	if (isset($Id) and $InputError != 1) {

		$SQL = "UPDATE debtortypenotes SET note='" . $_POST['Note'] . "',
											date='" . FormatDateForSQL($_POST['NoteDate']) . "',
											href='" . $_POST['Href'] . "',
											priority='" . $_POST['Priority'] . "'
										WHERE typeid ='" . $DebtorType . "'
										AND noteid='" . $Id . "'";
		$Msg = _('Customer Group Notes') . ' ' . $DebtorType . ' ' . _('has been updated');
	} elseif ($InputError != 1) {

		$SQL = "INSERT INTO debtortypenotes (typeid,
											href,
											note,
											date,
											priority)
									VALUES ('" . $DebtorType . "',
											'" . $_POST['Href'] . "',
											'" . $_POST['Note'] . "',
											'" . FormatDateForSQL($_POST['NoteDate']) . "',
											'" . $_POST['Priority'] . "')";
		$Msg = _('The contact group notes record has been added');
	}

	if ($InputError != 1) {
		$Result = DB_query($SQL);

		prnMsg($Msg, 'success');
		unset($Id);
		unset($_POST['Note']);
		unset($_POST['NoteID']);
	}
} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SalesOrders'
	$SQL = "DELETE FROM debtortypenotes
			WHERE noteid='" . $Id . "'
			AND typeid='" . $DebtorType . "'";
	$Result = DB_query($SQL);

	prnMsg(_('The contact group note record has been deleted'), 'success');
	unset($Id);
	unset($_GET['delete']);

}

if (!isset($Id)) {
	$SQLname = "SELECT typename from debtortype where typeid='" . $DebtorType . "'";
	$Result = DB_query($SQLname);
	$MyRow = DB_fetch_array($Result);
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Search'), '" alt="" />', _('Notes for Customer Type'), ': <b>', $MyRow['typename'], '</b>
		</p>';

	$SQL = "SELECT noteid,
					typeid,
					href,
					note,
					date,
					priority
				FROM debtortypenotes
				WHERE typeid='" . $DebtorType . "'
				ORDER BY date DESC";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) > 0) {
		echo '<table>';
		echo '<tr>
				<th>', _('Date'), '</th>
				<th>', _('Note'), '</th>
				<th>', _('href'), '</th>
				<th>', _('Priority'), '</th>
				<th colspan="2"></th>
			</tr>';

		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr class="striped_row">
				<td>', ConvertSQLDate($MyRow['date']), '</td>
				<td>', $MyRow['note'], '</td>
				<td>', $MyRow['href'], '</td>
				<td>', $MyRow['priority'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Id=', urlencode($MyRow['noteid']), '&amp;DebtorType=', urlencode($MyRow['typeid']), '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Id=', urlencode($MyRow['noteid']), '&amp;DebtorType=', urlencode($MyRow['typeid']), '&amp;delete=1">', _('Delete'), '</a></td>
			</tr>';

		}
		//END WHILE LIST LOOP
		echo '</table>';
	}
}

if (isset($Id)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorType=', urlencode($DebtorType), '">', _('Review all notes for this Customer Type'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorType=', urlencode($DebtorType), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($Id)) {
		//editing an existing
		$SQL = "SELECT noteid,
					typeid,
					href,
					note,
					date,
					priority
				FROM debtortypenotes
				WHERE noteid=" . $Id . "
					AND typeid='" . $DebtorType . "'";

		$Result = DB_query($SQL);

		$MyRow = DB_fetch_array($Result);

		$_POST['NoteID'] = $MyRow['noteid'];
		$_POST['Note'] = $MyRow['note'];
		$_POST['Href'] = $MyRow['href'];
		$_POST['NoteDate'] = $MyRow['date'];
		$_POST['Priority'] = $MyRow['priority'];
		$_POST['TypeID'] = $MyRow['typeid'];
		echo '<input type="hidden" name="Id" value="', $Id, '" />';
		echo '<input type="hidden" name="Con_ID" value="', $_POST['NoteID'], '" />';
		echo '<input type="hidden" name="DebtorType" value="', $_POST['TypeID'], '" />';
		echo '<fieldset>
				<legend>', _('Edit the customer group note'), '</legend>
				<field>
					<td>', _('Note ID'), ':</td>
					<td>', $_POST['NoteID'], '</td>
				</field>';
	} else {
		echo '<fieldset>
				<legend>', _('New customer group note'), '</legend>';
		$_POST['NoteID'] = '';
		$_POST['Note'] = '';
		$_POST['Href'] = '';
		$_POST['NoteDate'] = date('Y-m-d');
		$_POST['Priority'] = 0;
		$_POST['TypeID'] = '';
	}

	echo '<field>
			<label for="Note">', _('Contact Group Note'), ':</label>
			<textarea name="Note" rows="3" cols="32" autofocus="autofocus">', $_POST['Note'], '</textarea>
			<fieldhelp>', _('Any notes for this customer group.'), '</fieldhelp>
		</field>
		<field>
			<label for="Href">', _('Web site'), ':</label>
			<input type="text" name="Href" value="', $_POST['Href'], '" size="35" maxlength="100" />
			<fieldhelp>', _('Any web site associated with this note.'), '</fieldhelp>
		</field>
		<field>
			<label for="NoteDate">', _('Date'), ':</label>
			<input type="text" name="NoteDate" class="date" value="', ConvertSQLDate($_POST['NoteDate']), '" size="10" maxlength="10" />
			<fieldhelp>', _('The date for this note.'), '</fieldhelp>
		</field>
		<field>
			<label for="Priority">', _('Priority'), ':</label>
			<input type="text" name="Priority" value="', $_POST['Priority'], '" size="1" maxlength="3" />
			<fieldhelp>', _('The priority for this note, (0-9)'), '</fieldhelp>
		</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="submit" value="', _('Enter Information'), '" />
		</div>
	</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>