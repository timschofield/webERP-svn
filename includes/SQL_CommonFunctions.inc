<?php
/* Common SQL Functions */

function GetNextTransNo($TransType) {
	/* SQL to get the next transaction number these are maintained in the table SysTypes - Transaction Types
	Also updates the transaction number
	
	10 sales invoice
	11 sales credit note
	12 sales receipt
	etc
	*
	*/

	DB_query("SELECT typeno FROM systypes WHERE typeid='" . $TransType . "' FOR UPDATE");
	$SQL = "UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '" . $TransType . "'";
	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The transaction number could not be incremented');
	$DbgMsg = _('The following SQL to increment the transaction number was used');
	$UpdTransNoResult = DB_query($SQL, $ErrMsg, $DbgMsg);
	$SQL = "SELECT typeno FROM systypes WHERE typeid= '" . $TransType . "'";

	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
	$DbgMsg = _('The following SQL to retrieve the transaction number was used');
	$GetTransNoResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	$MyRow = DB_fetch_row($GetTransNoResult);

	return $MyRow[0];
}

function GetNextSupplierCode($SupplierType) {
	/* Get the next supplier number */
	$SQL = "SELECT nextsupplierno FROM suppliertype WHERE typeid='" . $SupplierType . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	/* Increase the counter */
	$SQL = "UPDATE suppliertype SET nextsupplierno='" . ($MyRow['nextsupplierno'] + 1) . "' WHERE typeid='" . $SupplierType . "'";
	$Result = DB_query($SQL);
	return $MyRow['nextsupplierno'] + 1;
}

function GetStockGLCode($StockId) {

	/*Gets the GL Codes relevant to the stock item account from the stock category record */
	$QuerySQL = "SELECT stockact,
						adjglact,
						issueglact,
						purchpricevaract,
						materialuseagevarac,
						wipact
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.stockid = '" . $StockId . "'";

	$ErrMsg = _('The stock GL codes could not be retrieved because');
	$GetStkGLResult = DB_query($QuerySQL, $ErrMsg);

	$MyRow = DB_fetch_array($GetStkGLResult);
	return $MyRow;
}

function GetTaxRate($TaxAuthority, $DispatchTaxProvince, $TaxCategory) {

	/*Gets the Tax rate applicable to an item from the TaxAuthority of the branch and TaxLevel of the item */

	$QuerySQL = "SELECT taxrate
				FROM taxauthrates
				WHERE taxauthority='" . $TaxAuthority . "'
				AND dispatchtaxprovince='" . $DispatchTaxProvince . "'
				AND taxcatid = '" . $TaxCategory . "'";

	$ErrMsg = _('The tax rate for this item could not be retrieved because');
	$GetTaxRateResult = DB_query($QuerySQL, $ErrMsg);

	if (DB_num_rows($GetTaxRateResult) == 1) {
		$MyRow = DB_fetch_row($GetTaxRateResult);
		return $MyRow[0];
	} else {
		/*The tax rate is not defined for this Tax Authority and Dispatch Tax Authority */
		return 0;
	}

}

function GetTaxes($TaxGroup, $DispatchTaxProvince, $TaxCategory) {
	$SQL = "SELECT taxgrouptaxes.calculationorder,
					taxauthorities.description,
					taxgrouptaxes.taxauthid,
					taxauthorities.taxglcode,
					taxgrouptaxes.taxontax,
					taxauthrates.taxrate
			FROM taxauthrates INNER JOIN taxgrouptaxes ON
				taxauthrates.taxauthority=taxgrouptaxes.taxauthid
				INNER JOIN taxauthorities ON
				taxauthrates.taxauthority=taxauthorities.taxid
			WHERE taxgrouptaxes.taxgroupid='" . $TaxGroup . "'
			AND taxauthrates.dispatchtaxprovince='" . $DispatchTaxProvince . "'
			AND taxauthrates.taxcatid = '" . $TaxCategory . "'
			ORDER BY taxgrouptaxes.calculationorder";

	$ErrMsg = _('The taxes and rate for this tax group could not be retrieved because');
	$GetTaxesResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($GetTaxesResult) >= 1) {
		return $GetTaxesResult;
	} else {
		/*The tax group is not defined with rates */
		return 0;
	}
}

