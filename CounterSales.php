<?php
include ('includes/DefineCartClass.php');

/* Session started in session.php for password checking and authorisation level check
 config.php is in turn included in session.php $PageSecurity now comes from session.php (and gets read in by GetConfig.php*/

include ('includes/session.php');
$Title = _('Counter Sales');
/* Manual links before header.php */
$ViewTopic = 'SalesOrders';
$BookMark = 'SalesOrderCounterSales';

include ('includes/header.php');
include ('includes/GetPrice.inc');
include ('includes/SQL_CommonFunctions.inc');
include ('includes/GetSalesTransGLCodes.inc');

$AlreadyWarnedAboutCredit = false;

if (empty($_GET['identifier'])) {
	$Identifier = date('U');
} else {
	$Identifier = $_GET['identifier'];
}
if (isset($_SESSION['Items' . $Identifier]) and isset($_POST['CustRef'])) {
	//update the Items object variable with the data posted from the form
	$_SESSION['Items' . $Identifier]->CustRef = $_POST['CustRef'];
	$_SESSION['Items' . $Identifier]->Comments = $_POST['Comments'];
	$_SESSION['Items' . $Identifier]->DeliverTo = $_POST['DeliverTo'];
	$_SESSION['Items' . $Identifier]->PhoneNo = $_POST['PhoneNo'];
	$_SESSION['Items' . $Identifier]->Email = $_POST['Email'];
	if ($_SESSION['SalesmanLogin'] != '') {
		$_SESSION['Items' . $Identifier]->SalesPerson = $_SESSION['SalesmanLogin'];
	} else {
		$_SESSION['Items' . $Identifier]->SalesPerson = $_POST['SalesPerson'];
	}
}

if (isset($_POST['QuickEntry'])) {
	unset($_POST['PartSearch']);
}

if (isset($_POST['SelectingOrderItems'])) {
	foreach ($_POST as $FormVariable => $Quantity) {
		if (mb_strpos($FormVariable, 'OrderQty') !== false) {
			$NewItemArray[$_POST['StockID' . mb_substr($FormVariable, 8) ]] = filter_number_format($Quantity);
		}
	}
}

if (isset($_GET['NewItem'])) {
	$NewItem = trim($_GET['NewItem']);
}

if (isset($_GET['NewOrder'])) {
	/*New order entry - clear any existing order details from the Items object and initiate a newy*/
	if (isset($_SESSION['Items' . $Identifier])) {
		unset($_SESSION['Items' . $Identifier]->LineItems);
		$_SESSION['Items' . $Identifier]->ItemsOrdered = 0;
		unset($_SESSION['Items' . $Identifier]);
	}
}

if (!isset($_SESSION['Items' . $Identifier])) {
	/* It must be a new order being created $_SESSION['Items'.$Identifier] would be set up from the order
	modification code above if a modification to an existing order. Also $ExistingOrder would be
	set to 1. The delivery check screen is where the details of the order are either updated or
	inserted depending on the value of ExistingOrder */

	$_SESSION['ExistingOrder' . $Identifier] = 0;
	$_SESSION['Items' . $Identifier] = new cart;
	$_SESSION['PrintedPackingSlip'] = 0;
	/*Of course 'cos the order ain't even started !!*/
	/*Get the default customer-branch combo from the user's default location record */
	$SQL = "SELECT cashsalecustomer,
				cashsalebranch,
				locationname,
				taxprovinceid
			FROM locations
			WHERE loccode='" . $_SESSION['UserStockLocation'] . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		prnMsg(_('Your user account does not have a valid default inventory location set up. Please see the system administrator to modify your user account.'), 'error');
		include ('includes/footer.php');
		exit;
	} else {
		$MyRow = DB_fetch_array($Result); //get the only row returned
		if ($MyRow['cashsalecustomer'] == '' or $MyRow['cashsalebranch'] == '') {
			prnMsg(_('To use this script it is first necessary to define a cash sales customer for the location that is your default location. The default cash sale customer is defined under set up ->Inventory Locations Maintenance. The customer should be entered using the customer code and a valid branch code of the customer entered.'), 'info');
			include ('includes/footer.php');
			exit;
		}
		if (isset($_GET['DebtorNo'])) {
			$_SESSION['Items' . $Identifier]->DebtorNo = $_GET['DebtorNo'];
			$_SESSION['Items' . $Identifier]->Branch = $_GET['BranchNo'];
		} else {
			$_SESSION['Items' . $Identifier]->Branch = $MyRow['cashsalebranch'];
			$_SESSION['Items' . $Identifier]->DebtorNo = $MyRow['cashsalecustomer'];
		}

		$_SESSION['Items' . $Identifier]->LocationName = $MyRow['locationname'];
		$_SESSION['Items' . $Identifier]->Location = $_SESSION['UserStockLocation'];
		$_SESSION['Items' . $Identifier]->DispatchTaxProvince = $MyRow['taxprovinceid'];

		// Now check to ensure this account exists and set defaults */
		$SQL = "SELECT debtorsmaster.name,
					holdreasons.dissallowinvoices,
					debtorsmaster.salestype,
					salestypes.sales_type,
					debtorsmaster.currcode,
					debtorsmaster.customerpoline,
					paymentterms.terms,
					currencies.decimalplaces
				FROM debtorsmaster INNER JOIN holdreasons
				ON debtorsmaster.holdreason=holdreasons.reasoncode
				INNER JOIN salestypes
				ON debtorsmaster.salestype=salestypes.typeabbrev
				INNER JOIN paymentterms
				ON debtorsmaster.paymentterms=paymentterms.termsindicator
				INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
				WHERE debtorsmaster.debtorno = '" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'";

		$ErrMsg = _('The details of the customer selected') . ': ' . $_SESSION['Items' . $Identifier]->DebtorNo . ' ' . _('cannot be retrieved because');
		$DbgMsg = _('The SQL used to retrieve the customer details and failed was') . ':';
		// echo $SQL;
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		$MyRow = DB_fetch_array($Result);
		if ($MyRow['dissallowinvoices'] != 1) {
			if ($MyRow['dissallowinvoices'] == 2) {
				prnMsg($MyRow['name'] . ' ' . _('Although this account is defined as the cash sale account for the location.  The account is currently flagged as an account that needs to be watched. Please contact the credit control personnel to discuss'), 'warn');
			}

			$_SESSION['RequireCustomerSelection'] = 0;
			$_SESSION['Items' . $Identifier]->CustomerName = $MyRow['name'];
			// the sales type is the price list to be used for this sale
			$_SESSION['Items' . $Identifier]->DefaultSalesType = $MyRow['salestype'];
			$_SESSION['Items' . $Identifier]->SalesTypeName = $MyRow['sales_type'];
			$_SESSION['Items' . $Identifier]->DefaultCurrency = $MyRow['currcode'];
			$_SESSION['Items' . $Identifier]->DefaultPOLine = $MyRow['customerpoline'];
			$_SESSION['Items' . $Identifier]->PaymentTerms = $MyRow['terms'];
			$_SESSION['Items' . $Identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
			/* now get the branch defaults from the customer branches table CustBranch. */

			$SQL = "SELECT custbranch.brname,
					   custbranch.braddress1,
					   custbranch.defaultshipvia,
					   custbranch.deliverblind,
					   custbranch.specialinstructions,
					   custbranch.estdeliverydays,
					   custbranch.salesman,
					   custbranch.taxgroupid,
					   custbranch.defaultshipvia
				FROM custbranch
				WHERE custbranch.branchcode='" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "'
				AND custbranch.debtorno = '" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'
				AND custbranch.disabletrans = 0";
			$ErrMsg = _('The customer branch record of the customer selected') . ': ' . $_SESSION['Items' . $Identifier]->Branch . ' ' . _('cannot be retrieved because');
			$DbgMsg = _('SQL used to retrieve the branch details was') . ':';
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

			if (DB_num_rows($Result) == 0) {

				prnMsg(_('The branch details for branch code') . ': ' . $_SESSION['Items' . $Identifier]->Branch . ' ' . _('against customer code') . ': ' . $_SESSION['Items' . $Identifier]->DebtorNo . ' ' . _('could not be retrieved') . '. ' . _('Check the set up of the customer and branch'), 'error');

				if ($Debug == 1) {
					echo '<br />', _('The SQL that failed to get the branch details was'), ':<br />', $SQL;
				}
				include ('includes/footer.php');
				exit;
			}
			// add echo
			$MyRow = DB_fetch_array($Result);

			$_SESSION['Items' . $Identifier]->DeliverTo = '';
			$_SESSION['Items' . $Identifier]->DelAdd1 = $MyRow['braddress1'];
			$_SESSION['Items' . $Identifier]->ShipVia = $MyRow['defaultshipvia'];
			$_SESSION['Items' . $Identifier]->DeliverBlind = $MyRow['deliverblind'];
			$_SESSION['Items' . $Identifier]->SpecialInstructions = $MyRow['specialinstructions'];
			$_SESSION['Items' . $Identifier]->DeliveryDays = $MyRow['estdeliverydays'];
			$_SESSION['Items' . $Identifier]->TaxGroup = $MyRow['taxgroupid'];
			$_SESSION['Items' . $Identifier]->SalesPerson = $MyRow['salesman'];

			if ($_SESSION['Items' . $Identifier]->SpecialInstructions) {
				prnMsg($_SESSION['Items' . $Identifier]->SpecialInstructions, 'warn');
			}

			if ($_SESSION['CheckCreditLimits'] > 0 and $AlreadyWarnedAboutCredit == false) {
				/*Check credit limits is 1 for warn and 2 for prohibit sales */
				$_SESSION['Items' . $Identifier]->CreditAvailable = GetCreditAvailable(DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo));

				if ($_SESSION['CheckCreditLimits'] == 1 and $_SESSION['Items' . $Identifier]->CreditAvailable <= 0) {
					prnMsg(_('The') . ' ' . $MyRow['brname'] . ' ' . _('account is currently at or over their credit limit'), 'warn');
					$AlreadyWarnedAboutCredit = true;
				} elseif ($_SESSION['CheckCreditLimits'] == 2 and $_SESSION['Items' . $Identifier]->CreditAvailable <= 0) {
					prnMsg(_('No more orders can be placed by') . ' ' . $MyRow[0] . ' ' . _(' their account is currently at or over their credit limit'), 'warn');
					$AlreadyWarnedAboutCredit = true;
					include ('includes/footer.php');
					exit;
				}
			}

		} else {
			prnMsg($MyRow['brname'] . ' ' . _('Although the account is defined as the cash sale account for the location  the account is currently on hold. Please contact the credit control personnel to discuss'), 'warn');
		}

	}
} // end if its a new sale to be set up ...
if (isset($_POST['CancelOrder'])) {

	unset($_SESSION['Items' . $Identifier]->LineItems);
	$_SESSION['Items' . $Identifier]->ItemsOrdered = 0;
	unset($_SESSION['Items' . $Identifier]);
	$_SESSION['Items' . $Identifier] = new cart;

	prnMsg(_('This sale has been cancelled as requested'), 'success');
	echo '<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Start a new Counter Sale'), '</a>';
	include ('includes/footer.php');
	exit;

} else {
	/*Not cancelling the order */

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/inventory.png" title="', _('Counter Sales'), '" alt="" />', ' ', $_SESSION['Items' . $Identifier]->CustomerName, ' ', _('Counter Sale'), ' ', _('from'), ' ', $_SESSION['Items' . $Identifier]->LocationName, ' ', _('inventory'), ' (', _('all amounts in'), ' ', $_SESSION['Items' . $Identifier]->DefaultCurrency . ')
		</p>';
}

if (isset($_POST['Search']) or isset($_POST['Next']) or isset($_POST['Previous'])) {

	if ($_POST['Keywords'] != '' and $_POST['StockCode'] == '') {
		$Msg = _('Item description has been used in search');
	} else if ($_POST['StockCode'] != '' and $_POST['Keywords'] == '') {
		$Msg = _('Item Code has been used in search');
	} else if ($_POST['Keywords'] == '' and $_POST['StockCode'] == '') {
		$Msg = _('Stock Category has been used in search');
	}
	if (isset($_POST['Keywords']) and mb_strlen($_POST['Keywords']) > 0) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON  stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else if (mb_strlen($_POST['StockCode']) > 0) {

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					  ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					AND (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON  stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}
	}

	if (isset($_POST['Next'])) {
		$Offset = $_POST['NextList'];
	}
	if (isset($_POST['Previous'])) {
		$Offset = $_POST['PreviousList'];
	}
	if (!isset($Offset) or $Offset < 0) {
		$Offset = 0;
	}
	$SQL = $SQL . ' LIMIT ' . $_SESSION['DefaultDisplayRecordsMax'] . ' OFFSET ' . strval($_SESSION['DefaultDisplayRecordsMax'] * $Offset);

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL used to get the part selection was');
	$SearchResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(_('There are no products available meeting the criteria specified'), 'info');
	}
	if (DB_num_rows($SearchResult) == 1) {
		$MyRow = DB_fetch_array($SearchResult);
		$NewItem = $MyRow['stockid'];
		DB_data_seek($SearchResult, 0);
	}
	if (DB_num_rows($SearchResult) < $_SESSION['DisplayRecordsMax']) {
		$Offset = 0;
	}

} //end of if search


