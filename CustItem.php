<?php
/* $Id: CustItem.php 1 2014-04-23 05:10:46Z agaluski $*/

include ('includes/session.php');

$Title = _('Customer Item Data');

include ('includes/header.php');

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = trim(mb_strtoupper($_GET['DebtorNo']));
} elseif (isset($_POST['DebtorNo'])) {
	$DebtorNo = trim(mb_strtoupper($_POST['DebtorNo']));
}

if (isset($_GET['StockID'])) {
	$StockId = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockId = trim(mb_strtoupper($_POST['StockID']));
}

if (isset($_GET['Edit'])) {
	$Edit = true;
} elseif (isset($_POST['Edit'])) {
	$Edit = true;
} else {
	$Edit = false;
}

if (isset($_POST['StockUOM'])) {
	$StockUOM = $_POST['StockUOM'];
}

$NoCustItemData = 0;

echo '<div class="toplink">
		<a href="', $RootPath, '/SelectProduct.php">', _('Back to Items'), '</a>
	</div>';

if (isset($_POST['cust_description'])) {
	$_POST['cust_description'] = trim($_POST['cust_description']);
}
if (isset($_POST['cust_part'])) {
	$_POST['cust_part'] = trim($_POST['cust_part']);
}

if ((isset($_POST['AddRecord']) or isset($_POST['UpdateRecord'])) and isset($DebtorNo)) {
	/*Validate Inputs */
	$InputError = 0;
	/*Start assuming the best */

	if ($StockId == '' or !isset($StockId)) {
		$InputError = 1;
		prnMsg(_('There is no stock item set up enter the stock code or select a stock item using the search page'), 'error');
	}

	if (!is_numeric(filter_number_format($_POST['ConversionFactor']))) {
		$InputError = 1;
		unset($_POST['ConversionFactor']);
		prnMsg(_('The conversion factor entered was not numeric') . ' (' . _('a number is expected') . '). ' . _('The conversion factor is the number which the price must be divided by to get the unit price in our unit of measure') . '. <br />' . _('E.g.') . ' ' . _('The customer sells an item by the tonne and we hold stock by the kg') . '. ' . _('The debtorsmaster.price must be divided by 1000 to get to our cost per kg') . '. ' . _('The conversion factor to enter is 1000') . '. <br /><br />' . _('No changes will be made to the database'), 'error');
	}

	if ($InputError == 0 and isset($_POST['AddRecord'])) {
		$SQL = "INSERT INTO custitem (debtorno,
										stockid,
										customersuom,
										conversionfactor,
										cust_description,
										cust_part)
						VALUES ('" . $DebtorNo . "',
							'" . $StockId . "',
							'" . $_POST['customersUOM'] . "',
							'" . filter_number_format($_POST['ConversionFactor']) . "',
							'" . $_POST['cust_description'] . "',
							'" . $_POST['cust_part'] . "')";
		$ErrMsg = _('The customer Item details could not be added to the database because');
		$DbgMsg = _('The SQL that failed was');
		$AddResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		prnMsg(_('This customer data has been added to the database'), 'success');
		unset($debtorsmasterResult);
	}
	if ($InputError == 0 and isset($_POST['UpdateRecord'])) {
		$SQL = "UPDATE custitem SET customersuom='" . $_POST['customersUOM'] . "',
										conversionfactor='" . filter_number_format($_POST['ConversionFactor']) . "',
										cust_description='" . $_POST['cust_description'] . "',
										custitem.cust_part='" . $_POST['cust_part'] . "'
							WHERE custitem.stockid='" . $StockId . "'
							AND custitem.debtorno='" . $DebtorNo . "'";
		$ErrMsg = _('The customer details could not be updated because');
		$DbgMsg = _('The SQL that failed was');
		$UpdResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		prnMsg(_('customer data has been updated'), 'success');
		unset($debtorsmasterResult);
		unset($DebtorNo);
	}

	if ($InputError == 0 and isset($_POST['AddRecord'])) {
		/*  insert took place and need to clear the form  */
		unset($DebtorNo);
		unset($_POST['customersUOM']);
		unset($_POST['ConversionFactor']);
		unset($_POST['cust_description']);
		unset($_POST['cust_part']);

	}
}

