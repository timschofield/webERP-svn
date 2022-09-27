<?php
//============================================================+
// File name   : example_035.php
// Begin       : 2008-07-22
// Last Update : 2013-05-14
//
// Description : Example 035 for TCPDF class
//               Line styles with cells and multicells
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
 * @abstract TCPDF - Example: Line styles with cells and multicells
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
$PDF->SetTitle('TCPDF Example 035');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 035', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', 'BI', 16);

	// add a page
	$PDF->AddPage();

	$PDF->Write(0, 'Example of SetLineStyle() method', '', 0, 'L', true, 0, false, false, 0);

	$PDF->Ln();

	$PDF->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 0, 0)));
	$PDF->SetFillColor(255, 255, 128);
	$PDF->SetTextColor(0, 0, 128);

	$text = "DUMMY";

	$PDF->Cell(0, 0, $text, 1, 1, 'L', 1, 0);

	$PDF->Ln();

	$PDF->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 255)));
	$PDF->SetFillColor(255, 255, 0);
	$PDF->SetTextColor(0, 0, 255);
	$PDF->MultiCell(60, 4, $text, 1, 'C', 1, 0);

	$PDF->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 0)));
	$PDF->SetFillColor(0, 0, 255);
	$PDF->SetTextColor(255, 255, 0);
	$PDF->MultiCell(60, 4, $text, 'TB', 'C', 1, 0);

	$PDF->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 255)));
	$PDF->SetFillColor(0, 255, 0);
	$PDF->SetTextColor(255, 0, 255);
	$PDF->MultiCell(60, 4, $text, 1, 'C', 1, 1);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_035.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	