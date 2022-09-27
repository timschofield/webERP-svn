<?php
/* Adds customer contacts */

include ('includes/session.php');
$Title = _('Customer Contacts');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'AddCustomerContacts';
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

if (isset($_GET['Id'])) {
	$Id = (int)$_GET['Id'];
} else if (isset($_POST['Id'])) {
	$Id = (int)$_POST['Id'];
}
if (isset($_POST['DebtorNo'])) {
	$DebtorNo = $_POST['DebtorNo'];
} elseif (isset($_GET['DebtorNo'])) {
	$DebtorNo = $_GET['DebtorNo'];
}
echo '<div class="toplink">
		<a class="noPrint" href="', $RootPath, '/Customers.php?DebtorNo=', urlencode($DebtorNo), '">', _('Back to Customers'), '</a>
	</div>';
$NameSql = "SELECT name FROM debtorsmaster WHERE debtorno='" . $DebtorNo . "'";
$Result = DB_query($NameSql);
$MyRow = DB_fetch_array($Result);
if (!isset($_GET['Id'])) {
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', _('Contacts for Customer'), ': ', htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8'), '
		</p>';
} else {
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', _('Edit contact for'), ': ', htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8'), '
		</p>';
}

if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (isset($_POST['Con_ID']) and !is_long((integer)$_POST['Con_ID'])) {
		$InputError = 1;
		prnMsg(_('The Contact ID must be an integer.'), 'error');
	} elseif (mb_strlen($_POST['ContactName']) > 40) {
		$InputError = 1;
		prnMsg(_('The contact name must be forty characters or less long'), 'error');
	} elseif (trim($_POST['ContactName']) == '') {
		$InputError = 1;
		prnMsg(_('The contact name may not be empty'), 'error');
	} elseif (!IsEmailAddress($_POST['ContactEmail']) and mb_strlen($_POST['ContactEmail']) > 0) {
		$InputError = 1;
		prnMsg(_('The contact email address is not a valid email address'), 'error');
	}

	if (isset($Id) and ($Id and $InputError != 1)) {
		$SQL = "UPDATE custcontacts SET contactname='" . $_POST['ContactName'] . "',
										role='" . $_POST['ContactRole'] . "',
										phoneno='" . $_POST['ContactPhone'] . "',
										notes='" . $_POST['ContactNotes'] . "',
										email='" . $_POST['ContactEmail'] . "',
										statement='" . $_POST['StatementAddress'] . "'
					WHERE debtorno ='" . $DebtorNo . "'
					AND contid='" . $Id . "'";
		$Msg = _('Customer Contacts') . ' ' . $DebtorNo . ' ' . _('has been updated');
	} elseif ($InputError != 1) {

		$SQL = "INSERT INTO custcontacts (debtorno,
										contactname,
										role,
										phoneno,
										notes,
										email,
										statement)
				VALUES ('" . $DebtorNo . "',
						'" . $_POST['ContactName'] . "',
						'" . $_POST['ContactRole'] . "',
						'" . $_POST['ContactPhone'] . "',
						'" . $_POST['ContactNotes'] . "',
						'" . $_POST['ContactEmail'] . "',
						'" . $_POST['StatementAddress'] . "')";
		$Msg = _('The contact record has been added');
	}

	if ($InputError != 1) {
		$Result = DB_query($SQL);

		prnMsg($Msg, 'success');
		unset($Id);
		unset($_POST['ContactName']);
		unset($_POST['ContactRole']);
		unset($_POST['ContactPhone']);
		unset($_POST['ContactNotes']);
		unset($_POST['ContactEmail']);
		unset($_POST['Con_ID']);
		unset($_POST['submit']);
	}
} elseif (isset($_GET['delete']) and $_GET['delete']) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SalesOrders'
	$SQL = "DELETE FROM custcontacts
			WHERE contid='" . $Id . "'
			AND debtorno='" . $DebtorNo . "'";
	$Result = DB_query($SQL);

	prnMsg(_('The contact record has been deleted'), 'success');
	unset($Id);
	unset($_GET['delete']);

}

