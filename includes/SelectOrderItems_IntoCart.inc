<?php


/*SelectOrderItems_IntoCart.inc

This snippet is used to enter order line items into the cart object:

Used only in: SelectOrderItems.php

The reason that it is in this seperate file is because it is used within a loop to get kitset
items into the cart as well as plain vanilla items outside of the kitset loop
*/

$AlreadyOnThisOrder =0;

if (count($_SESSION['Items'.$Identifier]->LineItems)>0
	AND $_SESSION['SO_AllowSameItemMultipleTimes']==0){

	foreach ($_SESSION['Items'.$Identifier]->LineItems AS $OrderItem) {

	/* do a loop round the items on the order to see that the item
	is not already on this order */

		if (strcasecmp($OrderItem->StockID, $NewItem)==0) {
			$AlreadyOnThisOrder = 1;
			prnMsg(_('The item') . ' ' . $NewItem . ' ' . _('is already on this order the system is set up to prevent the same item being on the order more than once. However you can change the quantity ordered of the existing line if necessary'));
       		}
	} /* end of the foreach loop to look for preexisting items of the same code */
}

if ($AlreadyOnThisOrder!=1){

    $sql = "SELECT stockmaster.description,
					stockmaster.longdescription,
					stockmaster.stockid,
					stockmaster.units,
					stockmaster.volume,
					stockmaster.grossweight,
					(materialcost+labourcost+overheadcost) AS standardcost,
					locstock.quantity,
					stockmaster.mbflag,
					stockmaster.discountcategory,
					stockmaster.decimalplaces,
					stockmaster.discontinued,
					stockmaster.serialised,
					stockmaster.eoq,
					stockmaster.nextserialno,
					stockmaster.taxcatid
			FROM stockmaster INNER JOIN locstock
			ON stockmaster.stockid=locstock.stockid
			WHERE locstock.loccode='" . $_SESSION['Items'.$Identifier]->Location . "'
			AND stockmaster.stockid = '". $NewItem . "'";

    $ErrMsg = _('The details for') . ' ' . $NewItem . ' ' . _('could not be retrieved because');
    $DbgMsg = _('The SQL used to retrieve the pricing details but failed was');
    $result1 = DB_query($sql,$ErrMsg,$DbgMsg);

    if (DB_num_rows($result1)==0){
		prnMsg(_('The item code') . ' ' . $NewItem  . ' '  . _('could not be found in the database') . ' - ' . _('it has not been added to the order'),'warn',_('Item Does Not Exist'));

    } elseif ($myItemRow = DB_fetch_array($result1)){

    	if ($myItemRow['discontinued']==1){
			prnMsg(_('The item') . ' ' . $NewItem . ' ' . _('could not be added to the order because it has been flagged as obsolete'),'error',_('Obsolete Item'));

		} elseif (($_SESSION['AllowSalesOfZeroCostItems'] == false
						AND $myItemRow['standardcost']>0
						AND ($myItemRow['mbflag']=='B'
						OR $myItemRow['mbflag']=='M'))
					OR ($_SESSION['AllowSalesOfZeroCostItems'] == false
						AND ($myItemRow['mbflag']=='A'
						OR $myItemRow['mbflag']=='D'
						OR $myItemRow['mbflag']=='K'))
					OR $_SESSION['AllowSalesOfZeroCostItems']==true) {

		/*these checks above ensure that the item has a cost if the
		config.php variable AllowSalesOfZeroCostItems is set to false */

		   	if ($_SESSION['ExistingOrder' . $Identifier]!=0){
				$UpdateDB = 'Yes';
				$result = DB_query("SELECT MAX(orderlineno) AS newlineno FROM salesorderdetails WHERE orderno='" . $_SESSION['ExistingOrder' . $Identifier] . "'");
				$MaxNumRow = DB_fetch_row($result);
				if ($MaxNumRow[0] != '' AND $MaxNumRow[0] >= 0) {
					$NewLineNo = $MaxNumRow[0]+1;
				} else {
					$NewLineNo = 0;
				}
			} else {
				$UpdateDB = 'No';
				$NewLineNo = -1; /* this is ok b/c CartClass will change to the correct line no */
			}

			if (isset($StockItem) AND $myItemRow['discountcategory'] != '' ){
				$DiscCatsDone[$Counter]=$StockItem->DiscCat;
				$QuantityOfDiscCat =0;
				$result = DB_query("SELECT MAX(discountrate) AS discount
										FROM discountmatrix
										WHERE salestype='" .  $_SESSION['Items'.$Identifier]->DefaultSalesType . "'
										AND discountcategory ='" . $myrow['discountcategory'] . "'
										AND quantitybreak <" . $NewItemQty);
				$DiscCatRow = DB_fetch_row($result);
				if ($DiscCatRow[0] != '' AND $DiscCatRow[0] > 0) {
					$Discount = $DiscCatRow[0];
				} else {
					$Discount = 0;
				}
			} elseif (!isset($Discount)){
				$Discount = 0;
			}

			$Price = GetPrice($NewItem, $_SESSION['Items'.$Identifier]->DebtorNo,$_SESSION['Items'.$Identifier]->Branch, $NewItemQty);

			/* Need to check for sell through support deals */

			$sql = "SELECT rebateamount/rate AS rebateinfunctionalcurrency,
							rebatepercent
					FROM sellthroughsupport INNER JOIN suppliers
						ON suppliers.supplierid=sellthroughsupport.supplierno
					INNER JOIN currencies
						ON currencies.currabrev=suppliers.currcode
					WHERE stockid='" . $NewItem . "'
					AND (debtorno='" . $_SESSION['Items'.$Identifier]->DebtorNo . "'
						OR debtorno='')
					AND effectivefrom <='" . Date('Y-m-d') . "'
					AND effectiveto >='" . Date('Y-m-d') . "'";
			$SellSuppResult = DB_query($sql);
			$SellSupportDiscount = 0;
			$SellSupportPercent = 0;
			/*Only the highest discount is taken - it is not accumulated ? */
			if (DB_num_rows($SellSuppResult) > 0){
				while ($SellSupportRow = DB_fetch_array($SellSuppResult)){
					if ($SellSupportRow['rebatepercent'] > $SellSupportPercent){
						$SellSupportPercent = $SellSupportRow['rebatepercent'];
					}
					if ($SellSupportRow['rebateinfunctionalcurrency'] > $SellSupportAmount){
						$SellSupportDiscount = $SellSupportRow['rebateinfunctionalcurrency'];
					}
				}
			}

			/* Sell support can also be specified by stock category */

			$sql = "SELECT rebateamount/rate AS rebateinfunctionalcurrency,
							rebatepercent
					FROM sellthroughsupport INNER JOIN stockmaster
					ON sellthroughsupport.categoryid=stockmaster.categoryid
					INNER JOIN suppliers
					ON suppliers.supplierid=sellthroughsupport.supplierno
					INNER JOIN currencies
					ON currencies.currabrev=suppliers.currcode
					WHERE stockmaster.stockid='" . $NewItem . "'
					AND (debtorno='" . $_SESSION['Items'.$Identifier]->DebtorNo . "'
						OR debtorno='')
					AND effectivefrom <='" . Date('Y-m-d') . "'
					AND effectiveto >='" . Date('Y-m-d') . "'";
			$SellSuppResult = DB_query($sql);
			/*Only the highest discount is taken - it is not accumulated ? */
			if (DB_num_rows($SellSuppResult) > 0){
				while ($SellSupportRow = DB_fetch_array($SellSuppResult)){
					if ($SellSupportRow['rebatepercent'] > $SellSupportPercent){
						$SellSupportPercent = $SellSupportRow['rebatepercent'];
					}
					if ($SellSupportRow['rebateinfunctionalcurrency'] > $SellSupportDiscount){
						$SellSupportDiscount = $SellSupportRow['rebateinfunctionalcurrency'];
					}
				}
			}
			if ($_SESSION['Items'.$Identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault'] AND $SellSupportDiscount != 0) {
				/* Customer currency is not the same as the functional/home currency so need to convert the rebate amount */
				/* Would be better to have the currency rate held in the cart object so only have to run this query once - this is a hack for this purpose! */
				$CurrResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items'.$Identifier]->DefaultCurrency . "'");
				$CurrRow = DB_fetch_array($CurrResult);
				$SellSupportDiscount *= $CurrRow['rate'];
			}
			/*Convert the Sell through support discount to a percentage and add it to the $SellSupportPercent */
			if ($Price > 0){
				$SellSupportPercent += ($SellSupportDiscount/$Price);
			}
			if ($SellSupportPercent >0){
				$Discount += $SellSupportPercent; //combine any discount matrix discount with the sell through support.
				prnMsg(_('Sell through support available and applied of') . ' ' . locale_number_format($SellSupportPercent*100,2), 'info');
			}

			$WithinCreditLimit = true;

			if ($_SESSION['Items'.$Identifier]->SpecialInstructions) {
			  	prnMsg($_SESSION['Items'.$Identifier]->SpecialInstructions,'warn');
            }
			if ($_SESSION['CheckCreditLimits'] > 0 AND $AlreadyWarnedAboutCredit==false){
				/*Check credit limits is 1 for warn	and 2 for prohibit sales */
				$_SESSION['Items'.$Identifier]->CreditAvailable -= round(($NewItemQty * $Price * (1- $Discount)),$_SESSION['Items'.$Identifier]->CurrDecimalPlaces);

				if ($_SESSION['CheckCreditLimits']==1 AND $_SESSION['Items'.$Identifier]->CreditAvailable <=0){
					prnMsg(_('The customer account will breach their credit limit'),'warn');
					$AlreadyWarnedAboutCredit = true;
				} elseif ($_SESSION['CheckCreditLimits']==2 AND $_SESSION['Items'.$Identifier]->CreditAvailable <=0){
					prnMsg(_('No more lines can be added to this order the customer account is currently at or over their credit limit'),'warn');
					$WithinCreditLimit = false;
					$AlreadyWarnedAboutCredit = true;
				}
			}


			if ($WithinCreditLimit == true){
				$_SESSION['Items'.$Identifier]->add_to_cart ($NewItem,
															$NewItemQty,
															$myItemRow['description'],
															$myItemRow['longdescription'],
															$Price,
															$Discount,
															$myItemRow['units'],
															$myItemRow['volume'],
															$myItemRow['grossweight'],
															$myItemRow['quantity'],
															$myItemRow['mbflag'],
															NULL, /*Actual Dispatch Date */
															0, /*Qty Invoiced */
															$myItemRow['discountcategory'],
															0, /*Controlled - dont care */
															$myItemRow['serialised'], /* need to know for autocreation wos */
															$myItemRow['decimalplaces'],
															'', /*Narrative - none yet */
															$UpdateDB,
															$NewLineNo,
															$myItemRow['taxcatid'],
															'',
															$NewItemDue,
															$NewPOLine,
															$myItemRow['standardcost'],
															$myItemRow['eoq'],
															$myItemRow['nextserialno'],
															$ExRate,
															$Identifier);

			}
         } else {
			prnMsg(_('The item code') . ' ' . $NewItem . ' ' . _('does not have a cost set up and order entry is set up to prohibit sales of items with no cost data entered'),'warn');
	     }
	}
} /* end of if not already on the order */
?>