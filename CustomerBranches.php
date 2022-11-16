<?php
include ('includes/session.php');
$Title = _('Customer Branches'); // Screen identification.
$ViewTopic = 'AccountsReceivable'; // Filename's id in ManualContents.php's TOC.
$BookMark = 'NewCustomerBranch'; // Anchor's id in the manual's html document.
include ('includes/header.php');
include ('includes/CountriesArray.php');

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = mb_strtoupper($_GET['DebtorNo']);
} else if (isset($_POST['DebtorNo'])) {
	$DebtorNo = mb_strtoupper($_POST['DebtorNo']);
}

if (!isset($DebtorNo)) {
	prnMsg(_('This page must be called with the debtor code of the customer for whom you wish to edit the branches for') . '.
		<br />' . _('When the pages is called from within the system this will always be the case') . ' <br />' . _('Select a customer first then select the link to add/edit/delete branches'), 'warn');
	include ('includes/footer.php');
	exit;
}

if (isset($_GET['SelectedBranch'])) {
	$SelectedBranch = mb_strtoupper($_GET['SelectedBranch']);
} else if (isset($_POST['SelectedBranch'])) {
	$SelectedBranch = mb_strtoupper($_POST['SelectedBranch']);
}
$InputError = 0;

if (isset($SelectedBranch)) {
	echo '<div class="toplink">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode(stripslashes($DebtorNo)), '">', _('Show all branches defined for'), ' ', stripslashes($DebtorNo), '</a>
		</div>';
}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" />', ' ', _('Customer Branches For'), ' ', stripslashes($DebtorNo), ' - ', $MyRow[0], '
	</p>';

if (isset($_POST['submit'])) {

	$i = 1;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$_POST['BranchCode'] = mb_strtoupper($_POST['BranchCode']);

	if ($_SESSION['SalesmanLogin'] != '') {
		$_POST['Salesman'] = $_SESSION['SalesmanLogin'];
	}

	if (mb_strlen($_POST['BranchCode']) == 0) {
		$InputError = 1;
		prnMsg(_('The Branch code must be at least one character long'), 'error');
	}
	if (!is_numeric($_POST['FwdDate'])) {
		$InputError = 1;
		prnMsg(_('The date after which invoices are charged to the following month is expected to be a number and a recognised number has not been entered'), 'error');
	}
	if ($_POST['FwdDate'] > 30) {
		$InputError = 1;
		prnMsg(_('The date (in the month) after which invoices are charged to the following month should be a number less than 31'), 'error');
	}
	if (!is_numeric(filter_number_format($_POST['EstDeliveryDays']))) {
		$InputError = 1;
		prnMsg(_('The estimated delivery days is expected to be a number and a recognised number has not been entered'), 'error');
	}
	if (filter_number_format($_POST['EstDeliveryDays']) > 60) {
		$InputError = 1;
		prnMsg(_('The estimated delivery days should be a number of days less than 60') . '. ' . _('A package can be delivered by seafreight anywhere in the world normally in less than 60 days'), 'error');
	}
	if (!isset($_POST['EstDeliveryDays'])) {
		$_POST['EstDeliveryDays'] = 1;
	}
	if (!isset($Latitude)) {
		$Latitude = 0.0;
		$Longitude = 0.0;
	}
	if ($_SESSION['geocode_integration'] == 1) {
		// Get the lat/long from our geocoding host
		$SQL = "SELECT * FROM geocode_param WHERE 1";
		$ErrMsg = _('An error occurred in retrieving the information');
		$ResultGeo = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_array($ResultGeo);
		$ApiKey = $MyRow['geocode_key'];
		$MapHost = $MyRow['map_host'];
		define('MAPS_HOST', $MapHost);
		define('KEY', $ApiKey);
		if ($MapHost == "") {
			// check that some sane values are setup already in geocode tables, if not skip the geocoding but add the record anyway.
			echo '<div class="warn">', _('Warning - Geocode Integration is enabled, but no hosts are setup.  Go to Geocode Setup'), '</div>';
		} else {
			$Address = urlencode($_POST['BrAddress1'] . ', ' . $_POST['BrAddress2'] . ', ' . $_POST['BrAddress3'] . ', ' . $_POST['BrAddress4']);
			$BaseUrl = "http://" . MAPS_HOST . "/maps/api/geocode/xml?address=";
			$RequestUrl = $BaseUrl . $Address . ',&sensor=true';
			$xml = simplexml_load_string(utf8_encode(file_get_contents($RequestUrl))) or die('url not loading');

			$Status = $xml->status;
			if (strcmp($Status, 'OK') == 0) {
				// Successful geocode
				$Geocode_Pending = false;
				$Latitude = $xml->result->geometry->location->lat;
				$Longitude = $xml->result->geometry->location->lng;
			} else {
				// failure to geocode
				$Geocode_Pending = false;
				echo '<div class="page_help_text"><b>' . _('Geocode Notice') . ':</b> ' . _('Address') . ': ' . $Address . ' ' . _('failed to geocode');
				echo _('Received status') . ' ' . $Status . '</div>';
			}
		}
	}

	if (isset($_FILES['Attachment']) and $_FILES['Attachment']['name'] != '' and $InputError != 1) {

		$UploadTheFile = 'Yes'; //Assume all is well to start off with
		//But check for the worst
		if (mb_strtoupper(mb_substr(trim($_FILES['Attachment']['name']), mb_strlen($_FILES['Attachment']['name']) - 3)) != 'PDF') {
			prnMsg(_('Only pdf files are supported - a file extension of .pdf is expected'), 'warn');
			$UploadTheFile = 'No';
		} elseif ($_FILES['Attachment']['size'] > ($_SESSION['MaxImageSize'] * 1024)) { //File Size Check
			prnMsg(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $_SESSION['MaxImageSize'], 'warn');
			$UploadTheFile = 'No';
		} elseif ($_FILES['Attachment']['type'] != 'application/pdf') { //File Type Check
			prnMsg(_('Only pdf files can be uploaded'), 'warn');
			$UploadTheFile = 'No';
		} elseif ($_FILES['Attachment']['error'] == 6) { //upload temp directory check
			prnMsg(_('No tmp directory set. You must have a tmp directory set in your PHP for upload of files.'), 'warn');
			$UploadTheFile = 'No';
		} elseif (file_exists($FileName)) {
			prnMsg(_('Attempting to overwrite an existing item attachment'), 'warn');
			$Result = unlink($FileName);
			if (!$Result) {
				prnMsg(_('The existing attachment could not be removed'), 'error');
				$UploadTheFile = 'No';
			}
		}

		if ($UploadTheFile == 'Yes') {
			$DebtorNumber = $DebtorNo;
			$BranchCode = $SelectedBranch;
			$Name = $_FILES['Attachment']['name'];
			$Type = $_FILES['Attachment']['type'];
			$Size = $_FILES['Attachment']['size'];
			$fp = fopen($_FILES['Attachment']['tmp_name'], 'r');
			$Content = fread($fp, $Size);
			$Content = addslashes($Content);
			fclose($fp);
			$SQL = "INSERT INTO custbranchattachments VALUES('" . $DebtorNumber . "',
															'" . $BranchCode . "',
															'" . $Name . "',
															'" . $Type . "',
															" . $Size . ",
															'" . $Content . "'
															)";
			$Result = DB_query($SQL);

		}
	}

	if (isset($SelectedBranch) and $InputError != 1) {

		/*SelectedBranch could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the 	delete code below*/

		$SQL = "UPDATE custbranch SET brname = '" . $_POST['BrName'] . "',
						braddress1 = '" . $_POST['BrAddress1'] . "',
						braddress2 = '" . $_POST['BrAddress2'] . "',
						braddress3 = '" . $_POST['BrAddress3'] . "',
						braddress4 = '" . $_POST['BrAddress4'] . "',
						braddress5 = '" . $_POST['BrAddress5'] . "',
						braddress6 = '" . $_POST['BrAddress6'] . "',
						lat = '" . $Latitude . "',
						lng = '" . $Longitude . "',
						specialinstructions = '" . $_POST['SpecialInstructions'] . "',
						phoneno='" . $_POST['PhoneNo'] . "',
						faxno='" . $_POST['FaxNo'] . "',
						fwddate= '" . $_POST['FwdDate'] . "',
						contactname='" . $_POST['ContactName'] . "',
						salesman= '" . $_POST['Salesman'] . "',
						area='" . $_POST['Area'] . "',
						estdeliverydays ='" . filter_number_format($_POST['EstDeliveryDays']) . "',
						email='" . $_POST['Email'] . "',
						taxgroupid='" . $_POST['TaxGroup'] . "',
						defaultlocation='" . $_POST['DefaultLocation'] . "',
						brpostaddr1 = '" . $_POST['BrPostAddr1'] . "',
						brpostaddr2 = '" . $_POST['BrPostAddr2'] . "',
						brpostaddr3 = '" . $_POST['BrPostAddr3'] . "',
						brpostaddr4 = '" . $_POST['BrPostAddr4'] . "',
						brpostaddr5 = '" . $_POST['BrPostAddr5'] . "',
						disabletrans='" . $_POST['DisableTrans'] . "',
						defaultshipvia='" . $_POST['DefaultShipVia'] . "',
						custbranchcode='" . $_POST['CustBranchCode'] . "',
						deliverblind='" . $_POST['DeliverBlind'] . "'
					WHERE branchcode = '" . $SelectedBranch . "' AND debtorno='" . $DebtorNo . "'";
		if ($_SESSION['SalesmanLogin'] != '') {
			$SQL.= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
		}
		$Msg = $_POST['BrName'] . ' ' . _('branch has been updated.');

	} else if ($InputError != 1) {

		/*Selected branch is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Customer Branches form */

		$SQL = "INSERT INTO custbranch (branchcode,
						debtorno,
						brname,
						braddress1,
						braddress2,
						braddress3,
						braddress4,
						braddress5,
						braddress6,
						lat,
						lng,
 						specialinstructions,
						estdeliverydays,
						fwddate,
						salesman,
						phoneno,
						faxno,
						contactname,
						area,
						email,
						taxgroupid,
						defaultlocation,
						brpostaddr1,
						brpostaddr2,
						brpostaddr3,
						brpostaddr4,
						brpostaddr5,
						brpostaddr6,
						disabletrans,
						defaultshipvia,
						custbranchcode,
						deliverblind)
				VALUES ('" . html_entity_decode($_POST['BranchCode']) . "',
					'" . html_entity_decode($DebtorNo) . "',
					'" . $_POST['BrName'] . "',
					'" . $_POST['BrAddress1'] . "',
					'" . $_POST['BrAddress2'] . "',
					'" . $_POST['BrAddress3'] . "',
					'" . $_POST['BrAddress4'] . "',
					'" . $_POST['BrAddress5'] . "',
					'" . $_POST['BrAddress6'] . "',
					'" . $Latitude . "',
					'" . $Longitude . "',
					'" . $_POST['SpecialInstructions'] . "',
					'" . filter_number_format($_POST['EstDeliveryDays']) . "',
					'" . $_POST['FwdDate'] . "',
					'" . $_POST['Salesman'] . "',
					'" . $_POST['PhoneNo'] . "',
					'" . $_POST['FaxNo'] . "',
					'" . $_POST['ContactName'] . "',
					'" . $_POST['Area'] . "',
					'" . $_POST['Email'] . "',
					'" . $_POST['TaxGroup'] . "',
					'" . $_POST['DefaultLocation'] . "',
					'" . $_POST['BrPostAddr1'] . "',
					'" . $_POST['BrPostAddr2'] . "',
					'" . $_POST['BrPostAddr3'] . "',
					'" . $_POST['BrPostAddr4'] . "',
					'" . $_POST['BrPostAddr5'] . "',
					'',
					'" . $_POST['DisableTrans'] . "',
					'" . $_POST['DefaultShipVia'] . "',
					'" . $_POST['CustBranchCode'] . "',
					'" . $_POST['DeliverBlind'] . "'
					)";

		$Msg = _('Customer branch') . '<b> ' . $_POST['BranchCode'] . ': ' . $_POST['BrName'] . ' </b>' . _('has been added, add another branch, or return to the') . ' <a href="index.php">' . _('Main Menu') . '</a>';
	}

	//run the SQL from either of the above possibilites
	$ErrMsg = _('The branch record could not be inserted or updated because');
	if ($InputError == 0) {
		$Result = DB_query($SQL, $ErrMsg);
	}

	if (isset($_POST['CashSaleBranch'])) {
		/* It is possible that the location has changed so
		 * firstly remove any old record
		*/
		$SQL = "UPDATE locations SET cashsalecustomer='',
									 cashsalebranch=''
								WHERE cashsalecustomer='" . $DebtorNo . "'
									AND cashsalebranch='" . $_POST['BranchCode'] . "'";
		$Result = DB_query($SQL);
		/* Then update this location */
		$SQL = "UPDATE locations SET cashsalecustomer='" . $DebtorNo . "',
									 cashsalebranch='" . $_POST['BranchCode'] . "'
								WHERE loccode='" . $_POST['DefaultLocation'] . "'";
		$Result = DB_query($SQL);
	} else {
		$SQL = "UPDATE locations SET cashsalecustomer='',
									 cashsalebranch=''
								WHERE cashsalecustomer='" . $DebtorNo . "'
									AND cashsalebranch='" . $_POST['BranchCode'] . "'";
		$Result = DB_query($SQL);
	}

	if (DB_error_no() == 0 and $InputError == 0) {
		prnMsg($Msg, 'success');
		unset($_POST['BranchCode']);
		unset($_POST['BrName']);
		unset($_POST['BrAddress1']);
		unset($_POST['BrAddress2']);
		unset($_POST['BrAddress3']);
		unset($_POST['BrAddress4']);
		unset($_POST['BrAddress5']);
		unset($_POST['BrAddress6']);
		unset($_POST['SpecialInstructions']);
		unset($_POST['EstDeliveryDays']);
		unset($_POST['FwdDate']);
		unset($_POST['Salesman']);
		unset($_POST['PhoneNo']);
		unset($_POST['FaxNo']);
		unset($_POST['ContactName']);
		unset($_POST['Area']);
		unset($_POST['Email']);
		unset($_POST['TaxGroup']);
		unset($_POST['DefaultLocation']);
		unset($_POST['DisableTrans']);
		unset($_POST['BrPostAddr1']);
		unset($_POST['BrPostAddr2']);
		unset($_POST['BrPostAddr3']);
		unset($_POST['BrPostAddr4']);
		unset($_POST['BrPostAddr5']);
		unset($_POST['DefaultShipVia']);
		unset($_POST['CustBranchCode']);
		unset($_POST['DeliverBlind']);
		unset($_POST['CashSaleBranch']);
		unset($SelectedBranch);
	}
} else if (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
	$SQL = "SELECT COUNT(*) FROM debtortrans WHERE debtortrans.branchcode='" . $SelectedBranch . "' AND debtorno = '" . $DebtorNo . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(_('Cannot delete this branch because customer transactions have been created to this branch') . '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('transactions with this Branch Code'), 'error');

	} else {
		$SQL = "SELECT COUNT(*) FROM salesanalysis WHERE salesanalysis.custbranch='" . $SelectedBranch . "' AND salesanalysis.cust = '" . $DebtorNo . "'";

		$Result = DB_query($SQL);

		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(_('Cannot delete this branch because sales analysis records exist for it'), 'error');
			echo '<br />', _('There are'), ' ', $MyRow[0], ' ', _('sales analysis records with this Branch Code/customer');

		} else {

			$SQL = "SELECT COUNT(*) FROM salesorders WHERE salesorders.branchcode='" . $SelectedBranch . "' AND salesorders.debtorno = '" . $DebtorNo . "'";
			$Result = DB_query($SQL);

			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				prnMsg(_('Cannot delete this branch because sales orders exist for it') . '. ' . _('Purge old sales orders first'), 'warn');
				echo '<br />', _('There are'), ' ', $MyRow[0], ' ', _('sales orders for this Branch/customer');
			} else {
				// Check if there are any users that refer to this branch code
				$SQL = "SELECT COUNT(*) FROM www_users WHERE www_users.branchcode='" . $SelectedBranch . "' AND www_users.customerid = '" . $DebtorNo . "'";

				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);

				if ($MyRow[0] > 0) {
					prnMsg(_('Cannot delete this branch because users exist that refer to it') . '. ' . _('Purge old users first'), 'warn');
					echo '<br />', _('There are'), ' ', $MyRow[0], ' ', _('users referring to this Branch/customer');
				} else {
					// Check if there are any contract that refer to this branch code
					$SQL = "SELECT COUNT(*) FROM contracts WHERE contracts.branchcode='" . $SelectedBranch . "' AND contracts.debtorno = '" . $DebtorNo . "'";

					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);

					if ($MyRow[0] > 0) {
						prnMsg(_('Cannot delete this branch because contract have been created that refer to it') . '. ' . _('Purge old contracts first'), 'warn');
						echo '<br />', _('There are'), ' ', $MyRow[0], ' ', _('contracts referring to this branch/customer');
					} else {
						//check if this it the last customer branch - don't allow deletion of the last branch
						$SQL = "SELECT COUNT(*) FROM custbranch WHERE debtorno='" . $DebtorNo . "'";

						$Result = DB_query($SQL);
						$MyRow = DB_fetch_row($Result);

						if ($MyRow[0] == 1) {
							prnMsg(_('Cannot delete this branch because it is the only branch defined for this customer.'), 'warn');
						} else {
							$SQL = "DELETE FROM custbranch WHERE branchcode='" . $SelectedBranch . "' AND debtorno='" . $DebtorNo . "'";
							if ($_SESSION['SalesmanLogin'] != '') {
								$SQL.= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
							}
							$ErrMsg = _('The branch record could not be deleted') . ' - ' . _('the SQL server returned the following message');
							$Result = DB_query($SQL, $ErrMsg);
							if (DB_error_no() == 0) {
								prnMsg(_('Branch Deleted'), 'success');
							}
						}
					}
				}
			}
		} //end ifs to test if the branch can be deleted

	}
}
if (!isset($SelectedBranch)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedBranch will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters then none of the above are true and the list of branches will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of the records*/

	$SQL = "SELECT debtorsmaster.name,
					custbranch.branchcode,
					brname,
					salesman.salesmanname,
					areas.areadescription,
					contactname,
					phoneno,
					faxno,
					custbranch.email,
					taxgroups.taxgroupdescription,
					custbranch.disabletrans
				FROM custbranch INNER JOIN debtorsmaster
				ON custbranch.debtorno=debtorsmaster.debtorno
				INNER JOIN areas
				ON custbranch.area=areas.areacode
				INNER JOIN salesman
				ON custbranch.salesman=salesman.salesmancode
				INNER JOIN taxgroups
				ON custbranch.taxgroupid=taxgroups.taxgroupid
				WHERE custbranch.debtorno = '" . html_entity_decode($DebtorNo) . "'";
	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$TotalEnable = 0;
	$TotalDisable = 0;
	if ($MyRow) {

		echo '<table>
				<tr>
					<th>', _('Code'), '</th>
					<th>', _('Name'), '</th>
					<th>', _('Branch Contact'), '</th>
					<th>', _('Salesman'), '</th>
					<th>', _('Area'), '</th>
					<th>', _('Phone No'), '</th>
					<th>', _('Fax No'), '</th>
					<th>', _('Email'), '</th>
					<th>', _('Tax Group'), '</th>
					<th>', _('Enabled?'), '</th>
					<th></th>
					<th></th>
				</tr>';

		$k = 0;
		do {

			if ($MyRow['disabletrans']) {
				$MyRow['disabletrans'] = _('No');
			} else {
				$MyRow['disabletrans'] = _('Yes');
			}

			echo '<tr class="striped_row">
					<td>', $MyRow['branchcode'], '</td>
					<td>', $MyRow['brname'], '</td>
					<td>', $MyRow['contactname'], '</td>
					<td>', $MyRow['salesmanname'], '</td>
					<td>', $MyRow['areadescription'], '</td>
					<td>', $MyRow['phoneno'], '</td>
					<td>', $MyRow['faxno'], '</td>
					<td><a href="Mailto:', $MyRow['email'], '">', $MyRow['email'], '</a></td>
					<td>', $MyRow['taxgroupdescription'], '</td>
					<td>', $MyRow['disabletrans'], '</td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode(stripslashes($DebtorNo)), '&amp;SelectedBranch=', urlencode($MyRow['branchcode']), '">', _('Edit'), '</a></td>
					<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?DebtorNo=', urlencode(stripslashes($DebtorNo)), '&amp;SelectedBranch=', urlencode($MyRow['branchcode']), '&amp;delete=yes" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this branch?') . '\', \'Confirm Delete\', this);">', _('Delete Branch'), '</a></td>
				</tr>';

			if ($MyRow['disabletrans'] == _('No')) {
				++$TotalDisable;
			} else {
				++$TotalEnable;
			}

		} while ($MyRow = DB_fetch_array($Result));
		//END WHILE LIST LOOP
		echo '</table>';
		echo '<div class="centre">
				<b>', $TotalEnable, '</b> ', _('Branches are enabled.'), '<br />
				<b>', $TotalDisable, '</b> ', _('Branches are disabled.'), '<br />
				<b>', ($TotalEnable + $TotalDisable), '</b> ', _('Total Branches'), '
			</div>';
	} else {
		$SQL = "SELECT debtorsmaster.name,
						address1,
						address2,
						address3,
						address4,
						address5,
						address6
					FROM debtorsmaster
					WHERE debtorno = '" . $DebtorNo . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		echo '<div class="page_help_text">', _('No Branches are defined for'), ' - ', $MyRow[0], '. ', _('You must have a minimum of one branch for each Customer. Please add a branch now.'), '</div>';
		$_POST['BranchCode'] = mb_substr($DebtorNo, 0, 10);
		$_POST['BrName'] = $MyRow[0];
		$_POST['BrAddress1'] = $MyRow[1];
		$_POST['BrAddress2'] = $MyRow[2];
		$_POST['BrAddress3'] = $MyRow[3];
		$_POST['BrAddress4'] = $MyRow[4];
		$_POST['BrAddress5'] = $MyRow[5];
		$_POST['BrAddress6'] = $MyRow[6];
		unset($MyRow);
	}
}

