<?php


// ReorderLevel.php - Report of parts with quantity below reorder level
// Shows if there are other locations that have quantities for the parts that are short

include('includes/session.php');
if (isset($_POST['PrintPDF'])) {
	$PaperSize='A4_Landscape';
	include('includes/PDFStarter.php');
	$PDF->addInfo('Title',_('Reorder Level Report'));
	$PDF->addInfo('Subject',_('Parts below reorder level'));
	$FontSize=9;
	$PageNumber=1;
	$line_height=12;

	$Xpos = $Left_Margin+1;
	$WhereCategory = ' ';
	$CategoryDescription = ' ';
	if ($_POST['StockCat'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
		$sql= "SELECT categoryid,
					categorydescription
				FROM stockcategory
				WHERE categoryid='" . $_POST['StockCat'] . "'";
		$result = DB_query($sql);
		$myrow = DB_fetch_row($result);
		$CategoryDescription = $myrow[1];
	}
	$WhereLocation = " ";
	if ($_POST['StockLocation'] != 'All') {
		$WhereLocation = " AND locstock.loccode='" . $_POST['StockLocation'] . "' ";
	}

	$sql = "SELECT locstock.stockid,
					stockmaster.description,
					locstock.loccode,
					locations.locationname,
					locstock.quantity,
					locstock.reorderlevel,
					stockmaster.decimalplaces,
					stockmaster.serialised,
					stockmaster.controlled
				FROM locstock INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
					stockmaster
				LEFT JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid,
					locations
				WHERE locstock.stockid=stockmaster.stockid " .
				$WhereLocation .
				"AND locstock.loccode=locations.loccode
				AND locstock.reorderlevel > locstock.quantity
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
				$WhereCategory . " ORDER BY locstock.loccode,locstock.stockid";

	$result = DB_query($sql,'','',false,true);

	if (DB_error_no() !=0) {
	  $Title = _('Reorder Level') . ' - ' . _('Problem Report');
	  include('includes/header.php');
	   prnMsg( _('The Reorder Level report could not be retrieved by the SQL because') . ' '  . DB_error_msg(),'error');
	   echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($debug==1){
		  echo '<br />' . $sql;
	   }
	   include('includes/footer.php');
	   exit;
	}

	PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
				$Page_Width,$Right_Margin,$CategoryDescription);

	$FontSize=8;

	$ListCount = 0; // UldisN

	while ($myrow = DB_fetch_array($result)){
		$YPos -=(2 * $line_height);

		$ListCount ++;

		$OnOrderSQL = "SELECT SUM(quantityord-quantityrecd) AS quantityonorder
								FROM purchorders
								LEFT JOIN purchorderdetails
									ON purchorders.orderno=purchorderdetails.orderno
								WHERE purchorders.status != 'Cancelled'
									AND purchorders.status != 'Rejected'
									AND purchorders.status != 'Pending'
									AND purchorders.status != 'Completed'
									AND purchorderdetails.itemcode='".$myrow['stockid']."'
									AND purchorders.intostocklocation='".$myrow['loccode']."'";
		$OnOrderResult = DB_query($OnOrderSQL);
		$OnOrderRow = DB_fetch_array($OnOrderResult);
		// Parameters for addTextWrap are defined in /includes/class.pdf.php
		// 1) X position 2) Y position 3) Width
		// 4) Height 5) Text 6) Alignment 7) Border 8) Fill - True to use SetFillColor
		// and False to set to transparent
		$fill = '';
		$PDF->addTextWrap(50,$YPos,100,$FontSize,$myrow['stockid'],'',0,$fill);
		$PDF->addTextWrap(150,$YPos,150,$FontSize,$myrow['description'],'',0,$fill);
		$PDF->addTextWrap(410,$YPos,60,$FontSize,$myrow['loccode'],'left',0,$fill);
		$PDF->addTextWrap(470,$YPos,50,$FontSize,locale_number_format($myrow['quantity'], $myrow['decimalplaces']),'right',0,$fill);
		$PDF->addTextWrap(520,$YPos,50,$FontSize,locale_number_format($myrow['reorderlevel'], $myrow['decimalplaces']),'right',0,$fill);
		$PDF->addTextWrap(570,$YPos,50,$FontSize,locale_number_format($OnOrderRow['quantityonorder'], $myrow['decimalplaces']),'right',0,$fill);
		$shortage = $myrow['reorderlevel'] - $myrow['quantity'] - $OnOrderRow['quantityonorder'];
		$PDF->addTextWrap(620,$YPos,50,$FontSize,locale_number_format($shortage, $myrow['decimalplaces']),'right',0,$fill);

		if ($YPos < $Bottom_Margin + $line_height){
		   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
					   $Right_Margin,$CategoryDescription);
		}

		// Print if stock for part in other locations
		$sql2 = "SELECT locstock.quantity,
								locstock.loccode,
								locstock.reorderlevel,
								stockmaster.decimalplaces
						 FROM locstock INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1, stockmaster
						 WHERE locstock.quantity > 0
						 AND locstock.quantity > reorderlevel
						 AND locstock.stockid = stockmaster.stockid
						 AND locstock.stockid ='" . $myrow['stockid'] .
						 "' AND locstock.loccode !='" . $myrow['loccode'] . "'";
		$OtherResult = DB_query($sql2,'','',false,true);
		while ($myrow2 = DB_fetch_array($OtherResult)){
			$YPos -=$line_height;

			// Parameters for addTextWrap are defined in /includes/class.pdf.php
			// 1) X position 2) Y position 3) Width
			// 4) Height 5) Text 6) Alignment 7) Border 8) Fill - True to use SetFillColor
			// and False to set to transparent
			$OnOrderSQL = "SELECT SUM(quantityord-quantityrecd) AS quantityonorder
								FROM purchorders
								LEFT JOIN purchorderdetails
								ON purchorders.orderno=purchorderdetails.orderno
								WHERE purchorders.status !='Cancelled'
									AND purchorders.status !='Rejected'
									AND purchorders.status !='Pending'
							      	      AND purchorderdetails.itemcode='".$myrow['stockid']."'
								      AND purchorders.intostocklocation='".$myrow2['loccode']."'";
			$OnOrderResult = DB_query($OnOrderSQL);
			$OnOrderRow = DB_fetch_array($OnOrderResult);

			$PDF->addTextWrap(410,$YPos,60,$FontSize,$myrow2['loccode'],'left',0,$fill);
			$PDF->addTextWrap(470,$YPos,50,$FontSize,locale_number_format($myrow2['quantity'], $myrow2['decimalplaces']),'right',0,$fill);
			$PDF->addTextWrap(520,$YPos,50,$FontSize,locale_number_format($myrow2['reorderlevel'], $myrow2['decimalplaces']),'right',0,$fill);
			$PDF->addTextWrap(570,$YPos,50,$FontSize,locale_number_format($OnOrderRow['quantityonorder'], $myrow['decimalplaces']),'right',0,$fill);
			$shortage = $myrow['reorderlevel'] - $myrow['quantity'] - $OnOrderRow['quantityonorder'];
			$PDF->addTextWrap(620,$YPos,50,$FontSize,locale_number_format($shortage, $myrow['decimalplaces']),'right',0,$fill);

			if ($YPos < $Bottom_Margin + $line_height){
			   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,$Right_Margin,$CategoryDescription);
			}

		} /*end while loop */

	} /*end while loop */

	if ($YPos < $Bottom_Margin + $line_height){
		   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,$Right_Margin,$CategoryDescription);
	}
