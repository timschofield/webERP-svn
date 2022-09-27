<?php
include ('includes/session.php');
$Title = _('Customer Notes');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'CustomerNotes';

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
		$DebtorNo = stripslashes($_GET['DebtorNo']);
	}

	echo '<div class="toplink">
		<a href="', $RootPath, '/SelectCustomer.php?DebtorNo=', urlencode($DebtorNo), '">', _('Back to Select Customer'), '</a>
	</div>';

	if (isset($_POST['submit'])) {

		//initialise no input errors assumed initially before we test
		$InputError = 0;
		/* actions to take once the user has clicked the submit button
		 ie the page has called itself with some user input */

		//first off validate inputs sensible
		if (!is_long((integer)$_POST['Priority'])) {
			$InputError = 1;
			prnMsg(_('The contact priority must be an integer.'), 'error');
		} elseif (mb_strlen($_POST['Note']) > 200) {
			$InputError = 1;
			prnMsg(_('The contact\'s notes must be two hundred characters or less long'), 'error');
		} elseif (trim($_POST['Note']) == '') {
			$InputError = 1;
			prnMsg(_('The contact\'s notes may not be empty'), 'error');
		}

		if (isset($Id) and $InputError != 1) {

			$SQL = "UPDATE custnotes SET note='" . $_POST['Note'] . "',
									date='" . FormatDateForSQL($_POST['NoteDate']) . "',
									href='" . $_POST['Href'] . "',
									priority='" . $_POST['Priority'] . "'
				WHERE debtorno ='" . $DebtorNo . "'
				AND noteid='" . $Id . "'";
			$Msg = _('Customer Notes') . ' ' . $DebtorNo . ' ' . _('has been updated');
		} elseif ($InputError != 1) {

			$SQL = "INSERT INTO custnotes (debtorno,
										href,
										note,
										date,
										priority)
				VALUES ('" . $_POST['DebtorNo'] . "',
						'" . $_POST['Href'] . "',
						'" . $_POST['Note'] . "',
						'" . FormatDateForSQL($_POST['NoteDate']) . "',
						'" . $_POST['Priority'] . "')";
			$Msg = _('The contact notes record has been added');
		}

		if ($InputError != 1) {
			$Result = DB_query($SQL);

			prnMsg($Msg, 'success');
			unset($Id);
			unset($_POST['Note']);
			unset($_POST['Noteid']);
			unset($_POST['NoteDate']);
			unset($_POST['Href']);
			unset($_POST['Priority']);
		}
	} elseif (isset($_GET['delete'])) {
		//the link to delete a selected record was clicked instead of the submit button
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'SalesOrders'
		$SQL = "DELETE FROM custnotes
			WHERE noteid='" . $Id . "'
			AND debtorno='" . $DebtorNo . "'";
		$Result = DB_query($SQL);

		echo '<br />';
		prnMsg(_('The contact note record has been deleted'), 'success');
		unset($Id);
		unset($_GET['delete']);
	}

	if (!isset($Id)) {
		$NameSql = "SELECT * FROM debtorsmaster
				WHERE debtorno='" . $DebtorNo . "'";
		$Result = DB_query($NameSql);
		$MyRow = DB_fetch_array($Result);
		echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', _('Notes for Customer'), ': <b>', $MyRow['name'], '</b>
		</p>';

		$SQL = "SELECT noteid,
					debtorno,
					href,
					note,
					date,
					priority
				FROM custnotes
				WHERE debtorno='" . $DebtorNo . "'
				ORDER BY date DESC";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) > 0) {
			echo '<table>
				<tr>
					<th>', _('Date'), '</th>
					<th>', _('Note'), '</th>
					<th>', _('WWW'), '</th>
					<th>', _('Priority'), '</th>
					<th colspan="2"></th>
				</tr>';

			while ($MyRow = DB_fetch_array($Result)) {
				echo '<tr class="striped_row">
					<td>', ConvertSQLDate($MyRow['date']), '</td>
					<td>', $MyRow['note'], '</td>
					<td><a href="', $MyRow['href'], '">', $MyRow['href'], '</a></td>
					<td>', $MyRow['priority'], '</td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Id=', urlencode($MyRow['noteid']), '&DebtorNo=', urlencode($MyRow['debtorno']), '">', _('Edit'), ' </td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Id=', urlencode($MyRow['noteid']), '&DebtorNo=', urlencode($MyRow['debtorno']), '&delete=1" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this customer note?') . '\', \'Confirm Delete\', this);">', _('Delete'), '</td>
				</tr>';
			}
			//END WHILE LIST LOOP
			echo '</table>';
		}
	}
	if (isset($Id)) {
		echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode($DebtorNo), '">', _('Review all notes for this Customer'), '</a>
		</div>';
	}

	if (!isset($_GET['delete'])) {

		echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode($DebtorNo), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
		echo '<input type="hidden" name="DebtorNo" value="', stripslashes(stripslashes($DebtorNo)), '" />';

		if (isset($Id)) {
			//editing an existing
			$SQL = "SELECT noteid,
						debtorno,
						href,
						note,
						date,
						priority
					FROM custnotes
					WHERE noteid='" . $Id . "'
						AND debtorno='" . $DebtorNo . "'";

			$Result = DB_query($SQL);

			$MyRow = DB_fetch_array($Result);

			$_POST['Noteid'] = $MyRow['noteid'];
			$_POST['Note'] = $MyRow['note'];
			$_POST['Href'] = $MyRow['href'];
			$_POST['NoteDate'] = $MyRow['date'];
			$_POST['Priority'] = $MyRow['priority'];
			$_POST['debtorno'] = $MyRow['debtorno'];
			echo '<input type="hidden" name="Id" value="', $Id, '" />';
			echo '<input type="hidden" name="Con_ID" value="', $_POST['Noteid'], '" />';
			echo '<input type="hidden" name="DebtorNo" value="', $_POST['debtorno'], '" />';
			echo '<fieldset>
				<legend>', _('Edit customer note'), '</legend>
				<field>
					<label for="Noteid">', _('Note ID'), ':</label>
					<div class="fieldtext">', $_POST['Noteid'], '</div>
				</field>';
		} else {
			$_POST['Note'] = '';
			$_POST['Href'] = '';
			$_POST['NoteDate'] = date('Y-m-d');
			$_POST['Priority'] = 0;
			$_POST['debtorno'] = '';
			echo '<fieldset>
				<legend>', _('New customer note'), '</legend>';
		}

		echo '<field>
			<label for="Note">', _('Contact Note'), '</label>
			<textarea name="Note" rows="3" required="required" autofocus="autofocus" cols="32">', $_POST['Note'], '</textarea>
			<fieldhelp>', _('Any notes for this customer'), '</fieldhelp>
		</field>';

		echo '<field>
			<label for="Href">', _('WWW'), '</label>
			<input type="text" name="Href" value="', $_POST['Href'], '" size="35" maxlength="100" />
			<fieldhelp>', _('Any web site associated with this note.'), '</fieldhelp>
		</field>';

		echo '<field>
			<label for="NoteDate">' . _('Date') . '</label>
			<input type="text" name="NoteDate" class="date" id="datepicker" value="', ConvertSQLDate($_POST['NoteDate']), '" size="10" maxlength="10" />
			<fieldhelp>', _('The date for this note.'), '</fieldhelp>
		</field>';

		echo '<field>
			<label for="Priority">' . _('Priority') . '</label>
			<input type="text" class=integer" name="Priority" value="', $_POST['Priority'], '" size="1" maxlength="3" />
			<fieldhelp>', _('The priority for this note, (0-9)'), '</fieldhelp>
		</field>';

		echo '</fieldset>';

		echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Enter Information'), '" />
		</div>
	</form>';

	} //end if record deleted no point displaying form to add record
	include ('includes/footer.php');
?>