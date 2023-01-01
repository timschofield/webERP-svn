<?php
include ('includes/session.php');

$Title = _('Discount Categories Maintenance');
/* Manual links before header.php */
$ViewTopic = 'SalesOrders';
$BookMark = 'DiscountMatrix';
include ('includes/header.php');
echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_POST['stockID'])) {
	$_POST['StockID'] = $_POST['stockID'];
} elseif (isset($_GET['StockID'])) {
	$_POST['StockID'] = $_GET['StockID'];
	$_POST['ChooseOption'] = 1;
	$_POST['SelectChoice'] = 1;
}

if (isset($_POST['submit']) and !isset($_POST['SubmitCategory'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$Result = DB_query("SELECT stockid
						FROM stockmaster
						WHERE mbflag <>'K'
						AND mbflag<>'D'
						AND stockid='" . mb_strtoupper($_POST['StockID']) . "'");
	if (DB_num_rows($Result) == 0) {
		$InputError = 1;
		prnMsg(_('The stock item entered must be set up as either a manufactured or purchased or assembly item'), 'warn');
	}

	if ($InputError != 1) {

		$SQL = "UPDATE stockmaster SET discountcategory='" . $_POST['DiscountCategory'] . "'
				WHERE stockid='" . mb_strtoupper($_POST['StockID']) . "'";

		$Result = DB_query($SQL, _('The discount category') . ' ' . $_POST['DiscountCategory'] . ' ' . _('record for') . ' ' . mb_strtoupper($_POST['StockID']) . ' ' . _('could not be updated because'));

		prnMsg(_('The stock master has been updated with this discount category'), 'success');
		unset($_POST['DiscountCategory']);
		unset($_POST['StockID']);
	}

} elseif (isset($_GET['Delete']) and $_GET['Delete'] == 'yes') {
	/*the link to delete a selected record was clicked instead of the submit button */

	$SQL = "UPDATE stockmaster SET discountcategory='' WHERE stockid='" . trim(mb_strtoupper($_GET['StockID'])) . "'";
	$Result = DB_query($SQL);
	prnMsg(_('The stock master record has been updated to no discount category'), 'success');
	echo '<br />';
} elseif (isset($_POST['SubmitCategory'])) {
	$SQL = "SELECT stockid FROM stockmaster WHERE categoryid='" . $_POST['stockcategory'] . "'";
	$ErrMsg = _('Failed to retrieve stock category data');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result) > 0) {
		$SQL = "UPDATE stockmaster
				SET discountcategory='" . $_POST['DiscountCategory'] . "'
				WHERE categoryid='" . $_POST['stockcategory'] . "'";
		$Result = DB_query($SQL);
	} else {
		prnMsg(_('There are no stock defined for this stock category, you must define stock for it first'), 'error');
		include ('includes/footer.php');
		exit;
	}
}

if (isset($_POST['SelectChoice'])) {
	echo '<form id="update" method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	$SQL = "SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		echo '<fieldset>
				<legend>', _('Select Category'), '</legend>
				<field>
					<label for="DiscCat">', _('Discount Category Code'), ': </label>';

		echo '<select name="DiscCat" onchange="ReloadForm(update.select)">';

		while ($MyRow = DB_fetch_array($Result)) {
			if (isset($_POST['DiscCat']) and $MyRow['discountcategory'] == $_POST['DiscCat']) {
				echo '<option selected="selected" value="', $MyRow['discountcategory'], '">', $MyRow['discountcategory'], '</option>';
			} else {
				echo '<option value="', $MyRow['discountcategory'], '">', $MyRow['discountcategory'], '</option>';
			}
		}

		echo '</select>
			</field>
		</fieldset>';

		echo '<div class="centre">
				<input type="submit" name="select" value="', _('Select'), '" />
			</div>';
	}
	echo '</form>';

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<input type="hidden" name="ChooseOption" value="', $_POST['ChooseOption'], '" />';
	echo '<input type="hidden" name="SelectChoice" value="', $_POST['SelectChoice'], '" />';

	if (isset($_POST['ChooseOption']) and $_POST['ChooseOption'] == 1) {
		echo '<fieldset>
				<field>
					<label for="DiscountCategory">', _('Discount Category Code'), ':</label>';

		if (isset($_POST['DiscCat'])) {
			echo '<input type="text" name="DiscountCategory" required="required" maxlength="2" size="2" value="', $_POST['DiscCat'], '" />
				</field>';
		} else {
			echo '<input type="text" name="DiscountCategory" required="required" maxlength="2" size="2" />
				</field>';
		}

		if (!isset($_POST['StockID'])) {
			$_POST['StockID'] = '';
		}
		if (!isset($_POST['PartID'])) {
			$_POST['PartID'] = '';
		}
		if (!isset($_POST['PartDesc'])) {
			$_POST['PartDesc'] = '';
		}
		echo '<field>
				<label for="StockID">', _('Enter Stock Code'), ':</label>
				<input type="text" name="StockID" size="20" maxlength="20" value="', $_POST['StockID'], '" />
			</field>';

		echo '<h1>', _('OR'), '</h1>';

		echo '<field>
				<label for="PartID">', _('Partial code'), ':</label>
				<input type="text" name="PartID" size="10" maxlength="10" value="', $_POST['PartID'], '" />
			</field>';

		echo '<h1>', _('OR'), '</h1>';

		echo '<field>
				<label for="PartDesc">', _('Partial description'), ':</label>
				<input type="text" name="PartDesc" size="10" value="', $_POST['PartDesc'], '" maxlength="10" />
				<input type="submit" name="search" value="', _('Search'), '" />
			</field>';

		echo '</fieldset>';

		echo '<div class="centre"><input type="submit" name="submit" value="', _('Update Item'), '" /></div>';

		if (isset($_POST['search'])) {
			if ($_POST['PartID'] != '' and $_POST['PartDesc'] == '') {
				$SQL = "SELECT stockid, description FROM stockmaster
						WHERE stockid " . LIKE . " '%" . $_POST['PartID'] . "%'";
			} elseif ($_POST['PartID'] == '' and $_POST['PartDesc'] != '') {
				$SQL = "SELECT stockid, description FROM stockmaster
						WHERE description " . LIKE . " '%" . $_POST['PartDesc'] . "%'";
			} elseif ($_POST['PartID'] != '' and $_POST['PartDesc'] != '') {
				$SQL = "SELECT stockid, description FROM stockmaster
						WHERE stockid " . LIKE . " '%" . $_POST['PartID'] . "%'
						AND description " . LIKE . " '%" . $_POST['PartDesc'] . "%'";
			} else {
				$SQL = "SELECT stockid, description FROM stockmaster
						WHERE stockid " . LIKE . " '%%'
						AND description " . LIKE . " '%%'";
			}

			$Result = DB_query($SQL);

			if (!isset($_POST['stockID'])) {
				echo _('Select a part code') . ':<br />';
				while ($MyRow = DB_fetch_array($Result)) {
					echo '<input type="submit" name="stockID" value="', $MyRow['stockid'], '" />';
				}
			}
		}
	} else {
		echo '<fieldset>
				<legend>', _('Assign discount category'), '</legend>';

		echo '<field>
				<label for="DiscountCategory">', _('Assign discount category'), '</label>
				<input type="text" name="DiscountCategory" required="required" maxlength="2" size="2" />
			</field>';

		$SQL = "SELECT categoryid,
				categorydescription
				FROM stockcategory";
		$Result = DB_query($SQL);
		echo '<field>
				<label for="stockcategory">', _('to all items in stock category'), '</label>
				<select name="stockcategory">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		}
		echo '</select>
			</field>
		</fieldset>';

		echo '<div class="centre">
				<input type="submit" name="SubmitCategory" value="', _('Update Items'), '" />
			</div>';
	}
	echo '</form>';

	if (!isset($_POST['DiscCat'])) {
		/*set DiscCat to something to show results for first cat defined */

		$SQL = "SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) > 0) {
			DB_data_seek($Result, 0);
			$MyRow = DB_fetch_array($Result);
			$_POST['DiscCat'] = $MyRow['discountcategory'];
		} else {
			$_POST['DiscCat'] = '0';
		}
	}

	if ($_POST['DiscCat'] != '0') {

		$SQL = "SELECT stockmaster.stockid,
			stockmaster.description,
			discountcategory
		FROM stockmaster
		WHERE discountcategory='" . DB_escape_string($_POST['DiscCat']) . "'
		ORDER BY stockmaster.stockid";

		$Result = DB_query($SQL);

		echo '<table>
				<thead>
					<tr>
						<th class="SortedColumn">', _('Discount Category'), '</th>
						<th class="SortedColumn">', _('Item'), '</th>
						<th></th>
					</tr>
				</thead>
				<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {
			$DeleteURL = htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Delete=yes&amp;StockID=' . urlencode($MyRow['stockid']) . '&amp;DiscountCategory=' . urlencode($MyRow['discountcategory']);

			echo '<tr class="striped_row">
					<td>', $MyRow['discountcategory'], '</td>
					<td>', $MyRow['stockid'], ' - ', $MyRow['description'], '</td>
					<td><a href="', $DeleteURL, '" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this discount category?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
				</tr>';

		}

		echo '</tbody>
			</table>';

	} else {
		/* $_POST['DiscCat'] ==0 */

		prnMsg(_('There are currently no discount categories defined') . '. ' . _('Enter a two character abbreviation for the discount category and the stock code to which this category will apply to. Discount rules can then be applied to this discount category'), 'info');
	}
}

if (!isset($_POST['SelectChoice'])) {
	echo '<form method="post" id="choose" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<legend>', _('Select Items'), '</legend>
			<field>
				<label for="ChooseOption">', _('Update discount category for'), '</label>
				<select name="ChooseOption" onchange="ReloadForm(choose.SelectChoice)">
					<option value="1">', _('a single stock item'), '</option>
					<option value="2">', _('a complete stock category'), '</option>
				</select>
			</field>
		</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="SelectChoice" value="', _('Select'), '" />
		</div>
	</form>';
}

include ('includes/footer.php');
?>