<?php


include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

/* Check that the config variable is set for
 * picking notes and get out if not.
 */
if ($_SESSION['RequirePickingNote']==0) {
	$Title = _('Picking Lists Not Enabled');
	include('includes/header.php');
	echo '<br />';
	prnMsg( _('The system is not configured for picking lists. A configuration parameter is required where picking slips are required. Please consult your system administrator.'), 'info');
	include('includes/footer.php');
	exit;
}

/* Show selection screen if we have no orders to work with */
if ((!isset($_GET['TransNo']) or $_GET['TransNo']=='') and !isset($_POST['TransDate'])){
	$Title = _('Select Picking Lists');
	include('includes/header.php');
	$sql="SELECT locations.loccode,
				locationname
			FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$result=DB_query($sql);
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p><br />';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" name="form">
		<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Create picking lists for all deliveries to be made on').' : ' . '</td>
			<td><input type="text" required="required" autofocus="autofocus" class="date" name="TransDate" maxlength="10" size="11" value="' . date($_SESSION['DefaultDateFormat'], mktime(date('m'),date('Y'),date('d')+1)) . '" /></td>
		</tr>
		<tr>
			<td>' . _('From Warehouse').' : ' . '</td>
			<td><select required="required" name="loccode">';
	while ($myrow=DB_fetch_array($result)) {
		echo '<option value="'.$myrow['loccode'].'">' . $myrow['locationname'] . '</option>';
	}
	echo '</select></td>
		</tr>
		</table>';
	echo '<br />
		<div class="centre">
			<input type="submit" name="Process" value="' . _('Print Picking Lists') . '" />
		</div>
        </div>
		</form>';
	include('includes/footer.php');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the order header details from the database');

if (!isset($_POST['TransDate']) AND $_GET['TransNo'] != 'Preview') {
/* If there is no transaction date set, then it must be for a single order */
	$sql = "SELECT salesorders.debtorno,
        		salesorders.orderno,
        		salesorders.customerref,
        		salesorders.comments,
        		salesorders.orddate,
        		salesorders.deliverto,
        		salesorders.deladd1,
        		salesorders.deladd2,
        		salesorders.deladd3,
        		salesorders.deladd4,
        		salesorders.deladd5,
        		salesorders.deladd6,
        		salesorders.deliverblind,
        		salesorders.deliverydate,
        		debtorsmaster.name,
        		debtorsmaster.address1,
        		debtorsmaster.address2,
        		debtorsmaster.address3,
        		debtorsmaster.address4,
        		debtorsmaster.address5,
        		debtorsmaster.address6,
        		shippers.shippername,
        		salesorders.printedpackingslip,
        		salesorders.datepackingslipprinted,
        		locations.locationname
        	FROM salesorders,
        		debtorsmaster,
        		shippers,
        		locations
        	WHERE salesorders.debtorno=debtorsmaster.debtorno
        	AND salesorders.shipvia=shippers.shipper_id
        	AND salesorders.fromstkloc=locations.loccode
        	AND salesorders.orderno='" . $_GET['TransNo']."'";
} else if (isset($_POST['TransDate'])
		OR (isset($_GET['TransNo']) AND $_GET['TransNo'] != 'Preview')) {
/* We are printing picking lists for all orders on a day */
	$sql = "SELECT salesorders.debtorno,
            		salesorders.orderno,
            		salesorders.customerref,
            		salesorders.comments,
            		salesorders.orddate,
            		salesorders.deliverto,
            		salesorders.deladd1,
            		salesorders.deladd2,
            		salesorders.deladd3,
            		salesorders.deladd4,
            		salesorders.deladd5,
            		salesorders.deladd6,
            		salesorders.deliverblind,
            		salesorders.deliverydate,
            		debtorsmaster.name,
            		debtorsmaster.address1,
            		debtorsmaster.address2,
            		debtorsmaster.address3,
            		debtorsmaster.address4,
            		debtorsmaster.address5,
            		debtorsmaster.address6,
            		shippers.shippername,
            		salesorders.printedpackingslip,
            		salesorders.datepackingslipprinted,
            		locations.locationname
            	FROM salesorders,
            		debtorsmaster,
            		shippers,
            		locations
            	WHERE salesorders.debtorno=debtorsmaster.debtorno
            	AND salesorders.shipvia=shippers.shipper_id
            	AND salesorders.fromstkloc=locations.loccode
            	AND salesorders.fromstkloc='".$_POST['loccode']."'
            	AND salesorders.deliverydate<='" . FormatDateForSQL($_POST['TransDate'])."'";
}

if ($_SESSION['SalesmanLogin'] != '') {
	$sql .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

if (isset($_POST['TransDate'])
	OR (isset($_GET['TransNo']) AND $_GET['TransNo'] != 'Preview')) {
	$result=DB_query($sql, $ErrMsg);

	/*if there are no rows, there's a problem. */
	if (DB_num_rows($result)==0){
		$Title = _('Print Picking List Error');
		include('includes/header.php');
		echo '<br />';
		prnMsg( _('Unable to Locate any orders for this criteria '), 'info');
		echo '<br />
				<table class="selection">
				<tr>
					<td><a href="'. $RootPath . '/PDFPickingList.php">' . _('Enter Another Date') . '</a></td>
				</tr>
				</table>
				<br />';
		include('includes/footer.php');
		exit();
	}

	/*retrieve the order details from the database and place them in an array */
	$i=0;
	while ($myrow=DB_fetch_array($result)) {
		$OrdersToPick[$i]=$myrow;
		$i++;
	}
} else {
	$OrdersToPick[0]['debtorno']=str_pad('',10,'x');
	$OrdersToPick[0]['orderno']='Preview';
	$OrdersToPick[0]['customerref']=str_pad('',20,'x');
	$OrdersToPick[0]['comments']=str_pad('',100,'x');
	$OrdersToPick[0]['orddate']='1900-00-01';
	$OrdersToPick[0]['deliverto']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd1']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd2']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd3']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd4']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd5']=str_pad('',20,'x');
	$OrdersToPick[0]['deladd6']=str_pad('',20,'x');
	$OrdersToPick[0]['deliverblind']=str_pad('',20,'x');
	$OrdersToPick[0]['deliverydate']='1900-00-01';
	$OrdersToPick[0]['name']=str_pad('',20,'x');
	$OrdersToPick[0]['address1']=str_pad('',20,'x');
	$OrdersToPick[0]['address2']=str_pad('',20,'x');
	$OrdersToPick[0]['address3']=str_pad('',20,'x');
	$OrdersToPick[0]['address4']=str_pad('',20,'x');
	$OrdersToPick[0]['address5']=str_pad('',20,'x');
	$OrdersToPick[0]['address6']=str_pad('',20,'x');
	$OrdersToPick[0]['shippername']=str_pad('',20,'x');
	$OrdersToPick[0]['printedpackingslip']=str_pad('',20,'x');
	$OrdersToPick[0]['datepackingslipprinted']='1900-00-01';
	$OrdersToPick[0]['locationname']=str_pad('',15,'x');
}
/* Then there's an order to print and its not been printed already (or its been flagged for reprinting/ge_Width=807;
)
LETS GO */

