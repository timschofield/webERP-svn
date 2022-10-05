<?php


/* $Revision: 1.5 $ */

include('includes/session.php');

if (isset($_POST['JournalNo'])) {
	$JournalNo=$_POST['JournalNo'];
	$Type = $_POST['Type'];
} else if (isset($_GET['JournalNo'])) {
	$JournalNo=$_GET['JournalNo'];
	$Type = $_GET['Type'];
} else {
	$JournalNo='';
}
if (empty($JournalNo) OR empty($Type)) {
	prnMsg(_('This page should be called with Journal No and Type'),'error');
	include('includes/footer.php');
	exit;
}

if ($JournalNo=='Preview') {
	$FormDesign = simplexml_load_file(sys_get_temp_dir().'/Journal.xml');
} else {
	$FormDesign = simplexml_load_file($PathPrefix.'companies/'.$_SESSION['DatabaseName'].'/FormDesigns/Journal.xml');
}

// Set the paper size/orintation
$PaperSize = $FormDesign->PaperSize;
$PageNumber=1;
$line_height=$FormDesign->LineHeight;
include('includes/PDFStarter.php');
$PDF->addInfo('Title', _('General Ledger Journal') );

if ($JournalNo=='Preview') {
	$LineCount = 2; // UldisN
} else {
	$sql="SELECT gltrans.typeno,
				gltrans.trandate,
				gltrans.account,
				chartmaster.accountname,
				gltrans.narrative,
				gltrans.amount,
				gltrans.tag,
				tags.tagdescription,
				gltrans.jobref
			FROM gltrans
			INNER JOIN chartmaster
				ON gltrans.account=chartmaster.accountcode
			LEFT JOIN tags
				ON gltrans.tag=tags.tagref
			WHERE gltrans.type='" . $Type . "'
				AND gltrans.typeno='" . $JournalNo . "'";

	$result=DB_query($sql);
	$LineCount = DB_num_rows($result); // UldisN
	$myrow=DB_fetch_array($result);
	$JournalDate=$myrow['trandate'];
	DB_data_seek($result, 0);
	include('includes/PDFGLJournalHeader.inc');
}
$counter=1;
$YPos=$FormDesign->Data->y;
while ($counter<=$LineCount) {
	if ($JournalNo=='Preview') {
		$AccountCode=str_pad('',10,'x');
		$Date='1/1/1900';
		$Description=str_pad('',30,'x');
		$Narrative=str_pad('',30,'x');
		$Amount='XXXX.XX';
		$Tag=str_pad('',25,'x');
		$JobRef=str_pad('',25,'x');
	} else {
		$myrow=DB_fetch_array($result);
		if ($myrow['tag']==0) {
			$myrow['tagdescription']='None';
		}
		$AccountCode = $myrow['account'];
		$Description = $myrow['accountname'];
		$Date = $myrow['trandate'];
		$Narrative = $myrow['narrative'];
		$Amount = $myrow['amount'];
		$Tag = $myrow['tag'].' - '.$myrow['tagdescription'];
		$JobRef = $myrow['jobref'];
	}
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column1->x,$Page_Height-$YPos,$FormDesign->Data->Column1->Length,$FormDesign->Data->Column1->FontSize, $AccountCode);
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column2->x,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,$FormDesign->Data->Column2->FontSize, $Description);
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column3->x,$Page_Height-$YPos,$FormDesign->Data->Column3->Length,$FormDesign->Data->Column3->FontSize, $Narrative);
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column4->x,$Page_Height-$YPos,$FormDesign->Data->Column4->Length,$FormDesign->Data->Column4->FontSize, locale_number_format($Amount,$_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column5->x,$Page_Height-$YPos,$FormDesign->Data->Column5->Length,$FormDesign->Data->Column5->FontSize, $Tag);
	$LeftOvers = $PDF->addTextWrap($FormDesign->Data->Column6->x,$Page_Height-$YPos,$FormDesign->Data->Column6->Length,$FormDesign->Data->Column6->FontSize, $JobRef, 'left');
	$YPos += $line_height;
	$counter++;
	if ($YPos >= $FormDesign->LineAboveFooter->starty){
		/* We reached the end of the page so finsih off the page and start a newy */
		$PageNumber++;
		$YPos=$FormDesign->Data->y;
		include ('includes/PDFGrnHeader.inc');
	} //end if need a new page headed up
}

if ($LineCount == 0) {   //UldisN
	$Title = _('Printing Error');
	include('includes/header.php');
	prnMsg(_('There were no Journals to print'),'warn');
	echo '<br /><a href="'.$RootPath.'/index.php">' .  _('Back to the menu') . '</a>';
	include('includes/footer.php');
	exit;
} else {
    $PDF->OutputD($_SESSION['DatabaseName'] . '_Journal_' . date('Y-m-d').'.pdf');//UldisN
    $PDF->__destruct(); //UldisN
}
?>
