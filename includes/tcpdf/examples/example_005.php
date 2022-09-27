<?php
//============================================================+
// File name   : example_005.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 005 for TCPDF class
//               Multicell
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
 * @abstract TCPDF - Example: Multicell
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
$PDF->SetTitle('TCPDF Example 005');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 005', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', '', 10);

	// add a page
	$PDF->AddPage();

	// set cell padding
	$PDF->setCellPaddings(1, 1, 1, 1);

	// set cell margins
	$PDF->setCellMargins(1, 1, 1, 1);

	// set color for background
	$PDF->SetFillColor(255, 255, 127);

	// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
	

	// set some text for example
	$txt = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

	// Multicell test
	$PDF->MultiCell(55, 5, '[LEFT] ' . $txt, 1, 'L', 1, 0, '', '', true);
	$PDF->MultiCell(55, 5, '[RIGHT] ' . $txt, 1, 'R', 0, 1, '', '', true);
	$PDF->MultiCell(55, 5, '[CENTER] ' . $txt, 1, 'C', 0, 0, '', '', true);
	$PDF->MultiCell(55, 5, '[JUSTIFY] ' . $txt . "\n", 1, 'J', 1, 2, '', '', true);
	$PDF->MultiCell(55, 5, '[DEFAULT] ' . $txt, 1, '', 0, 1, '', '', true);

	$PDF->Ln(4);

	// set color for background
	$PDF->SetFillColor(220, 255, 220);

	// Vertical alignment
	$PDF->MultiCell(55, 40, '[VERTICAL ALIGNMENT - TOP] ' . $txt, 1, 'J', 1, 0, '', '', true, 0, false, true, 40, 'T');
	$PDF->MultiCell(55, 40, '[VERTICAL ALIGNMENT - MIDDLE] ' . $txt, 1, 'J', 1, 0, '', '', true, 0, false, true, 40, 'M');
	$PDF->MultiCell(55, 40, '[VERTICAL ALIGNMENT - BOTTOM] ' . $txt, 1, 'J', 1, 1, '', '', true, 0, false, true, 40, 'B');

	$PDF->Ln(4);

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	

	// set color for background
	$PDF->SetFillColor(215, 235, 255);

	// set some text for example
	$txt = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. In sed imperdiet lectus. Phasellus quis velit velit, non condimentum quam. Sed neque urna, ultrices ac volutpat vel, laoreet vitae augue. Sed vel velit erat. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Cras eget velit nulla, eu sagittis elit. Nunc ac arcu est, in lobortis tellus. Praesent condimentum rhoncus sodales. In hac habitasse platea dictumst. Proin porta eros pharetra enim tincidunt dignissim nec vel dolor. Cras sapien elit, ornare ac dignissim eu, ultricies ac eros. Maecenas augue magna, ultrices a congue in, mollis eu nulla. Nunc venenatis massa at est eleifend faucibus. Vivamus sed risus lectus, nec interdum nunc.

Fusce et felis vitae diam lobortis sollicitudin. Aenean tincidunt accumsan nisi, id vehicula quam laoreet elementum. Phasellus egestas interdum erat, et viverra ipsum ultricies ac. Praesent sagittis augue at augue volutpat eleifend. Cras nec orci neque. Mauris bibendum posuere blandit. Donec feugiat mollis dui sit amet pellentesque. Sed a enim justo. Donec tincidunt, nisl eget elementum aliquam, odio ipsum ultrices quam, eu porttitor ligula urna at lorem. Donec varius, eros et convallis laoreet, ligula tellus consequat felis, ut ornare metus tellus sodales velit. Duis sed diam ante. Ut rutrum malesuada massa, vitae consectetur ipsum rhoncus sed. Suspendisse potenti. Pellentesque a congue massa.';

	// print a blox of text using multicell()
	$PDF->MultiCell(80, 5, $txt . "\n", 1, 'J', 1, 1, '', '', true);

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	

	// AUTO-FITTING
	

	// set color for background
	$PDF->SetFillColor(255, 235, 235);

	// Fit text on cell by reducing font size
	$PDF->MultiCell(55, 60, '[FIT CELL] ' . $txt . "\n", 1, 'J', 1, 1, 125, 145, true, 0, false, true, 60, 'M', true);

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	

	// CUSTOM PADDING
	

	// set color for background
	$PDF->SetFillColor(255, 255, 215);

	// set font
	$PDF->SetFont('helvetica', '', 8);

	// set cell padding
	$PDF->setCellPaddings(2, 4, 6, 8);

	$txt = "CUSTOM PADDING:\nLeft=2, Top=4, Right=6, Bottom=8\nLorem ipsum dolor sit amet, consectetur adipiscing elit. In sed imperdiet lectus. Phasellus quis velit velit, non condimentum quam. Sed neque urna, ultrices ac volutpat vel, laoreet vitae augue.\n";

	$PDF->MultiCell(55, 5, $txt, 1, 'J', 1, 2, 125, 210, true);

	// move pointer to last page
	$PDF->lastPage();

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_005.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	