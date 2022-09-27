<?php
//============================================================+
// File name   : example_062.php
// Begin       : 2010-08-25
// Last Update : 2013-05-14
//
// Description : Example 062 for TCPDF class
//               XObject Template
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
 * @abstract TCPDF - Example: XObject Template
 * @author Nicola Asuni
 * @since 2010-08-25
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 062');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 062', PDF_HEADER_STRING);

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

	$PDF->Write(0, 'XObject Templates', '', 0, 'C', 1, 0, false, false, 0);

	/*
	 * An XObject Template is a PDF block that is a self-contained
	 * description of any sequence of graphics objects (including path
	 * objects, text objects, and sampled images).
	 * An XObject Template may be painted multiple times, either on
	 * several pages or at several locations on the same page and produces
	 * the same results each time, subject only to the graphics state at
	 * the time it is invoked.
	*/

	// start a new XObject Template and set transparency group option
	$template_id = $PDF->startTemplate(60, 60, true);

	// create Template content
	// ...................................................................
	//Start Graphic Transformation
	$PDF->StartTransform();

	// set clipping mask
	$PDF->StarPolygon(30, 30, 29, 10, 3, 0, 1, 'CNZ');

	// draw jpeg image to be clipped
	$PDF->Image('images/image_demo.jpg', 0, 0, 60, 60, '', '', '', true, 72, '', false, false, 0, false, false, false);

	//Stop Graphic Transformation
	$PDF->StopTransform();

	$PDF->SetXY(0, 0);

	$PDF->SetFont('times', '', 40);

	$PDF->SetTextColor(255, 0, 0);

	// print a text
	$PDF->Cell(60, 60, 'Template', 0, 0, 'C', false, '', 0, false, 'T', 'M');
	// ...................................................................
	

	// end the current Template
	$PDF->endTemplate();

	// print the selected Template various times using various transparencies
	

	$PDF->SetAlpha(0.4);
	$PDF->printTemplate($template_id, 15, 50, 20, 20, '', '', false);

	$PDF->SetAlpha(0.6);
	$PDF->printTemplate($template_id, 27, 62, 40, 40, '', '', false);

	$PDF->SetAlpha(0.8);
	$PDF->printTemplate($template_id, 55, 85, 60, 60, '', '', false);

	$PDF->SetAlpha(1);
	$PDF->printTemplate($template_id, 95, 125, 80, 80, '', '', false);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_062.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	