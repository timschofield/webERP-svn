<?php
/* Call this page with:
 * 1. A TransID to show the make up and to modify existing allocations.
 * 2. A DebtorNo to show all outstanding receipts or credits yet to be allocated.
 * 3. No parameters to show all outstanding credits and receipts yet to be allocated.
*/

include ('includes/DefineCustAllocsClass.php'); // Before includes/session.php **********
include ('includes/session.php');
$Title = _('Customer Receipt') . '/' . _('Credit Note Allocations');
/* Manual links before header.php */
$ViewTopic = 'ARTransactions';
$BookMark = 'CustomerAllocations';
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

if (isset($_POST['Cancel'])) {
	unset($_POST['AllocTrans']);
}

if (isset($_POST['UpdateDatabase']) or isset($_POST['RefreshAllocTotal'])) {

	if (!isset($_SESSION['Alloc'])) {
		prnMsg(_('Allocations can not be processed again') . '. ' . _('If you hit refresh on this page after having just processed an allocation') . ', ' . _('try to use the navigation links provided rather than the back button') . ', ' . _('to avoid this message in future'), 'info');
		include ('includes/footer.php');
		exit;
	}

	$InputError = 0;
	$TotalAllocated = 0;
	$TotalDiffOnExch = 0;

	for ($AllocCounter = 0;$AllocCounter < $_POST['TotalNumberOfAllocs'];$AllocCounter++) {
		// loop through amounts allocated using AllocnItm->ID for each record
		if (isset($_POST['Amt' . $AllocCounter])) {
			// allocatable charge amounts
			if (!is_numeric(filter_number_format($_POST['Amt' . $AllocCounter]))) {
				$_POST['Amt' . $AllocCounter] = 0;
			}
			if (isset($_POST['All' . $AllocCounter]) and $_POST['All' . $AllocCounter] == True) {
				$_POST['Amt' . $AllocCounter] = $_POST['YetToAlloc' . $AllocCounter];
			}
			//			if (filter_number_format($_POST['Amt' . $AllocCounter]) > $_POST['YetToAlloc' . $AllocCounter]) {
			//				$_POST['Amt' . $AllocCounter] = locale_number_format($_POST['YetToAlloc' . $AllocCounter], $_SESSION['Alloc']->CurrDecimalPlaces);
			//				// Amount entered must be smaller than unallocated amount
			//			}
			$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->AllocAmt = filter_number_format($_POST['Amt' . $AllocCounter]);
			// recalcuate the new difference on exchange (a +positive amount is a gain -ve a loss)
			$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch = (filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->TransExRate) - (filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->ExRate);

			$TotalDiffOnExch+= $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch;
			$TotalAllocated+= filter_number_format($_POST['Amt' . $AllocCounter]);
		}

	}

	if ($TotalAllocated + $_SESSION['Alloc']->TransAmt > 0.008) {
		prnMsg(_('Allocation could not be processed because the amount allocated is more than the') . ' ' . $_SESSION['Alloc']->TransTypeName . ' ' . _('being allocated') . '<br />' . _('Total allocated') . ' = ' . $TotalAllocated . ' ' . _('and the total amount of the') . ' ' . $_SESSION['Alloc']->TransTypeName . ' ' . _('was') . ' ' . -$_SESSION['Alloc']->TransAmt, 'error');
		$InputError = 1;
	}
}

