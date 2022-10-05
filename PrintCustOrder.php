<?php


include('includes/session.php');
include('includes/class.pdf.php');
include('includes/SQL_CommonFunctions.inc');

//Get Out if we have no order number to work with
If (!isset($_GET['TransNo']) OR $_GET['TransNo']==''){
	$Title = _('Select Order To Print');
	include('includes/header.php');
	echo '<div class="centre">
         <br />
         <br />
         <br />';
	prnMsg( _('Select an Order Number to Print before calling this page') , 'error');
	echo '<br />
          <br />
          <br />
          <table class="table_index">
			<tr>
			<td class="menu_group_item">
                 <ul>
					<li><a href="'. $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
					<li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
                 </ul>
				 </td>
				 </tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
	include('includes/footer.php');
	exit;
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the order header details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');

$sql = "SELECT salesorders.customerref,
			salesorders.comments,
			salesorders.orddate,
			salesorders.deliverto,
			salesorders.deladd1,
			salesorders.deladd2,
			salesorders.deladd3,
			salesorders.deladd4,
			salesorders.deladd5,
			salesorders.deladd6,
			salesorders.debtorno,
			salesorders.branchcode,
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
		FROM salesorders INNER JOIN debtorsmaster
			ON salesorders.debtorno=debtorsmaster.debtorno
		INNER JOIN shippers
			ON salesorders.shipvia=shippers.shipper_id
		INNER JOIN locations
			ON salesorders.fromstkloc=locations.loccode
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
		WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";

if ($_SESSION['SalesmanLogin'] != '') {
	$sql .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$result=DB_query($sql, $ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){

	$ListCount = 0;

	$Title = _('Print Packing Slip Error');
        include('includes/header.php');
        echo '<div class="centre"><br /><br /><br />';
	prnMsg( _('Unable to Locate Order Number') . ' : ' . $_GET['TransNo'] . ' ', 'error');
        echo '<br />
			<br />
			<br />
			<table class="table_index">
			<tr>
			<td class="menu_group_item">
                <li><a href="'. $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
                <li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
			</td>
			</tr>
			</table>
			</div>
			<br />
			<br />
			<br />';
        include('includes/footer.php');
        exit();
} elseif (DB_num_rows($result)==1){ /*There is only one order header returned - thats good! */

/* Javier */	$ListCount = 1;

	$myrow = DB_fetch_array($result);
	if ($myrow['printedpackingslip']==1 AND ($_GET['Reprint']!='OK' OR !isset($_GET['Reprint']))){
		$Title = _('Print Packing Slip Error');
	      	include('includes/header.php');
		echo '<p>';
		prnMsg( _('The packing slip for order number') . ' ' . $_GET['TransNo'] . ' ' .
			_('has previously been printed') . '. ' . _('It was printed on'). ' ' . ConvertSQLDate($myrow['datepackingslipprinted']) .
			'<br />' . _('This check is there to ensure that duplicate packing slips are not produced and dispatched more than once to the customer'), 'warn' );
	      echo '<p><a href="' . $RootPath . '/PrintCustOrder.php?TransNo=' . $_GET['TransNo'] . '&Reprint=OK">'
		. _('Do a Re-Print') . ' (' . _('On Pre-Printed Stationery') . ') ' . _('Even Though Previously Printed') . '</a><p>' .
		'<a href="' . $RootPath. '/PrintCustOrder_generic.php?TransNo=' . $_GET['TransNo'] . '&Reprint=OK">' .  _('Do a Re-Print') . ' (' . _('Plain paper') . ' - ' . _('A4') . ' ' . _('landscape') . ') ' . _('Even Though Previously Printed'). '</a>';

		echo '<br /><br /><br />';
		echo  _('Or select another Order Number to Print');
	        echo '<table class="table_index">
					<tr>
						<td class="menu_group_item">
							<li><a href="'. $RootPath . '/SelectSalesOrder.php">' . _('Outstanding Sales Orders') . '</a></li>
							<li><a href="'. $RootPath . '/SelectCompletedOrder.php">' . _('Completed Sales Orders') . '</a></li>
						</td>
					</tr>
				</table>
				</div>
				<br />
				<br />
				<br />';

      		include('includes/footer.php');
		exit;
   	}//packing slip has been printed.
}
/* Then there's an order to print and it has not been printed already (or its been flagged for reprinting)
LETS GO */


/* Now ... Has the order got any line items still outstanding to be invoiced */

$PageNumber = 1;
$ErrMsg = _('There was a problem retrieving the details for Order Number') . ' ' . $_GET['TransNo'] . ' ' . _('from the database');
$sql = "SELECT salesorderdetails.stkcode,
			stockmaster.description,
			salesorderdetails.quantity,
			salesorderdetails.qtyinvoiced,
			salesorderdetails.unitprice,
			stockmaster.decimalplaces,
			stockmaster.units
		FROM salesorderdetails INNER JOIN stockmaster
			ON salesorderdetails.stkcode=stockmaster.stockid
		 WHERE salesorderdetails.orderno='" . $_GET['TransNo'] . "'";
$result=DB_query($sql, $ErrMsg);

if (DB_num_rows($result)>0){
/*Yes there are line items to start the ball rolling with a page header */

	/*Set specifically for the stationery being used -needs to be modified for clients own
	packing slip 2 part stationery is recommended so storeman can note differences on and
	a copy retained */

//Javier
//	$Page_Width=807;
	$Page_Width=792;
	$Page_Height=612;
	$Top_Margin=34;
	$Bottom_Margin=20;
	$Left_Margin=15;
	$Right_Margin=10;

// Javier: now I use the native constructor
// Javier: better to not use references
//	$PageSize = array(0,0,$Page_Width,$Page_Height);
	//	$PDF = & new Cpdf($PageSize);
	Class Cpdf1 extends Cpdf {

		Public function Footer() {
			// Position at 15 mm from bottom
		$this->SetY(-15);
		//Set font and Page number
		$this->SetFont($UserPdfFont,'I',8);
		$this->Cell(0, 10, _('Page').$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
		}
	}

	$PDF = new Cpdf1('L', 'pt', 'LETTER');

	$PDF->addInfo('Creator', 'webERP http://www.weberp.org');
	$PDF->addInfo('Author', 'webERP ' . $Version);
	$PDF->addInfo('Title', _('Customer Packing Slip') );
	$PDF->addInfo('Subject', _('Packing slip for order') . ' ' . $_GET['TransNo']);

/* Javier: I have brought this piece from the pdf class constructor to get it closer to the admin/user,
	I corrected it to match TCPDF, but it still needs check, after which,
	I think it should be moved to each report to provide flexible Document Header and Margins in a per-report basis. */
	$PDF->setAutoPageBreak(0);	// Javier: needs check.
	$PDF->setPrintHeader(false);	// Javier: I added this must be called before Add Page
	$PDF->AddPage();
//	$this->SetLineWidth(1); 	   Javier: It was ok for FPDF but now is too gross with TCPDF. TCPDF defaults to 0'57 pt (0'2 mm) which is ok.
	$PDF->cMargin = 0;		// Javier: needs check.
/* END Brought from class.pdf.php constructor */
	$PDF->setPrintFooter(true);
	$FontSize=12;
	$line_height=16;

	include('includes/PDFOrderPageHeader.inc');

	while ($myrow2=DB_fetch_array($result)){

		$DisplayQty = locale_number_format($myrow2['quantity'],$myrow2['decimalplaces']);
		$DisplayPrevDel = locale_number_format($myrow2['qtyinvoiced'],$myrow2['decimalplaces']);
		$DisplayQtySupplied = locale_number_format($myrow2['quantity'] - $myrow2['qtyinvoiced'],$myrow2['decimalplaces']);

		$LeftOvers = $PDF->addTextWrap(13,$YPos,135,$FontSize,$myrow2['stkcode'],'left');
		$LeftOvers = $PDF->addTextWrap(148,$YPos,239,$FontSize,$myrow2['description'],'left');
		$LeftOvers = $PDF->addTextWrap(387,$YPos,90,$FontSize,$DisplayQty,'right');
		$LeftOvers = $PDF->addTextWrap(475,$YPos,20,$FontSize,$myrow2['units'],'left');
		$LeftOvers = $PDF->addTextWrap(505,$YPos,90,$FontSize,$DisplayQtySupplied,'right');
		$LeftOvers = $PDF->addTextWrap(604,$YPos,90,$FontSize,$DisplayPrevDel,'right');

		if ($YPos-$line_height <= 136){
	   /* We reached the end of the page so finsih off the page and start a newy */

	      $PageNumber++;
	      include ('includes/PDFOrderPageHeader.inc');

	   } //end if need a new page headed up

	   /*increment a line down for the next line item */
	   $YPos -= ($line_height);

      } //end while there are line items to print out

	$PDF->OutputD($_SESSION['DatabaseName'] . '_Customer_Order_' . $_GET['TransNo'] . '_' . Date('Y-m-d') .'.pdf');
	$PDF-> __destruct();

	$sql = "UPDATE salesorders SET printedpackingslip=1,
									datepackingslipprinted='" . Date('Y-m-d') . "'
			WHERE salesorders.orderno='" . $_GET['TransNo'] . "'";
	$result = DB_query($sql);
} else {
	$Title = _('Print Packing Slip Error');
	include('includes/header.php');
	echo '<p>' .  _('There were no outstanding items on the order to deliver. A dispatch note cannot be printed').
		'<br /><a href="' . $RootPath . '/SelectSalesOrder.php">' .  _('Print Another Packing Slip/Order').
		'</a>' . '<br />' .  '<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
	include('includes/footer.php');
	exit;
} /*end if there are order details to show on the order*/
?>
