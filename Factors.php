<?php
include ('includes/session.php');

$Title = _('Factor Company Maintenance');

include ('includes/header.php');

if (isset($_GET['FactorID'])) {
	$FactorID = mb_strtoupper($_GET['FactorID']);
	$_POST['Amend'] = True;
} elseif (isset($_POST['FactorID'])) {
	$FactorID = mb_strtoupper($_POST['FactorID']);
} else {
	unset($FactorID);
}

if (isset($_POST['Create'])) {
	$FactorID = 0;
	$_POST['New'] = 'Yes';
}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/supplier.png" title="', _('Factor Companies'), '" alt="" />', ' ', $Title, '
	</p>';

/* This section has been reached because the user has pressed either the insert/update buttons on the
 form hopefully with input in the correct fields, which we check for firsrt. */

//initialise no input errors assumed initially before we test
$InputError = 0;

if (isset($_POST['Submit']) or isset($_POST['Update'])) {

	if (mb_strlen($_POST['FactorName']) > 40 or mb_strlen($_POST['FactorName']) == 0 or $_POST['FactorName'] == '') {
		$InputError = 1;
		prnMsg(_('The factoring company name must be entered and be forty characters or less long'), 'error');
	}
	if (mb_strlen($_POST['Email']) > 0 and !IsEmailAddress($_POST['Email'])) {
		prnMsg(_('The email address entered does not appear to be a valid email address format'), 'error');
		$InputError = 1;
	}
	// But if errors were found in the input
	if ($InputError > 0) {
		prnMsg(_('Validation failed no insert or update took place'), 'warn');
		include ('includes/footer.php');
		exit;
	}

	/* If no input errors have been recieved */
	if ($InputError == 0 and isset($_POST['Submit'])) {
		//And if its not a new part then update existing one
		$SQL = "INSERT INTO factorcompanies (id,
						coyname,
						address1,
						address2,
						address3,
						address4,
						address5,
						address6,
						contact,
						telephone,
						fax,
						email)
					 VALUES (null,
					 	'" . $_POST['FactorName'] . "',
						'" . $_POST['Address1'] . "',
						'" . $_POST['Address2'] . "',
						'" . $_POST['Address3'] . "',
						'" . $_POST['Address4'] . "',
						'" . $_POST['Address5'] . "',
						'" . $_POST['Address6'] . "',
						'" . $_POST['ContactName'] . "',
						'" . $_POST['Telephone'] . "',
						'" . $_POST['Fax'] . "',
						'" . $_POST['Email'] . "')";

		$ErrMsg = _('The factoring company') . ' ' . $_POST['FactorName'] . ' ' . _('could not be added because');
		$DbgMsg = _('The SQL that was used to insert the factor but failed was');

		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		prnMsg(_('A new factoring company for') . ' ' . $_POST['FactorName'] . ' ' . _('has been added to the database'), 'success');

	} elseif ($InputError == 0 and isset($_POST['Update'])) {
		$SQL = "UPDATE factorcompanies SET coyname='" . $_POST['FactorName'] . "',
				address1='" . $_POST['Address1'] . "',
				address2='" . $_POST['Address2'] . "',
				address3='" . $_POST['Address3'] . "',
				address4='" . $_POST['Address4'] . "',
				address5='" . $_POST['Address5'] . "',
				address6='" . $_POST['Address6'] . "',
				contact='" . $_POST['ContactName'] . "',
				telephone='" . $_POST['Telephone'] . "',
				fax='" . $_POST['Fax'] . "',
				email='" . $_POST['Email'] . "'
			WHERE id = '" . $FactorID . "'";

		$ErrMsg = _('The factoring company could not be updated because');
		$DbgMsg = _('The SQL that was used to update the factor but failed was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		prnMsg(_('The factoring company record for') . ' ' . $_POST['FactorName'] . ' ' . _('has been updated'), 'success');

		//If it is a new part then insert it
		
	}
	unset($FactorID);
	unset($_POST['FactorName']);
	unset($_POST['Address1']);
	unset($_POST['Address2']);
	unset($_POST['Address3']);
	unset($_POST['Address4']);
	unset($_POST['Address5']);
	unset($_POST['Address6']);
	unset($_POST['ContactName']);
	unset($_POST['Telephone']);
	unset($_POST['Fax']);
	unset($_POST['Email']);
}
if (isset($_POST['Delete'])) {

	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SuppTrans' , PurchOrders, SupplierContacts
	$SQL = "SELECT COUNT(*) FROM suppliers WHERE factorcompanyid='" . $FactorID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		$CancelDelete = 1;
		prnMsg(_('Cannot delete this factor because there are suppliers using them'), 'warn');
		echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('suppliers using this factor company');
	}

	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM factorcompanies WHERE id='" . $FactorID . "'";
		$Result = DB_query($SQL);
		prnMsg(_('Factoring company record record for') . ' ' . $_POST['FactorName'] . ' ' . _('has been deleted'), 'success');
		unset($_SESSION['FactorID']);
	} //end if Delete factor
	unset($FactorID);
}

