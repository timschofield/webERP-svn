<?php



include('includes/session.php');

if (!in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens'])){
	echo '<html><body><br /><br /><br /><br /><br /><br /><br /><div class="centre"><font color="red" size="4"><b>' . _('The security settings on your account do not permit you to access this function') . '</b></font></div></body></html>';
	exit;
}

include('includes/ConstructSQLForUserDefinedSalesReport.inc');

if (isset($_GET['ProducePDF'])){

	include ('includes/PDFSalesAnalysis.inc');

	if ($Counter >0) {
		$PDF->OutputD('SalesAnalysis_' . date('Y-m-d') . '.pdf');
		$PDF->__destruct();
	} else {
		$PDF->__destruct();
		$Title = _('User Defined Sales Analysis Problem');
		include('includes/header.php');
		echo '<p>' . _('The report did not have any none zero lines of information to show and so it has not been created');
		echo '<br /><a href="' . $RootPath . '/SalesAnalRepts.php?SelectedReport=' . $_GET['ReportID'] . '">' . _('Look at the design of this report') . '</a>';
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	}
} /* end if we wanted a PDF file */



if ($_GET['ProduceCVSFile']==True){

	include('includes/CSVSalesAnalysis.inc');

	$Title = _('Sales Analysis Comma Separated File (CSV) Generation');
	include('includes/header.php');

	 echo 'http://' . getenv(SERVER_NAME) . $RootPath . '/' . $_SESSION['reports_dir'] .  '/SalesAnalysis.csv';
	 echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/' . $_SESSION['reports_dir'] .  '/SalesAnalysis.csv">';

	 echo '<p>' . _('You should automatically be forwarded to the CSV Sales Analysis file when it is ready') . '. ' . _('If this does not happen') . ' <a href="' . $RootPath . '/' . $_SESSION['reports_dir'] . '/SalesAnalysis.csv">' . _('click here') . '</a> ' . _('to continue') . '<br />';
	 include('includes/footer.php');
}
?>