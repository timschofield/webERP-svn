<?php
include ('includes/session.php');

if (isset($_GET['SelectedSalesPerson'])) {
	$SelectedSalesPerson = mb_strtoupper($_GET['SelectedSalesPerson']);
} elseif (isset($_POST['SelectedSalesPerson'])) {
	$SelectedSalesPerson = mb_strtoupper($_POST['SelectedSalesPerson']);
} else {
	$Title = _('Sales Commission Rates');
	include ('includes/header.php');
	prnMsg(_('This script can only be called from') . ' ' . '<a href="' . $RootPath . '/SalesPeople.php">' . _('here') . '</a>', 'error');
	include ('includes/footer.php');
	exit;
}

$SQL = "SELECT salesarea, salesmanname FROM salesman WHERE salesmancode='" . $SelectedSalesPerson . "'";
$Result = DB_query($SQL);
$MyRow = DB_fetch_array($Result);
$SalesPersonName = $MyRow['salesmanname'];
$SalesPersonArea = $MyRow['salesarea'];

$Title = _('Sales Commission Maintenance For') . ' ' . $SalesPersonName;
$ViewTopic = 'SalesPeople';
$BookMark = 'SalesPeople';
include ('includes/header.php');

if (isset($_POST['Submit'])) {
	$InputError = 0;

	if (!is_numeric($_POST['StartFrom'])) {
		prnMsg(_('The start from field must be numeric'), 'error');
		$InputError = 1;
	}
	if (!is_numeric($_POST['Rate'])) {
		prnMsg(_('The commission rate field must be numeric'), 'error');
		$InputError = 1;
	}

	if (isset($_POST['New'])) {
		$CheckSQL = "SELECT salespersoncode
						FROM salescommissionrates
						WHERE salespersoncode='" . $SelectedSalesPerson . "'
							AND categoryid='" . $_POST['CategoryID'] . "'
							AND area='" . $_POST['Area'] . "'
							AND startfrom='" . $_POST['StartFrom'] . "'
							AND daysactive='" . $_POST['DaysActive'] . "'
							AND currency='" . $_POST['Currency'] . "'";
		$CheckResult = DB_query($CheckSQL);
		if (DB_num_rows($CheckResult) > 0) {
			prnMsg(_('A record already exists for this category, area, currency, start from amount, and salesperson'), 'error');
			$InputError = 1;
		}
		if ($InputError == 0) {
			$InsertSQL = "INSERT INTO salescommissionrates (salespersoncode,
															categoryid,
															area,
															startfrom,
															daysactive,
															rate,
															currency
														) VALUES (
															'" . $SelectedSalesPerson . "',
															'" . $_POST['CategoryID'] . "',
															'" . $_POST['Area'] . "',
															'" . filter_number_format($_POST['StartFrom']) . "',
															'" . filter_number_format($_POST['DaysActive']) . "',
															'" . filter_number_format($_POST['Rate']) . "',
															'" . $_POST['Currency'] . "'
														)";
			$InsertResult = DB_query($InsertSQL);
			if (DB_error_no() == 0) {
				prnMsg(_('Commission record was correctly saved'), 'success');
			} else {
				prnMsg(_('There was an error saving the commission record'), 'error');
			}
		}
	} else {
		if ($InputError == 0) {
			$UpdateSQL = "UPDATE salescommissionrates SET rate='" . filter_number_format($_POST['Rate']) . "'
													WHERE salespersoncode='" . $SelectedSalesPerson . "'
														AND categoryid='" . $_POST['CategoryID'] . "'
														AND area='" . $_POST['Area'] . "'
														AND startfrom='" . $_POST['StartFrom'] . "'
														AND daysactive='" . $_POST['DaysActive'] . "'
														AND currency='" . $_POST['Currency'] . "'";
			$UpdateResult = DB_query($UpdateSQL);
			if (DB_error_no() == 0) {
				prnMsg(_('Commission record was correctly saved'), 'success');
			} else {
				prnMsg(_('There was an error saving the commission record'), 'error');
			}
		}
	}
}

if (isset($_GET['Delete'])) {
	$DeleteSQL = "DELETE FROM salescommissionrates
					WHERE salespersoncode='" . $SelectedSalesPerson . "'
						AND categoryid='" . $_GET['SelectedCategory'] . "'
						AND area='" . $_GET['Area'] . "'
						AND startfrom='" . $_GET['StartFrom'] . "'
						AND daysactive='" . $_GET['DaysActive'] . "'
						AND currency='" . $_GET['Currency'] . "'";
	$DeleteResult = DB_query($DeleteSQL);
	if (DB_error_no() == 0) {
		prnMsg(_('Commission record was correctly deleted'), 'success');
	} else {
		prnMsg(_('There was an error deleting the commission record'), 'error');
	}
}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

