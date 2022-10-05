<?php

// MRPReport.php - Shows supply and demand for a part as determined by MRP

include('includes/session.php');

if (isset($_POST['Select'])) {
	$_POST['Part']=$_POST['Select'];
	$_POST['PrintPDF']='Yes';
}

if (isset($_POST['PrintPDF']) AND $_POST['Part']!='') {

	include('includes/PDFStarter.php');
	$PDF->addInfo('Title',_('MRP Report'));
	$PDF->addInfo('Subject',_('MRP Report'));
	$FontSize=9;
	$PageNumber=1;
	$line_height=10   ;

	// Load mrprequirements into $Requirements array
	// Use weekindex to assign supplies, requirements, and planned orders to weekly buckets
	$sql = "SELECT mrprequirements.*,
				TRUNCATE(((TO_DAYS(daterequired) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				TO_DAYS(daterequired) - TO_DAYS(CURRENT_DATE) AS datediff
			FROM mrprequirements
			WHERE part = '" . $_POST['Part'] ."'
			ORDER BY daterequired,whererequired";

	$result = DB_query($sql,'','',False,False);
	if (DB_error_no() !=0) {
		$errors = 1;
		$Title = _('Print MRP Report Error');
		include('includes/header.php');
		prnMsg(_('The MRP calculation must be run before this report will have any output. MRP requires set up of many parameters, including, EOQ, lead times, minimums, bills of materials, demand types, master schedule etc'),'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	}

	if (DB_num_rows($result) == 0) {
		$errors = 1;
		$Title = _('Print MRP Report Warning');
		include('includes/header.php');
		prnMsg(_('The MRP calculation must be run before this report will have any output. MRP requires set up of many parameters, including, EOQ, lead times, minimums, bills of materials, demand types, master schedule, etc'), 'warn');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include('includes/footer.php');
		exit;
	}

	$Requirements = array();
	$WeeklyReq = array();
	for ($i=0;$i<28;$i++) {
		$WeeklyReq[$i]=0;
	}
	$PastDueReq = 0;
	$FutureReq = 0;
	$GrossReq = 0;

	while ($myrow=DB_fetch_array($result)) {
			array_push($Requirements,$myrow);
			$GrossReq += $myrow['quantity'];
			if ($myrow['datediff'] < 0) {
				$PastDueReq += $myrow['quantity'];
			} elseif ($myrow['weekindex'] > 27) {
				$FutureReq += $myrow['quantity'];
			} else {
			$WeeklyReq[$myrow['weekindex']] += $myrow['quantity'];
			}
	}  //end of while loop

	// Load mrpsupplies into $Supplies array
	$sql = "SELECT mrpsupplies.*,
				   TRUNCATE(((TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				   TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE) AS datediff
			 FROM mrpsupplies
			 WHERE part = '" . $_POST['Part'] . "'
			 ORDER BY mrpdate";
	$result = DB_query($sql,'','',false,true);
	if (DB_error_no() !=0) {
		$errors = 1;
	}
	$Supplies = array();
	$WeeklySup = array();
	for ($i=0;$i<28;$i++) {
		$WeeklySup[$i]=0;
	}
	$PastDueSup = 0;
	$FutureSup = 0;
	$qoh = 0; // Get quantity on Hand to display
	$OpenOrd = 0;
	while ($myrow=DB_fetch_array($result)) {
		if ($myrow['ordertype'] == 'QOH') {
			$qoh += $myrow['supplyquantity'];
		} else {
			$OpenOrd += $myrow['supplyquantity'];
			if ($myrow['datediff'] < 0) {
				$PastDueSup += $myrow['supplyquantity'];
			} elseif ($myrow['weekindex'] > 27) {
				$FutureSup += $myrow['supplyquantity'];
			} else {
				$WeeklySup[$myrow['weekindex']] += $myrow['supplyquantity'];
			}
		}
		array_push($Supplies,$myrow);
	}  //end of while loop

	$sql = "SELECT mrpplannedorders.*,
				   TRUNCATE(((TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				   TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE) AS datediff
				FROM mrpplannedorders WHERE part = '" . $_POST['Part'] . "' ORDER BY mrpdate";
	$result = DB_query($sql,'','',false,true);
	if (DB_error_no() !=0) {
		$errors = 1;
	}

	// Fields for Order Due weekly buckets based on planned orders
	$weeklyplan = array();
	for ($i=0;$i<28;$i++) {
		$weeklyplan[$i]=0;
	}
	$pastdueplan = 0;
	$futureplan = 0;
	while ($myrow=DB_fetch_array($result)) {
			array_push($Supplies,$myrow);
			if ($myrow['datediff'] < 0) {
				$pastdueplan += $myrow['supplyquantity'];
			} elseif ($myrow['weekindex'] > 27) {
				$futureplan += $myrow['supplyquantity'];
			} else {
			$weeklyplan[$myrow['weekindex']] += $myrow['supplyquantity'];
			}
	}  //end of while loop
	// The following sorts the $Supplies array by mrpdate. Have to sort because are loading
	// mrpsupplies and mrpplannedorders into same array
	foreach ($Supplies as $key => $row) {
			 $mrpdate[$key] = $row['mrpdate'];
	 }

	if (isset($errors)) {
		$Title = _('MRP Report') . ' - ' . _('Problem Report');
		include('includes/header.php');
		prnMsg( _('The MRP Report could not be retrieved by the SQL because') . ' '  . DB_error_msg(),'error');
		echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
		if ($debug==1){
			echo '<br />' . $sql;
		}
		include('includes/footer.php');
		exit;
	}

	if (count($Supplies)) {
		array_multisort($mrpdate, SORT_ASC, $Supplies);
	}
	PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
					   $Right_Margin);

	$fill = false;
	$PDF->SetFillColor(224,235,255);  // Defines color to make alternating lines highlighted

	// Get and display part information
	$sql = "SELECT levels.*,
				   stockmaster.description,
				   stockmaster.lastcost,
				   stockmaster.decimalplaces,
				   stockmaster.mbflag
				   FROM levels
			LEFT JOIN stockmaster
			ON levels.part = stockmaster.stockid
			WHERE part = '" . $_POST['Part'] . "'";
	$result = DB_query($sql,'','',false,true);
	$myrow=DB_fetch_array($result);
	$PDF->addTextWrap($Left_Margin,$YPos,35,$FontSize,_('Part:'),'');
	$PDF->addTextWrap(70,$YPos,100,$FontSize,$myrow['part'],'');
	$PDF->addTextWrap(245,$YPos,40,$FontSize,_('EOQ').':','right');
	$PDF->addTextWrap(285,$YPos,45,$FontSize,locale_number_format($myrow['eoq'],$myrow['decimalplaces']),'right');
	$PDF->addTextWrap(360,$YPos,50,$FontSize,_('On Hand:'),'right');
	$PDF->addTextWrap(410,$YPos,50,$FontSize,locale_number_format($qoh,$myrow['decimalplaces']),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,30,$FontSize,_('Desc:'),'');
	$PDF->addTextWrap(70,$YPos,240,$FontSize,$myrow['description'],'');
	$PDF->addTextWrap(245,$YPos,40,$FontSize,_('Pan Size:'),'right');
	$PDF->addTextWrap(285,$YPos,45,$FontSize,locale_number_format($myrow['pansize'],$myrow['decimalplaces']),'right');
	$PDF->addTextWrap(360,$YPos,50,$FontSize,_('On Order:'),'right');
	$PDF->addTextWrap(410,$YPos,50,$FontSize,locale_number_format($OpenOrd,$myrow['decimalplaces']),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,30,$FontSize,'M/B:','');
	$PDF->addTextWrap(70,$YPos,150,$FontSize,$myrow['mbflag'],'');
	$PDF->addTextWrap(225,$YPos,60,$FontSize,'Shrinkage:','right');
	$PDF->addTextWrap(300,$YPos,30,$FontSize,locale_number_format($myrow['shrinkfactor'],$myrow['decimalplaces']),'right');
	$PDF->addTextWrap(360,$YPos,50,$FontSize,_('Gross Req:'),'right');
	$PDF->addTextWrap(410,$YPos,50,$FontSize,locale_number_format($GrossReq,$myrow['decimalplaces']),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap(225,$YPos,60,$FontSize,'Lead Time:','right');
	$PDF->addTextWrap(300,$YPos,30,$FontSize,$myrow['leadtime'],'right');
	$PDF->addTextWrap(360,$YPos,50,$FontSize,_('Last Cost:'),'right');
	$PDF->addTextWrap(410,$YPos,50,$FontSize,locale_number_format($myrow['lastcost'],2),'right');
	$YPos -= (2*$line_height);

	// Calculate fields for prjected available weekly buckets
	$plannedaccum = array();
	$pastdueavail = ($qoh + $PastDueSup + $pastdueplan) - $PastDueReq;
	$weeklyavail = array();
	$weeklyavail[0] = ($pastdueavail + $WeeklySup[0] + $weeklyplan[0]) - $WeeklyReq[0];
	$plannedaccum[0] = $pastdueplan + $weeklyplan[0];
	for ($i = 1; $i < 28; $i++) {
		 $weeklyavail[$i] = ($weeklyavail[$i - 1] + $WeeklySup[$i] + $weeklyplan[$i]) - $WeeklyReq[$i];
		 $plannedaccum[$i] = $plannedaccum[$i-1] + $weeklyplan[$i];
	}
	$futureavail = ($weeklyavail[27] + $FutureSup + $futureplan) - $FutureReq;
	$futureplannedaccum = $plannedaccum[27] + $futureplan;

	// Headers for Weekly Buckets
	$FontSize =7;
	$dateformat = $_SESSION['DefaultDateFormat'];
	$today = date("$dateformat");
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,_('Past Due'),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,$today,'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,DateAdd($today,'w',1),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,DateAdd($today,'w',2),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,DateAdd($today,'w',3),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,DateAdd($today,'w',4),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,DateAdd($today,'w',5),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,DateAdd($today,'w',6),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,DateAdd($today,'w',7),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,DateAdd($today,'w',8),'right');
	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Gross Reqts'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($PastDueReq,0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklyReq[0],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklyReq[1],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklyReq[2],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklyReq[3],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklyReq[4],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklyReq[5],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklyReq[6],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklyReq[7],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($WeeklyReq[8],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Open Order'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($PastDueSup,0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklySup[0],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklySup[1],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklySup[2],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklySup[3],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklySup[4],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklySup[5],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklySup[6],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklySup[7],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($WeeklySup[8],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($pastdueplan,0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyplan[0],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyplan[1],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyplan[2],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyplan[3],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyplan[4],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyplan[5],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyplan[6],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyplan[7],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($weeklyplan[8],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Proj Avail'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($pastdueavail,0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyavail[0],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyavail[1],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyavail[2],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyavail[3],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyavail[4],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyavail[5],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyavail[6],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyavail[7],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($weeklyavail[8],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned Acc'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($pastdueplan,0),'right');
	$InitialPoint = 130;
	for($c=0;$c<9;$c++){
		$PDF->addTextWrap($InitialPoint,$YPos,45,$FontSize,locale_number_format($plannedaccum[$c],0),'right');
		$InitialPoint += 45;
	}
	$YPos -= 2 * $line_height;

	// Second Group of Weeks
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,DateAdd($today,'w',9),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,DateAdd($today,'w',10),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,DateAdd($today,'w',11),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,DateAdd($today,'w',12),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,DateAdd($today,'w',13),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,DateAdd($today,'w',14),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,DateAdd($today,'w',15),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,DateAdd($today,'w',16),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,DateAdd($today,'w',17),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,DateAdd($today,'w',18),'right');
	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Gross Reqts'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($WeeklyReq[9],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklyReq[10],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklyReq[11],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklyReq[12],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklyReq[13],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklyReq[14],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklyReq[15],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklyReq[16],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklyReq[17],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($WeeklyReq[18],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Open Order'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($WeeklySup[9],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklySup[10],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklySup[11],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklySup[12],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklySup[13],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklySup[14],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklySup[15],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklySup[16],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklySup[17],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($WeeklySup[18],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($weeklyplan[9],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyplan[10],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyplan[11],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyplan[12],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyplan[13],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyplan[14],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyplan[15],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyplan[16],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyplan[17],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($weeklyplan[18],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Proj Avail'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($weeklyavail[9],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyavail[10],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyavail[11],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyavail[12],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyavail[13],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyavail[14],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyavail[15],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyavail[16],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyavail[17],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($weeklyavail[18],0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned Acc'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($plannedaccum[9],0),right);
	$InitialPoint = 130;
	for($c=10;$c<19;$c++){
		$PDF->addTextWrap($InitialPoint,$YPos,45,$FontSize,locale_number_format($plannedaccum[$c],0),'right');
		$InitialPoint += 45;
	}
	$YPos -= 2 * $line_height;

	// Third Group of Weeks
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,DateAdd($today,'w',19),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,DateAdd($today,'w',20),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,DateAdd($today,'w',21),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,DateAdd($today,'w',22),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,DateAdd($today,'w',23),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,DateAdd($today,'w',24),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,DateAdd($today,'w',25),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,DateAdd($today,'w',26),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,DateAdd($today,'w',27),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,"Future",'right');
	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Gross Reqts'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($WeeklyReq[19],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklyReq[20],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklyReq[21],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklyReq[22],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklyReq[23],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklyReq[24],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklyReq[25],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklyReq[26],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklyReq[27],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($FutureReq,0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Open Order'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($WeeklySup[19],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($WeeklySup[20],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($WeeklySup[21],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($WeeklySup[22],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($WeeklySup[23],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($WeeklySup[24],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($WeeklySup[25],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($WeeklySup[26],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($WeeklySup[27],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($FutureSup,0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($weeklyplan[19],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyplan[20],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyplan[21],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyplan[22],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyplan[23],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyplan[24],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyplan[25],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyplan[26],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyplan[27],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($futureplan,0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Proj Avail'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($weeklyavail[19],0),'right');
	$PDF->addTextWrap(130,$YPos,45,$FontSize,locale_number_format($weeklyavail[20],0),'right');
	$PDF->addTextWrap(175,$YPos,45,$FontSize,locale_number_format($weeklyavail[21],0),'right');
	$PDF->addTextWrap(220,$YPos,45,$FontSize,locale_number_format($weeklyavail[22],0),'right');
	$PDF->addTextWrap(265,$YPos,45,$FontSize,locale_number_format($weeklyavail[23],0),'right');
	$PDF->addTextWrap(310,$YPos,45,$FontSize,locale_number_format($weeklyavail[24],0),'right');
	$PDF->addTextWrap(355,$YPos,45,$FontSize,locale_number_format($weeklyavail[25],0),'right');
	$PDF->addTextWrap(400,$YPos,45,$FontSize,locale_number_format($weeklyavail[26],0),'right');
	$PDF->addTextWrap(445,$YPos,45,$FontSize,locale_number_format($weeklyavail[27],0),'right');
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($futureavail,0),'right');
	$YPos -=$line_height;
	$PDF->addTextWrap($Left_Margin,$YPos,40,$FontSize,_('Planned Acc'));
	$PDF->addTextWrap($Left_Margin+40,$YPos,45,$FontSize,locale_number_format($plannedaccum[19],0),'right');
	$InitialPoint = 130;
	for($c=20;$c<28;$c++){
		$PDF->addTextWrap($InitialPoint,$YPos,45,$FontSize,locale_number_format($plannedaccum[$c],0),'right');
		$InitialPoint += 45;
	}
	$PDF->addTextWrap(490,$YPos,45,$FontSize,locale_number_format($futureplannedaccum,0),'right');

	// Headers for Demand/Supply Sections
	$YPos -= (2*$line_height);
	$PDF->addTextWrap($Left_Margin,$YPos,265,$FontSize,'D E M A N D','center');
	$PDF->addTextWrap(290,$YPos,260,$FontSize,'S U P P L Y','center');
	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,55,$FontSize,_('Dem Type'));
	$PDF->addTextWrap(80,$YPos,90,$FontSize,_('Where Required'));
	$PDF->addTextWrap(170,$YPos,30,$FontSize,_('Order'),'');
	$PDF->addTextWrap(200,$YPos,40,$FontSize,_('Quantity'),'right');
	$PDF->addTextWrap(240,$YPos,50,$FontSize,_('Due Date'),'right');

	$PDF->addTextWrap(310,$YPos,45,$FontSize,_('Order No.'),'');
	$PDF->addTextWrap(355,$YPos,35,$FontSize,_('Sup Type'),'');
	$PDF->addTextWrap(390,$YPos,25,$FontSize,_('For'),'');
	$PDF->addTextWrap(415,$YPos,40,$FontSize,_('Quantity'),'right');
	$PDF->addTextWrap(455,$YPos,50,$FontSize,_('Due Date'),'right');
	$PDF->addTextWrap(505,$YPos,50,$FontSize,_('MRP Date'),'right');

	// Details for Demand/Supply Sections
	$i = 0;
	while ((isset($Supplies[$i]) AND mb_strlen($Supplies[$i]['part']) > 1)
			OR (isset($Requirements[$i]) AND mb_strlen($Requirements[$i]['part']) > 1)){

		$YPos -=$line_height;
		$FontSize=7;

		/* Use to alternate between lines with transparent and painted background
		if ($_POST['Fill'] == 'yes'){
			$fill=!$fill;
		}
		*/
		// Parameters for addTextWrap are defined in /includes/class.pdf.php
		// 1) X position 2) Y position 3) Width
		// 4) Height 5) Text To Display  6) Alignment 7) Border 8) Fill - True to use SetFillColor
		// and False to set for transparent
		if (isset($Requirements[$i]['part']) and mb_strlen($Requirements[$i]['part']) > 1) {
			$FormatedReqDueDate = ConvertSQLDate($Requirements[$i]['daterequired']);
			$PDF->addTextWrap($Left_Margin,$YPos,55,$FontSize,$Requirements[$i]['mrpdemandtype'],'');
			$PDF->addTextWrap(80,$YPos,90,$FontSize,$Requirements[$i]['whererequired'],'');
			$PDF->addTextWrap(170,$YPos,30,$FontSize,$Requirements[$i]['orderno'],'');
			$PDF->addTextWrap(200,$YPos,40,$FontSize,locale_number_format($Requirements[$i]['quantity'],
																$myrow['decimalplaces']),'right');
			$PDF->addTextWrap(240,$YPos,50,$FontSize,$FormatedReqDueDate,'right');
		}
		if (mb_strlen($Supplies[$i]['part']) > 1) {
			$suptype = $Supplies[$i]['ordertype'];
			// If ordertype is not QOH,PO,or WO, it is an MRP generated planned order and the
			// ordertype is actually the demandtype that caused the planned order
			if ($suptype == 'QOH' || $suptype == 'PO' || $suptype == 'WO') {
				$displaytype = $suptype;
				$fortype = " ";
			} else {
				$displaytype = 'Planned';
				$fortype = $suptype;
			}
			$FormatedSupDueDate = ConvertSQLDate($Supplies[$i]['duedate']);
			$FormatedSupMRPDate = ConvertSQLDate($Supplies[$i]['mrpdate']);
			// Order no is meaningless for QOH and REORD ordertypes
			if ($suptype == 'QOH' OR $suptype == 'REORD') {
				$PDF->addTextWrap(310,$YPos,45,$FontSize,' ','');
			} else {
				$PDF->addTextWrap(310,$YPos,45,$FontSize,$Supplies[$i]['orderno'],'');
			}
			$PDF->addTextWrap(355,$YPos,35,$FontSize,$displaytype,'');
			$PDF->addTextWrap(390,$YPos,25,$FontSize,$fortype,'');
			$PDF->addTextWrap(415,$YPos,40,$FontSize,locale_number_format($Supplies[$i]['supplyquantity'],$myrow['decimalplaces']),'right');
			$PDF->addTextWrap(455,$YPos,50,$FontSize,$FormatedSupDueDate,'right');
			$PDF->addTextWrap(505,$YPos,50,$FontSize,$FormatedSupMRPDate,'right');
		}

		if ($YPos < $Bottom_Margin + $line_height){
		   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
					   $Right_Margin);
		}
		$i++;
	} /*end while loop */

	$FontSize =8;
	$YPos -= (2*$line_height);

	if ($YPos < $Bottom_Margin + $line_height){
		   PrintHeader($PDF,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
					   $Right_Margin);
	}

	$PDF->OutputD($_SESSION['DatabaseName'] . '_MRPReport_' . date('Y-m-d').'.pdf');//UldisN
	$PDF->__destruct();

} else { /*The option to print PDF was not hit so display form */

	$Title=_('MRP Report');
	include('includes/header.php');

	if (isset($_POST['PrintPDF'])) {
		prnMsg(_('This report shows the MRP calculation for a specific item - a part code must be selected'),'warn');
	}
	// Always show the search facilities
	$SQL = "SELECT categoryid,
					categorydescription
			FROM stockcategory
			ORDER BY categorydescription";
	$result1 = DB_query($SQL);
	if (DB_num_rows($result1) == 0) {
		echo '<p class="bad">' . _('Problem Report') . ':<br />' . _('There are no stock categories currently defined please use the link below to set them up');
		echo '<br /><a href="' . $RootPath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
		exit;
	}

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
		<div>
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . _('Search for Inventory Items') . '</p>
		<table class="selection">
			<tr>
				<td>' . _('In Stock Category') . ':<select name="StockCat">';
	if (!isset($_POST['StockCat'])) {
		$_POST['StockCat'] = '';
	}
	if ($_POST['StockCat'] == 'All') {
		echo '<option selected="selected" value="All">' . _('All') . '</option>';
	} else {
		echo '<option value="All">' . _('All') . '</option>';
	}
	while ($myrow1 = DB_fetch_array($result1)) {
		if ($myrow1['categoryid'] == $_POST['StockCat']) {
			echo '<option selected="selected" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
		} else {
			echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
		}
	}
	echo '</select></td>';
	echo '<td>' . _('Enter partial') . '<b> ' . _('Description') . '</b>:</td><td>';
	if (isset($_POST['Keywords'])) {
		echo '<input type="text" autofocus="autofocus" name="Keywords" value="' . $_POST['Keywords'] . '" size="20" maxlength="25" />';
	} else {
		echo '<input type="text" autofocus="autofocus" name="Keywords" size="20" maxlength="25" />';
	}
	echo '</td>
		</tr>
		<tr>
			<td></td>
			<td><h3><b>' . _('OR') . ' ' . '</b></h3>' . _('Enter partial') . ' <b>' . _('Stock Code') . '</b>:</td>
			<td>';
	if (isset($_POST['StockCode'])) {
		echo '<input type="text" name="StockCode" value="' . $_POST['StockCode'] . '" size="15" maxlength="18" />';
	} else {
		echo '<input type="text" name="StockCode" size="15" maxlength="18" />';
	}
	echo '</td>
		</tr>
		</table>
		<br />
		<div class="centre">
			<input type="submit" name="Search" value="' . _('Search Now') . '" />
		</div>
		<br />
		</div>
		</form>';
	if (!isset($_POST['Search'])) {
		include('includes/footer.php');
	}

} /*end of else not PrintPDF */
// query for list of record(s)
if(isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	$_POST['Search']='Search';
}
if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) {
		// if Search then set to first page
		$_POST['PageOffset'] = 1;
	}
	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg( _('Stock description keywords have been used in preference to the Stock code extract entered'), 'info' );
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				AND stockmaster.description " . LIKE . " '".$SearchString."'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				AND description " . LIKE . " '".$SearchString."'
				AND categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		}
	} elseif (isset($_POST['StockCode'])) {
		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.mbflag,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.mbflag,
					sum(locstock.quantity) as qoh,
					stockmaster.units,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
				AND categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		}
	} elseif (!isset($_POST['StockCode']) AND !isset($_POST['Keywords'])) {
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.mbflag,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
					stockmaster.description,
					stockmaster.mbflag,
					SUM(locstock.quantity) AS qoh,
					stockmaster.units,
					stockmaster.decimalplaces
				FROM stockmaster,
					locstock
				WHERE stockmaster.stockid=locstock.stockid
				AND categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.mbflag,
					stockmaster.decimalplaces
				ORDER BY stockmaster.stockid";
		}
	}
	$ErrMsg = _('No stock items were returned by the SQL because');
	$DbgMsg = _('The SQL that returned an error was');
	$searchresult = DB_query($SQL, $ErrMsg, $DbgMsg);
	if (DB_num_rows($searchresult) == 0) {
		prnMsg(_('No stock items were returned by this search please re-enter alternative criteria to try again'), 'info');
	}
	unset($_POST['Search']);
}
/* end query for list of records */
/* display list if there is more than one record */
if (isset($searchresult) AND !isset($_POST['Select'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$ListCount = DB_num_rows($searchresult);
	if ($ListCount > 0) {
		// If the user hit the search button and there is more than one item to show
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) {
				$_POST['PageOffset'] = $_POST['PageOffset'] + 1;
			}
		}
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) {
				$_POST['PageOffset'] = $_POST['PageOffset'] - 1;
			}
		}
		if ($_POST['PageOffset'] > $ListPageMax) {
			$_POST['PageOffset'] = $ListPageMax;
		}
		if ($ListPageMax > 1) {
			echo '<div class="centre">
					<p>&nbsp;&nbsp;' . $_POST['PageOffset'] . ' ' . _('of') . ' ' . $ListPageMax . ' ' . _('pages') . '. ' . _('Go to Page') . ': ';
			echo '<select name="PageOffset">';
			$ListPage = 1;
			while ($ListPage <= $ListPageMax) {
				if ($ListPage == $_POST['PageOffset']) {
					echo '<option value="' . $ListPage . '" selected="selected">' . $ListPage . '</option>';
				} else {
					echo '<option value="' . $ListPage . '">' . $ListPage . '</option>';
				}
				$ListPage++;
			}
			echo '</select>
				<input type="submit" name="Go" value="' . _('Go') . '" />
				<input type="submit" name="Previous" value="' . _('Previous') . '" />
				<input type="submit" name="Next" value="' . _('Next') . '" />
				<input type="hidden" name="Keywords" value="'.$_POST['Keywords'].'" />
				<input type="hidden" name="StockCat" value="'.$_POST['StockCat'].'" />
				<input type="hidden" name="StockCode" value="'.$_POST['StockCode'].'" />
				</div>';
		}
		echo '<table class="selection">';
		$tableheader = '<tr>
							<th>' . _('Code') . '</th>
							<th>' . _('Description') . '</th>
							<th>' . _('Total Qty On Hand') . '</th>
							<th>' . _('Units') . '</th>
							<th>' . _('Stock Status') . '</th>
						</tr>';
		echo $tableheader;
		$j = 1;
		$RowIndex = 0;
		if (DB_num_rows($searchresult) <> 0) {
			DB_data_seek($searchresult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}
		while (($myrow = DB_fetch_array($searchresult)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			if ($myrow['mbflag'] == 'D') {
				$qoh = 'N/A';
			} else {
				$qoh = locale_number_format($myrow['qoh'], $myrow['decimalplaces']);
			}
			echo '<tr class="striped_row">
				<td><input type="submit" name="Select" value="'.$myrow['stockid']. '" /></td>
				<td>' . $myrow['description'] . '</td>
				<td class="number">' . $qoh . '</td>
				<td>' . $myrow['units'] . '</td>
				<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=' . $myrow['stockid'] .'">' . _('View') . '</a></td>
				</tr>';
			$j++;
			if ($j == 20 AND ($RowIndex + 1 != $_SESSION['DisplayRecordsMax'])) {
				$j = 1;
				echo $tableheader;
			}
			$RowIndex = $RowIndex + 1;
			//end of page full new headings if
		}
		//end of while loop
		echo '</table>
            </div>
			</form>
			<br />';
	}

	include('includes/footer.php');
}
/* end display list if there is more than one record */

function PrintHeader(&$PDF,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
					 $Page_Width,$Right_Margin) {

	$line_height=12;
	/*PDF page header for MRP Report */
	if ($PageNumber>1){
		$PDF->newPage();
	}

	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;

	$PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

	$YPos -=$line_height;

	$PDF->addTextWrap($Left_Margin,$YPos,300,$FontSize,_('MRP Report'));
	$PDF->addTextWrap($Page_Width-$Right_Margin-110,$YPos,160,$FontSize,_('Printed') . ': ' .
		 Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber,'left');

	$YPos -=(2*$line_height);

	/*set up the headings */
	$Xpos = $Left_Margin+1;

	$FontSize=8;
	$YPos =$YPos - (2*$line_height);
	$PageNumber++;

} // End of PrintHeader function

?>
