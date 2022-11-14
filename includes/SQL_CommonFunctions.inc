<?php


/* Common SQL Functions */


Function GetNextTransNo ($TransType){

/* SQL to get the next transaction number these are maintained in the table SysTypes - Transaction Types
Also updates the transaction number

10 sales invoice
11 sales credit note
12 sales receipt
etc
*
*/
	DB_query("SELECT typeno FROM systypes WHERE typeid='" . $TransType ."' FOR UPDATE");
	$SQL = "UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '" . $TransType . "'";
	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The transaction number could not be incremented');
	$DbgMsg =  _('The following SQL to increment the transaction number was used');
	$UpdTransNoResult = DB_query($SQL,$ErrMsg,$DbgMsg);
	$SQL = "SELECT typeno FROM systypes WHERE typeid= '" . $TransType . "'";
	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>' . _('The next transaction number could not be retrieved from the database because');
	$DbgMsg =  _('The following SQL to retrieve the transaction number was used');
	$GetTransNoResult = DB_query($SQL,$ErrMsg,$DbgMsg);
	$myrow = DB_fetch_row($GetTransNoResult);
	return $myrow[0];

}


Function GetStockGLCode ($StockID){

/*Gets the GL Codes relevant to the stock item account from the stock category record */
	$QuerySQL = "SELECT stockact,
						adjglact,
						issueglact,
						purchpricevaract,
						materialuseagevarac,
						wipact
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE stockmaster.stockid = '" . $StockID . "'";

	$ErrMsg =  _('The stock GL codes could not be retrieved because');
	$GetStkGLResult = DB_query($QuerySQL, $ErrMsg);

	$myrow = DB_fetch_array($GetStkGLResult);
	return $myrow;
}

Function GetTaxRate ($TaxAuthority, $DispatchTaxProvince, $TaxCategory){

/*Gets the Tax rate applicable to an item from the TaxAuthority of the branch and TaxLevel of the item */

	$QuerySQL = "SELECT taxrate
				FROM taxauthrates
				WHERE taxauthority='" . $TaxAuthority . "'
				AND dispatchtaxprovince='" . $DispatchTaxProvince . "'
				AND taxcatid = '" . $TaxCategory . "'";

	$ErrMsg = _('The tax rate for this item could not be retrieved because');
	$GetTaxRateResult = DB_query($QuerySQL,$ErrMsg);

	if (DB_num_rows($GetTaxRateResult)==1){
		$myrow = DB_fetch_row($GetTaxRateResult);
		return $myrow[0];
	} else {
		/*The tax rate is not defined for this Tax Authority and Dispatch Tax Authority */
		return 0;
	}

}

Function GetTaxes ($TaxGroup, $DispatchTaxProvince, $TaxCategory) {

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
	$GetTaxesResult = DB_query($SQL,$ErrMsg);

	if (DB_num_rows($GetTaxesResult)>=1){
		return $GetTaxesResult;
	} else {
		/*The tax group is not defined with rates */
		return 0;
	}
}



Function GetCreditAvailable($DebtorNo) {

	$sql = "SELECT debtorsmaster.debtorno,
			debtorsmaster.creditlimit,
			SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount - debtortrans.alloc) as balance
		FROM debtorsmaster INNER JOIN debtortrans
			ON debtorsmaster.debtorno=debtortrans.debtorno
		WHERE debtorsmaster.debtorno='" . $DebtorNo . "'
		GROUP BY debtorsmaster.debtorno,
			debtorsmaster.creditlimit";

	$ErrMsg =  _('The current account balance of the customer could not be retrieved because');
	$GetAccountBalanceResult = DB_query($sql, $ErrMsg);

	if (DB_num_rows($GetAccountBalanceResult)==1){

		$myrow = DB_fetch_array($GetAccountBalanceResult);
		$CreditAvailable = $myrow['creditlimit'] - $myrow['balance'];
	} else {
		$sql = "SELECT creditlimit
				FROM debtorsmaster
				WHERE debtorno='" . $DebtorNo . "'";
		$GetAccountBalanceResult = DB_query($sql, $ErrMsg);
		$myrow = DB_fetch_array($GetAccountBalanceResult);
		$CreditAvailable = $myrow['creditlimit'];
	}
	/*Take into account the value of outstanding sales orders too */
	$sql = "SELECT SUM(salesorderdetails.unitprice *
				(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) *
				(1 - salesorderdetails.discountpercent)) AS ordervalue
				FROM salesorders INNER JOIN salesorderdetails
				ON salesorders.orderno = salesorderdetails.orderno
				WHERE salesorders.debtorno = '" . $DebtorNo . "'
				AND salesorderdetails.completed = 0
				AND salesorders.quotation = 0";

	$ErrMsg =  _('The value of outstanding orders for the customer could not be retrieved because');
	$GetOSOrdersResult = DB_query($sql, $ErrMsg);

	$myrow = DB_fetch_array($GetOSOrdersResult);
	$CreditAvailable -= $myrow['ordervalue'];

	return $CreditAvailable;
}