$SQL = "SELECT  salescommissionrates.categoryid,
				salescommissionrates.area,
				salescommissionrates.startfrom,
				salescommissionrates.daysactive,
				salescommissionrates.rate,
				salescommissionrates.currency,
				currencies.currency AS currencyname,
				currencies.decimalplaces
			FROM salescommissionrates
			INNER JOIN currencies
				ON currencies.currabrev=salescommissionrates.currency
			WHERE salescommissionrates.salespersoncode='" . $SelectedSalesPerson . "'
			ORDER BY categoryid, startfrom";
$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<table>
			<thead>
				<tr>
					<th class="SortedColumn">', _('Stock Category'), '</th>
					<th class="SortedColumn">', _('Area'), '</th>
					<th class="SortedColumn">', _('Currency'), '</th>
					<th class="SortedColumn">', _('Starting Level'), '</th>
					<th class="SortedColumn">', _('After Days Active'), '</th>
					<th class="SortedColumn">', _('Commission Rate (%)'), '</th>
					<th colspan="2"></th>
				</tr>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['categoryid'] == 'ALL') {
			$CategoryRow['categorydescription'] = _('All Categories');
		} else {
			$CategorySQL = "SELECT categorydescription FROM stockcategory WHERE categoryid='" . $MyRow['categoryid'] . "'";
			$CategoryResult = DB_query($CategorySQL);
			$CategoryRow = DB_fetch_array($CategoryResult);
		}
		if ($MyRow['area'] == 'ALL') {
			$AreaRow['areadescription'] = _('All Areas');
		} else {
			$AreaSQL = "SELECT areadescription FROM areas WHERE areacode='" . $MyRow['area'] . "'";
			$AreaResult = DB_query($AreaSQL);
			$AreaRow = DB_fetch_array($AreaResult);
		}
		echo '<tr class="striped_row">
				<td>', $MyRow['categoryid'], ' - ', $CategoryRow['categorydescription'], '</td>
				<td>', $MyRow['area'], ' - ', $AreaRow['areadescription'], '</td>
				<td>', $MyRow['currency'], ' - ', $MyRow['currencyname'], '</td>
				<td class="number">', locale_number_format($MyRow['startfrom'], $MyRow['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['daysactive'], 0), '</td>
				<td class="number">', locale_number_format($MyRow['rate'], 2), '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedSalesPerson=', urlencode($SelectedSalesPerson), '&SelectedCategory=', urlencode($MyRow['categoryid']), '&Area=', urlencode($MyRow['area']), '&Currency=', urlencode($MyRow['currency']), '&StartFrom=', urlencode($MyRow['startfrom']), '&DaysActive=', urlencode($MyRow['daysactive']), '&Edit=Yes">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedSalesPerson=', urlencode($SelectedSalesPerson), '&SelectedCategory=', urlencode($MyRow['categoryid']), '&Area=', urlencode($MyRow['area']), '&Currency=', urlencode($MyRow['currency']), '&StartFrom=', urlencode($MyRow['startfrom']), '&DaysActive=', urlencode($MyRow['daysactive']), '&Delete=1" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this sales commission record?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';
	}

	echo '</tbody>
		</table>';
}

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if (isset($_GET['Edit'])) {
	$SQL = "SELECT  salescommissionrates.rate,
					currencies.currency AS currencyname
				FROM salescommissionrates
				INNER JOIN currencies
					ON currencies.currabrev=salescommissionrates.currency
				WHERE salescommissionrates.salespersoncode='" . $SelectedSalesPerson . "'
					AND categoryid='" . $_GET['SelectedCategory'] . "'
					AND area='" . $_GET['Area'] . "'
					AND salescommissionrates.currency='" . $_GET['Currency'] . "'
					AND daysactive='" . $_GET['DaysActive'] . "'
					AND startfrom='" . $_GET['StartFrom'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_POST['Area'] = $_GET['Area'];
	$_POST['CategoryID'] = $_GET['SelectedCategory'];
	$_POST['Currency'] = $_GET['Currency'];
	$_POST['StartFrom'] = $_GET['StartFrom'];
	$_POST['DaysActive'] = $_GET['DaysActive'];
	$_POST['Rate'] = $MyRow['rate'];

	if ($_POST['CategoryID'] == 'ALL') {
		$CategoryRow['categorydescription'] = _('All Categories');
	} else {
		$CategorySQL = "SELECT categorydescription FROM stockcategory WHERE categoryid='" . $_POST['CategoryID'] . "'";
		$CategoryResult = DB_query($CategorySQL);
		$CategoryRow = DB_fetch_array($CategoryResult);
	}

	if ($_POST['Area'] == 'ALL') {
		$AreaRow['areadescription'] = _('All Areas');
	} else {
		$AreaSQL = "SELECT areadescription FROM areas WHERE areacode='" . $_POST['Area'] . "'";
		$AreaResult = DB_query($AreaSQL);
		$AreaRow = DB_fetch_array($AreaResult);
	}

	echo '<input type="hidden" name="SelectedSalesPerson" value="', $SelectedSalesPerson, '" />';
	echo '<input type="hidden" name="Currency" value="', $_GET['Currency'], '" />';
	echo '<input type="hidden" name="StartFrom" value="', $_GET['StartFrom'], '" />';
	echo '<input type="hidden" name="DaysActive" value="', $_GET['DaysActive'], '" />';
	echo '<input type="hidden" name="CategoryID" value="', $_POST['CategoryID'], '" />';
	echo '<input type="hidden" name="Area" value="', $_POST['Area'], '" />';
	echo '<fieldset>
			<legend>', _('Edit Commission Details'), '</legend>
			<field>
				<label for="CategoryID">', _('Stock Category'), '</label>
				<div class="fieldtext">', $_GET['SelectedCategory'], ' - ', $CategoryRow['categorydescription'], '</div>
			</field>
			<field>
				<label for="Area">', _('Sales Area'), '</label>
				<div class="fieldtext">', $_GET['Area'], ' - ', $AreaRow['areadescription'], '</div>
			</field>
			<field>
				<label for="Currency">', _('Currency'), '</label>
				<div class="fieldtext">', $_GET['Currency'], ' - ', $MyRow['currencyname'], '</div>
			</field>
			<field>
				<label for="StartFrom">', _('Start From Value'), '</label>
				<div class="fieldtext">', $_GET['StartFrom'], '</div>
			</field>
			<field>
				<label for="DaysActive">', _('Below Number of Active Days'), '</label>
				<div class="fieldtext">', $_GET['DaysActive'], '</div>
			</field>';
} else {
	$_POST['CategoryID'] = '';
	$_POST['Currency'] = $_SESSION['CompanyRecord']['currencydefault'];
	$_POST['StartFrom'] = 0;
	$_POST['Rate'] = 0;
	$_POST['DaysActive'] = 0;

	echo '<input type="hidden" name="SelectedSalesPerson" value="', $SelectedSalesPerson, '" />';
	echo '<input type="hidden" name="New" value="Yes" />';
	echo '<fieldset>
			<legend>', _('Create Commission Details'), '</legend>';

	$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="CategoryID">', _('Stock Category'), '</label>
			<select name="CategoryID" autofocus="autofocus" required="required">
				<option value=""></option>
				<option value="ALL">', _('All Categories'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['categoryid'] == $_POST['CategoryID']) {
			echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], ' (', $MyRow['categoryid'], ')</option>';
		} else {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], ' (', $MyRow['categoryid'], ')</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('A commission rate can apply to only certain items. Select the stock category to which this commission rate applies. If it applies to all categories select All.'), '</fieldhelp>
	</field>';

	$SQL = "SELECT areacode, areadescription FROM areas";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="Area">', _('Sales Area'), '</label>
			<select name="Area" required="required">
				<option value="ALL">', _('All Areas'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['areacode'] == $_POST['Area'] or $MyRow['areacode'] == $SalesPersonArea) {
			echo '<option selected="selected" value="', $MyRow['areacode'], '">', $MyRow['areadescription'], ' (', $MyRow['areacode'], ')</option>';
		} else {
			echo '<option value="', $MyRow['areacode'], '">', $MyRow['areadescription'], ' (', $MyRow['areacode'], ')</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('A commission rate can apply to only a certain area. Select the area to which this commission rate applies. If it applies to all areas select All.'), '</fieldhelp>
	</field>';

	$SQL = "SELECT currabrev, currency FROM currencies";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="Currency">', _('Currency'), '</label>
			<select name="Currency" required="required">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['currabrev'] == $_POST['Currency']) {
			echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $MyRow['currency'], ' (', $MyRow['currabrev'], ')</option>';
		} else {
			echo '<option value="', $MyRow['currabrev'], '">', $MyRow['currency'], ' (', $MyRow['currabrev'], ')</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the currency for this commission record. Only transactions in this currency will be included for commission purposes.'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="StartFrom">', _('Start From Value'), '</label>
			<input type="text" class="number" requited="required" name="StartFrom" value="0.0" />
			<fieldhelp>', _('The value at which this commission rate kicks in.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="DaysActive">', _('Below Number of Active Days'), '</label>
			<input type="text" class="number" requited="required" name="DaysActive" size="3" value="0" />
			<fieldhelp>', _('This commission can apply for just a certain number of days after the customer has become active. Choose zero if the rate always applies.'), '</fieldhelp>
		</field>';
}

echo '<field>
		<label for="Rate">', _('Commission Rate'), '</label>
		<input type="text" class="number" requited="required" name="Rate" value="', $_POST['Rate'], '" /> %
		<fieldhelp>', _('The percentage commission to be applied for this record.'), '</fieldhelp>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="Submit" value="Save" />
	</div>';

echo '</form>';
include ('includes/footer.php');
?>