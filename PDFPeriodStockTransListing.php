<?php

include('includes/SQL_CommonFunctions.inc');
include ('includes/session.php');

$InputError=0;
if (isset($_POST['FromDate']) AND !Is_Date($_POST['FromDate'])){
	$msg = _('The date must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
	unset($_POST['FromDate']);
}

if (!isset($_POST['FromDate'])){

	 $Title = _('Stock Transaction Listing');
	 include ('includes/header.php');

	echo '<div class="centre">
			<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title . '" alt="" />' . ' '. _('Stock Transaction Listing') . '</p>
		</div>';

	if ($InputError==1){
		prnMsg($msg,'error');
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Enter the date from which the transactions are to be listed') . ':</td>
			<td><input type="text" required="required" autofocus="autofocus" name="FromDate" maxlength="10" size="11" class="date" value="' . Date($_SESSION['DefaultDateFormat']) . '" /></td>
		</tr>
		<tr>
			<td>' . _('Enter the date to which the transactions are to be listed') . ':</td>
			<td><input type="text" required="required" name="ToDate" maxlength="10" size="11" class="date" value="' . Date($_SESSION['DefaultDateFormat']) . '" /></td>
		</tr>
		<tr>
			<td>' . _('Transaction type') . '</td>
			<td><select name="TransType">
				<option value="10">' . _('Sales Invoice') . '</option>
				<option value="11">' . _('Sales Credit Note') . '</option>
				<option value="16">' . _('Location Transfer') . '</option>
				<option value="17">' . _('Stock Adjustment') . '</option>
				<option value="25">' . _('Purchase Order Delivery') . '</option>
				<option value="26">' . _('Work Order Receipt') . '</option>
				<option value="28">' . _('Work Order Issue') . '</option>
				</select></td>
		</tr>';

	$sql = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$resultStkLocs = DB_query($sql);

	echo '<tr>
			<td>' . _('For Stock Location') . ':</td>
			<td><select required="required" name="StockLocation">
				<option value="All">' . _('All') . '</option>';

	while ($myrow=DB_fetch_array($resultStkLocs)){
		if (isset($_POST['StockLocation']) AND $_POST['StockLocation']!='All'){
			if ($myrow['loccode'] == $_POST['StockLocation']){
				echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			}
		} elseif ($myrow['loccode']==$_SESSION['UserStockLocation']){
			echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			$_POST['StockLocation']=$myrow['loccode'];
		} else {
			echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
		}
	}
	echo '</select></td></tr>';

	echo '</table>
			<br />
			<div class="centre">
				<input type="submit" name="Go" value="' . _('Create PDF') . '" />
			</div>';
    echo '</div>
          </form>';

	 include('includes/footer.php');
	 exit;
} else {

	include('includes/ConnectDB.inc');
}


if ($_POST['StockLocation']=='All') {
	$sql= "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='".FormatDateForSQL($_POST['FromDate'])."'
			AND date_format(trandate, '%Y-%m-%d')<='".FormatDateForSQL($_POST['ToDate'])."'";
} else {
	$sql= "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='".FormatDateForSQL($_POST['FromDate'])."'
			AND date_format(trandate, '%Y-%m-%d')<='".FormatDateForSQL($_POST['ToDate'])."'
			AND stockmoves.loccode='" . $_POST['StockLocation'] . "'";
}
$result=DB_query($sql,'','',false,false);

if (DB_error_no()!=0){
	$Title = _('Transaction Listing');
	include('includes/header.php');
	prnMsg(_('An error occurred getting the transactions'),'error');
	include('includes/footer.php');
	exit;
} elseif (DB_num_rows($result) == 0){
	$Title = _('Transaction Listing');
	include('includes/header.php');
	echo '<br />';
	prnMsg (_('There were no transactions found in the database between the dates') . ' ' . $_POST['FromDate'] . ' ' . _('and') . ' '. $_POST['ToDate']  . '<br />' ._('Please try again selecting a different date'), 'info');
	include('includes/footer.php');
  	exit;
}

include('includes/PDFStarter.php');

/*PDFStarter.php has all the variables for page size and width set up depending on the users default preferences for paper size */

$PDF->addInfo('Title',_('Stock Transaction Listing'));
$PDF->addInfo('Subject',_('Stock transaction listing from') . '  ' . $_POST['FromDate'] . ' ' . $_POST['ToDate']);
$line_height=12;
$PageNumber = 1;


switch ($_POST['TransType']) {
	case 10:
		$TransType=_('Customer Invoices');
		break;
	case 11:
		$TransType=_('Customer Credit Notes');
		break;
	case 16:
		$TransType=_('Location Transfers');
		break;
	case 17:
		$TransType=_('Stock Adjustments');
		break;
	case 25:
		$TransType=_('Purchase Order Deliveries');
		break;
	case 26:
		$TransType=_('Work Order Receipts');
		break;
	case 28:
		$TransType=_('Work Order Issues');
		break;
}

include ('includes/PDFPeriodStockTransListingPageHeader.inc');

while ($myrow=DB_fetch_array($result)){

	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,160,$FontSize,$myrow['description'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+162,$YPos,80,$FontSize,$myrow['transno'], 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+242,$YPos,70,$FontSize,ConvertSQLDate($myrow['trandate']), 'left');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+312,$YPos,70,$FontSize,locale_number_format($myrow['qty'],$myrow['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+382,$YPos,70,$FontSize,$myrow['locationname'], 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+452,$YPos,70,$FontSize,$myrow['reference'], 'right');

	$YPos -= ($line_height);

	  if ($YPos - (2 *$line_height) < $Bottom_Margin){
		  /*Then set up a new page */
			  $PageNumber++;
		  include ('includes/PDFPeriodStockTransListingPageHeader.inc');
	  } /*end of new page header  */
} /* end of while there are customer receipts in the batch to print */


$YPos-=$line_height;

$ReportFileName = $_SESSION['DatabaseName'] . '_StockTransListing_' . date('Y-m-d').'.pdf';
$PDF->OutputD($ReportFileName);
$PDF->__destruct();

?>