if (isset($_GET['Delete'])) {
	$SQL = "DELETE FROM custitem
	   				WHERE custitem.debtorno='" . $DebtorNo . "'
	   				AND custitem.stockid='" . $StockId . "'";
	$ErrMsg = _('The customer details could not be deleted because');
	$DelResult = DB_query($SQL, $ErrMsg);
	prnMsg(_('This customer data record has been successfully deleted'), 'success');
	unset($DebtorNo);
}

if ($Edit == false) {

	$ItemResult = DB_query("SELECT description FROM stockmaster WHERE stockid='" . $StockId . "'");
	$DescriptionRow = DB_fetch_array($ItemResult);
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, ' ', _('For Stock Code'), ' - ', $StockId, ' - ', $DescriptionRow['description'], '
		</p>';

	$SQL = "SELECT custitem.debtorno,
				debtorsmaster.name,
				debtorsmaster.currcode,
				custitem.customersUOM,
				custitem.conversionfactor,
				custitem.cust_description,
				custitem.cust_part,
				currencies.decimalplaces AS currdecimalplaces
			FROM custitem INNER JOIN debtorsmaster
				ON custitem.debtorno=debtorsmaster.DebtorNo
			INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			WHERE custitem.stockid = '" . $StockId . "'";
	$ErrMsg = _('The customer details for the selected part could not be retrieved because');
	$custitemResult = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($custitemResult) == 0 and $StockId != '') {
		prnMsg(_('There is no customer data set up for the part selected'), 'info');
		$NoCustItemData = 1;
	} else if ($StockId != '') {

		echo '<table cellpadding="2">
				<thead>
					<tr>
						<th class="SortedColumn">', _('Customer'), '</th>
						<th>', _('Customer Unit'), '</th>
						<th>', _('Conversion Factor'), '</th>
						<th class="SortedColumn">', _('Customer Item'), '</th>
						<th class="SortedColumn">', _('Customer Description'), '</th>
					</tr>
				</thead>';

		$CountPreferreds = 0;

		echo '<tbody>';
		while ($MyRow = DB_fetch_array($custitemResult)) {

			echo '<tr class="striped_row">
						<td>', $MyRow['name'], '</td>
						<td>', $MyRow['customersUOM'], 's</td>
						<td class="number">', locale_number_format($MyRow['conversionfactor'], 'Variable'), '</td>
						<td>', $MyRow['cust_part'], '</td>
						<td>', $MyRow['cust_description'], '</td>
						<td><a href="', htmlspecialchars(basename(__FILE__)), '?StockID=', urlencode($StockId), '&amp;DebtorNo=', urlencode($MyRow['debtorno']), '&amp;Edit=1">', _('Edit'), '</a></td>
						<td><a href="', htmlspecialchars(basename(__FILE__)), '?StockID=', urlencode($StockId), '&amp;DebtorNo=', urlencode($MyRow['debtorno']), '&amp;Delete=1" onclick=\'return confirm("', _('Are you sure you wish to delete this customer data?'), '");\'>', _('Delete'), '</a></td>
					</tr>';
		} //end of while loop
		echo '</tbody>
			</table>';
	} // end of there are rows to show

}
/* Only show the existing records if one is not being edited */

