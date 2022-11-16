<?php
include ('includes/session.php');

$Title = _('Customer Maintenance');
/* Manual links before header.php */
if (isset($_POST['Edit']) or isset($_GET['Edit']) or isset($_GET['DebtorNo'])) {
	$ViewTopic = 'AccountsReceivable';
	$BookMark = 'AmendCustomer';
	} else {
		$ViewTopic = 'AccountsReceivable';
		$BookMark = 'NewCustomer';
	}
	include ('includes/header.php');
	include ('includes/SQL_CommonFunctions.php');
	include ('includes/CountriesArray.php');

	echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/customer.png" title="', _('Customer'), '" alt="" />', ' ', _('Customer Maintenance'), '
	</p>';

	if (isset($_POST['submit'])) {
		//initialise no input errors assumed initially before we test
		$InputError = 0;
		$i = 1;
		/* actions to take once the user has clicked the submit button
		 ie the page has called itself with some user input */
		//first off validate inputs sensible
		$_POST['DebtorNo'] = mb_strtoupper($_POST['DebtorNo']);
		$SQL = "SELECT COUNT(debtorno) FROM debtorsmaster WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0 and isset($_POST['New'])) {
			$InputError = 1;
			prnMsg(_('The customer number already exists in the database'), 'error');
		} elseif (mb_strlen($_POST['CustName']) > 40 or mb_strlen($_POST['CustName']) == 0) {
			$InputError = 1;
			prnMsg(_('The customer name must be entered and be forty characters or less long'), 'error');
		} elseif ($_SESSION['AutoDebtorNo'] == 0 and mb_strlen($_POST['DebtorNo']) == 0) {
			$InputError = 1;
			prnMsg(_('The debtor code cannot be empty'), 'error');
		} elseif (mb_strlen($_POST['Address1']) > 40) {
			$InputError = 1;
			prnMsg(_('The Line 1 of the address must be forty characters or less long'), 'error');
		} elseif (mb_strlen($_POST['Address2']) > 40) {
			$InputError = 1;
			prnMsg(_('The Line 2 of the address must be forty characters or less long'), 'error');
		} elseif (mb_strlen($_POST['Address3']) > 40) {
			$InputError = 1;
			prnMsg(_('The Line 3 of the address must be forty characters or less long'), 'error');
		} elseif (mb_strlen($_POST['Address4']) > 50) {
			$InputError = 1;
			prnMsg(_('The Line 4 of the address must be fifty characters or less long'), 'error');
		} elseif (mb_strlen($_POST['Address5']) > 20) {
			$InputError = 1;
			prnMsg(_('The Line 5 of the address must be twenty characters or less long'), 'error');
		} elseif (!is_numeric(filter_number_format($_POST['CreditLimit']))) {
			$InputError = 1;
			prnMsg(_('The credit limit must be numeric'), 'error');
		} elseif (!is_numeric(filter_number_format($_POST['PymtDiscount']))) {
			$InputError = 1;
			prnMsg(_('The payment discount must be numeric'), 'error');
		} elseif (!is_date($_POST['ClientSince'])) {
			$InputError = 1;
			prnMsg(_('The customer since field must be a date in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
		} elseif (!is_numeric(filter_number_format($_POST['Discount']))) {
			$InputError = 1;
			prnMsg(_('The discount percentage must be numeric'), 'error');
		} elseif (filter_number_format($_POST['CreditLimit']) < 0) {
			$InputError = 1;
			prnMsg(_('The credit limit must be a positive number'), 'error');
		} elseif ((filter_number_format($_POST['PymtDiscount']) > 10) or (filter_number_format($_POST['PymtDiscount']) < 0)) {
			$InputError = 1;
			prnMsg(_('The payment discount is expected to be less than 10% and greater than or equal to 0'), 'error');
		} elseif ((filter_number_format($_POST['Discount']) > 100) or (filter_number_format($_POST['Discount']) < 0)) {
			$InputError = 1;
			prnMsg(_('The discount is expected to be less than 100% and greater than or equal to 0'), 'error');
		}
		if ($InputError != 1) {
			$SQL_ClientSince = FormatDateForSQL($_POST['ClientSince']);
			if (!isset($_POST['New'])) {
				$SQL = "SELECT count(id)
					  FROM debtortrans
					where debtorno = '" . $_POST['DebtorNo'] . "'";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_array($Result);
				if ($MyRow[0] == 0) {
					$SQL = "UPDATE debtorsmaster SET	name='" . $_POST['CustName'] . "',
												address1='" . $_POST['Address1'] . "',
												address2='" . $_POST['Address2'] . "',
												address3='" . $_POST['Address3'] . "',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												currcode='" . $_POST['CurrCode'] . "',
												clientsince='" . $SQL_ClientSince . "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount']) / 100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "',
												language_id='" . $_POST['LanguageID'] . "'
											WHERE debtorno = '" . stripslashes($_POST['DebtorNo']) . "'";
				} else {
					$CurrSQL = "SELECT currcode
					  		FROM debtorsmaster
							where debtorno = '" . $_POST['DebtorNo'] . "'";
					$CurrResult = DB_query($CurrSQL);
					$CurrRow = DB_fetch_array($CurrResult);
					$OldCurrency = $CurrRow[0];
					$SQL = "UPDATE debtorsmaster SET	name='" . $_POST['CustName'] . "',
												address1='" . $_POST['Address1'] . "',
												address2='" . $_POST['Address2'] . "',
												address3='" . $_POST['Address3'] . "',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												clientsince='" . $SQL_ClientSince . "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount']) / 100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "',
												language_id='" . $_POST['LanguageID'] . "'
											WHERE debtorno = '" . stripslashes($_POST['DebtorNo']) . "'";
					if ($OldCurrency != $_POST['CurrCode']) {
						prnMsg(_('The currency code cannot be updated as there are already transactions for this customer'), 'info');
					}

				}
				$ErrMsg = _('The customer could not be updated because');
				$Result = DB_query($SQL, $ErrMsg);
				prnMsg(_('Customer updated'), 'success');
			} else { //it is a new customer
				/* set the DebtorNo if $AutoDebtorNo in config.php has been set to
				 something greater 0 */
				if ($_SESSION['AutoDebtorNo'] > 0) {
					/* system assigned, sequential, numeric */
					if ($_SESSION['AutoDebtorNo'] == 1) {
						$_POST['DebtorNo'] = GetNextTransNo(500);
					}

				}
				$SQL = "INSERT INTO debtorsmaster (
							debtorno,
							name,
							address1,
							address2,
							address3,
							address4,
							address5,
							address6,
							currcode,
							clientsince,
							holdreason,
							paymentterms,
							discount,
							discountcode,
							pymtdiscount,
							creditlimit,
							salestype,
							invaddrbranch,
							taxref,
							customerpoline,
							typeid,
							language_id)
				VALUES ('" . $_POST['DebtorNo'] . "',
					'" . $_POST['CustName'] . "',
					'" . $_POST['Address1'] . "',
					'" . $_POST['Address2'] . "',
					'" . $_POST['Address3'] . "',
					'" . $_POST['Address4'] . "',
					'" . $_POST['Address5'] . "',
					'" . $_POST['Address6'] . "',
					'" . $_POST['CurrCode'] . "',
					'" . $SQL_ClientSince . "',
					'" . $_POST['HoldReason'] . "',
					'" . $_POST['PaymentTerms'] . "',
					'" . filter_number_format($_POST['Discount']) / 100 . "',
					'" . $_POST['DiscountCode'] . "',
					'" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
					'" . filter_number_format($_POST['CreditLimit']) . "',
					'" . $_POST['SalesType'] . "',
					'" . $_POST['AddrInvBranch'] . "',
					'" . $_POST['TaxRef'] . "',
					'" . $_POST['CustomerPOLine'] . "',
					'" . $_POST['typeid'] . "',
					'" . $_POST['LanguageID'] . "'
					)";
				$ErrMsg = _('This customer could not be added because');
				$Result = DB_query($SQL, $ErrMsg);
				$BranchCode = mb_substr($_POST['DebtorNo'], 0, 4);
				echo '<meta http-equiv="Refresh" content="0; url=', $RootPath, '/CustomerBranches.php?DebtorNo=', urlencode($_POST['DebtorNo']), '">';
				echo '<div class="centre">', _('You should automatically be forwarded to the entry of a new Customer Branch page'), '. ', _('If this does not happen'), ' (', _('if the browser does not support META Refresh'), ') ', '<a href="', $RootPath, '/CustomerBranches.php?DebtorNo=', urlencode($_POST['DebtorNo']), '"></a></div>';
				include ('includes/footer.php');
				exit;
			}
		} else {
			prnMsg(_('Validation failed') . '. ' . _('No updates or deletes took place'), 'error');
		}
	} elseif (isset($_POST['delete'])) {
		//the link to delete a selected record was clicked instead of the submit button
		$CancelDelete = 0;
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
		$SQL = "SELECT COUNT(*) FROM debtortrans WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$CancelDelete = 1;
			prnMsg(_('This customer cannot be deleted because there are transactions that refer to it'), 'warn');
			echo '<br /> ', _('There are'), ' ', $MyRow[0], ' ', _('transactions against this customer');
		} else {
			$SQL = "SELECT COUNT(*) FROM salesorders WHERE debtorno='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				$CancelDelete = 1;
				prnMsg(_('Cannot delete the customer record because orders have been created against it'), 'warn');
				echo '<br /> ' . _('There are') . ' ' . $MyRow[0] . ' ' . _('orders against this customer');
			} else {
				$SQL = "SELECT COUNT(*) FROM salesanalysis WHERE cust='" . $_POST['DebtorNo'] . "'";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0] > 0) {
					$CancelDelete = 1;
					prnMsg(_('Cannot delete this customer record because sales analysis records exist for it'), 'warn');
					echo '<br /> ' . _('There are') . ' ' . $MyRow[0] . ' ' . _('sales analysis records against this customer');
				} else {
					// Check if there are any users that refer to this CUSTOMER code
					$SQL = "SELECT COUNT(*) FROM www_users WHERE www_users.customerid = '" . $_POST['DebtorNo'] . "'";
					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0] > 0) {
						prnMsg(_('Cannot delete this customer because users exist that refer to it') . '. ' . _('Purge old users first'), 'warn');
						echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('users referring to this Branch/customer');
					} else {
						// Check if there are any contract that refer to this branch code
						$SQL = "SELECT COUNT(*) FROM contracts WHERE contracts.debtorno = '" . $_POST['DebtorNo'] . "'";
						$Result = DB_query($SQL);
						$MyRow = DB_fetch_row($Result);
						if ($MyRow[0] > 0) {
							prnMsg(_('Cannot delete this customer because contracts have been created that refer to it') . '. ' . _('Purge old contracts first'), 'warn');
							echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('contracts referring to this customer');
						}
					} //$MyRow[0] > 0
					

					
				}
			}
		}
		if ($CancelDelete == 0) { //ie not cancelled the delete as a result of above tests
			$SQL = "DELETE FROM custbranch WHERE debtorno='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL, $ErrMsg);
			$SQL = "DELETE FROM custcontacts WHERE debtorno='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			$SQL = "DELETE FROM debtorsmaster WHERE debtorno='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			prnMsg(_('Customer') . ' ' . $_POST['DebtorNo'] . ' ' . _('has been deleted - together with all the associated branches and contacts'), 'success');
			include ('includes/footer.php');
			unset($_SESSION['CustomerID']);
			exit;
		} //end if Delete Customer
		

		
	} //isset($_POST['delete'])
	if (isset($_POST['Reset'])) {
		unset($_POST['CustName']);
		unset($_POST['Address1']);
		unset($_POST['Address2']);
		unset($_POST['Address3']);
		unset($_POST['Address4']);
		unset($_POST['Address5']);
		unset($_POST['Address6']);
		unset($_POST['HoldReason']);
		unset($_POST['PaymentTerms']);
		unset($_POST['Discount']);
		unset($_POST['DiscountCode']);
		unset($_POST['PymtDiscount']);
		unset($_POST['CreditLimit']);
		// Leave Sales Type set so as to faciltate fast customer setup
		//	unset($_POST['SalesType']);
		unset($_POST['DebtorNo']);
		unset($_POST['InvAddrBranch']);
		unset($_POST['TaxRef']);
		unset($_POST['CustomerPOLine']);
		unset($_POST['LanguageID']);
		// Leave Type ID set so as to faciltate fast customer setup
		//	unset($_POST['typeid']);
		

		
	} //isset($_POST['Reset'])
	/*DebtorNo could be set from a post or a get when passed as a parameter to this page */

	if (isset($_POST['DebtorNo'])) {
		$DebtorNo = stripslashes($_POST['DebtorNo']);
	} elseif (isset($_GET['DebtorNo'])) {
		$DebtorNo = stripslashes($_GET['DebtorNo']);
	}
	if (isset($_POST['ID'])) {
		$ID = $_POST['ID'];
	} elseif (isset($_GET['ID'])) {
		$ID = $_GET['ID'];
	} else {
		$ID = '';
	}
	if (isset($_POST['Edit'])) {
		$Edit = $_POST['Edit'];
	} elseif (isset($_GET['Edit'])) {
		$Edit = $_GET['Edit'];
	} else {
		$Edit = '';
	}

	if (isset($_POST['Add'])) {
		$Add = $_POST['Add'];
	} elseif (isset($_GET['Add'])) {
		$Add = $_GET['Add'];
	}

	if (isset($_POST['AddContact']) and (isset($_POST['AddContact']) != '')) {
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . $DebtorNo . '">';
	}

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	/*If the page was called without $_POST['DebtorNo'] passed to page then assume a new customer is to be entered show a form with a Debtor Code field other wise the form showing the fields with the existing entries against the customer will show for editing with only a hidden DebtorNo field*/

	/* First check that all the necessary items have been setup */

	$SetupErrors = 0; //Count errors
	$SQL = "SELECT COUNT(typeabbrev)
			FROM salestypes";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 0) {
		prnMsg(_('In order to create a new customer you must first set up at least one sales type/price list') . '<br />' . _('Click') . ' ' . '<a target="_blank" href="' . $RootPath . '/SalesTypes.php">' . _('here') . ' ' . '</a>' . _('to set up your price lists'), 'warning') . '<br />';
		$SetupErrors+= 1;
	} //$MyRow[0] == 0
	$SQL = "SELECT COUNT(typeid)
				FROM debtortype";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 0) {
		prnMsg(_('In order to create a new customer you must first set up at least one customer type') . '<br />' . _('Click') . ' ' . '<a target="_blank" href="' . $RootPath . '/CustomerTypes.php">' . _('here') . ' ' . '</a>' . _('to set up your customer types'), 'warning');
		$SetupErrors+= 1;
	} //$MyRow[0] == 0
	if ($SetupErrors > 0) {
		echo '<br /><div class="centre"><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" >' . _('Click here to continue') . '</a></div>';
		include ('includes/footer.php');
		exit;
	} //$SetupErrors > 0
	$DataError = 0;

	//Main Table
	if (!isset($DebtorNo)) {
		/* if $AutoDebtorNo in config.php has not been set or if it has been set to a number less than one,
		 then provide an input box for the DebtorNo to manually assigned */
		//DebtorNo exists - either passed when calling the form or from the form itself
		$_POST['New'] = true;
		$DebtorNo = '';
		$_POST['CustName'] = '';
		$_POST['Address1'] = '';
		$_POST['Address2'] = '';
		$_POST['Address3'] = '';
		$_POST['Address4'] = '';
		$_POST['Address5'] = '';
		$_POST['Address6'] = $CountriesArray[$_SESSION['CountryOfOperation']];
		$_POST['SalesType'] = '';
		$_POST['CurrCode'] = $_SESSION['CompanyRecord']['currencydefault'];
		$_POST['ClientSince'] = date($_SESSION['DefaultDateFormat']);
		$_POST['HoldReason'] = '';
		$_POST['PaymentTerms'] = '';
		$_POST['Discount'] = locale_number_format(0, 2);
		$_POST['DiscountCode'] = '';
		$_POST['PymtDiscount'] = locale_number_format(0, 2);
		$_POST['CreditLimit'] = locale_number_format(0, 0);
		$_POST['InvAddrBranch'] = '';
		$_POST['TaxRef'] = '';
		$_POST['CustomerPOLine'] = '';
		$_POST['typeid'] = '';
		$_POST['LanguageID'] = '';
		//Sub table
		echo '<fieldset>
			<legend>', _('Insert new Customer'), '</legend>';
		/* if $AutoDebtorNo in config.php has not been set or if it has been set to a number less than one,
		 then provide an input box for the DebtorNo to manually assigned */

		if ($_SESSION['AutoDebtorNo'] == 0) {
			echo '<field>
				<label for="DebtorNo">', _('Customer Code'), ':</label>
				<input type="text" name="DebtorNo" value="" size="12" required="required" maxlength="10" />
			</field>';
		}
	} else {
		$SQL = "SELECT debtorno,
						name,
						address1,
						address2,
						address3,
						address4,
						address5,
						address6,
						currcode,
						salestype,
						clientsince,
						holdreason,
						paymentterms,
						discount,
						discountcode,
						pymtdiscount,
						creditlimit,
						invaddrbranch,
						taxref,
						customerpoline,
						typeid,
						language_id
				FROM debtorsmaster
				WHERE debtorno = '" . $DebtorNo . "'";
		$ErrMsg = _('The customer details could not be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_array($Result);
		/* if $AutoDebtorNo in config.php has not been set or if it has been set to a number less than one,
		 then display the DebtorNo */
		$_POST['CustName'] = $MyRow['name'];
		$_POST['Address1'] = $MyRow['address1'];
		$_POST['Address2'] = $MyRow['address2'];
		$_POST['Address3'] = $MyRow['address3'];
		$_POST['Address4'] = $MyRow['address4'];
		$_POST['Address5'] = $MyRow['address5'];
		$_POST['Address6'] = $MyRow['address6'];
		$_POST['SalesType'] = $MyRow['salestype'];
		$_POST['CurrCode'] = $MyRow['currcode'];
		$_POST['ClientSince'] = ConvertSQLDate($MyRow['clientsince']);
		$_POST['HoldReason'] = $MyRow['holdreason'];
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['Discount'] = locale_number_format($MyRow['discount'] * 100, 2);
		$_POST['DiscountCode'] = $MyRow['discountcode'];
		$_POST['PymtDiscount'] = locale_number_format($MyRow['pymtdiscount'] * 100, 2);
		$_POST['CreditLimit'] = locale_number_format($MyRow['creditlimit'], 0);
		$_POST['InvAddrBranch'] = $MyRow['invaddrbranch'];
		$_POST['TaxRef'] = $MyRow['taxref'];
		$_POST['CustomerPOLine'] = $MyRow['customerpoline'];
		$_POST['typeid'] = $MyRow['typeid'];
		$_POST['LanguageID'] = $MyRow['language_id'];
		echo '<input type="hidden" name="DebtorNo" value="' . $DebtorNo . '" />';
		echo '<fieldset>
			<legend>', _('Edit Customer Details'), '</legend>';

		if ($_SESSION['AutoDebtorNo'] == 0) {
			echo '<field>
				<label>', _('Customer Code'), ':</label>
				<div class="fieldtext">', stripslashes($DebtorNo), '</div>
			</field>';
		} //$_SESSION['AutoDebtorNo'] == 0
		

		
	}

	echo '<field>
		<label for="CustName">', _('Customer Name'), ':</label>
		<input type="text" name="CustName" value="', $_POST['CustName'], '" size="42" required="required" maxlength="40" />
	</field>
	<field>
		<label for="Address1">', _('Address Line 1 (Street)'), ':</label>
		<input type="text" name="Address1" size="42" maxlength="40" value="', $_POST['Address1'], '" />
	</field>
	<field>
		<label for="Address2">', _('Address Line 2 (Street)'), ':</label>
		<input type="text" name="Address2" size="42" maxlength="40" value="', $_POST['Address2'], '" />
	</field>
	<field>
		<label for="Address3">', _('Address Line 3 (Suburb/City)'), ':</label>
		<input type="text" name="Address3" size="42" maxlength="40" value="', $_POST['Address3'], '" />
	</field>
	<field>
		<label for="Address4">', _('Address Line 4 (State/Province)'), ':</label>
		<input type="text" name="Address4" size="42" maxlength="40" value="', $_POST['Address4'], '" />
	</field>
	<field>
		<label for="Address5">', _('Address Line 5 (Postal Code)'), ':</label>
		<input type="text" name="Address5" size="42" maxlength="40" value="', $_POST['Address5'], '" />
	</field>
	<field>
		<label for="Address6">', _('Country'), ':</label>
		<select name="Address6">';
	foreach ($CountriesArray as $CountryEntry => $CountryName) {
		if (isset($_POST['Address6']) and (strtoupper($_POST['Address6']) == strtoupper($CountryName))) {
			echo '<option selected="selected" value="', $CountryName, '">', $CountryName, '</option>';
		} //isset($_POST['Address6']) and ($_POST['Address6'] == $CountryName)
		elseif (!isset($_POST['Address6']) and $CountryName == "") {
			echo '<option selected="selected" value="', $CountryName, '">', $CountryName, '</option>';
		} //!isset($_POST['Address6']) and $CountryName == ""
		else {
			echo '<option value="', $CountryName, '">', $CountryName, '</option>';
		}
	}
	echo '</select>
	</field>';

	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
	echo '<field>
		<label for="SalesType">', _('Sales Type'), '/', _('Price List'), ':</label>
		<select name="SalesType">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['SalesType'] == $MyRow['typeabbrev']) {
			echo '<option selected="selected" value="', $MyRow['typeabbrev'], '">', $MyRow['sales_type'], '</option>';
		} else {
			echo '<option value="', $MyRow['typeabbrev'], '">', $MyRow['sales_type'], '</option>';
		}
	} //end while loop
	echo '</select>
	</field>';

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<field>
		<label for="typeid">', _('Customer Type'), ':</label>
		<select name="typeid">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['typeid'] == $MyRow['typeid']) {
			echo '<option selected="selected" value="', $MyRow['typeid'], '">', $MyRow['typename'], '</option>';
		} else {
			echo '<option value="', $MyRow['typeid'], '">', $MyRow['typename'], '</option>';
		}
	} //end while loop
	DB_data_seek($Result, 0);

	echo '</select>
	</field>';

	echo '<field>
		<label for="ClientSince">', _('Customer Since'), ' (', $_SESSION['DefaultDateFormat'], '):</label>
		<input type="text" required="required" class="date" name="ClientSince" value="', $_POST['ClientSince'], '" maxlength="10" size="12" />
	</field>';

	echo '<field>
		<label for="Discount">', _('Discount Percent'), ':</label>
		<input type="text" name="Discount" class="number" size="5" maxlength="5" value="', $_POST['Discount'], '" />
	</field>
	<field>
		<label for="DiscountCode">', _('Discount Code'), ':</label>
		<input type="text" name="DiscountCode" size="3" maxlength="2" value="', $_POST['DiscountCode'], '" />
	</field>
	<field>
		<label for="PymtDiscount">', _('Payment Discount Percent'), ':</label>
		<input type="text" class="number" name="PymtDiscount" size="5" maxlength="4" value="', $_POST['PymtDiscount'], '" />
	</field>
	<field>
		<label for="CreditLimit">', _('Credit Limit'), ':</label>
		<input type="text" class="number" name="CreditLimit" size="16" maxlength="14" value="', $_POST['CreditLimit'], '" />
	</field>
	<field>
		<label for="TaxRef">', _('Tax Reference'), ':</label>
		<input type="text" name="TaxRef" size="22" maxlength="20"  value="', $_POST['TaxRef'], '" />
	</field>';

	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	echo '<field>
		<label for="PaymentTerms">', _('Payment Terms'), ':</label>
		<select name="PaymentTerms">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['PaymentTerms'] == $MyRow['termsindicator']) {
			echo '<option selected="selected" value="', $MyRow['termsindicator'], '">', $MyRow['terms'], '</option>';
		} //$_POST['PaymentTerms'] == $MyRow['termsindicator']
		else {
			echo '<option value="', $MyRow['termsindicator'], '">', $MyRow['terms'], '</option>';
		}
	} //end while loop
	DB_data_seek($Result, 0);
	echo '</select>
	</field>';

	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	echo '<field>
		<label for="HoldReason">', _('Credit Status'), ':</label>
		<select name="HoldReason">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['HoldReason'] == $MyRow['reasoncode']) {
			echo '<option selected="selected" value="', $MyRow['reasoncode'], '">', $MyRow['reasondescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['reasoncode'], '">', $MyRow['reasondescription'], '</option>';
		}
	} //end while loop
	DB_data_seek($Result, 0);
	echo '</select>
	</field>';

	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	echo '<field>
		<label for="CurrCode">', _('Customer Currency'), ':</label>
		<select name="CurrCode">';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['CurrCode'] == $MyRow['currabrev']) {
			echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $MyRow['currency'], '</option>';
		} //$_POST['CurrCode'] == $MyRow['currabrev']
		else {
			echo '<option value="', $MyRow['currabrev'], '">', $MyRow['currency'], '</option>';
		}
	} //end while loop
	DB_data_seek($Result, 0);
	echo '</select>
	</field>';

	if (!isset($_POST['LanguageID']) or $_POST['LanguageID'] == '') {
		$_POST['LanguageID'] = $_SESSION['Language'];
	}

	echo '<field>
		<label for="LanguageID">', _('Language'), ':</label>
		<select name="LanguageID" required="required">';
	foreach ($LanguagesArray as $LanguageCode => $LanguageName) {
		if ($_POST['LanguageID'] == $LanguageCode) {
			echo '<option selected="selected" value="', $LanguageCode, '">', $LanguageName['LanguageName'], '</option>';
		} else {
			echo '<option value="', $LanguageCode, '">', $LanguageName['LanguageName'], '</option>';
		}
	}
	echo '</select>
	</field>';

	echo '<field>
		<label for="CustomerPOLine">', _('Require Customer PO Line on SO'), ':</label>
		<td><select name="CustomerPOLine">';
	if ($_POST['CustomerPOLine'] == 0) {
		echo '<option selected="selected" value="0">', _('No'), '</option>';
		echo '<option value="1">', _('Yes'), '</option>';
	} //$_POST['CustomerPOLine'] == 0
	else {
		echo '<option value="0">', _('No'), '</option>';
		echo '<option selected="selected" value="1">', _('Yes'), '</option>';
	}
	echo '</select>
	</field>';

	echo '<field>
		<label for="AddrInvBranch">', _('Invoice Addressing'), ':</label>
		<select name="AddrInvBranch">';
	if (isset($_POST['InvAddrBranch']) and $_POST['InvAddrBranch'] == 0) {
		echo '<option selected="selected" value="0">', _('Address to HO'), '</option>';
		echo '<option value="1">', _('Address to Branch'), '</option>';
	} else {
		echo '<option value="0">', _('Address to HO'), '</option>';
		echo '<option selected="selected" value="1">', _('Address to Branch'), '</option>';
	}

	echo '</select>
	</field>';

	echo '</fieldset>';

	if (isset($_GET['delete'])) { //User hit delete link on customer contacts
		/*Process this first before showing remaining contacts */
		$Resultupcc = DB_query("DELETE FROM custcontacts
								WHERE debtorno='" . $DebtorNo . "'
								AND contid='" . $ID . "'");
		prnMsg(_('Contact Deleted'), 'success');
	} //isset($_GET['delete'])
	$SQL = "SELECT contid,
					debtorno,
					contactname,
					role,
					phoneno,
					notes,
					email
			FROM custcontacts
			WHERE debtorno='" . $DebtorNo . "'
			ORDER BY contid";
	$Result = DB_query($SQL);

	echo '<table>';
	if (isset($_GET['Modify'])) {
		echo '<tr>
				<th>' . _('Name') . '</th>
				<th>' . _('Role') . '</th>
				<th>' . _('Phone Number') . '</th>
				<th>' . _('Email') . '</th>
				<th>' . _('Notes') . '</th>
			</tr>';
	} else {
		echo '<tr>
				<th>' . _('Name') . '</th>
				<th>' . _('Role') . '</th>
				<th>' . _('Phone Number') . '</th>
				<th>' . _('Email') . '</th>
				<th>' . _('Notes') . '</th>
				<th>' . _('Edit') . '</th>
				<th colspan="2"><input type="submit" name="AddContact" value="' . _('Add Contact') . '" /></th>
			</tr>';
	}

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
			<td>' . $MyRow['contactname'] . '</td>
			<td>' . $MyRow['role'] . '</td>
			<td>' . $MyRow['phoneno'] . '</td>
			<td><a href="mailto:' . $MyRow['email'] . '">' . $MyRow['email'] . '</a></td>
			<td>' . $MyRow['notes'] . '</td>
			<td><a href="AddCustomerContacts.php?Id=' . urlencode($MyRow['contid']) . '&amp;DebtorNo=' . urlencode($MyRow['debtorno']) . '">' . _('Edit') . '</a></td>
			<td><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?ID=' . urlencode($MyRow['contid']) . '&amp;DebtorNo=' . urlencode($MyRow['debtorno']) . '&amp;delete=1" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this customer contact?') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>
		</tr>';
	} //END WHILE LIST LOOP
	echo '</table>';

	echo '</td></tr></table>';

	if (isset($_POST['New']) and $_POST['New']) {
		echo '<input type="hidden" name="New" value="Yes" />';
		echo '<div class="centre">
				<input type="submit" name="submit" value="' . _('Add New Customer') . '" />&nbsp;
				<input type="submit" name="Reset" value="' . _('Reset') . '" />
			</div>';
	} elseif (!isset($_GET['Modify'])) {
		echo '<div class="centre">
				<input type="submit" name="submit" value="' . _('Update Customer') . '" />&nbsp;
				<input type="submit" name="delete" value="' . _('Delete Customer') . '" onclick="return MakeConfirm(\'' . _('Are You Sure?') . '\');" />
			</div>';
	} //!isset($_GET['Modify'])
	echo '</form>';

	include ('includes/footer.php');
?>