/* So the page hasn't called itself with the input/update/delete/buttons */

if (isset($FactorID) and isset($_POST['Amend'])) {

	$SQL = "SELECT id,
					coyname,
					address1,
					address2,
					address3,
					address4,
					address5,
					address6,
					contact,
					telephone,
					fax,
					email
			FROM factorcompanies
			WHERE id = '" . $FactorID . "'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['FactorName'] = $MyRow['coyname'];
	$_POST['Address1'] = $MyRow['address1'];
	$_POST['Address2'] = $MyRow['address2'];
	$_POST['Address3'] = $MyRow['address3'];
	$_POST['Address4'] = $MyRow['address4'];
	$_POST['Address5'] = $MyRow['address5'];
	$_POST['Address6'] = $MyRow['address6'];
	$_POST['ContactName'] = $MyRow['contact'];
	$_POST['Telephone'] = $MyRow['telephone'];
	$_POST['Fax'] = $MyRow['fax'];
	$_POST['Email'] = $MyRow['email'];

} else {
	$_POST['FactorName'] = '';
	$_POST['Address1'] = '';
	$_POST['Address2'] = '';
	$_POST['Address3'] = '';
	$_POST['Address4'] = '';
	$_POST['Address5'] = '';
	$_POST['Address6'] = '';
	$_POST['ContactName'] = '';
	$_POST['Telephone'] = '';
	$_POST['Fax'] = '';
	$_POST['Email'] = '';
}

if (isset($_POST['Amend']) or isset($_POST['Create'])) {
	// its a new factor being added
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<input type="hidden" name="FactorID" value="', $FactorID, '" />';
	echo '<input type="hidden" name="New" value="Yes" />';

	if (isset($_POST['Create'])) {
		echo '<fieldset>
			<legend>', _('Create new factor company'), '</legend>';
	} else {
		echo '<fieldset>
			<legend>', _('Amend factor company'), '</legend>';
	}

	echo '<field>
			<label for="FactorName">', _('Factor company Name'), ':</label>
			<input type="text" name="FactorName" size="42" required="required" maxlength="40" value="', $_POST['FactorName'], '" />
		</field>';

	echo '<field>
			<label for="Address1">', _('Address Line 1'), ':</label>
			<input type="text" name="Address1" size="42" maxlength="40" value="', $_POST['Address1'], '" />
		</field>';

	echo '<field>
			<label for="Address2">', _('Address Line 2'), ':</label>
			<input type="text" name="Address2" size="42" maxlength="40" value="', $_POST['Address2'], '" />
		</field>';

	echo '<field>
			<label for="Address3">', _('Address Line 3'), ':</label>
			<input type="text" name="Address3" size="42" maxlength="40" value="', $_POST['Address3'], '" />
		</field>';

	echo '<field>
			<label for="Address4">', _('Address Line 4'), ':</label>
			<input type="text" name="Address4" size="42" maxlength="40" value="', $_POST['Address4'], '" />
		</field>';

	echo '<field>
			<label for="Address5">', _('Address Line 5'), ':</label>
			<input type="text" name="Address5" size="42" maxlength="40" value="', $_POST['Address5'], '" />
		</field>';

	echo '<field>
			<label for="Address6">', _('Address Line 6'), ':</label>
			<input type="text" name="Address6" size="42" maxlength="40" value="', $_POST['Address6'], '" />
		</field>';

	echo '<field>
			<label for="ContactName">', _('Contact Name'), ':</label>
			<input type="text" name="ContactName" size="20" maxlength="25" value="', $_POST['ContactName'], '" />
		</field>';

	echo '<field>
			<label>', _('Telephone'), ':</label>
			<input type="tel" name="Telephone" size="20" maxlength="25" value="', $_POST['Telephone'], '" />
		</field>';

	echo '<field>
			<label for="Telephone">', _('Fax'), ':</label>
			<input type="tel" name="Fax" size="20" maxlength="25" value="', $_POST['Fax'], '" />
		</field>';

	echo '<field>
			<label for="email">', _('Email'), ':</label>
			<input type="email" name="Email" size="55" maxlength="55" value="', $_POST['Email'], '" />
		</field>
	</fieldset>';
}

