<?php

/*  Performs login checks and $_SESSION initialisation */

define('UL_OK',  0);		/* User verified, session initialised */
define('UL_NOTVALID', 1);	/* User/password do not agree */
define('UL_BLOCKED', 2);	/* Account locked, too many failed logins */
define('UL_CONFIGERR', 3);	/* Configuration error in webERP or server */
define('UL_SHOWLOGIN', 4);
define('UL_MAINTENANCE', 5);

/*	UserLogin
 *  Function to validate user name,  perform validity checks and initialise
 *  $_SESSION data.
 *  Returns:
 *	See define() statements above.
 */

function userLogin($Name, $Password, $SysAdminEmail = '') {

	global $debug;
	global $PathPrefix;

	if (!isset($_SESSION['AccessLevel']) OR $_SESSION['AccessLevel'] == '' OR
		(isset($Name) AND $Name != '')) {
	/* if not logged in */
		$_SESSION['AccessLevel'] = '';
		$_SESSION['CustomerID'] = '';
		$_SESSION['UserBranch'] = '';
		$_SESSION['SalesmanLogin'] = '';
		$_SESSION['Module'] = '';
		$_SESSION['PageSize'] = '';
		$_SESSION['UserStockLocation'] = '';
		$_SESSION['AttemptsCounter']++;

		// Show login screen
		if (!isset($Name) or $Name == '') {
			$_SESSION['DatabaseName'] = '';
		    $_SESSION['CompanyName'] = '';
			return  UL_SHOWLOGIN;
		}
		/* The SQL to get the user info must use the * syntax because the field name could change between versions if the fields are specifed directly then the sql fails and the db upgrade will fail */
		$sql = "SELECT *
				FROM www_users
				WHERE www_users.userid='" . $Name . "'";

		$ErrMsg = _('Could not retrieve user details on login because');
		$debug =1;
        $PasswordVerified = false;
		$Auth_Result = DB_query($sql,$ErrMsg);

		if (DB_num_rows($Auth_Result) > 0) {
			$myrow = DB_fetch_array($Auth_Result);
			if (VerifyPass($Password,$myrow['password'])) {
				$PasswordVerified = true;
			} elseif (isset($GLOBALS['CryptFunction'])) {
				/*if the password stored in the DB was compiled the old way,
				 * the previous comparison will fail,
				 * try again with the old hashing algorithm,
				 * then re-hash the password using the new algorithm.
				 * The next version should not have $CryptFunction any more for new installs.
				 */
				switch ($GLOBALS['CryptFunction']) {
					case 'sha1':
						if ($myrow['password'] == sha1($Password)) {
							$PasswordVerified = true;
						}
						break;
					case 'md5':
						if ($myrow['password'] == md5($Password)) {
							$PasswordVerified = true;
						}
						break;
					default:
						if ($myrow['password'] == $Password) {
							$PasswordVerified = true;
						}
				}
				if ($PasswordVerified) {
					$sql = "UPDATE www_users SET password = '" . CryptPass($Password) . "'"
							. " WHERE userid = '" . $Name . "';";
					DB_query($sql);
				}

			}
		}


		// Populate session variables with data base results
		if ($PasswordVerified) {

			if ($myrow['blocked']==1){
			//the account is blocked
				return  UL_BLOCKED;
			}
			/*reset the attempts counter on successful login */
			$_SESSION['UserID'] = $myrow['userid'];
			$_SESSION['AttemptsCounter'] = 0;
			$_SESSION['AccessLevel'] = $myrow['fullaccess'];
			$_SESSION['CustomerID'] = $myrow['customerid'];
			$_SESSION['UserBranch'] = $myrow['branchcode'];
			$_SESSION['DefaultPageSize'] = $myrow['pagesize'];
			$_SESSION['UserStockLocation'] = $myrow['defaultlocation'];
			$_SESSION['UserEmail'] = $myrow['email'];
			$_SESSION['ModulesEnabled'] = explode(",", $myrow['modulesallowed']);
			$_SESSION['UsersRealName'] = $myrow['realname'];
			$_SESSION['Theme'] = $myrow['theme'];
			$_SESSION['Language'] = $myrow['language'];
			$_SESSION['SalesmanLogin'] = $myrow['salesman'];
			$_SESSION['CanCreateTender'] = $myrow['cancreatetender'];
			$_SESSION['AllowedDepartment'] = $myrow['department'];
			$_SESSION['ShowDashboard'] = $myrow['showdashboard'];
			$_SESSION['ShowPageHelp'] = $myrow['showpagehelp'];
			$_SESSION['ShowFieldHelp'] = $myrow['showfieldhelp'];
			$_SESSION['ScreenFontSize'] = $myrow['fontsize'];

			switch ($_SESSION['ScreenFontSize']) {
				case 0:
					$_SESSION['FontSize'] = '0.667rem';
				break;
				case 1:
					$_SESSION['FontSize'] = '0.833rem';
				break;
				case 2:
					$_SESSION['FontSize'] = '1rem';
				break;
				default:
					$_SESSION['FontSize'] = '0.833rem';
			}

			if (isset($myrow['pdflanguage'])) {
				$_SESSION['PDFLanguage'] = $myrow['pdflanguage'];
			} else {
				$_SESSION['PDFLanguage'] = '0'; //default to latin western languages
			}

			if ($myrow['displayrecordsmax'] > 0) {
				$_SESSION['DisplayRecordsMax'] = $myrow['displayrecordsmax'];
			} else {
				$_SESSION['DisplayRecordsMax'] = $_SESSION['DefaultDisplayRecordsMax'];  // default comes from config.php
			}

			$sql = "UPDATE www_users SET lastvisitdate='". date('Y-m-d H:i:s') ."'
							WHERE www_users.userid='" . $Name . "'";
			$Auth_Result = DB_query($sql);
			/*get the security tokens that the user has access to */
			$sql = "SELECT tokenid
					FROM securitygroups
					WHERE secroleid =  '" . $_SESSION['AccessLevel'] . "'";
			$Sec_Result = DB_query($sql);
			$_SESSION['AllowedPageSecurityTokens'] = array();
			if (DB_num_rows($Sec_Result)==0){
				return  UL_CONFIGERR;
			} else {
				$i=0;
				$UserIsSysAdmin = FALSE;
				while ($myrow = DB_fetch_row($Sec_Result)){
					if ($myrow[0] == 15){
						$UserIsSysAdmin = TRUE;
					}
					$_SESSION['AllowedPageSecurityTokens'][$i] = $myrow[0];
					$i++;
				}
			}


			/*User is logged in so get configuration parameters  - save in session*/
			include($PathPrefix . 'includes/GetConfig.php');


			if(isset($_SESSION['DB_Maintenance'])){
				if ($_SESSION['DB_Maintenance']>0)  { //run the DB maintenance script
					if (DateDiff(Date($_SESSION['DefaultDateFormat']),
							ConvertSQLDate($_SESSION['DB_Maintenance_LastRun'])
							,'d')	>= 	$_SESSION['DB_Maintenance']){

						/*Do the DB maintenance routing for the DB_type selected */
						DB_Maintenance();
						$_SESSION['DB_Maintenance_LastRun'] = Date('Y-m-d');

						/* Audit trail purge only runs if DB_Maintenance is enabled */
						if (isset($_SESSION['MonthsAuditTrail'])){
							 $sql = "DELETE FROM audittrail
									WHERE  transactiondate <= '" . Date('Y-m-d', mktime(0,0,0, Date('m')-$_SESSION['MonthsAuditTrail'])) . "'";
							$ErrMsg = _('There was a problem deleting expired audit-trail history');
							$result = DB_query($sql);
						}
					}
				}
			}

			/*Check to see if currency rates need to be updated */
			if (isset($_SESSION['UpdateCurrencyRatesDaily'])){
				if ($_SESSION['UpdateCurrencyRatesDaily']!=0)  {
					/* Only run the update to currency rates if today is after the last update i.e. only runs once a day */
					if (DateDiff(Date($_SESSION['DefaultDateFormat']),
						ConvertSQLDate($_SESSION['UpdateCurrencyRatesDaily']),'d')> 0){

						if ($_SESSION['ExchangeRateFeed']=='ECB') {
							$CurrencyRates = GetECBCurrencyRates(); // gets rates from ECB see includes/MiscFunctions.php
							/*Loop around the defined currencies and get the rate from ECB */
							if ($CurrencyRates!=false) {
								$CurrenciesResult = DB_query("SELECT currabrev FROM currencies");
								while ($CurrencyRow = DB_fetch_row($CurrenciesResult)){
									if ($CurrencyRow[0]!=$_SESSION['CompanyRecord']['currencydefault']){

										$UpdateCurrRateResult = DB_query("UPDATE currencies SET rate='" . GetCurrencyRate($CurrencyRow[0],$CurrencyRates) . "'
																			WHERE currabrev='" . $CurrencyRow[0] . "'");
									}
								}
							}
						} else {
							$CurrenciesResult = DB_query("SELECT currabrev FROM currencies");
							while ($CurrencyRow = DB_fetch_row($CurrenciesResult)){
								if ($CurrencyRow[0]!=$_SESSION['CompanyRecord']['currencydefault']){
									$UpdateCurrRateResult = DB_query("UPDATE currencies SET rate='" . google_currency_rate($CurrencyRow[0]) . "'
																		WHERE currabrev='" . $CurrencyRow[0] . "'");
								}
							}
						}
						$_SESSION['UpdateCurrencyRatesDaily'] = Date('Y-m-d');
						$UpdateConfigResult = DB_query("UPDATE config SET confvalue = '" . Date('Y-m-d') . "' WHERE confname='UpdateCurrencyRatesDaily'");
					}
				}
			}


			/* Set the logo if not yet set.
			 * will be done only once per session and each time
			 * we are not in session (i.e. before login)
			 */
			if (empty($_SESSION['LogoFile'])) {
				/* find a logo in companies/CompanyDir */
				if (file_exists($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/logo.png')) {
					$_SESSION['LogoFile'] = 'companies/' .  $_SESSION['DatabaseName'] . '/logo.png';
				} elseif (file_exists($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/logo.jpg')) {
					$_SESSION['LogoFile'] = 'companies/' .  $_SESSION['DatabaseName'] . '/logo.jpg';
				}
			}


			if(!isset($_SESSION['DB_Maintenance'])){
				return  UL_CONFIGERR;
			} else {

				if ($_SESSION['DB_Maintenance']==-1 AND !in_array(15, $_SESSION['AllowedPageSecurityTokens'])){
					// the configuration setting has been set to -1 ==> Allow SysAdmin Access Only
					// the user is NOT a SysAdmin
					return  UL_MAINTENANCE;
				}
			}
		} else {     // Incorrect password
			// 5 login attempts, show failed login screen

			if (!isset($_SESSION['AttemptsCounter'])) {
				$_SESSION['AttemptsCounter'] = 0;
			} elseif ($_SESSION['AttemptsCounter'] >= 5 AND isset($Name)) {
				/*User blocked from future accesses until sysadmin releases */
				$sql = "UPDATE www_users
							SET blocked=1
							WHERE www_users.userid='" . $Name . "'";
				$Auth_Result = DB_query($sql);

				if ($SysAdminEmail != ''){
					$EmailSubject = _('User access blocked'). ' ' . $Name ;
					$EmailText =  _('User ID') . ' ' . $Name . ' - ' . $Password . ' - ' . _('has been blocked access at') . ' ' .
								Date('Y-m-d H:i:s') . ' ' . _('from IP') . ' ' . $_SERVER["REMOTE_ADDR"] . ' ' . _('due to too many failed attempts.');
					if($_SESSION['SmtpSetting']==0){
							mail($SysAdminEmail,$EmailSubject,$EmailText);

					} else{
							include('includes/htmlMimeMail.php');
							$mail = new htmlMimeMail();
							$mail->setSubject($EmailSubject);
							$mail->setText($EmailText);
							$result = SendmailBySmtp($mail,array($SysAdminEmail));
					}

				}

				return  UL_BLOCKED;
			}
			return  UL_NOTVALID;
		}
	}		// End of userid/password check
	// Run with debugging messages for the system administrator(s) but not anyone else

	return   UL_OK;		    /* All is well */
}

?>
