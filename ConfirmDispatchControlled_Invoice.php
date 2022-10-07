<?php

include('includes/DefineCartClass.php');
include('includes/DefineSerialItems.php');
include('includes/session.php');
$Title = _('Specify Dispatched Controlled Items');

/* Session started in header.php for password checking and authorisation level check */
include('includes/header.php');


if (empty($_GET['identifier'])) {
	/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
	$Identifier = date('U');
} else {
	$Identifier = $_GET['identifier'];
}

if (isset($_GET['LineNo'])) {
	$LineNo = (int) $_GET['LineNo'];
} elseif (isset($_POST['LineNo'])) {
	$LineNo = (int) $_POST['LineNo'];
} else {
	echo '<div class="centre">
			<a href="' . $RootPath . '/ConfirmDispatch_Invoice.php">' . _('Select a line item to invoice') . '</a>
			<br />
			<br />';
	prnMsg(_('This page can only be opened if a line item on a sales order to be invoiced has been selected') . '. ' . _('Please do that first'), 'error');
	echo '</div>';
	include('includes/footer.php');
	exit;
}

if (!isset($_SESSION['Items' . $Identifier]) or !isset($_SESSION['ProcessingOrder'])) {
	/* This page can only be called with a sales order number to invoice */
	echo '<div class="centre">
			<a href="' . $RootPath . '/SelectSalesOrder.php">' . _('Select a sales order to invoice') . '</a>
			<br />';
	prnMsg(_('This page can only be opened if a sales order and line item has been selected Please do that first'), 'error');
	echo '</div>';
	include('includes/footer.php');
	exit;
}


/*Save some typing by referring to the line item class object in short form */
$LineItem =& $_SESSION['Items' . $Identifier]->LineItems[$LineNo];


//Make sure this item is really controlled
if ($LineItem->Controlled != 1) {
	echo '<div class="centre"><a href="' . $RootPath . '/ConfirmDispatch_Invoice.php">' . _('Back to the Sales Order') . '</a></div>';
	echo '<br />';
	prnMsg(_('The line item must be defined as controlled to require input of the batch numbers or serial numbers being sold'), 'error');
	include('includes/footer.php');
	exit;
}

/********************************************
Get the page going....
********************************************/
echo '<div class="centre">';

echo '<br /><a href="' . $RootPath . '/ConfirmDispatch_Invoice.php?identifier=' . urlencode($Identifier) . '">' . _('Back to Confirmation of Dispatch') . '/' . _('Invoice') . '</a>';

echo '<br /><b>' . _('Dispatch of up to') . ' ' . locale_number_format($LineItem->Quantity - $LineItem->QtyInv, $LineItem->DecimalPlaces) . ' ' . _('Controlled items') . ' ' . $LineItem->StockID . ' - ' . $LineItem->ItemDescription . ' ' . _('on order') . ' ' . $_SESSION['Items' . $Identifier]->OrderNo . ' ' . _('to') . ' ' . $_SESSION['Items' . $Identifier]->CustomerName . '</b></div>';

/** vars needed by InputSerialItem : **/
$StockId = $LineItem->StockID;
$RecvQty = $LineItem->Quantity - $LineItem->QtyInv;
$ItemMustExist = true;
/*Can only invoice valid batches/serial numbered items that exist */
$LocationOut = $_SESSION['Items' . $Identifier]->Location;
if ($_SESSION['RequirePickingNote'] == 1) {
	$OrderstoPick = $_SESSION['Items' . $Identifier]->OrderNo;
} else {
	unset($OrderstoPick);
}
$InOutModifier = 1;
$ShowExisting = true;

include('includes/InputSerialItems.php');

/*TotalQuantity set inside this include file from the sum of the bundles
of the item selected for dispatch */
$_SESSION['Items' . $Identifier]->LineItems[$LineNo]->QtyDispatched = $TotalQuantity;

include('includes/footer.php');
exit;
?>