if (!isset($_GET['delete'])) {
	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedBranch)) {
		//editing an existing branch
		$SQL = "SELECT branchcode,
						debtorno,
						brname,
						braddress1,
						braddress2,
						braddress3,
						braddress4,
						braddress5,
						braddress6,
						specialinstructions,
						estdeliverydays,
						fwddate,
						salesman,
						area,
						phoneno,
						faxno,
						contactname,
						email,
						taxgroupid,
						defaultlocation,
						brpostaddr1,
						brpostaddr2,
						brpostaddr3,
						brpostaddr4,
						brpostaddr5,
						disabletrans,
						defaultshipvia,
						custbranchcode,
						deliverblind
					FROM custbranch
					WHERE branchcode='" . $SelectedBranch . "'
					AND debtorno='" . $DebtorNo . "'";

		if ($_SESSION['SalesmanLogin'] != '') {
			$SQL.= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
		}

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		if ($InputError == 0) {
			$_POST['BranchCode'] = $MyRow['branchcode'];
			$_POST['DebtorNo'] = $MyRow['debtorno'];
			$_POST['BrName'] = $MyRow['brname'];
			$_POST['BrAddress1'] = $MyRow['braddress1'];
			$_POST['BrAddress2'] = $MyRow['braddress2'];
			$_POST['BrAddress3'] = $MyRow['braddress3'];
			$_POST['BrAddress4'] = $MyRow['braddress4'];
			$_POST['BrAddress5'] = $MyRow['braddress5'];
			$_POST['BrAddress6'] = $MyRow['braddress6'];
			$_POST['SpecialInstructions'] = $MyRow['specialinstructions'];
			$_POST['BrPostAddr1'] = $MyRow['brpostaddr1'];
			$_POST['BrPostAddr2'] = $MyRow['brpostaddr2'];
			$_POST['BrPostAddr3'] = $MyRow['brpostaddr3'];
			$_POST['BrPostAddr4'] = $MyRow['brpostaddr4'];
			$_POST['BrPostAddr5'] = $MyRow['brpostaddr5'];
			$_POST['EstDeliveryDays'] = locale_number_format($MyRow['estdeliverydays'], 0);
			$_POST['FwdDate'] = $MyRow['fwddate'];
			$_POST['ContactName'] = $MyRow['contactname'];
			$_POST['Salesman'] = $MyRow['salesman'];
			$_POST['Area'] = $MyRow['area'];
			$_POST['PhoneNo'] = $MyRow['phoneno'];
			$_POST['FaxNo'] = $MyRow['faxno'];
			$_POST['Email'] = $MyRow['email'];
			$_POST['TaxGroup'] = $MyRow['taxgroupid'];
			$_POST['DisableTrans'] = $MyRow['disabletrans'];
			$_POST['DefaultLocation'] = $MyRow['defaultlocation'];
			$_POST['DefaultShipVia'] = $MyRow['defaultshipvia'];
			$_POST['CustBranchCode'] = $MyRow['custbranchcode'];
			$_POST['DeliverBlind'] = $MyRow['deliverblind'];
		}

		$SQL = "SELECT cashsalecustomer,
						cashsalebranch
					FROM locations
					WHERE loccode='" . $_POST['DefaultLocation'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		if ($MyRow['cashsalecustomer'] == $_POST['DebtorNo'] and $MyRow['cashsalebranch'] == $_POST['BranchCode']) {
			$_POST['CashSaleBranch'] = true;
		} else {
			$_POST['CashSaleBranch'] = false;
		}

		echo '<input type="hidden" name="SelectedBranch" value="', stripslashes($SelectedBranch), '" />';
		echo '<input type="hidden" name="BranchCode" value="', stripslashes($_POST['BranchCode']), '" />';

		echo '<fieldset>
				<legend>', _('Edit Branch Details'), '</legend>
				<field>
					<label for="BranchCode">', _('Branch Code'), ':</label>
					<div class="fieldtext">', stripslashes($_POST['BranchCode']), '</div>
				</field>';

	} else { //end of if $SelectedBranch only do the else when a new record is being entered
		/* SETUP ANY $_GET VALUES THAT ARE PASSED.  This really is just used coming from the Customers.php when a new customer is created.
		Maybe should only do this when that page is the referrer?
		*/
		if (isset($_GET['BranchCode'])) {
			$SQL = "SELECT name,
						address1,
						address2,
						address3,
						address4,
						address5,
						address6
					FROM
					debtorsmaster
					WHERE debtorno='" . $_GET['BranchCode'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$_POST['BranchCode'] = $_GET['BranchCode'];
			$_POST['BrName'] = $MyRow['name'];
			$_POST['BrAddress1'] = $MyRow['addrsss1'];
			$_POST['BrAddress2'] = $MyRow['addrsss2'];
			$_POST['BrAddress3'] = $MyRow['addrsss3'];
			$_POST['BrAddress4'] = $MyRow['addrsss4'];
			$_POST['BrAddress5'] = $MyRow['addrsss5'];
			$_POST['BrAddress6'] = $MyRow['addrsss6'];
		}
		if (!isset($_POST['BranchCode'])) {
			$_POST['BranchCode'] = '';
			$_POST['DisableTrans'] = 1;
		}
		echo '<fieldset>
				<legend>', _('Create a New Branch'), '</legend>
				<field>
					<label for="BranchCode">', _('Branch Code'), ':</label>
					<input type="text" name="BranchCode" size="12" autofocus="autofocus" required="required" maxlength="10" value="', stripslashes($_POST['BranchCode']), '" />
				</field>';
		$_POST['DeliverBlind'] = $_SESSION['DefaultBlindPackNote'];
	}

	echo '<input type="hidden" name="DebtorNo" value="' . stripslashes($DebtorNo) . '" />';

	if (!isset($_POST['BrName'])) {
		$_POST['BrName'] = '';
	}
	echo '<field>
			<label for="BrName">', _('Branch Name') . ':</label>
			<input type="text" name="BrName" size="41" required="required" maxlength="40" value="', $_POST['BrName'], '" />
		</field>';

	if (!isset($_POST['ContactName'])) {
		$_POST['ContactName'] = '';
	}
	echo '<field>
			<label for="ContactName">', _('Branch Contact'), ':</label>
			<input type="text" name="ContactName" size="41" required="required" maxlength="40" value="', $_POST['ContactName'], '" />
		</field>';

	if (!isset($_POST['BrAddress1'])) {
		$_POST['BrAddress1'] = '';
	}
	echo '<field>
			<label for="BrAddress1">', _('Street Address 1 (Street)'), ':</label>
			<input type="text" name="BrAddress1" size="41" maxlength="40" value="', $_POST['BrAddress1'], '" />
		</field>';

	if (!isset($_POST['BrAddress2'])) {
		$_POST['BrAddress2'] = '';
	}
	echo '<field>
			<label for="BrAddress2">', _('Street Address 2 (Street)'), ':</label>
			<input type="text" name="BrAddress2" size="41" maxlength="40" value="', $_POST['BrAddress2'], '" />
		</field>';

	if (!isset($_POST['BrAddress3'])) {
		$_POST['BrAddress3'] = '';
	}
	echo '<field>
			<label for="BrAddress3">', _('Street Address 3 (Suburb/City)'), ':</label>
			<input type="text" name="BrAddress3" size="41" maxlength="40" value="', $_POST['BrAddress3'], '" />
		</field>';

	if (!isset($_POST['BrAddress4'])) {
		$_POST['BrAddress4'] = '';
	}
	echo '<field>
			<label for="BrAddress4">', _('Street Address 4 (State/Province)'), ':</label>
			<input type="text" name="BrAddress4" size="51" maxlength="50" value="', $_POST['BrAddress4'], '" />
		</field>';

	if (!isset($_POST['BrAddress5'])) {
		$_POST['BrAddress5'] = '';
	}
	echo '<field>
			<label for="BrAddress5">', _('Street Address 5 (Postal Code)'), ':</label>
			<input type="text" name="BrAddress5" size="21" maxlength="20" value="', $_POST['BrAddress5'], '" />
		</field>';

	if (!isset($_POST['BrAddress6'])) {
		$_POST['BrAddress6'] = '';
	}
	echo '<field>
			<label for="BrAddress6">', _('Country'), ':</label>
			<select name="BrAddress6">';
	foreach ($CountriesArray as $CountryEntry => $CountryName) {
		if (isset($_POST['BrAddress6']) and ($_POST['BrAddress6'] == $CountryName)) {
			echo '<option selected="selected" value="', $CountryName, '">', $CountryName, '</option>';
		} elseif (!isset($_POST['BrAddress6']) and $CountryName == '') {
			echo '<option selected="selected" value="', $CountryName, '">', $CountryName, '</option>';
		} else {
			echo '<option value="', $CountryName, '">', $CountryName, '</option>';
		}
	}
	echo '</select>
		</field>';

	if (!isset($_POST['SpecialInstructions'])) {
		$_POST['SpecialInstructions'] = '';
	}
	echo '<field>
			<label for="SpecialInstructions">', _('Special Instructions'), ':</label>
			<input type="text" name="SpecialInstructions" size="56" value="', $_POST['SpecialInstructions'], '" />
		</field>';

	if (!isset($_POST['EstDeliveryDays'])) {
		$_POST['EstDeliveryDays'] = 0;
	}
	echo '<field>
			<label for="EstDeliveryDays">', _('Default days to deliver'), ':</label>
			<input type="text" class="number" name="EstDeliveryDays" size="4" maxlength="2" value="', $_POST['EstDeliveryDays'], '" />
		</field>';

	if (!isset($_POST['FwdDate'])) {
		$_POST['FwdDate'] = 0;
	}
	echo '<field>
			<label for="FwdDate">', _('Forward Date After (day in month)'), ':</label>
			<input type="text" class="number" name="FwdDate" size="4" maxlength="2" value="', $_POST['FwdDate'], '" />
		</field>';

	DB_data_seek($Result, 0);

	$SQL = "SELECT areacode,
					areadescription
				FROM areas
				ORDER BY areadescription";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		echo '</fieldset>';
		prnMsg(_('There are no areas defined as yet') . ' - ' . _('customer branches must be allocated to an area') . '. ' . _('Please use the link below to define at least one sales area'), 'error');
		echo '<a href="', $RootPath, '/Areas.php">', _('Define Sales Areas'), '</a>';
		include ('includes/footer.php');
		exit;
	}

	echo '<field>
			<label for="Area">', _('Sales Area'), ':</label>
			<select required="required" name="Area">
				<option value=""></option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['Area']) and $MyRow['areacode'] == $_POST['Area']) {
			echo '<option selected="selected" value="', $MyRow['areacode'], '">', $MyRow['areadescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['areacode'], '">', $MyRow['areadescription'], '</option>';
		}
	} //end while loop
	echo '</select>
		</field>';
	DB_data_seek($Result, 0);

	if ($_SESSION['SalesmanLogin'] != '') {
		echo '<field>
				<label for="Salesman">', _('Salesperson'), ':</label>
				<div class="fieldtext">', $_SESSION['UsersRealName'], '</div>
			</field>';
	} else {

		//SQL to poulate account selection boxes
		$SQL = "SELECT salesmanname,
						salesmancode
				FROM salesman
				WHERE current = 1
				ORDER BY salesmanname";

		$Result = DB_query($SQL);

		if (DB_num_rows($Result) == 0) {
			echo '</table>';
			prnMsg(_('There are no sales people defined as yet') . ' - ' . _('customer branches must be allocated to a sales person') . '. ' . _('Please use the link below to define at least one sales person'), 'error');
			echo '<p align="center"><a href="', $RootPath, '/SalesPeople.php">', _('Define Sales People'), '</a>';
			include ('includes/footer.php');
			exit;
		}

		echo '<field>
				<label for="Salesman">', _('Salesperson'), ':</label>
				<select name="Salesman">';

		while ($MyRow = DB_fetch_array($Result)) {
			if (isset($_POST['Salesman']) and $MyRow['salesmancode'] == $_POST['Salesman']) {
				echo '<option selected="selected" value="', $MyRow['salesmancode'], '">', $MyRow['salesmanname'], '</option>';
			} else {
				echo '<option value="', $MyRow['salesmancode'], '">', $MyRow['salesmanname'], '</option>';
			}
		} //end while loop
		echo '</select>
			</field>';

		//	DB_data_seek($Result,0); //by thumb

	}

	// BEGIN: **********************************************************************
	$SQL = "SELECT locations.loccode,
					locationname
				FROM locations
				INNER JOIN locationusers
					ON locationusers.loccode=locations.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canupd=1
				WHERE locations.allowinvoicing='1'
				ORDER BY locationname";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {
		echo '</table>';
		prnMsg(_('There are no stock locations defined for which this user has access to as yet') . ' - ' . _('customer branches must refer to a default location where stock is normally drawn from') . '. ' . _('Please use the link below to define at least one stock location'), 'error');
		echo '<a href="', $RootPath, '/Locations.php">', _('Define Stock Locations'), '</a>';
		include ('includes/footer.php');
		exit;
	}

	echo '<field>
			<label ="DefaultLocation">', _('Draw Stock From'), ':</label>
			<select name="DefaultLocation">';

	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['DefaultLocation']) and $MyRow['loccode'] == $_POST['DefaultLocation']) {
			echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		} else {
			echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		}
	} //end while loop
	// END: ************************************************************************
	echo '</select>
		</field>';

	if (!isset($_POST['CashSaleBranch'])) {
		$_POST['CashSaleBranch'] = false;
	}
	echo '<field>
			<label for="CashSaleBranch">', _('Use this branch as default for cash sales'), ':</label>';
	if ($_POST['CashSaleBranch'] == true) {
		echo '<input type="checkbox" name="CashSaleBranch" checked="checked" />';
	} else {
		echo '<input type="checkbox" name="CashSaleBranch" />';
	}
	echo '</field>';

	if (!isset($_POST['PhoneNo'])) {
		$_POST['PhoneNo'] = '';
	}
	echo '<field>
			<label for="PhoneNo">', _('Phone Number'), ':</label>
			<input type="tel" name="PhoneNo" size="22" maxlength="20" value="', $_POST['PhoneNo'], '" />
		</field>';

	if (!isset($_POST['FaxNo'])) {
		$_POST['FaxNo'] = '';
	}
	echo '<field>
			<label for="FaxNo">', _('Fax Number'), ':</label>
			<input type="tel" name="FaxNo" size="22" maxlength="20" value="', $_POST['FaxNo'], '" />
		</field>';

	if (!isset($_POST['Email'])) {
		echo '<field>
				<label for="Email">', _('Email'), '</label>
				<input type="email" name="Email" size="56" maxlength="55" value="" />
			</field>';
	} else {
		echo '<field>
				<label for="Email"><a href="Mailto:', $_POST['Email'], '">', _('Email'), ':</a>: </label>
				<input type="email" name="Email" size="56" maxlength="55" value="' . $_POST['Email'] . '" />
			</field>';
	}

	DB_data_seek($Result, 0);

	$SQL = "SELECT taxgroupid, taxgroupdescription FROM taxgroups";
	$TaxGroupResults = DB_query($SQL);
	if (DB_num_rows($TaxGroupResults) == 0) {
		echo '</fieldset>';
		prnMsg(_('There are no tax groups defined - these must be set up first before any branches can be set up') . '
				<br /><a href="' . $RootPath . '/TaxGroups.php">' . _('Define Tax Groups') . '</a>', 'error');
		include ('includes/footer.php');
		exit;
	}
	echo '<field>
			<label for="TaxGroup">', _('Tax Group'), ':</label>
			<select name="TaxGroup">';

	while ($MyRow = DB_fetch_array($TaxGroupResults)) {
		if (isset($_POST['TaxGroup']) and $MyRow['taxgroupid'] == $_POST['TaxGroup']) {
			echo '<option selected="selected" value="', $MyRow['taxgroupid'], '">', $MyRow['taxgroupdescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['taxgroupid'], '">', $MyRow['taxgroupdescription'], '</option>';
		}
	} //end while loop
	echo '</select>
		</field>';

	if ($_SESSION['NewBranchesMustBeAuthorised'] == 0 or (in_array($_SESSION['PageSecurityArray']['EnableBranches.php'], $_SESSION['AllowedPageSecurityTokens']))) {
		echo '<field>
				<label for="DisableTrans">', _('Transactions on this branch'), ':</label>
				<select name="DisableTrans">';
		if (isset($_POST['DisableTrans']) and $_POST['DisableTrans'] == 0) {
			echo '<option selected="selected" value="0">', _('Enabled'), '</option>
					<option value="1">', _('Disabled'), '</option>';
		} else {
			echo '<option selected="selected" value="1">', _('Disabled'), '</option>
					<option value="0">', _('Enabled'), '</option>';
		}
		echo '</select>
			</field>';
	} else {
		echo '<field>
				<label for="DisableTrans">', _('Transactions on this branch'), ':</label>';
		if (isset($_POST['DisableTrans']) and $_POST['DisableTrans'] == 0) {
			echo '<div class="fieldtext">', _('Enabled'), '</div>';
		} else {
			echo '<div class="fieldtext">', _('Disabled'), '</div>';
		}
		echo '</field>';
	}
	$SQL = "SELECT shipper_id, shippername FROM shippers";
	$ShipperResults = DB_query($SQL);
	if (DB_num_rows($ShipperResults) == 0) {
		echo '</fieldset>';
		prnMsg(_('There are no shippers defined - these must be set up first before any branches can be set up') . '
				<br /><a href="' . $RootPath . '/Shippers.php">' . _('Define Shippers') . '</a>', 'error');
		include ('includes/footer.php');
		exit;
	}
	echo '<field>
			<label for="DefaultShipVia">', _('Default freight/shipper method'), ':</label>
			<select name="DefaultShipVia">';
	while ($MyRow = DB_fetch_array($ShipperResults)) {
		if (isset($_POST['DefaultShipVia']) and ($MyRow['shipper_id'] == $_POST['DefaultShipVia']) or ($_SESSION['Default_Shipper'] == $MyRow['shipper_id'])) {
			echo '<option selected="selected" value="', $MyRow['shipper_id'], '">', $MyRow['shippername'], '</option>';
		} else {
			echo '<option value="', $MyRow['shipper_id'], '">', $MyRow['shippername'], '</option>';
		}
	}
	echo '</select>
		</field>';

	/* This field is a default value that will be used to set the value
	on the sales order which will control whether or not to display the
	company logo and address on the packlist */
	echo '<field>
			<label for="DeliverBlind">', _('Default Packlist'), ':</label>
			<select name="DeliverBlind">';
	if ($_POST['DeliverBlind'] == 2) {
		echo '<option value="1">', _('Show company details and logo'), '</option>
				<option selected="selected" value="2">', _('Hide company details and logo'), '</option>';
	} else {
		echo '<option selected="selected" value="1">', _('Show company details and logo'), '</option>
				<option value="2">', _('Hide company details and logo'), '</option>';
	}
	echo '</select>
		</field>';

	if (!isset($_POST['BrPostAddr1'])) { // Postal address, line 1. Database: brpostaddr1, varchar(40)
		$_POST['BrPostAddr1'] = '';
	}
	echo '<field>
			<label for="BrPostAddr1">', _('Postal Address 1 (Street)'), ':</label>
			<input type="text" name="BrPostAddr1" size="41" maxlength="40" value="', $_POST['BrPostAddr1'], '" />
		</field>';

	if (!isset($_POST['BrPostAddr2'])) { // Postal address, line 2. Database: brpostaddr2, varchar(40)
		$_POST['BrPostAddr2'] = '';
	}
	echo '<field>
			<label for="BrPostAddr2">', _('Postal Address 2 (Suburb/City)'), ':</label>
			<input type="text" name="BrPostAddr2" size="41" maxlength="40" value="', $_POST['BrPostAddr2'], '" />
		</field>';

	if (!isset($_POST['BrPostAddr3'])) { // Postal address, line 3. Database: brpostaddr3, varchar(30)
		$_POST['BrPostAddr3'] = '';
	}
	echo '<field>
			<label for="BrPostAddr3">', _('Postal Address 3 (State)'), ':</label>
			<input type="text" name="BrPostAddr3" size="31" maxlength="30" value="', $_POST['BrPostAddr3'], '" />
		</field>';

	if (!isset($_POST['BrPostAddr4'])) { // Postal address, line 4. Database: brpostaddr4, varchar(20)
		$_POST['BrPostAddr4'] = '';
	}
	echo '<field>
			<label for="BrPostAddr4">', _('Postal Address 4 (Postal Code)'), ':</label>
			<input type="text" name="BrPostAddr4" size="21" maxlength="20" value="', $_POST['BrPostAddr4'], '" />
		</field>';

	if (!isset($_POST['BrPostAddr5'])) { // Postal address, line 5. Database: brpostaddr5, varchar(20)
		$_POST['BrPostAddr5'] = '';
	}
	echo '<field>
			<label for="BrPostAddr5">', _('Postal Address 5'), ':</label>
			<input type="text" name="BrPostAddr5" size="21" maxlength="20" value="', $_POST['BrPostAddr5'], '" />
		</field>';

	echo '<field>
			<label for="Attachment">', _('Customer Branch Attachment'), '</label>
			<input type="file" name="Attachment" id="Attachment" />
		</field>';

	if (!isset($_POST['CustBranchCode'])) {
		$_POST['CustBranchCode'] = '';
	}
	echo '<field>
			<label for="CustBranchCode">', _('Customers Internal Branch Code (EDI)'), ':</label>
			<input type="text" name="CustBranchCode" size="31" maxlength="30" value="', $_POST['CustBranchCode'], '" />
		</field>
	</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Enter Or Update Branch'), '" />
		</div>
	</form>';

} //end if record deleted no point displaying form to add record
include ('includes/footer.php');
?>