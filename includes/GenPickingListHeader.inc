<?php
/* pdf-php by R&OS code to set up a new sales order page */
if ($PageNumber > 1) {
	$PDF->newPage();
	$YPos = $FormDesign->Data->y;
}

/* if the deliver blind flag is set on the order, we do not want to output
the company logo */
$PDF->addJpegFromFile($_SESSION['LogoFile'], $FormDesign->logo->x, $Page_Height - $FormDesign->logo->y, $FormDesign->logo->width, $FormDesign->logo->height);
$PDF->setFont('', 'B');
$PDF->addText($FormDesign->Heading->x, $Page_Height - $FormDesign->Heading->y, $FormDesign->Heading->FontSize, _('Pick List-NOT USED FOR SHIPMENT'));
$PDF->setFont('', '');
$PDF->addText($FormDesign->HeadingLine2->x, $Page_Height - $FormDesign->HeadingLine2->y, $FormDesign->HeadingLine2->FontSize, _('From') . ' ' . $OrdersToPick[$i]['locationname'] . ' ' . _('Warehouse'));

/* if the deliver blind flag is set on the order, we do not want to output
the company contact info */
//$PDF->addText($FormDesign->CompanyName->x,$Page_Height - $FormDesign->CompanyName->y, $FormDesign->CompanyName->FontSize, $_SESSION['CompanyRecord']['coyname']);
//$PDF->addText($FormDesign->CompanyAddress->Line1->x,$Page_Height - $FormDesign->CompanyAddress->Line1->y, $FormDesign->CompanyAddress->Line1->FontSize,  $_SESSION['CompanyRecord']['regoffice1']);
//$PDF->addText($FormDesign->CompanyAddress->Line2->x,$Page_Height - $FormDesign->CompanyAddress->Line2->y, $FormDesign->CompanyAddress->Line2->FontSize,  $_SESSION['CompanyRecord']['regoffice2']);
//$PDF->addText($FormDesign->CompanyAddress->Line3->x,$Page_Height - $FormDesign->CompanyAddress->Line3->y, $FormDesign->CompanyAddress->Line3->FontSize,  $_SESSION['CompanyRecord']['regoffice3']);
//$PDF->addText($FormDesign->CompanyAddress->Line4->x,$Page_Height - $FormDesign->CompanyAddress->Line4->y, $FormDesign->CompanyAddress->Line4->FontSize,  $_SESSION['CompanyRecord']['regoffice4']);
//$PDF->addText($FormDesign->CompanyAddress->Line5->x,$Page_Height - $FormDesign->CompanyAddress->Line5->y, $FormDesign->CompanyAddress->Line5->FontSize,  $_SESSION['CompanyRecord']['regoffice5']);
$PDF->addText($FormDesign->CompanyPhone->x, $Page_Height - $FormDesign->CompanyPhone->y, $FormDesign->CompanyPhone->FontSize, _('Tel') . ': ' . $_SESSION['CompanyRecord']['telephone']);
$PDF->addText($FormDesign->CompanyFax->x, $Page_Height - $FormDesign->CompanyFax->y, $FormDesign->CompanyFax->FontSize, _('Fax') . ': ' . $_SESSION['CompanyRecord']['fax']);
$PDF->addText($FormDesign->CompanyEmail->x, $Page_Height - $FormDesign->CompanyEmail->y, $FormDesign->CompanyEmail->FontSize, _('Email') . ': ' . $_SESSION['CompanyRecord']['email']);

/*Now the delivery details */
$PDF->addText($FormDesign->DeliveryAddress->Caption->x, $Page_Height - $FormDesign->DeliveryAddress->Caption->y, $FormDesign->DeliveryAddress->Caption->FontSize, _('Deliver To') . ':');
$PDF->addText($FormDesign->DeliveryAddress->Line1->x, $Page_Height - $FormDesign->DeliveryAddress->Line1->y, $FormDesign->DeliveryAddress->Line1->FontSize, $OrdersToPick[$i]['deladd1']);
$PDF->addText($FormDesign->DeliveryAddress->Line2->x, $Page_Height - $FormDesign->DeliveryAddress->Line2->y, $FormDesign->DeliveryAddress->Line2->FontSize, $OrdersToPick[$i]['deladd2']);
$PDF->addText($FormDesign->DeliveryAddress->Line3->x, $Page_Height - $FormDesign->DeliveryAddress->Line3->y, $FormDesign->DeliveryAddress->Line3->FontSize, $OrdersToPick[$i]['deladd3']);
$PDF->addText($FormDesign->DeliveryAddress->Line4->x, $Page_Height - $FormDesign->DeliveryAddress->Line4->y, $FormDesign->DeliveryAddress->Line4->FontSize, $OrdersToPick[$i]['deladd4']);
$PDF->addText($FormDesign->DeliveryAddress->Line5->x, $Page_Height - $FormDesign->DeliveryAddress->Line5->y, $FormDesign->DeliveryAddress->Line5->FontSize, $OrdersToPick[$i]['deladd5']);

