<?php
//============================================================+
// File name   : example_015.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 015 for TCPDF class
//               Bookmarks (Table of Content)
//               and Named Destinations.
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
 * @abstract TCPDF - Example: Bookmarks (Table of Content)
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
$PDF->SetTitle('TCPDF Example 015');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 015', PDF_HEADER_STRING);

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
	

	// Bookmark($txt, $level=0, $y=-1, $page='', $style='', $color=array(0,0,0))
	

	// set font
	$PDF->SetFont('times', 'B', 20);

	// add a page
	$PDF->AddPage();

	// set a bookmark for the current position
	$PDF->Bookmark('Chapter 1', 0, 0, '', 'B', array(0, 64, 128));

	// print a line using Cell()
	$PDF->Cell(0, 10, 'Chapter 1', 0, 1, 'L');

	$PDF->SetFont('times', 'I', 14);
	$PDF->Write(0, 'You can set PDF Bookmarks using the Bookmark() method.
You can set PDF Named Destinations using the setDestination() method.');

	$PDF->SetFont('times', 'B', 20);

	// add other pages and bookmarks
	

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.1', 1, 0, '', '', array(0, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.2', 1, 0, '', '', array(0, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.2', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Sub-Paragraph 1.2.1', 2, 0, '', 'I', array(0, 0, 0));
	$PDF->Cell(0, 10, 'Sub-Paragraph 1.2.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.3', 1, 0, '', '', array(0, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.3', 0, 1, 'L');

	$PDF->AddPage();
	// add a named destination so you can open this document at this page using the link: "example_015.pdf#chapter2"
	$PDF->setDestination('chapter2', 0, '');
	// add a bookmark that points to a named destination
	$PDF->Bookmark('Chapter 2', 0, 0, '', 'BI', array(128, 0, 0), -1, '#chapter2');
	$PDF->Cell(0, 10, 'Chapter 2', 0, 1, 'L');
	$PDF->SetFont('times', 'I', 14);
	$PDF->Write(0, 'Once saved, you can open this document at this page using the link: "example_015.pdf#chapter2".');

	$PDF->AddPage();
	$PDF->setDestination('chapter3', 0, '');
	$PDF->SetFont('times', 'B', 20);
	$PDF->Bookmark('Chapter 3', 0, 0, '', 'B', array(0, 64, 128));
	$PDF->Cell(0, 10, 'Chapter 3', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->setDestination('chapter4', 0, '');
	$PDF->SetFont('times', 'B', 20);
	$PDF->Bookmark('Chapter 4', 0, 0, '', 'B', array(0, 64, 128));
	$PDF->Cell(0, 10, 'Chapter 4', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Chapter 5', 0, 0, '', 'B', array(0, 128, 0));
	$PDF->Cell(0, 10, 'Chapter 5', 0, 1, 'L');
	$txt = 'Example of File Attachment.
Double click on the icon to open the attached file.';
	$PDF->SetFont('helvetica', '', 10);
	$PDF->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

	// attach an external file TXT file
	$PDF->Annotation(20, 50, 5, 5, 'TXT file', array('Subtype' => 'FileAttachment', 'Name' => 'PushPin', 'FS' => 'data/utf8test.txt'));

	// attach an external file
	$PDF->Annotation(50, 50, 5, 5, 'PDF file', array('Subtype' => 'FileAttachment', 'Name' => 'PushPin', 'FS' => 'example_012.pdf'));

	// add a bookmark that points to an embedded file
	// NOTE: prefix the file name with the * character for generic file and with % character for PDF file
	$PDF->Bookmark('TXT file', 0, 0, '', 'B', array(128, 0, 255), -1, '*utf8test.txt');

	// add a bookmark that points to an embedded file
	// NOTE: prefix the file name with the * character for generic file and with % character for PDF file
	$PDF->Bookmark('PDF file', 0, 0, '', 'B', array(128, 0, 255), -1, '%example_012.pdf');

	// add a bookmark that points to an external URL
	$PDF->Bookmark('External URL', 0, 0, '', 'B', array(0, 0, 255), -1, 'http://www.tcpdf.org');

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_015.pdf', 'D');

	//============================================================+
	// END OF FILE
	//============================================================+
	