if (isset($DebtorNo) and $DebtorNo != '' and !isset($_POST['Searchcustomer'])) {
	/*NOT EDITING AN EXISTING BUT customer selected or ENTERED*/

	$SQL = "SELECT debtorsmaster.name,
					debtorsmaster.currcode,
					currencies.decimalplaces AS currdecimalplaces
			FROM debtorsmaster
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE DebtorNo='" . $DebtorNo . "'";
	$ErrMsg = _('The customer details for the selected customer could not be retrieved because');
	$DbgMsg = _('The SQL that failed was');
	$SuppSelResult = DB_query($SQL, $ErrMsg, $DbgMsg);
	if (DB_num_rows($SuppSelResult) == 1) {
		$MyRow = DB_fetch_array($SuppSelResult);
		$Name = $MyRow['name'];
		$CurrCode = $MyRow['currcode'];
		$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
	} else {
		prnMsg(_('The customer code') . ' ' . $DebtorNo . ' ' . _('is not an existing customer in the database') . '. ' . _('You must enter an alternative customer code or select a customer using the search facility below'), 'error');
		unset($DebtorNo);
	}
} else {
	if ($NoCustItemData == 0) {
		echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', ' ', _('Search For Customer'), '
			</p>';
	}
	if (!isset($_POST['Searchcustomer'])) {
		echo '<form action="', htmlspecialchars(basename(__FILE__)), '" method="post">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

		echo '<fieldset>
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
				<input type="hidden" name="StockID" value="', $StockId, '" />
				<legend>', _('Search Criteria'), '</legend>
				<field>
					<label for="Keywords">', _('Text in the customer'), ' <b>', _('NAME'), '</b>:</label>
					<input type="text" name="Keywords" size="20" maxlength="25" />
				</field>
				<h1>', _('OR'), '</h1>
				<field>
					<label for="cust_no">', _('Text in customer'), ' <b>', _('CODE'), '</b>:</label>
					<input type="text" name="cust_no" data-type="no-illegal-chars" size="20" maxlength="50" />
				</field>
			</fieldset>';

		echo '<div class="centre">
				<input type="submit" name="Searchcustomer" value="', _('Find Customers Now'), '" />
			</div>
		</form>';
		include ('includes/footer.php');
		exit;
	}
}

if ($Edit == true) {
	$ItemResult = DB_query("SELECT description FROM stockmaster WHERE stockid='" . $StockId . "'");
	$DescriptionRow = DB_fetch_array($ItemResult);
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, ' ', _('For Stock Code'), ' - ', $StockId, ' - ', $DescriptionRow['description'], '
		</p>';
}

