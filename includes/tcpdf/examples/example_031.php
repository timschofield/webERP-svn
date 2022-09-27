<?php
//============================================================+
// File name   : example_031.php
// Begin       : 2008-06-09
// Last Update : 2013-05-14
//
// Description : Example 031 for TCPDF class
//               Pie Chart
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
 * @abstract TCPDF - Example: Pie Chart
 * @author Nicola Asuni
 * @since 2008-06-09
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 031');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 031', PDF_HEADER_STRING);

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

	$PDF->Write(0, 'Example of PieSector() method.');

	$xc = 105;
	$yc = 100;
	$r = 50;

	$PDF->SetFillColor(0, 0, 255);
	$PDF->PieSector($xc, $yc, $r, 20, 120, 'FD', false, 0, 2);

	$PDF->SetFillColor(0, 255, 0);
	$PDF->PieSector($xc, $yc, $r, 120, 250, 'FD', false, 0, 2);

	$PDF->SetFillColor(255, 0, 0);
	$PDF->PieSector($xc, $yc, $r, 250, 20, 'FD', false, 0, 2);

	// write labels
	$PDF->SetTextColor(255, 255, 255);
	$PDF->Text(105, 65, 'BLUE');
	$PDF->Text(60, 95, 'GREEN');
	$PDF->Text(120, 115, 'RED');

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_031.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	