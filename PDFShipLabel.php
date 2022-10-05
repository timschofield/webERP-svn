<?php
/* Prints a ship label */

include('includes/session.php');

if (isset($_GET['ORD'])) {
	$SelectedORD = $_GET['ORD'];
} elseif (isset($_POST['ORD'])) {
	$SelectedORD = $_POST['ORD'];
} else {
	unset($SelectedORD);
}

if (isset($_GET['StockID'])) {
	$StockId = $_GET['StockID'];
} elseif (isset($_POST['StockID'])) {
	$StockId = $_POST['StockID'];
} else {
	unset($StockId);
}


if (isset($_GET['LabelItem'])) {
	$LabelItem = $_GET['LabelItem'];
} elseif (isset($_POST['LabelItem'])) {
	$LabelItem = $_POST['LabelItem'];
} else {
	unset($LabelItem);
}

if (isset($_GET['LabelDesc'])) {
	$LabelDesc = $_GET['LabelDesc'];
} elseif (isset($_POST['LabelDesc'])) {
	$LabelDesc = $_POST['LabelDesc'];
} else {
	unset($LabelDesc);
}

if (isset($_GET['LabelLot'])) {
	$LabelLot = $_GET['LabelLot'];
} elseif (isset($_POST['LabelLot'])) {
	$LabelLot = $_POST['LabelLot'];
} else {
	unset($LabelLot);
}

if (isset($_GET['NoOfBoxes'])) {
	$NoOfBoxes = $_GET['NoOfBoxes'];
} elseif (isset($_POST['NoOfBoxes'])) {
	$NoOfBoxes = $_POST['NoOfBoxes'];
} else {
	unset($NoOfBoxes);
}

if (isset($_GET['LabelsPerBox'])) {
	$LabelsPerBox = $_GET['LabelsPerBox'];
} elseif (isset($_POST['LabelsPerBox'])) {
	$LabelsPerBox = $_POST['LabelsPerBox'];
} else {
	unset($LabelsPerBox);
}

if (isset($_GET['QtyPerBox'])) {
	$QtyPerBox = $_GET['QtyPerBox'];
} elseif (isset($_POST['QtyPerBox'])) {
	$QtyPerBox = $_POST['QtyPerBox'];
} else {
	unset($QtyPerBox);
}

if (isset($_GET['LeftOverQty'])) {
	$LeftOverQty = $_GET['LeftOverQty'];
} elseif (isset($_POST['LeftOverQty'])) {
	$LeftOverQty = $_POST['LeftOverQty'];
} else {
	unset($LeftOverQty);
}

if (isset($_GET['Type'])) {
	$Type = $_GET['Type'];
} elseif (isset($_POST['Type'])) {
	$Type = $_POST['Type'];
} else {
	unset($Type);
}

/* If we are previewing the order then we dont want to email it */
if ($SelectedORD == 'Preview') { //ORD is set to 'Preview' when just looking at the format of the printed order
	$_POST['PrintOrEmail'] = 'Print';
	$MakePDFThenDisplayIt = True;
} //$SelectedORD == 'Preview'

if (isset($_POST['PrintOrEmail']) and $_POST['PrintOrEmail'] == 'Print') {
	$MakePDFThenDisplayIt = True;
	$MakePDFThenEmailIt = False;
} elseif (isset($_POST['PrintOrEmail']) and $_POST['PrintOrEmail'] == 'Email' and isset($_POST['EmailTo'])) {
	$MakePDFThenEmailIt = True;
	$MakePDFThenDisplayIt = False;
} else {
	$MakePDFThenEmailIt = False;
	$MakePDFThenDisplayIt = True;
}

$FormDesign = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/ShippingLabel.xml');

// Set the paper size/orintation
$PaperSize = $FormDesign->PaperSize;
$line_height = $FormDesign->LineHeight;
include('includes/PDFStarter.php');
$PageNumber = 1;
$PDF->addInfo('Title', _('FG Label'));