/* Always do the stuff below */

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', urlencode($Identifier), '" id="SelectParts" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

//Get The exchange rate used for GPPercent calculations on adding or amending items
if ($_SESSION['Items' . $Identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault']) {
	$ExRateResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items' . $Identifier]->DefaultCurrency . "'");
	if (DB_num_rows($ExRateResult) > 0) {
		$ExRateRow = DB_fetch_row($ExRateResult);
		$ExRate = $ExRateRow[0];
	} else {
		$ExRate = 1;
	}
} else {
	$ExRate = 1;
}

/*Process Quick Entry */
/* If enter is pressed on the quick entry screen, the default button may be Recalculate */
if (isset($_POST['SelectingOrderItems']) or isset($_POST['QuickEntry']) or isset($_POST['Recalculate'])) {
	if (!isset($_POST['TotalQuickEntryRows'])) {
		$_POST['TotalQuickEntryRows'] = 0;
	}
	/* get the item details from the database and hold them in the cart object */

	/*Discount can only be set later on  -- after quick entry -- so default discount to 0 in the first place */
	$Discount = 0;
	$AlreadyWarnedAboutCredit = false;
	$i = 1;
	while ($i <= max($_SESSION['QuickEntries'], $_POST['TotalQuickEntryRows']) and isset($_POST['part_' . $i]) and $_POST['part_' . $i] != '') {

		$QuickEntryCode = 'part_' . $i;
		$QuickEntryQty = 'qty_' . $i;
		$QuickEntryPOLine = 'poline_' . $i;
		$QuickEntryItemDue = 'ItemDue_' . $i;

		++$i;

		if (isset($_POST[$QuickEntryCode])) {
			$NewItem = mb_strtoupper($_POST[$QuickEntryCode]);
		}
		if (isset($_POST[$QuickEntryQty])) {
			$NewItemQty = filter_number_format($_POST[$QuickEntryQty]);
		}
		if (isset($_POST[$QuickEntryItemDue])) {
			$NewItemDue = $_POST[$QuickEntryItemDue];
		} else {
			$NewItemDue = DateAdd(Date($_SESSION['DefaultDateFormat']), 'd', $_SESSION['Items' . $Identifier]->DeliveryDays);
		}
		if (isset($_POST[$QuickEntryPOLine])) {
			$NewPOLine = $_POST[$QuickEntryPOLine];
		} else {
			$NewPOLine = 0;
		}

		if (!isset($NewItem)) {
			unset($NewItem);
			break;
			/* break out of the loop if nothing in the quick entry fields*/
		}

		if (!is_date($NewItemDue)) {
			prnMsg(_('An invalid date entry was made for ') . ' ' . $NewItem . ' ' . _('The date entry') . ' ' . $NewItemDue . ' ' . _('must be in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'warn');
			//Attempt to default the due date to something sensible?
			$NewItemDue = DateAdd(Date($_SESSION['DefaultDateFormat']), 'd', $_SESSION['Items' . $Identifier]->DeliveryDays);
		}
		/*Now figure out if the item is a kit set - the field MBFlag='K'*/
		$SQL = "SELECT stockmaster.mbflag,
						stockmaster.controlled
				FROM stockmaster
				WHERE stockmaster.stockid='" . $NewItem . "'";

		$ErrMsg = _('Could not determine if the part being ordered was a kitset or not because');
		$DbgMsg = _('The sql that was used to determine if the part being ordered was a kitset or not was ');
		$KitResult = DB_query($SQL, $ErrMsg, $DbgMsg);

		if (DB_num_rows($KitResult) == 0) {
			prnMsg(_('The item code') . ' ' . $NewItem . ' ' . _('could not be retrieved from the database and has not been added to the order'), 'warn');
		} elseif ($MyRow = DB_fetch_array($KitResult)) {
			if ($MyRow['mbflag'] == 'K') {
				/*It is a kit set item */
				$SQL = "SELECT bom.component,
							bom.quantity
						FROM bom
						WHERE bom.parent='" . $NewItem . "'
						AND bom.effectiveto > CURRENT_DATE
						AND bom.effectiveafter <= CURRENT_DATE";

				$ErrMsg = _('Could not retrieve kitset components from the database because') . ' ';
				$KitResult = DB_query($SQL, $ErrMsg, $DbgMsg);

				$ParentQty = $NewItemQty;
				while ($KitParts = DB_fetch_array($KitResult)) {
					$NewItem = $KitParts['component'];
					$NewItemQty = $KitParts['quantity'] * $ParentQty;
					$NewPOLine = 0;
					include ('includes/SelectOrderItems_IntoCart.inc');
					$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
				}

			} else if ($MyRow['mbflag'] == 'G') {
				prnMsg(_('Phantom assemblies cannot be sold, these items exist only as bills of materials used in other manufactured items. The following item has not been added to the order') . ': ' . $NewItem, 'warn');
			} else if ($MyRow['controlled'] == 1) {
				prnMsg(_('The system does not currently cater for counter sales of lot controlled or serialised items'), 'warn');
			} else if ($NewItemQty <= 0) {
				prnMsg(_('Only items entered with a positive quantity can be added to the sale'), 'warn');
			} else {
				/*Its not a kit set item*/
				include ('includes/SelectOrderItems_IntoCart.inc');
				$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
			}
		}
	}
	unset($NewItem);
}
/* end of if quick entry */

/*Now do non-quick entry delete/edits/adds */

if ((isset($_SESSION['Items' . $Identifier])) or isset($NewItem)) {

	if (isset($_GET['Delete'])) {
		$_SESSION['Items' . $Identifier]->remove_from_cart($_GET['Delete']);
		/*Don't do any DB updates*/
	}
	$AlreadyWarnedAboutCredit = false;
	foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {

		if (isset($_POST['Quantity_' . $OrderLine->LineNumber])) {

			$Quantity = round(filter_number_format($_POST['Quantity_' . $OrderLine->LineNumber]), $OrderLine->DecimalPlaces);

			if (ABS($OrderLine->Price - filter_number_format($_POST['Price_' . $OrderLine->LineNumber])) > 0.01) {
				/*There is a new price being input for the line item */

				$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
				$_POST['GPPercent_' . $OrderLine->LineNumber] = (($Price * (1 - (filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]) / 100))) - $OrderLine->StandardCost * $ExRate) / ($Price * (1 - filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])) / 100);

			} elseif (ABS($OrderLine->GPPercent - filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber])) >= 0.01) {
				/* A GP % has been input so need to do a recalculation of the price at this new GP Percentage */

				prnMsg(_('Recalculated the price from the GP % entered - the GP % was') . ' ' . $OrderLine->GPPercent . '  the new GP % is ' . filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]), 'info');

				$Price = ($OrderLine->StandardCost * $ExRate) / (1 - ((filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]) + filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])) / 100));
			} else {
				$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
			}
			$DiscountPercentage = filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]);
			if ($_SESSION['AllowOrderLineItemNarrative'] == 1) {
				$Narrative = $_POST['Narrative_' . $OrderLine->LineNumber];
			} else {
				$Narrative = '';
			}

			if (!isset($OrderLine->DiscountPercent)) {
				$OrderLine->DiscountPercent = 0;
			}

			if ($Quantity < 0 or $Price < 0 or $DiscountPercentage > 100 or $DiscountPercentage < 0) {
				prnMsg(_('The item could not be updated because you are attempting to set the quantity ordered to less than 0 or the price less than 0 or the discount more than 100% or less than 0%'), 'warn');
			} else if ($OrderLine->Quantity != $Quantity or $OrderLine->Price != $Price or abs($OrderLine->DiscountPercent - $DiscountPercentage / 100) > 0.001 or $OrderLine->Narrative != $Narrative or $OrderLine->ItemDue != $_POST['ItemDue_' . $OrderLine->LineNumber] or $OrderLine->POLine != $_POST['POLine_' . $OrderLine->LineNumber]) {

				$_SESSION['Items' . $Identifier]->update_cart_item($OrderLine->LineNumber, $Quantity, $Price, $DiscountPercentage / 100, $Narrative, 'Yes', /*Update DB */
				$_POST['ItemDue_' . $OrderLine->LineNumber], $_POST['POLine_' . $OrderLine->LineNumber], filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]), $Identifier);
			}
		} //page not called from itself - POST variables not set

	}
}

if (isset($_POST['Recalculate'])) {
	foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {
		$NewItem = $OrderLine->StockID;
		$SQL = "SELECT stockmaster.mbflag,
						stockmaster.controlled
				FROM stockmaster
				WHERE stockmaster.stockid='" . $OrderLine->StockID . "'";

		$ErrMsg = _('Could not determine if the part being ordered was a kitset or not because');
		$DbgMsg = _('The sql that was used to determine if the part being ordered was a kitset or not was ');
		$KitResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		if ($MyRow = DB_fetch_array($KitResult)) {
			if ($MyRow['mbflag'] == 'K') {
				/*It is a kit set item */
				$SQL = "SELECT bom.component,
								bom.quantity
							FROM bom
							WHERE bom.parent='" . $OrderLine->StockID . "'
							AND bom.effectiveto > CURRENT_DATE
							AND bom.effectiveafter <= CURRENT_DATE";

				$ErrMsg = _('Could not retrieve kitset components from the database because');
				$KitResult = DB_query($SQL, $ErrMsg);

				$ParentQty = $NewItemQty;
				while ($KitParts = DB_fetch_array($KitResult)) {
					$NewItem = $KitParts['component'];
					$NewItemQty = $KitParts['quantity'] * $ParentQty;
					$NewPOLine = 0;
					$NewItemDue = date($_SESSION['DefaultDateFormat']);
					$_SESSION['Items' . $Identifier]->GetTaxes($OrderLine->LineNumber);
				}

			} else {
				/*Its not a kit set item*/
				$NewItemDue = date($_SESSION['DefaultDateFormat']);
				$NewPOLine = 0;
				$_SESSION['Items' . $Identifier]->GetTaxes($OrderLine->LineNumber);
			}
		}
		unset($NewItem);
	}
	/* end of if its a new item */
}

if (isset($NewItem)) {
	/* get the item details from the database and hold them in the cart object make the quantity 1 by default then add it to the cart
	Now figure out if the item is a kit set - the field MBFlag='K'
	* controlled items and ghost/phantom items cannot be selected because the SQL to show items to select doesn't show 'em
	* */
	$AlreadyWarnedAboutCredit = false;

	$SQL = "SELECT stockmaster.mbflag,
				stockmaster.taxcatid
			FROM stockmaster
			WHERE stockmaster.stockid='" . $NewItem . "'";

	$ErrMsg = _('Could not determine if the part being ordered was a kitset or not because');

	$KitResult = DB_query($SQL, $ErrMsg);

	$NewItemQty = 1;
	/*By Default */
	$Discount = 0;
	/*By default - can change later or discount category override */

	if ($MyRow = DB_fetch_array($KitResult)) {
		if ($MyRow['mbflag'] == 'K') {
			/*It is a kit set item */
			$SQL = "SELECT bom.component,
						bom.quantity
					FROM bom
					WHERE bom.parent='" . $NewItem . "'
					AND bom.effectiveto > CURRENT_DATE
					AND bom.effectiveafter <= CURRENT_DATE";

			$ErrMsg = _('Could not retrieve kitset components from the database because');
			$KitResult = DB_query($SQL, $ErrMsg);

			$ParentQty = $NewItemQty;
			while ($KitParts = DB_fetch_array($KitResult)) {
				$NewItem = $KitParts['component'];
				$NewItemQty = $KitParts['quantity'] * $ParentQty;
				$NewPOLine = 0;
				$NewItemDue = date($_SESSION['DefaultDateFormat']);
				include ('includes/SelectOrderItems_IntoCart.inc');
				$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
			}

		} else {
			/*Its not a kit set item*/
			$NewItemDue = date($_SESSION['DefaultDateFormat']);
			$NewPOLine = 0;

			include ('includes/SelectOrderItems_IntoCart.inc');
			$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
		}

	}
	/* end of if its a new item */

}
/*end of if its a new item */

