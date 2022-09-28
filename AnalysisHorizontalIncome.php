<?php
/* AnalysisHorizontalIncome.php
Shows the horizontal analysis of the statement of comprehensive income.
Parameters:
	PeriodFrom: Select the beginning of the reporting period.
	PeriodTo: Select the end of the reporting period.
	Period: Select a period instead of using the beginning and end of the reporting period.
	ShowDetail: Check this box to show all accounts instead a summary.
	ShowZeroBalance: Check this box to show accounts with zero balance.
	ShowFinancialPosition: Check this box to show the statement of financial position as at the end and at the beginning of the period;
	ShowComprehensiveIncome: Check this box to show the statement of comprehensive income;
	ShowChangesInEquity: Check this box to show the statement of changes in equity;
	ShowCashFlows: Check this box to show the statement of cash flows; and
	ShowNotes: Check this box to show the notes that summarize the significant accounting policies and other explanatory information.
	NewReport: Click this button to start a new report.
	IsIncluded: Parameter to indicate that a script is included within another.
*/
// BEGIN: Functions division ===================================================
function RelativeChange($SelectedPeriod, $PreviousPeriod) {
	// Calculates the relative change between selected and previous periods. Uses percent in locale number format.
	if ($PreviousPeriod <> 0) {
		return locale_number_format(($SelectedPeriod - $PreviousPeriod) * 100 / $PreviousPeriod, $_SESSION['CompanyRecord']['decimalplaces']) . '%';
	} else {
		return _('N/A');
	}
}

include ('includes/session.php');
$Title = _('Horizontal Analysis of Statement of Comprehensive Income');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AnalysisHorizontalIncome';

include ('includes/header.php');
// Merges gets into posts:
if (isset($_GET['PeriodFrom'])) {
	$_POST['PeriodFrom'] = $_GET['PeriodFrom'];
}
if (isset($_GET['PeriodTo'])) {
	$_POST['PeriodTo'] = $_GET['PeriodTo'];
}
if (isset($_GET['Period'])) {
	$_POST['Period'] = $_GET['Period'];
}
if (isset($_GET['ShowDetail'])) {
	$_POST['ShowDetail'] = $_GET['ShowDetail'];
}
if (isset($_GET['ShowZeroBalance'])) {
	$_POST['ShowZeroBalance'] = $_GET['ShowZeroBalance'];
}
if (isset($_GET['NewReport'])) {
	$_POST['NewReport'] = $_GET['NewReport'];
}

include ('includes/SQL_CommonFunctions.php');
include ('includes/AccountSectionsDef.php'); // This loads the $Sections variable
if (isset($_POST['PeriodFrom']) and ($_POST['PeriodFrom'] > $_POST['PeriodTo'])) {
	prnMsg(_('The selected period from is actually after the period to') . '! ' . _('Please reselect the reporting period'), 'error');
	$_POST['NewReport'] = 'on';
}

if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

