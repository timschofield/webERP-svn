<?php
//============================================================+
// File name   : example_022.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 022 for TCPDF class
//               CMYK colors
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
 * @abstract TCPDF - Example: CMYK colors.
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
$PDF->SetTitle('TCPDF Example 022');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 022', PDF_HEADER_STRING);

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
	

	// check also the following methods:
	// SetDrawColorArray()
	// SetFillColorArray()
	// SetTextColorArray()
	

	// set font
	$PDF->SetFont('helvetica', 'B', 18);

	// add a page
	$PDF->AddPage();

	$PDF->Write(0, 'Example of CMYK, RGB and Grayscale colours', '', 0, 'L', true, 0, false, false, 0);

	// define style for border
	$border_style = array('all' => array('width' => 2, 'cap' => 'square', 'join' => 'miter', 'dash' => 0, 'phase' => 0));

	// --- CMYK ------------------------------------------------
	

	$PDF->SetDrawColor(50, 0, 0, 0);
	$PDF->SetFillColor(100, 0, 0, 0);
	$PDF->SetTextColor(100, 0, 0, 0);
	$PDF->Rect(30, 60, 30, 30, 'DF', $border_style);
	$PDF->Text(30, 92, 'Cyan');

	$PDF->SetDrawColor(0, 50, 0, 0);
	$PDF->SetFillColor(0, 100, 0, 0);
	$PDF->SetTextColor(0, 100, 0, 0);
	$PDF->Rect(70, 60, 30, 30, 'DF', $border_style);
	$PDF->Text(70, 92, 'Magenta');

	$PDF->SetDrawColor(0, 0, 50, 0);
	$PDF->SetFillColor(0, 0, 100, 0);
	$PDF->SetTextColor(0, 0, 100, 0);
	$PDF->Rect(110, 60, 30, 30, 'DF', $border_style);
	$PDF->Text(110, 92, 'Yellow');

	$PDF->SetDrawColor(0, 0, 0, 50);
	$PDF->SetFillColor(0, 0, 0, 100);
	$PDF->SetTextColor(0, 0, 0, 100);
	$PDF->Rect(150, 60, 30, 30, 'DF', $border_style);
	$PDF->Text(150, 92, 'Black');

	// --- RGB -------------------------------------------------
	

	$PDF->SetDrawColor(255, 127, 127);
	$PDF->SetFillColor(255, 0, 0);
	$PDF->SetTextColor(255, 0, 0);
	$PDF->Rect(30, 110, 30, 30, 'DF', $border_style);
	$PDF->Text(30, 142, 'Red');

	$PDF->SetDrawColor(127, 255, 127);
	$PDF->SetFillColor(0, 255, 0);
	$PDF->SetTextColor(0, 255, 0);
	$PDF->Rect(70, 110, 30, 30, 'DF', $border_style);
	$PDF->Text(70, 142, 'Green');

	$PDF->SetDrawColor(127, 127, 255);
	$PDF->SetFillColor(0, 0, 255);
	$PDF->SetTextColor(0, 0, 255);
	$PDF->Rect(110, 110, 30, 30, 'DF', $border_style);
	$PDF->Text(110, 142, 'Blue');

	// --- GRAY ------------------------------------------------
	

	$PDF->SetDrawColor(191);
	$PDF->SetFillColor(127);
	$PDF->SetTextColor(127);
	$PDF->Rect(30, 160, 30, 30, 'DF', $border_style);
	$PDF->Text(30, 192, 'Gray');

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_022.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	