if (isset($NewItemArray) and isset($_POST['SelectingOrderItems'])) {
	/* get the item details from the database and hold them in the cart object make the quantity 1 by default then add it to the cart */
	/*Now figure out if the item is a kit set - the field MBFlag='K'*/
	$AlreadyWarnedAboutCredit = false;

	foreach ($NewItemArray as $NewItem => $NewItemQty) {
		if ($NewItemQty > 0) {
			$SQL = "SELECT stockmaster.mbflag
					FROM stockmaster
					WHERE stockmaster.stockid='" . $NewItem . "'";

			$ErrMsg = _('Could not determine if the part being ordered was a kitset or not because');

			$KitResult = DB_query($SQL, $ErrMsg);

			//$NewItemQty = 1; /*By Default */
			$Discount = 0;
			/*By default - can change later or discount category override */

			if ($MyRow = DB_fetch_array($KitResult)) {
				if ($MyRow['mbflag'] == 'K') {
					/*It is a kit set item */
					$SQL = "SELECT bom.component,
								bom.quantity
				  			FROM bom
							WHERE bom.parent='" . $NewItem . "'
							AND bom.effectiveto > CURRENT_DATE
							AND bom.effectiveafter <= CURRENT_DATE";

					$ErrMsg = _('Could not retrieve kitset components from the database because');
					$KitResult = DB_query($SQL, $ErrMsg);

					$ParentQty = $NewItemQty;
					while ($KitParts = DB_fetch_array($KitResult)) {
						$NewItem = $KitParts['component'];
						$NewItemQty = $KitParts['quantity'] * $ParentQty;
						$NewItemDue = date($_SESSION['DefaultDateFormat']);
						$NewPOLine = 0;
						include ('includes/SelectOrderItems_IntoCart.inc');
						$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
					}

				} else {
					/*Its not a kit set item*/
					$NewItemDue = date($_SESSION['DefaultDateFormat']);
					$NewPOLine = 0;
					include ('includes/SelectOrderItems_IntoCart.inc');
					$_SESSION['Items' . $Identifier]->GetTaxes(($_SESSION['Items' . $Identifier]->LineCounter - 1));
				}
			}
			/* end of if its a new item */
		}
		/*end of if its a new item */
	}
}

/* Now Run through each line of the order again to work out the appropriate discount from the discount matrix */
$DiscCatsDone = array();
foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {

	if ($OrderLine->DiscCat != '' and !in_array($OrderLine->DiscCat, $DiscCatsDone)) {
		$DiscCatsDone[] = $OrderLine->DiscCat;
		$QuantityOfDiscCat = 0;

		foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine_2) {
			/* add up total quantity of all lines of this DiscCat */
			if ($OrderLine_2->DiscCat == $OrderLine->DiscCat) {
				$QuantityOfDiscCat+= $OrderLine_2->Quantity;
			}
		}
		$Result = DB_query("SELECT MAX(discountrate) AS discount
							FROM discountmatrix
							WHERE salestype='" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "'
							AND discountcategory ='" . $OrderLine->DiscCat . "'
							AND quantitybreak <= '" . $QuantityOfDiscCat . "'");
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] == NULL) {
			$DiscountMatrixRate = 0;
		} else {
			$DiscountMatrixRate = $MyRow[0];
		}
		if ($MyRow[0] != 0) {
			/* need to update the lines affected */
			foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine_2) {
				if ($OrderLine_2->DiscCat == $OrderLine->DiscCat) {
					$_SESSION['Items' . $Identifier]->LineItems[$OrderLine_2->LineNumber]->DiscountPercent = $DiscountMatrixRate;
					$_SESSION['Items' . $Identifier]->LineItems[$OrderLine_2->LineNumber]->GPPercent = (($_SESSION['Items' . $Identifier]->LineItems[$OrderLine_2->LineNumber]->Price * (1 - $DiscountMatrixRate)) - $_SESSION['Items' . $Identifier]->LineItems[$OrderLine_2->LineNumber]->StandardCost * $ExRate) / ($_SESSION['Items' . $Identifier]->LineItems[$OrderLine_2->LineNumber]->Price * (1 - $DiscountMatrixRate) / 100);
				}
			}
		}
	}
}
/* end of discount matrix lookup code */