function ItemCostUpdateGL($StockID, $NewCost, $OldCost, $QOH) {

	if ($_SESSION['CompanyRecord']['gllink_stock']==1
		AND $QOH!=0
		AND (abs($NewCost - $OldCost) > pow(10,-($_SESSION['StandardCostDecimalPlaces']+1)))){

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
						'" . Date('Y-m-d') . "',
						'" . $PeriodNo . "',
						'" . $StockGLCode['adjglact'] . "',
						'" . $StockID . ' ' . _('cost was') . ' ' . $OldCost . ' ' . _('changed to') . ' ' . $NewCost . ' x ' . _('Quantity on hand of') . ' ' . $QOH . "',
						'" . -$ValueOfChange . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The GL credit for the stock cost adjustment posting could not be inserted because');
		$DbgMsg = _('The following SQL to insert the GLTrans record was used');
		$Result = DB_query($SQL,$ErrMsg,$DbgMsg,true);

		$SQL = "INSERT INTO gltrans (type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount)
					VALUES ('35',
						'" . $CostUpdateNo . "',
						'" . Date('Y-m-d') . "',
						'" . $PeriodNo . "',
						'" . $StockGLCode['stockact'] . "',
						'" . $StockID . ' ' . _('cost was') . ' ' . $OldCost . ' ' . _('changed to') .' ' . $NewCost . ' x ' . _('Quantity on hand of') . ' ' . $QOH . "',
						'" . $ValueOfChange . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The GL debit for stock cost adjustment posting could not be inserted because');
		$DbgMsg = _('The following SQL to insert the GLTrans record was used');
		$Result = DB_query($SQL,$ErrMsg,$DbgMsg,true);
	}
}

/* Calculates the material cost of a bill of materials, given parent code*/
function BomMaterialCost($Parent) {
	$SQL = "SELECT materialcost FROM stockmaster WHERE stockid='" . $Parent . "'";
	$result1 = DB_query($SQL);
	$MyRow1 = DB_fetch_row($result1);
	$OldCost = $MyRow1[0];
	$SQL = "SELECT sum(quantity) as qoh from locstock where stockid='" . $Parent . "'";
	$result1 = DB_query($SQL);
	$MyRow1 = DB_fetch_row($result1);
	$QOH = $MyRow1[0];
	$SQL = "SELECT Sum(stockmaster.materialcost*bom.quantity) AS SumOfmaterialcost
	   					FROM bom LEFT JOIN stockmaster
						ON bom.component = stockmaster.stockid
						WHERE bom.parent='". $Parent . "'
                        AND bom.effectiveafter <= '" . date('Y-m-d') . "'
                        AND bom.effectiveto > '" . date('Y-m-d') . "'";
	$result = DB_query($SQL);
	$MyRow = DB_fetch_row($result);
	$MaterialCost = $MyRow[0];
	if (abs($QOH*($MaterialCost-$OldCost))>0) {
		ItemCostUpdateGL($Parent, $MaterialCost, $OldCost, $QOH);
	}
	return $MaterialCost;
}

/*Iterates through the levels of the bom, recalculating each bom it meets*/
function UpdateCost($Item) {
	//update the items cost itself first;
	BomMaterialCost(strtoupper($Item));
	$SQL = "SELECT parent FROM bom where component = '" . $Item . "'";
	$Result = DB_query($SQL);
	while ($MyRow=DB_fetch_array($Result)){
		$NewParent = $MyRow['parent'];
		$MaterialCost = BomMaterialCost($NewParent);
		$SQL = "UPDATE stockmaster SET materialcost=" . $MaterialCost . " WHERE stockid='" . $NewParent . "'";
		$result1 = DB_query($SQL);
		if (DB_error_no()!=0) {
			return 1;
		}
		UpdateCost($NewParent);
	}
	return 0;
}

