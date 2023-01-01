<?php
include ('includes/session.php');
$Title = _('Discount Matrix Maintenance');
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	if (!is_numeric(filter_number_format($_POST['QuantityBreak']))) {
		prnMsg(_('The quantity break must be entered as a positive number'), 'error');
		$InputError = 1;
	}

	if (filter_number_format($_POST['QuantityBreak']) <= 0) {
		prnMsg(_('The quantity of all items on an order in the discount category') . ' ' . $_POST['DiscountCategory'] . ' ' . _('at which the discount will apply is 0 or less than 0') . '. ' . _('Positive numbers are expected for this entry'), 'warn');
		$InputError = 1;
	}
	if (!is_numeric(filter_number_format($_POST['DiscountRate']))) {
		prnMsg(_('The discount rate must be entered as a positive number'), 'warn');
		$InputError = 1;
	}
	if (filter_number_format($_POST['DiscountRate']) <= 0 or filter_number_format($_POST['DiscountRate']) > 100) {
		prnMsg(_('The discount rate applicable for this record is either less than 0% or greater than 100%') . '. ' . _('Numbers between 1 and 100 are expected'), 'warn');
		$InputError = 1;
	}

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	if ($InputError != 1) {

		$SQL = "INSERT INTO discountmatrix (salestype,
							discountcategory,
							quantitybreak,
							discountrate)
					VALUES('" . $_POST['SalesType'] . "',
						'" . $_POST['DiscountCategory'] . "',
						'" . filter_number_format($_POST['QuantityBreak']) . "',
						'" . (filter_number_format($_POST['DiscountRate']) / 100) . "')";

		$Result = DB_query($SQL);
		prnMsg(_('The discount matrix record has been added'), 'success');
		unset($_POST['DiscountCategory']);
		unset($_POST['SalesType']);
		unset($_POST['QuantityBreak']);
		unset($_POST['DiscountRate']);
	}
} elseif (isset($_GET['Delete']) and $_GET['Delete'] == 'yes') {
	/*the link to delete a selected record was clicked instead of the submit button */

	$SQL = "DELETE FROM discountmatrix
		WHERE discountcategory='" . $_GET['DiscountCategory'] . "'
		AND salestype='" . $_GET['SalesType'] . "'
		AND quantitybreak='" . $_GET['QuantityBreak'] . "'";

	$Result = DB_query($SQL);
	prnMsg(_('The discount matrix record has been deleted'), 'success');
}

$SQL = "SELECT sales_type,
			salestype,
			discountcategory,
			quantitybreak,
			discountrate
		FROM discountmatrix INNER JOIN salestypes
			ON discountmatrix.salestype=salestypes.typeabbrev
		ORDER BY salestype,
			discountcategory,
			quantitybreak";

$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Sales Type'), '</th>
					<th class="SortedColumn">', _('Discount Category'), '</th>
					<th>', _('Quantity Break'), '</th>
					<th>', _('Discount Rate'), ' %</th>
					<th></th>
				</tr>
			</thead>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td>', $MyRow['sales_type'], '</td>
				<td>', $MyRow['discountcategory'], '</td>
				<td class="number">', $MyRow['quantitybreak'], '</td>
				<td class="number">', $MyRow['discountrate'] * 100, '%</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?Delete=yes&amp;SalesType=', urlencode($MyRow['salestype']), '&amp;DiscountCategory=', urlencode($MyRow['discountcategory']), '&amp;QuantityBreak=', urlencode($MyRow['quantitybreak']), '" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this discount matrix record?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';
	}

	echo '</tbody>
		</table>';
}

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<fieldset>
		<legend>', _('Create new discount matrix record'), '</legend>';

$SQL = "SELECT typeabbrev,
		sales_type
		FROM salestypes";

$Result = DB_query($SQL);

echo '<field>
		<label for="SalesType">', _('Customer Price List'), ' (', _('Sales Type'), '):</label>
		<select name="SalesType" autofocus="autofocus">';

while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_POST['SalesType']) and $MyRow['typeabbrev'] == $_POST['SalesType']) {
		echo '<option selected="selected" value="', $MyRow['typeabbrev'], '">', $MyRow['sales_type'], '</option>';
	} else {
		echo '<option value="', $MyRow['typeabbrev'], '">', $MyRow['sales_type'], '</option>';
	}
}

echo '</select>
	<fieldhelp>', _('Select the sales type/price list to apply this discount record to.'), '</fieldhelp>
</field>';

$SQL = "SELECT DISTINCT discountcategory FROM stockmaster WHERE discountcategory <>''";
$Result = DB_query($SQL);
if (DB_num_rows($Result) > 0) {
	echo '<field>
			<label for="DiscountCategory">', _('Discount Category Code'), ': </label>
			<select name="DiscountCategory">';

	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['discountcategory'] == $_POST['DiscCat']) {
			echo '<option selected="selected" value="', $MyRow['discountcategory'], '">', $MyRow['discountcategory'], '</option>';
		} else {
			echo '<option value="', $MyRow['discountcategory'], '">', $MyRow['discountcategory'], '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the discount category to apply this discount record to.'), '</fieldhelp>
	</field>';
} else {
	echo '<input type="hidden" name="DiscountCategory" value="" />';
}

echo '<field>
		<label for="QuantityBreak">', _('Quantity Break'), '</label>
		<input class="number" type="text" name="QuantityBreak" size="10" required="required" maxlength="10" />
		<fieldhelp>', _('The quantity above which this discount record takes affect'), '</fieldhelp>
	</field>';

echo '<field>
		<label for="DiscountRate">', _('Discount Rate'), ' (%):</label>
		<input class="number" type="text" name="DiscountRate" size="5" required="required" maxlength="5" />
		<fieldhelp>', _('The discount rate to use when this record is applkied'), '</fieldhelp>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="' . _('Enter Information') . '" />
	</div>';

echo '</form>';

include ('includes/footer.php');
?>