function GetCreditAvailable($DebtorNo) {

	$SQL = "SELECT debtorsmaster.debtorno,
			debtorsmaster.creditlimit,
			SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) as balance
		FROM debtorsmaster INNER JOIN debtortrans
			ON debtorsmaster.debtorno=debtortrans.debtorno
		WHERE debtorsmaster.debtorno='" . $DebtorNo . "'
		GROUP BY debtorsmaster.debtorno,
			debtorsmaster.creditlimit";

	$ErrMsg = _('The current account balance of the customer could not be retrieved because');
	$GetAccountBalanceResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($GetAccountBalanceResult) == 1) {

		$MyRow = DB_fetch_array($GetAccountBalanceResult);
		$CreditAvailable = $MyRow['creditlimit'] - $MyRow['balance'];
	} else {
		$SQL = "SELECT creditlimit
				FROM debtorsmaster
				WHERE debtorno='" . $DebtorNo . "'";
		$GetAccountBalanceResult = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_array($GetAccountBalanceResult);
		$CreditAvailable = $MyRow['creditlimit'];
	}
	/*Take into account the value of outstanding sales orders too */
	$SQL = "SELECT SUM(salesorderdetails.unitprice *
				(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) *
				(1 - salesorderdetails.discountpercent)) AS ordervalue
				FROM salesorders INNER JOIN salesorderdetails
				ON salesorders.orderno = salesorderdetails.orderno
				WHERE salesorders.debtorno = '" . $DebtorNo . "'
				AND salesorderdetails.completed = 0
				AND salesorders.quotation = 0";

	$ErrMsg = _('The value of outstanding orders for the customer could not be retrieved because');
	$GetOSOrdersResult = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($GetOSOrdersResult);
	$CreditAvailable-= $MyRow['ordervalue'];

	return $CreditAvailable;
}

function ItemCostUpdateGL($StockID, $NewCost) {

	$SQL = "SELECT stockcosts.materialcost,
					stockcosts.labourcost,
					stockcosts.overheadcost,
					mbflag,
					sum(quantity) as totalqoh
			FROM stockmaster
			INNER JOIN locstock
				ON stockmaster.stockid=locstock.stockid
			LEFT JOIN stockcosts
				ON stockmaster.stockid=stockcosts.stockid
				AND stockcosts.succeeded=0
			WHERE stockmaster.stockid='" . $StockID . "'
			GROUP BY description,
					units,
					lastcost,
					actualcost,
					stockcosts.materialcost,
					stockcosts.labourcost,
					stockcosts.overheadcost,
					mbflag";
	$ErrMsg = _('The entered item code does not exist');
	$OldResult = DB_query($SQL, $ErrMsg);
	$OldRow = DB_fetch_array($OldResult);
	$QOH = $OldRow['totalqoh'];
	if ($OldRow['mbflag'] == 'M') {
		$OldCost = $OldRow['materialcost'] + $OldRow['labourcost'] + $OldRow['overheadcost'];
	} else {
		$OldCost = $OldRow['materialcost'];
	}
	DB_free_result($OldResult);

	if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 and $QOH != 0 and (abs($NewCost - $OldCost) > pow(10, -($_SESSION['StandardCostDecimalPlaces'] + 1)))) {
		$CostUpdateNo = GetNextTransNo(35);
		$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));
		$StockGLCode = GetStockGLCode($StockID);

		$ValueOfChange = $QOH * ($NewCost - $OldCost);

		$SQL = "INSERT INTO gltrans (type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount)
							VALUES ('35',
									'" . $CostUpdateNo . "',
									CURRENT_DATE,
									'" . $PeriodNo . "',
									'" . $StockGLCode['stockact'] . "',
									'" . $StockID . ' ' . _('cost was') . ' ' . $OldCost . ' ' . _('changed to') . ' ' . $NewCost . ' x ' . _('Quantity on hand of') . ' ' . $QOH . "',
									'" . $ValueOfChange . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The GL debit for stock cost adjustment posting could not be inserted because');
		$DbgMsg = _('The following SQL to insert the GLTrans record was used');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		$SQL = "INSERT INTO gltrans (type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount)
							VALUES ('35',
									'" . $CostUpdateNo . "',
									CURRENT_DATE,
									'" . $PeriodNo . "',
									'" . $StockGLCode['adjglact'] . "',
									'" . $StockID . ' ' . _('cost was') . ' ' . $OldCost . ' ' . _('changed to') . ' ' . $NewCost . ' x ' . _('Quantity on hand of') . ' ' . $QOH . "',
									'" . -$ValueOfChange . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The GL debit for stock cost adjustment posting could not be inserted because');
		$DbgMsg = _('The following SQL to insert the GLTrans record was used');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
	}
}