if ($OrdersToPick[0]['orderno']=='Preview') {
	$FormDesign = simplexml_load_file(sys_get_temp_dir().'/PickingList.xml');
} else {
	$FormDesign = simplexml_load_file($PathPrefix.'companies/'.$_SESSION['DatabaseName'].'/FormDesigns/PickingList.xml');
}

$PaperSize = $FormDesign->PaperSize;
include('includes/PDFStarter.php');
$PDF->addInfo('Title', _('Picking List') );
$PDF->addInfo('Subject', _('Laser Picking List') );
$FontSize=12;
$ListCount = 0;
$Copy='';

$line_height=$FormDesign->LineHeight;

for ($i=0;$i<sizeof($OrdersToPick);$i++){
/*Cycle through each of the orders to pick */
	if ($i>0) {
		$PDF->newPage();
	}

	/* Now ... Has the order got any line items still outstanding to be picked */

	$PageNumber = 1;

	if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
		$ErrMsg = _('There was a problem retrieving the order line details for Order Number') . ' ' .
			$OrdersToPick[$i]['orderno'] . ' ' . _('from the database');

		/* Are there any picking lists for this order already */
		$sql="SELECT COUNT(orderno)
				FROM pickinglists
				WHERE orderno='" . $OrdersToPick[$i]['orderno'] . "'";
		$CountResult=DB_query($sql);
		$Count=DB_fetch_row($CountResult);
		if ($Count[0]==0) {
		/* There are no previous picking lists for this order */
			$sql = "SELECT salesorderdetails.stkcode,
            				stockmaster.description,
            				salesorderdetails.orderlineno,
            				salesorderdetails.quantity,
            				salesorderdetails.qtyinvoiced,
            				salesorderdetails.unitprice,
            				salesorderdetails.narrative,
            				stockmaster.decimalplaces
            			FROM salesorderdetails
            			INNER JOIN stockmaster
            				ON salesorderdetails.stkcode=stockmaster.stockid
            			WHERE salesorderdetails.orderno='" . $OrdersToPick[$i]['orderno'] ."'";
		} else {
		/* There are previous picking lists for this order so
		 * need to take those quantities into account
		 */
			$sql = "SELECT salesorderdetails.stkcode,
            				stockmaster.description,
            				salesorderdetails.orderlineno,
            				salesorderdetails.quantity,
            				salesorderdetails.qtyinvoiced,
            				SUM(pickinglistdetails.qtyexpected) as qtyexpected,
            				SUM(pickinglistdetails.qtypicked) as qtypicked,
            				salesorderdetails.unitprice,
            				salesorderdetails.narrative,
            				stockmaster.decimalplaces
            			FROM salesorderdetails
            			INNER JOIN stockmaster
            				ON salesorderdetails.stkcode=stockmaster.stockid
            			LEFT JOIN pickinglists
            				ON salesorderdetails.orderno=pickinglists.orderno
            			LEFT JOIN pickinglistdetails
            				ON pickinglists.pickinglistno=pickinglistdetails.pickinglistno
            			WHERE salesorderdetails.orderno='" . $OrdersToPick[$i]['orderno'] ."'
            			AND salesorderdetails.orderlineno=pickinglistdetails.orderlineno";
		}
		$LineResult=DB_query($sql, $ErrMsg);
	}
	if ((isset($_GET['TransNo'])
		AND $_GET['TransNo'] == 'Preview')
		OR (isset($LineResult)
		AND DB_num_rows($LineResult)>0)){
		/*Yes there are line items to start the ball rolling with a page header */
		include('includes/PDFPickingListHeader.inc');
		if (isset($_POST['TransDate']) or (isset($_GET['TransNo']) and $_GET['TransNo'] != 'Preview')) {
			$LinesToShow=DB_num_rows($LineResult);
			$PickingListNo = GetNextTransNo(19);
			$sql="INSERT INTO pickinglists
				VALUES (
				'" . $PickingListNo ."',
				'" . $OrdersToPick[$i]['orderno']."',
				'" . FormatDateForSQL($_POST['TransDate'])."',
				'" . date('Y-m-d')."',
				'0000-00-00')";
			$headerresult=DB_query($sql);
		} else {
			$LinesToShow=1;
		}
		$YPos=$FormDesign->Data->y;
		$Lines=0;

		while ($Lines<$LinesToShow){
			if (isset($_GET['TransNo']) and $_GET['TransNo'] == 'Preview') {
				$myrow2['stkcode']=str_pad('',10,'x');
				$myrow2['decimalplaces']=2;
				$DisplayQty='XXXX.XX';
				$DisplayPrevDel='XXXX.XX';
				$DisplayQtySupplied='XXXX.XX';
				$myrow2['description']=str_pad('',18,'x');
				$myrow2['narrative']=str_pad('',18,'x');
				$itemdesc = $myrow2['description'] . ' - ' . $myrow2['narrative'];
			} else {
				$myrow2=DB_fetch_array($LineResult);
				if ($Count[0]==0) {
					$myrow2['qtyexpected']=0;
					$myrow2['qtypicked']=0;
				}
				$DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);
				$DisplayPrevDel = locale_number_format($myrow2['qtyinvoiced'],$myrow2['decimalplaces']);
				$DisplayQtySupplied = locale_number_format($myrow2['quantity'] - $myrow2['qtyinvoiced']-$myrow2['qtyexpected']-$myrow2['qtypicked'],$myrow2['decimalplaces']);
				$itemdesc = $myrow2['description'] . ' - ' . $myrow2['narrative'];
				$sql="INSERT INTO pickinglistdetails
					VALUES(
					'" . $PickingListNo ."',
					'" . $Lines."',
					'" . $myrow2['orderlineno']."',
					'" . $DisplayQtySupplied ."',
					0)";
					$LineResult=DB_query($sql);
			}
			$ListCount ++;

			$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column1->x,$Page_Height - $YPos,$FormDesign->Headings->Column1->Length,$FormDesign->Headings->Column1->FontSize,$myrow2['stkcode'],'left');
			$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column2->x,$Page_Height - $YPos,$FormDesign->Headings->Column2->Length,$FormDesign->Headings->Column2->FontSize,$itemdesc);
			$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column3->x,$Page_Height - $YPos,$FormDesign->Headings->Column3->Length,$FormDesign->Headings->Column3->FontSize,$DisplayQty,'right');
			$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column4->x,$Page_Height - $YPos,$FormDesign->Headings->Column4->Length,$FormDesign->Headings->Column4->FontSize,$DisplayQtySupplied,'right');
			$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column5->x,$Page_Height - $YPos,$FormDesign->Headings->Column5->Length,$FormDesign->Headings->Column5->FontSize,$DisplayPrevDel,'right');

			if ($Page_Height-$YPos-$line_height <= 50){
			/* We reached the end of the page so finish off the page and start a new */
				$PageNumber++;
				include ('includes/PDFPickingListHeader.inc');
			} //end if need a new page headed up
			else{
				/*increment a line down for the next line item */
				$YPos += ($line_height);
			}
			$Lines++;
		} //end while there are line items to print out

	} /*end if there are order details to show on the order*/
} /*end for loop to print the whole lot twice */

if ($ListCount == 0){
	$Title = _('Print Picking List Error');
	include('includes/header.php');
	include('includes/footer.php');
	exit;
} else {
	$PDF->OutputD($_SESSION['DatabaseName'] . '_PickingLists_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();
}
?>