if (count($_SESSION['Items' . $Identifier]->LineItems) > 0) {
	/*only show order lines if there are any */
	/*
	// *************************************************************************
	//   T H I S   W H E R E   T H E   S A L E  I S   D I S P L A Y E D
	// *************************************************************************
	*/

	echo '<table>
			<tr>
				<th>', _('Item Code'), '</th>
				<th>', _('Item Description'), '</th>
				<th>', _('Quantity'), '</th>
				<th>', _('QOH'), '</th>
				<th>', _('Unit'), '</th>
				<th>', _('Price'), '</th>';
	if (in_array(1000, $_SESSION['AllowedPageSecurityTokens'])) {
		echo '<th>', _('Discount'), '</th>
			  <th>', _('GP %'), '</th>';
	}
	echo '<th>', _('Net'), '</th>
		  <th>', _('Tax'), '</th>
		  <th>', _('Total'), '<br />', _('Incl Tax'), '</th>
		</tr>';

	$_SESSION['Items' . $Identifier]->total = 0;
	$_SESSION['Items' . $Identifier]->totalVolume = 0;
	$_SESSION['Items' . $Identifier]->totalWeight = 0;
	$TaxTotals = array();
	$TaxGLCodes = array();
	$TaxTotal = 0;

	foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {

		$SubTotal = round($OrderLine->Quantity * $OrderLine->Price * (1 - $OrderLine->DiscountPercent), $_SESSION['Items' . $Identifier]->CurrDecimalPlaces);
		$DisplayDiscount = locale_number_format(($OrderLine->DiscountPercent * 100), 2);
		$QtyOrdered = $OrderLine->Quantity;
		$QtyRemain = $QtyOrdered - $OrderLine->QtyInv;

		echo '<tr class="striped_row">
				<input type="hidden" name="POLine_', $OrderLine->LineNumber, '" value="" />
				<input type="hidden" name="ItemDue_', $OrderLine->LineNumber, '" value="', $OrderLine->ItemDue, '" />
				<td><a target="_blank" href="', $RootPath, '/StockStatus.php?identifier=', urlencode($Identifier), '&amp;StockID=', urlencode($OrderLine->StockID), '&amp;DebtorNo=', urlencode($_SESSION['Items' . $Identifier]->DebtorNo), '">', $OrderLine->StockID, '</a></td>
				<td data-title="', $OrderLine->LongDescription, '">', $OrderLine->ItemDescription, '</td>
				<td><input class="number" type="text" name="Quantity_', $OrderLine->LineNumber, '" size="6" required="required" maxlength="6" value="', locale_number_format($OrderLine->Quantity, $OrderLine->DecimalPlaces), '" /></td>
				<td class="number">', locale_number_format($OrderLine->QOHatLoc, $OrderLine->DecimalPlaces), '</td>
				<td>', $OrderLine->Units, '</td>';

		if (in_array(1000, $_SESSION['AllowedPageSecurityTokens'])) {
			echo '<td><input class="number" type="text" name="Price_', $OrderLine->LineNumber, '" size="16" required="required" maxlength="16" value="', locale_number_format($OrderLine->Price, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '" /></td>
				<td><input class="number" type="text" name="Discount_', $OrderLine->LineNumber, '" size="5" required="required" maxlength="4" value="', locale_number_format(($OrderLine->DiscountPercent * 100), 2), '" /></td>
				<td><input class="number" type="text" name="GPPercent_', $OrderLine->LineNumber, '" size="3" required="required" maxlength="40" value="', locale_number_format($OrderLine->GPPercent, 2), '" /></td>';
		} else {
			echo '<td class="number">', locale_number_format($OrderLine->Price, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '<input type="hidden" name="Price_', $OrderLine->LineNumber, '"  value="', locale_number_format($OrderLine->Price, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '" />
				<input type="hidden" name="Discount_', $OrderLine->LineNumber, '" value="', locale_number_format(($OrderLine->DiscountPercent * 100), 2), '" />
				<input type="hidden" name="GPPercent_', $OrderLine->LineNumber . '" value="' . locale_number_format($OrderLine->GPPercent, 2), '" /></td>';
		}
		echo '<td class="number">', locale_number_format($SubTotal, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>';
		$LineDueDate = $OrderLine->ItemDue;
		if (!is_date($OrderLine->ItemDue)) {
			$LineDueDate = DateAdd(Date($_SESSION['DefaultDateFormat']), 'd', $_SESSION['Items' . $Identifier]->DeliveryDays);
			$_SESSION['Items' . $Identifier]->LineItems[$OrderLine->LineNumber]->ItemDue = $LineDueDate;
		}
		$i = 0; // initialise the number of taxes iterated through
		$TaxLineTotal = 0; //initialise tax total for the line
		if (sizeOf($OrderLine->Taxes) > 0) {
			foreach ($OrderLine->Taxes as $Tax) {
				if (empty($TaxTotals[$Tax->TaxAuthID])) {
					$TaxTotals[$Tax->TaxAuthID] = 0;
				}
				if ($Tax->TaxOnTax == 1) {
					$TaxTotals[$Tax->TaxAuthID]+= ($Tax->TaxRate * ($SubTotal + $TaxLineTotal));
					$TaxLineTotal+= ($Tax->TaxRate * ($SubTotal + $TaxLineTotal));
				} else {
					$TaxTotals[$Tax->TaxAuthID]+= ($Tax->TaxRate * $SubTotal);
					$TaxLineTotal+= ($Tax->TaxRate * $SubTotal);
				}
				$TaxGLCodes[$Tax->TaxAuthID] = $Tax->TaxGLCode;
			}
		}

		$TaxTotal+= $TaxLineTotal;
		$_SESSION['Items' . $Identifier]->TaxTotals = $TaxTotals;
		$_SESSION['Items' . $Identifier]->TaxGLCodes = $TaxGLCodes;
		echo '<td class="number">', locale_number_format($TaxLineTotal, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>';
		echo '<td class="number">', locale_number_format($SubTotal + $TaxLineTotal, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>';
		echo '<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', urlencode($Identifier), '&amp;Delete=', urlencode($OrderLine->LineNumber), '" onclick="return MakeConfirm(\'', _('Are You Sure?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
		</tr>';

		if ($_SESSION['AllowOrderLineItemNarrative'] == 1) {
			echo '<tr>
					<td valign="top" colspan="11">', _('Narrative'), ':<textarea name="Narrative_', $OrderLine->LineNumber, '" cols="100" rows="1">', stripslashes(AddCarriageReturns($OrderLine->Narrative)), '</textarea></td>
				</tr>';
		} else {
			echo '<input type="hidden" name="Narrative" value="" />';
		}

		$_SESSION['Items' . $Identifier]->total = $_SESSION['Items' . $Identifier]->total + $SubTotal;
		$_SESSION['Items' . $Identifier]->totalVolume = $_SESSION['Items' . $Identifier]->totalVolume + $OrderLine->Quantity * $OrderLine->Volume;
		$_SESSION['Items' . $Identifier]->totalWeight = $_SESSION['Items' . $Identifier]->totalWeight + $OrderLine->Quantity * $OrderLine->Weight;

	}
	/* end of loop around items */

	echo '<tr class="striped_row">';
	if (in_array(1000, $_SESSION['AllowedPageSecurityTokens'])) {
		echo '<td colspan="8" class="number"><b>', _('Total'), '</b></td>';
	} else {
		echo '<td colspan="6" class="number"><b>', _('Total'), '</b></td>';
	}
	echo '<td class="number">', locale_number_format(($_SESSION['Items' . $Identifier]->total), $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>
			<td class="number">', locale_number_format($TaxTotal, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>
			<td class="number">', locale_number_format(($_SESSION['Items' . $Identifier]->total + $TaxTotal), $_SESSION['Items' . $Identifier]->CurrDecimalPlaces), '</td>
		</tr>
	</table>';

	echo '<input type="hidden" name="TaxTotal" value="', $TaxTotal, '" />';

	echo '<fieldset>';
	//nested table
	echo '<fieldset>
			<legend>', _('Delivery Details'), '</legend>';

	echo '<field>
			<label for="DeliverTo">', _('Picked Up By'), ':</label>
			<input type="text" size="25" maxlength="25" name="DeliverTo" value="', stripslashes($_SESSION['Items' . $Identifier]->DeliverTo), '" />
		</field>';

	echo '<field>
			<label for="PhoneNo">', _('Contact Phone Number'), ':</label>
			<input type="tel" size="25" maxlength="25" name="PhoneNo" value="', stripslashes($_SESSION['Items' . $Identifier]->PhoneNo), '" />
		</field>';

	echo '<field>
			<label for="Email">', _('Contact Email'), ':</label>
			<input type="email" size="25" maxlength="30" name="Email" value="', stripslashes($_SESSION['Items' . $Identifier]->Email), '" />
		</field>';

	echo '<field>
			<label for="CustRef">', _('Customer Reference'), ':</label>
			<input type="text" size="25" maxlength="25" name="CustRef" value="', stripcslashes($_SESSION['Items' . $Identifier]->CustRef), '" />
		</field>';

	echo '<field>
			<label for="SalesPerson">', _('Sales person'), ':</label>';

	if ($_SESSION['SalesmanLogin'] != '') {
		echo '<div class="fieldtext">', $_SESSION['UsersRealName'], '</div>';
	} else {
		echo '<select name="SalesPerson">';
		$SalesPeopleResult = DB_query("SELECT salesmancode, salesmanname FROM salesman WHERE current=1");
		if (!isset($_POST['SalesPerson']) and $_SESSION['SalesmanLogin'] != NULL) {
			$_SESSION['Items' . $Identifier]->SalesPerson = $_SESSION['SalesmanLogin'];
		}

		while ($SalesPersonRow = DB_fetch_array($SalesPeopleResult)) {
			if ($SalesPersonRow['salesmancode'] == $_SESSION['Items' . $Identifier]->SalesPerson) {
				echo '<option selected="selected" value="', $SalesPersonRow['salesmancode'], '">', $SalesPersonRow['salesmanname'], '</option>';
			} else {
				echo '<option value="', $SalesPersonRow['salesmancode'], '">', $SalesPersonRow['salesmanname'], '</option>';
			}
		}
		echo '</select>';
	}
	echo '</field>';

	echo '<field>
			<label for="Comments">', _('Comments'), ':</label>
			<textarea name="Comments" cols="23" rows="5">', stripcslashes($_SESSION['Items' . $Identifier]->Comments), '</textarea>
		</field>';

	echo '</fieldset>'; //end the sub table in the first column of master table
	echo '<fieldset>
			<legend>', _('Payment Details'), '</legend>'; // a new nested table in the second column of master table
	//now the payment stuff in this column
	$PaymentMethodsResult = DB_query("SELECT paymentid, paymentname FROM paymentmethods");

	echo '<field>
			<label for="PaymentMethod">', _('Payment Type'), ':</label>
			<select name="PaymentMethod">';
	while ($PaymentMethodRow = DB_fetch_array($PaymentMethodsResult)) {
		if (isset($_POST['PaymentMethod']) and $_POST['PaymentMethod'] == $PaymentMethodRow['paymentid']) {
			echo '<option selected="selected" value="', $PaymentMethodRow['paymentid'], '">', $PaymentMethodRow['paymentname'], '</option>';
		} else {
			echo '<option value="', $PaymentMethodRow['paymentid'], '">', $PaymentMethodRow['paymentname'], '</option>';
		}
	}
	echo '</select>
		</field>';

	$BankAccountsResult = DB_query("SELECT bankaccountname, accountcode FROM bankaccounts ORDER BY bankaccountname");

	echo '<field>
			<label for="BankAccount">', _('Banked to'), ':</label>
			<select name="BankAccount">';
	while ($BankAccountsRow = DB_fetch_array($BankAccountsResult)) {
		if (isset($_POST['BankAccount']) and $_POST['BankAccount'] == $BankAccountsRow['accountcode']) {
			echo '<option selected="selected" value="', $BankAccountsRow['accountcode'], '">', $BankAccountsRow['bankaccountname'], '</option>';
		} else {
			echo '<option value="', $BankAccountsRow['accountcode'], '">', $BankAccountsRow['bankaccountname'], '</option>';
		}
	}
	echo '</select>
		</field>';

	if (!isset($_POST['CashReceived'])) {
		$_POST['CashReceived'] = 0;
	}
	echo '<field>
			<label for="CashReceived">', _('Cash Received'), ':</label>
			<input type="text" class="number" id="CashReceived" name="CashReceived" required="required"  maxlength="12" size="12" value="', $_POST['CashReceived'], '" onkeyup="CounterSales.CalculateChangeDue();" />
		</field>';

	if (!isset($_POST['AmountPaid'])) {
		$_POST['AmountPaid'] = 0;
	}
	echo '<field>
			<label for="AmountPaid">', _('Amount Paid'), ':</label>
			<input type="text" class="number" id="AmountPaid" name="AmountPaid" required="required" data-title="', _('Enter the amount paid by the customer, this must equal the amount of the sale'), '" maxlength="12" size="12" value="', $_POST['AmountPaid'], '" readonly />
		</field>';

	if (!isset($_POST['ChangeDue'])) {
		$_POST['ChangeDue'] = 0;
	}

	echo '<field>
			<label for="ChangeDue">', _('Change'), ':</label>
			<input type="text" class="number" id="ChangeDue" name="ChangeDue" maxlength="12" size="12" value="', $_POST['ChangeDue'], '" readonly />
		</field>';

	echo '</fieldset>'; //end the sub table in the second column of master table
	echo '</fieldset>'; //end of column/row/master table
	if (!isset($_POST['ProcessSale'])) {
		echo '<div class="centre">
				<input type="submit" name="Recalculate" value="', _('Re-Calculate'), '" />
				<input type="submit" name="ProcessSale" value="', _('Process The Sale'), '" />
			</div>';
	}
} # end of if lines
/* **********************************
 * Invoice Processing Here
 * **********************************
 * */
if (isset($_POST['ProcessSale']) and $_POST['ProcessSale'] != '') {

	$InputError = false; //always assume the best
	//but check for the worst
	if ($_SESSION['Items' . $Identifier]->LineCounter == 0) {
		prnMsg(_('There are no lines on this sale. Please enter lines to invoice first'), 'error');
		$InputError = true;
	}
	if (abs(filter_number_format($_POST['AmountPaid']) - (round($_SESSION['Items' . $Identifier]->total + filter_number_format($_POST['TaxTotal']), $_SESSION['Items' . $Identifier]->CurrDecimalPlaces))) >= 0.01) {
		prnMsg(_('The amount entered as payment does not equal the amount of the invoice. Please ensure the customer has paid the correct amount and re-enter'), 'error');
		$InputError = true;
	}

	if ($_SESSION['ProhibitNegativeStock'] == 1) { // checks for negative stock after processing invoice
		//sadly this check does not combine quantities occuring twice on and order and each line is considered individually :-(
		$NegativesFound = false;
		foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {
			$SQL = "SELECT stockmaster.description,
					   		locstock.quantity,
					   		stockmaster.mbflag
		 			FROM locstock
		 			INNER JOIN stockmaster
					ON stockmaster.stockid=locstock.stockid
					WHERE stockmaster.stockid='" . $OrderLine->StockID . "'
					AND locstock.loccode='" . $_SESSION['Items' . $Identifier]->Location . "'";

			$ErrMsg = _('Could not retrieve the quantity left at the location once this order is invoiced (for the purposes of checking that stock will not go negative because)');
			$Result = DB_query($SQL, $ErrMsg);
			$CheckNegRow = DB_fetch_array($Result);
			if ($CheckNegRow['mbflag'] == 'B' or $CheckNegRow['mbflag'] == 'M') {
				if ($CheckNegRow['quantity'] < $OrderLine->Quantity) {
					prnMsg(_('Invoicing the selected order would result in negative stock. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'), 'error', $OrderLine->StockID . ' ' . $CheckNegRow['description'] . ' - ' . _('Negative Stock Prohibited'));
					$NegativesFound = true;
				}
			} else if ($CheckNegRow['mbflag'] == 'A') {

				/*Now look for assembly components that would go negative */
				$SQL = "SELECT bom.component,
							   stockmaster.description,
							   locstock.quantity-(" . $OrderLine->Quantity . "*bom.quantity) AS qtyleft
						FROM bom
						INNER JOIN locstock
							ON bom.component=locstock.stockid
						INNER JOIN stockmaster
							ON stockmaster.stockid=bom.component
						WHERE bom.parent='" . $OrderLine->StockID . "'
							AND locstock.loccode='" . $_SESSION['Items' . $Identifier]->Location . "'
							AND effectiveafter <= CURRENT_DATE
							AND effectiveto > CURRENT_DATE";

				$ErrMsg = _('Could not retrieve the component quantity left at the location once the assembly item on this order is invoiced (for the purposes of checking that stock will not go negative because)');
				$Result = DB_query($SQL, $ErrMsg);
				while ($NegRow = DB_fetch_array($Result)) {
					if ($NegRow['qtyleft'] < 0) {
						prnMsg(_('Invoicing the selected order would result in negative stock for a component of an assembly item on the order. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'), 'error', $NegRow['component'] . ' ' . $NegRow['description'] . ' - ' . _('Negative Stock Prohibited'));
						$NegativesFound = true;
					} // end if negative would result

				} //loop around the components of an assembly item

			} //end if its an assembly item - check component stock

		} //end of loop around items on the order for negative check
		if ($NegativesFound) {
			prnMsg(_('The parameter to prohibit negative stock is set and invoicing this sale would result in negative stock. No futher processing can be performed. Alter the sale first changing quantities or deleting lines which do not have sufficient stock.'), 'error');
			$InputError = true;
		}

	} //end of testing for negative stocks


	if ($InputError == true) { //allow the error to be fixed and then resubmit buttone needs to show
		echo '<div class="centre">
				<input type="submit" name="Recalculate" value="', _('Re-Calculate'), '" />
				<input type="submit" name="ProcessSale" value="', _('Process The Sale'), '" />
			</div>';
	} else { //all good so let's get on with the processing
		/* Now Get the area where the sale is to from the branches table */

		$SQL = "SELECT area,
						defaultshipvia
				FROM custbranch
				WHERE custbranch.debtorno ='" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'
				AND custbranch.branchcode = '" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "'";

		$ErrMsg = _('We were unable to load the area from the custbranch table where the sale is to ');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		$Area = $MyRow[0];
		$DefaultShipVia = $MyRow[1];
		DB_free_result($Result);

		/*company record read in on login with info on GL Links and debtors GL account*/

		if ($_SESSION['CompanyRecord'] == 0) {
			/*The company data and preferences could not be retrieved for some reason */
			prnMsg(_('The company information and preferences could not be retrieved. See your system administrator'), 'error');
			include ('includes/footer.php');
			exit;
		}

		// *************************************************************************
		//   S T A R T   O F   I N V O I C E   S Q L   P R O C E S S I N G
		// *************************************************************************
		$Result = DB_Txn_Begin();
		/*First add the order to the database - it only exists in the session currently! */
		$OrderNo = GetNextTransNo(30);
		$InvoiceNo = GetNextTransNo(10);
		$PeriodNo = GetPeriod(Date($_SESSION['DefaultDateFormat']));

		$HeaderSQL = "INSERT INTO salesorders (	orderno,
												debtorno,
												branchcode,
												customerref,
												comments,
												orddate,
												ordertype,
												shipvia,
												deliverto,
												deladd1,
												contactphone,
												contactemail,
												fromstkloc,
												deliverydate,
												confirmeddate,
												deliverblind,
												salesperson
											) VALUES (
												'" . $OrderNo . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
												'" . $_SESSION['Items' . $Identifier]->CustRef . "',
												'" . $_SESSION['Items' . $Identifier]->Comments . "',
												'" . Date('Y-m-d H:i') . "',
												'" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "',
												'" . $_SESSION['Items' . $Identifier]->ShipVia . "',
												'" . $_SESSION['Items' . $Identifier]->DeliverTo . "',
												'" . _('Counter Sale') . "',
												'" . $_SESSION['Items' . $Identifier]->PhoneNo . "',
												'" . $_SESSION['Items' . $Identifier]->Email . "',
												'" . $_SESSION['Items' . $Identifier]->Location . "',
												CURRENT_DATE,
												CURRENT_DATE,
												0,
												'" . $_SESSION['Items' . $Identifier]->SalesPerson . "'
											)";

		$DbgMsg = _('Trouble inserting the sales order header. The SQL that failed was');
		$ErrMsg = _('The order cannot be added because');
		$InsertQryResult = DB_query($HeaderSQL, $ErrMsg, $DbgMsg, true);

		$StartOf_LineItemsSQL = "INSERT INTO salesorderdetails (orderlineno,
																orderno,
																stkcode,
																unitprice,
																quantity,
																discountpercent,
																narrative,
																itemdue,
																actualdispatchdate,
																qtyinvoiced,
																completed)
															VALUES (";

		$DbgMsg = _('Trouble inserting a line of a sales order. The SQL that failed was');
		foreach ($_SESSION['Items' . $Identifier]->LineItems as $StockItem) {

			$LineItemsSQL = $StartOf_LineItemsSQL . "'" . $StockItem->LineNumber . "',
					'" . $OrderNo . "',
					'" . $StockItem->StockID . "',
					'" . $StockItem->Price . "',
					'" . $StockItem->Quantity . "',
					'" . floatval($StockItem->DiscountPercent) . "',
					'" . $StockItem->Narrative . "',
					CURRENT_DATE,
					CURRENT_DATE,
					'" . $StockItem->Quantity . "',
					1)";

			$ErrMsg = _('Unable to add the sales order line');
			$Ins_LineItemResult = DB_query($LineItemsSQL, $ErrMsg, $DbgMsg, true);

			/*Now check to see if the item is manufactured
			 * 			and AutoCreateWOs is on
			 * 			and it is a real order (not just a quotation)*/

			if ($StockItem->MBflag == 'M' and $_SESSION['AutoCreateWOs'] == 1) { //oh yeah its all on!
				//now get the data required to test to see if we need to make a new WO
				$QOHResult = DB_query("SELECT SUM(quantity) FROM locstock WHERE stockid='" . $StockItem->StockID . "'");
				$QOHRow = DB_fetch_row($QOHResult);
				$QOH = $QOHRow[0];

				$SQL = "SELECT SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qtydemand
							FROM salesorderdetails
							INNER JOIN salesorders
								ON salesorderdetails.orderno=salesorders.orderno
							WHERE salesorderdetails.stkcode = '" . $StockItem->StockID . "'
								AND salesorderdetails.completed = 0
								AND salesorders.quotation = 0";
				$DemandResult = DB_query($SQL);
				$DemandRow = DB_fetch_row($DemandResult);
				$QuantityDemand = $DemandRow[0];

				$SQL = "SELECT SUM((salesorderdetails.quantity-salesorderdetails.qtyinvoiced)*bom.quantity) AS dem
							FROM salesorderdetails
							INNER JOIN salesorders
								ON salesorderdetails.orderno=salesorders.orderno
							INNER JOIN bom
								ON salesorderdetails.stkcode=bom.parent
							INNER JOIN stockmaster
								ON stockmaster.stockid=bom.parent
							WHERE salesorderdetails.quantity-salesorderdetails.qtyinvoiced > 0
								AND bom.component='" . $StockItem->StockID . "'
								AND salesorderdetails.completed=0
								AND salesorders.quotation=0";
				$AssemblyDemandResult = DB_query($SQL);
				$AssemblyDemandRow = DB_fetch_row($AssemblyDemandResult);
				$QuantityAssemblyDemand = $AssemblyDemandRow[0];

				// Get the QOO due to Purchase orders for all locations. Function defined in SQL_CommonFunctions.php
				$QuantityPurchOrders = GetQuantityOnOrderDueToPurchaseOrders($StockItem->StockID);
				// Get the QOO dues to Work Orders for all locations. Function defined in SQL_CommonFunctions.php
				$QuantityWorkOrders = GetQuantityOnOrderDueToWorkOrders($StockItem->StockID);

				//Now we have the data - do we need to make any more?
				$ShortfallQuantity = $QOH - $QuantityDemand - $QuantityAssemblyDemand + $QuantityPurchOrders + $QuantityWorkOrders;

				if ($ShortfallQuantity < 0) { //then we need to make a work order
					//How many should the work order be for??
					if ($ShortfallQuantity + $StockItem->EOQ < 0) {
						$WOQuantity = - $ShortfallQuantity;
					} else {
						$WOQuantity = $StockItem->EOQ;
					}

					$WONo = GetNextTransNo(40);
					$ErrMsg = _('Unable to insert a new work order for the sales order item');
					$InsWOResult = DB_query("INSERT INTO workorders (wo,
																	loccode,
																	requiredby,
																	startdate
																)  VALUES (
																	'" . $WONo . "',
																	'" . $_SESSION['DefaultFactoryLocation'] . "',
																	CURRENT_DATE,
																	CURRENT_DATE)", $ErrMsg, $DbgMsg, true);
					//Need to get the latest BOM to roll up cost
					$CostResult = DB_query("SELECT SUM((materialcost+labourcost+overheadcost)*bom.quantity) AS cost
													FROM stockmaster
													INNER JOIN bom
														ON stockmaster.stockid=bom.component
													WHERE bom.parent='" . $StockItem->StockID . "'
														AND bom.loccode='" . $_SESSION['DefaultFactoryLocation'] . "'");
					$CostRow = DB_fetch_row($CostResult);
					if (is_null($CostRow[0]) or $CostRow[0] == 0) {
						$Cost = 0;
						prnMsg(_('In automatically creating a work order for') . ' ' . $StockItem->StockID . ' ' . _('an item on this sales order, the cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'), 'warn');
					} else {
						$Cost = $CostRow[0];
					}

					// insert parent item info
					$SQL = "INSERT INTO woitems (wo,
												 stockid,
												 qtyreqd,
												 stdcost
											) VALUES (
												'" . $WONo . "',
												'" . $StockItem->StockID . "',
												'" . $WOQuantity . "',
												'" . $Cost . "'
											)";
					$ErrMsg = _('The work order item could not be added');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

					//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
					WoRealRequirements($WONo, $_SESSION['DefaultFactoryLocation'], $StockItem->StockID);

					$FactoryManagerEmail = _('A new work order has been created for') . ":\n" . $StockItem->StockID . ' - ' . $StockItem->ItemDescription . ' x ' . $WOQuantity . ' ' . $StockItem->Units . "\n" . _('These are for') . ' ' . $_SESSION['Items' . $Identifier]->CustomerName . ' ' . _('there order ref') . ': ' . $_SESSION['Items' . $Identifier]->CustRef . ' ' . _('our order number') . ': ' . $OrderNo;

					if ($StockItem->Serialised and $StockItem->NextSerialNo > 0) {
						//then we must create the serial numbers for the new WO also
						$FactoryManagerEmail.= "\n" . _('The following serial numbers have been reserved for this work order') . ':';

						for ($i = 0;$i < $WOQuantity;$i++) {

							$Result = DB_query("SELECT serialno FROM stockserialitems
													WHERE serialno='" . ($StockItem->NextSerialNo + $i) . "'
													AND stockid='" . $StockItem->StockID . "'");
							if (DB_num_rows($Result) != 0) {
								$WOQuantity++;
								prnMsg(($StockItem->NextSerialNo + $i) . ': ' . _('This automatically generated serial number already exists - it cannot be added to the work order'), 'error');
							} else {
								$SQL = "INSERT INTO woserialnos (wo,
																stockid,
																serialno
															) VALUES (
																'" . $WONo . "',
																'" . $StockItem->StockID . "',
																'" . ($StockItem->NextSerialNo + $i) . "'
															)";
								$ErrMsg = _('The serial number for the work order item could not be added');
								$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
								$FactoryManagerEmail.= "\n" . ($StockItem->NextSerialNo + $i);
							}
						} //end loop around creation of woserialnos
						$NewNextSerialNo = ($StockItem->NextSerialNo + $WOQuantity + 1);
						$ErrMsg = _('Could not update the new next serial number for the item');
						$UpdateSQL = "UPDATE stockmaster SET nextserialno='" . $NewNextSerialNo . "' WHERE stockid='" . $StockItem->StockID . "'";
						$UpdateNextSerialNoResult = DB_query($UpdateSQL, $ErrMsg, $DbgMsg, true);
					} // end if the item is serialised and nextserialno is set
					$EmailSubject = _('New Work Order Number') . ' ' . $WONo . ' ' . _('for') . ' ' . $StockItem->StockID . ' x ' . $WOQuantity;
					//Send email to the Factory Manager
					if ($_SESSION['SmtpSetting'] == 0) {
						mail($_SESSION['FactoryManagerEmail'], $EmailSubject, $FactoryManagerEmail);

					} else {
						include ('includes/htmlMimeMail.php');
						$Mail = new htmlMimeMail();
						$Mail->setSubject($EmailSubject);
						$Result = SendmailBySmtp($Mail, array($_SESSION['FactoryManagerEmail']));
					}

				} //end if with this sales order there is a shortfall of stock - need to create the WO

			} //end if auto create WOs in on

		}
		/* end inserted line items into sales order details */

		prnMsg(_('Order Number') . ' ' . $OrderNo . ' ' . _('has been entered'), 'success');

		/* End of insertion of new sales order */

		/*Now Get the next invoice number - GetNextTransNo() function in SQL_CommonFunctions
		 * GetPeriod() in includes/DateFunctions.php */

		$DefaultDispatchDate = Date('Y-m-d');

		/*Update order header for invoice charged on */
		$SQL = "UPDATE salesorders SET comments = CONCAT(comments,'" . ' ' . _('Invoice') . ': ' . "','" . $InvoiceNo . "') WHERE orderno= '" . $OrderNo . "'";

		$ErrMsg = _('CRITICAL ERROR') . ' ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The sales order header could not be updated with the invoice number');
		$DbgMsg = _('The following SQL to update the sales order was used');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		/*Now insert the DebtorTrans */

		$SQL = "INSERT INTO debtortrans (transno,
										type,
										debtorno,
										branchcode,
										trandate,
										inputdate,
										prd,
										reference,
										tpe,
										order_,
										ovamount,
										ovgst,
										rate,
										invtext,
										shipvia,
										alloc,
										settled,
										salesperson
									) VALUES (
										'" . $InvoiceNo . "',
										10,
										'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
										'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
										'" . $DefaultDispatchDate . "',
										CURRENT_TIMESTAMP,
										'" . $PeriodNo . "',
										'" . $_SESSION['Items' . $Identifier]->CustRef . "',
										'" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "',
										'" . $OrderNo . "',
										'" . $_SESSION['Items' . $Identifier]->total . "',
										'" . filter_number_format($_POST['TaxTotal']) . "',
										'" . $ExRate . "',
										'" . $_SESSION['Items' . $Identifier]->Comments . "',
										'" . $_SESSION['Items' . $Identifier]->ShipVia . "',
										'" . ($_SESSION['Items' . $Identifier]->total + filter_number_format($_POST['TaxTotal'])) . "',
										'1',
										'" . $_SESSION['Items' . $Identifier]->SalesPerson . "'
									)";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The debtor transaction record could not be inserted because');
		$DbgMsg = _('The following SQL to insert the debtor transaction record was used');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		$DebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');

		/* Insert the tax totals for each tax authority where tax was charged on the invoice */
		foreach ($_SESSION['Items' . $Identifier]->TaxTotals as $TaxAuthID => $TaxAmount) {

			$SQL = "INSERT INTO debtortranstaxes (debtortransid,
													taxauthid,
													taxamount
												) VALUES (
													'" . $DebtorTransID . "',
													'" . $TaxAuthID . "',
													'" . $TaxAmount / $ExRate . "'
												)";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The debtor transaction taxes records could not be inserted because');
			$DbgMsg = _('The following SQL to insert the debtor transaction taxes record was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		}

		//Loop around each item on the sale and process each in turn
		foreach ($_SESSION['Items' . $Identifier]->LineItems as $OrderLine) {
			/* Update location stock records if not a dummy stock item
			 need the MBFlag later too so save it to $MBFlag */
			$Result = DB_query("SELECT mbflag FROM stockmaster WHERE stockid = '" . $OrderLine->StockID . "'");
			$MyRow = DB_fetch_row($Result);
			$MBFlag = $MyRow[0];
			if ($MBFlag == 'B' or $MBFlag == 'M') {
				$Assembly = False;

				/* Need to get the current location quantity
				 will need it later for the stock movement */
				$SQL = "SELECT locstock.quantity
								FROM locstock
								WHERE locstock.stockid='" . $OrderLine->StockID . "'
								AND loccode= '" . $_SESSION['Items' . $Identifier]->Location . "'";
				$ErrMsg = _('WARNING') . ': ' . _('Could not retrieve current location stock');
				$Result = DB_query($SQL, $ErrMsg);

				if (DB_num_rows($Result) == 1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					/* There must be some error this should never happen */
					$QtyOnHandPrior = 0;
				}

				$SQL = "UPDATE locstock
							SET quantity = locstock.quantity - " . $OrderLine->Quantity . "
							WHERE locstock.stockid = '" . $OrderLine->StockID . "'
							AND loccode = '" . $_SESSION['Items' . $Identifier]->Location . "'";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Location stock record could not be updated because');
				$DbgMsg = _('The following SQL to update the location stock record was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			} else if ($MBFlag == 'A') {
				/* its an assembly */
				/*Need to get the BOM for this part and make
				 stock moves for the components then update the Location stock balances */
				$Assembly = True;
				$StandardCost = 0;
				/*To start with - accumulate the cost of the comoponents for use in journals later on */
				$SQL = "SELECT bom.component,
								bom.quantity,
								materialcost+labourcost+overheadcost AS standard
							FROM bom
							LEFT JOIN stockmaster
								ON bom.component=stockmaster.stockid
							WHERE bom.parent='" . $OrderLine->StockID . "'
								AND bom.effectiveto > CURRENT_DATE
								AND bom.effectiveafter <= CURRENT_DATE";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Could not retrieve assembly components from the database for') . ' ' . $OrderLine->StockID . _('because') . ' ';
				$DbgMsg = _('The SQL that failed was');
				$AssResult = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				while ($AssParts = DB_fetch_array($AssResult)) {

					$StandardCost+= ($AssParts['standard'] * $AssParts['quantity']);
					/* Need to get the current location quantity
					 will need it later for the stock movement */
					$SQL = "SELECT locstock.quantity
									FROM locstock
									WHERE locstock.stockid='" . $AssParts['component'] . "'
									AND loccode= '" . $_SESSION['Items' . $Identifier]->Location . "'";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Can not retrieve assembly components location stock quantities because ');
					$DbgMsg = _('The SQL that failed was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					if (DB_num_rows($Result) == 1) {
						$LocQtyRow = DB_fetch_row($Result);
						$QtyOnHandPrior = $LocQtyRow[0];
					} else {
						/*There must be some error this should never happen */
						$QtyOnHandPrior = 0;
					}
					if (empty($AssParts['standard'])) {
						$AssParts['standard'] = 0;
					}
					$SQL = "INSERT INTO stockmoves (stockid,
													type,
													transno,
													loccode,
													trandate,
													userid,
													debtorno,
													branchcode,
													prd,
													reference,
													qty,
													standardcost,
													show_on_inv_crds,
													newqoh
												) VALUES (
													'" . $AssParts['component'] . "',
													10,
													'" . $InvoiceNo . "',
													'" . $_SESSION['Items' . $Identifier]->Location . "',
													'" . $DefaultDispatchDate . "',
													'" . $_SESSION['UserID'] . "',
													'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
													'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
													'" . $PeriodNo . "',
													'" . _('Assembly') . ': ' . $OrderLine->StockID . ' ' . _('Order') . ': ' . $OrderNo . "',
													'" . -$AssParts['quantity'] * $OrderLine->Quantity . "',
													'" . $AssParts['standard'] . "',
													0,
													newqoh-" . ($AssParts['quantity'] * $OrderLine->Quantity) . "
												)";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records for the assembly components of') . ' ' . $OrderLine->StockID . ' ' . _('could not be inserted because');
					$DbgMsg = _('The following SQL to insert the assembly components stock movement records was used');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

					$SQL = "UPDATE locstock
							SET quantity = locstock.quantity - " . $AssParts['quantity'] * $OrderLine->Quantity . "
							WHERE locstock.stockid = '" . $AssParts['component'] . "'
							AND loccode = '" . $_SESSION['Items' . $Identifier]->Location . "'";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Location stock record could not be updated for an assembly component because');
					$DbgMsg = _('The following SQL to update the locations stock record for the component was used');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				}
				/* end of assembly explosion and updates */

				/*Update the cart with the recalculated standard cost from the explosion of the assembly's components*/
				$_SESSION['Items' . $Identifier]->LineItems[$OrderLine->LineNumber]->StandardCost = $StandardCost;
				$OrderLine->StandardCost = $StandardCost;
			}
			/* end of its an assembly */

			// Insert stock movements - with unit cost
			$LocalCurrencyPrice = ($OrderLine->Price / $ExRate);

			if (empty($OrderLine->StandardCost)) {
				$OrderLine->StandardCost = 0;
			}
			if ($MBFlag == 'B' or $MBFlag == 'M') {
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												userid,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												newqoh,
												narrative
											) VALUES (
												'" . $OrderLine->StockID . "',
												10,
												'" . $InvoiceNo . "',
												'" . $_SESSION['Items' . $Identifier]->Location . "',
												'" . $DefaultDispatchDate . "',
												'" . $_SESSION['UserID'] . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
												'" . $LocalCurrencyPrice . "',
												'" . $PeriodNo . "',
												'" . $OrderNo . "',
												'" . -$OrderLine->Quantity . "',
												'" . $OrderLine->DiscountPercent . "',
												'" . $OrderLine->StandardCost . "',
												'" . ($QtyOnHandPrior - $OrderLine->Quantity) . "',
												'" . $OrderLine->Narrative . "'
											)";
			} else {
				// its an assembly or dummy and assemblies/dummies always have nil stock (by definition they are made up at the time of dispatch  so new qty on hand will be nil
				if (empty($OrderLine->StandardCost)) {
					$OrderLine->StandardCost = 0;
				}
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												userid,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												narrative
											) VALUES (
												'" . $OrderLine->StockID . "',
												10,
												'" . $InvoiceNo . "',
												'" . $_SESSION['Items' . $Identifier]->Location . "',
												'" . $DefaultDispatchDate . "',
												'" . $_SESSION['UserID'] . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
												'" . $LocalCurrencyPrice . "',
												'" . $PeriodNo . "',
												'" . $OrderNo . "',
												'" . -$OrderLine->Quantity . "',
												'" . $OrderLine->DiscountPercent . "',
												'" . $OrderLine->StandardCost . "',
												'" . $OrderLine->Narrative . "'
											)";
			}

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records could not be inserted because');
			$DbgMsg = _('The following SQL to insert the stock movement records was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

			/*Insert the taxes that applied to this line */
			foreach ($OrderLine->Taxes as $Tax) {

				$SQL = "INSERT INTO stockmovestaxes (stkmoveno,
													taxauthid,
													taxrate,
													taxcalculationorder,
													taxontax
												) VALUES (
													'" . $StkMoveNo . "',
													'" . $Tax->TaxAuthID . "',
													'" . $Tax->TaxRate . "',
													'" . $Tax->TaxCalculationOrder . "',
													'" . $Tax->TaxOnTax . "'
												)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Taxes and rates applicable to this invoice line item could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement tax detail records was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			} //end for each tax for the line
			/* Controlled stuff not currently handled by counter orders

			Insert the StockSerialMovements and update the StockSerialItems  for controlled items

			if ($OrderLine->Controlled ==1){
			foreach($OrderLine->SerialItems as $Item){
			//We need to add the StockSerialItem record and the StockSerialMoves as well

			$SQL = "UPDATE stockserialitems
			SET quantity= quantity - " . $Item->BundleQty . "
			WHERE stockid='" . $OrderLine->StockID . "'
			AND loccode='" . $_SESSION['Items'.$Identifier]->Location . "'
			AND serialno='" . $Item->BundleRef . "'";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be updated because');
			$DbgMsg = _('The following SQL to update the serial stock item record was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			// now insert the serial stock movement

			$SQL = "INSERT INTO stockserialmoves (stockmoveno,
			stockid,
			serialno,
			moveqty)
			VALUES (" . $StkMoveNo . ",
			'" . $OrderLine->StockID . "',
			'" . $Item->BundleRef . "',
			" . -$Item->BundleQty . ")";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock movement record could not be inserted because');
			$DbgMsg = _('The following SQL to insert the serial stock movement records was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			}// foreach controlled item in the serialitems array
			} //end if the orderline is a controlled item

			End of controlled stuff not currently handled by counter orders
			*/

			$SalesValue = 0;
			if ($ExRate > 0) {
				$SalesValue = $OrderLine->Price * $OrderLine->Quantity / $ExRate;
			}

			/*Insert Sales Analysis records */

			$SQL = "SELECT COUNT(*),
							salesanalysis.stockid,
							salesanalysis.stkcategory,
							salesanalysis.cust,
							salesanalysis.custbranch,
							salesanalysis.area,
							salesanalysis.periodno,
							salesanalysis.typeabbrev,
							salesanalysis.salesperson
						FROM salesanalysis
						INNER JOIN custbranch
							ON salesanalysis.cust=custbranch.debtorno
							AND salesanalysis.custbranch=custbranch.branchcode
							AND salesanalysis.area=custbranch.area
						INNER JOIN stockmaster
							ON salesanalysis.stkcategory=stockmaster.categoryid
							AND salesanalysis.stockid=stockmaster.stockid
						WHERE salesanalysis.salesperson='" . $_SESSION['Items' . $Identifier]->SalesPerson . "'
							AND salesanalysis.typeabbrev ='" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "'
							AND salesanalysis.periodno='" . $PeriodNo . "'
							AND salesanalysis.cust " . LIKE . " '" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'
							AND salesanalysis.custbranch " . LIKE . " '" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "'
							AND salesanalysis.stockid " . LIKE . " '" . $OrderLine->StockID . "'
							AND salesanalysis.budgetoractual=1
						GROUP BY salesanalysis.stockid,
								salesanalysis.stkcategory,
								salesanalysis.cust,
								salesanalysis.custbranch,
								salesanalysis.area,
								salesanalysis.periodno,
								salesanalysis.typeabbrev,
								salesanalysis.salesperson";

			$ErrMsg = _('The count of existing Sales analysis records could not run because');
			$DbgMsg = _('SQL to count the no of sales analysis records');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			$MyRow = DB_fetch_row($Result);

			if ($MyRow[0] > 0) {
				/*Update the existing record that already exists */

				$SQL = "UPDATE salesanalysis
							SET amt=amt+" . ($SalesValue) . ",
								cost=cost+" . ($OrderLine->StandardCost * $OrderLine->Quantity) . ",
								qty=qty+" . $OrderLine->Quantity . ",
								disc=disc+" . ($OrderLine->DiscountPercent * $SalesValue) . "
							WHERE salesanalysis.area='" . $MyRow[5] . "'
								AND salesanalysis.salesperson='" . $_SESSION['Items' . $Identifier]->SalesPerson . "'
								AND typeabbrev ='" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "'
								AND periodno = '" . $PeriodNo . "'
								AND cust " . LIKE . " '" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'
								AND custbranch " . LIKE . " '" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "'
								AND stockid " . LIKE . " '" . $OrderLine->StockID . "'
								AND salesanalysis.stkcategory ='" . $MyRow[2] . "'
								AND budgetoractual=1";

			} else {
				/* insert a new sales analysis record */

				$SQL = "INSERT INTO salesanalysis (typeabbrev,
													periodno,
													amt,
													cost,
													cust,
													custbranch,
													qty,
													disc,
													stockid,
													area,
													budgetoractual,
													salesperson,
													stkcategory
												)
										SELECT  '" . $_SESSION['Items' . $Identifier]->DefaultSalesType . "',
												'" . $PeriodNo . "',
												'" . ($SalesValue) . "',
												'" . ($OrderLine->StandardCost * $OrderLine->Quantity) . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "',
												'" . $OrderLine->Quantity . "',
												'" . ($OrderLine->DiscountPercent * $SalesValue) . "',
												'" . $OrderLine->StockID . "',
												custbranch.area,
												1,
												'" . $_SESSION['Items' . $Identifier]->SalesPerson . "',
												stockmaster.categoryid
											FROM stockmaster,
												custbranch
											WHERE stockmaster.stockid = '" . $OrderLine->StockID . "'
												AND custbranch.debtorno = '" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'
												AND custbranch.branchcode='" . DB_escape_string($_SESSION['Items' . $Identifier]->Branch) . "'";
			}

			$ErrMsg = _('Sales analysis record could not be added or updated because');
			$DbgMsg = _('The following SQL to insert the sales analysis record was used');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			/* If GLLink_Stock then insert GLTrans to credit stock and debit cost of sales at standard cost*/

			if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 and $OrderLine->StandardCost != 0) {

				/*first the cost of sales entry*/

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . GetCOGSGLAccount($Area, $OrderLine->StockID, $_SESSION['Items' . $Identifier]->DefaultSalesType) . "',
											'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->StandardCost . "',
											'" . $OrderLine->StandardCost * $OrderLine->Quantity . "'
										)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The cost of sales GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				/*now the stock entry*/
				$StockGLCode = GetStockGLCode($OrderLine->StockID);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $StockGLCode['stockact'] . "',
											'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->StandardCost . "',
											'" . (-$OrderLine->StandardCost * $OrderLine->Quantity) . "'
										)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The stock side of the cost of sales GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			}
			/* end of if GL and stock integrated and standard cost !=0 */

			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1 and $OrderLine->Price != 0) {

				//Post sales transaction to GL credit sales
				$SalesGLAccounts = GetSalesGLAccount($Area, $OrderLine->StockID, $_SESSION['Items' . $Identifier]->DefaultSalesType);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $SalesGLAccounts['salesglcode'] . "',
											'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->Price . "',
											'" . (-$OrderLine->Price * $OrderLine->Quantity / $ExRate) . "'
										)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The sales GL posting could not be inserted because');
				$DbgMsg = '<br />' . _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				if ($OrderLine->DiscountPercent != 0) {

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount
											) VALUES (
												10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . $SalesGLAccounts['discountglcode'] . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . " - " . $OrderLine->StockID . " @ " . ($OrderLine->DiscountPercent * 100) . "%',
												'" . ($OrderLine->Price * $OrderLine->Quantity * $OrderLine->DiscountPercent / $ExRate) . "'
											)";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The sales discount GL posting could not be inserted because');
					$DbgMsg = _('The following SQL to insert the GLTrans record was used');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				}
				/*end of if discount !=0 */
			}
			/*end of if sales integrated with debtors */
		}
		/*end of OrderLine loop */

		if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {

			/*Post debtors transaction to GL debit debtors, credit freight re-charged and credit sales */
			if (($_SESSION['Items' . $Identifier]->total + filter_number_format($_POST['TaxTotal'])) != 0) {
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
											'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
											'" . (($_SESSION['Items' . $Identifier]->total + filter_number_format($_POST['TaxTotal'])) / $ExRate) . "'
										)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The total debtor GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the total debtors control GLTrans record was used');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			}

			foreach ($_SESSION['Items' . $Identifier]->TaxTotals as $TaxAuthID => $TaxAmount) {
				if ($TaxAmount != 0) {
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount
											) VALUES (
												10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . $_SESSION['Items' . $Identifier]->TaxGLCodes[$TaxAuthID] . "',
												'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
												'" . (-$TaxAmount / $ExRate) . "'
											)";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The tax GL posting could not be inserted because');
					$DbgMsg = _('The following SQL to insert the GLTrans record was used');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				}
			}

			EnsureGLEntriesBalance(10, $InvoiceNo);

			/*Also if GL is linked to debtors need to process the debit to bank and credit to debtors for the payment */
			/*Need to figure out the cross rate between customer currency and bank account currency */

			if ($_POST['AmountPaid'] != 0) {
				$ReceiptNumber = GetNextTransNo(12);
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											12,
											'" . $ReceiptNumber . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $_POST['BankAccount'] . "',
											'" . $_SESSION['Items' . $Identifier]->LocationName . ' ' . _('Counter Sale') . ' ' . $InvoiceNo . "',
											'" . (filter_number_format($_POST['AmountPaid']) / $ExRate) . "'
										)";
				$DbgMsg = _('The SQL that failed to insert the GL transaction for the bank account debit was');
				$ErrMsg = _('Cannot insert a GL transaction for the bank account debit');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

				/* Now Credit Debtors account with receipt */
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount
										) VALUES (
											12,
											'" . $ReceiptNumber . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
											'" . $_SESSION['Items' . $Identifier]->LocationName . ' ' . _('Counter Sale') . ' ' . $InvoiceNo . "',
											'" . -(filter_number_format($_POST['AmountPaid']) / $ExRate) . "'
										)";
				$DbgMsg = _('The SQL that failed to insert the GL transaction for the debtors account credit was');
				$ErrMsg = _('Cannot insert a GL transaction for the debtors account credit');
				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
			} //amount paid we not zero
			EnsureGLEntriesBalance(12, $ReceiptNumber);

		}
		/*end of if Sales and GL integrated */
		if ($_POST['AmountPaid'] != 0) {
			if (!isset($ReceiptNumber)) {
				$ReceiptNumber = GetNextTransNo(12);
			}
			//Now need to add the receipt banktrans record
			//First get the account currency that it has been banked into
			$Result = DB_query("SELECT rate
								FROM currencies
								INNER JOIN bankaccounts
									ON currencies.currabrev=bankaccounts.currcode
								WHERE bankaccounts.accountcode='" . $_POST['BankAccount'] . "'");
			$MyRow = DB_fetch_row($Result);
			$BankAccountExRate = $MyRow[0];

			/*
			 * Some interesting exchange rate conversion going on here
			 * Say :
			 * The business's functional currency is NZD
			 * Customer location counter sales are in AUD - 1 NZD = 0.80 AUD
			 * Banking money into a USD account - 1 NZD = 0.68 USD
			 *
			 * Customer sale is for $100 AUD
			 * GL entries  conver the AUD 100 to NZD  - 100 AUD / 0.80 = $125 NZD
			 * Banktrans entries convert the AUD 100 to USD using 100/0.8 * 0.68
			*/

			//insert the banktrans record in the currency of the bank account
			$SQL = "INSERT INTO banktrans (type,
											transno,
											bankact,
											ref,
											exrate,
											functionalexrate,
											transdate,
											banktranstype,
											amount,
											currcode
										) VALUES (
											12,
											'" . $ReceiptNumber . "',
											'" . $_POST['BankAccount'] . "',
											'" . $_SESSION['Items' . $Identifier]->LocationName . ' ' . _('Counter Sale') . ' ' . $InvoiceNo . "',
											'" . $ExRate . "',
											'" . $BankAccountExRate . "',
											'" . $DefaultDispatchDate . "',
											'" . $_POST['PaymentMethod'] . "',
											'" . filter_number_format($_POST['AmountPaid']) * $BankAccountExRate / $ExRate . "',
											'" . $_SESSION['Items' . $Identifier]->DefaultCurrency . "'
										)";

			$DbgMsg = _('The SQL that failed to insert the bank account transaction was');
			$ErrMsg = _('Cannot insert a bank transaction');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			//insert a new debtortrans for the receipt
			$SQL = "INSERT INTO debtortrans (transno,
											type,
											debtorno,
											trandate,
											inputdate,
											prd,
											reference,
											rate,
											ovamount,
											alloc,
											invtext,
											settled,
											salesperson
										) VALUES (
											'" . $ReceiptNumber . "',
											12,
											'" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "',
											'" . $DefaultDispatchDate . "',
											'" . date('Y-m-d H-i-s') . "',
											'" . $PeriodNo . "',
											'" . $InvoiceNo . "',
											'" . $ExRate . "',
											'" . -filter_number_format($_POST['AmountPaid']) . "',
											'" . -filter_number_format($_POST['AmountPaid']) . "',
											'" . $_SESSION['Items' . $Identifier]->LocationName . ' ' . _('Counter Sale') . "',
											'1',
											'" . $_SESSION['Items' . $Identifier]->SalesPerson . "'
										)";

			$DbgMsg = _('The SQL that failed to insert the customer receipt transaction was');
			$ErrMsg = _('Cannot insert a receipt transaction against the customer because');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			$ReceiptDebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');

			$SQL = "UPDATE debtorsmaster SET lastpaiddate = '" . $DefaultDispatchDate . "',
											lastpaid='" . filter_number_format($_POST['AmountPaid']) . "'
									WHERE debtorsmaster.debtorno='" . DB_escape_string($_SESSION['Items' . $Identifier]->DebtorNo) . "'";

			$DbgMsg = _('The SQL that failed to update the date of the last payment received was');
			$ErrMsg = _('Cannot update the customer record for the date of the last payment received because');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

			//and finally add the allocation record between receipt and invoice
			$SQL = "INSERT INTO custallocns (amt,
											datealloc,
											transid_allocfrom,
											transid_allocto
										) VALUES (
											'" . filter_number_format($_POST['AmountPaid']) . "',
											'" . $DefaultDispatchDate . "',
											'" . $ReceiptDebtorTransID . "',
											'" . $DebtorTransID . "'
										)";
			$DbgMsg = _('The SQL that failed to insert the allocation of the receipt to the invoice was');
			$ErrMsg = _('Cannot insert the customer allocation of the receipt to the invoice because');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		} //end if $_POST['AmountPaid']!= 0
		DB_Txn_Commit();
		// *************************************************************************
		//   E N D   O F   I N V O I C E   S Q L   P R O C E S S I N G
		// *************************************************************************
		unset($_SESSION['Items' . $Identifier]->LineItems);
		unset($_SESSION['Items' . $Identifier]);

		prnMsg(_('Invoice number') . ' ' . $InvoiceNo . ' ' . _('processed'), 'success');

		echo '<div class="centre">';

		if ($_SESSION['InvoicePortraitFormat'] == 0) {
			echo '<meta http-equiv="Refresh" content="0; url=', $RootPath, '/PrintCustTrans.php?FromTransNo=', $InvoiceNo, '&amp;InvOrCredit=Invoice&amp;PrintPDF=True" />';
		} else {
			echo '<meta http-equiv="Refresh" content="0; url=', $RootPath, '/PrintCustTransPortrait.php?FromTransNo=', $InvoiceNo, '&amp;InvOrCredit=Invoice&amp;PrintPDF=True" />';
		}

		echo '<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Start a new Counter Sale'), '</a></div>';

	}
	// There were input errors so don't process nuffin

} else {
	//pretend the user never tried to commit the sale
	unset($_POST['ProcessSale']);
}
/*******************************
 * end of Invoice Processing
 * *****************************
*/

