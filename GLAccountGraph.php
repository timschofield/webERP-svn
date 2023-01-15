<?php
// GLAccountGraph.php
/* By Paul Becker */
/* Shows a graph of GL account transactions */

include ('includes/session.php');
include ('includes/phplot/phplot.php');
$Title = _('GL Account Graph');

$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountGraph';

include ('includes/header.php');

$NewReport = '';

if (isset($_POST['Account'])) {
	$SelectedAccount = $_POST['Account'];
} elseif (isset($_GET['Account'])) {
	$SelectedAccount = $_GET['Account'];
}

if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

if (isset($_POST['PeriodFrom']) and isset($_POST['PeriodTo'])) {

	if ($_POST['PeriodFrom'] > $_POST['PeriodTo']) {
		prnMsg(_('The selected period from is actually after the period to! Please re-select the reporting period'), 'error');
		$NewReport = 'on';
	}

}

if ((!isset($_POST['PeriodFrom']) or !isset($_POST['PeriodTo'])) or $NewReport == 'on') {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<fieldset>
			<legend>', _('Select Report Criteria'), '</legend>
			<field>
				<label for="Account">', _('Select GL Account'), ':</label>';
	GLSelect(2, 'Account');
	echo '<fieldhelp>', _('Select the GL account for this graph.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="GraphType">', _('Graph Type'), '</label>
			<select name="GraphType">
				<option value="bars">' . _('Bar Graph') . '</option>
				<option value="stackedbars">' . _('Stacked Bar Graph') . '</option>
				<option value="lines">' . _('Line Graph') . '</option>
				<option value="linepoints">' . _('Line Point Graph') . '</option>
				<option value="area">' . _('Area Graph') . '</option>
				<option value="points">' . _('Points Graph') . '</option>
				<option value="pie">' . _('Pie Graph') . '</option>
				<option value="thinbarline">' . _('Thin Bar Line Graph') . '</option>
				<option value="squared">' . _('Squared Graph') . '</option>
				<option value="stackedarea">' . _('Stacked Area Graph') . '</option>
			</select>
			<fieldhelp>', _('Select the type of graph to show.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="InvertGraph">', _('Invert Graph'), '</label>
			<td><input type="checkbox" name="InvertGraph" /></td>
			<td><fieldhelp>', _('If the selected account is normally a credit balance then select this to show the graph with credits as positive values'), '</fieldhelp>
		</field>';

	$SQL = "SELECT `id`,
					`name`,
					`current`
				FROM glbudgetheaders";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="SelectedBudget">', _('Budget To Show Comparisons With'), '</label>
			<select name="SelectedBudget">';
	while ($MyRow = DB_fetch_array($Result)) {
		if (!isset($_POST['SelectedBudget']) and $MyRow['current'] == 1) {
			$_POST['SelectedBudget'] = $MyRow['id'];
		}
		if ($MyRow['id'] == $_POST['SelectedBudget']) {
			echo '<option selected="selected" value="', $MyRow['id'], '">', $MyRow['name'], '</option>';
		} else {
			echo '<option value="', $MyRow['id'], '">', $MyRow['name'], '</option>';
		}
	}
	echo '<fieldhelp>', _('Select the budget to make comparisons with.'), '</fieldhelp>
		</select>
	</field>';

	echo '<field>
			<label for="PeriodFrom">', _('Select Period From'), ':</label>
			<select name="PeriodFrom">';

	if (Date('m') > $_SESSION['YearEnd']) {
		/*Dates in SQL format */
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
	} else {
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
	}
	$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if (isset($_POST['PeriodFrom']) and $_POST['PeriodFrom'] != '') {
			if ($_POST['PeriodFrom'] == $MyRow['periodno']) {
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

	if (!isset($_POST['PeriodTo']) or $_POST['PeriodTo'] == '') {
		$DefaultPeriodTo = GetPeriod(DateAdd(ConvertSQLDate($DefaultFromDate), 'm', 11));
	} else {
		$DefaultPeriodTo = $_POST['PeriodTo'];
	}

	echo '<field>
			<label for="PeriodTo">', _('Select Period To'), ':</label>
			<select name="PeriodTo">';

	$RetResult = DB_data_seek($Periods, 0);

	while ($MyRow = DB_fetch_array($Periods)) {

		if ($MyRow['periodno'] == $DefaultPeriodTo) {
			echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
		} else {
			echo '<option value ="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the end period for this report'), '</fieldhelp>
	</field>';

	if (!isset($_POST['Period'])) {
		$_POST['Period'] = '';
	}

	echo '<field>
				<h3>', _('OR'), '</h3>
		</field>';

	echo '<field>
			<label for="Period">', _('Select Period'), ':</label>
			', ReportPeriodList($_POST['Period']), '
			<fieldhelp>', _('Select a predefined period from this list. If a selection is made here it will override anything selected in the From and To options above.'), '</fieldhelp>
		</field>';

	echo '</fieldset>
			<div class="centre">
				<input type="submit" name="ShowGraph" value="', _('Show Account Graph'), '" />
			</div>
        </form>';
	include ('includes/footer.php');
} else {

	$Graph = new PHPlot(950, 450);
	$SelectClause = '';
	$WhereClause = '';
	$GraphTitle = '';

	$GraphTitle = _('GL Account Graph - Actual vs. Budget') . "\n\r";
	//$GraphTitle .= $SelectedAccount . " - " . $SelectedAccountName . "\n\r";
	$SQL = "SELECT YEAR(`lastdate_in_period`) AS year, MONTHNAME(`lastdate_in_period`) AS month
			  FROM `periods`
			 WHERE `periodno`='" . $_POST['PeriodFrom'] . "' OR periodno='" . $_POST['PeriodTo'] . "'";

	$Result = DB_query($SQL);

	$PeriodFrom = DB_fetch_array($Result);
	$Starting = $PeriodFrom['month'] . ' ' . $PeriodFrom['year'];

	$PeriodTo = DB_fetch_array($Result);
	$Ending = $PeriodTo['month'] . ' ' . $PeriodTo['year'];

	$GraphTitle.= ' ' . _('From Period') . ' ' . $Starting . ' ' . _('to') . ' ' . $Ending . "\n\r";

	$WhereClause = "WHERE " . $WhereClause . " periods.periodno>='" . $_POST['PeriodFrom'] . "' AND periods.periodno <= '" . $_POST['PeriodTo'] . "'";

	$SQL = "SELECT amount AS actual,
					period
				FROM gltotals
				WHERE period>='" . $_POST['PeriodFrom'] . "'
					AND period <='" . $_POST['PeriodTo'] . "'
					AND account='" . $SelectedAccount . "'
				GROUP BY period
				ORDER BY period";

	$Graph->SetTitle($GraphTitle);
	$Graph->SetTitleColor('blue');
	$Graph->SetOutputFile('companies/' . $_SESSION['DatabaseName'] . '/reports/glaccountgraph.png');
	$Graph->SetXTitle(_('Month'));

	$Graph->SetXTickPos('none');
	$Graph->SetXTickLabelPos('none');
	$Graph->SetXLabelAngle(90);
	$Graph->SetBackgroundColor('white');
	$Graph->SetTitleColor('blue');
	$Graph->SetFileFormat('png');
	$Graph->SetPlotType($_POST['GraphType']);
	$Graph->SetIsInline('1');
	$Graph->SetShading(5);
	$Graph->SetDrawYGrid(true);
	$Graph->SetDataType('text-data');
	$Graph->SetNumberFormat($DecimalPoint, $ThousandsSeparator);
	$Graph->SetPrecisionY($_SESSION['CompanyRecord']['decimalplaces']);

	$SalesResult = DB_query($SQL);
	if (DB_error_no() != 0) {

		prnMsg(_('The GL Account graph data for the selected criteria could not be retrieved because') . ' - ' . DB_error_msg(), 'error');
		include ('includes/footer.php');
		exit;
	}
	if (DB_num_rows($SalesResult) == 0) {
		prnMsg(_('There is not GL Account data for the criteria entered to graph'), 'info');
		include ('includes/footer.php');
		exit;
	}

	$GraphArray = array();
	$i = 0;
	while ($MyRow = DB_fetch_array($SalesResult)) {
		$SQL = "SELECT SUM(amount) AS budget
				FROM glbudgetdetails
				WHERE account='" . $SelectedAccount . "'
					AND period='" . $MyRow['period'] . "'
					AND headerid='" . $_POST['SelectedBudget'] . "'";
		$PeriodBudgetResult = DB_query($SQL);
		$PeriodBudgetRow = DB_fetch_array($PeriodBudgetResult);
		if (!isset($PeriodBudgetRow['periodbudget'])) {
			$PeriodBudgetRow['periodbudget'] = 0;
		}
		$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $MyRow['period'] . "'";
		$PeriodResult = DB_query($PeriodSQL);
		$PeriodRow = DB_fetch_array($PeriodResult);
		if (!isset($_POST['InvertGraph'])) {
			$GraphArray[$i] = array(MonthAndYearFromSQLDate($PeriodRow['lastdate_in_period']), $MyRow['actual'], $PeriodBudgetRow['budget']);
		} else {
			$GraphArray[$i] = array(MonthAndYearFromSQLDate($PeriodRow['lastdate_in_period']), -$MyRow['actual'], $PeriodBudgetRow['budget']);
		}
		++$i;
	}

	$Graph->SetDataValues($GraphArray);
	$Graph->SetDataColors(array('grey', 'wheat'), //Data Colors
	array('black') //Border Colors
	);
	$Graph->SetLegend(array(_('Actual'), _('Budget')));
	$Graph->SetYDataLabelPos('plotin');

	//Draw it
	$Graph->DrawGraph();
	echo '<table class="selection">
			<tr>
				<td><p><img class="Graph" src="companies/', $_SESSION['DatabaseName'], '/reports/glaccountgraph.png" alt="Sales Report Graph"></img></p></td>
			</tr>
		  </table>';

	echo '<div class="noprint centre">
			<a href="', basename(__FILE__), '">', _('Select Different Criteria'), '</a>
		</div>';
	include ('includes/footer.php');
}
?>