if (isset($_POST['UpdateDatabase'])) {
	if ($InputError == 0) {
		//
		//========[ START TRANSACTION ]===========
		//
		$Error = '';
		$Result = DB_Txn_Begin();
		$AllAllocations = 0;
		foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
			if ($AllocnItem->PrevAllocRecordID != 'NA') {
				// original allocation has changed so delete the old allocation record
				$SQL = "DELETE FROM custallocns WHERE id = '" . $AllocnItem->PrevAllocRecordID . "'";
				if (!$Result = DB_query($SQL)) {
					$Error = 'Could not delete old allocation record';
				}
			}

			if ($AllocnItem->AllocAmt > 0) {
				$SQL = "INSERT INTO
								custallocns (
								datealloc,
								amt,
								transid_allocfrom,
								transid_allocto
							) VALUES (
								'CURRENT_DATE',
								'" . $AllocnItem->AllocAmt . "',
								'" . $_SESSION['Alloc']->AllocTrans . "',
								'" . $AllocnItem->ID . "'
							)";
				if (!$Result = DB_query($SQL)) {
					$Error = 'Could not change allocation record';
				}
			}
			$NewAllocTotal = $AllocnItem->PrevAlloc + $AllocnItem->AllocAmt;
			$AllAllocations = $AllAllocations + $AllocnItem->AllocAmt;
			$Settled = (abs($NewAllocTotal - $AllocnItem->TransAmount) < 0.005) ? 1 : 0;

			$SQL = "UPDATE debtortrans
						SET diffonexch='" . $AllocnItem->DiffOnExch . "',
						alloc = '" . $NewAllocTotal . "',
						settled = '" . $Settled . "'
						WHERE id = '" . $AllocnItem->ID . "'";
			if (!$Result = DB_query($SQL)) {
				$Error = 'Could not update difference on exchange';
			}
		}
		if (abs($TotalAllocated + $_SESSION['Alloc']->TransAmt) < 0.01) {
			$Settled = 1;
		} else {
			$Settled = 0;
		}

		// Update the receipt or credit note
		$SQL = "UPDATE debtortrans
				SET alloc = '" . -$AllAllocations . "',
				diffonexch = '" . -$TotalDiffOnExch . "',
				settled='" . $Settled . "'
				WHERE id = '" . $_POST['AllocTrans'] . "'";

		if (!$Result = DB_query($SQL)) {
			$Error = 'Could not update receipt or credit note';
		}

		// If GLLink to debtors active post diff on exchange to GL
		$MovtInDiffOnExch = - $_SESSION['Alloc']->PrevDiffOnExch - $TotalDiffOnExch;

		if ($MovtInDiffOnExch != 0) {
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
				$PeriodNo = GetPeriod($_SESSION['Alloc']->TransDate);
				$_SESSION['Alloc']->TransDate = FormatDateForSQL($_SESSION['Alloc']->TransDate);

				$SQL = "INSERT INTO gltrans (
								type,
								typeno,
								trandate,
								periodno,
								account,
								narrative,
								amount
							) VALUES (
								'" . $_SESSION['Alloc']->TransType . "',
								'" . $_SESSION['Alloc']->TransNo . "',
								'" . $_SESSION['Alloc']->TransDate . "',
								'" . $PeriodNo . "',
								'" . $_SESSION['CompanyRecord']['exchangediffact'] . "',
								'',
								'" . $MovtInDiffOnExch . "'
							)";
				if (!$Result = DB_query($SQL)) {
					$Error = 'Could not update exchange difference in General Ledger';
				}

				$SQL = "INSERT INTO gltrans (
							type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount
		  				) VALUES (
							'" . $_SESSION['Alloc']->TransType . "',
							'" . $_SESSION['Alloc']->TransNo . "',
							'" . $_SESSION['Alloc']->TransDate . "',
							'" . $PeriodNo . "',
							'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
							'',
							'" . -$MovtInDiffOnExch . "'
						)";
				if (!$Result = DB_query($SQL)) {
					$Error = 'Could not update debtors control in General Ledger';
				}
			}

		}

		//
		//========[ COMMIT TRANSACTION ]===========
		//
		if (empty($Error)) {
			$Result = DB_Txn_Commit();
		} else {
			$Result = DB_Txn_Rollback();
			prnMsg($Error, 'error');
		}
		unset($_SESSION['Alloc']);
		unset($_POST['AllocTrans']);
	}
}

