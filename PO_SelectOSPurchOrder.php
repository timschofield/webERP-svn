<?php


$PricesSecurity = 12;

include('includes/session.php');

$Title = _('Search Outstanding Purchase Orders');

include('includes/header.php');
include('includes/DefinePOClass.php');

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = trim($_GET['SelectedStockItem']);
}
elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = trim($_POST['SelectedStockItem']);
}

if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
}
elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
}

if (isset($_GET['SelectedSupplier'])) {
	$SelectedSupplier = trim($_GET['SelectedSupplier']);
}
elseif (isset($_POST['SelectedSupplier'])) {
	$SelectedSupplier = trim($_POST['SelectedSupplier']);
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
	<div>
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

if (isset($OrderNumber) AND $OrderNumber != '') {
	if (!is_numeric($OrderNumber)) {
		echo '<br /><b>' . _('The Order Number entered') . ' <u>' . _('MUST') . '</u> ' . _('be numeric') . '.</b><br />';
		unset($OrderNumber);
	} else {
		echo _('Order Number') . ' - ' . $OrderNumber;
	}
} else {
	if (isset($SelectedSupplier)) {
		echo '<br />
				<div class="page_help_text">' . _('For supplier') . ': ' . $SelectedSupplier . ' ' . _('and') . ' ';
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />
				</div>';
	}
	if (isset($SelectedStockItem)) {
		echo '<input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	}
}

if (isset($_POST['SearchParts'])) {
	if (isset($_POST['Keywords']) AND isset($_POST['StockCode'])) {
		echo '<div class="page_help_text">' . _('Stock description keywords have been used in preference to the Stock code extract entered') . '.</div>';
	}
	if (isset($_POST['StockCat']) AND $_POST['StockCat'] == 'All'){
		$WhereStockCat = ' ';
	} else {
		$WhereStockCat = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					stockmaster.units,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
				FROM stockmaster INNER JOIN purchorderdetails
						ON stockmaster.stockid=purchorderdetails.itemcode
					INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				AND stockmaster.description " . LIKE . " '" . $SearchString . "'
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";


	} elseif ($_POST['StockCode']) {

		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord,
					stockmaster.units
				FROM stockmaster INNER JOIN purchorderdetails
				ON stockmaster.stockid=purchorderdetails.itemcode
				INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";

	} elseif (!$_POST['StockCode'] AND !$_POST['Keywords']) {
		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					stockmaster.units,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
				FROM stockmaster INNER JOIN purchorderdetails
				ON stockmaster.stockid=purchorderdetails.itemcode
				INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";
	}

	$ErrMsg = _('No stock items were returned by the SQL because');
	$DbgMsg = _('The SQL used to retrieve the searched parts was');
	$StockItemsResult = DB_query($SQL, $ErrMsg, $DbgMsg);
} //isset($_POST['SearchParts'])


/* Not appropriate really to restrict search by date since user may miss older ouststanding orders
$OrdersAfterDate = Date("d/m/Y",Mktime(0,0,0,Date("m")-2,Date("d"),Date("Y")));
*/

if (!isset($OrderNumber) or $OrderNumber == '') {
	if (isset($SelectedSupplier)) {
		echo '<a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes&amp;SupplierID=' . $SelectedSupplier . '">' . _('Add Purchase Order') . '</a>';
	} else {
		echo '<a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes">' . _('Add Purchase Order') . '</a>';
	}
	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';
	echo '<table class="selection">
			<tr>
				<td>' . _('Order Number') . ': <input type="text" name="OrderNumber" autofocus="autofocus" maxlength="8" size="9" />  ' . _('Into Stock Location') . ':
				<select name="StockLocation">';

	if (!isset($_POST['DateFrom'])) {
		$DateSQL = "SELECT min(orddate) as fromdate,
							max(orddate) as todate
						FROM purchorders";
		$DateResult = DB_query($DateSQL);
		$DateRow = DB_fetch_array($DateResult);
		if ($DateRow['fromdate'] != null) {
		$DateFrom = $DateRow['fromdate'];
		$DateTo = $DateRow['todate'];
	} else {
			$DateFrom = date('Y-m-d');
			$DateTo = date('Y-m-d');
		}
	} else {
		$DateFrom = FormatDateForSQL($_POST['DateFrom']);
		$DateTo = FormatDateForSQL($_POST['DateTo']);
	}

	$sql = "SELECT locations.loccode, locationname,(SELECT count(*) FROM locations) AS total FROM locations
				INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ErrMsg = _('Failed to retrieve location data');
	$resultStkLocs = DB_query($sql,$ErrMsg);
	$UserLocations = DB_num_rows($resultStkLocs);
	$AllListed = false;
	while ($myrow = DB_fetch_array($resultStkLocs)) {
		if(!isset($LocQty)){
			$LocQty = $myrow['total'];
		}
		if (isset($_POST['StockLocation'])) {//The user has selected location
			if ($_POST['StockLocation'] == 'ALLLOC'){//user have selected all locations
				if($AllListed === false) {//it's the first loop
					echo '<option selected="selected" value="ALLLOC">' . _('All') . '</option>';
					echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
					$AllListed = true;
				} else { //it's not the first loop
					echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				}

			} else {//user have not selected all locations; There are two possibilities that users have right, but not choose all; or vice visa
				if ($myrow['total'] == $UserLocations) { //user have allloc right
					if($AllListed === false){//first loop
						echo '<option value="ALLLOC">' . _('All') . '</option>';
						$AllListed = true;
					}
				}
				if ($myrow['loccode'] == $_POST['StockLocation']){
					echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				} else {
					echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				}
			}
		} else {//users have not selected locations
			if($myrow['total'] == $UserLocations){//users have right to submit All locations
				if($AllListed === false){//first loop
					echo '<option selected="selected" value="ALLLOC">' . _('All') . '</option>';//default value is all
					echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
					$AllListed = true;
				} else {//not first loop
					echo '<option value="' . $myrow['loccode'] . '" >' . $myrow['locationname'] . '</option>';
				}
			} else {//no right to submit all locations
				if ($myrow['loccode'] == $_SESSION['UserStockLocation']) {
					echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				} else {
					echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				}
			}

		}
	}
	echo '</select> ' . _('Order Status:') . ' <select name="Status">';
	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		echo '<option selected="selected" value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	} else {
		echo '<option value="Pending_Authorised">' . _('Pending and Authorised') . '</option>';
	}
	if(isset($_POST['Status'])){
		if ($_POST['Status'] == 'Pending') {
			echo '<option selected="selected" value="Pending">' . _('Pending') . '</option>';
		} else {
			echo '<option value="Pending">' . _('Pending') . '</option>';
		}
		if ($_POST['Status'] == 'Authorised') {
			echo '<option selected="selected" value="Authorised">' . _('Authorised') . '</option>';
		} else {
			echo '<option value="Authorised">' . _('Authorised') . '</option>';
		}
		if ($_POST['Status'] == 'Cancelled') {
			echo '<option selected="selected" value="Cancelled">' . _('Cancelled') . '</option>';
		} else {
			echo '<option value="Cancelled">' . _('Cancelled') . '</option>';
		}
		if ($_POST['Status'] == 'Rejected') {
			echo '<option selected="selected" value="Rejected">' . _('Rejected') . '</option>';
		} else {
			echo '<option value="Rejected">' . _('Rejected') . '</option>';
		}
	}
	$Checked = (isset($_POST['PODetails']))?'checked="checked"':'';
	echo '</select>
		' . _('Orders Between') . ':&nbsp;
			<input type="text" name="DateFrom" value="' . ConvertSQLDate($DateFrom) . '"  class="date" size="10" />
		' . _('and') . ':&nbsp;
			<input type="text" name="DateTo" value="' . ConvertSQLDate($DateTo) . '"  class="date" size="10" />
		<input type="submit" name="SearchOrders" value="' . _('Search Purchase Orders') . '" />
		</td>
		</tr>
		<tr><td>' . _('Show PO Details') . '<input type="checkbox" name="PODetails" ' . $Checked . ' /></td></tr>
		</table>';
} //!isset($OrderNumber) or $OrderNumber == ''

$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$result1 = DB_query($SQL);

echo '<br /><div class="page_help_text">' . _('To search for purchase orders for a specific part use the part selection facilities below') . '</div>';
echo '<br />
		<table class="selection">
		<tr>';

echo '<td>' . _('Select a stock category') . ':
		<select name="StockCat">';
if (DB_num_rows($result1)>0){
	echo '<option value="All">' . _('All') . '</option>';
}
while ($myrow1 = DB_fetch_array($result1)) {
	if (isset($_POST['StockCat']) and $myrow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	}
} //end loop through categories
echo '</select></td>';
echo '<td>' . _('Enter text extracts in the') .' '. '<b>' . _('description') . '</b>:</td>';
echo '<td><input type="text" name="Keywords" size="20" maxlength="25" /></td>
		</tr>
		<tr><td></td>';
echo '<td><b>' . _('OR').' '. '</b>' . _('Enter extract of the') .' '. '<b>' . _('Stock Code') . '</b>:</td>';
echo '<td><input type="text" name="StockCode" size="15" maxlength="18" /></td>
	</tr>
	</table>
	<br />';
echo '<table>
		<tr>
			<td><input type="submit" name="SearchParts" value="' . _('Search Parts Now') . '" />
				<input type="submit" name="ResetPart" value="' . _('Show All') . '" /></td>
		</tr>
	</table>';

echo '<br />';

if (isset($StockItemsResult)) {
	echo '<table cellpadding="2" class="selection">
		<thead>
		<tr>
			<th class="ascending">' . _('Code') . '</th>
			<th class="ascending">' . _('Description') . '</th>
			<th class="ascending">' . _('On Hand') . '</th>
			<th class="ascending">' . _('Orders') . '<br />' . _('Outstanding') . '</th>
			<th class="ascending">' . _('Units') . '</th>
		</tr>
		</thead>
		<tbody>';

	$StocksStr = '(';
	$q = 0;
	while ($myrow = DB_fetch_array($StockItemsResult)){
		if ($q>0) {
			$StockStr .=',';
		}
		$StockStr .="'".$myrow['stockid']."'";

	}
	$StockStr .=')';
	$QOHSQL = "SELECT stockid, sum(quantity) FROM locstock INNER JOIN locationusers ON locationusers.loccode=locationusers.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' GROUP BY stockid";
	$ErrMsg = _('Failed to retrieve qoh');
	$QOHResult = DB_query($QOHSQL,$ErrMsg);
	$QOH = array();
	while ($myrow=DB_fetch_array($QOHResult)){
		$QOH[$myrow['stockid']] = $myrow[1];
	}
	DB_data_seek($StockItemsResult,0);

	while ($myrow = DB_fetch_array($StockItemsResult)) {
		$myrow['qoh'] = $QOH[$myrow['stockid']];

		printf('<tr class="striped_row">
				<td><input type="submit" name="SelectedStockItem" value="%s"</td>
				<td>%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td>%s</td></tr>',
				$myrow['stockid'],
				$myrow['description'],
				locale_number_format($myrow['qoh'],$myrow['decimalplaces']),
				locale_number_format($myrow['qord'],$myrow['decimalplaces']),
				$myrow['units']);
	} //end of while loop through search items

	echo '</tbody></table>';

} //end if stock search results to show
else {
	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		$StatusCriteria = " AND (purchorders.status='Pending' OR purchorders.status='Authorised' OR purchorders.status='Printed') ";
	} elseif ($_POST['Status'] == 'Authorised') {
		$StatusCriteria = " AND (purchorders.status='Authorised' OR purchorders.status='Printed')";
	} elseif ($_POST['Status'] == 'Pending') {
		$StatusCriteria = " AND purchorders.status='Pending' ";
	} elseif ($_POST['Status'] == 'Rejected') {
		$StatusCriteria = " AND purchorders.status='Rejected' ";
	} elseif ($_POST['Status'] == 'Cancelled') {
		$StatusCriteria = " AND purchorders.status='Cancelled' ";
	}
	if (isset($OrderNumber) AND $OrderNumber != '') {
		$SQL = "SELECT purchorders.orderno,
						purchorders.realorderno,
						suppliers.suppname,
						purchorders.orddate,
						purchorders.deliverydate,
						purchorders.initiator,
						purchorders.status,
						purchorders.requisitionno,
						purchorders.allowprint,
						suppliers.currcode,
						currencies.decimalplaces AS currdecimalplaces,
						group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
						SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
				FROM purchorders INNER JOIN purchorderdetails
				ON purchorders.orderno=purchorderdetails.orderno
				INNER JOIN locationusers
				ON purchorders.intostocklocation=locationusers.loccode
				AND userid='" . $_SESSION['UserID'] . "' AND canview = 1
				INNER JOIN suppliers
				ON purchorders.supplierno = suppliers.supplierid
				INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				WHERE purchorderdetails.completed=0
				AND purchorders.orderno='" . $OrderNumber . "'
				GROUP BY purchorders.orderno,
					suppliers.suppname,
					purchorders.orddate,
					purchorders.status,
					purchorders.initiator,
					purchorders.requisitionno,
					purchorders.allowprint,
					suppliers.currcode
				ORDER BY purchorders.orderno ASC";
	} else {
		//$OrderNumber is not set
		if (isset($SelectedSupplier)) {
			if (!isset($_POST['StockLocation'])) {
				if (isset($UserLocations) AND isset($LocQty) AND $UserLocations == $LocQty) {
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				} else {
					$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				}
			} else {
				if ($_POST['StockLocation'] == 'ALLLOC'){
					$WhereStockLocation = ' ';
				} else {
					$WhereStockLocation = " AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "' ";
				}
			}

			if (isset($SelectedStockItem)) {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorderdetails.itemcode='" . $SelectedStockItem . "'
						AND purchorders.supplierno='" . $SelectedSupplier . "'
						" . $WhereStockLocation
						 . $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			} else {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorders.supplierno='" . $SelectedSupplier . "'
						" . $WhereStockLocation
						 . $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			}
		} //isset($SelectedSupplier)
		else { //no supplier selected
			if (!isset($_POST['StockLocation'])) {
				if (isset($UserLocations) AND isset($LocQty) AND $UserLocations == $LocQty) {
					$WhereStockLocation = " ";
					$_POST['StockLocation'] = 'ALLLOC';
				} else {
					$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				}
			} else {
				if ($_POST['StockLocation'] == 'ALLLOC'){
					$WhereStockLocation = ' ';
				} else {
					$WhereStockLocation = " AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'";
				}
			}
			if (isset($SelectedStockItem) AND isset($_POST['StockLocation'])) {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorderdetails.itemcode='" . $SelectedStockItem . "'
						" . $WhereStockLocation .
						 $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			} else {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						" . $WhereStockLocation .
						  $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			}
		} //end selected supplier
	} //end not order number selected

	$ErrMsg = _('No orders were returned by the SQL because');
	$PurchOrdersResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PurchOrdersResult) > 0) {
	/*show a table of the orders returned by the SQL */

		echo '<table cellpadding="2" width="97%" class="selection">
			<thead>';

	if (isset($_POST['PODetails'])) {
		$BalHead = '<th class="ascending">' . _('Balance') .' (' . _('Stock ID') . '--' . _('Quantity') . ' )</th>';
	} else {
		$BalHead = '';
	}
	echo '<tr>
			<th class="ascending">' . _('Order #') . '</th>
			<th class="ascending">' . _('Order Date') . '</th>
			<th class="ascending">' . _('Delivery Date') . '</th>
			<th class="ascending">' . _('Initiated by') . '</th>
			<th class="ascending">' . _('Supplier') . '</th>
			' . $BalHead . '
			<th class="ascending">' . _('Currency') . '</th>';

	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
		echo '<th class="ascending">' . _('Order Total') . '</th>';
	}
	echo '<th class="ascending">' . _('Status') . '</th>
			<th>' . _('Print') . '</th>
			<th>' . _('Receive') . '</th>
			</tr>
		</thead>
		<tbody>';

	while ($myrow = DB_fetch_array($PurchOrdersResult)) {
		$Bal = '';
		if (isset($_POST['PODetails'])) {
			//lets retrieve the PO balance here to make it a standard sql query.
			$BalSql = "SELECT itemcode, quantityord - quantityrecd as balance FROM purchorderdetails WHERE orderno = '" . $myrow['orderno'] . "'";
			$ErrMsg = _('Failed to retrieve purchorder details');
			$BalResult  = DB_query($BalSql,$ErrMsg);
			if (DB_num_rows($BalResult)>0) {
				while ($BalRow = DB_fetch_array($BalResult)) {
					$Bal .= '<br/>' . $BalRow['itemcode'] . ' -- ' . $BalRow['balance'];
				}
			}
		}
		if (isset($_POST['PODetails'])) {
			$BalRow = '<td width="250" style="word-break:break-all">' . $Bal . '</td>';
		} else {
			$BalRow = '';
		}

		$ModifyPage = $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $myrow['orderno'];
		if ($myrow['status'] == 'Printed') {
			$ReceiveOrder = '<a href="' . $RootPath . '/GoodsReceived.php?PONumber=' . $myrow['orderno'] . '">' . _('Receive') . '</a>';
		} else {
			$ReceiveOrder = '';
		}
		if ($myrow['status'] == 'Authorised' AND $myrow['allowprint'] == 1) {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $myrow['orderno'] . '">' . _('Print') . '</a>';
		} elseif ($myrow['status'] == 'Authorisied' AND $myrow['allowprint'] == 0) {
			$PrintPurchOrder = _('Printed');
		} elseif ($myrow['status'] == 'Printed') {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $myrow['orderno'] . '&amp;realorderno=' . $myrow['realorderno'] . '&amp;ViewingOnly=2">
				' . _('Print Copy') . '</a>';
		} else {
			$PrintPurchOrder = _('N/A');
		}


		$FormatedOrderDate = ConvertSQLDate($myrow['orddate']);
		$FormatedDeliveryDate = ConvertSQLDate($myrow['deliverydate']);
		$FormatedOrderValue = locale_number_format($myrow['ordervalue'], $myrow['currdecimalplaces']);
		$sql = "SELECT realname FROM www_users WHERE userid='" . $myrow['initiator'] . "'";
		$UserResult = DB_query($sql);
		$MyUserRow = DB_fetch_array($UserResult);
		$InitiatorName = $MyUserRow['realname'];

		echo '<tr class="striped_row">
			<td><a href="' . $ModifyPage . '">' . $myrow['orderno'] . '</a></td>
			<td>' . $FormatedOrderDate . '</td>
			<td>' . $FormatedDeliveryDate . '</td>
			<td>' . $InitiatorName . '</td>
			<td>' . $myrow['suppname'] . '</td>
			' . $BalRow . '
			<td>' . $myrow['currcode'] . '</td>';
		if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
			echo '<td class="number">' . $FormatedOrderValue . '</td>';
		}
		echo '<td>' . _($myrow['status']) . '</td>
				<td>' . $PrintPurchOrder . '</td>
				<td>' . $ReceiveOrder . '</td>
			</tr>';
	} //end of while loop around purchase orders retrieved

		echo '</tbody></table>';
	}
}
echo '</div>
      </form>';
include('includes/footer.php');
?>
