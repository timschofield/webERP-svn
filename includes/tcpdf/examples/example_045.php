<?php
//============================================================+
// File name   : example_045.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 045 for TCPDF class
//               Bookmarks and Table of Content
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
 * @abstract TCPDF - Example: Bookmarks and Table of Content
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
$PDF->SetTitle('TCPDF Example 045');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 045', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', 'B', 20);

	// add a page
	$PDF->AddPage();

	// set a bookmark for the current position
	$PDF->Bookmark('Chapter 1', 0, 0, '', 'B', array(0, 64, 128));

	// print a line using Cell()
	$PDF->Cell(0, 10, 'Chapter 1', 0, 1, 'L');

	// Create a fixed link to the first page using the * character
	$index_link = $PDF->AddLink();
	$PDF->SetLink($index_link, 0, '*1');
	$PDF->Cell(0, 10, 'Link to INDEX', 0, 1, 'R', false, $index_link);

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.1', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.2', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.2', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Sub-Paragraph 1.2.1', 2, 0, '', 'I', array(0, 128, 0));
	$PDF->Cell(0, 10, 'Sub-Paragraph 1.2.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.3', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.3', 0, 1, 'L');

	// fixed link to the first page using the * character
	$html = '<a href="#*1" style="color:blue;">link to INDEX (page 1)</a>';
	$PDF->writeHTML($html, true, false, true, false, '');

	// add some pages and bookmarks
	for ($i = 2;$i < 12;$i++) {
		$PDF->AddPage();
		$PDF->Bookmark('Chapter ' . $i, 0, 0, '', 'B', array(0, 64, 128));
		$PDF->Cell(0, 10, 'Chapter ' . $i, 0, 1, 'L');
	}

	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	// add a new page for TOC
	$PDF->addTOCPage();

	// write the TOC title
	$PDF->SetFont('times', 'B', 16);
	$PDF->MultiCell(0, 0, 'Table Of Content', 0, 'C', 0, 1, '', '', true, 0);
	$PDF->Ln();

	$PDF->SetFont('dejavusans', '', 12);

	// add a simple Table Of Content at first page
	// (check the example n. 59 for the HTML version)
	$PDF->addTOC(1, 'courier', '.', 'INDEX', 'B', array(128, 0, 0));

	// end of TOC page
	$PDF->endTOCPage();

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_045.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	