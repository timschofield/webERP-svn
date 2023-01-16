<?php
/* Shows the general ledger transactions for a specified account over a specified range of periods */
include ('includes/session.php');
$Title = _('General Ledger Account Inquiry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountInquiry';
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/transactions.png" title="', _('General Ledger Account Inquiry'), '" alt="', _('General Ledger Account Inquiry'), '" />', ' ', _('General Ledger Account Inquiry'), '
	</p>';

if (isset($_POST['Select'])) {
	$_POST['Account'] = $_POST['Select'];
}
if (isset($_POST['Account'])) {
	$SelectedAccount = $_POST['Account'];
} elseif (isset($_GET['Account'])) {
	$SelectedAccount = $_GET['Account'];
	$_POST['Account'] = $_GET['Account'];
}

if (isset($_POST['PeriodTo'])) {
	$SelectedPeriodTo = $_POST['PeriodTo'];
} elseif (isset($_GET['PeriodTo'])) {
	$SelectedPeriodTo = $_GET['PeriodTo'];
}

if (isset($_POST['PeriodFrom'])) {
	$SelectedPeriodFrom = $_POST['PeriodFrom'];
} elseif (isset($_GET['PeriodFrom'])) {
	$SelectedPeriodFrom = $_GET['PeriodFrom'];
}

if (isset($_POST['Period'])) {
	$SelectedPeriod = $_POST['Period'];
}

if (isset($_GET['Show'])) {
	$_POST['Show'] = $_GET['Show'];
}

if (!isset($_POST['tag'])) {
	$_POST['tag'] = 0;
}

if (isset($SelectedAccount) and $_SESSION['CompanyRecord']['retainedearnings'] == $SelectedAccount) {
	prnMsg(_('The retained earnings account is managed separately by the system, and therefore cannot be inquired upon. See manual for details'), 'info');
	echo '<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Select another account'), '</a>';
	include ('includes/footer.php');
	exit;
}

echo '<div class="page_help_text noPrint">', _('Use the keyboard Shift key to select multiple periods'), '</div>';

echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

/* Get the start and periods, depending on how this script was called*/
if (isset($SelectedPeriod)) { //If it was called from itself (in other words an inquiry was run and we wish to leave the periods selected unchanged
	$FirstPeriodSelected = min($SelectedPeriod);
	$LastPeriodSelected = max($SelectedPeriod);
} elseif (isset($_GET['PeriodFrom'])) { //If it was called from the Trial Balance/P&L or Balance sheet
	$FirstPeriodSelected = $_GET['PeriodFrom'];
	$LastPeriodSelected = $_GET['PeriodTo'];
	$SelectedPeriod[0] = $_GET['PeriodFrom'];
	$SelectedPeriod[1] = $_GET['PeriodTo'];
} else { // Otherwise just highlight the current period
	$FirstPeriodSelected = GetPeriod(date($_SESSION['DefaultDateFormat']));
	$LastPeriodSelected = GetPeriod(date($_SESSION['DefaultDateFormat']));
}

/*Dates in SQL format for the last day of last month*/
$DefaultPeriodDate = Date('Y-m-d', Mktime(0, 0, 0, Date('m'), 0, Date('Y')));

/*Show a form to allow input of criteria for TB to show */

echo '<fieldset>
		<legend>', _('Inquiry Selection Criteria'), '</legend>
		<field>
			<label for="Account">', _('Account'), ':</label>';
GLSelect(2, 'Account');
echo '<fieldhelp>', _('Select a General Ledger account to report on.'), '</fieldhelp>
	</field>';

//Select the tag
$SQL = "SELECT tagref,
			tagdescription
		FROM tags
		ORDER BY tagref";
$Result = DB_query($SQL);

echo '<field>
		<label for="tag">', _('Select Tag'), ':</label>
		<select name="tag">';