/* Accepts work order information and iterates through the bom, inserting real components (dissolving phantom assemblies) */
function WoRealRequirements($WO, $LocCode, $StockID, $Qty=1, $ParentID='') {

	// remember, 'G' is for ghost (phantom part type)

	// all components should be referenced to the initial parent
	if ($ParentID == '') {
		$ParentID = $StockID;
	}

	// insert new real immediate components of this item
	$sql = "INSERT INTO worequirements (wo,
				parentstockid,
				stockid,
				qtypu,
				stdcost,
				autoissue)
			SELECT '" . $WO . "',
				'" . $ParentID . "',
				bom.component,
				bom.quantity*" . $Qty . ",
				materialcost+labourcost+overheadcost,
				bom.autoissue
			FROM bom INNER JOIN stockmaster
			ON bom.component=stockmaster.stockid
			WHERE bom.parent='" . $StockID . "'
			AND bom.loccode ='" . $LocCode . "'
            AND bom.effectiveafter <= '" . date('Y-m-d') . "'
            AND bom.effectiveto > '" . date('Y-m-d') . "'
			AND stockmaster.mbflag<>'G'
			AND bom.component NOT IN (
				SELECT stockid
				FROM worequirements
				WHERE wo = '" . $WO . "'
				AND parentstockid = '" . $ParentID . "'
			)";
	$result = DB_query($sql);

	// combine real immediate components of this item with other occurrences in this work order
	// otherwise, we could encounter a uniqueness violation:
	//     - the same component could occur in multiple dissolved phantom assemblies
	//     - need to sum quantities of multiple component occurrences
	if ($ParentID != $StockID) {
		$sql = "UPDATE worequirements
					INNER JOIN (
						SELECT CAST('" . $WO . "' AS SIGNED) as wo,
							CAST('NODE-1' AS CHAR) as parentstockid,
							bom.component AS stockid,
							bom.quantity*1 AS qtypu,
							materialcost+labourcost+overheadcost AS stdcost,
							bom.autoissue
						FROM bom INNER JOIN stockmaster
						ON bom.component=stockmaster.stockid
						WHERE bom.parent='" . $StockID . "'
						AND bom.loccode ='" . $LocCode . "'
                        AND bom.effectiveafter <= '" . date('Y-m-d') . "'
                        AND bom.effectiveto > '" . date('Y-m-d') . "'
						AND stockmaster.mbflag<>'G'
						AND bom.component IN (
							SELECT stockid
							FROM worequirements
							WHERE wo = '" . $WO . "'
							AND parentstockid = '". $ParentID . "'
						)
					) AS g ON g.wo=worequirements.wo
						AND g.parentstockid=worequirements.parentstockid
						AND g.stockid=worequirements.stockid
					SET worequirements.qtypu=worequirements.qtypu+g.qtypu";
		$result = DB_query($sql);
	}

	// dissolve phantom assemblies
	$sql = "SELECT
				bom.component,
				bom.quantity
			FROM bom INNER JOIN stockmaster
			ON bom.component=stockmaster.stockid
			WHERE parent='" . $StockID . "'
			AND loccode ='" . $LocCode . "'
            AND bom.effectiveafter <= '" . date('Y-m-d') . "'
            AND bom.effectiveto > '" . date('Y-m-d') . "'
			AND stockmaster.mbflag='G'";
	$result = DB_query($sql);
	while ($MyRow=DB_fetch_array($result)) {
		WoRealRequirements($WO, $LocCode, $MyRow['component'], $MyRow['quantity'], $ParentID);
	}

}