/* Calculates the material cost of a bill of materials, given parent code*/
function BomMaterialCost($Parent) {

	$SQL = "SELECT materialcost FROM stockcosts WHERE stockid='" . $Parent . "'";
	$Result1 = DB_query($SQL);
	$MyRow1 = DB_fetch_row($Result1);
	$OldCost = $MyRow1[0];
	$SQL = "SELECT sum(quantity) AS qoh FROM locstock WHERE stockid='" . $Parent . "'";
	$Result1 = DB_query($SQL);
	$MyRow1 = DB_fetch_row($Result1);
	$QOH = $MyRow1[0];
	$SQL = "SELECT Sum(stockcosts.materialcost*bom.quantity) AS SumOfmaterialcost
	   		FROM bom
	   		LEFT JOIN stockcosts
				ON bom.component = stockcosts.stockid
				AND stockcosts.succeeded=0
			WHERE bom.parent='" . $Parent . "'
				AND bom.effectiveafter <= CURRENT_DATE
				AND bom.effectiveto > CURRENT_DATE";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	$MaterialCost = $MyRow[0];
	if (abs($QOH * ($MaterialCost - $OldCost)) > 0) {
		ItemCostUpdateGL($Parent, $MaterialCost);
	}
	return $MaterialCost;
}

/*Iterates through the levels of the bom, recalculating each bom it meets*/
function UpdateCost($Item) {
	BomMaterialCost(strtoupper($Item));
	$SQL = "SELECT parent FROM bom WHERE component = '" . $Item . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$NewParent = $MyRow['parent'];
		$MaterialCost = BomMaterialCost($NewParent);
		$SQL = "SELECT stockid
					FROM stockcosts
					WHERE stockid='" . $NewParent . "'
						AND costfrom=CURRENT_TIME";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			$SQL = "INSERT INTO stockcosts VALUES ( '" . $NewParent . "',
													'" . $MaterialCost . "',
													0,
													0,
													CURRENT_TIME,
													0)";
			$Result1 = DB_query($SQL);
		} else {
			$SQL = "UPDATE stockcosts SET materialcost='" . $MaterialCost . "'
						WHERE stockid='" . $NewParent . "'
							AND costfrom=CURRENT_TIME";
			$Result1 = DB_query($SQL);
		}
		if (DB_error_no() != 0) {
			return 1;
		}
		UpdateCost($NewParent);
	}
	return 0;
}

function UpdateItemCost($Item, $MaterialCost, $LabourCost, $OverheadCost) {

	$SQL = "UPDATE stockcosts SET succeeded=1
							WHERE stockid='" . $Item . "'";
	$Result = DB_query($SQL);

	$SQL = "INSERT INTO stockcosts VALUES ('" . $Item . "',
										'" . $MaterialCost . "',
										'" . $LabourCost . "',
										'" . $OverheadCost . "',
										CURRENT_TIME,
										0)";
	$Result = DB_query($SQL);
}

