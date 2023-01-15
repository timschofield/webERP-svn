<?php
include ('includes/session.php');
include ('includes/SQL_CommonFunctions.inc');

$Title = _('Enter GL Budget amounts');

if (isset($_POST['SelectedBudget'])) {
	$SelectedBudget = $_POST['SelectedBudget'];
} elseif (isset($_GET['SelectedBudget'])) {
	$SelectedBudget = $_GET['SelectedBudget'];
}

$ViewTopic = 'GeneralLedger';
$BookMark = 'GLBudgets';
include ('includes/header.php');

if (isset($_POST['Update'])) {
	$UpdateSQL = array();
	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 6) == 'Period') {
			$Period = mb_substr($Key, 6);
			$Amount = $Value;
			$UpdateSQL[] = "UPDATE glbudgetdetails SET amount='" . $Amount . "'
							WHERE headerid='" . $SelectedBudget . "'
								AND account='" . $_POST['SelectedAccount'] . "'
								AND period='" . $Period . "'";
		}
	}
	$Errors = 0;
	foreach ($UpdateSQL as $SQL) {
		$UpdateResult = DB_query($SQL);
		$Errors+= DB_error_no();
	}
	if ($Errors == 0) {
		prnMsg(_('The budget figures were update successfully'), 'success');
	} else {
		prnMsg(_('There was a problem updating the budget figures'), 'error');
	}
}

