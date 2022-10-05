<?php
/* Creates a report of the customer and branch information held. This report has options to print only customer branches in a specified sales area and sales person. Additional option allows to list only those customers with activity either under or over a specified amount, since a specified date. */

include('includes/session.php');
$ViewTopic = 'ARReports';
$BookMark = 'CustomerListing';

if(isset($_POST['PrintPDF'])) {

	include('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Customer Listing') );
	$PDF->addInfo('Subject', _('Customer Listing') );
	$line_height=12;
	$PageNumber = 0;
	$FontSize=10;

	if($_POST['Activity']!='All') {
		if(!is_numeric($_POST['ActivityAmount'])) {
			$Title = _('Customer List') . ' - ' . _('Problem Report') . '....';
			include('includes/header.php');
			echo '<p />';
			prnMsg( _('The activity amount is not numeric and you elected to print customer relative to a certain amount of activity') . ' - ' . _('this level of activity must be specified in the local currency') .'.', 'error');
			include('includes/footer.php');
			exit;
		}
	}

	/* Now figure out the customer data to report for the selections made */

	if(in_array('All', $_POST['Areas'])) {
		if(in_array('All', $_POST['SalesPeople'])) {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					ORDER BY area,
						salesman,
						debtorsmaster.debtorno,
						custbranch.branchcode";
		} else {
		/* there are a range of salesfolk selected need to build the where clause */
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					WHERE (";

				$i=0;
				foreach ($_POST['SalesPeople'] as $Salesperson) {
					if($i>0) {
						$SQL .= " OR ";
					}
					$i++;
					$SQL .= "custbranch.salesman='" . $Salesperson ."'";
				}

				$SQL .=") ORDER BY area,
						salesman,
						debtorsmaster.debtorno,
						custbranch.branchcode";
		} /*end if SalesPeople =='All' */
	} else { /* not all sales areas has been selected so need to build the where clause */
		if(in_array('All', $_POST['SalesPeople'])) {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					WHERE (";

			$i=0;
			foreach ($_POST['Areas'] as $Area) {
				if($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.area='" . $Area ."'";
			}

			$SQL .= ") ORDER BY custbranch.area,
					custbranch.salesman,
					debtorsmaster.debtorno,
					custbranch.branchcode";
		} else {
		/* there are a range of salesfolk selected need to build the where clause */
			$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
					debtorsmaster.address1,
					debtorsmaster.address2,
					debtorsmaster.address3,
					debtorsmaster.address4,
					debtorsmaster.address5,
					debtorsmaster.address6,
					debtorsmaster.salestype,
					custbranch.branchcode,
					custbranch.brname,
					custbranch.braddress1,
					custbranch.braddress2,
					custbranch.braddress3,
					custbranch.braddress4,
					custbranch.braddress5,
					custbranch.braddress6,
					custbranch.contactname,
					custbranch.phoneno,
					custbranch.faxno,
					custbranch.email,
					custbranch.area,
					custbranch.salesman,
					areas.areadescription,
					salesman.salesmanname
				FROM debtorsmaster INNER JOIN custbranch
				ON debtorsmaster.debtorno=custbranch.debtorno
				INNER JOIN areas
				ON custbranch.area = areas.areacode
				INNER JOIN salesman
				ON custbranch.salesman=salesman.salesmancode
				WHERE (";

			$i=0;
			foreach ($_POST['Areas'] as $Area) {
				if($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.area='" . $Area ."'";
			}

			$SQL .= ") AND (";

			$i=0;
			foreach ($_POST['SalesPeople'] as $Salesperson) {
				if($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.salesman='" . $Salesperson ."'";
			}

			$SQL .=") ORDER BY custbranch.area,
					custbranch.salesman,
					debtorsmaster.debtorno,
					custbranch.branchcode";
		} /*end if Salesfolk =='All' */

	} /* end if not all sales areas was selected */


	$CustomersResult = DB_query($SQL);

	if(DB_error_no() !=0) {
	  $Title = _('Customer List') . ' - ' . _('Problem Report') . '....';
	  include('includes/header.php');
	   prnMsg( _('The customer List could not be retrieved by the SQL because') . ' - ' . DB_error_msg() );
	   echo '<br /><a href="' .$RootPath .'/index.php">' .  _('Back to the menu'). '</a>';
	   if($debug==1) {
	      echo '<br />' .  $SQL;
	   }
	   include('includes/footer.php');
	   exit;
	}

	if(DB_num_rows($CustomersResult) == 0) {
	  $Title = _('Customer List') . ' - ' . _('Problem Report') . '....';
	  include('includes/header.php');
	  prnMsg( _('This report has no output because there were no customers retrieved'), 'error' );
	  echo '<br /><a href="' .$RootPath .'/index.php">' .  _('Back to the menu'). '</a>';
	  include('includes/footer.php');
	  exit;
	}


	include('includes/PDFCustomerListPageHeader.inc');

	$Area ='';
	$SalesPerson='';

	while($Customers = DB_fetch_array($CustomersResult)) {

		if($_POST['Activity']!='All') {

			/*Get the total turnover in local currency for the customer/branch
			since the date entered */

			$SQL = "SELECT SUM((ovamount+ovfreight+ovdiscount)/rate) AS turnover
					FROM debtortrans
					WHERE debtorno='" . $Customers['debtorno'] . "'
					AND branchcode='" . $Customers['branchcode'] . "'
					AND (type=10 or type=11)
					AND trandate >='" . FormatDateForSQL($_POST['ActivitySince']). "'";
			$ActivityResult = DB_query($SQL, _('Could not retrieve the activity of the branch because'), _('The failed SQL was'));

			$ActivityRow = DB_fetch_row($ActivityResult);
			$LocalCurrencyTurnover = $ActivityRow[0];

			if($_POST['Activity'] =='GreaterThan') {
				if($LocalCurrencyTurnover > $_POST['ActivityAmount']) {
					$PrintThisCustomer = true;
				} else {
					$PrintThisCustomer = false;
				}
			} elseif($_POST['Activity'] =='LessThan') {
				if($LocalCurrencyTurnover < $_POST['ActivityAmount']) {
					$PrintThisCustomer = true;
				} else {
					$PrintThisCustomer = false;
				}
			}
		} else {
			$PrintThisCustomer = true;
		}

		if($PrintThisCustomer) {
			if($Area!=$Customers['area']) {
				$FontSize=10;
				$YPos -=$line_height;
				if($YPos < ($Bottom_Margin + 80)) {
					include('includes/PDFCustomerListPageHeader.inc');
				}
				$PDF->setFont('','B');
				$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,_('Customers in') . ' ' . $Customers['areadescription']);
				$Area = $Customers['area'];
				$PDF->setFont('','');
				$FontSize=8;
				$YPos -=$line_height;
			}

			if($SalesPerson!=$Customers['salesman']) {
				$FontSize=10;
				$YPos -=($line_height);
				if($YPos < ($Bottom_Margin + 80)) {
					include('includes/PDFCustomerListPageHeader.inc');
				}
				$PDF->setFont('','B');
				$LeftOvers = $PDF->addTextWrap($Left_Margin,$YPos,300-$Left_Margin,$FontSize,$Customers['salesmanname']);
				$PDF->setFont('','');
				$SalesPerson = $Customers['salesman'];
				$FontSize=8;
				$YPos -=$line_height;
			}

			$YPos -=$line_height;

			$LeftOvers = $PDF->addTextWrap(20,$YPos,60,$FontSize,$Customers['debtorno']);
			$LeftOvers = $PDF->addTextWrap(80,$YPos,150,$FontSize,$Customers['name']);
			$LeftOvers = $PDF->addTextWrap(80,$YPos-10,150,$FontSize,$Customers['address1']);
			$LeftOvers = $PDF->addTextWrap(80,$YPos-20,150,$FontSize,$Customers['address2']);
			$LeftOvers = $PDF->addTextWrap(80,$YPos-30,150,$FontSize,$Customers['address3']);
			$LeftOvers = $PDF->addTextWrap(140,$YPos-30,150,$FontSize,$Customers['address4']);
			$LeftOvers = $PDF->addTextWrap(180,$YPos-30,150,$FontSize,$Customers['address5']);
			$LeftOvers = $PDF->addTextWrap(210,$YPos-30,150,$FontSize,$Customers['address6']);

			$LeftOvers = $PDF->addTextWrap(230,$YPos,60,$FontSize,$Customers['branchcode']);
			$LeftOvers = $PDF->addTextWrap(230,$YPos-10,60,$FontSize, _('Price List') . ': ' . $Customers['salestype']);

			if($_POST['Activity']!='All') {
				$LeftOvers = $PDF->addTextWrap(230,$YPos-20,60,$FontSize,_('Turnover'),'right');
				$LeftOvers = $PDF->addTextWrap(230,$YPos-30,60,$FontSize,locale_number_format($LocalCurrencyTurnover,0), 'right');
			}

			$LeftOvers = $PDF->addTextWrap(290,$YPos,150,$FontSize,$Customers['brname']);
			$LeftOvers = $PDF->addTextWrap(290,$YPos-10,150,$FontSize,$Customers['contactname']);
			$LeftOvers = $PDF->addTextWrap(290,$YPos-20,150,$FontSize, _('Ph'). ': ' . $Customers['phoneno']);
			$LeftOvers = $PDF->addTextWrap(290,$YPos-30,150,$FontSize, _('Fax').': ' . $Customers['faxno']);
			$LeftOvers = $PDF->addTextWrap(440,$YPos,150,$FontSize,$Customers['braddress1']);
			$LeftOvers = $PDF->addTextWrap(440,$YPos-10,150,$FontSize,$Customers['braddress2']);
			$LeftOvers = $PDF->addTextWrap(440,$YPos-20,150,$FontSize,$Customers['braddress3']);
			$LeftOvers = $PDF->addTextWrap(500,$YPos-20,150,$FontSize,$Customers['braddress4']);
			$LeftOvers = $PDF->addTextWrap(540,$YPos-20,150,$FontSize,$Customers['braddress5']);
			$LeftOvers = $PDF->addTextWrap(570,$YPos-20,150,$FontSize,$Customers['braddress6']);
			$LeftOvers = $PDF->addTextWrap(440,$YPos-30,150,$FontSize,$Customers['email']);

			$PDF->line($Page_Width-$Right_Margin, $YPos-32,$Left_Margin, $YPos-32);

			$YPos -=40;
			if($YPos < ($Bottom_Margin +30)) {
				include('includes/PDFCustomerListPageHeader.inc');
			}
		} /*end if $PrintThisCustomer == true */
	} /*end while loop */

	$PDF->OutputD($_SESSION['DatabaseName'] . '_CustomerList_' . date('Y-m-d').'.pdf');//UldisN
	$PDF->__destruct();
	exit;

} else {

	$Title = _('Customer Details Listing');
	include('includes/header.php');
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" title="' .
		 $Title . '" alt="" />' . ' ' . $Title . '</p>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<table class="selection">';
	echo '<tr><td>' . _('For Sales Areas') . ':</td><td><select name="Areas[]" multiple="multiple">';

	$sql="SELECT areacode, areadescription FROM areas";
	$AreasResult= DB_query($sql);

	echo '<option selected="selected" value="All">' . _('All Areas') . '</option>';

	while($myrow = DB_fetch_array($AreasResult)) {
		echo '<option value="' . $myrow['areacode'] . '">' . $myrow['areadescription'] . '</option>';
	}
	echo '</select></td></tr>';

	echo '<tr><td>' . _('For Salesperson:') . '</td>
			<td><select name="SalesPeople[]" multiple="multiple">
				<option selected="selected" value="All">' .  _('All Salespeople') . '</option>';

	$sql = "SELECT salesmancode, salesmanname FROM salesman";
	$SalesFolkResult = DB_query($sql);

	while($myrow = DB_fetch_array($SalesFolkResult)) {
		echo '<option value="' . $myrow['salesmancode'] . '">' . $myrow['salesmanname'] . '</option>';
	}
	echo '</select></td></tr>';

	echo '<tr><td>' . _('Level Of Activity'). ':</td>
			<td><select name="Activity">
				<option selected="selected" value="All">' .  _('All customers') . '</option>
				<option value="GreaterThan">' .  _('Sales Greater Than') . '</option>
				<option value="LessThan">' .  _('Sales Less Than') . '</option>
				</select></td>
			<td>';

	echo '<input type="text" class="number" name="ActivityAmount" size="8" maxlength="8" value="0" /></td>
		</tr>';

	$DefaultActivitySince = Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,Date('m')-6,0,Date('y')));
	echo '<tr>
			<td>' . _('Activity Since'). ':</td>
			<td><input type="text" class="date" name="ActivitySince" size="11" maxlength="10" value="' . $DefaultActivitySince . '" /></td>
		</tr>';

	echo '</table>
			<br />
			<div class="centre">
				<input type="submit" name="PrintPDF" value="'. _('Print PDF'). '" />
			</div>';
    echo '</div>
          </form>';

	include('includes/footer.php');

} /*end of else not PrintPDF */
?>