if (isset($_POST['Searchcustomer'])) {
	if (isset($_POST['Keywords']) and isset($_POST['cust_no'])) {
		prnMsg(_('Customer Name keywords have been used in preference to the customer Code extract entered') . '.', 'info');
	}
	if ($_POST['Keywords'] == '' and $_POST['cust_no'] == '') {
		$_POST['Keywords'] = ' ';
	}
	if (mb_strlen($_POST['Keywords']) > 0) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL = "SELECT debtorsmaster.DebtorNo,
						debtorsmaster.name,
						debtorsmaster.currcode,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3
				FROM debtorsmaster
				WHERE debtorsmaster.name " . LIKE . " '" . $SearchString . "'";

	} elseif (mb_strlen($_POST['cust_no']) > 0) {
		$SQL = "SELECT debtorsmaster.DebtorNo,
						debtorsmaster.name,
						debtorsmaster.currcode,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3
				FROM debtorsmaster
				WHERE debtorsmaster.DebtorNo " . LIKE . " '%" . $_POST['cust_no'] . "%'";

	} //one of keywords or cust_part was more than a zero length string
	$ErrMsg = _('The cuswtomer matching the criteria entered could not be retrieved because');
	$DbgMsg = _('The SQL to retrieve customer details that failed was');
	$debtorsmasterResult = DB_query($SQL, $ErrMsg, $DbgMsg);
} //end of if search
if (isset($debtorsmasterResult) and DB_num_rows($debtorsmasterResult) > 0) {
	if (isset($StockId)) {
		$Result = DB_query("SELECT stockmaster.description,
								stockmaster.units,
								stockmaster.mbflag
						FROM stockmaster
						WHERE stockmaster.stockid='" . $StockId . "'");
		$MyRow = DB_fetch_row($Result);
		$StockUOM = $MyRow[1];
		if (DB_num_rows($Result) <> 1) {
			prnMsg(_('Stock Item') . ' - ' . $StockId . ' ' . _('is not defined in the database'), 'warn');
		}
	} else {
		$StockId = '';
		$StockUOM = 'each';
	}
	echo '<form action="' . htmlspecialchars(basename(__FILE__)) . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<table cellpadding="2" colspan="7">
			<thead>
				<tr>
					<th class="SortedColumn">' . _('Code') . '</th>
					<th class="SortedColumn">' . _('Customer Name') . '</th>
					<th class="SortedColumn">' . _('Currency') . '</th>
					<th class="SortedColumn">' . _('Address 1') . '</th>
					<th class="SortedColumn">' . _('Address 2') . '</th>
					<th class="SortedColumn">' . _('Address 3') . '</th>
				</tr>
			</thead>';

	$k = 0;
	echo '<tbody>';
	while ($MyRow = DB_fetch_array($debtorsmasterResult)) {
		echo '<tr class="striped_row">
				<td><input type="submit" name="DebtorNo" value="', $MyRow['DebtorNo'], '" /></td>
				<td>', $MyRow['name'], '</td>
				<td>', $MyRow['currcode'], '</td>
				<td>', $MyRow['address1'], '</td>
				<td>', $MyRow['address2'], '</td>
				<td>', $MyRow['address3'], '</td>
			</tr>';

		echo '<input type="hidden" name="StockID" value="' . $StockId . '" />';
		echo '<input type="hidden" name="StockUOM" value="' . $StockUOM . '" />';

	}
	//end of while loop
	echo '</tbody>
		</table>
	</form>';
}
//end if results to show
/*Show the input form for new customer details */
if (!isset($debtorsmasterResult)) {
	if ($Edit == true or isset($_GET['Copy'])) {

		$SQL = "SELECT custitem.debtorno,
						debtorsmaster.name,
						debtorsmaster.currcode,
						custitem.customersUOM,
						custitem.cust_description,
						custitem.conversionfactor,
						custitem.cust_part,
						stockmaster.units,
						currencies.decimalplaces AS currdecimalplaces
				FROM custitem INNER JOIN debtorsmaster
					ON custitem.debtorno=debtorsmaster.DebtorNo
				INNER JOIN stockmaster
					ON custitem.stockid=stockmaster.stockid
				INNER JOIN currencies
					ON debtorsmaster.currcode = currencies.currabrev
				WHERE custitem.debtorno='" . $DebtorNo . "'
				AND custitem.stockid='" . $StockId . "'";

		$ErrMsg = _('The customer purchasing details for the selected customer and item could not be retrieved because');
		$EditResult = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_array($EditResult);
		$Name = $MyRow['name'];

		$CurrCode = $MyRow['currcode'];
		$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
		$_POST['customersUOM'] = $MyRow['customersUOM'];
		$_POST['cust_description'] = $MyRow['cust_description'];
		$_POST['ConversionFactor'] = locale_number_format($MyRow['conversionfactor'], 'Variable');
		$_POST['cust_part'] = $MyRow['cust_part'];
		$StockUOM = $MyRow['units'];
	}
	echo '<form action="', htmlspecialchars(basename(__FILE__)), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	if (!isset($DebtorNo)) {
		$DebtorNo = '';
	}
	if ($Edit == true) {
		echo '<fieldset>
				<legend>', _('Edit Details'), '</legend>
				<field>
					<label for="DebtorNo">', _('Customer Name'), ':</label>
					<input type="hidden" name="DebtorNo" value="', $DebtorNo, '" />
					<div class="fieldtext">', $DebtorNo, ' - ', $Name, '</div>
				</field>';
	} else {
		echo '<fieldset>
				<legend>', _('New Details'), '</legend>
				<field>
					<label for="DebtorNo">', _('Customer Name'), ':</label>
					<input type="hidden" name="DebtorNo" maxlength="10" size="11" value="', $DebtorNo, '" />';

		if ($DebtorNo != '') {
			echo '<div class="fieldtext">', $Name;
		}
		if (!isset($Name) or $Name = '') {
			echo '(', _('A search facility is available below if necessary'), ')';
		} else {
			echo '<div class="fieldtext">' . $Name;
		}
		echo '</div>
			</field>';
	}
	echo '<input type="hidden" name="StockID" maxlength="10" size="11" value="' . $StockId . '" />';
	if (!isset($CurrCode)) {
		$CurrCode = '';
	}

	if (!isset($_POST['customersUOM'])) {
		$_POST['customersUOM'] = '';
	}
	if (!isset($_POST['cust_description'])) {
		$_POST['cust_description'] = '';
	}
	if (!isset($_POST['cust_part'])) {
		$_POST['cust_part'] = '';
	}
	echo '<field>
			<label for="CurrCode">', _('Currency'), ':</label>
			<input type="hidden" name="CurrCode" value="', $CurrCode, '" />
			<div class="fieldtext">', $CurrCode, '</div>
		</field>';

	echo '<field>
			<label>', _('Our Unit of Measure'), ':</label>';

	if (isset($DebtorNo)) {
		echo '<div class="fieldtext">', $StockUOM, '</div>
		</field>';
	}
	echo '<field>
			<label for="customersUOM">', _('Customer Unit of Measure'), ':</label>
			<input type="text" name="customersUOM" size="20" maxlength="20" value ="', $_POST['customersUOM'], '"/>
		</field>';

	if (!isset($_POST['ConversionFactor']) or $_POST['ConversionFactor'] == '') {
		$_POST['ConversionFactor'] = 1;
	}

	echo '<field>
			<label for="ConversionFactor">', _('Conversion Factor (to our UOM)'), ':</label>
			<input type="text" class="number" name="ConversionFactor" maxlength="12" size="12" value="', $_POST['ConversionFactor'], '" />
		</field>';

	echo '<field>
			<label for="cust_part">', _('Customer Stock Code'), ':</label>
			<input type="text" name="cust_part" maxlength="20" size="20" value="', $_POST['cust_part'], '" />
		</field>';

	echo '<field>
			<label for="cust_description">', _('Customer Stock Description'), ':</label>
			<input type="text" name="cust_description" maxlength="30" size="30" value="', $_POST['cust_description'], '" />
		</field>';

	echo '</fieldset>';

	if ($Edit == true) {
		echo '<div class="centre">
				<input type="submit" name="UpdateRecord" value="', _('Update'), '" />
				<input type="hidden" name="Edit" value="1" />
			</div>';
	} else {
		echo '<div class="centre">
				<input type="submit" name="AddRecord" value="', _('Add'), '" />
			</div>';
	}
	echo '</div>
		';

	if (isset($StockLocation) and isset($StockId) and mb_strlen($StockId) != 0) {
		echo '<div class="centre">
				<a href="', $RootPath, '/StockStatus.php?StockID=', $StockId, '">', _('Show Stock Status'), '</a><br />
				<a href="', $RootPath, '/StockMovements.php?StockID=', $StockId, '&StockLocation=', $StockLocation, '">', _('Show Stock Movements'), '</a><br />
				<a href="', $RootPath, '/SelectSalesOrder.php?SelectedStockItem=', $StockId, '&StockLocation=', $StockLocation, '">', _('Search Outstanding Sales Orders'), '</a><br />
				<a href="', $RootPath, '/SelectCompletedOrder.php?SelectedStockItem=', $StockId, '">', _('Search Completed Sales Orders'), '</a><br />
			</div>';
	}
	echo '</form>';
}

include ('includes/footer.php');
?>