/* Now show the stock item selection search stuff below */
if (!isset($_POST['ProcessSale'])) {
	if (isset($_POST['PartSearch']) and $_POST['PartSearch'] != '') {

		echo '<input type="hidden" name="PartSearch" value="', _('Yes Please'), '" />';

		if ($_SESSION['FrequentlyOrderedItems'] > 0) { //show the Frequently Order Items selection where configured to do so
			// Select the most recently ordered items for quick select
			$SixMonthsAgo = DateAdd(Date($_SESSION['DefaultDateFormat']), 'm', -6);

			$SQL = "SELECT stockmaster.units,
							stockmaster.description,
							stockmaster.stockid,
							salesorderdetails.stkcode,
							SUM(qtyinvoiced) Sales
						FROM salesorderdetails
						INNER JOIN stockmaster
							ON salesorderdetails.stkcode = stockmaster.stockid
						WHERE ActualDispatchDate >= '" . FormatDateForSQL($SixMonthsAgo) . "'
							AND stockmaster.controlled=0
						GROUP BY stkcode
						ORDER BY sales DESC
						LIMIT " . $_SESSION['FrequentlyOrderedItems'];
			$Result2 = DB_query($SQL);
			echo '<p class="page_title_text">
					<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', _('Frequently Ordered Items'), '
				</p>';
			echo '<div class="page_help_text">', _('Frequently Ordered Items, shows the most frequently ordered items in the last 6 months. You can choose from this list, or search further for other items'), '.
				</div>';
			echo '<table>
					<thead>
						<tr>
							<th class="SortedColumn">', _('Code'), '</th>
							<th class="SortedColumn">', _('Description'), '</th>
							<th>', _('Units'), '</th>
							<th>', _('On Hand'), '</th>
							<th>', _('On Demand'), '</th>
							<th>', _('On Order'), '</th>
							<th>', _('Available'), '</th>
							<th>', _('Quantity'), '</th>
						</tr>
					</thead>';
			$i = 0;

			echo '<tbody>';
			while ($MyRow = DB_fetch_array($Result2)) {
				// This code needs sorting out, but until then :
				$ImageSource = _('No Image');
				// Find the quantity in stock at location
				$QohSql = "SELECT sum(quantity)
						   FROM locstock
						   WHERE stockid='" . $MyRow['stockid'] . "' AND
						   loccode = '" . $_SESSION['Items' . $Identifier]->Location . "'";
				$QohResult = DB_query($QohSql);
				$QohRow = DB_fetch_row($QohResult);
				$QOH = $QohRow[0];

				// Find the quantity on outstanding sales orders
				$SQL = "SELECT SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS dem
							FROM salesorderdetails
							INNER JOIN salesorders
								ON salesorders.orderno = salesorderdetails.orderno
							WHERE  salesorders.fromstkloc='" . $_SESSION['Items' . $Identifier]->Location . "'
								AND salesorderdetails.completed=0
								AND salesorders.quotation=0
								AND salesorderdetails.stkcode='" . $MyRow['stockid'] . "'";

				$ErrMsg = _('The demand for this product from') . ' ' . $_SESSION['Items' . $Identifier]->Location . ' ' . _('cannot be retrieved because');
				$DemandResult = DB_query($SQL, $ErrMsg);

				$DemandRow = DB_fetch_row($DemandResult);
				if ($DemandRow[0] != null) {
					$DemandQty = $DemandRow[0];
				} else {
					$DemandQty = 0;
				}

				// Get the QOO due to Purchase orders for all locations. Function defined in SQL_CommonFunctions.php
				$QOO = GetQuantityOnOrderDueToPurchaseOrders($MyRow['stockid']);
				// Get the QOO dues to Work Orders for all locations. Function defined in SQL_CommonFunctions.php
				$QOO+= GetQuantityOnOrderDueToWorkOrders($MyRow['stockid']);

				$Available = $QOH - $DemandQty + $QOO;

				echo '<tr class="striped_row">
						<td>', $MyRow['stockid'], '</td>
						<td>', $MyRow['description'], '</td>
						<td>', $MyRow['units'], '</td>
						<td class="number">', $QOH, '</td>
						<td class="number">', $DemandQty, '</td>
						<td class="number">', $QOO, '</td>
						<td class="number">', $Available, '</td>
						<td><input class="number" type="text" size="6" name="OrderQty', $i, '" value="0" />
							<input type="hidden" name="StockID', $i, '" value="', $MyRow['stockid'], '" />
						</td>
					</tr>';
				++$i; //index for controls
				#end of page full new headings if

			}
			echo '</tbody>';
			#end of while loop for Frequently Ordered Items
			echo '<tr>
					<td style="text-align:center" colspan="8">
						<input type="hidden" name="SelectingOrderItems" value="1" />
						<input type="submit" value="', _('Add to Sale'), '" />
					</td>
				</tr>
			</table>';
		} //end of if Frequently Ordered Items > 0
		if (isset($Msg)) {
			echo '<div class="page_help_text"><b>', $Msg, '</b></div>';
		}
		echo '<p class="page_title_text">
				<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', _('Search'), '" alt="" />', _('Search for Items'), '
			</p>';
		echo '<div class="page_help_text">
				', _('Search for Items'), '.&nbsp;', _('Searches the database for items, you can narrow the results by selecting a stock category, or just enter a partial item description or partial item code'), '.
			</div>';
		echo '<fieldset>
				<legend>', _('Item Search Criteria'), '</legend>';

		$SQL = "SELECT categoryid,
					categorydescription
				FROM stockcategory
				WHERE stocktype='F' OR stocktype='D'
				ORDER BY categorydescription";
		$Result1 = DB_query($SQL);
		echo '<field>
				<label for="StockCat">', _('Select a Stock Category'), ': </label>
				<select name="StockCat">';

		if (!isset($_POST['StockCat']) or $_POST['StockCat'] == 'All') {
			echo '<option selected="selected" value="All">', _('All'), '</option>';
			$_POST['StockCat'] = 'All';
		} else {
			echo '<option value="All">', _('All'), '</option>';
		}
		while ($MyRow1 = DB_fetch_array($Result1)) {
			if ($_POST['StockCat'] == $MyRow1['categoryid']) {
				echo '<option selected="selected" value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
			} else {
				echo '<option value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
			}
		}
		echo '</select>
			</field>';

		if (!isset($_POST['Keywords'])) {
			$_POST['Keywords'] = '';
		}
		echo '<field>
				<label for="Keywords">', _('Enter partial Description'), ':</label>
				<input type="search" name="Keywords" size="20" maxlength="25" value="', $_POST['Keywords'], '" />
			</field>';

		if (!isset($_POST['StockCode'])) {
			$_POST['StockCode'] = '';
		}
		echo '<field>
				<label for="StockCode"> ', _('OR'), ' ', _('Enter extract of the Stock Code'), ':</label>
				<input type="search" autofocus="autofocus" name="StockCode" size="15" maxlength="18" value="', $_POST['StockCode'], '" />
			</field>
		</fieldset>';

		echo '<div class="centre">
				<input type="submit" name="Search" value="', _('Search Now'), '" />
				<input type="submit" name="QuickEntry" value="', _('Use Quick Entry'), '" />
			</div>';

		// Add some useful help as the order progresses
		if (isset($SearchResult)) {
			echo '<div class="page_help_text">', _('Select an item by entering the quantity required.  Click Order when ready.'), '</div>';
		}

		if (isset($SearchResult)) {
			echo '<table class="table1">
					<thead>
						<tr>
							<td>
								<input type="hidden" name="PreviousList" value="', strval($Offset - 1), '" />
								<input type="submit" name="Previous" value="', _('Prev'), '" />
							</td>
							<td style="text-align:center" colspan="6">
								<input type="hidden" name="SelectingOrderItems" value="1" />
								<input type="submit" value="', _('Add to Sale'), '" />
							</td>
							<td>
								<input type="hidden" name="NextList" value="', strval($Offset + 1), '" />
								<input type="submit" name="Next" value="', _('Next'), '" />
							</td>
						</tr>
						<tr>
							<th class="SortedColumn">', _('Code'), '</th>
							<th class="SortedColumn">', _('Description'), '</th>
							<th>', _('Units'), '</th>
							<th>', _('On Hand'), '</th>
							<th>', _('On Demand'), '</th>
							<th>', _('On Order'), '</th>
							<th>', _('Available'), '</th>
							<th>', _('Quantity'), '</th>
						</tr>
					</thead>';
			$i = 0;

			echo '<tbody>';
			while ($MyRow = DB_fetch_array($SearchResult)) {

				// Find the quantity in stock at location
				$QOHSql = "SELECT sum(quantity) AS qoh
 					   FROM locstock
					   WHERE locstock.stockid='" . $MyRow['stockid'] . "'
					   AND loccode = '" . $_SESSION['Items' . $Identifier]->Location . "'";
				$QOHResult = DB_query($QOHSql);
				$QOHRow = DB_fetch_array($QOHResult);
				$QOH = $QOHRow['qoh'];

				// Find the quantity on outstanding sales orders
				$SQL = "SELECT SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS dem
							FROM salesorderdetails
							INNER JOIN salesorders
								ON salesorders.orderno = salesorderdetails.orderno
							WHERE salesorders.fromstkloc='" . $_SESSION['Items' . $Identifier]->Location . "'
								AND salesorderdetails.completed=0
								AND salesorders.quotation=0
								AND salesorderdetails.stkcode='" . $MyRow['stockid'] . "'";

				$ErrMsg = _('The demand for this product from') . ' ' . $_SESSION['Items' . $Identifier]->Location . ' ' . _('cannot be retrieved because');
				$DemandResult = DB_query($SQL, $ErrMsg);

				$DemandRow = DB_fetch_row($DemandResult);
				if ($DemandRow[0] != null) {
					$DemandQty = $DemandRow[0];
				} else {
					$DemandQty = 0;
				}

				// Get the QOO due to Purchase orders for all locations. Function defined in SQL_CommonFunctions.php
				$QOO = GetQuantityOnOrderDueToPurchaseOrders($MyRow['stockid']);
				// Get the QOO dues to Work Orders for all locations. Function defined in SQL_CommonFunctions.php
				$QOO+= GetQuantityOnOrderDueToWorkOrders($MyRow['stockid']);

				$Available = $QOH - $DemandQty + $QOO;

				echo '<tr class="striped_row">
						<td>', $MyRow['stockid'], '</td>
						<td>', $MyRow['description'], '</td>
						<td>', $MyRow['units'], '</td>
						<td class="number">', locale_number_format($QOH, $MyRow['decimalplaces']), '</td>
						<td class="number">', locale_number_format($DemandQty, $MyRow['decimalplaces']), '</td>
						<td class="number">', locale_number_format($QOO, $MyRow['decimalplaces']), '</td>
						<td class="number">', locale_number_format($Available, $MyRow['decimalplaces']), '</td>
						<td>
							<input class="number" required="required" type="text" size="6" name="OrderQty', $i, '" value="0" />
							<input type="hidden" name="StockID', $i, '" value="', $MyRow['stockid'], '" />
						</td>
					</tr>';
				++$i;
				#end of page full new headings if

			}
			#end of while loop
			echo '</tbody>';
			echo '<tr>
					<td>
						<input type="hidden" name="previous" value="', strval($Offset - 1), '" />
						<input type="submit" name="Prev" value="', _('Prev'), '" />
					</td>
					<td style="text-align:center" colspan="6">
						<input type="hidden" name="SelectingOrderItems" value="1" />
						<input type="submit" value="', _('Add to Sale'), '" />
					</td>
					<td>
						<input type="hidden" name="NextList" value="', strval($Offset + 1), '" />
						<input type="submit" name="Next" value="', _('Next'), '" />
					</td>
				</tr>';
			echo '</table>';

			echo '<input type="hidden" name="CustRef" value="', $_SESSION['Items' . $Identifier]->CustRef, '" />';
			echo '<input type="hidden" name="Comments" value="', $_SESSION['Items' . $Identifier]->Comments, '" />';
			echo '<input type="hidden" name="DeliverTo" value="', $_SESSION['Items' . $Identifier]->DeliverTo, '" />';
			echo '<input type="hidden" name="PhoneNo" value="', $_SESSION['Items' . $Identifier]->PhoneNo, '" />';
			echo '<input type="hidden" name="Email" value="', $_SESSION['Items' . $Identifier]->Email, '" />';
			echo '<input type="hidden" name="SalesPerson" value="', $_SESSION['Items' . $Identifier]->SalesPerson, '" />';
		} #end if SearchResults to show

	}
	/*end of PartSearch options to be displayed */
	else {
		/* show the quick entry form variable */

		echo '<div class="page_help_text">
				<b>', _('Use this form to add items quickly if the item codes are already known'), '</b>
			</div>';
		if (count($_SESSION['Items' . $Identifier]->LineItems) == 0) {
			echo '<input type="hidden" name="CustRef" value="', $_SESSION['Items' . $Identifier]->CustRef, '" />';
			echo '<input type="hidden" name="Comments" value="', $_SESSION['Items' . $Identifier]->Comments, '" />';
			echo '<input type="hidden" name="DeliverTo" value="', $_SESSION['Items' . $Identifier]->DeliverTo, '" />';
			echo '<input type="hidden" name="PhoneNo" value="', $_SESSION['Items' . $Identifier]->PhoneNo, '" />';
			echo '<input type="hidden" name="Email" value="', $_SESSION['Items' . $Identifier]->Email, '" />';
			echo '<input type="hidden" name="SalesPerson" value="', $_SESSION['Items' . $Identifier]->SalesPerson, '" />';
		}

		$SQL = "SELECT stockid
				FROM stockmaster
				WHERE controlled = 0";
		$ErrMsg = _('Could not fetch items list because');
		$DbgMsg = _('The sql that was used to fetch items list was ');
		$ItemsResult = DB_query($SQL, $ErrMsg, $DbgMsg);
		$ItemCount = DB_num_rows($ItemsResult);
		if ($ItemCount == 0) {
			prnMsg(_('There are no available item(s) retrieved from the database'), 'warn');
		} else if (!isset($_SESSION['ItemList']) || $ItemCount != count($_SESSION['ItemList'])) {
			unset($_SESSION['ItemList']);
			$_SESSION['ItemList'] = array();
			while ($MyRow = DB_fetch_array($ItemsResult)) {
				$_SESSION['ItemList'][] = $MyRow['stockid'];
			}
		}
		echo '<input type="text" autofocus="autofocus" onkeydown="if (event.keyCode == 13 || event.which == 13) AddQuickEntry(this);" onchange="AddQuickEntry(this);" placeholder="Item Code";  /><br><br>';
		echo '<table id="QuickEntryTable" border="1">
				<tr>
					<th>', _('Item Code'), '</th>
					<th>', _('Quantity'), '</th>
				</tr>';
		$DefaultDeliveryDate = DateAdd(Date($_SESSION['DefaultDateFormat']), 'd', $_SESSION['Items' . $Identifier]->DeliveryDays);
		for ($i = 1;$i <= $_SESSION['QuickEntries'];$i++) {

			echo '<tr class="striped_row">';
			/* Do not display colum unless customer requires po line number by sales order line*/
			if ($i == 1) {
				echo '<td><input type="text" autofocus="autofocus" name="part_', $i, '" size="21" maxlength="20" /></td>';
			} else {
				echo '<td><input type="text" name="part_', $i, '" size="21" maxlength="20" /></td>';
			}
			echo '<td>
					<input type="text" class="number" name="qty_', $i, '" size="6" maxlength="6" />
					<input type="hidden" class="date" name="ItemDue_', $i, '" value="', $DefaultDeliveryDate, '" />
				</td>
			</tr>';
		}
		echo '</table>';
		echo '<div class="centre">
				<input type="hidden" id="TotalQuickEntryRows" name="TotalQuickEntryRows" value="', $_SESSION['QuickEntries'], '" />
				<input type="submit" name="QuickEntry" value="', _('Quick Entry'), '" />
				<input type="submit" name="PartSearch" value="', _('Search Parts'), '" />
			</div>';

	}
	if ($_SESSION['Items' . $Identifier]->ItemsOrdered >= 1) {
		echo '<div class="centre">
				<input type="submit" name="CancelOrder" value="', _('Cancel Sale'), '" onclick="return MakeConfirm(\'', _('Are you sure you wish to cancel this sale?'), '\');" />
			</div>';
	}
	echo '</form>';
}
echo '<script src="', $RootPath, 'javscripts/CounterSalesFunctions.js"></script>';
?>

<script defer="defer">
	CounterSales.SetItemList(<?php echo json_encode($_SESSION['ItemList']); ?>);
	CounterSales.SetQuickEntryTableId('QuickEntryTable');
	CounterSales.SetRowCounter(<?php echo empty($i) ? 0 : $i; ?>);
	CounterSales.SetDefaultDeliveryDate(<?php echo empty($DefaultDeliveryDate) ? '""' : $DefaultDeliveryDate; ?>);
	CounterSales.SetTotalQuickEntryRowsId('TotalQuickEntryRows');
	CounterSales.SetTotalDue(<?php echo round($_SESSION['Items' . $Identifier]->total + $_POST['TaxTotal'] + $RoundingAdjustment, $_SESSION['Items' . $Identifier]->CurrDecimalPlaces) ?>);
	CounterSales.SetDecimal(<?php echo $_SESSION['Items' . $Identifier]->CurrDecimalPlaces; ?>);
	CounterSales.SetCashReceivedId('CashReceived');
	CounterSales.SetAmountPaidId('AmountPaid');
	CounterSales.SetChangeDueId('ChangeDue');
</script>

<?php
include ('includes/footer.php');
?>