if (!isset($SelectedBudget)) {
	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" id="createbudget">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<p class="page_title_text" >
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Budgets'), '" alt="', _('Budgets'), '" />', ' ', _('Select Budget'), '
		</p>';

	echo '<fieldset>
			<legend>', _('Select a Budget'), '</legend>';

	$SQL = "SELECT `id`,
					`name`
				FROM glbudgetheaders";
	$Result = DB_query($SQL);
	echo '<field>
			<label for="SelectedBudget">', _('Budget to enter amounts in'), '</label>
			<select name="SelectedBudget">';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="', $MyRow['id'], '">', $MyRow['name'], '</option>';
	}
	echo '</select>
		</field>
	</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Select" value="', _('Select'), '" />
		</div>';

	echo '</form>';

} else {
	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" id="createbudget">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<input type="hidden" name="SelectedBudget" value="', $SelectedBudget, '" />';

	echo '<p class="page_title_text" >
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/gl.png" title="', _('Enter Budget Amounts'), '" alt="', _('Enter Budget Amounts'), '" />', ' ', _('Enter Budget Amounts'), '
		</p>';

	echo '<fieldset>
			<legend>', _('General ledger account selection'), '</legend>';

	$SQL = "SELECT accountcode
				FROM chartmaster
				INNER JOIN accountgroups
					ON accountgroups.groupcode=chartmaster.groupcode
					AND accountgroups.language=chartmaster.language
				WHERE pandl=1
					AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
				ORDER BY accountcode";
	$Result = DB_query($SQL);
	$AccountList = array();
	while ($MyRow = DB_fetch_array($Result)) {
		$AccountList[] = $MyRow['accountcode'];
	}

	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 8) == 'Previous') {
			$AccountIndex = mb_substr($Key, 8);
			$_POST['SelectedAccount'] = $AccountList[$AccountIndex - 1];
		}
		if (mb_substr($Key, 0, 4) == 'Next') {
			$AccountIndex = mb_substr($Key, 4);
			$_POST['SelectedAccount'] = $AccountList[$AccountIndex + 1];
		}
	}

	if (!isset($_POST['SelectedAccount'])) {
		$_POST['SelectedAccount'] = $AccountList[0];
		$AccountIndex = 0;
	} else {
		$AccountIndex = array_search($_POST['SelectedAccount'], $AccountList);
	}

	if (isset($_POST['Update'])) {
		$AccountIndex++;
		$_POST['SelectedAccount'] = $AccountList[$AccountIndex];
	}

	echo '<input type="hidden" name="SelectedAccount" value="', $_POST['SelectedAccount'], '" />';

	echo '<field>
			<label for="SelectedAccount">', _('Select GL Account'), ':</label>';

	GLSelect(1, 'SelectedAccount');

	echo '</field>';

	echo '</fieldset>';

	echo '<div class="centre">';
	if ($AccountIndex == 0) {
		echo '<input type="submit" disabled="true" class="previous_button" name="Previous', $AccountIndex, '" value="', _('Prev Account'), '" />';
	} else {
		echo '<input type="submit" class="previous_button" name="Previous', $AccountIndex, '" value="', _('Prev Account'), '" />';
	}
	echo '<input type="submit" name="Select" value="', _('Select Account'), '" />';
	if ($AccountIndex == array_search(end($AccountList), $AccountList)) {
		echo '<input type="submit" disabled="true" class="next_button" name="Next', $AccountIndex, '" value="', _('Next Account'), '" />';
	} else {
		echo '<input type="submit" class="next_button" name="Next', $AccountIndex, '" value="', _('Next Account'), '" />';
	}
	echo '</div>';

	$SQL = "SELECT accountname
				FROM chartmaster
				INNER JOIN accountgroups
					ON accountgroups.groupcode=chartmaster.groupcode
					AND accountgroups.language=chartmaster.language
				WHERE pandl=1
					AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
					AND accountcode='" . $_POST['SelectedAccount'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	echo '<fieldset>
			<legend>', _('Budget values for account'), ' ', $_POST['SelectedAccount'], ' - ', $MyRow['accountname'], '</legend>';

	$SQL = "SELECT `owner`,
					`name`,
					`description`,
					`startperiod`,
					`endperiod`,
					`current`
				FROM glbudgetheaders
				WHERE id='" . $SelectedBudget . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$Total = 0;

	for ($CurrentPeriod = $MyRow['startperiod'];$CurrentPeriod <= $MyRow['endperiod'];$CurrentPeriod++) {
		$PeriodSQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $CurrentPeriod . "'";
		$PeriodResult = DB_query($PeriodSQL);
		$MyPeriodRow = DB_fetch_array($PeriodResult);

		$AmountSQL = "SELECT amount
						FROM glbudgetdetails
						WHERE headerid='" . $SelectedBudget . "'
							AND account='" . $_POST['SelectedAccount'] . "'
							AND period='" . $CurrentPeriod . "'";
		$AmountResult = DB_query($AmountSQL);
		$AmountRow = DB_fetch_array($AmountResult);
		if (!isset($AmountRow['amount'])) {
			$AmountRow['amount'] = 0;
			$InsertSQL = "INSERT INTO glbudgetdetails (id,
														headerid,
														account,
														period,
														amount
													) VALUES (
														NULL,
														'" . $SelectedBudget . "',
														'" . $_POST['SelectedAccount'] . "',
														'" . $CurrentPeriod . "',
														'" . $AmountRow['amount'] . "'
													)";
			$InsertResult = DB_query($InsertSQL);
		}
		$Total+= $AmountRow['amount'];
		echo '<field>
				<label for="', $CurrentPeriod, '">', MonthAndYearFromSQLDate($MyPeriodRow['lastdate_in_period']), '</label>
				<input type="text" class="number" name="Period', $CurrentPeriod, '" id="Period', $CurrentPeriod, '" value="', $AmountRow['amount'], '" onkeyup="UpdateTotal(', $MyRow['startperiod'], ', ', $MyRow['endperiod'], ')" />
			</field>';
	}
	echo '<field>
			<label for="Total">', _('Total'), '</label>
			<input readonly="readonly" type="text" class="number" id="Total" name="Total" value="', $Total, '" />
		</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Update" value="', _('Update Budget'), '" />
		</div>';

	echo '</form>';
}

echo '<script>
		function UpdateTotal(start, end) {
		total = 0;
		for (i = start; i <= end; i++) {
			total = total + Number(document.getElementById(\'Period\' + i).value);
		}
		document.getElementById(\'Total\').value = total
	}
	</script>';

include ('includes/footer.php');

?>