/* Accepts work order information and iterates through the bom, inserting real components (dissolving phantom assemblies) */
function WoRealRequirements($WO, $LocCode, $StockId, $Qty = 1, $ParentID = '') {

	// remember, 'G' is for ghost (phantom part type)
	// all components should be referenced to the initial parent
	if ($ParentID == '') {
		$ParentID = $StockId;
	}

	// insert new real immediate components of this item
	$SQL = "INSERT INTO worequirements (wo,
				parentstockid,
				stockid,
				workcentre,
				qtypu,
				stdcost,
				autoissue)
			SELECT '" . $WO . "',
				'" . $ParentID . "',
				bom.component,
				bom.workcentreadded,
				bom.quantity*" . $Qty . ",
				stockcosts.materialcost+stockcosts.labourcost+stockcosts.overheadcost,
				bom.autoissue
			FROM bom
			INNER JOIN stockmaster
				ON bom.component=stockmaster.stockid
			LEFT JOIN stockcosts
				ON stockcosts.stockid=stockmaster.stockid
				AND stockcosts.succeeded=0
			WHERE bom.parent='" . $StockId . "'
				AND bom.loccode ='" . $LocCode . "'
				AND bom.effectiveafter <= CURRENT_DATE
				AND bom.effectiveto > CURRENT_DATE
				AND stockmaster.mbflag<>'G'
				AND bom.component NOT IN (
					SELECT stockid
					FROM worequirements
					WHERE wo = '" . $WO . "'
					AND parentstockid = '" . $ParentID . "'
					)";
	$Result = DB_query($SQL);

	// combine real immediate components of this item with other occurrences in this work order
	// otherwise, we could encounter a uniqueness violation:
	//     - the same component could occur in multiple dissolved phantom assemblies
	//     - need to sum quantities of multiple component occurrences
	if ($ParentID != $StockId) {
		$SQL = "UPDATE worequirements
					INNER JOIN (
						SELECT CAST('" . $WO . "' AS SIGNED) as wo,
							CAST('NODE-1' AS CHAR) as parentstockid,
							bom.component AS stockid,
							bom.quantity*1 AS qtypu,
							stockcosts.materialcost+stockcosts.labourcost+stockcosts.overheadcost AS stdcost,
							bom.autoissue
						FROM bom
						INNER JOIN stockmaster
							ON bom.component=stockmaster.stockid
						LEFT JOIN stockcosts
							ON stockcosts.stockid=stockmaster.stockid
							AND stockcosts.succeeded=0
						WHERE bom.parent='" . $StockId . "'
							AND bom.loccode ='" . $LocCode . "'
							AND bom.effectiveafter <= CURRENT_DATE
							AND bom.effectiveto > CURRENT_DATE
							AND stockmaster.mbflag<>'G'
							AND bom.component IN (
								SELECT stockid
								FROM worequirements
								WHERE wo = '" . $WO . "'
								AND parentstockid = '" . $ParentID . "'
							)
					) AS g ON g.wo=worequirements.wo
						AND g.parentstockid=worequirements.parentstockid
						AND g.stockid=worequirements.stockid
					SET worequirements.qtypu=worequirements.qtypu+g.qtypu";
		$Result = DB_query($SQL);
	}

	// dissolve phantom assemblies
	$SQL = "SELECT
				bom.component,
				bom.quantity
			FROM bom INNER JOIN stockmaster
			ON bom.component=stockmaster.stockid
			WHERE parent='" . $StockId . "'
			AND loccode ='" . $LocCode . "'
			AND bom.effectiveafter <= CURRENT_DATE
			AND bom.effectiveto > CURRENT_DATE
			AND stockmaster.mbflag='G'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		WoRealRequirements($WO, $LocCode, $MyRow['component'], $MyRow['quantity'], $ParentID);
	}

}