if ($SelectedORD == 'Preview') {
	$NoOfLabels = 1;
	$MyArray[1]['deliverto'] = str_pad('', 30, 'x');
	$MyArray[1]['deladd1'] = str_pad('', 30, 'x');
	$MyArray[1]['deladd2'] = str_pad('', 50, 'x');
	$MyArray[1]['deladd3'] = str_pad('', 50, 'x');
	$MyArray[1]['deladd4'] = str_pad('', 50, 'x');
	$MyArray[1]['deladd5'] = str_pad('', 50, 'x');
	$MyArray[1]['deladd6'] = str_pad('', 50, 'x');
	$MyArray[1]['contactphone'] = '+1 987-654-3210';
	$MyArray[1]['customerref'] = str_pad('', 30, 'x');
	$MyArray[1]['stockid'] = str_pad('', 30, 'x');
	$MyArray[1]['custitem'] = str_pad('', 30, 'x');

} else { //NOT PREVIEW
	if ($Type == "Sales") {
		$OrderHeaderSQL = "SELECT debtorsmaster.name as deliverto,
								salesorderdetails.quantity as shipqty,
								salesorderdetails.stkcode as stockid,
								salesorders.branchcode,
								salesorders.customerref,
								salesorders.deliverto,
								salesorders.deladd1,
								salesorders.deladd2,
								salesorders.deladd3,
								salesorders.deladd4,
								salesorders.deladd5,
								salesorders.deladd6,
								salesorders.contactphone,
								stockmaster.decimalplaces,
								custitem.cust_part,
								custitem.cust_description
						FROM salesorderdetails
						INNER JOIN stockmaster
							ON stockmaster.stockid = stkcode
						INNER JOIN salesorders
							ON salesorders.orderno=salesorderdetails.orderno
						INNER JOIN debtorsmaster
							ON salesorders.debtorno = debtorsmaster.debtorno
						INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
						LEFT OUTER JOIN custitem
							ON custitem.debtorno=debtorsmaster.debtorno
							AND custitem.stockid=salesorderdetails.stkcode
						WHERE salesorders.orderno = '" . $SelectedORD . "'";
	} else {
		$OrderHeaderSQL = "SELECT loctransfers.reference as customerref,
								loctransfers.stockid,
								stockmaster.description,
								loctransfers.shipqty,
								loctransfers.shiploc,
								locations.locationname as shiplocname,
								loctransfers.recloc,
								locationsrec.contact as deliverto,
								locationsrec.deladd1,
								locationsrec.deladd2,
								locationsrec.deladd3,
								locationsrec.deladd4,
								locationsrec.deladd5,
								locationsrec.deladd6,
								locationsrec.tel as contactphone,
								stockmaster.decimalplaces
							FROM loctransfers
							INNER JOIN stockmaster
								ON loctransfers.stockid=stockmaster.stockid
							INNER JOIN locations
								ON loctransfers.shiploc=locations.loccode
							INNER JOIN locations AS locationsrec
								ON loctransfers.recloc = locationsrec.loccode
							WHERE loctransfers.reference='" . $SelectedORD . "'";
	}
	//echo $OrderHeaderSQL;
	$ErrMsg = _('The order cannot be retrieved because');
	$DbgMsg = _('The SQL that failed to get the order header was');
	$GetOrdHdrResult = DB_query($OrderHeaderSQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($GetOrdHdrResult) > 0) {
		$BoxNumber = 1;
		$LabelsPerBox = 1;
		$NoOfLabels = 1;
		while ($MyRow = DB_fetch_array($GetOrdHdrResult)) {
			$i = 1;
			$QtyPerBox = 0;
			$SQL = "SELECT value
					FROM stockitemproperties
					INNER JOIN stockcatproperties
						ON stockcatproperties.stkcatpropid=stockitemproperties.stkcatpropid
					WHERE stockid='" . $MyRow['stockid'] . "'
						AND label='PackQty'";
			$Result = DB_query($SQL, $ErrMsg);
			$PackQtyArray = DB_fetch_array($Result);
			$QtyPerBox = $PackQtyArray['value'];
			if ($QtyPerBox == 0) {
				$QtyPerBox = 1;
			}
			$NoOfBoxes = (int) ($MyRow['shipqty'] / $QtyPerBox);
			$LeftOverQty = $MyRow['shipqty'] % $QtyPerBox;
			$NoOfLabelsLine = $LabelsPerBox * $NoOfBoxes;
			$QtyPerBox = locale_number_format($QtyPerBox, $MyRow['decimalplaces']);
			$LeftOverQty = locale_number_format($LeftOverQty, $MyRow['decimalplaces']);

			//echo $MyRow['stockid'] . ' ' .$MyRow['deliverto'] . ' ' .$MyRow['deladd1'] . ' ' . $MyRow['shipqty'].' ' .$QtyPerBox.' ' .$NoOfLabelsLine.' ' .$LeftOverQty.'<br>';
			while ($i <= $NoOfLabelsLine) {
				$MyArray[$NoOfLabels]['deliverto'] = $MyRow['deliverto'];
				$MyArray[$NoOfLabels]['deladd1'] = $MyRow['deladd1'];
				$MyArray[$NoOfLabels]['deladd2'] = $MyRow['deladd2'];
				$MyArray[$NoOfLabels]['deladd3'] = $MyRow['deladd3'];
				$MyArray[$NoOfLabels]['deladd4'] = $MyRow['deladd4'];
				$MyArray[$NoOfLabels]['deladd5'] = $MyRow['deladd5'];
				$MyArray[$NoOfLabels]['deladd6'] = $MyRow['deladd6'];
				$MyArray[$NoOfLabels]['deladd6'] = $MyRow['deladd6'];
				$MyArray[$NoOfLabels]['customerref'] = $MyRow['customerref'];
				$MyArray[$NoOfLabels]['stockid'] = $MyRow['stockid'];
				$MyArray[$NoOfLabels]['custitem'] = $MyRow['cust_part'] . ' ' . $MyRow['cust_description'];
				++$i;
				++$NoOfLabels;
			}
			if ($LeftOverQty > 0) {
				$j = 1;
				while ($j <= $LabelsPerBox) {
					$MyArray[$NoOfLabels]['deliverto'] = $MyRow['deliverto'];
					$MyArray[$NoOfLabels]['deladd1'] = $MyRow['deladd1'];
					$MyArray[$NoOfLabels]['deladd2'] = $MyRow['deladd2'];
					$MyArray[$NoOfLabels]['deladd3'] = $MyRow['deladd3'];
					$MyArray[$NoOfLabels]['deladd4'] = $MyRow['deladd4'];
					$MyArray[$NoOfLabels]['deladd5'] = $MyRow['deladd5'];
					$MyArray[$NoOfLabels]['deladd6'] = $MyRow['deladd6'];
					$MyArray[$NoOfLabels]['deladd6'] = $MyRow['deladd6'];
					$MyArray[$NoOfLabels]['customerref'] = $MyRow['customerref'];
					$MyArray[$NoOfLabels]['stockid'] = $MyRow['stockid'];
					$MyArray[$NoOfLabels]['custitem'] = $MyRow['cust_part'] . ' ' . $MyRow['cust_description'];
					++$i;
					++$j;
					//$NoOfLabels++;
				}
			}
		}
	} // get data to print
} //
//echo $LeftOverQty . ' ' . $NoOfLabels ;
if ($NoOfLabels > 0) {
	for ($i = 1; $i < $NoOfLabels; $i++) {
		if ($PageNumber > 1) {
			$PDF->newPage();
		}
		$PageNumber++;
		$AddressLine = 0;
		$PDF->addJpegFromFile($_SESSION['LogoFile'], $FormDesign->logo->x, $Page_Height - $FormDesign->logo->y, $FormDesign->logo->width, $FormDesign->logo->height);
		$PDF->setFont('', 'B');
		$PDF->addText($FormDesign->CompanyAddress->CompanyLabel->x, $Page_Height - $FormDesign->CompanyAddress->CompanyLabel->y, $FormDesign->CompanyAddress->CompanyLabel->FontSize, _('Ship From') . ': ');
		$PDF->setFont('', '');
		$PDF->addText($FormDesign->CompanyAddress->CompanyName->x, $Page_Height - $FormDesign->CompanyAddress->CompanyName->y, $FormDesign->CompanyAddress->CompanyName->FontSize, $_SESSION['CompanyRecord']['coyname']);
		$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice1']);
		++$AddressLine;
		if ($_SESSION['CompanyRecord']['regoffice2'] > '') {
			$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice2']);
			++$AddressLine;
		}
		if ($_SESSION['CompanyRecord']['regoffice3'] > '') {
			$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice3']);
			++$AddressLine;
		}
		if ($_SESSION['CompanyRecord']['regoffice4'] > '') {
			$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice4']);
			++$AddressLine;
		}
		if ($_SESSION['CompanyRecord']['regoffice5'] > '') {
			$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice5']);
			++$AddressLine;
		}
		if ($_SESSION['CompanyRecord']['regoffice6'] > '') {
			$PDF->addText($FormDesign->CompanyAddress->Address->x, $Page_Height - $FormDesign->CompanyAddress->Address->y - ($AddressLine * $FormDesign->CompanyAddress->Address->FontSize), $FormDesign->CompanyAddress->Address->FontSize, $_SESSION['CompanyRecord']['regoffice6']);
			++$AddressLine;
		}
		$PDF->Line($FormDesign->LabelLine->startx, $Page_Height - $FormDesign->LabelLine->starty, $FormDesign->LabelLine->endx, $Page_Height - $FormDesign->LabelLine->endy);
		$PDF->setFont('', 'B');
		$PDF->addText($FormDesign->DeliveryAddress->DelLabel->x, $Page_Height - $FormDesign->DeliveryAddress->DelLabel->y, $FormDesign->DeliveryAddress->DelLabel->FontSize, _('Ship To') . ': ');
		$PDF->setFont('', '');
		$PDF->addText($FormDesign->DeliveryAddress->DelName->x, $Page_Height - $FormDesign->DeliveryAddress->DelName->y, $FormDesign->DeliveryAddress->DelName->FontSize, $MyArray[$i]['deliverto']);
		$AddressLine = 0;
		$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd1']);
		++$AddressLine;
		if ($MyArray[$i]['deladd2'] > '') {
			$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd2']);
			++$AddressLine;
		}
		if ($MyArray[$i]['deladd3'] > '') {
			$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd3']);
			++$AddressLine;
		}
		if ($MyArray[$i]['deladd4'] > '') {
			$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd4']);
			++$AddressLine;
		}
		if ($MyArray[$i]['deladd5'] > '') {
			$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd5']);
			++$AddressLine;
		}
		if ($MyArray[$i]['deladd6'] > '') {
			$PDF->addText($FormDesign->DeliveryAddress->DelAddress->x, $Page_Height - $FormDesign->DeliveryAddress->DelAddress->y - ($AddressLine * $FormDesign->DeliveryAddress->DelAddress->FontSize), $FormDesign->DeliveryAddress->DelAddress->FontSize, $MyArray[$i]['deladd6']);
			++$AddressLine;
		}

		$PDF->addText($FormDesign->PONbr->x, $Page_Height - $FormDesign->PONbr->y, $FormDesign->PONbr->FontSize, _('Order') . ': ' . $MyArray[$i]['customerref']);
		$PDF->addText($FormDesign->ItemNbr->x, $Page_Height - $FormDesign->ItemNbr->y, $FormDesign->ItemNbr->FontSize, _('Item') . ': ' . $MyArray[$i]['stockid']);
		$PDF->addText($FormDesign->CustItem->x, $Page_Height - $FormDesign->CustItem->y, $FormDesign->CustItem->FontSize, _('Customer Item') . ': ' . $MyArray[$i]['custitem']);

	} //end of loop labels

	$Success = 1; //assume the best and email goes - has to be set to 1 to allow update status
	if ($MakePDFThenDisplayIt) {
		$PDF->OutputD($_SESSION['DatabaseName'] . '_FGLABEL_' . $SelectedORD . '_' . date('Y-m-d') . '.pdf');
		$PDF->__destruct();
	} else {
		$PdfFileName = $_SESSION['DatabaseName'] . '__FGLABEL_' . $SelectedORD . '_' . date('Y-m-d') . '.pdf';
		$PDF->Output($_SESSION['reports_dir'] . '/' . $PdfFileName, 'F');
		$PDF->__destruct();
		include('includes/htmlMimeMail.php');
		$Mail = new htmlMimeMail();
		$Attachment = $Mail->getFile($_SESSION['reports_dir'] . '/' . $PdfFileName);
		$Mail->setText(_('Please Process this Work order number') . ' ' . $SelectedORD);
		$Mail->setSubject(_('Work Order Number') . ' ' . $SelectedORD);
		$Mail->addAttachment($Attachment, $PdfFileName, 'application/pdf');
		//since sometime the mail server required to verify the users, so must set this information.
		if ($_SESSION['SmtpSetting'] == 0) { //use the mail service provice by the server.
			$Mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] . '>');
			$Success = $Mail->send(array(
				$_POST['EmailTo']
			));
		} else if ($_SESSION['SmtpSetting'] == 1) {
			$Success = SendmailBySmtp($Mail, array(
				$_POST['EmailTo']
			));

		} else {
			prnMsg(_('The SMTP settings are wrong, please ask administrator for help'), 'error');
			include('includes/footer.php');
			exit;
		}

		if ($Success == 1) {
			$Title = _('Email a Work Order');
			include('includes/header.php');
			prnMsg(_('Work Order') . ' ' . $SelectedORD . ' ' . _('has been emailed to') . ' ' . $_POST['EmailTo'] . ' ' . _('as directed'), 'success');

		} else { //email failed
			$Title = _('Email a Work Order');
			include('includes/header.php');
			prnMsg(_('Emailing Work order') . ' ' . $SelectedORD . ' ' . _('to') . ' ' . $_POST['EmailTo'] . ' ' . _('failed'), 'error');
		}
	}
	include('includes/footer.php');

} else { //there were not labels to print
	$Title = _('Label Error');
	include('includes/header.php');
	prnMsg(_('There were no labels to print'), 'warn');
	echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
	include('includes/footer.php');
}
?>
