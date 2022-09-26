<?php
// Systems can temporarily force a reload by setting the variable
// $ForceConfigReload to true

if(isset($ForceConfigReload) AND $ForceConfigReload==true OR !isset($_SESSION['CompanyDefaultsLoaded'])) {
	$SQL = "SELECT confname, confvalue FROM config";
	$ErrMsg = _('Could not get the configuration parameters from the database because');
	$ConfigResult = DB_query($SQL,$ErrMsg);
	while( $MyRow = DB_fetch_array($ConfigResult) ) {
		if (is_numeric($MyRow['confvalue']) AND $MyRow['confname']!='DefaultPriceList' AND $MyRow['confname']!='VersionNumber'){
			//the variable name is given by $MyRow[0]
			$_SESSION[$MyRow['confname']] = (double) $MyRow['confvalue'];
		} else {
			$_SESSION[$MyRow['confname']] =  $MyRow['confvalue'];
		}
	} //end loop through all config variables
	$_SESSION['CompanyDefaultsLoaded'] = true;

	DB_free_result($ConfigResult); // no longer needed
	/*Maybe we should check config directories exist and try to create if not */

	if (!isset($_SESSION['VersionNumber'])){ // the config record for VersionNumber is not yet added
		header('Location: UpgradeDatabase.php'); //divert to the db upgrade if the VersionNumber is not in the config table
	}

	/*Load the pagesecurity settings from the database */
	$SQL="SELECT script, pagesecurity FROM scripts";
	$Result=DB_query($SQL,'','',false,false);
	if (DB_error_no()!=0){
		/* the table may not exist with the pagesecurity field in it if it is an older webERP database
		 * divert to the db upgrade if the VersionNumber is not in the config table
		 * */
		header('Location: UpgradeDatabase.php');
	}
	//Populate the PageSecurityArray array for each script's  PageSecurity value
	while ($MyRow=DB_fetch_array($Result)) {
		$_SESSION['PageSecurityArray'][$MyRow['script']]=$MyRow['pagesecurity'];
	}

	/*
	 check the decimalplaces field exists in currencies - this was added in 4.0 but is required in 4.04 as it is used everywhere as the default decimal places to show on all home currency amounts
	*/
	$Result = DB_query("SELECT decimalplaces FROM currencies",'','',false,false);
	if (DB_error_no()!=0) { //then decimalplaces not already a field in currencies
		$Result = DB_query("ALTER TABLE `currencies`
							ADD COLUMN `decimalplaces` tinyint(3) NOT NULL DEFAULT 2 AFTER `hundredsname`");
	}

	if ($_SESSION['DBUpdateNumber'] > 1) {
		$_SESSION['ChartLanguage'] = GetChartLanguage();
		$_SESSION['InventoryLanguage'] = GetInventoryLanguage();
	}

/* Also reads all the company data set up in the company record and returns an array */

	$SQL=	"SELECT	coyname,
					gstno,
					regoffice1,
					regoffice2,
					regoffice3,
					regoffice4,
					regoffice5,
					regoffice6,
					telephone,
					fax,
					email,
					currencydefault,
					debtorsact,
					pytdiscountact,
					creditorsact,
					payrollact,
					grnact,
					exchangediffact,
					purchasesexchangediffact,
					retainedearnings,
					freightact,
					gllink_debtors,
					gllink_creditors,
					gllink_stock,
					decimalplaces
				FROM companies
				INNER JOIN currencies ON companies.currencydefault=currencies.currabrev
				WHERE coycode=1";

	$ErrMsg = _('An error occurred accessing the database to retrieve the company information');
	$ReadCoyResult = DB_query($SQL,$ErrMsg);

	if (DB_num_rows($ReadCoyResult)==0) {
      		echo '<br /><b>';
		prnMsg( _('The company record has not yet been set up') . '</b><br />' . _('From the system setup tab select company maintenance to enter the company information and system preferences'),'error',_('CRITICAL PROBLEM'));
		exit;
	} else {
		$_SESSION['CompanyRecord'] = DB_fetch_array($ReadCoyResult);
	}

	/*Now read in smtp email settings - not needed in a properly set up server environment - but helps for those who can't control their server .. I think! */

	$SQL="SELECT id,
				host,
				port,
				heloaddress,
				username,
				password,
				timeout,
				auth
			FROM emailsettings";
	$Result=DB_query($SQL,'','',false,false);
	if (DB_error_no()==0 and DB_num_rows($Result) > 0) {
		/*test to ensure that the emailsettings table exists!!
		 * if it doesn't exist then we are into an UpgradeDatabase scenario anyway
		*/
		$MyRow=DB_fetch_array($Result);

		$_SESSION['SMTPSettings']['host']=$MyRow['host'];
		$_SESSION['SMTPSettings']['port']=$MyRow['port'];
		$_SESSION['SMTPSettings']['heloaddress']=$MyRow['heloaddress'];
		$_SESSION['SMTPSettings']['username']=$MyRow['username'];
		$_SESSION['SMTPSettings']['password']=$MyRow['password'];
		$_SESSION['SMTPSettings']['timeout']=$MyRow['timeout'];
		$_SESSION['SMTPSettings']['auth']=$MyRow['auth'];
	}
	//Add favorite scripts
	//Check that the favourites table exists (upgrades will choke otherwise)

	$SQL = "SELECT href, caption FROM favourites WHERE userid='" . $_SESSION['UserID'] . "'";
	$Result = DB_query($SQL,'','',false,false);
	if (DB_num_rows($Result)>0) {
		while ($MyRow = DB_fetch_array($Result)) {
			$_SESSION['Favourites'][$MyRow['href']] = $MyRow['caption'];
		}
	}

} //end if force reload or not set already


/*
These variable if required are in config.php

$DefaultLanguage = en_GB
$AllowDemoMode = 1

$EDIHeaderMsgId = D:01B:UN:EAN010
$EDIReference = WEBERP
$EDI_MsgPending = EDI_Pending
$EDI_MsgSent = EDI_Sent
$EDI_Incoming_Orders = EDI_Incoming_Orders

$RadioBeaconStockLocation = BL
$RadioBeaconHomeDir = /home/RadioBeacon
$RadioBeaconFileCounter = /home/RadioBeacon/FileCounter
$RadioBeaconFilePrefix = ORDXX
$RadioBeaconFTP_server = 192.168.2.2
$RadioBeaconFTP_user_name = RadioBeacon ftp server user name
$RadionBeaconFTP_user_pass = Radio Beacon remote ftp server password
*/
?>