if (isset($_GET['AllocTrans'])) {
	if (isset($_SESSION['Alloc'])) {
		unset($_SESSION['Alloc']->Allocs);
		unset($_SESSION['Alloc']);
	}

	$_SESSION['Alloc'] = new Allocation;
	$_POST['AllocTrans'] = $_GET['AllocTrans']; // Set AllocTrans when page first called
	$SQL = "SELECT systypes.typename,
					debtortrans.type,
					debtortrans.transno,
					debtortrans.trandate,
					debtortrans.debtorno,
					debtorsmaster.name,
					debtortrans.rate,
					(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) as total,
					debtortrans.diffonexch,
					debtortrans.alloc,
					currencies.decimalplaces
			FROM debtortrans
			INNER JOIN systypes
				ON debtortrans.type = systypes.typeid
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno = debtorsmaster.debtorno
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtortrans.id='" . $_POST['AllocTrans'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_SESSION['Alloc']->AllocTrans = $_POST['AllocTrans'];
	$_SESSION['Alloc']->DebtorNo = $MyRow['debtorno'];
	$_SESSION['Alloc']->CustomerName = $MyRow['name'];
	$_SESSION['Alloc']->TransType = $MyRow['type'];
	$_SESSION['Alloc']->TransTypeName = $MyRow['typename'];
	$_SESSION['Alloc']->TransNo = $MyRow['transno'];
	$_SESSION['Alloc']->TransExRate = $MyRow['rate'];
	$_SESSION['Alloc']->TransAmt = $MyRow['total'];
	$_SESSION['Alloc']->PrevDiffOnExch = $MyRow['diffonexch'];
	$_SESSION['Alloc']->TransDate = ConvertSQLDate($MyRow['trandate']);
	$_SESSION['Alloc']->CurrDecimalPlaces = $MyRow['decimalplaces'];

	// First get transactions that have outstanding balances
	$SQL = "SELECT debtortrans.id,
					typename,
					transno,
					trandate,
					rate,
					ovamount+ovgst+ovfreight+ovdiscount as total,
					diffonexch,
					alloc
			FROM debtortrans INNER JOIN systypes
			ON debtortrans.type = systypes.typeid
			WHERE debtortrans.settled=0
			AND debtorno='" . DB_escape_string($_SESSION['Alloc']->DebtorNo) . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL.= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		$_SESSION['Alloc']->add_to_AllocsAllocn($MyRow['id'], $MyRow['typename'], $MyRow['transno'], ConvertSQLDate($MyRow['trandate']), 0, $MyRow['total'], $MyRow['rate'], $MyRow['diffonexch'], $MyRow['diffonexch'], $MyRow['alloc'], 'NA');
	}
	DB_free_result($Result);

	// Get trans previously allocated to by this trans - this will overwrite incomplete allocations above
	$SQL = "SELECT debtortrans.id,
					typename,
					transno,
					trandate,
					rate,
					ovamount+ovgst+ovfreight+ovdiscount AS total,
					diffonexch,
					debtortrans.alloc-custallocns.amt AS prevallocs,
					amt,
					custallocns.id AS allocid
				FROM debtortrans
				INNER JOIN systypes
					ON debtortrans.type = systypes.typeid
				INNER JOIN custallocns
					ON debtortrans.id=custallocns.transid_allocto
				WHERE custallocns.transid_allocfrom='" . $_POST['AllocTrans'] . "'
					AND debtorno='" . DB_escape_string($_SESSION['Alloc']->DebtorNo) . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL.= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		$DiffOnExchThisOne = ($MyRow['amt'] / $MyRow['rate']) - ($MyRow['amt'] / $_SESSION['Alloc']->TransExRate);
		$_SESSION['Alloc']->add_to_AllocsAllocn($MyRow['id'], $MyRow['typename'], $MyRow['transno'], ConvertSQLDate($MyRow['trandate']), $MyRow['amt'], $MyRow['total'], $MyRow['rate'], $DiffOnExchThisOne, ($MyRow['diffonexch'] - $DiffOnExchThisOne), $MyRow['prevallocs'], $MyRow['allocid']);
	}
	DB_free_result($Result);
}

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/allocation.png" title="', _('Allocate Receipt'), '" alt="" />', ' ', _('Allocate Receipts'), '
	</p>';

