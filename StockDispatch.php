<?php

// StockDispatch.php - Report of parts with overstock at one location that can be transferred
// to another location to cover shortage based on reorder level. Creates loctransfer records
// that can be processed using Bulk Inventory Transfer - Receive.

include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/GetPrice.inc');
if (isset($_POST['PrintPDF'])) {

	include('includes/PDFStarter.php');
	if (!is_numeric(filter_number_format($_POST['Percent']))) {
		$_POST['Percent'] = 0;
	}

	$PDF->addInfo('Title',_('Stock Dispatch Report'));
	$PDF->addInfo('Subject',_('Parts to dispatch to another location to cover reorder level'));
	$FontSize=9;
	$PageNumber=1;
	$line_height=19;
	$Xpos = $Left_Margin+1;

	//template
	if($_POST['template']=='simple') {
		$template='simple';
	} elseif($_POST['template']=='standard') {
		$template='standard';
	} elseif($_POST['template']=='full') {
		$template='full';
	} else {
		$template='fullprices';
	}
	// Create Transfer Number
	if(!isset($Trf_ID) and $_POST['ReportType'] == 'Batch') {
		$Trf_ID = GetNextTransNo(16);
	}

	// from location
	$ErrMsg = _('Could not retrieve location name from the database');
	$sqlfrom="SELECT locationname FROM `locations` WHERE loccode='" . $_POST['FromLocation'] . "'";
	$result = DB_query($sqlfrom,$ErrMsg);
	$Row = DB_fetch_row($result);
	$FromLocation=$Row['0'];

	// to location
	$sqlto="SELECT locationname,
					cashsalecustomer,
					cashsalebranch
			FROM `locations`
			WHERE loccode='" . $_POST['ToLocation'] . "'";
	$resultto = DB_query($sqlto,$ErrMsg);
	$RowTo = DB_fetch_row($resultto);
	$ToLocation=$RowTo['0'];
	$ToCustomer=$RowTo['1'];
	$ToBranch=$RowTo['2'];

	if($template=='fullprices'){
		$SqlPrices="SELECT debtorsmaster.currcode,
						debtorsmaster.salestype,
						currencies.decimalplaces
				FROM debtorsmaster, currencies
				WHERE debtorsmaster.currcode = currencies.currabrev
					AND debtorsmaster.debtorno ='" . $ToCustomer . "'";
		$ResultPrices = DB_query($SqlPrices,$ErrMsg);
		$RowPrices = DB_fetch_row($ResultPrices);
		$ToCurrency=$RowPrices['0'];
		$ToPriceList=$RowPrices['1'];
		$ToDecimalPlaces=$RowPrices['2'];
	}

	// Creates WHERE clause for stock categories. StockCat is defined as an array so can choose
	// more than one category
	if ($_POST['StockCat'] != 'All') {
		$CategorySQL="SELECT categorydescription FROM stockcategory WHERE categoryid='".$_POST['StockCat']."'";
		$CategoryResult=DB_query($CategorySQL);
		$CategoryRow=DB_fetch_array($CategoryResult);
		$CategoryDescription=$CategoryRow['categorydescription'];
		$WhereCategory = " AND stockmaster.categoryid ='" . $_POST['StockCat'] . "' ";
	} else {
		$CategoryDescription=_('All');
		$WhereCategory = " ";
	}

	// If Strategy is "Items needed at TO location with overstock at FROM" we need to control the "needed at TO" part
	// The "overstock at FROM" part is controlled in any case with AND (fromlocstock.quantity - fromlocstock.reorderlevel) > 0
	if ($_POST['Strategy'] == 'All') {
		$WhereCategory = $WhereCategory . " AND locstock.reorderlevel > locstock.quantity ";
	}

	$sql = "SELECT locstock.stockid,
				stockmaster.description,
				locstock.loccode,
				locstock.quantity,
				locstock.reorderlevel,
				stockmaster.decimalplaces,
				stockmaster.serialised,
				stockmaster.controlled,
				stockmaster.discountcategory,
				ROUND((locstock.reorderlevel - locstock.quantity) *
				   (1 + (" . filter_number_format($_POST['Percent']) . "/100)))
				as neededqty,
			   (fromlocstock.quantity - fromlocstock.reorderlevel)  as available,
			   fromlocstock.reorderlevel as fromreorderlevel,
			   fromlocstock.quantity as fromquantity
			FROM stockmaster
			LEFT JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid,
			locstock
			LEFT JOIN locstock AS fromlocstock ON
			  locstock.stockid = fromlocstock.stockid
			  AND fromlocstock.loccode = '" . $_POST['FromLocation'] . "'
			WHERE locstock.stockid=stockmaster.stockid
			AND locstock.loccode ='" . $_POST['ToLocation'] . "'
			AND (fromlocstock.quantity - fromlocstock.reorderlevel) > 0
			AND stockcategory.stocktype<>'A'
			AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
			$WhereCategory . " ORDER BY locstock.loccode,locstock.stockid";

	$result = DB_query($sql,'','',false,true);

	if (DB_error_no() !=0) {
		$Title = _('Stock Dispatch - Problem Report');
		include('includes/header.php');
		prnMsg( _('The Stock Dispatch report could not be retrieved by the SQL because') . ' '  . DB_error_msg(),'error');
		echo '<br />
				<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
			echo '<br />' . $sql;
		}
		include('includes/footer.php');
		exit;
	}
	if (DB_num_rows($result) ==0) {
		$Title = _('Stock Dispatch - Problem Report');
		include('includes/header.php');
		echo '<br />';
		prnMsg( _('The stock dispatch did not have any items to list'),'warn');
		echo '<br />
				<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	}

	PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
				$Page_Width,$Right_Margin,$Trf_ID,$FromLocation,$ToLocation,$template,$CategoryDescription);

	$FontSize=8;
	$Now = Date('Y-m-d H-i-s');
	while ($myrow = DB_fetch_array($result)){
		// Check if there is any stock in transit already sent from FROM LOCATION
		$InTransitQuantityAtFrom = 0;
		if ($_SESSION['ProhibitNegativeStock']==1){
			$InTransitSQL="SELECT SUM(shipqty-recqty) as intransit
							FROM loctransfers
							WHERE stockid='" . $myrow['stockid'] . "'
								AND shiploc='".$_POST['FromLocation']."'
								AND shipqty>recqty";
			$InTransitResult=DB_query($InTransitSQL);
			$InTransitRow=DB_fetch_array($InTransitResult);
			$InTransitQuantityAtFrom=$InTransitRow['intransit'];
		}
		// The real available stock to ship is the (qty - reorder level - in transit).
		$AvailableShipQtyAtFrom = $myrow['available'] - $InTransitQuantityAtFrom;

		// Check if TO location is already waiting to receive some stock of this item
		$InTransitQuantityAtTo=0;
		$InTransitSQL="SELECT SUM(shipqty-recqty) as intransit
						FROM loctransfers
						WHERE stockid='" . $myrow['stockid'] . "'
							AND recloc='".$_POST['ToLocation']."'
							AND shipqty>recqty";
		$InTransitResult=DB_query($InTransitSQL);
		$InTransitRow=DB_fetch_array($InTransitResult);
		$InTransitQuantityAtTo=$InTransitRow['intransit'];

		// The real needed stock is reorder level - qty - in transit).
		$NeededQtyAtTo = $myrow['neededqty'] - $InTransitQuantityAtTo;

		// Decide how many are sent (depends on the strategy)
		if ($_POST['Strategy'] == 'OverFrom') {
			// send items with overstock at FROM, no matter qty needed at TO.
			$ShipQty = $AvailableShipQtyAtFrom;
		}else{
			// Send all items with overstock at FROM needed at TO
			$ShipQty = 0;
			if ($AvailableShipQtyAtFrom > 0) {
				if ($AvailableShipQtyAtFrom >= $NeededQtyAtTo) {
					// We can ship all the needed qty at TO location
					$ShipQty = $NeededQtyAtTo;
				}else{
					// We can't ship all the needed qty at TO location, but at least can ship some
					$ShipQty = $AvailableShipQtyAtFrom;
				}
			}
		}

		if ($ShipQty>0) {
			$YPos -=(2 * $line_height);
			// Parameters for addTextWrap are defined in /includes/class.pdf.php
			// 1) X position 2) Y position 3) Width
			// 4) Height 5) Text 6) Alignment 7) Border 8) Fill - True to use SetFillColor
			// and False to set to transparent
			$fill = False;

			if($template=='simple'){
				//for simple template
				$PDF->addTextWrap(50,$YPos,70,$FontSize,$myrow['stockid'],'',0,$fill);
				$PDF->addTextWrap(135,$YPos,250,$FontSize,$myrow['description'],'',0,$fill);
				$PDF->addTextWrap(380,$YPos,45,$FontSize,locale_number_format($myrow['fromquantity'], $myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(425,$YPos,40,$FontSize,locale_number_format($myrow['quantity'], $myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(465,$YPos,40,11,locale_number_format($ShipQty, $myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(510,$YPos,40,$FontSize,'_________','right',0,$fill);
			} elseif ($template=='standard') {
				//for standard template
				$PDF->addTextWrap(50,$YPos,70,$FontSize,$myrow['stockid'],'',0,$fill);
				$PDF->addTextWrap(135,$YPos,200,$FontSize,$myrow['description'],'',0,$fill);
				$PDF->addTextWrap(320,$YPos,40,$FontSize,locale_number_format($myrow['fromquantity'] - $InTransitQuantityAtFrom,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(390,$YPos,40,$FontSize,locale_number_format($myrow['quantity'] + $InTransitQuantityAtTo,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(460,$YPos,40,11,locale_number_format($ShipQty,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(510,$YPos,40,$FontSize,'_________','right',0,$fill);
			} else {
				//for full template
				$PDF->addTextWrap(50,$YPos,70,$FontSize,$myrow['stockid'],'',0,$fill);
				$SupportedImgExt = array('png','jpg','jpeg');
				$imagefile = reset((glob($_SESSION['part_pics_dir'] . '/' . $myrow['stockid'] . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE)));
				if (file_exists ($imagefile) ) {
					$PDF->Image($imagefile,135,$Page_Height-$Top_Margin-$YPos+10,35,35);
				}/*end checked file exist*/
				$PDF->addTextWrap(180,$YPos,200,$FontSize,$myrow['description'],'',0,$fill);
				$PDF->addTextWrap(355,$YPos,40,$FontSize,locale_number_format($myrow['fromquantity'] - $InTransitQuantityAtFrom,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(405,$YPos,40,$FontSize,locale_number_format($myrow['quantity'] + $InTransitQuantityAtTo,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(450,$YPos,40,11,locale_number_format($ShipQty,$myrow['decimalplaces']),'right',0,$fill);
				$PDF->addTextWrap(510,$YPos,40,$FontSize,'_________','right',0,$fill);
				if($template=='fullprices'){
					// looking for price info
					$DefaultPrice = GetPrice($myrow['stockid'],$ToCustomer, $ToBranch, $ShipQty, false);
					if ($myrow['discountcategory'] != "")
					{
						$DiscountLine = ' -> ' . _('Discount Category') . ':' . $myrow['discountcategory'];
					}else{
						$DiscountLine = '';
					}
					if ($DefaultPrice != 0){
						$PriceLine = $ToPriceList . ":" . locale_number_format($DefaultPrice,$ToDecimalPlaces) . " " . $ToCurrency . $DiscountLine;
						$PDF->addTextWrap(180,$YPos - 0.5 * $line_height,200,$FontSize,$PriceLine,'',0,$fill);
					}
				}
			}

			if ($YPos < $Bottom_Margin + $line_height + 200){
				PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,$Right_Margin,$Trf_ID,$FromLocation,$ToLocation,$template,$CategoryDescription);
			}

			// Create loctransfers records for each record
			$sql2 = "INSERT INTO loctransfers (reference,
												stockid,
												shipqty,
												shipdate,
												shiploc,
												recloc)
											VALUES ('" . $Trf_ID . "',
												'" . $myrow['stockid'] . "',
												'" . $ShipQty . "',
												'" . $Now . "',
												'" . $_POST['FromLocation']  ."',
												'" . $_POST['ToLocation'] . "')";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('Unable to enter Location Transfer record for'). ' '.$myrow['stockid'];
			if ($_POST['ReportType'] == 'Batch') {
				$resultLocShip = DB_query($sql2, $ErrMsg);
			}
		}
	} /*end while loop  */
	//add prepared by
	$PDF->addTextWrap(50,$YPos-50,100,9,_('Prepared By :'), 'left');
	$PDF->addTextWrap(50,$YPos-70,100,$FontSize,_('Name'), 'left');
	$PDF->addTextWrap(90,$YPos-70,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(50,$YPos-90,100,$FontSize,_('Date'), 'left');
	$PDF->addTextWrap(90,$YPos-90,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(50,$YPos-110,100,$FontSize,_('Hour'), 'left');
	$PDF->addTextWrap(90,$YPos-110,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(50,$YPos-150,100,$FontSize,_('Signature'), 'left');
	$PDF->addTextWrap(90,$YPos-150,200,$FontSize,':__________________','left',0,$fill);

	//add shipped by
	$PDF->addTextWrap(240,$YPos-50,100,9,_('Shipped By :'), 'left');
	$PDF->addTextWrap(240,$YPos-70,100,$FontSize,_('Name'), 'left');
	$PDF->addTextWrap(280,$YPos-70,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(240,$YPos-90,100,$FontSize,_('Date'), 'left');
	$PDF->addTextWrap(280,$YPos-90,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(240,$YPos-110,100,$FontSize,_('Hour'), 'left');
	$PDF->addTextWrap(280,$YPos-110,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(240,$YPos-150,100,$FontSize,_('Signature'), 'left');
	$PDF->addTextWrap(280,$YPos-150,200,$FontSize,':__________________','left',0,$fill);

	//add received by
	$PDF->addTextWrap(440,$YPos-50,100,9,_('Received By :'), 'left');
	$PDF->addTextWrap(440,$YPos-70,100,$FontSize,_('Name'), 'left');
	$PDF->addTextWrap(480,$YPos-70,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(440,$YPos-90,100,$FontSize,_('Date'), 'left');
	$PDF->addTextWrap(480,$YPos-90,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(440,$YPos-110,100,$FontSize,_('Hour'), 'left');
	$PDF->addTextWrap(480,$YPos-110,200,$FontSize,':__________________','left',0,$fill);
	$PDF->addTextWrap(440,$YPos-150,100,$FontSize,_('Signature'), 'left');
	$PDF->addTextWrap(480,$YPos-150,200,$FontSize,':__________________','left',0,$fill);

	if ($YPos < $Bottom_Margin + $line_height){
		   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
					   $Right_Margin,$Trf_ID,$FromLocation,$ToLocation,$template);
	}
/*Print out the grand totals */

	$PDF->OutputD($_SESSION['DatabaseName'] . '_Stock_Transfer_Dispatch_' . Date('Y-m-d') . '.pdf');
	$PDF->__destruct();

} else { /*The option to print PDF was not hit so display form */

	$Title=_('Stock Dispatch Report');
	include('includes/header.php');
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Inventory') . '" alt="" />' . ' ' . _('Inventory Stock Dispatch Report') . '</p>';
	echo '<div class="page_help_text">' . _('Create a transfer batch of overstock from one location to another location that is below reorder level.') . '<br/>'
										. _('Quantity to ship is based on reorder level minus the quantity on hand at the To Location; if there is a') . '<br/>'
										. _('dispatch percentage entered, that needed quantity is inflated by the percentage entered.') . '<br/>'
										. _('Use Bulk Inventory Transfer - Receive to process the batch') . '</div>';

	$sql = "SELECT defaultlocation FROM www_users WHERE userid='".$_SESSION['UserID']."'";
	$result = DB_query($sql);
	$myrow = DB_fetch_array($result);
	$DefaultLocation = $myrow['defaultlocation'];
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
	echo '<div>
		  <br />';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$sql = "SELECT locations.loccode,
			locationname
		FROM locations
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
	$resultStkLocs = DB_query($sql);
	if (!isset($_POST['FromLocation'])) {
		$_POST['FromLocation']=$DefaultLocation;
	}
	echo '<table class="selection">
		 <tr>
			 <td>' . _('Dispatch Percent') . ':</td>
			 <td><input type ="text" name="Percent" class="number" size="8" value="0" /></td>
		 </tr>';
	echo '<tr>
			  <td>' . _('From Stock Location') . ':</td>
			  <td><select name="FromLocation"> ';
	while ($myrow=DB_fetch_array($resultStkLocs)){
		if ($myrow['loccode'] == $_POST['FromLocation']){
			 echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		} else {
			 echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		}
	}
	echo '</select></td>
		</tr>';
	DB_data_seek($resultStkLocs,0);
	if (!isset($_POST['ToLocation'])) {
		$_POST['ToLocation']=$DefaultLocation;
	}
	echo '<tr>
			<td>' . _('To Stock Location') . ':</td>
			<td><select name="ToLocation"> ';
	while ($myrow=DB_fetch_array($resultStkLocs)){
		if ($myrow['loccode'] == $_POST['ToLocation']){
			 echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		} else {
			 echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		}
	}
	echo '</select></td>
		</tr>';

	$SQL="SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$result1 = DB_query($SQL);
	if (DB_num_rows($result1)==0){
		echo '</table>';
		prnMsg(_('There are no stock categories currently defined please use the link below to set them up'),'warn');
		echo '<br /><a href="' . $RootPath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
		echo '</div>
			  </form>';
		include ('includes/footer.php');
		exit;
	}

	echo '<tr>
			<td>' . _('In Stock Category') . ':</td>
			<td><select name="StockCat">';
	if (!isset($_POST['StockCat'])){
		$_POST['StockCat']='All';
	}
	if ($_POST['StockCat']=='All'){
		echo '<option selected="selected" value="All">' . _('All') . '</option>';
	} else {
		echo '<option value="All">' . _('All') . '</option>';
	}
	while ($myrow1 = DB_fetch_array($result1)) {
		if ($myrow1['categoryid']==$_POST['StockCat']){
			echo '<option selected="selected" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
		} else {
			echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
		}
	}
	echo '</select></td>
		</tr>';

	echo '<tr>
			<td>' . _('Dispatch Strategy:') . ':</td>
			<td>
				<select name="Strategy">
					<option selected="selected" value="All">' . _('Items needed at TO location with overstock at FROM location') . '</option>
					<option value="OverFrom">' . _('Items with overstock at FROM location') . '</option>
				</select>
			</td>
			<td>&nbsp;</td>
		</tr>';

	echo '<tr>
			<td>' . _('Report Type') . ':</td>
			<td>
				<select name="ReportType">
					<option selected="selected" value="Batch">' . _('Create Batch') . '</option>
					<option value="Report">' . _('Report Only') . '</option>
				</select>
			</td>
			<td>&nbsp;</td>
		</tr>';


	echo '<tr>
			<td>' . _('Template') . ':</td>
			<td>
				<select name="template">
					<option selected="selected" value="fullprices">' . _('Full with Prices') . '</option>
					<option value="full">' . _('Full') . '</option>
					<option value="standard">' . _('Standard') . '</option>
					<option value="simple">' . _('Simple') . '</option>
				</select>
			</td>
			<td>&nbsp;</td>
		</tr>';

	echo '</table>
		 <br/>
		 <div class="centre">
			  <input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
		 </div>';
	echo '</div>
		  </form>';

	include('includes/footer.php');

} /*end of else not PrintPDF */


function PrintHeader(&$PDF,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
					 $Page_Width,$Right_Margin,$Trf_ID,$FromLocation,$ToLocation,$template,$CategoryDescription) {


	/*PDF page header for Stock Dispatch report */
	if ($PageNumber>1){
		$PDF->newPage();
	}
	$line_height=12;
	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;
	$YPos -=(3*$line_height);

	$PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,150,$FontSize,_('Stock Dispatch ') . $_POST['ReportType']);
	$PDF->addTextWrap(200,$YPos,30,$FontSize,_('From :'));
	$PDF->addTextWrap(230,$YPos,200,$FontSize,$FromLocation);

	$PDF->addTextWrap($Page_Width-$Right_Margin-150,$YPos,160,$FontSize,_('Printed') . ': ' .
		 Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber,'left');
	$YPos -= $line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Transfer No.'));
	$PDF->addTextWrap(95,$YPos,50,$FontSize,$Trf_ID);
	$PDF->setFont('','B');
	$PDF->addTextWrap(200,$YPos,30,$FontSize,_('To :'));
	$PDF->addTextWrap(230,$YPos,200,$FontSize,$ToLocation);
	$PDF->setFont('','');
	$YPos -= $line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Category'));
	$PDF->addTextWrap(95,$YPos,50,$FontSize,$_POST['StockCat']);
	$PDF->addTextWrap(160,$YPos,150,$FontSize,$CategoryDescription,'left');
	$YPos -= $line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Over transfer'));
	$PDF->addTextWrap(95,$YPos,50,$FontSize,$_POST['Percent'] . "%");
	if ($_POST['Strategy'] == 'OverFrom') {
		$PDF->addTextWrap(200,$YPos,200,$FontSize,_('Overstock items at '). $FromLocation);
	}else{
		$PDF->addTextWrap(200,$YPos,200,$FontSize,_('Items needed at '). $ToLocation);
	}
	$YPos -=(2*$line_height);
	/*set up the headings */
	$Xpos = $Left_Margin+1;

	if($template=='simple'){
		$PDF->addTextWrap(50,$YPos,100,$FontSize,_('Part Number'), 'left');
		$PDF->addTextWrap(135,$YPos,220,$FontSize,_('Description'), 'left');
		$PDF->addTextWrap(380,$YPos,45,$FontSize,_('QOH-From'), 'right');
		$PDF->addTextWrap(425,$YPos,40,$FontSize,_('QOH-To'), 'right');
		$PDF->addTextWrap(465,$YPos,40,$FontSize,_('Shipped'), 'right');
		$PDF->addTextWrap(510,$YPos,40,$FontSize,_('Received'), 'right');
	}else{
		$PDF->addTextWrap(50,$YPos,100,$FontSize,_('Part Number'), 'left');
		$PDF->addTextWrap(135,$YPos,170,$FontSize,_('Image/Description'), 'left');
		$PDF->addTextWrap(360,$YPos,40,$FontSize,_('From'), 'right');
		$PDF->addTextWrap(405,$YPos,40,$FontSize,_('To'), 'right');
		$PDF->addTextWrap(460,$YPos,40,$FontSize,_('Shipped'), 'right');
		$PDF->addTextWrap(510,$YPos,40,$FontSize,_('Received'), 'right');
		$YPos -= $line_height;
		$PDF->addTextWrap(370,$YPos,40,$FontSize,_('Available'), 'right');
		$PDF->addTextWrap(420,$YPos,40,$FontSize,_('Available'), 'right');

	}

	$FontSize=8;
	$PageNumber++;
} // End of PrintHeader() function
?>