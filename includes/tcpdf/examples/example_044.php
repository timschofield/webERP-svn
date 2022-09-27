<?php
//============================================================+
// File name   : example_044.php
// Begin       : 2009-01-02
// Last Update : 2013-05-14
//
// Description : Example 044 for TCPDF class
//               Move, copy and delete pages
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
 * @abstract TCPDF - Example: Move, copy and delete pages
 * @author Nicola Asuni
 * @since 2009-01-02
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 044');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 044', PDF_HEADER_STRING);

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
	$PDF->SetFont('helvetica', 'B', 40);

	// print a line using Cell()
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: A', 0, 1, 'L');

	// add some vertical space
	$PDF->Ln(10);

	// print some text
	$PDF->SetFont('times', 'I', 16);
	$txt = 'TCPDF allows you to Copy, Move and Delete pages.';
	$PDF->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

	$PDF->SetFont('helvetica', 'B', 40);

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: B', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: D', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: E', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: E-2', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: F', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: C', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: G', 0, 1, 'L');

	// Move page 7 to page 3
	$PDF->movePage(7, 3);

	// Delete page 6
	$PDF->deletePage(6);

	$PDF->AddPage();
	$PDF->Cell(0, 10, 'PAGE: H', 0, 1, 'L');

	// copy the second page
	$PDF->copyPage(2);

	// NOTE: to insert a page to a previous position, you can add a new page to the end of document and then move it using movePage().
	

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_044.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	