<?php


include ('includes/session.php');
include ('includes/PDFStarter.php');
$FontSize = 10;
$PDF->addInfo('Title', _('Top Items Search Result'));
$PageNumber = 1;
$line_height = 12;
include ('includes/PDFTopItemsHeader.inc');
$FontSize = 10;
$FromDate = FormatDateForSQL(DateAdd(Date($_SESSION['DefaultDateFormat']),'d', -$_GET['NumberOfDays']));

//the situation if the location and customer type selected "All"
if (($_GET['Location'] == 'All') AND ($_GET['Customers'] == 'All')) {
	$SQL = "SELECT 	salesorderdetails.stkcode,
				SUM(salesorderdetails.qtyinvoiced) totalinvoiced,
				SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS valuesales,
				stockmaster.description,
				stockmaster.units,
				stockmaster.decimalplaces
			FROM 	salesorderdetails, salesorders INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
			debtorsmaster,stockmaster
			WHERE 	salesorderdetails.orderno = salesorders.orderno
				AND salesorderdetails.stkcode = stockmaster.stockid
				AND salesorders.debtorno = debtorsmaster.debtorno
				AND salesorderdetails.actualdispatchdate >='" . $FromDate . "'
			GROUP BY salesorderdetails.stkcode
			ORDER BY `" . $_GET['Sequence'] . "` DESC
			LIMIT " . intval($_GET['NumberOfTopItems']) ;
} else { //the situation if only location type selected "All"
	if ($_GET['Location'] == 'All') {
		$SQL = "SELECT 	salesorderdetails.stkcode,
					SUM(salesorderdetails.qtyinvoiced) totalinvoiced,
					SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS valuesales,
					stockmaster.description,
					stockmaster.units
				FROM 	salesorderdetails, salesorders INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
				debtorsmaster,stockmaster
				WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND debtorsmaster.typeid = '" . $_GET['Customers'] . "'
						AND salesorderdetails.ActualDispatchDate >= '" . $FromDate . "'
				GROUP BY salesorderdetails.stkcode
				ORDER BY `" . $_GET['Sequence'] . "` DESC
				LIMIT " . intval($_GET['NumberOfTopItems']);
	} else {
		//the situation if the customer type selected "All"
		if ($_GET['Customers'] == 'All') {
			$SQL = "SELECT 	salesorderdetails.stkcode,
						SUM(salesorderdetails.qtyinvoiced) totalinvoiced,
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS valuesales,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM 	salesorderdetails, salesorders INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
					debtorsmaster,stockmaster
					WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND salesorders.fromstkloc = '" . $_GET['Location'] . "'
						AND salesorderdetails.ActualDispatchDate >= '" . $FromDate . "'
					GROUP BY salesorderdetails.stkcode
					ORDER BY `" . $_GET['Sequence'] . "` DESC
					LIMIT 0," . intval($_GET['NumberOfTopItems']);
		} else {
			//the situation if the location and customer type not selected "All"
			$SQL = "SELECT 	salesorderdetails.stkcode,
						SUM(salesorderdetails.qtyinvoiced) totalinvoiced,
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS valuesales,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM 	salesorderdetails, salesorders INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
					debtorsmaster,stockmaster
					WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND salesorders.fromstkloc = '" . $_GET['Location'] . "'
						AND debtorsmaster.typeid = '" . $_GET['Customers'] . "'
						AND salesorderdetails.actualdispatchdate >= '" . $FromDate . "'
					GROUP BY salesorderdetails.stkcode
					ORDER BY `" . $_GET['Sequence'] . "` DESC
					LIMIT " . intval($_GET['NumberOfTopItems']);
		}
	}
}
$result = DB_query($SQL);
if (DB_num_rows($result)>0){
	$YPos = $YPos - 6;
	while ($myrow = DB_fetch_array($result)) {
		//find the quantity onhand item
		$sqloh = "SELECT sum(quantity)as qty
					FROM locstock
					INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
					WHERE stockid='" . DB_escape_string($myrow['stkcode']) . "'";
		$oh = DB_query($sqloh);
		$ohRow = DB_fetch_row($oh);
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 1, $YPos, 80, $FontSize, $myrow['stkcode']);
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 100, $YPos, 100, $FontSize, $myrow['description']);
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 330, $YPos, 30, $FontSize, locale_number_format($myrow['totalinvoiced'],$myrow['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 300 - $Left_Margin, $FontSize, $myrow['units'], 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 400, $YPos, 70, $FontSize, locale_number_format($myrow['valuesales'], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin + 490, $YPos, 30, $FontSize, locale_number_format($ohRow[0],$myrow['decimalplaces']), 'right');
		if (mb_strlen($LeftOvers) > 1) {
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 1 + 94, $YPos - $line_height, 270, $FontSize, $LeftOvers, 'left');
			$YPos-= $line_height;
		}
		if ($YPos - $line_height <= $Bottom_Margin) {
			/* We reached the end of the page so finish off the page and start a newy */
			$PageNumber++;
			include ('includes/PDFTopItemsHeader.inc');
			$FontSize = 10;
		} //end if need a new page headed up
		/*increment a line down for the next line item */
		$YPos-= $line_height;
	}

	$PDF->OutputD($_SESSION['DatabaseName'] . '_TopItemsListing_' . date('Y-m-d').'.pdf');
	$PDF->__destruct();
}
/*end of else not PrintPDF */
?>