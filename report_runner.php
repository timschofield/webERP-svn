#!/usr/bin/php
<?php
//--------------------------------------------------------------------
// report_runner.php
// This program is designed to run reports in batch command mode for
// weberp. Much thanks to Phil Daintree as the major author of WEBERP.
//
// --------------------------------------------------------------------
// Written by Alan B Jones (mor3ton@yahoo.com)
// based on code orgiginally from weberp
// (c) alan jones 2006.
// (c) 2006 logic works Ltd and others
// licenced under the terms of the GPL V(2)
// if you want to know the details of the use of this software
// and how you are licenced to use it under the terms of the
// see here http://www.gnu.org/licenses/gpl.txt
//--------------------------------------------------------------------
//you must tell the script where you main installation is located
//Rememeber this is different for each location
//$weberp_home=/srv/www/htdocs/weberp

$usage="USAGE\n".$argv[0].":\n".
       "     -r reportnumber (the number of the weberp report)\n".
       "     -n reportname   (the name you want to give the report)\n".
       "     -e emailaddress[;emailaddress;emailaddres...] (who you want to send it to)\n".
       "     -d database name (the mysql db to use for the data for the report)\n".
       "     [-t reporttext ]  (some words you want to send with the report-optional)\n".
       "     [ -H weberpHOME]  (the home directory for weberp - or edit the php file)\n";

if ($argc < 7 ) {
        echo $usage;
        exit;
}
for ($i=1;$i<$argc;$i++){
        switch($argv[$i]) {
        case '-r':
                $i++;
                $reportnumber=$argv[$i];
             break;
        case '-n':
                $i++;
                $reportname=$argv[$i];
             break;
        case '-e':
                $i++;
                $emailaddresses=$argv[$i];
             break;
	case '-d':
                $i++;
                $DatabaseName=$argv[$i];
             break;
        case '-H':
                $i++;
                $WEBERPHOME=$argv[$i];
             break;
        case '-t':
                $i++;
                $mailtext=$argv[$i];
             break;
         default:
             echo "unknown option".$argv[$i]."\n";
             echo $usage;
             exit;
             break;
	}
}
// test the existance
if (( $reportname=="") ||
    ( $reportnumber=="") ||
    ( $emailaddresses=="")) {
             echo $usage;
             exit;
}
// do we have a variable
if ($WEBERPHOME!="") {
	$weberp_home=$WEBERPHOME;
}

if ($weberp_home=="") {
 	echo "weberp home is not set in this file or -H is not set";
}
// change directory to the weberp home to get all the includes to work nicely
chdir($weberp_home);

// get me the report name from the command line

$_GET['ReportID'] = $reportnumber;
$Recipients = explode(";",$emailaddresses);
//wrap in angles so that mail can accept it
for ($i=0;$i<count($Recipients); $i++) {
	$Recipient[$i]="<".$Recipient[$i].">";
}

$AllowAnyone = true;
include('includes/session.php');

include ('includes/ConstructSQLForUserDefinedSalesReport.inc');
include ('includes/PDFSalesAnalysis.inc');

include('includes/htmlMimeMail.php');
$mail = new htmlMimeMail();

if ($Counter >0){ /* the number of lines of the sales report is more than 0  ie there is a report to send! */
	$PDFcode = $PDF->output();
	$fp = fopen( $_SESSION['reports_dir']. "/".$reportname,"wb");
	fwrite ($fp, $PDFcode);
	fclose ($fp);

	$attachment = $mail->getFile( $_SESSION['reports_dir'] . "/".$reportname);
	$mail->setText($mailtext."\nPlease find herewith ".$reportname."  report");
	$mail->setSubject($reportname." Report");
	$mail->addAttachment($attachment, $reportname, 'application/pdf');
	if($_SESSION['SmtpSetting']==0){
		$mail->setFrom("");
		$result = $mail->send($Recipients);
	}else{
		$result = SendmailBySmtp($mail,$Recipients);
	}

} else {
	$mail->setText("Error running automated sales report number $ReportID");
	if($_SESSION['SmtpSetting']==0){
		$mail->setFrom("Do_not_reply_".$_SESSION['CompanyRecord']['coyname'] . "<" . $_SESSION['CompanyRecord']['email'] . ">");
		$result = $mail->send($Recipients);
	}else{
		$result = SendmailBySmtp($mail,$Recipients);
	}
}
?>