/*Now the customer details */
$PDF->addText($FormDesign->CustomerAddress->Caption->x, $Page_Height - $FormDesign->CustomerAddress->Caption->y, $FormDesign->CustomerAddress->Caption->FontSize, _('Customer') . ':');
$PDF->addText($FormDesign->CustomerAddress->Name->x, $Page_Height - $FormDesign->CustomerAddress->Name->y, $FormDesign->CustomerAddress->Name->FontSize, $OrdersToPick[$i]['name']);
$PDF->addText($FormDesign->CustomerAddress->Line1->x, $Page_Height - $FormDesign->CustomerAddress->Line1->y, $FormDesign->CustomerAddress->Line1->FontSize, $OrdersToPick[$i]['address1']);
$PDF->addText($FormDesign->CustomerAddress->Line2->x, $Page_Height - $FormDesign->CustomerAddress->Line2->y, $FormDesign->CustomerAddress->Line2->FontSize, $OrdersToPick[$i]['address2']);
$PDF->addText($FormDesign->CustomerAddress->Line3->x, $Page_Height - $FormDesign->CustomerAddress->Line3->y, $FormDesign->CustomerAddress->Line3->FontSize, $OrdersToPick[$i]['address3']);
$PDF->addText($FormDesign->CustomerAddress->Line4->x, $Page_Height - $FormDesign->CustomerAddress->Line4->y, $FormDesign->CustomerAddress->Line4->FontSize, $OrdersToPick[$i]['address4']);
$PDF->addText($FormDesign->CustomerAddress->Line5->x, $Page_Height - $FormDesign->CustomerAddress->Line5->y, $FormDesign->CustomerAddress->Line5->FontSize, $OrdersToPick[$i]['address5']);

$PDF->addText($FormDesign->OrderNumberCaption->x, $Page_Height - $FormDesign->OrderNumberCaption->y, $FormDesign->OrderNumberCaption->FontSize, _('Order No') . ':');
$PDF->addText($FormDesign->OrderNumber->x, $Page_Height - $FormDesign->OrderNumber->y, $FormDesign->OrderNumber->FontSize, $OrdersToPick[$i]['orderno']);
$PDF->addText($FormDesign->DeliveryDateCaption->x, $Page_Height - $FormDesign->DeliveryDateCaption->y, $FormDesign->DeliveryDateCaption->FontSize, _('Delivery Date') . ':');
$PDF->addText($FormDesign->DeliveryDate->x, $Page_Height - $FormDesign->DeliveryDate->y, $FormDesign->DeliveryDate->FontSize, ConvertSQLDate($OrdersToPick[$i]['deliverydate']));
$PDF->addText($FormDesign->DatePrintedCaption->x, $Page_Height - $FormDesign->DatePrintedCaption->y, $FormDesign->DatePrintedCaption->FontSize, _('Printed') . ': ');
$PDF->addText($FormDesign->DatePrinted->x, $Page_Height - $FormDesign->DatePrinted->y, $FormDesign->DatePrinted->FontSize, Date($_SESSION['DefaultDateFormat']));

$PDF->addText($FormDesign->CustomerCode->x, $Page_Height - $FormDesign->CustomerCode->y, $FormDesign->CustomerCode->FontSize, _('Customer No.') . ' : ' . $OrdersToPick[$i]['debtorno']);
$PDF->addText($FormDesign->ShipperCode->x, $Page_Height - $FormDesign->ShipperCode->y, $FormDesign->ShipperCode->FontSize, _('Shipped by') . ' : ' . $OrdersToPick[$i]['shippername']);
$PDF->addText($FormDesign->PickList->x, $Page_Height - $FormDesign->PickList->y, $FormDesign->PickList->FontSize, _('Pick List') . ' :     ' . str_pad($Count[1], 10, '0', STR_PAD_LEFT));

$PDF->setFont('', 'B');
$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, stripcslashes($Count[2]));

if (mb_strlen($LeftOvers) > 1) {
	$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y - 13, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, $LeftOvers);
	if (mb_strlen($LeftOvers) > 1) {
		$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y - 26, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, $LeftOvers);
		if (mb_strlen($LeftOvers) > 1) {
			$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y - 39, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, $LeftOvers);
			if (mb_strlen($LeftOvers) > 1) {
				$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y - 52, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, $LeftOvers);
				if (mb_strlen($LeftOvers) > 1) {
					$LeftOvers = $PDF->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y - 65, $FormDesign->Comments->Length, $FormDesign->Comments->FontSize, $LeftOvers);
				}
			}
		}
	}
}
$PDF->setFont('', '');

