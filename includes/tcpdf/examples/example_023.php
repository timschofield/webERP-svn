<?php
//============================================================+
// File name   : example_023.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 023 for TCPDF class
//               Page Groups
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
 * @abstract TCPDF - Example: Page Groups.
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
$PDF->SetTitle('TCPDF Example 023');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 023', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', 'BI', 14);

	// Start First Page Group
	$PDF->startPageGroup();

	// add a page
	$PDF->AddPage();

	// set some text to print
	$txt = <<<EOD
Example of page groups.
Check the page numbers on the page footer.

This is the first page of group 1.
EOD;
	

	// print a block of text using Write()
	$PDF->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

	// add second page
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'This is the second page of group 1', 0, 1, 'L');

	// Start Second Page Group
	$PDF->startPageGroup();

	// add some pages
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'This is the first page of group 2', 0, 1, 'L');
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'This is the second page of group 2', 0, 1, 'L');
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'This is the third page of group 2', 0, 1, 'L');
	$PDF->AddPage();
	$PDF->Cell(0, 10, 'This is the fourth page of group 2', 0, 1, 'L');

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_023.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	