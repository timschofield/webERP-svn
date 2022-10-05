<?php


include ('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['BatchNo'])){
	$_POST['BatchNo'] = $_GET['BatchNo'];
}

if (!isset($_POST['BatchNo'])){
	$Title = _('Create PDF Print Out For A Batch Of Receipts');

	$ViewTopic = 'ARReports';
	$BookMark = 'BankingSummary';

	include ('includes/header.php');

	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/magnifier.png" title="' .
		 $Title . '" alt="" />' . ' ' . $Title . '</p>';

	$sql="SELECT DISTINCT
			transno,
			transdate
		FROM banktrans
		WHERE type=12
		ORDER BY transno DESC";
	$result=DB_query($sql);

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<div>';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<table class="selection">
		<tr>
			<td>' . _('Select the batch number of receipts to be printed') . ':</td>
			<td><select required="required" autofocus="autofocus" name="BatchNo">';
	while ($myrow=DB_fetch_array($result)) {
		echo '<option value="'.$myrow['transno'].'">' . _('Batch') .' '. $myrow['transno'].' - '.ConvertSqlDate($myrow['transdate']) . '</option>';
	}
	echo '</select></td>
			</tr>
			</table>';
	echo '<br />
			<div class="centre">
				<input type="submit" name="EnterBatchNo" value="' . _('Create PDF') . '" />
			</div>
        </div>
		</form>';

	include ('includes/footer.php');
	exit;
}

if (isset($_POST['BatchNo']) and $_POST['BatchNo']!='') {
	$SQL= "SELECT bankaccountname,
				bankaccountnumber,
				ref,
				transdate,
				banktranstype,
				bankact,
				banktrans.exrate,
				banktrans.functionalexrate,
				banktrans.currcode,
				currencies.decimalplaces AS currdecimalplaces
			FROM bankaccounts INNER JOIN banktrans
			ON bankaccounts.accountcode=banktrans.bankact
			INNER JOIN currencies
			ON bankaccounts.currcode=currencies.currabrev
			WHERE banktrans.transno='" . $_POST['BatchNo'] . "'
			AND banktrans.type=12";

	$ErrMsg = _('An error occurred getting the header information about the receipt batch number') . ' ' . $_POST['BatchNo'];
	$DbgMsg = _('The SQL used to get the receipt header information that failed was');
	$Result=DB_query($SQL,$ErrMsg,$DbgMsg);

	if (DB_num_rows($Result) == 0){
		$Title = _('Create PDF Print-out For A Batch Of Receipts');
		include ('includes/header.php');
		prnMsg(_('The receipt batch number') . ' ' . $_POST['BatchNo'] . ' ' . _('was not found in the database') . '. ' . _('Please try again selecting a different batch number'), 'warn');
		include('includes/footer.php');
		exit;
	}
	/* OK get the row of receipt batch header info from the BankTrans table */
	$myrow = DB_fetch_array($Result);
	$ExRate = $myrow['exrate'];
	$FunctionalExRate = $myrow['functionalexrate'];
	$Currency = $myrow['currcode'];
	$BankTransType = $myrow['banktranstype'];
	$BankedDate =  $myrow['transdate'];
	$BankActName = $myrow['bankaccountname'];
	$BankActNumber = $myrow['bankaccountnumber'];
	$BankingReference = $myrow['ref'];
    $BankCurrDecimalPlaces = $myrow['currdecimalplaces'];

	$SQL = "SELECT debtorsmaster.name,
			ovamount,
			invtext,
			reference
		FROM debtorsmaster INNER JOIN debtortrans
		ON debtorsmaster.debtorno=debtortrans.debtorno
		WHERE debtortrans.transno='" . $_POST['BatchNo'] . "'
		AND debtortrans.type=12";

	$CustRecs=DB_query($SQL,'','',false,false);
	if (DB_error_no()!=0){
		$Title = _('Create PDF Print-out For A Batch Of Receipts');
		include ('includes/header.php');
	   	prnMsg(_('An error occurred getting the customer receipts for batch number') . ' ' . $_POST['BatchNo'],'error');
		if ($debug==1){
	        	prnMsg(_('The SQL used to get the customer receipt information that failed was') . '<br />' . $SQL,'error');
	  	}
		include('includes/footer.php');
	  	exit;
	}
	$SQL = "SELECT narrative,
			amount
		FROM gltrans
		WHERE gltrans.typeno='" . $_POST['BatchNo'] . "'
		AND gltrans.type=12 and gltrans.amount <0
		AND gltrans.account !='" . $myrow['bankact'] . "'
		AND gltrans.account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "'";

	$GLRecs=DB_query($SQL,'','',false,false);
	if (DB_error_no()!=0){
		$Title = _('Create PDF Print-out For A Batch Of Receipts');
		include ('includes/header.php');
		prnMsg(_('An error occurred getting the GL receipts for batch number') . ' ' . $_POST['BatchNo'],'error');
		if ($debug==1){
			prnMsg(_('The SQL used to get the GL receipt information that failed was') . ':<br />' . $SQL,'error');
		}
		include('includes/footer.php');
		exit;
	}


	include('includes/PDFStarter.php');

	/*PDFStarter.php has all the variables for page size and width set up depending on the users default preferences for paper size */

	$PDF->addInfo('Title',_('Banking Summary'));
	$PDF->addInfo('Subject',_('Banking Summary Number') . ' ' . $_POST['BatchNo']);
	$line_height=12;
	$PageNumber = 0;
	$TotalBanked = 0;

	include ('includes/PDFBankingSummaryPageHeader.inc');

	while ($myrow=DB_fetch_array($CustRecs)){

		$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,locale_number_format(-$myrow['ovamount'],$BankCurrDecimalPlaces), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+65,$YPos,150,$FontSize,$myrow['name'], 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+215,$YPos,100,$FontSize,$myrow['invtext'], 'left');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+315,$YPos,100,$FontSize,$myrow['reference'], 'left');

		$YPos -= ($line_height);
		$TotalBanked -= $myrow['ovamount'];

		if ($YPos - (2 *$line_height) < $Bottom_Margin){
			/*Then set up a new page */
			include ('includes/PDFBankingSummaryPageHeader.inc');
		} /*end of new page header  */
	} /* end of while there are customer receipts in the batch to print */

	/* Right now print out the GL receipt entries in the batch */
	while ($myrow=DB_fetch_array($GLRecs)){

		$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,locale_number_format((-$myrow['amount']*$ExRate*$FunctionalExRate),$BankCurrDecimalPlaces), 'right');
		$LeftOvers = $PDF->addTextWrap($Left_Margin+65,$YPos,300,$FontSize,$myrow['narrative'], 'left');
		$YPos -= ($line_height);
		$TotalBanked +=  (-$myrow['amount']*$ExRate);

		if ($YPos - (2 *$line_height) < $Bottom_Margin){
			/*Then set up a new page */
			include ('includes/PDFBankingSummaryPageHeader.inc');
		} /*end of new page header  */
	} /* end of while there are GL receipts in the batch to print */


	$YPos-=$line_height;
	$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,60,$FontSize,locale_number_format($TotalBanked,2), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin+65,$YPos,300,$FontSize,_('TOTAL') . ' ' . $Currency . ' ' . _('BANKED'), 'left');

	$PDF->OutputD($_SESSION['DatabaseName'] . '_BankingSummary_' . date('Y-m-d').'.pdf');
	$PDF->__destruct();
}

?>