/*Ensures general ledger entries balance for a given transaction */
function EnsureGLEntriesBalance ($TransType, $TransTypeNo) {

	$result = DB_query("SELECT SUM(amount)
						FROM gltrans
						WHERE type = '" . $TransType . "'
						AND typeno = '" . $TransTypeNo . "'");
	$myrow = DB_fetch_row($result);
	$Difference = $myrow[0];
	if (abs($Difference)!=0){
		if (abs($Difference)>0.1){
			prnMsg(_('The general ledger entries created do not balance. See your system administrator'),'error');
			DB_Txn_Rollback();
		} else {
			$result = DB_query("SELECT counterindex,
										MAX(amount)
								FROM gltrans
								WHERE type = '" . $TransType . "'
								AND typeno = '" . $TransTypeNo . "'
								GROUP BY counterindex");
			$myrow = DB_fetch_array($result);
			$TransToAmend = $myrow['counterindex'];
			$result = DB_query("UPDATE gltrans SET amount = amount - " . $Difference . "
								WHERE counterindex = '" . $TransToAmend . "'");

		}
	}
}

/* 	GetQuantityOnOrderDueToPurchaseOrders return the QOO for an item due to Purchase orders (not WO).
	If Location is empty, it returns the QOO for all locations
	If location is NOT empty, returns the QOO for that specific location.
*/
function GetQuantityOnOrderDueToPurchaseOrders($StockID, $Location = ""){
	if ($Location == ""){
		// All locations to be considered
		$WhereLocation = "";
		$ErrMsg = _('The quantity on order due to purchase orders for') . ' ' . $StockID . ' ' . _('to be received into all locations cannot be retrieved because');
	}else{
		// Just 1 location to consider
		$WhereLocation = " AND purchorders.intostocklocation = '" . $Location . "'";
		$ErrMsg = _('The quantity on order due to purchase orders for') . ' ' . $StockID . ' ' . _('to be received into') . ' ' . $Location . ' ' . _('cannot be retrieved because');
	}

	$SQL="SELECT SUM(purchorderdetails.quantityord -purchorderdetails.quantityrecd) AS QtyOnOrder
		FROM purchorders
			INNER JOIN purchorderdetails
				ON purchorders.orderno=purchorderdetails.orderno
			INNER JOIN locationusers
				ON locationusers.loccode=purchorders.intostocklocation
					AND locationusers.userid='" .  $_SESSION['UserID'] . "'
					AND locationusers.canview=1
		WHERE purchorderdetails.itemcode='" . $StockID . "'
			AND purchorderdetails.completed = 0
			AND purchorders.status<>'Cancelled'
			AND purchorders.status<>'Pending'
			AND purchorders.status<>'Rejected'
			AND purchorders.status<>'Completed'" .
			$WhereLocation;

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
function GetQuantityOnOrderDueToWorkOrders($StockID, $Location=""){
	if ($Location == ''){
		// All locations to be considered
		$WhereLocation = '';
		$ErrMsg = _('The quantity on order due to work orders for') . ' ' . $StockID . ' ' . _('to be received into all locations cannot be retrieved because');
	}else{
		// Just 1 location to consider
		$WhereLocation = " AND workorders.loccode='" . $Location . "'";
		$ErrMsg = _('The quantity on order due to work orders for') . ' ' . $StockID . ' ' . _('to be received into') . ' ' . $Location . ' ' . _('cannot be retrieved because');
	}

	$SQL="SELECT SUM(woitems.qtyreqd-woitems.qtyrecd) AS qtywo
		FROM woitems
			INNER JOIN workorders
				ON woitems.wo=workorders.wo
			INNER JOIN locationusers
				ON locationusers.loccode=workorders.loccode
					AND locationusers.userid='" .  $_SESSION['UserID'] . "'
					AND locationusers.canview=1
		WHERE workorders.closed=0
			AND woitems.stockid='" . $StockID . "'" .
			$WhereLocation;

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
function CreateQASample ($ProdSpecKey, $LotKey, $Identifier, $Comments, $Cert, $DuplicateOK) {
	$result = DB_query("SELECT COUNT(testid) FROM prodspecs
							WHERE keyval='" . $ProdSpecKey . "'
							AND active='1'");
	$myrow = DB_fetch_row($result);
	if ($myrow[0]>0) {
		if ($DuplicateOK==0) {
			$result = DB_query("SELECT COUNT(sampleid) FROM qasamples
								WHERE prodspeckey='" . $ProdSpecKey . "'
								AND lotkey='" . $LotKey ."'");
			$myrow2 = DB_fetch_row($result);
		} else {
			$myrow2[0]=0;
		}
		if ($myrow2[0]==0 OR $DuplicateOK==1) {
			$SQL = "INSERT INTO qasamples (prodspeckey,
											lotkey,
											identifier,
											comments,
											cert,
											createdby,
											sampledate)
								VALUES('" . $ProdSpecKey . "',
										'" . $LotKey  . "',
										'" . $Identifier  . "',
										'" . $Comments  . "',
										'" . $Cert  . "',
										'" . $_SESSION['UserID'] . "',
										'" . date('Y-m-d') . "')";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The create of the qasamples record failed');
			$DbgMsg = _('The following SQL to create the qasamples was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			$SampleID=DB_Last_Insert_ID('qasamples','sampleid');
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
											FROM prodspecs WHERE keyval='" .$ProdSpecKey. "'
											AND prodspecs.active='1'";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The create of the sampleresults record failed');
			$DbgMsg = _('The following SQL to create the sampleresults was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		} //$myrow2[0]=0
	} //$myrow[0]>0
}

?>