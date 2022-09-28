<?php
/* AnalysisHorizontalPosition.php
Shows the horizontal analysis of the statement of financial position.
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

$Title = _('Horizontal Analysis of Statement of Financial Position');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AnalysisHorizontalPosition';
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
if (!isset($_POST['BalancePeriodEnd']) or isset($_POST['NewReport'])) {

	/*Show a form to allow input of criteria for TB to show */
	echo '<p class="page_title_text">
			<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print Horizontal Analysis of Statement of Financial Position'), '" /> ', // Icon title.
	_('Horizontal Analysis of Statement of Financial Position'), '</p>'; // Page title.
	echo '<div class="page_help_text">', _('Horizontal analysis (also known as trend analysis) is a financial statement analysis technique that shows changes in the amounts of corresponding financial statement items over a period of time. It is a useful tool to evaluate trend situations.'), '<br />', _('The statements for two periods are used in horizontal analysis. The earliest period is used as the base period. The items on the later statement are compared with items on the statement of the base period. The changes are shown both in currency (absolute change) and percentage (relative change).'), '<br />', _('KwaMoja is an accrual based system (not a cash based system).  Accrual systems include items when they are invoiced to the customer, and when expenses are owed based on the supplier invoice date.'), '</div>';
	// Show a form to allow input of criteria for the report to show:
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<fieldset>
			<legend>', _('Select Report Criteria'), '</legend>
			<field>
				<label for="BalancePeriodEnd">', _('Select the balance date'), ':</label>
				<select required="required" autofocus="autofocus" name="BalancePeriodEnd">';

	$PeriodNo = GetPeriod(Date($_SESSION['DefaultDateFormat']));
	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $PeriodNo . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$LastDateInPeriod = $MyRow[0];

	$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if ($MyRow['periodno'] == $PeriodNo) {
			echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
		} else {
			echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
		}
	}

	echo '</select>
		<fieldhelp>', _('Choose the period on which to base the report.'), '</fieldhelp>
	</field>';

	echo '<field>
			<label for="Detail">', _('Detail or summary'), ':</label>
			<select name="Detail" required="required">
				<option value="Summary">', _('Summary'), '</option>
				<option selected="selected" value="Detailed">', _('All Accounts'), '</option>
			</select>
			<fieldhelp>', _('Selecting Summary will show on the totals at the account group level'), '</fieldhelp>
		</field>
		<field>
			<label for="ShowZeroBalances">', _('Show all accounts including zero balances'), '</label>
			<input name="ShowZeroBalances" type="checkbox" />
			<fieldhelp>', _('Check this box to display all accounts including those accounts with no balance'), '</fieldhelp>
		</field>
	</fieldset>';

	echo '<div class="centre noPrint">
			<input name="ShowBalanceSheet" type="submit" value="', _('Show on Screen (HTML)'), '" />
		</div>';

} else {

	$RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['BalancePeriodEnd'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$BalanceDate = ConvertSQLDate($MyRow[0]);

	// Page title as IAS 1, numerals 10 and 51:
	include_once ('includes/CurrenciesArray.php'); // Array to retrieve currency name.
	echo '<div id="Report">
			<p class="page_title_text">
				<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" title="', _('Horizontal Analysis of Statement of Financial Position'), '" /> ', // Icon title.
	_('Horizontal Analysis of Statement of Financial Position'), '<br />', // Page title, reporting statement.
	stripslashes($_SESSION['CompanyRecord']['coyname']), '<br />', // Page title, reporting entity.
	_('as at'), ' ', $BalanceDate, '<br />', // Page title, reporting period.
	_('All amounts stated in'), ': ', _($CurrencyName[$_SESSION['CompanyRecord']['currencydefault']]), '
			</p>'; // Page title, reporting presentation currency and level of rounding used.
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
				<td class="text" colspan="6">', // Prints an explanation of signs in absolute and relative changes:
	'<br /><b>', _('Notes'), ':</b><br />', _('Absolute change signs: a positive number indicates a source of funds; a negative number indicates an application of funds.'), '<br />', _('Relative change signs: a positive number indicates an increase in the amount of that account; a negative number indicates a decrease in the amount of that account.'), '<br />
				</td>
			</tr>
		</tfoot>
		<tbody>'; // thead and tfoot used in conjunction with tbody enable scrolling of the table body independently of the header and footer. Also, when printing a large table that spans multiple pages, these elements can enable the table header to be printed at the top of each page.
	/* Get the retained earnings amount */
	$ThisYearRetainedEarningsSQL = "SELECT ROUND(SUM(amount),3) AS retainedearnings
									FROM gltotals
									INNER JOIN chartmaster
										ON gltotals.account=chartmaster.accountcode
									INNER JOIN accountgroups
										ON chartmaster.groupcode=accountgroups.groupcode
										AND accountgroups.language=chartmaster.language
									WHERE period<='" . $_POST['BalancePeriodEnd'] . "'
										AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
										AND pandl=1";
	$ThisYearRetainedEarningsResult = DB_query($ThisYearRetainedEarningsSQL);
	$ThisYearRetainedEarningsRow = DB_fetch_array($ThisYearRetainedEarningsResult);

	$LastYearRetainedEarningsSQL = "SELECT ROUND(SUM(amount),3) AS retainedearnings
									FROM gltotals
									INNER JOIN chartmaster
										ON gltotals.account=chartmaster.accountcode
									INNER JOIN accountgroups
										ON chartmaster.groupcode=accountgroups.groupcode
										AND accountgroups.language=chartmaster.language
									WHERE period<='" . ($_POST['BalancePeriodEnd'] - 12) . "'
										AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
										AND pandl=1";
	$LastYearRetainedEarningsResult = DB_query($LastYearRetainedEarningsSQL);
	$LastYearRetainedEarningsRow = DB_fetch_array($LastYearRetainedEarningsResult);

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
					AND pandl=0
				ORDER BY sequenceintb,
						groupcode,
						accountcode";

	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'));

	$CheckTotal = 0;
	$CheckTotalLY = 0;

	$Section = '';
	$SectionBalance = 0;
	$SectionBalanceLY = 0;

	$ActGrp = '';
	$Level = 0;
	$ParentGroups = array();
	$ParentGroups[$Level] = '';
	$GroupTotal = array(0);
	$GroupTotalLY = array(0);

	$k = 0; // Row colour counter.
	$DrawTotalLine = '<tr>
						<td colspan="2">&nbsp;</td>
						<td><hr /></td>
						<td><hr /></td>
						<td><hr /></td>
						<td><hr /></td>
					</tr>';

	while ($MyRow = DB_fetch_array($AccountsResult)) {
		$ThisYearSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<='" . $_POST['BalancePeriodEnd'] . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$ThisYearResult = DB_query($ThisYearSQL);
		$ThisYearRow = DB_fetch_array($ThisYearResult);

		$LastYearSQL = "SELECT account,
					SUM(amount) AS accounttotal
				FROM gltotals
				WHERE period<='" . ($_POST['BalancePeriodEnd'] - 12) . "'
					AND account='" . $MyRow['accountcode'] . "'";
		$LastYearResult = DB_query($LastYearSQL);
		$LastYearRow = DB_fetch_array($LastYearResult);

		$AccountBalance = $ThisYearRow['accounttotal'];
		$AccountBalanceLY = $LastYearRow['accounttotal'];

		if ($MyRow['accountcode'] == $RetainedEarningsAct) {
			$AccountBalance+= $ThisYearRetainedEarningsRow['retainedearnings'];
			$AccountBalanceLY+= $LastYearRetainedEarningsRow['retainedearnings'];
		}

		if ($MyRow['groupname'] != $ActGrp and $ActGrp != '') {
			if ($MyRow['parentgroupname'] != $ActGrp) {
				while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
					if ($_POST['Detail'] == 'Detailed') {
						echo $DrawTotalLine;
					}
					echo '<tr>
							<td colspan="2">', $ParentGroups[$Level], '</td>
							<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format($GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', locale_number_format(-$GroupTotal[$Level] + $GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td class="number">', RelativeChange(-$GroupTotal[$Level], -$GroupTotalLY[$Level]), '</td>
						</tr>';
					$GroupTotal[$Level] = 0;
					$GroupTotalLY[$Level] = 0;
					$ParentGroups[$Level] = '';
					$Level--;
				}
				if ($_POST['Detail'] == 'Detailed') {
					echo $DrawTotalLine;
				}
				echo '<tr>
						<td class="text" colspan="2">', $ParentGroups[$Level], '</td>
						<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$GroupTotal[$Level] + $GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeChange(-$GroupTotal[$Level], -$GroupTotalLY[$Level]), '</td>
					</tr>';
				$GroupTotal[$Level] = 0;
				$GroupTotalLY[$Level] = 0;
				$ParentGroups[$Level] = '';
			}
		}
		if ($MyRow['sectioninaccounts'] != $Section) {
			if ($Section != '') {
				echo $DrawTotalLine;
				echo '<tr>
						<td class="text" colspan="2"><h2>', $Sections[$Section], '</h2></td>
						<td class="number"><h2>', locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', locale_number_format(-$SectionBalance + $SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
						<td class="number"><h2>', RelativeChange(-$SectionBalance, -$SectionBalanceLY), '</h2></td>
					</tr>';
			}
			$SectionBalance = 0;
			$SectionBalanceLY = 0;
			$Section = $MyRow['sectioninaccounts'];
			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
						<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
					</tr>';
			}
		}

		if ($MyRow['groupname'] != $ActGrp) {

			if ($ActGrp != '' and $MyRow['parentgroupname'] == $ActGrp) {
				$Level++;
			}

			if ($_POST['Detail'] == 'Detailed') {
				$ActGrp = $MyRow['groupname'];
				echo '<tr>
						<td colspan="6"><h3>', $MyRow['groupname'], '</h3></td>
					</tr>';
			}
			$GroupTotal[$Level] = 0;
			$GroupTotalLY[$Level] = 0;
			$ActGrp = $MyRow['groupname'];
			$ParentGroups[$Level] = $MyRow['groupname'];
		}
		$SectionBalance+= $AccountBalance;
		$SectionBalanceLY+= $AccountBalanceLY;

		for ($i = 0;$i <= $Level;$i++) {
			$GroupTotalLY[$i]+= $AccountBalanceLY;
			$GroupTotal[$i]+= $AccountBalance;
		}
		$CheckTotal+= $AccountBalance;
		$CheckTotalLY+= $AccountBalanceLY;

		if ($_POST['Detail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalances']) or (!isset($_POST['ShowZeroBalances']) and (round($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) <> 0 or round($AccountBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']) <> 0))) {
				echo '<tr class="striped_row">
						<td class="text"><a href="', $RootPath, '/GLAccountInquiry.php?Period=', $_POST['BalancePeriodEnd'], '&amp;Account=', $MyRow['accountcode'], '">', $MyRow['accountcode'], '</a></td>
						<td class="text">', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</td>
						<td class="number">', locale_number_format($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format($AccountBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', locale_number_format(-$AccountBalance + $AccountBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="number">', RelativeChange(-$AccountBalance, -$AccountBalanceLY), '</td>
					</tr>';
			}
		}
	} // End of loop.
	while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
		if ($_POST['Detail'] == 'Detailed') {
			echo $DrawTotalLine;
		}
		echo '<tr>
				<td colspan="2">', $ParentGroups[$Level], '</td>
				<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format(-$GroupTotal[$Level] + $GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', RelativeChange(-$GroupTotal[$Level], -$GroupTotalLY[$Level]), '</td>
			</tr>';
		$Level--;
	}
	if ($_POST['Detail'] == 'Detailed') {
		echo $DrawTotalLine;
	}
	echo '<tr>
			<td colspan="2">', $ParentGroups[$Level], '</td>
			<td class="number">', locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format($GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', locale_number_format(-$GroupTotal[$Level] + $GroupTotalLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			<td class="number">', RelativeChange(-$GroupTotal[$Level], -$GroupTotalLY[$Level]), '</td>
		</tr>';
	echo $DrawTotalLine;
	echo '<tr>
			<td colspan="2"><h2>', $Sections[$Section], '</h2></td>
			<td class="number"><h2>', locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format(-$SectionBalance + $SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', RelativeChange(-$SectionBalance, -$SectionBalanceLY), '</h2></td>
		</tr>';

	$Section = $MyRow['sectioninaccounts'];

	if (isset($MyRow['sectioninaccounts']) and $_POST['Detail'] == 'Detailed') {
		echo '<tr>
				<td colspan="6"><h2>', $Sections[$MyRow['sectioninaccounts']], '</h2></td>
			</tr>';
	}
	echo $DrawTotalLine;
	echo '<tr>
			<td colspan="2"><h2>', _('Check Total'), '</h2></td>
			<td class="number"><h2>', locale_number_format($CheckTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format($CheckTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', locale_number_format(-$CheckTotal + $CheckTotalLY, $_SESSION['CompanyRecord']['decimalplaces']), '</h2></td>
			<td class="number"><h2>', RelativeChange(-$CheckTotal, -$CheckTotalLY), '</h2></td>
		</tr>';
	echo $DrawTotalLine;
	echo '</tbody>', // See comment at the begin of the table.
	'</table>
		</div>'; // Close div id="Report".
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">
			<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
			<input type="hidden" name="BalancePeriodEnd" value="', $_POST['BalancePeriodEnd'], '" />
			<div class="centre noPrint">
				<input name="NewReport" type="submit" value="', _('Select A Different Period'), '" />
			</div>';
}
echo '</form>';
include ('includes/footer.php');
?>