$PDF->addText($FormDesign->PageNumberCaption->x, $Page_Height - $FormDesign->PageNumberCaption->y, $FormDesign->PageNumberCaption->FontSize, _('Page') . ':');
$PDF->addText($FormDesign->PageNumber->x, $Page_Height - $FormDesign->PageNumber->y, $FormDesign->PageNumber->FontSize, $PageNumber);
$PDF->setFont('', 'B');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column1->x, $Page_Height - $FormDesign->Headings->Column1->y, $FormDesign->Headings->Column1->Length, $FormDesign->Headings->Column1->FontSize, _('Item Code'), 'left');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column2->x, $Page_Height - $FormDesign->Headings->Column2->y, $FormDesign->Headings->Column2->Length, $FormDesign->Headings->Column2->FontSize, _('Item Description'), 'left');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column3->x, $Page_Height - $FormDesign->Headings->Column3->y, $FormDesign->Headings->Column3->Length, $FormDesign->Headings->Column3->FontSize, _('Bin Location'), 'right');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column4->x, $Page_Height - $FormDesign->Headings->Column4->y, $FormDesign->Headings->Column4->Length, $FormDesign->Headings->Column4->FontSize, _('Open Quantity'), 'right');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column5->x, $Page_Height - $FormDesign->Headings->Column5->y, $FormDesign->Headings->Column5->Length, $FormDesign->Headings->Column5->FontSize, _('Available'), 'right');
$LeftOvers = $PDF->addTextWrap($FormDesign->Headings->Column6->x, $Page_Height - $FormDesign->Headings->Column6->y, $FormDesign->Headings->Column6->Length, $FormDesign->Headings->Column6->FontSize, _('Actual Picked'), 'right');
$PDF->setFont('', '');
$PDF->RoundRectangle($FormDesign->DeliveryAddressBox->x, $Page_Height - $FormDesign->DeliveryAddressBox->y, $FormDesign->DeliveryAddressBox->width, $FormDesign->DeliveryAddressBox->height, $FormDesign->DeliveryAddressBox->radius, $FormDesign->DeliveryAddressBox->radius); // Function RoundRectangle from includes/class.pdf.php
$PDF->RoundRectangle($FormDesign->CustomerAddressBox->x, $Page_Height - $FormDesign->CustomerAddressBox->y, $FormDesign->CustomerAddressBox->width, $FormDesign->CustomerAddressBox->height, $FormDesign->CustomerAddressBox->radius, $FormDesign->CustomerAddressBox->radius); // Function RoundRectangle from includes/class.pdf.php
$PDF->RoundRectangle($FormDesign->DataBox->x, $Page_Height - $FormDesign->DataBox->y, $FormDesign->DataBox->width, $FormDesign->DataBox->height, $FormDesign->DataBox->radius, $FormDesign->DataBox->radius); // Function RoundRectangle from includes/class.pdf.php

$PDF->line($FormDesign->LineBelowColumns->startx, $Page_Height - $FormDesign->LineBelowColumns->starty, $FormDesign->LineBelowColumns->endx, $Page_Height - $FormDesign->LineBelowColumns->endy);
$PDF->line($FormDesign->ColumnLine1->startx, $Page_Height - $FormDesign->ColumnLine1->starty, $FormDesign->ColumnLine1->endx, $Page_Height - $FormDesign->ColumnLine1->endy);
$PDF->line($FormDesign->ColumnLine2->startx, $Page_Height - $FormDesign->ColumnLine2->starty, $FormDesign->ColumnLine2->endx, $Page_Height - $FormDesign->ColumnLine2->endy);
$PDF->line($FormDesign->ColumnLine3->startx, $Page_Height - $FormDesign->ColumnLine3->starty, $FormDesign->ColumnLine3->endx, $Page_Height - $FormDesign->ColumnLine3->endy);
$PDF->line($FormDesign->ColumnLine4->startx, $Page_Height - $FormDesign->ColumnLine4->starty, $FormDesign->ColumnLine4->endx, $Page_Height - $FormDesign->ColumnLine4->endy);
$PDF->line($FormDesign->ColumnLine5->startx, $Page_Height - $FormDesign->ColumnLine5->starty, $FormDesign->ColumnLine5->endx, $Page_Height - $FormDesign->ColumnLine5->endy);
$PDF->setFont('', 'B');
$PDF->addText($FormDesign->DeliveryAddressBox->x, $Page_Height - $FormDesign->ColumnLine5->endy, $FormDesign->Heading->FontSize, _('NOT A PACKING LIST.      DO NOT USE FOR SHIPMENT.      DRIVER DO NOT ACCEPT'));
$PDF->setFont('', '');
?>