if ((!isset($_POST['PeriodFrom']) and !isset($_POST['PeriodTo'])) or isset($_POST['NewReport'])) {

	echo '<p class="page_title_text">
			<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print Horizontal Analysis of Statement of Comprehensive Income'), '" /> ', // Icon title.
	_('Horizontal Analysis of Statement of Comprehensive Income'), '
		</p>'; // Page title.
	echo '<div class="page_help_text">
			', _('Horizontal analysis (also known as trend analysis) is a financial statement analysis technique that shows changes in the amounts of corresponding financial statement items over a period of time. It is a useful tool to evaluate trend situations.'), '<br />', _('The statements for two periods are used in horizontal analysis. The earliest period is used as the base period. The items on the later statement are compared with items on the statement of the base period. The changes are shown both in currency (absolute change) and percentage (relative change).'), '<br />', _('KwaMoja is an accrual based system (not a cash based system).  Accrual systems include items when they are invoiced to the customer, and when expenses are owed based on the supplier invoice date.'), '
		</div>';
	// Show a form to allow input of criteria for the report to show:
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<fieldset>
			<legend>', _('Select Report Criteria'), '</legend>
			<field>
				<label for="PeriodFrom">', _('Select period from'), ':</label>
				<select name="PeriodFrom" required="required" autofocus="autofocus">';

	if (Date('m') > $_SESSION['YearEnd']) {
		/*Dates in SQL format */
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
	} else {
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
	}

	$SQL = "SELECT periodno,
					lastdate_in_period
				FROM periods
				ORDER BY periodno DESC";
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
		<fieldhelp>', _('Select the first period in the report'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="PeriodTo">', _('Select period to'), ':</label>
			<select required="required" name="PeriodTo">';

	if (!isset($_POST['PeriodTo']) or $_POST['PeriodTo'] == '') {
		$LastDate = date('Y-m-d', mktime(0, 0, 0, Date('m') + 1, 0, Date('Y')));
		$SQL = "SELECT periodno FROM periods where lastdate_in_period = '" . $LastDate . "'";
		$MaxPrd = DB_query($SQL);
		$MaxPrdrow = DB_fetch_row($MaxPrd);
		$DefaultPeriodTo = (int)($MaxPrdrow[0]);
	} else {
		$DefaultPeriodTo = $_POST['PeriodTo'];
	}

	$RetResult = DB_data_seek($Periods, 0);

	while ($MyRow = DB_fetch_array($Periods)) {
		echo '<option';
		if ($MyRow['periodno'] == $DefaultPeriodTo) {
			echo ' selected="selected"';
		}
		echo ' value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
	}

	echo '</select>
		<fieldhelp>', _('Select the last period in the report'), '</fieldhelp>
	</field>';

	if (!isset($_POST['Period'])) {
		$_POST['Period'] = '';
	}

	echo '<h3>', _('OR'), '</h3>';

	echo '<field>
			<label for="Period">', _('Select Period'), ':</label>
			', ReportPeriodList($_POST['Period'], array('l', 't')), '
			<fieldhelp>', _('Select a predefined period from this list. If a selection is made here it will override anything selected in the From and To options above.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="Detail">', _('Detail or summary'), ':</label>
			<select name="Detail" required="required" title="', _('Selecting Summary will show on the totals at the account group level'), '" >
				<option value="Summary">', _('Summary'), '</option>
				<option selected="selected" value="Detailed">', _('All Accounts'), '</option>
			</select>
			<fieldhelp>', _('Selecting Summary will show on the totals at the account group level'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="ShowZeroBalances">', _('Show all accounts including zero balances'), '</label>
			<input name="ShowZeroBalances" title="', _('Check this box to display all accounts including those accounts with no balance'), '" type="checkbox" />
			<fieldhelp>', _('Check this box to display all accounts including those accounts with no balance'), '</fieldhelp>
		</field>
	</fieldset>';

	echo '<div class="centre noPrint">
			<input name="ShowPL" type="submit" value="', _('Show on Screen (HTML)'), '" />
		</div>';

} else {

	$NumberOfMonths = $_POST['PeriodTo'] - $_POST['PeriodFrom'] + 1;

	if ($NumberOfMonths > 12) {
		echo '<br />';
		prnMsg(_('A period up to 12 months in duration can be specified') . ' - ' . _('the system automatically shows a comparative for the same period from the previous year') . ' - ' . _('it cannot do this if a period of more than 12 months is specified') . '. ' . _('Please select an alternative period range'), 'error');
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['PeriodTo'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($MyRow[0]);

	// Page title as IAS 1, numerals 10 and 51:
	include_once ('includes/CurrenciesArray.php'); // Array to retrieve currency name.
	echo '<div id="Report">', // Division to identify the report block.
	'<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" title="', // Icon image.
	_('Horizontal Analysis of Statement of Comprehensive Income'), '" /> ', // Icon title.
	_('Horizontal Analysis of Statement of Comprehensive Income'), '<br />', // Page title, reporting statement.
	stripslashes($_SESSION['CompanyRecord']['coyname']), '<br />', // Page title, reporting entity.
	_('For'), ' ', $NumberOfMonths, ' ', _('months to'), ' ', $PeriodToDate, '<br />', // Page title, reporting period.
	_('All amounts stated in'), ': ', _($CurrencyName[$_SESSION['CompanyRecord']['currencydefault']]), '</p>'; // Page title, reporting presentation currency and level of rounding used.
	echo '<table class="scrollable">
			<thead>
				<tr class="noPrint">
					<th colspan="6"><h3>', $Title, '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print'), '" alt="', _('Print'), '" onclick="window.print();" />
					</h3></th>
				<tr>';
	if ($_POST['Detail'] == 'Detailed') { // Detailed report:
		echo '<th class="text">', _('Account'), '</th>
			<th class="text">', _('Account Name'), '</th>';
	} else { // Summary report:
		echo '<th class="text" colspan="2">', _('Summary'), '</th>';
	}
	echo '<th class="number">', _('Current period'), '</th>
			<th class="number">', _('Last period'), '</th>
			<th class="number">', _('Absolute change'), '</th>
			<th class="number">', _('Relative change'), '</th>
		</tr>
	</thead>';
	echo '<tfoot>
			<tr>
				<td class="text" colspan="6">
					<br /><b>', _('Notes'), ':</b><br />', _('Absolute change signs: a positive number indicates a change that increases the net profit; a negative number indicates a change that decreases the net profit.'), '<br />', _('Relative change signs: a positive number indicates an increase in the amount of that account; a negative number indicates a decrease in the amount of that account.'), '<br />
				</td>
			</tr>
		</tfoot>
		<tbody>'; // thead and tfoot used in conjunction with tbody enable scrolling of the table body independently of the header and footer. Also, when printing a large table that spans multiple pages, these elements can enable the table header to be printed at the top of each page.
	// Get all account codes
	$SQL = "SELECT sectionid,
					sectionname,
					parentgroupname,
					parentgroupcode,
					chartmaster.groupcode,
					chartmaster.accountcode,
					group_ AS groupname,
					chartmaster.language,
					accountname,
					sectioninaccounts,
					pandl
				FROM chartmaster
				INNER JOIN glaccountusers
					ON glaccountusers.accountcode=chartmaster.accountcode
					AND glaccountusers.userid='" . $_SESSION['UserID'] . "'
					AND glaccountusers.canview=1
				INNER JOIN accountgroups
					ON accountgroups.groupcode=chartmaster.groupcode
					AND accountgroups.language=chartmaster.language
				INNER JOIN accountsection
					ON accountsection.sectionid=accountgroups.sectioninaccounts
					AND accountgroups.language=accountsection.language
				WHERE chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
					AND pandl=1
				ORDER BY sequenceintb,
						groupcode,
						accountcode";
	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'), _('The SQL that failed was'));

	$PeriodTotal = 0;
	$PeriodTotalLY = 0;

	$Section = '';
	$SectionTotal = 0;
	$SectionTotalLY = 0;

	$ActGrp = '';
	$GrpTotal = array(0);
	$GrpTotalLY = array(0);
	$Level = 0;
	$ParentGroups = array();
	$ParentGroups[$Level] = '';

	$k = 0; // Row colour counter.
	$DrawTotalLine = '<tr>
		<td colspan="2">&nbsp;</td>
		<td><hr /></td>
		<td><hr /></td>
		<td><hr /></td>
		<td><hr /></td>
	</tr>';

	while ($MyRow = DB_fetch_array($AccountsResult)) {
		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
				while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
					if ($_POST['Detail'] == 'Detailed') {
						echo $DrawTotalLine;
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' *' . _('total');
					} else {
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
					}
					echo '<tr>
							<td class="text" colspan="2">', $ActGrpLabel, '</td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
						</tr>';
					$GrpTotal[$Level] = 0;
					$GrpTotalLY[$Level] = 0;
					$ParentGroups[$Level] = '';
					$Level--;
				} // End while.
				//still need to print out the old group totals
				if ($_POST['Detail'] == 'Detailed') {
					echo $DrawTotalLine;
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}

				// --->
				if ($Section == 1) { // Income
					echo '<tr>
						<td class="text" colspan="2">', $ActGrpLabel, '</td>
						<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
					</tr>';
				} else { // Costs
					// <---
					echo '<tr>
						<td class="text" colspan="2">', $ActGrpLabel, '</td>
						<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
					</tr>';
					// --->
					
				}
				// <---
				$GrpTotalLY[$Level] = 0;
				$GrpTotal[$Level] = 0;
				$ParentGroups[$Level] = '';
			}
		}

		if ($MyRow['sectioninaccounts'] != $Section) {

			if ($SectionTotal + $SectionTotalLY != 0) {

				if ($Section == 1) { // Income.
					echo $DrawTotalLine;
					echo '<tr>
							<td class="text" colspan="2"><h2>', $Sections[$Section], '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotal + $SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', RelativeChange(-$SectionTotal, -$SectionTotalLY), '</h2></td>
						</tr>';
					$GPIncome = $SectionTotal;
					$GPIncomeLY = $SectionTotalLY;
				} else {
					echo '<tr>
							<td class="text" colspan="2"><h2>', $Sections[$Section], '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$SectionTotal + $SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', RelativeChange(-$SectionTotal, -$SectionTotalLY), '</h2></td>
						</tr>';
				}

				if ($Section == 2) { // Cost of Sales - need sub total for Gross Profit.
					echo $DrawTotalLine;
					echo '<tr>
							<td class="text" colspan="2"><h2>', _('Gross Profit'), '</h2></td>
							<td class="number"><h2>', locale_number_format(-($GPIncome + $SectionTotal), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-($GPIncomeLY + $SectionTotalLY), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-($GPIncome + $SectionTotal) + ($GPIncomeLY + $SectionTotalLY), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', RelativeChange(-($GPIncome + $SectionTotal), -($GPIncomeLY + $SectionTotalLY)), '</h2></td>
						</tr>';
				}

				if (($Section != 1) and ($Section != 2)) {
					echo $DrawTotalLine;
					echo '<tr>
							<td class="text" colspan="2"><h2>', _('Earnings after'), ' ', $Sections[$Section], '</h2></td>
							<td class="number"><h2>', locale_number_format(-$PeriodTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$PeriodTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', locale_number_format(-$PeriodTotal + $PeriodTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
							<td class="number"><h2>', RelativeChange(-$PeriodTotal, -$PeriodTotalLY), '</h2></td>
						</tr>';
					echo $DrawTotalLine;
				}
			}

			$Section = $MyRow['sectioninaccounts'];
			$SectionTotal = 0;
			$SectionTotalLY = 0;

			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
						<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
					</tr>';
			}
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') { // Adding another level of nesting
				$Level++;
			}
			$ActGrp = $MyRow['groupname'];
			$ParentGroups[$Level] = $MyRow['groupname'];
			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
						<td colspan="6"><h2>', $MyRow['groupname'], '</h2></td>
					</tr>';
			}
		}

		// Set totals for account, groups, section and period:
		$FirstPeriodSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<'" . $_POST['PeriodFrom'] . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$FirstPeriodResult = DB_query($FirstPeriodSQL);
		$FirstPeriodRow = DB_fetch_array($FirstPeriodResult);

		$LastPeriodSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<'" . $_POST['PeriodTo'] . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$LastPeriodResult = DB_query($LastPeriodSQL);
		$LastPeriodRow = DB_fetch_array($LastPeriodResult);

		$LYFirstPeriodSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<'" . ($_POST['PeriodFrom'] - 12) . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$LYFirstPeriodResult = DB_query($LYFirstPeriodSQL);
		$LYFirstPeriodRow = DB_fetch_array($LYFirstPeriodResult);

		$LYLastPeriodSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<'" . ($_POST['PeriodTo'] - 12) . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$LYLastPeriodResult = DB_query($LYLastPeriodSQL);
		$LYLastPeriodRow = DB_fetch_array($LYLastPeriodResult);

		$AccountTotal = $LastPeriodRow['accounttotal'] - $FirstPeriodRow['accounttotal'];
		$AccountTotalLY = $LYLastPeriodRow['accounttotal'] - $LYFirstPeriodRow['accounttotal'];
		for ($i = 0;$i <= $Level;$i++) {
			if (!isset($GrpTotalLY[$i])) {
				$GrpTotalLY[$i] = 0;
			}
			$GrpTotalLY[$i]+= $AccountTotalLY;
			if (!isset($GrpTotal[$i])) {
				$GrpTotal[$i] = 0;
			}
			$GrpTotal[$i]+= $AccountTotal;
		}
		$SectionTotal+= $AccountTotal;
		$SectionTotalLY+= $AccountTotalLY;
		$PeriodTotal+= $AccountTotal;
		$PeriodTotalLY+= $AccountTotalLY;

		if ($_POST['Detail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalances']) or (!isset($_POST['ShowZeroBalances']) and ($AccountTotal <> 0 or $AccountTotalLY <> 0))) {
				echo '<tr class="striped_row">';
				echo '<td class="text"><a href="', $RootPath, '/GLAccountInquiry.php?Period=', $_POST['PeriodTo'], '&amp;Account=', $MyRow['accountcode'], '&amp;Show=Yes">', $MyRow['accountcode'], '</a></td>';
				// --->
				if ($Section == 1) {
					echo '	<td class="text">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</td>
							<td class="number">', locale_number_format(-$AccountTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$AccountTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$AccountTotal + $AccountTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$AccountTotal, -$AccountTotalLY), '</td>
						</tr>';
				} else {
					// <---
					echo '	<td class="text">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</td>
							<td class="number">', locale_number_format(-$AccountTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$AccountTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$AccountTotal + $AccountTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$AccountTotal, -$AccountTotalLY), '</td>
						</tr>';
				}
			}
		}
	} // End of loop.
	if ($MyRow['groupname'] != $ActGrp) {
		if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
			while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
				if ($_POST['Detail'] == 'Detailed') {
					echo $DrawTotalLine;
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}
				// --->
				if ($Section == 1) { // Income.
					echo '<tr>
							<td colspan="2"><h3>', $ActGrpLabel, '</h3></td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
						</tr>';
				} else { // Costs.
					// <---
					echo '<tr>
							<td colspan="2"><h3>', $ActGrpLabel, '</h3></td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
						</tr>';
				}
				$GrpTotal[$Level] = 0;
				$GrpTotalLY[$Level] = 0;
				$ParentGroups[$Level] = '';
				$Level--;
			} // End while.
			//still need to print out the old group totals
			if ($_POST['Detail'] == 'Detailed') {
				echo $DrawTotalLine;
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
			} else {
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
			}
			echo '<tr>
					<td colspan="2"><h3>', $ActGrpLabel, '</h3></td>
					<td class="number">', locale_number_format(-$GrpTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', locale_number_format(-$GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', locale_number_format(-$GrpTotal[$Level] + $GrpTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]), '</td>
				</tr>';
			$GrpTotal[$Level] = 0;
			$GrpTotalLY[$Level] = 0;
			$ParentGroups[$Level] = '';
		}
	}

	if ($MyRow['sectioninaccounts'] != $Section) {

		if ($Section == 1) { // Income.
			echo $DrawTotalLine, '<tr>
					<td colspan="2"><h2>', $Sections[$Section], '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotal + $SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', RelativeChange(-$SectionTotal, -$SectionTotalLY), '</h2></td>
				</tr>';
			$GPIncome = $SectionTotal;
			$GPIncomeLY = $SectionTotalLY;
		} else {
			echo $DrawTotalLine, '<tr>
					<td colspan="2"><h2>', $Sections[$Section], '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-$SectionTotal + $SectionTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', RelativeChange(-$SectionTotal, -$SectionTotalLY), '</h2></td>
				</tr>';
		}
		if ($Section == 2) { // Cost of Sales - need sub total for Gross Profit.
			echo $DrawTotalLine, '<tr>
					<td colspan="2"><h2>', _('Gross Profit'), '</h2></td>
					<td class="number"><h2>', locale_number_format(-($GPIncome + $SectionTotal), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-($GPIncomeLY + $SectionTotalLY), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', locale_number_format(-($GPIncome + $SectionTotal) + ($GPIncomeLY + $SectionTotalLY), $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
					<td class="number"><h2>', RelativeChange(-($GPIncome + $SectionTotal), -($GPIncomeLY + $SectionTotalLY)), '</h2></td>
				</tr>';
		}
		$Section = $MyRow['sectioninaccounts'];
		$SectionTotal = 0;
		$SectionTotalLY = 0;

		if ($_POST['Detail'] == 'Detailed' and isset($Sections[$MyRow['sectioninaccounts']])) {
			echo '<tr>
					<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
				</tr>';
		}
	}

	echo $DrawTotalLine;
	echo '<tr>
			<td colspan="2"><h2>', _('Net Profit'), '</h2></td>
			<td class="number"><h2>', locale_number_format(-$PeriodTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format(-$PeriodTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format(-$PeriodTotal + $PeriodTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', RelativeChange(-$PeriodTotal, -$PeriodTotalLY), '</h2></td>
		</tr>';
	echo $DrawTotalLine;
	echo '</tbody>', // See comment at the begin of the table.
	'</table>
		</div>'; // Close div id="Report".
	echo '<br />';
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">
			<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
			<input type="hidden" name="PeriodFrom" value="', $_POST['PeriodFrom'], '" />
			<input type="hidden" name="PeriodTo" value="', $_POST['PeriodTo'], '" />
			<div class="centre noPrint">
				<input name="NewReport" type="submit" value="', _('Select A Different Period'), '" />
			</div>';
}
echo '</form>';
include ('includes/footer.php');
?>