if (isset($_POST['AllocTrans'])) {
	// Page called with trans number
	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<input type="hidden" name="AllocTrans" value="', $_POST['AllocTrans'], '" />';

	// Show trans already allocated and potential new allocations
	echo '<table>';
	echo '<tr>
			<th colspan="7">
				<b>', $_SESSION['Alloc']->DebtorNo, ' - ', $_SESSION['Alloc']->CustomerName, '</b>';

	if ($_SESSION['Alloc']->TransExRate != 1) {
		echo '<br />', _('Amount in customer currency'), ' <b>', locale_number_format(-$_SESSION['Alloc']->TransAmt, $_SESSION['Alloc']->CurrDecimalPlaces), '</b><i>(', _('converted into local currency at an exchange rate of'), ' ', $_SESSION['Alloc']->TransExRate . ')</i>';
	}
	echo '</th>
		</tr>';
	echo '<tr>
			<th>', _('Trans'), '<br />', _('Type'), '</th>
			<th>', _('Trans'), '<br />', _('Number'), '</th>
			<th>', _('Trans'), '<br />', _('Date'), '</th>
			<th>', _('Total'), '<br />', _('Amount'), '</th>
			<th>', _('Yet to'), '<br />', _('Allocate'), '</th>
			<th>', _('This'), '<br />', _('Allocation'), '</th>
			<th>', _('Running'), '<br />', _('Balance'), '</th>
		</tr>';

	$Counter = 0;
	$TotalAllocated = 0;
	$Balance = 0;
	$j = 0;
	foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
		$YetToAlloc = ($AllocnItem->TransAmount - $AllocnItem->PrevAlloc);

		if ($AllocnItem->ID == $_POST['AllocTrans']) {
			$CurTrans = _('Being allocated');
		} else if ($AllocnItem->AllocAmt > 0) {
		} else {
			$CurTrans = "&nbsp;";
		}

		echo '<tr class="striped_row"><td>', _($AllocnItem->TransType), '</td>
				<td class="number">', $AllocnItem->TypeNo, '</td>
				<td>', $AllocnItem->TransDate, '</td>
				<td class="number">', locale_number_format($AllocnItem->TransAmount, $_SESSION['Alloc']->CurrDecimalPlaces), '</td>
				<td class="number">', locale_number_format($YetToAlloc, $_SESSION['Alloc']->CurrDecimalPlaces), '</td>';

		if ($AllocnItem->ID == $_POST['AllocTrans']) {
			$Balance+= $YetToAlloc;
			echo '<td>', $CurTrans, '</td>
					<td class="number">', locale_number_format($Balance, $_SESSION['Alloc']->CurrDecimalPlaces), '</td>
				</tr>';
			++$j;
		} else {
			echo '<td class="number"><input type="hidden" name="YetToAlloc', $Counter, '" value="', round($YetToAlloc, $_SESSION['Alloc']->CurrDecimalPlaces), '" />';
			if (ABS($AllocnItem->AllocAmt - $YetToAlloc) < 0.01) {
				echo '<input type="checkbox" name="All', $Counter, '" checked="checked" />';
			} else {
				echo '<input type="checkbox" name="All', $Counter, '" />';
			}
			$Balance+= $YetToAlloc - $AllocnItem->AllocAmt;
			++$j;
			echo '<input type="text" class="number" name="Amt', $Counter, '" required="required" maxlength="12" size="13" value="', locale_number_format(round($AllocnItem->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces), $_SESSION['Alloc']->CurrDecimalPlaces), '" />
					<input type="hidden" name="AllocID', $Counter, '" value="', $AllocnItem->ID, '" /></td>
					<td class="number">', locale_number_format($Balance, $_SESSION['Alloc']->CurrDecimalPlaces), '</td>
				</tr>';
		}
		$TotalAllocated+= round($AllocnItem->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces);
		++$Counter;
	}

	echo '<tr class="striped_row">
			<td colspan="5" class="number"><b>', _('Total Allocated'), ':</b></td>
			<td class="number"><b>', locale_number_format($TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces), '</b></td>
			<td colspan="2">
				<input type="submit" name="RefreshAllocTotal" value="', _('Recalculate Total To Allocate'), '" />
			</td>
		</tr>
		<tr class="striped_row">
			<td colspan="5" class="number"><b>', _('Left to allocate'), '</b></td>
			<td class="number"><b>', locale_number_format(-$_SESSION['Alloc']->TransAmt - $TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces), '</b></td>
		</tr>
	</table>';

	echo '<input type="hidden" name="TotalNumberOfAllocs" value="', $Counter, '" />';

	echo '<div class="centre">
			<input type="submit" name="UpdateDatabase" value="', _('Process Allocations'), '" />
			<input type="submit" name="Cancel" value="', _('Cancel'), '" />
		</div>
	</form>';

} elseif (isset($_GET['DebtorNo'])) {
	// Page called with customer code
	unset($_SESSION['Alloc']->Allocs);
	unset($_SESSION['Alloc']);

	$SQL = "SELECT debtortrans.id,
				debtortrans.transno,
				systypes.typename,
				debtortrans.type,
				debtortrans.debtorno,
				debtorsmaster.name,
				debtortrans.trandate,
				debtortrans.reference,
				debtortrans.rate,
				debtortrans.ovamount+debtortrans.ovgst+debtortrans.ovdiscount+debtortrans.ovfreight as total,
				debtortrans.alloc,
				currencies.decimalplaces AS currdecimalplaces,
				debtorsmaster.currcode
			FROM debtortrans
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN systypes
				ON debtortrans.type=systypes.typeid
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtortrans.debtorno='" . $_GET['DebtorNo'] . "'
				AND (debtortrans.type=12 OR debtortrans.type=11)
				AND debtortrans.settled=0";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL.= " ORDER BY debtortrans.id";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {
		prnMsg(_('No outstanding receipts or credits to be allocated for this customer'), 'info');
		include ('includes/footer.php');
		exit;
	}
	echo '<table>
				<tr>
					<th>', _('Trans Type'), '</th>
					<th>', _('Customer'), '</th>
					<th>', _('Cust No'), '</th>
					<th>', _('Number'), '</th>
					<th>', _('Date'), '</th>
					<th>', _('Total'), '</th>
					<th>', _('To Alloc'), '</th>
					<th>', _('Currency'), '</th>
					<th>', _('Action'), '</th>
				</tr>';
	$k = 0;
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td>', $MyRow['name'], '</td>
				<td>', $MyRow['debtorno'], '</td>
				<td>', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td class="number">', locale_number_format($MyRow['total'], $MyRow['currdecimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['total'] - $MyRow['alloc'], $MyRow['currdecimalplaces']), '</td>
				<td>', $MyRow['currcode'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?AllocTrans=', $MyRow['id'], '">', _('Allocate'), '</a></td>
			</tr>';
	}
	echo '</table>';
} else {
	/* Page called with no parameters */
	unset($_SESSION['Alloc']->Allocs);
	unset($_SESSION['Alloc']);

	$SQL = "SELECT debtortrans.id,
				debtortrans.transno,
				systypes.typename,
				debtortrans.type,
				debtortrans.debtorno,
				debtorsmaster.name,
				debtortrans.trandate,
				debtortrans.reference,
				debtortrans.rate,
				debtortrans.ovamount+debtortrans.ovgst+debtortrans.ovdiscount+debtortrans.ovfreight as total,
				debtortrans.alloc,
				debtorsmaster.currcode,
				currencies.decimalplaces AS currdecimalplaces
			FROM debtortrans
			INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN systypes
				ON debtortrans.type=systypes.typeid
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			WHERE (debtortrans.type=12 OR debtortrans.type=11)
				AND debtortrans.settled=0";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL.= " ORDER BY debtorsmaster.name";

	$Result = DB_query($SQL);
	$NoOfUnallocatedTrans = DB_num_rows($Result);
	$CurrentTransaction = 1;
	$CurrentDebtor = '';
	echo '<table>
				<tr>
					<th>', _('Trans Type'), '</th>
					<th>', _('Customer'), '</th>
					<th>', _('Cust No'), '</th>
					<th>', _('Number'), '</th>
					<th>', _('Date'), '</th>
					<th>', _('Total'), '</th>
					<th>', _('To Alloc'), '</th>
					<th>', _('Currency'), '</th>
					<th>', _('Action'), '</th>
				</tr>';

	$k = 0;
	while ($MyRow = DB_fetch_array($Result)) {

		$AllocateLink = '<a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?AllocTrans=' . $MyRow['id'] . '">' . _('Allocate') . '</a>';

		if ($CurrentDebtor != $MyRow['debtorno']) {
			if ($CurrentTransaction > 1) {
				echo '<tr class="striped_row">
						<td colspan="7" class="number"><b>', locale_number_format($Balance, $CurrDecimalPlaces), '</b></td>
						<td><b>', $CurrCode, '</b></td>
						<td><b>', _('Balance'), '</b></td>
					</tr>';
			}

			$Balance = 0;
			$CurrentDebtor = $MyRow['debtorno'];

			$BalSQL = "SELECT SUM(ovamount+ovgst+ovfreight+ovdiscount-alloc) as total
						FROM debtortrans
						WHERE (type=12 OR type=11)
						AND debtorno='" . $MyRow['debtorno'] . "'";
			$BalResult = DB_query($BalSQL);
			$BalRow = DB_fetch_array($BalResult);
			$Balance = $BalRow['total'];
		}
		$CurrentTransaction++;
		$CurrCode = $MyRow['currcode'];
		$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
		if (isset($Balance) and abs($Balance) < - 0.01) {
			$AllocateLink = '&nbsp;';
		}

		echo '<tr class="striped_row">
				<td>', _($MyRow['typename']), '</td>
				<td>', $MyRow['name'], '</td>
				<td>', $MyRow['debtorno'], '</td>
				<td>', $MyRow['transno'], '</td>
				<td>', ConvertSQLDate($MyRow['trandate']), '</td>
				<td class="number">', locale_number_format($MyRow['total'], $CurrDecimalPlaces), '</td>
				<td class="number">', locale_number_format($MyRow['total'] - $MyRow['alloc'], $CurrDecimalPlaces), '</td>
				<td>', $CurrCode, '</td>
				<td>', $AllocateLink, '</td>
			</tr>';

	} //end loop around unallocated receipts and credit notes
	if (!isset($Balance)) {
		$Balance = 0;
	}
	if ($NoOfUnallocatedTrans == 0) {
		prnMsg(_('There are no allocations to be done'), 'info');
	} else {
		echo '<tr class="striped_row">
				<td colspan="7" class="number"><b>', locale_number_format($Balance, $CurrDecimalPlaces), '</b></td>
				<td><b>', $CurrCode, '</b></td>
				<td><b>', _('Balance'), '</b></td>
			</tr>';
	}
	echo '</table>';
}

include ('includes/footer.php');

?>