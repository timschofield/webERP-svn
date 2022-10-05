<?php


include('includes/session.php');
/* webERP manual links before header.php */
$ViewTopic= "Inventory";
$BookMark = "PlanningReport";

include ('includes/SQL_CommonFunctions.inc');

if (isset($_POST['PrintPDF'])) {

	include ('includes/class.pdf.php');

	/* A4_Landscape */

	$Page_Width=842;
	$Page_Height=595;
	$Top_Margin=20;
	$Bottom_Margin=20;
	$Left_Margin=25;
	$Right_Margin=22;

// Javier: now I use the native constructor
//	$PageSize = array(0,0,$Page_Width,$Page_Height);

/* Standard PDF file creation header stuff */

// Javier: better to not use references
//	$PDF = & new Cpdf($PageSize);
	$PDF = new Cpdf('L', 'pt', 'A4');
	$PDF->addInfo('Creator','webERP http://www.weberp.org');
	$PDF->addInfo('Author','webERP ' . $Version);
	$PDF->addInfo('Title',_('Inventory Planning Report') . ' ' . Date($_SESSION['DefaultDateFormat']));
	$PDF->addInfo('Subject',_('Inventory Planning'));

/* Javier: I have brought this piece from the pdf class constructor to get it closer to the admin/user,
	I corrected it to match TCPDF, but it still needs some check, after which,
	I think it should be moved to each report to provide flexible Document Header and Margins in a per-report basis. */
	$PDF->setAutoPageBreak(0);	// Javier: needs check.
	$PDF->setPrintHeader(false);	// Javier: I added this must be called before Add Page
	$PDF->AddPage();
//	$this->SetLineWidth(1); 	   Javier: It was ok for FPDF but now is too gross with TCPDF. TCPDF defaults to 0'57 pt (0'2 mm) which is ok.
	$PDF->cMargin = 0;		// Javier: needs check.
/* END Brought from class.pdf.php constructor */

// Javier:
	$PageNumber = 1;
	$line_height = 12;

      /*Now figure out the inventory data to report for the category range under review
      need QOH, QOO, QDem, Sales Mth -1, Sales Mth -2, Sales Mth -3, Sales Mth -4*/
	if ($_POST['Location']=='All'){
		$SQL = "SELECT stockmaster.categoryid,
						stockmaster.description,
						stockcategory.categorydescription,
						locstock.stockid,
						SUM(locstock.quantity) AS qoh
					FROM locstock
					INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
						stockmaster,
						stockcategory
					WHERE locstock.stockid=stockmaster.stockid
					AND stockmaster.discontinued = 0
					AND stockmaster.categoryid=stockcategory.categoryid
					AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
					AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
					GROUP BY stockmaster.categoryid,
						stockmaster.description,
						stockcategory.categorydescription,
						locstock.stockid,
						stockmaster.stockid
					ORDER BY stockmaster.categoryid,
						stockmaster.stockid";
	} else {
		$SQL = "SELECT stockmaster.categoryid,
					locstock.stockid,
					stockmaster.description,
					stockcategory.categorydescription,
					locstock.quantity  AS qoh
				FROM locstock
				INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
					stockmaster,
					stockcategory
				WHERE locstock.stockid=stockmaster.stockid
				AND stockmaster.discontinued = 0
				AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
				AND stockmaster.categoryid=stockcategory.categoryid
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
				AND locstock.loccode = '" . $_POST['Location'] . "'
				ORDER BY stockmaster.categoryid,
					stockmaster.stockid";

	}
	$InventoryResult = DB_query($SQL, '', '', false, false);

	if (DB_error_no() !=0) {
	  $Title = _('Inventory Planning') . ' - ' . _('Problem Report') . '....';
	  include('includes/header.php');
	   prnMsg(_('The inventory quantities could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
	   echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($debug==1){
	      echo '<br />' . $SQL;
	   }
	   include('includes/footer.php');
	   exit;
	}

	$Period_0_Name = GetMonthText(date('m', mktime(0,0,0,Date('m'),Date('d'),Date('Y'))));
	$Period_1_Name = GetMonthText(date('m', mktime(0,0,0,Date('m')-1,Date('d'),Date('Y'))));
	$Period_2_Name = GetMonthText(date('m', mktime(0,0,0,Date('m')-2,Date('d'),Date('Y'))));
	$Period_3_Name = GetMonthText(date('m', mktime(0,0,0,Date('m')-3,Date('d'),Date('Y'))));
	$Period_4_Name = GetMonthText(date('m', mktime(0,0,0,Date('m')-4,Date('d'),Date('Y'))));
	$Period_5_Name = GetMonthText(date('m', mktime(0,0,0,Date('m')-5,Date('d'),Date('Y'))));

	include ('includes/PDFInventoryPlanPageHeader.inc');

	$Category = '';

	$CurrentPeriod = GetPeriod(Date($_SESSION['DefaultDateFormat']));
	$Period_1 = $CurrentPeriod -1;
	$Period_2 = $CurrentPeriod -2;
	$Period_3 = $CurrentPeriod -3;
	$Period_4 = $CurrentPeriod -4;
	$Period_5 = $CurrentPeriod -5;

	while ($InventoryPlan = DB_fetch_array($InventoryResult)){

		if ($Category!=$InventoryPlan['categoryid']){
			$FontSize=10;
			if ($Category!=''){ /*Then it's NOT the first time round */
				/*draw a line under the CATEGORY TOTAL*/
				$YPos -=$line_height;
		   		$PDF->line($Left_Margin, $YPos,$Page_Width-$Right_Margin, $YPos);
				$YPos -=(2*$line_height);
			}

			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 260-$Left_Margin,$FontSize,$InventoryPlan['categoryid'] . ' - ' . $InventoryPlan['categorydescription'],'left');
			$Category = $InventoryPlan['categoryid'];
			$FontSize=8;
		}

		$YPos -=$line_height;


		if ($_POST['Location']=='All'){
   		   $SQL = "SELECT SUM(CASE WHEN prd='" . $CurrentPeriod . "' THEN -qty ELSE 0 END) AS prd0,
				   		SUM(CASE WHEN prd='" . $Period_1 . "' THEN -qty ELSE 0 END) AS prd1,
						SUM(CASE WHEN prd='" . $Period_2 . "' THEN -qty ELSE 0 END) AS prd2,
						SUM(CASE WHEN prd='" . $Period_3 . "' THEN -qty ELSE 0 END) AS prd3,
						SUM(CASE WHEN prd='" . $Period_4 . "' THEN -qty ELSE 0 END) AS prd4,
						SUM(CASE WHEN prd='" . $Period_5 . "' THEN -qty ELSE 0 END) AS prd5
					FROM stockmoves
					INNER JOIN locationusers ON locationusers.loccode=stockmoves.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
					WHERE stockid='" . $InventoryPlan['stockid'] . "'
					AND (type=10 OR type=11)
					AND stockmoves.hidemovt=0";
		} else {
  		   $SQL = "SELECT SUM(CASE WHEN prd='" . $CurrentPeriod . "' THEN -qty ELSE 0 END) AS prd0,
				   		SUM(CASE WHEN prd='" . $Period_1 . "' THEN -qty ELSE 0 END) AS prd1,
						SUM(CASE WHEN prd='" . $Period_2 . "' THEN -qty ELSE 0 END) AS prd2,
						SUM(CASE WHEN prd='" . $Period_3 . "' THEN -qty ELSE 0 END) AS prd3,
						SUM(CASE WHEN prd='" . $Period_4 . "' THEN -qty ELSE 0 END) AS prd4,
						SUM(CASE WHEN prd='" . $Period_5 . "' THEN -qty ELSE 0 END) AS prd5
					FROM stockmoves
					INNER JOIN locationusers ON locationusers.loccode=stockmoves.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
					WHERE stockid='" . $InventoryPlan['stockid'] . "'
					AND stockmoves.loccode ='" . $_POST['Location'] . "'
					AND (stockmoves.type=10 OR stockmoves.type=11)
					AND stockmoves.hidemovt=0";
		}

		$SalesResult = DB_query($SQL,'','', false, false);

		if (DB_error_no() !=0) {
	 		 $Title = _('Inventory Planning') . ' - ' . _('Problem Report') . '....';
	  		include('includes/header.php');
	   		prnMsg( _('The sales quantities could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
	   		echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   		if ($debug==1){
	      		echo '<br />' .$SQL;
	   		}

	   		include('includes/footer.php');
	   		exit;
		}

		$SalesRow = DB_fetch_array($SalesResult);

		if ($_POST['Location']=='All'){
			$SQL = "SELECT SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qtydemand
				FROM salesorderdetails INNER JOIN salesorders
				ON salesorderdetails.orderno=salesorders.orderno
				INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE salesorderdetails.stkcode = '" . $InventoryPlan['stockid'] . "'
				AND salesorderdetails.completed = 0
				AND salesorders.quotation=0";
		} else {
			$SQL = "SELECT SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qtydemand
				FROM salesorderdetails INNER JOIN salesorders
				ON salesorderdetails.orderno=salesorders.orderno
				INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE salesorders.fromstkloc ='" . $_POST['Location'] . "'
				AND salesorderdetails.stkcode = '" . $InventoryPlan['stockid'] . "'
				AND salesorderdetails.completed = 0
				AND salesorders.quotation=0";
		}

		$DemandResult = DB_query($SQL, '', '', false , false);
		$ListCount = DB_num_rows($DemandResult);

		if (DB_error_no() !=0) {
	 		$Title = _('Inventory Planning') . ' - ' . _('Problem Report') . '....';
	  		include('includes/header.php');
	   		prnMsg( _('The sales order demand quantities could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
	   		echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   		if ($debug==1){
	      			echo '<br />' . $SQL;
	   		}
	   		include('includes/footer.php');
	   		exit;
		}

// Also need to add in the demand as a component of an assembly items if this items has any assembly parents.

		if ($_POST['Location']=='All'){
			$SQL = "SELECT SUM((salesorderdetails.quantity-salesorderdetails.qtyinvoiced)*bom.quantity) AS dem
				FROM salesorderdetails INNER JOIN bom
					ON salesorderdetails.stkcode=bom.parent
					INNER JOIN	stockmaster
					ON stockmaster.stockid=bom.parent
					INNER JOIN salesorders
					ON salesorders.orderno = salesorderdetails.orderno
					INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE salesorderdetails.quantity-salesorderdetails.qtyinvoiced > 0
				AND bom.component='" . $InventoryPlan['stockid'] . "'
				AND stockmaster.mbflag='A'
				AND salesorderdetails.completed=0
				AND salesorders.quotation=0";
		} else {
			$SQL = "SELECT SUM((salesorderdetails.quantity-salesorderdetails.qtyinvoiced)*bom.quantity) AS dem
				FROM salesorderdetails INNER JOIN bom
					ON salesorderdetails.stkcode=bom.parent
					INNER JOIN	stockmaster
					ON stockmaster.stockid=bom.parent
					INNER JOIN salesorders
					ON salesorders.orderno = salesorderdetails.orderno
					INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE salesorderdetails.quantity-salesorderdetails.qtyinvoiced > 0
				AND salesorderdetails.quantity-salesorderdetails.qtyinvoiced > 0
				AND bom.component='" . $InventoryPlan['stockid'] . "'
				AND stockmaster.stockid=bom.parent
				AND salesorders.fromstkloc ='" . $_POST['Location'] . "'
				AND stockmaster.mbflag='A'
				AND salesorderdetails.completed=0
				AND salesorders.quotation=0";
		}

		$BOMDemandResult = DB_query($SQL,'','',false,false);

		if (DB_error_no() !=0) {
	 		$Title = _('Inventory Planning') . ' - ' . _('Problem Report') . '....';
	  		include('includes/header.php');
	   		prnMsg( _('The sales order demand quantities from parent assemblies could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
	   		echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   		if ($debug==1){
	      			echo '<br />' . $SQL;
	   		}
	   		include('includes/footer.php');
	   		exit;
		}

		// Get the QOO due to Purchase orders for all locations. Function defined in SQL_CommonFunctions.inc
		// Get the QOO dues to Work Orders for all locations. Function defined in SQL_CommonFunctions.inc
		if ($_POST['Location']=='All'){
			$QOO = GetQuantityOnOrderDueToPurchaseOrders($InventoryPlan['stockid'], '');
			$QOO += GetQuantityOnOrderDueToWorkOrders($InventoryPlan['stockid'], '');
		} else {
			$QOO = GetQuantityOnOrderDueToPurchaseOrders($InventoryPlan['stockid'], $_POST['Location']);
			$QOO += GetQuantityOnOrderDueToWorkOrders($InventoryPlan['stockid'], $_POST['Location']);
		}

		$DemandRow = DB_fetch_array($DemandResult);
		$BOMDemandRow = DB_fetch_array($BOMDemandResult);
		$TotalDemand = $DemandRow['qtydemand'] + $BOMDemandRow['dem'];

		$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 110, $FontSize, $InventoryPlan['stockid'], 'left');
		$LeftOvers = $PDF->addTextWrap(130, $YPos, 120,6,$InventoryPlan['description'],'left');
		$LeftOvers = $PDF->addTextWrap(251, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd5'],0),'right');
		$LeftOvers = $PDF->addTextWrap(292, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd4'],0),'right');
		$LeftOvers = $PDF->addTextWrap(333, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd3'],0),'right');
		$LeftOvers = $PDF->addTextWrap(374, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd2'],0),'right');
		$LeftOvers = $PDF->addTextWrap(415, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd1'],0),'right');
		$LeftOvers = $PDF->addTextWrap(456, $YPos, 40,$FontSize,locale_number_format($SalesRow['prd0'],0),'right');

		if ($_POST['NumberMonthsHolding']>10){
			$NumberMonths=$_POST['NumberMonthsHolding']-10;
			$MaxMthSales = ($SalesRow['prd1']+$SalesRow['prd2']+$SalesRow['prd3']+$SalesRow['prd4']+$SalesRow['prd5'])/5;
		}
		else{
			$NumberMonths=$_POST['NumberMonthsHolding'];
			$MaxMthSales = max($SalesRow['prd1'], $SalesRow['prd2'], $SalesRow['prd3'], $SalesRow['prd4'], $SalesRow['prd5']);
		}



		$IdealStockHolding = ceil($MaxMthSales * $NumberMonths);
		$LeftOvers = $PDF->addTextWrap(497, $YPos, 40,$FontSize,locale_number_format($IdealStockHolding,0),'right');
		$LeftOvers = $PDF->addTextWrap(597, $YPos, 40,$FontSize,locale_number_format($InventoryPlan['qoh'],0),'right');
		$LeftOvers = $PDF->addTextWrap(638, $YPos, 40,$FontSize,locale_number_format($TotalDemand,0),'right');

		$LeftOvers = $PDF->addTextWrap(679, $YPos, 40,$FontSize,locale_number_format($QOO,0),'right');

		$SuggestedTopUpOrder = $IdealStockHolding - $InventoryPlan['qoh'] + $TotalDemand - $QOO;
		if ($SuggestedTopUpOrder <=0){
			$LeftOvers = $PDF->addTextWrap(720, $YPos, 40,$FontSize,'   ','right');

		} else {

			$LeftOvers = $PDF->addTextWrap(720, $YPos, 40,$FontSize,locale_number_format($SuggestedTopUpOrder,0),'right');
		}



		if ($YPos < $Bottom_Margin + $line_height){
		   $PageNumber++;
		   include('includes/PDFInventoryPlanPageHeader.inc');
		}

	} /*end inventory valn while loop */

	$YPos -= (2*$line_height);

	$PDF->line($Left_Margin, $YPos+$line_height,$Page_Width-$Right_Margin, $YPos+$line_height);

	if ($ListCount == 0){
		$Title = _('Print Inventory Planning Report Empty');
		include('includes/header.php');
		prnMsg( _('There were no items in the range and location specified'), 'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	} else {
		$PDF->OutputD($_SESSION['DatabaseName'] . '_Inventory_Planning_' . Date('Y-m-d') . '.pdf');
		$PDF-> __destruct();
	}

} elseif (isset($_POST['ExportToCSV'])){ //send the data to a CSV

	function stripcomma($str) { //because we're using comma as a delimiter
		return str_replace(',', '', str_replace(';','', $str));
	}
 /*Now figure out the inventory data to report for the category range under review
   need QOH, QOO, QDem, Sales Mth -1, Sales Mth -2, Sales Mth -3, Sales Mth -4*/
	if ($_POST['Location']=='All'){
		$SQL = "SELECT stockmaster.categoryid,
						stockmaster.description,
						stockcategory.categorydescription,
						locstock.stockid,
						SUM(locstock.quantity) AS qoh
					FROM locstock
					INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
						stockmaster,
						stockcategory
					WHERE locstock.stockid=stockmaster.stockid
					AND stockmaster.discontinued = 0
					AND stockmaster.categoryid=stockcategory.categoryid
					AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
					AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
					GROUP BY stockmaster.categoryid,
						stockmaster.description,
						stockcategory.categorydescription,
						locstock.stockid,
						stockmaster.stockid
					ORDER BY stockmaster.categoryid,
						stockmaster.stockid";
	} else {
		$SQL = "SELECT stockmaster.categoryid,
					locstock.stockid,
					stockmaster.description,
					stockcategory.categorydescription,
					locstock.quantity  AS qoh
				FROM locstock
				INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
					stockmaster,
					stockcategory
				WHERE locstock.stockid=stockmaster.stockid
				AND stockmaster.discontinued = 0
				AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
				AND stockmaster.categoryid=stockcategory.categoryid
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
				AND locstock.loccode = '" . $_POST['Location'] . "'
				ORDER BY stockmaster.categoryid,
					stockmaster.stockid";
	}
	$InventoryResult = DB_query($SQL);
	$CurrentPeriod = GetPeriod(Date($_SESSION['DefaultDateFormat']));
	$Periods = array();
	for ($i=0;$i<24;$i++) {
		$Periods[$i]['Period'] = $CurrentPeriod - $i;
		$Periods[$i]['Month'] = GetMonthText(Date('m',mktime(0,0,0,Date('m') - $i,Date('d'),Date('Y')))) .  ' ' . Date('Y',mktime(0,0,0,Date('m') - $i,Date('d'),Date('Y')));
	}
	$SQLStarter = "SELECT stockmoves.stockid,";
	for ($i=0;$i<24;$i++) {
		$SQLStarter .= "SUM(CASE WHEN prd='" . $Periods[$i]['Period'] . "' THEN -qty ELSE 0 END) AS prd" . $i . ' ';
		if ($i<23) {
			$SQLStarter .= ', ';
		}
	}
	$SQLStarter .= "FROM stockmoves
					INNER JOIN locationusers ON locationusers.loccode=stockmoves.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
					WHERE (type=10 OR type=11)
					AND stockmoves.hidemovt=0";
	if ($_POST['Location']!='All'){
		$SQLStarter .= " AND stockmoves.loccode ='" . $_POST['Location'] . "'";
	}


	$CSVListing = _('Category ID') .','. _('Category Description') .','. _('Stock ID') .','. _('Description') .',' . _('QOH') . ',';
	for ($i=0;$i<24;$i++) {
		$CSVListing .= $Periods[$i]['Month'] . ',';
	}
	$CSVListing .= "\r\n";

	$Category ='';

	while ($InventoryPlan = DB_fetch_array($InventoryResult)){

		$SQL = $SQLStarter . " AND stockid='" . $InventoryPlan['stockid'] . "' GROUP BY stockmoves.stockid";
		$SalesResult = DB_query($SQL,_('The stock usage of this item could not be retrieved because'));

		if (DB_num_rows($SalesResult)==0) {
			$CSVListing .= stripcomma($InventoryPlan['categoryid']) . ',' . stripcomma($InventoryPlan['categorydescription']) . ',' .stripcomma($InventoryPlan['stockid']) . ',' . stripcomma($InventoryPlan['description']) . ',' . stripcomma($InventoryPlan['qoh']) . "\r\n";
		} else {
			$SalesRow = DB_fetch_array($SalesResult);
			$CSVListing .= stripcomma($InventoryPlan['categoryid']) . ',' . stripcomma($InventoryPlan['categorydescription']) . ',' .stripcomma($InventoryPlan['stockid']) . ',' . stripcomma($InventoryPlan['description']) . ',' . stripcomma($InventoryPlan['qoh']);
			for ($i=0;$i<24;$i++) {
				$CSVListing .= ',' . $SalesRow['prd' .$i];
			}
			$CSVListing .= "\r\n";
		}

	}
	header('Content-Encoding: UTF-8');
    header('Content-type: text/csv; charset=UTF-8');
    header("Content-disposition: attachment; filename=InventoryPlanning_" .  Date('Y-m-d:h:m:s')  .'.csv');
    header("Pragma: public");
    header("Expires: 0");
    echo "\xEF\xBB\xBF"; // UTF-8
	echo $CSVListing;
	exit;

} else { /*The option to print PDF was not hit */

	$Title=_('Inventory Planning Reporting');
	include('includes/header.php');

	echo '<p class="page_title_text">
			<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';


	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">
			<tr>
				<td>' . _('Select Inventory Categories') . ':</td>
				<td><select autofocus="autofocus" required="required" minlength="1" size="12" name="Categories[]" multiple="multiple">';
	$SQL = 'SELECT categoryid, categorydescription
			FROM stockcategory
			ORDER BY categorydescription';
	$CatResult = DB_query($SQL);
	while ($MyRow = DB_fetch_array($CatResult)) {
		if (isset($_POST['Categories']) AND in_array($MyRow['categoryid'], $_POST['Categories'])) {
			echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] .'</option>';
		} else {
			echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
	}
	echo '</select>
			</td>
		</tr>';

	echo '<tr>
			<td>' . _('For Inventory in Location') . ':</td>
			<td><select name="Location">';

	$sql = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$LocnResult=DB_query($sql);

	echo '<option value="All">' . _('All Locations') . '</option>';

	while ($myrow=DB_fetch_array($LocnResult)){
		echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
	}
	echo '</select>
			</td>
		</tr>';

	echo '<tr>
			<td>' . _('Stock Planning') . ':</td>
			<td><select name="NumberMonthsHolding">
				<option selected="selected" value="1">' . _('One Month MAX')  . '</option>
				<option value="1.5">' . _('One Month and a half MAX')  . '</option>
				<option value="2">' . _('Two Months MAX')  . '</option>
				<option value="2.5">' . _('Two Month and a half MAX')  . '</option>
				<option value="3">' . _('Three Months MAX')  . '</option>
				<option value="4">' . _('Four Months MAX')  . '</option>
				<option value="11">' . _('One Month AVG')  . '</option>
				<option value="11.5">' . _('One Month and a half AVG')  . '</option>
				<option value="12">' . _('Two Months AVG')  . '</option>
				<option value="12.5">' . _('Two Month and a half AVG')  . '</option>
				<option value="13">' . _('Three Months AVG')  . '</option>
				<option value="14">' . _('Four Months AVG')  . '</option>
				</select>
			</td>
	</tr>
	</table>
	<br />
	<div class="centre">
		<input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
		<input type="submit" name="ExportToCSV" value="' . _('Export 24 months to CSV') . '" />
	</div>
	</div>
	</form>';

	include('includes/footer.php');

} /*end of else not PrintPDF */

?>