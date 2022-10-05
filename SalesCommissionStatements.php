<?php
include ('includes/session.php');

$Title = _('Sales Commission Statements');
/* Manual links before header.php */
$ViewTopic = 'SalesCommission';
$BookMark = 'Reports';
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/reports.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
	</p>';

if (isset($_POST['Submit'])) {

	if ($_POST['Period'] != '') {
		$_POST['FromPeriod'] = ReportPeriod($_POST['Period'], 'From');
		$_POST['ToPeriod'] = ReportPeriod($_POST['Period'], 'To');
	}

	$SQL = "SELECT salescommissions.commissionno,
					salescommissions.type,
					salescommissions.transno,
					salescommissions.stkmoveno,
					salescommissions.salespersoncode,
					salescommissions.paid,
					salescommissions.amount,
					salesman.salesmanname,
					MONTHNAME(periods.lastdate_in_period) AS month,
					YEAR(periods.lastdate_in_period) AS year,
					salescommissions.currency,
					salescommissions.exrate,
					stockmoves.debtorno,
					stockmoves.type AS invcredit,
					stockmoves.transno AS invcredno,
					debtorsmaster.name,
					currencies.decimalplaces
				FROM salescommissions
				INNER JOIN gltrans
					ON salescommissions.commissionno=gltrans.typeno
					AND gltrans.type=39
				INNER JOIN salesman
					ON salescommissions.salespersoncode=salesman.salesmancode
				INNER JOIN periods
					ON periods.periodno=gltrans.periodno
				INNER JOIN stockmoves
					ON salescommissions.stkmoveno=stockmoves.stkmoveno
				INNER JOIN debtorsmaster
					ON stockmoves.debtorno=debtorsmaster.debtorno
				INNER JOIN currencies
					ON salescommissions.currency=currencies.currabrev
				WHERE salescommissions.salespersoncode LIKE '" . $_POST['SalesPerson'] . "'
					AND salescommissions.currency LIKE '" . $_POST['Currency'] . "'
					AND salescommissions.paid LIKE '" . $_POST['PaidUnpaid'] . "'
					AND gltrans.periodno>='" . $_POST['FromPeriod'] . "'
					AND gltrans.periodno<='" . $_POST['ToPeriod'] . "'
					AND gltrans.account='" . $_SESSION['CompanyRecord']['commissionsact'] . "'
				ORDER BY salescommissions.commissionno";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) > 0) {
		echo '<table>
				<thead>
					<tr>
						<th class="SortedColumn">', _('Commission ID'), '</th>
						<th class="SortedColumn">', _('Sales Person'), '</th>
						<th class="SortedColumn">', _('Period'), '</th>
						<th class="SortedColumn">', _('Customer'), '</th>
						<th class="SortedColumn">', _('Invoice/Credit'), '</th>
						<th class="SortedColumn">', _('Amount'), '</th>
						<th class="SortedColumn">', _('Paid?'), '</th>
					</tr>
				</thead>';
		echo '<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {
			if ($MyRow['invcredit'] == 10) {
				$Type = _('Invoice');
			} else {
				$Type = _('Credit');
			}
			if ($MyRow['paid'] == 0) {
				$Paid = _('No');
			} else {
				$Paid = _('Yes');
			}
			echo '<tr class="striped_row">
					<td>', $MyRow['commissionno'], '</td>
					<td>', $MyRow['salesmanname'], '</td>
					<td>', $MyRow['month'], ' ', $MyRow['year'], '</td>
					<td>', $MyRow['debtorno'], ' - ', $MyRow['name'], '</td>
					<td>', $Type, ' no ', $MyRow['invcredno'], '</td>
					<td class="number">', locale_number_format($MyRow['amount'], $MyRow['decimalplaces']), '</td>
					<td>', $Paid, '</td>
				</tr>';
		}

		echo '</tbody>
			</table>';
	} else {
		prnMsg(_('There are no commissions meeting this criteria. Please select different criteria and run the report again.'), 'info');
	}
	echo '<a class="noPrint" href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Select different report criteria'), '</a><br />';
	include ('includes/footer.php');
	exit;

} else {

	if (!isset($_POST['SalesPerson'])) {
		$_POST['SalesPerson'] = '%%';
	}

	if (!isset($_POST['Currency'])) {
		$_POST['Currency'] = '%%';
	}

	if (!isset($_POST['PaidUnpaid'])) {
		$_POST['PaidUnpaid'] = '%%';
	}

	if (!isset($_POST['FromPeriod'])) {
		$_POST['FromPeriod'] = GetPeriod(date($_SESSION['DefaultDateFormat']));
	}

	if (!isset($_POST['ToPeriod'])) {
		$_POST['ToPeriod'] = GetPeriod(date($_SESSION['DefaultDateFormat']));
	}

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<legend>', _('Report Criteria'), '</legend>
			<field>
				<label for="SalesPerson">', _('Sales Person'), '</label>
				<select name="SalesPerson" autofocus="autofocus">';

	$SQL = "SELECT salesmancode,
					salesmanname
				FROM salesman";
	$Result = DB_query($SQL);
	echo '<option value="%%">', _('All Sales People'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="', $MyRow['salesmancode'], '">', $MyRow['salesmanname'], '</option>';
	}
	echo '</select>
		<fieldhelp>', _('Select the sales person to report on.'), '</fieldhelp>
	</field>';

	$SQL = "SELECT currabrev, currency FROM currencies";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="Currency">', _('Currency'), '</label>
			<select name="Currency" required="required">';
	echo '<option value="%%">', _('All Currencies'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['currabrev'] == $_POST['Currency']) {
			echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $MyRow['currency'], ' (', $MyRow['currabrev'], ')</option>';
		} else {
			echo '<option value="', $MyRow['currabrev'], '">', $MyRow['currency'], ' (', $MyRow['currabrev'], ')</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the currency of the transactions to report on.'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="PaidUnpaid">', _('Show Paid or Unpaid Commissions'), '</label>
			<select name="PaidUnpaid">
				<option value="%%">', _('All Commissions'), '</option>
				<option value="0">', _('Only Unpaid Commissions'), '</option>
				<option value="1">', _('Only Paid Commissions'), '</option>
			</select>
			<fieldhelp>', _('Filter commissions by whether they are paid or unpaid'), '</fieldhelp>
		</field>';

	echo '			<field>
				<label for="FromPeriod">', _('Select Period From'), ':</label>
				<select name="FromPeriod" autofocus="autofocus">';
	$NextYear = date('Y-m-d', strtotime('+1 Year'));
	$SQL = "SELECT periodno,
					lastdate_in_period
				FROM periods
				WHERE lastdate_in_period < '" . $NextYear . "'
				ORDER BY periodno DESC";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if (isset($_POST['FromPeriod']) and $_POST['FromPeriod'] != '') {
			if ($_POST['FromPeriod'] == $MyRow['periodno']) {
				echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			} else {
				echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			}
		} else {
			if ($MyRow['lastdate_in_period'] == $DefaultFromDate) {
				echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			} else {
				echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			}
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the starting period for this report'), '</fieldhelp>
	</field>';

	if (!isset($_POST['ToPeriod']) or $_POST['ToPeriod'] == '') {
		$DefaultToPeriod = GetPeriod(date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, Date('m') + 1, 0, Date('Y'))));
	} else {
		$DefaultToPeriod = $_POST['ToPeriod'];
	}

	echo '<field>
			<label for="ToPeriod">', _('Select Period To'), ':</label>
			<select name="ToPeriod">';

	$RetResult = DB_data_seek($Periods, 0);

	while ($MyRow = DB_fetch_array($Periods)) {

		if ($MyRow['periodno'] == $DefaultToPeriod) {
			echo '<option selected="selected" value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		} else {
			echo '<option value ="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the end period for this report'), '</fieldhelp>
	</field>';

	echo '<h3>', _('OR'), '</h3>';

	if (!isset($_POST['Period'])) {
		$_POST['Period'] = '';
	}

	echo '<field>
			<label for="Period">', _('Select Period'), ':</label>
			', ReportPeriodList($_POST['Period'], array('l', 't')), '
			<fieldhelp>', _('Select a predefined period from this list. If a selection is made here it will override anything selected in the From and To options above.'), '</fieldhelp>
		</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Submit" value="', _('View Report'), '" />
		</div>';

	echo '</form>';
}

include ('includes/footer.php');
?>