if (isset($_POST['Create'])) {
	echo '<div class="centre">
			<input type="submit" name="Submit" value="', _('Insert New Factor'), '" />
		</div>
	</form>';

} else if (isset($_POST['Amend'])) {
	prnMsg(_('There is no second warning if you hit the delete button below') . '. ' . _('However checks will be made to ensure there are no suppliers are using this factor before the deletion is processed'), 'warn');
	echo '<div class="centre">
			<input type="submit" name="Update" value="', _('Update Factor'), '" />
			<input type="submit" name="Delete" value="', _('Delete Factor'), '" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this factoring company?'), '\');" />
		</div>
	</form>';
}

/* If it didn't come with a $FactorID it must be a completely fresh start, so choose a new $factorID or give the
 option to create a new one*/

if (empty($FactorID) and !isset($_POST['Create']) and !isset($_POST['Amend'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<input type="hidden" name="New" value="No" />';
	echo '<table>
			<tr>
				<th>', _('ID'), '</th>
				<th>', _('Company Name'), '</th>
				<th>', _('Address 1'), '</th>
				<th>', _('Address 2'), '</th>
				<th>', _('Address 3'), '</th>
				<th>', _('Address 4'), '</th>
				<th>', _('Address 5'), '</th>
				<th>', _('Address 6'), '</th>
				<th>', _('Contact'), '</th>
				<th>', _('Telephone'), '</th>
				<th>', _('Fax Number'), '</th>
				<th>', _('Email'), '</th>
				<th></th>
			</tr>';
	$SQL = "SELECT id,
					coyname,
					address1,
					address2,
					address3,
					address4,
					address5,
					address6,
					contact,
					telephone,
					fax,
					email
			FROM factorcompanies";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td>', $MyRow['id'], '</td>
				<td>', $MyRow['coyname'], '</td>
				<td>', $MyRow['address1'], '</td>
				<td>', $MyRow['address2'], '</td>
				<td>', $MyRow['address3'], '</td>
				<td>', $MyRow['address4'], '</td>
				<td>', $MyRow['address5'], '</td>
				<td>', $MyRow['address6'], '</td>
				<td>', $MyRow['contact'], '</td>
				<td>', $MyRow['telephone'], '</td>
				<td>', $MyRow['fax'], '</td>
				<td>', $MyRow['email'], '</td>
				<td><a href="', $RootPath, '/Factors.php?FactorID=', urlencode($MyRow['id']), '">', _('Edit'), '</a></td>
			</tr>';
	} //end while loop
	echo '</table>';

	echo '<div class="centre">
			<input type="submit" name="Create" value="', _('Create New Factor'), '" />
		</div>
	</form>';
}

include ('includes/footer.php');
?>