if (!isset($Id)) {

	$SQL = "SELECT contid,
					debtorno,
					contactname,
					role,
					phoneno,
					statement,
					notes,
					email
			FROM custcontacts
			WHERE debtorno='" . $DebtorNo . "'
			ORDER BY contid";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) > 0) {
		echo '<table>';
		echo '<thead>
				<tr>
					<th class="SortedColumn">', _('Name'), '</th>
					<th class="SortedColumn">', _('Role'), '</th>
					<th class="SortedColumn">', _('Phone No'), '</th>
					<th class="SortedColumn">', _('Email'), '</th>
					<th class="SortedColumn">', _('Statement'), '</th>
					<th class="SortedColumn">', _('Notes'), '</th>
					<th class="noPrint" colspan="2">&nbsp;</th>
				</tr>
			</thead>';

		echo '<tbody>';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr class="striped_row">
					<td class="text">', $MyRow['contactname'], '</td>
					<td class="text">', $MyRow['role'], '</td>
					<td class="text">', $MyRow['phoneno'], '</td>
					<td class="text"><a href="mailto:', $MyRow['email'], '">', $MyRow['email'], '</a></td>
					<td class="text">', ($MyRow['statement'] == 0) ? _('No') : _('Yes'), '</td>
					<td class="text">', $MyRow['notes'], '</td>
					<td class="noPrint"><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Id=', urlencode($MyRow['contid']), '&DebtorNo=', urlencode($MyRow['debtorno']), '">' . _('Edit') . '</a></td>
					<td class="noPrint"><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Id=', urlencode($MyRow['contid']), '&DebtorNo=', urlencode($MyRow['debtorno']), '&delete=1" onclick="return confirm(\'' . _('Are you sure you wish to delete this contact?') . '\');">' . _('Delete') . '</a></td>
				</tr>';
			//END WHILE LIST LOOP
			
		}
		echo '</tbody>
		</table>';
	}
}

if (isset($Id)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode($DebtorNo), '">', _('Review all contacts for this Customer'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?DebtorNo=' . $DebtorNo . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($Id)) {

		$SQL = "SELECT contid,
						debtorno,
						contactname,
						role,
						phoneno,
						notes,
						email
					FROM custcontacts
					WHERE contid='" . $Id . "'
						AND debtorno='" . $DebtorNo . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['Con_ID'] = $MyRow['contid'];
		$_POST['ContactName'] = $MyRow['contactname'];
		$_POST['ContactRole'] = $MyRow['role'];
		$_POST['ContactPhone'] = $MyRow['phoneno'];
		$_POST['ContactEmail'] = $MyRow['email'];
		$_POST['ContactNotes'] = $MyRow['notes'];
		$_POST['DebtorNo'] = $MyRow['debtorno'];
		echo '<input type="hidden" name="Id" value="', $Id, '" />';
		echo '<input type="hidden" name="Con_ID" value="', $_POST['Con_ID'], '" />';
		echo '<input type="hidden" name="DebtorNo" value="', $_POST['DebtorNo'], '" />';
		echo '<fieldset>
				<legend>', _('Edit Customer Contact Details'), '</legend>
				<field>
					<label for="Con_ID">', _('Contact Code'), ':</label>
					<div class="fieldtext">', $_POST['Con_ID'], '</div>
				</field>';
	} else {
		$_POST['Con_ID'] = '';
		$_POST['ContactName'] = '';
		$_POST['ContactRole'] = '';
		$_POST['ContactPhone'] = '';
		$_POST['ContactEmail'] = '';
		$_POST['ContactNotes'] = '';
		$_POST['DebtorNo'] = '';
		echo '<fieldset>
				<legend>', _('New Customer Contact Details'), '</legend>';
	}

	echo '<field>
			<label for="ContactName">', _('Contact Name'), ':</label>
			<input type="text" name="ContactName" value="', $_POST['ContactName'], '" size="35" autofocus="autofocus" required="required" maxlength="40" />
			<fieldhelp>', _('The name of this contact'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="ContactRole">' . _('Role') . ':</label>
			<input type="text" name="ContactRole" value="' . $_POST['ContactRole'] . '" size="35" maxlength="40" />
			<fieldhelp>', _('The position this contact holds at the customer.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="ContactPhone">' . _('Phone') . ':</label>
			<input type="tel" name="ContactPhone" value="' . $_POST['ContactPhone'] . '" size="35" maxlength="40" />
			<fieldhelp>', _('The phone number of this contact'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="ContactEmail">' . _('Email') . ':</label>
			<input type="email" name="ContactEmail" value="' . $_POST['ContactEmail'] . '" size="55" maxlength="55" />
			<fieldhelp>', _('The email address of this contact'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="StatementAddress">', _('Send Statement'), ':</label>
			<select name="StatementAddress">';
	if ($_POST['StatementAddress'] == 0) {
		echo '<option selected="selected" value="0">', _('No'), '</option>
			<option value="1">', _('Yes'), '</option>';
	} else {
		echo '<option value="0">', _('No'), '</option>
			<option selected="selected" value="1">', _('Yes'), '</option>';
	}
	echo '</select>
		<fieldhelp>', _('This flag identifies the contact as one who should receive an email cusstomer statement'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="ContactNotes">' . _('Notes') . '</label>
			<textarea name="ContactNotes" rows="3" cols="40">' . $_POST['ContactNotes'] . '</textarea>
			<fieldhelp>', _('Any free form notes for this contact'), '</fieldhelp>
		</field>';
	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="' . _('Enter Information') . '" />
		</div>
	</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>