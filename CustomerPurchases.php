<?php

include('includes/session.php');
$Title = _('Customer Purchases');// Screen identificator.
$ViewTopic = 'ARInquiries';// Filename's id in ManualContents.php's TOC.
/* This help needs to be writing...
$BookMark = 'CustomerPurchases';// Anchor's id in the manual's html document.*/
include('includes/header.php');

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = stripslashes($_GET['DebtorNo']);
} //isset($_GET['DebtorNo'])
else if (isset($_POST['DebtorNo'])) {
	$DebtorNo = stripslashes($_POST['DebtorNo']);
} //isset($_POST['DebtorNo'])
else {
	prnMsg(_('This script must be called with a customer code.'), 'info');
	include('includes/footer.php');
	exit;
}

$SQL = "SELECT debtorsmaster.name,
				custbranch.brname
		FROM debtorsmaster
		INNER JOIN custbranch
			ON debtorsmaster.debtorno=custbranch.debtorno
		WHERE debtorsmaster.debtorno = '" . $DebtorNo . "'";

$ErrMsg = _('The customer details could not be retrieved by the SQL because');
$CustomerResult = DB_query($SQL, $ErrMsg);
$CustomerRecord = DB_fetch_array($CustomerResult);

echo '<div class="toplink"><a href="SelectCustomer.php">', _('Return to customer selection screen'), '</a></div>';

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" /> ', _('Items Purchased by Customer'), ' : ', $CustomerRecord['name'], '
	</p>';

$SQLWhere = '';
$SQL = "SELECT stockmoves.stockid,
				stockmaster.description,
				stockmaster.units,
				systypes.typename,
				transno,
				locations.locationname,
				trandate,
				branchcode,
				price,
				reference,
				qty,
				discountpercent,
				narrative
			FROM stockmoves
			INNER JOIN stockmaster
				ON stockmaster.stockid=stockmoves.stockid
			INNER JOIN systypes
				ON stockmoves.type=systypes.typeid
			INNER JOIN locations
				ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers
				ON locationusers.loccode=locations.loccode
				AND locationusers.userid='" .  $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE debtorno='" . $DebtorNo . "'";
	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " INNER JOIN custbranch
					ON stockmoves.branchcode=custbranch.branchcode";
		$SQLWhere = " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL .= $SQLWhere . " ORDER BY trandate DESC";
$ErrMsg = _('The stock movement details could not be retrieved by the SQL because');
$StockMovesResult = DB_query($SQL, $ErrMsg);

if (DB_num_rows($StockMovesResult) == 0) {
	prnMsg(_('There are no items for this customer'), 'notice');
} //DB_num_rows($StockMovesResult) == 0
else {
	echo '<table>
			<tr>
				<th>', _('Transaction Date'), '</th>
				<th>', _('Stock ID'), '</th>
				<th>', _('Description'), '</th>
				<th>', _('Type'), '</th>
				<th>', _('Transaction No.'), '</th>
				<th>', _('From Location'), '</th>
				<th>', _('Branch Code'), '</th>
				<th>', _('Quantity'), '</th>
				<th>', _('Unit'), '</th>
				<th>', _('Price'), '</th>
				<th>', _('Discount'), '</th>
				<th>', _('Total'), '</th>
				<th>', _('Reference'), '</th>
				<th>', _('Narrative'), '</th>
			</tr>';

	while ($StockMovesRow = DB_fetch_array($StockMovesResult)) {
		echo '<tr class="striped_row">
				<td>', ConvertSQLDate($StockMovesRow['trandate']), '</td>
				<td>', $StockMovesRow['stockid'], '</td>
				<td>', $StockMovesRow['description'], '</td>
				<td>', _($StockMovesRow['typename']), '</td>
				<td class="number">', $StockMovesRow['transno'], '</td>
				<td>', $StockMovesRow['locationname'], '</td>
				<td>', $StockMovesRow['branchcode'], '</td>
				<td class="number">', -$StockMovesRow['qty'], '</td>
				<td>', $StockMovesRow['units'], '</td>
				<td class="number">', locale_number_format($StockMovesRow['price'] * (1 - $StockMovesRow['discountpercent']), $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format(($StockMovesRow['discountpercent'] * 100), 2), '%', '</td>
				<td class="number">', locale_number_format((-$StockMovesRow['qty'] * $StockMovesRow['price'] * (1 - $StockMovesRow['discountpercent'])), $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', $StockMovesRow['reference'], '</td>
				<td>', $StockMovesRow['narrative'], '</td>
			</tr>';

	} //$StockMovesRow = DB_fetch_array($StockMovesResult)

	echo '</table>';
}

include('includes/footer.php');
?>