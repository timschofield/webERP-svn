<?php


include('includes/session.php');

if (!isset($_POST['FromCat'])  OR $_POST['FromCat']=='') {
	$Title=_('Low Gross Profit Sales');
}
$debug=0;
if (isset($_POST['PrintPDF'])) {

	include('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Low Gross Profit Sales'));
	$PDF->addInfo('Subject', _('Low Gross Profit Sales'));
	$FontSize=10;
	$PageNumber=1;
	$line_height=12;

	$Title = _('Low GP sales') . ' - ' . _('Problem Report');

	if (! Is_Date($_POST['FromDate']) OR ! Is_Date($_POST['ToDate'])){
		include('includes/header.php');
		prnMsg(_('The dates entered must be in the format') . ' '  . $_SESSION['DefaultDateFormat'],'error');
		include('includes/footer.php');
		exit;
	}

	  /*Now figure out the data to report for the category range under review */
	$SQL = "SELECT stockmaster.categoryid,
					stockmaster.stockid,
					stockmoves.transno,
					stockmoves.trandate,
					systypes.typename,
					stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost as unitcost,
					stockmoves.qty,
					stockmoves.debtorno,
					stockmoves.branchcode,
					stockmoves.price*(1-stockmoves.discountpercent) as sellingprice,
					(stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost) AS gp,
					debtorsmaster.name
				FROM stockmaster INNER JOIN stockmoves
					ON stockmaster.stockid=stockmoves.stockid
				INNER JOIN systypes
					ON stockmoves.type=systypes.typeid
				INNER JOIN debtorsmaster
					ON stockmoves.debtorno=debtorsmaster.debtorno
				WHERE stockmoves.trandate >= '" . FormatDateForSQL($_POST['FromDate']) . "'
				AND stockmoves.trandate <= '" . FormatDateForSQL($_POST['ToDate']) . "'
				AND ((stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost))/(stockmoves.price*(1-stockmoves.discountpercent)) <=" . $_POST['GPMin']/100 . "
				ORDER BY stockmaster.stockid";

	$LowGPSalesResult = DB_query($SQL,'','',false,false);

	if (DB_error_no() !=0) {

	  include('includes/header.php');
		prnMsg(_('The low GP items could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
		echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
		  echo '<br />' . $SQL;
		}
		include('includes/footer.php');
		exit;
	}

	if (DB_num_rows($LowGPSalesResult) == 0) {

		include('includes/header.php');
		prnMsg(_('No low GP items retrieved'), 'warn');
		echo '<br /><a href="'  . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
		  echo '<br />' .  $SQL;
		}
		include('includes/footer.php');
		exit;
	}

	include ('includes/PDFLowGPPageHeader.inc');
	$Tot_Val=0;
	$Category = '';
	$CatTot_Val=0;
	while ($LowGPItems = DB_fetch_array($LowGPSalesResult)){

		$YPos -=$line_height;
		$FontSize=8;

		$LeftOvers = $PDF->addTextWrap($Left_Margin+2,$YPos,30,$FontSize,$LowGPItems['typename']);
		$LeftOvers = $PDF->addTextWrap(100,$YPos,30,$FontSize,$LowGPItems['transno']);
		$LeftOvers = $PDF->addTextWrap(130,$YPos,50,$FontSize,$LowGPItems['stockid']);
		$LeftOvers = $PDF->addTextWrap(220,$YPos,50,$FontSize,$LowGPItems['name']);
		$DisplayUnitCost = locale_number_format($LowGPItems['unitcost'],$_SESSION['CompanyRecord']['decimalplaces']);
		$DisplaySellingPrice = locale_number_format($LowGPItems['sellingprice'],$_SESSION['CompanyRecord']['decimalplaces']);
		$DisplayGP = locale_number_format($LowGPItems['gp'],$_SESSION['CompanyRecord']['decimalplaces']);
		$DisplayGPPercent = locale_number_format(($LowGPItems['gp']*100)/$LowGPItems['sellingprice'],1);

		$LeftOvers = $PDF->addTextWrap(330,$YPos,60,$FontSize,$DisplaySellingPrice,'right');
		$LeftOvers = $PDF->addTextWrap(380,$YPos,62,$FontSize,$DisplayUnitCost, 'right');
		$LeftOvers = $PDF->addTextWrap(440,$YPos,60,$FontSize,$DisplayGP, 'right');
		$LeftOvers = $PDF->addTextWrap(500,$YPos,60,$FontSize,$DisplayGPPercent . '%', 'right');

		if ($YPos < $Bottom_Margin + $line_height){
			include('includes/PDFLowGPPageHeader.inc');
		}

	} /*end low GP items while loop */

	$FontSize =10;

	$YPos -= (2*$line_height);
	$PDF->OutputD($_SESSION['DatabaseName'] . '_LowGPSales_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();

} else { /*The option to print PDF was not hit */

	include('includes/header.php');

	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title . '" alt="" />' . ' '
		. _('Low Gross Profit Report') . '</p>';

	if (!isset($_POST['FromDate']) OR !isset($_POST['ToDate'])) {

	/*if $FromDate is not set then show a form to allow input */
		$_POST['FromDate']=Date($_SESSION['DefaultDateFormat']);
		$_POST['ToDate']=Date($_SESSION['DefaultDateFormat']);
		$_POST['GPMin']=0;
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
        echo '<div>';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<table class="selection">';
		echo '<tr>
				<td>' . _('Sales Made From') . ' (' . _('in the format') . ' ' . $_SESSION['DefaultDateFormat'] . '):</td>
				<td><input type="text" required="required" autofocus="autofocus" class="date" name="FromDate" size="11" maxlength="10" value="' . $_POST['FromDate'] . '" /></td>
			</tr>
			<tr>
				<td>' . _('Sales Made To') . ' (' . _('in the format') . ' ' . $_SESSION['DefaultDateFormat'] . '):</td>
				<td><input type="text" required="required" class="date" name="ToDate" size="11" maxlength="10" value="' . $_POST['ToDate'] . '" /></td>
			</tr>
			<tr>
				<td>' . _('Show sales with GP % below') . ':</td>
				<td><input type="text" class="integer" name="GPMin" maxlength="3" size="3" value="' . $_POST['GPMin'] . '" /></td>
			</tr>
			</table>
			<br />
			<div class="centre">
				<input type="submit" name="PrintPDF" value="' . _('Print PDF') . '" />
			</div>
			</div>
        </form>';
	}
	include('includes/footer.php');

} /*end of else not PrintPDF */

?>