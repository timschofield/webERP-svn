<?php


include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

$InputError=0;

if (isset($_POST['FromDate']) AND !Is_Date($_POST['FromDate'])){
	$msg = _('The date from must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
	unset($_POST['FromDate']);
}
if (isset($_POST['ToDate']) AND !Is_Date($_POST['ToDate'])){
	$msg = _('The date to must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
	unset($_POST['ToDate']);
}

if (!isset($_POST['FromDate']) OR !isset($_POST['ToDate'])){

	$Title = _('Order Status Report');
	include ('includes/header.php');

	if ($InputError==1){
		prnMsg($msg,'error');
	}

	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title . '" alt="" />' . ' ' . _('Order Status Report') . '</p>';

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Enter the date from which orders are to be listed') . ':</td>
			<td><input type="text" required="required" autofocus="autofocus" class="date" name="FromDate" maxlength="10" size="11" value="' . Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,Date('m'),Date('d')-1,Date('y'))) . '" /></td>
		</tr>
		<tr>
			<td>' . _('Enter the date to which orders are to be listed') . ':</td>
			<td><input type="text" required="required" class="date" name="ToDate" maxlength="10" size="11" value="' . Date($_SESSION['DefaultDateFormat']) . '" /></td>
		</tr>
		<tr>
			<td>' . _('Inventory Category') . '</td>
			<td>';

	$sql = "SELECT categorydescription, categoryid FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'";
	$result = DB_query($sql);


	echo '<select required="required" name="CategoryID">
		<option selected="selected" value="All">' . _('Over All Categories') . '</option>';

	while ($myrow=DB_fetch_array($result)){
		echo '<option value="' . $myrow['categoryid'] . '">' . $myrow['categorydescription'] . '</option>';
	}
	echo '</select></td>
		</tr>
		<tr>
			<td>' . _('Inventory Location') . ':</td>
			<td><select name="Location">
				<option selected="selected" value="All">' . _('All Locations') . '</option>';

	$result= DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1");
	while ($myrow=DB_fetch_array($result)){
		echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
	}
	echo '</select></td></tr>';

	echo '<tr>
			<td>' . _('Back Order Only') . ':</td>
			<td><select name="BackOrders">
				<option selected="selected" value="Yes">' . _('Only Show Back Orders') . '</option>
				<option value="No">' . _('Show All Orders') . '</option>
			</select></td>
		</tr>
		</table>
		<br />
		<div class="centre">
			<input type="submit" name="Go" value="' . _('Create PDF') . '" />
		</div>
		</div>
	</form>';

	include('includes/footer.php');
	exit;
} else {
	include('includes/PDFStarter.php');
	$PDF->addInfo('Title',_('Order Status Report'));
	$PDF->addInfo('Subject',_('Orders from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate']);
	$line_height=12;
	$PageNumber = 1;
	$TotalDiffs = 0;
}


if ($_POST['CategoryID']=='All' AND $_POST['Location']=='All'){
	$sql= "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
				 INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			 WHERE salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";

} elseif ($_POST['CategoryID']!='All' AND $_POST['Location']=='All') {
	$sql= "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
				 INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			 WHERE stockmaster.categoryid ='" . $_POST['CategoryID'] . "'
				  AND orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";


} elseif ($_POST['CategoryID']=='All' AND $_POST['Location']!='All') {
	$sql= "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
				 INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			 WHERE salesorders.fromstkloc ='" . $_POST['Location'] . "'
				  AND salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";


} elseif ($_POST['CategoryID']!='All' AND $_POST['location']!='All'){

	$sql= "SELECT salesorders.orderno,
				  salesorders.debtorno,
				  salesorders.branchcode,
				  salesorders.customerref,
				  salesorders.orddate,
				  salesorders.fromstkloc,
				  salesorders.printedpackingslip,
				  salesorders.datepackingslipprinted,
				  salesorderdetails.stkcode,
				  stockmaster.description,
				  stockmaster.units,
				  stockmaster.decimalplaces,
				  salesorderdetails.quantity,
				  salesorderdetails.qtyinvoiced,
				  salesorderdetails.completed,
				  debtorsmaster.name,
				  custbranch.brname,
				  locations.locationname
			 FROM salesorders
				 INNER JOIN salesorderdetails
				 ON salesorders.orderno = salesorderdetails.orderno
				 INNER JOIN stockmaster
				 ON salesorderdetails.stkcode = stockmaster.stockid
				 INNER JOIN debtorsmaster
				 ON salesorders.debtorno=debtorsmaster.debtorno
				 INNER JOIN custbranch
				 ON custbranch.debtorno=salesorders.debtorno
				 AND custbranch.branchcode=salesorders.branchcode
				 INNER JOIN locations
				 ON salesorders.fromstkloc=locations.loccode
				 INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			 WHERE stockmaster.categoryid ='" . $_POST['CategoryID'] . "'
				  AND salesorders.fromstkloc ='" . $_POST['Location'] . "'
				  AND salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
				  AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			 AND salesorders.quotation=0";
}

if ($_POST['BackOrders']=='Yes'){
		$sql .= " AND salesorderdetails.quantity-salesorderdetails.qtyinvoiced >0";
}
//Add salesman role control
if ($_SESSION['SalesmanLogin'] != '') {
		$sql .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$sql .= " ORDER BY salesorders.orderno";

$Result=DB_query($sql,'','',false,false); //dont trap errors here

if (DB_error_no()!=0){
	include('includes/header.php');
	echo '<br />' . _('An error occurred getting the orders details');
	if ($debug==1){
		echo '<br />' . _('The SQL used to get the orders that failed was') . '<br />' . $sql;
	}
	include ('includes/footer.php');
	exit;
} elseif (DB_num_rows($Result)==0){
	$Title=_('Order Status Report - No Data');
  	include('includes/header.php');
	prnMsg(_('There were no orders found in the database within the period from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' '. $_POST['ToDate'] . '. ' . _('Please try again selecting a different date range'),'info');
	include('includes/footer.php');
	exit;
}

include ('includes/PDFOrderStatusPageHeader.inc');

$OrderNo =0; /*initialise */

while ($myrow=DB_fetch_array($Result)){

	$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

	$YPos -= $line_height;
	/*Set up headings */
	/*draw a line */

	if ($myrow['orderno']!=$OrderNo	){
		$LeftOvers = $PDF->addTextWrap($Left_Margin+2,$YPos,40,$FontSize,_('Order'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+40,$YPos,150,$FontSize,_('Customer'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+190,$YPos,110,$FontSize,_('Branch'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+300,$YPos,60,$FontSize,_('Ord Date'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+360,$YPos,60,$FontSize,_('Location'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+420,$YPos,80,$FontSize,_('Status'), 'left');

		$YPos-=$line_height;

		/*draw a line */
		$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
		$PDF->line($XPos, $YPos-$line_height*2,$XPos, $YPos+$line_height*2);
		$PDF->line($Page_Width-$Right_Margin, $YPos-$line_height*2,$Page_Width-$Right_Margin, $YPos+$line_height*2);


		if ($YPos - (2 *$line_height) < $Bottom_Margin){
			/*Then set up a new page */
			$PageNumber++;
			include ('includes/PDFOrderStatusPageHeader.inc');
			$OrderNo=0;
		} /*end of new page header  */
		$YPos -= $line_height;

		$LeftOvers = $PDF->addTextWrap($Left_Margin+2,$YPos,40,$FontSize,$myrow['orderno'], 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+40,$YPos,150,$FontSize,html_entity_decode($myrow['name'],ENT_QUOTES,'UTF-8'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+190,$YPos,110,$FontSize,$myrow['brname'], 'left');

		$LeftOvers = $PDF->addTextWrap($Left_Margin+300,$YPos,60,$FontSize,ConvertSQLDate($myrow['orddate']), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+360,$YPos,80,$FontSize,$myrow['locationname'], 'left');

		if ($myrow['printedpackingslip']==1){
			$PackingSlipPrinted = _('Printed') . ' ' . ConvertSQLDate($myrow['datepackingslipprinted']);
		} else {
			$PackingSlipPrinted =_('Not yet printed');
		}

		$LeftOvers = $PDF->addTextWrap($Left_Margin+420,$YPos,100,$FontSize,$PackingSlipPrinted, 'left');
		$YPos -= $line_height;
		$PDF->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

		$YPos -= ($line_height);

		 /*Its not the first line */
		$OrderNo = $myrow['orderno'];
		$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Code'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+60,$YPos,120,$FontSize,_('Description'), 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+180,$YPos,60,$FontSize,_('Ordered'), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+240,$YPos,60,$FontSize,_('Invoiced'), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+320,$YPos,60,$FontSize,_('Outstanding'), 'center');
		$YPos -= ($line_height);

	}

	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,$myrow['stkcode'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+60,$YPos,120,$FontSize,$myrow['description'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+180,$YPos,60,$FontSize,locale_number_format($myrow['quantity'],$myrow['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+240,$YPos,60,$FontSize,locale_number_format($myrow['qtyinvoiced'],$myrow['decimalplaces']), 'right');

	  if ($myrow['quantity']>$myrow['qtyinvoiced']){
		   $LeftOvers = $PDF->addTextWrap($Left_Margin+320,$YPos,60,$FontSize,locale_number_format($myrow['quantity']-$myrow['qtyinvoiced'],$myrow['decimalplaces']), 'right');
	  } else {
		   $LeftOvers = $PDF->addTextWrap($Left_Margin+320,$YPos,60,$FontSize,_('Complete'), 'left');
	  }

	 $YPos -= ($line_height);
	 if ($YPos - (2 *$line_height) < $Bottom_Margin){
		/*Then set up a new page */
		$PageNumber++;
		include ('includes/PDFOrderStatusPageHeader.inc');
		$OrderNo=0;
	 } /*end of new page header  */
} /* end of while there are delivery differences to print */
$PDF->OutputD($_SESSION['DatabaseName'] . '_OrderStatus_' . date('Y-m-d') . '.pdf');
$PDF->__destruct();
?>