/*Ensures general ledger entries balance for a given transaction */
function EnsureGLEntriesBalance($TransType, $TransTypeNo) {

	$Result = DB_query("SELECT SUM(amount)
						FROM gltrans
						WHERE type = '" . $TransType . "'
						AND typeno = '" . $TransTypeNo . "'");
	$MyRow = DB_fetch_row($Result);
	$Difference = $MyRow[0];
	if (abs($Difference) != 0) {
		if (abs($Difference) > 0.1) {
			prnMsg(_('The general ledger entries created do not balance. See your system administrator'), 'error');
			DB_Txn_Rollback();
		} else {
			$Result = DB_query("SELECT counterindex,
										MAX(amount)
								FROM gltrans
								WHERE type = '" . $TransType . "'
								AND typeno = '" . $TransTypeNo . "'
								GROUP BY counterindex");
			$MyRow = DB_fetch_array($Result);
			$TransToAmend = $MyRow['counterindex'];
			$Result = DB_query("UPDATE gltrans SET amount = amount - " . $Difference . "
								WHERE counterindex = '" . $TransToAmend . "'");

		}
	}
}

/* 	GetQuantityOnOrderDueToPurchaseOrders return the QOO for an item due to Purchase orders (not WO).
If Location is empty, it returns the QOO for all locations
If location is NOT empty, returns the QOO for that specific location.
*/
function GetQuantityOnOrderDueToPurchaseOrders($StockId, $Location = '') {
	if ($Location == "") {
		// All locations to be considered
		$WhereLocation = "";
		$ErrMsg = _('The quantity on order due to purchase orders for') . ' ' . $StockId . ' ' . _('to be received into all locations cannot be retrieved because');
	} else {
		// Just 1 location to consider
		$WhereLocation = " AND purchorders.intostocklocation = '" . $Location . "'";
		$ErrMsg = _('The quantity on order due to purchase orders for') . ' ' . $StockId . ' ' . _('to be received into') . ' ' . $Location . ' ' . _('cannot be retrieved because');
	}

	$SQL = "SELECT SUM(purchorderdetails.quantityord -purchorderdetails.quantityrecd) AS QtyOnOrder
			FROM purchorders
			INNER JOIN purchorderdetails
				ON purchorders.orderno=purchorderdetails.orderno
			INNER JOIN locationusers
				ON locationusers.loccode=purchorders.intostocklocation
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE purchorderdetails.itemcode='" . $StockId . "'
				AND purchorderdetails.completed = 0
				AND purchorders.status<>'Cancelled'
				AND purchorders.status<>'Pending'
				AND purchorders.status<>'Rejected'
				AND purchorders.status<>'Completed'" . $WhereLocation;

	$QOOResult = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($QOOResult) == 0) {
		$QOO = 0;
	} else {
		$QOORow = DB_fetch_row($QOOResult);
		$QOO = $QOORow[0];
	}
	return $QOO;
}

/* 	GetQuantityOnOrderDueToWorkOrders return the QOO for an item due to Work orders (not PO).
If Location is empty, it returns the QOO for all locations
If location is NOT empty, returns the QOO for that specific location.
*/
function GetQuantityOnOrderDueToWorkOrders($StockId, $Location = '') {
	if ($Location == "") {
		// All locations to be considered
		$WhereLocation = "";
		$ErrMsg = _('The quantity on order due to work orders for') . ' ' . $StockId . ' ' . _('to be received into all locations cannot be retrieved because');
	} else {
		// Just 1 location to consider
		$WhereLocation = " AND workorders.loccode='" . $Location . "'";
		$ErrMsg = _('The quantity on order due to work orders for') . ' ' . $StockId . ' ' . _('to be received into') . ' ' . $Location . ' ' . _('cannot be retrieved because');
	}

	$SQL = "SELECT SUM(woitems.qtyreqd-woitems.qtyrecd) AS qtywo
			FROM woitems
			INNER JOIN workorders
				ON woitems.wo=workorders.wo
			INNER JOIN locationusers
				ON locationusers.loccode=workorders.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE workorders.closed=0
				AND woitems.stockid='" . $StockId . "'" . $WhereLocation;

	$QOOResult = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($QOOResult) == 0) {
		$QOO = 0;
	} else {
		$QOORow = DB_fetch_row($QOOResult);
		$QOO = $QOORow[0];
	}
	return $QOO;
}

/*Creates sample and testresults */
function CreateQASample($ProdSpecKey, $LotKey, $Identifier, $Comments, $Cert, $DuplicateOK) {
	$Result = DB_query("SELECT COUNT(testid) FROM prodspecs
							WHERE keyval='" . $ProdSpecKey . "'
							AND active='1'");
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		if ($DuplicateOK == 0) {
			$Result = DB_query("SELECT COUNT(sampleid) FROM qasamples
								WHERE prodspeckey='" . $ProdSpecKey . "'
								AND lotkey='" . $LotKey . "'");
			$MyRow2 = DB_fetch_row($Result);
		} else {
			$MyRow2[0] = 0;
		}
		if ($MyRow2[0] == 0 or $DuplicateOK == 1) {
			$SQL = "INSERT INTO qasamples (prodspeckey,
											lotkey,
											identifier,
											comments,
											cert,
											createdby,
											sampledate)
								VALUES('" . $ProdSpecKey . "',
										'" . $LotKey . "',
										'" . $Identifier . "',
										'" . $Comments . "',
										'" . $Cert . "',
										'" . $_SESSION['UserID'] . "',
										CURRENT_DATE)";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The create of the qasamples record failed');
			$DbgMsg = _('The following SQL to create the qasamples was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			$SampleID = DB_Last_Insert_ID('qasamples', 'sampleid');
			$SQL = "INSERT INTO sampleresults (sampleid,
											testid,
											defaultvalue,
											targetvalue,
											rangemin,
											rangemax,
											showoncert,
											showontestplan)
								SELECT '" . $SampleID . "',
											testid,
											defaultvalue,
											targetvalue,
											rangemin,
											rangemax,
											showoncert,
											showontestplan
											FROM prodspecs WHERE keyval='" . $ProdSpecKey . "'
											AND prodspecs.active='1'";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The create of the sampleresults record failed');
			$DbgMsg = _('The following SQL to create the sampleresults was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		} //$MyRow2[0]=0
		
	} //$MyRow[0]>0
	
}

function RecordExists($Table, $Field, $Value) {
	$SQL = "SELECT " . $Field . " FROM " . $Table . " WHERE " . $Field . "='" . $Value . "'";
	$Result = DB_query($SQL);
	return DB_num_rows($Result);
}

?>