echo '<option value="-1">', _('All tags'), '</option>';
echo '<option value="0">0 - ', _('No tag selected'), '</option>';
while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_POST['tag']) and $_POST['tag'] == $MyRow['tagref']) {
		echo '<option selected="selected" value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
	} else {
		echo '<option value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select a tag to filter the report on.'), '</fieldhelp>
</field>';

$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
$Periods = DB_query($SQL);
$id = 0;
echo '<field>
		<label for="Period">', _('For Period range'), ':</label>
		<select name="Period[]" size="12" multiple="multiple">';
while ($MyRow = DB_fetch_array($Periods)) {
	if (isset($FirstPeriodSelected) and $MyRow['periodno'] >= $FirstPeriodSelected and $MyRow['periodno'] <= $LastPeriodSelected) {
		echo '<option selected="selected" value="', $MyRow['periodno'], '">', _(MonthAndYearFromSQLDate($MyRow['lastdate_in_period'])), '</option>';
		$id++;
	} else {
		echo '<option value="', $MyRow['periodno'], '">', _(MonthAndYearFromSQLDate($MyRow['lastdate_in_period'])), '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select one or more financial periods to report on.'), '</fieldhelp>
</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="Show" value="', _('Show Account Transactions'), '" />
	</div>
</form>';

/* End of the Form  rest of script is what happens if the show button is hit*/

if (isset($_POST['Show'])) {

	if (!isset($SelectedPeriod)) {
		prnMsg(_('A period or range of periods must be selected from the list box'), 'info');
		include ('includes/footer.php');
		exit;
	}
	if ($_POST['tag'] == - 1) {
		$_POST['tag'] = '%%';
	}
	/*Is the account a balance sheet or a profit and loss account */
	$Result = DB_query("SELECT pandl
				FROM accountgroups
				INNER JOIN chartmaster
					ON accountgroups.groupcode=chartmaster.groupcode
					AND accountgroups.language=chartmaster.language
				WHERE chartmaster.accountcode='" . $SelectedAccount . "'
					AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'");
	$PandLRow = DB_fetch_row($Result);
	if ($PandLRow[0] == 1) {
		$PandLAccount = True;
	} else {
		$PandLAccount = False;
		/*its a balance sheet account */
	}

	$SQL = "SELECT  gltrans.counterindex,
					type,
					typename,
					gltrans.typeno,
					trandate,
					narrative,
					chequeno,
					amount,
					periodno
				FROM gltrans
				INNER JOIN systypes
					ON systypes.typeid=gltrans.type
				INNER JOIN gltags
					ON gltags.counterindex=gltrans.counterindex
				WHERE gltrans.account = '" . $SelectedAccount . "'
					AND periodno>='" . $FirstPeriodSelected . "'
					AND periodno<='" . $LastPeriodSelected . "'
					AND gltags.tagref LIKE '" . $_POST['tag'] . "'
				ORDER BY periodno,
						gltrans.trandate,
						counterindex";

	$NameSQL = "SELECT accountname
					FROM chartmaster
					WHERE accountcode='" . $SelectedAccount . "'
						AND language='" . $_SESSION['ChartLanguage'] . "'";
	$NameResult = DB_query($NameSQL);
	$NameRow = DB_fetch_array($NameResult);
	$SelectedAccountName = $NameRow['accountname'];
	$ErrMsg = _('The transactions for account') . ' ' . $SelectedAccount . ' ' . _('could not be retrieved because');
	$TransResult = DB_query($SQL, $ErrMsg);

	echo '<table summary="', _('General Ledger account inquiry details'), '">
			<thead>
				<tr>
					<th colspan="9">
						<b>', _('Transactions for account'), ' ', $SelectedAccount, ' - ', $SelectedAccountName, '</b>
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print'), '" alt="', _('Print'), '" onclick="window.print();" />
					</th>
				</tr>
				<tr>
					<th>', _('Type'), '</th>
					<th>', _('Trans no'), '</th>
					<th>', _('Cheque'), '</th>
					<th>', _('Date'), '</th>
					<th>', _('Narrative'), '</th>
					<th>', _('Tag'), '</th>
					<th>', _('Debit'), '</th>
					<th>', _('Credit'), '</th>
					<th>', _('Balance'), '</th>
				</tr>
			</thead>';

	$PeriodTotal = 0;
	$PeriodNo = - 9999;
	$j = 1;

	$IntegrityReport = '';
	while ($MyRow = DB_fetch_array($TransResult)) {
		if ($MyRow['periodno'] != $PeriodNo) {
			if ($PeriodNo == - 9999) {

				$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . ($FirstPeriodSelected - 1) . "'";
				$PeriodResult = DB_query($PeriodSQL);
				$PeriodRow = DB_fetch_array($PeriodResult);
				$PreviousPeriodName = _(MonthAndYearFromSQLDate($PeriodRow['lastdate_in_period']));

				$BFSQL = "SELECT SUM(amount) AS bftotal FROM gltotals WHERE account='" . $SelectedAccount . "' and period<'" . $FirstPeriodSelected . "'";
				$BFResult = DB_query($BFSQL);
				$BFRow = DB_fetch_array($BFResult);

				if ($PandLAccount == True) {
					$BFRow['bftotal'] = 0;
				}

				if ($BFRow['bftotal'] >= 0) {
					echo '<tr class="total_row">
							<td colspan="6">', _('Balance brought forward from'), ' ', $PreviousPeriodName, '</td>
							<td class="number">', locale_number_format($BFRow['bftotal'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td></td>
							<td></td>
						</tr>';
				} else {
					echo '<tr class="total_row">
							<td colspan="6">', _('Balance brought forward from'), ' ', $PreviousPeriodName, '</td>
							<td></td>
							<td class="number">', locale_number_format(-$BFRow['bftotal'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td></td>
						</tr>';
				}
				$RunningTotal = $BFRow['bftotal'];
				$PeriodTotalCredit = 0;
				$PeriodTotalDebit = 0;
			}
			if ($PeriodNo != - 9999) { //ie its not the first time around
				/*Get the ChartDetails balance b/fwd and the actual movement in the account for the period as recorded in the chart details - need to ensure integrity of transactions to the chart detail movements. Also, for a balance sheet account it is the balance carried forward that is important, not just the transactions*/

				if ($PeriodNo != - 9999) {
					$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $PeriodNo . "'";
					$PeriodResult = DB_query($PeriodSQL);
					$PeriodRow = DB_fetch_array($PeriodResult);
					echo '<tr class="total_row">
							<td colspan="6"><b>', _('Totals for period ending'), ' ', ConvertSQLDate($PeriodRow['lastdate_in_period']), '</b></td>
							<td class="number"><b>', locale_number_format($PeriodTotalDebit, $_SESSION['CompanyRecord']['decimalplaces']), '</b></td>
							<td class="number"><b>', locale_number_format($PeriodTotalCredit, $_SESSION['CompanyRecord']['decimalplaces']), '</b></td>
							<td></td>
						</tr>';
					$PeriodTotalCredit = 0;
					$PeriodTotalDebit = 0;
				}

				$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $PeriodNo . "'";
				$PeriodResult = DB_query($PeriodSQL);
				$PeriodRow = DB_fetch_array($PeriodResult);
				$PeriodName = _(MonthAndYearFromSQLDate($PeriodRow['lastdate_in_period']));

				$BFSQL = "SELECT SUM(amount) AS bftotal FROM gltotals WHERE account='" . $SelectedAccount . "' and period<='" . $PeriodNo . "'";
				$BFResult = DB_query($BFSQL);
				$BFRow = DB_fetch_array($BFResult);

				if ($BFRow['bftotal'] >= 0) {
					echo '<tr class="total_row">
							<td colspan="6">', _('Balance brought forward from'), ' ', $PeriodName, '</td>
							<td class="number">', locale_number_format($RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td></td>
							<td></td>
						</tr>';
				} else {
					echo '<tr class="total_row">
							<td colspan="6">', _('Balance brought forward from'), ' ', $PeriodName, '</td>
							<td></td>
							<td class="number">', locale_number_format(-$RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
							<td></td>
						</tr>';
				}
			}
			$PeriodNo = $MyRow['periodno'];
			$PeriodTotal = 0;
		}

		$RunningTotal+= $MyRow['amount'];
		$PeriodTotal+= $MyRow['amount'];

		if ($MyRow['amount'] >= 0) {
			$DebitAmount = locale_number_format($MyRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
			$CreditAmount = '';
			$PeriodTotalDebit+= $MyRow['amount'];
		} else {
			$CreditAmount = locale_number_format(-$MyRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
			$DebitAmount = '';
			$PeriodTotalCredit-= $MyRow['amount'];
		}

		$FormatedTranDate = ConvertSQLDate($MyRow['trandate']);
		$URL_to_TransDetail = $RootPath . '/GLTransInquiry.php?TypeID=' . urlencode($MyRow['type']) . '&amp;TransNo=' . urlencode($MyRow['typeno']);

		$TagDescriptions = '';
		$TagListSQL = "SELECT tagref FROM gltags WHERE counterindex='" . $MyRow['counterindex'] . "'";
		$TagListResult = DB_query($TagListSQL);
		if (DB_num_rows($TagListResult) == 0) {
			$TagDescriptions.= '0 - ' . _('No tag selected') . '<br />';
		}
		while ($TagListRow = DB_fetch_array($TagListResult)) {
			$TagSQL = "SELECT tagdescription FROM tags WHERE tagref='" . $TagListRow['tagref'] . "'";
			$TagResult = DB_query($TagSQL);
			$TagRow = DB_fetch_array($TagResult);
			if ($TagListRow['tagref'] == 0) {
				$TagRow['tagdescription'] = _('No tag selected');
			}
			$TagDescriptions.= $TagListRow['tagref'] . ' - ' . $TagRow['tagdescription'] . '<br />';
		}

		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td class="number"><a href="', $URL_to_TransDetail, '">', $MyRow['typeno'], '</a></td>
				<td>', $MyRow['chequeno'], '</td>
				<td>', $FormatedTranDate, '</td>
				<td>', $MyRow['narrative'], '</td>
				<td>', $TagDescriptions, '</td>
				<td class="number">', $DebitAmount, '</td>
				<td class="number">', $CreditAmount, '</td>
				<td class="number"><b>', locale_number_format($RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']), '</b></td>
			</tr>';

	}
	if ($PeriodNo != - 9999) {
		$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $PeriodNo . "'";
		$PeriodResult = DB_query($PeriodSQL);
		$PeriodRow = DB_fetch_array($PeriodResult);
		echo '<tr class="total_row">
				<td colspan="6"><b>', _('Totals for period ending'), ' ', ConvertSQLDate($PeriodRow['lastdate_in_period']), '</b></td>
				<td class="number"><b>', locale_number_format($PeriodTotalDebit, $_SESSION['CompanyRecord']['decimalplaces']), '</b></td>
				<td class="number"><b>', locale_number_format($PeriodTotalCredit, $_SESSION['CompanyRecord']['decimalplaces']), '</b></td>
				<td></td>
			</tr>';
	}

	echo '<tr class="total_row">
			<td colspan="6"><b>';
	if ($PandLAccount == True) {
		echo _('Total Movement for selected periods');
	} else {
		/*its a balance sheet account*/
		echo _('Balance C/Fwd');
	}
	echo '</b></td>';

	if ($RunningTotal > 0) {
		echo '<td class="number">
				<b>', locale_number_format(($RunningTotal), $_SESSION['CompanyRecord']['decimalplaces']), '</b>
			</td>
			<td></td>
		</tr>';
	} else {
		echo '<td class="number" colspan="2">
				<b>', locale_number_format((-$RunningTotal), $_SESSION['CompanyRecord']['decimalplaces']), '</b>
			</td>
			<td></td>
		</tr>';
	}
	echo '</table>';
}
/* end of if Show button hit */

include ('includes/footer.php');
?>