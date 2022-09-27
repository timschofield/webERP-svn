<?php
//============================================================+
// File name   : example_004.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 004 for TCPDF class
//               Cell stretching
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
 * @abstract TCPDF - Example: Cell stretching
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
$PDF->SetTitle('TCPDF Example 004');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 004', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', '', 11);

	// add a page
	$PDF->AddPage();

	//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')
	

	// test Cell stretching
	$PDF->Cell(0, 0, 'TEST CELL STRETCH: no stretch', 1, 1, 'C', 0, '', 0);
	$PDF->Cell(0, 0, 'TEST CELL STRETCH: scaling', 1, 1, 'C', 0, '', 1);
	$PDF->Cell(0, 0, 'TEST CELL STRETCH: force scaling', 1, 1, 'C', 0, '', 2);
	$PDF->Cell(0, 0, 'TEST CELL STRETCH: spacing', 1, 1, 'C', 0, '', 3);
	$PDF->Cell(0, 0, 'TEST CELL STRETCH: force spacing', 1, 1, 'C', 0, '', 4);

	$PDF->Ln(5);

	$PDF->Cell(45, 0, 'TEST CELL STRETCH: scaling', 1, 1, 'C', 0, '', 1);
	$PDF->Cell(45, 0, 'TEST CELL STRETCH: force scaling', 1, 1, 'C', 0, '', 2);
	$PDF->Cell(45, 0, 'TEST CELL STRETCH: spacing', 1, 1, 'C', 0, '', 3);
	$PDF->Cell(45, 0, 'TEST CELL STRETCH: force spacing', 1, 1, 'C', 0, '', 4);

	$PDF->AddPage();

	// example using general stretching and spacing
	

	for ($stretching = 90;$stretching <= 110;$stretching+= 10) {
		for ($spacing = - 0.254;$spacing <= 0.254;$spacing+= 0.254) {

			// set general stretching (scaling) value
			$PDF->setFontStretching($stretching);

			// set general spacing value
			$PDF->setFontSpacing($spacing);

			$PDF->Cell(0, 0, 'Stretching ' . $stretching . '%, Spacing ' . sprintf('%+.3F', $spacing) . 'mm, no stretch', 1, 1, 'C', 0, '', 0);
			$PDF->Cell(0, 0, 'Stretching ' . $stretching . '%, Spacing ' . sprintf('%+.3F', $spacing) . 'mm, scaling', 1, 1, 'C', 0, '', 1);
			$PDF->Cell(0, 0, 'Stretching ' . $stretching . '%, Spacing ' . sprintf('%+.3F', $spacing) . 'mm, force scaling', 1, 1, 'C', 0, '', 2);
			$PDF->Cell(0, 0, 'Stretching ' . $stretching . '%, Spacing ' . sprintf('%+.3F', $spacing) . 'mm, spacing', 1, 1, 'C', 0, '', 3);
			$PDF->Cell(0, 0, 'Stretching ' . $stretching . '%, Spacing ' . sprintf('%+.3F', $spacing) . 'mm, force spacing', 1, 1, 'C', 0, '', 4);

			$PDF->Ln(2);
		}
	}

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_004.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	