/*Print out the grand totals */

	//$PDFcode = $PDF->output();
	//$len = mb_strlen($PDFcode);

	if ($ListCount == 0){
			$Title = _('Print Reorder Level Report');
			include('includes/header.php');
			prnMsg(_('There were no items with demand greater than supply'),'error');
			echo '<br /><a href="' . $RootPath . '/index.php?">' . _('Back to the menu') . '</a>';
			include('includes/footer.php');
			exit;
	} else {
			$PDF->OutputD($_SESSION['DatabaseName'] . '_ReOrderLevel_' . date('Y-m-d') . '.pdf');
			$PDF->__destruct();
	}

} else { /*The option to print PDF was not hit so display form */

	$Title=_('Reorder Level Reporting');
	include('includes/header.php');
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Inventory') . '" alt="" />' . ' ' . _('Inventory Reorder Level Report') . '</p>';
	echo '<div class="page_help_text">' . _('Use this report to display the reorder levels for Inventory items in different categories.') . '</div><br />';

	echo '<br /><form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$sql = "SELECT locations.loccode,
			locationname
		FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$resultStkLocs = DB_query($sql);
	echo '<table class="selection">
			<tr>
				<td>' . _('From Stock Location') . ':</td>
				<td><select name="StockLocation"> ';
	if (!isset($_POST['StockLocation'])){
		$_POST['StockLocation']='All';
	}
	if ($_POST['StockLocation']=='All'){
		echo '<option selected="selected" value="All">' . _('All') . '</option>';
	} else {
		echo '<option value="All">' . _('All') . '</option>';
	}
	while ($myrow=DB_fetch_array($resultStkLocs)){
		if ($myrow['loccode'] == $_POST['StockLocation']){
			 echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		} else {
			 echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		}
	}
	echo '</select></td></tr>';

	$SQL="SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype<>'A' ORDER BY categorydescription";
	$result1 = DB_query($SQL);
	if (DB_num_rows($result1)==0){
		echo '</td></tr>
			</table>
			<br />';
		prnMsg(_('There are no stock categories currently defined please use the link below to set them up'),'warn');
		echo '<br /><a href="' . $RootPath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
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
	echo '</select></td></tr>';
	echo '</table>
			<br />
			<div class="centre">
				<input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
			</div>';
    echo '</div>
          </form>';
	include('includes/footer.php');

} /*end of else not PrintPDF */

function PrintHeader(&$PDF,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
					 $Page_Width,$Right_Margin,$CategoryDescription) {

	/*PDF page header for Reorder Level report */
	if ($PageNumber>1){
		$PDF->newPage();
	}
	$line_height=12;
	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;
	$PDF->RoundRectangle($Left_Margin-5, $YPos+5+10, 310, ($line_height*3)+10+10, 10, 10);// Function RoundRectangle from includes/class.pdf.php
	$PDF->addTextWrap($Left_Margin,$YPos,290,$FontSize,$_SESSION['CompanyRecord']['coyname']);

	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,150,$FontSize,_('Reorder Level Report'));
	$PDF->addTextWrap($Page_Width-$Right_Margin-150,$YPos,160,$FontSize,_('Printed') . ': ' .
		 Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber,'left');
	$YPos -= $line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Category'));
	$PDF->addTextWrap(95,$YPos,50,$FontSize,$_POST['StockCat']);
	$PDF->addTextWrap(160,$YPos,150,$FontSize,$CategoryDescription,'left');
	$YPos -= $line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Location'));
	$PDF->addTextWrap(95,$YPos,50,$FontSize,$_POST['StockLocation']);
	$YPos -=(2*$line_height);

	/*set up the headings */
	$Xpos = $Left_Margin+1;

	$PDF->addTextWrap(50,$YPos,100,$FontSize,_('Part Number'), 'left');
	$PDF->addTextWrap(150,$YPos,150,$FontSize,_('Description'), 'left');
	$PDF->addTextWrap(410,$YPos,60,$FontSize,_('Location'), 'left');
	$PDF->addTextWrap(470,$YPos,50,$FontSize,_('Quantity'), 'right');
	$PDF->addTextWrap(520,$YPos,50,$FontSize,_('Reorder'), 'right');
	$PDF->addTextWrap(570,$YPos,50,$FontSize,_('On Order'), 'right');
	$PDF->addTextWrap(620,$YPos,50,$FontSize,_('Needed'), 'right');
	$YPos -= $line_height;
	$PDF->addTextWrap(515,$YPos,50,$FontSize,_('Level'), 'right');


	$FontSize=8;
//	$YPos =$YPos - (2*$line_height);
	$PageNumber++;
} // End of PrintHeader() function
?>