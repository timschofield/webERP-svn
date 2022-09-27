<?php
//============================================================+
// File name   : example_013.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 013 for TCPDF class
//               Graphic Transformations
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+



/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Graphic Transformations
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 013');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 013', PDF_HEADER_STRING);

// set header and footer fonts
$PDF->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$PDF->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$PDF->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$PDF->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$PDF->SetHeaderMargin(PDF_MARGIN_HEADER);
$PDF->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$PDF->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// set image scale factor
$PDF->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
	require_once (dirname(__FILE__) . '/lang/eng.php');
	$PDF->setLanguageArray($l);
	}

	// ---------------------------------------------------------
	

	// set font
	$PDF->SetFont('helvetica', 'B', 20);

	// add a page
	$PDF->AddPage();

	$PDF->Write(0, 'Graphic Transformations', '', 0, 'C', 1, 0, false, false, 0);

	// set font
	$PDF->SetFont('helvetica', '', 10);

	// --- Scaling ---------------------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(50, 70, 40, 10, 'D');
	$PDF->Text(50, 66, 'Scale');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// Scale by 150% centered by (50,80) which is the lower left corner of the rectangle
	$PDF->ScaleXY(150, 50, 80);
	$PDF->Rect(50, 70, 40, 10, 'D');
	$PDF->Text(50, 66, 'Scale');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Translation -----------------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(125, 70, 40, 10, 'D');
	$PDF->Text(125, 66, 'Translate');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// Translate 7 to the right, 5 to the bottom
	$PDF->Translate(7, 5);
	$PDF->Rect(125, 70, 40, 10, 'D');
	$PDF->Text(125, 66, 'Translate');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Rotation --------------------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(70, 100, 40, 10, 'D');
	$PDF->Text(70, 96, 'Rotate');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// Rotate 20 degrees counter-clockwise centered by (70,110) which is the lower left corner of the rectangle
	$PDF->Rotate(20, 70, 110);
	$PDF->Rect(70, 100, 40, 10, 'D');
	$PDF->Text(70, 96, 'Rotate');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Skewing ---------------------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(125, 100, 40, 10, 'D');
	$PDF->Text(125, 96, 'Skew');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// skew 30 degrees along the x-axis centered by (125,110) which is the lower left corner of the rectangle
	$PDF->SkewX(30, 125, 110);
	$PDF->Rect(125, 100, 40, 10, 'D');
	$PDF->Text(125, 96, 'Skew');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Mirroring horizontally ------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(70, 130, 40, 10, 'D');
	$PDF->Text(70, 126, 'MirrorH');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// mirror horizontally with axis of reflection at x-position 70 (left side of the rectangle)
	$PDF->MirrorH(70);
	$PDF->Rect(70, 130, 40, 10, 'D');
	$PDF->Text(70, 126, 'MirrorH');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Mirroring vertically --------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(125, 130, 40, 10, 'D');
	$PDF->Text(125, 126, 'MirrorV');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// mirror vertically with axis of reflection at y-position 140 (bottom side of the rectangle)
	$PDF->MirrorV(140);
	$PDF->Rect(125, 130, 40, 10, 'D');
	$PDF->Text(125, 126, 'MirrorV');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Point reflection ------------------------------------
	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(70, 160, 40, 10, 'D');
	$PDF->Text(70, 156, 'MirrorP');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	// Start Transformation
	$PDF->StartTransform();
	// point reflection at the lower left point of rectangle
	$PDF->MirrorP(70, 170);
	$PDF->Rect(70, 160, 40, 10, 'D');
	$PDF->Text(70, 156, 'MirrorP');
	// Stop Transformation
	$PDF->StopTransform();

	// --- Mirroring against a straigth line described by a point (120, 120) and an angle -20°
	$angle = - 20;
	$px = 120;
	$py = 170;

	// just for visualisation: the straight line to mirror against
	

	$PDF->SetDrawColor(200);
	$PDF->Line($px - 1, $py - 1, $px + 1, $py + 1);
	$PDF->Line($px - 1, $py + 1, $px + 1, $py - 1);
	$PDF->StartTransform();
	$PDF->Rotate($angle, $px, $py);
	$PDF->Line($px - 5, $py, $px + 60, $py);
	$PDF->StopTransform();

	$PDF->SetDrawColor(200);
	$PDF->SetTextColor(200);
	$PDF->Rect(125, 160, 40, 10, 'D');
	$PDF->Text(125, 156, 'MirrorL');
	$PDF->SetDrawColor(0);
	$PDF->SetTextColor(0);
	//Start Transformation
	$PDF->StartTransform();
	//mirror against the straight line
	$PDF->MirrorL($angle, $px, $py);
	$PDF->Rect(125, 160, 40, 10, 'D');
	$PDF->Text(125, 156, 'MirrorL');
	//Stop Transformation
	$PDF->StopTransform();

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_013.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	