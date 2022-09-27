<?php
include ('includes/session.php');
$PricesSecurity = 1000; //don't show pricing info unless security token 1000 available to user
$Today = time();
$Title = _('Aged Controlled Inventory') . ' ' . _('as of') . ' ' . Date(($_SESSION['DefaultDateFormat']), $Today);
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/inventory.png" title="', _('Inventory'), '" alt="" />', $Title, '
	</p>';

$SQL = "SELECT stockserialitems.stockid,
				stockmaster.description,
				stockserialitems.serialno,
				stockserialitems.quantity,
				stockmoves.trandate,
				materialcost+labourcost+overheadcost AS cost,
				createdate,
				expirationdate,
				decimalplaces
			FROM stockserialitems
			LEFT JOIN stockserialmoves
				ON stockserialitems.serialno=stockserialmoves.serialno
			LEFT JOIN stockmoves
				ON stockserialmoves.stockmoveno=stockmoves.stkmoveno
			INNER JOIN stockmaster
				ON stockmaster.stockid = stockserialitems.stockid
			INNER JOIN locationusers
				ON locationusers.loccode=stockserialitems.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE quantity > 0
			ORDER BY createdate, quantity";
$ErrMsg = _('The stock held could not be retrieved because');
$LocStockResult = DB_query($SQL, $ErrMsg);
$NumRows = DB_num_rows($LocStockResult);

$TotalQty = 0;
$TotalVal = 0;

echo '<table>
		<thead>
			<tr>
				<th class="SortedColumn">', _('Stock'), '</th>
				<th class="SortedColumn">', _('Description'), '</th>
				<th class="SortedColumn">', _('Batch'), '</th>
				<th class="SortedColumn">', _('Quantity Remaining'), '</th>
				<th class="SortedColumn">', _('Inventory Value'), '</th>
				<th class="SortedColumn">', _('Date'), '</th>
				<th class="SortedColumn">', _('Days Old'), '</th>
			</tr>
		</thead>';

echo '<tbody>';
while ($LocQtyRow = DB_fetch_array($LocStockResult)) {

	$DaysOld = floor(($Today - strtotime($LocQtyRow['createdate'])) / (60 * 60 * 24));
	$TotalQty+= $LocQtyRow['quantity'];
	$DispVal = '-----------';
	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) or !isset($PricesSecurity)) {
		$DispVal = locale_number_format(($LocQtyRow['quantity'] * $LocQtyRow['cost']), $LocQtyRow['decimalplaces']);
		$TotalVal+= ($LocQtyRow['quantity'] * $LocQtyRow['cost']);
	}
	if ($LocQtyRow['createdate'] == '') {
		$LocQtyRow['createdate'] = $LocQtyRow['expirationdate'];
	}
	echo '<tr class="striped_row">
			<td>', mb_strtoupper($LocQtyRow['stockid']), '</td>
			<td>', $LocQtyRow['description'], '</td>
			<td>', $LocQtyRow['serialno'], '</td>
			<td class="number">', locale_number_format($LocQtyRow['quantity'], $LocQtyRow['decimalplaces']), '</td>
			<td class="number">', $DispVal, '</td>
			<td>', ConvertSQLDate($LocQtyRow['createdate']), '</td>
			<td class="number">', $DaysOld, '</td>
		</tr>';

} //while
echo '</tbody>';
echo '<tfoot>
		<tr class="total_row">
			<td colspan="3">', _('Total'), '</td>
			<td class="number">', locale_number_format($TotalQty, 2), '</td>
			<td class="number">', locale_number_format($TotalVal, 2), '</td>
			<td colspan="2"></td>
		</tr>
	</tfoot>';
echo '</table>';

include ('includes/footer.php');
?>