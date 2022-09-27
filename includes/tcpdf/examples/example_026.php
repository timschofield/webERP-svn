<?php
//============================================================+
// File name   : example_026.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 026 for TCPDF class
//               Text Rendering Modes and Text Clipping
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
 * @abstract TCPDF - Example: Text Rendering Modes and Text Clipping
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
$PDF->SetTitle('TCPDF Example 026');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 026', PDF_HEADER_STRING);

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
	$PDF->SetFont('helvetica', '', 22);

	// add a page
	$PDF->AddPage();

	// set color for text stroke
	$PDF->SetDrawColor(255, 0, 0);

	$PDF->setTextRenderingMode($stroke = 0, $fill = true, $clip = false);
	$PDF->Write(0, 'Fill text', '', 0, '', true, 0, false, false, 0);

	$PDF->setTextRenderingMode($stroke = 0.2, $fill = false, $clip = false);
	$PDF->Write(0, 'Stroke text', '', 0, '', true, 0, false, false, 0);

	$PDF->setTextRenderingMode($stroke = 0.2, $fill = true, $clip = false);
	$PDF->Write(0, 'Fill, then stroke text', '', 0, '', true, 0, false, false, 0);

	$PDF->setTextRenderingMode($stroke = 0, $fill = false, $clip = false);
	$PDF->Write(0, 'Neither fill nor stroke text (invisible)', '', 0, '', true, 0, false, false, 0);

	// * * * CLIPPING MODES  * * * * * * * * * * * * * * * * * *
	

	$PDF->StartTransform();
	$PDF->setTextRenderingMode($stroke = 0, $fill = true, $clip = true);
	$PDF->Write(0, 'Fill text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
	$PDF->Image('images/image_demo.jpg', 15, 65, 170, 10, '', '', '', true, 72);
	$PDF->StopTransform();

	$PDF->StartTransform();
	$PDF->setTextRenderingMode($stroke = 0.3, $fill = false, $clip = true);
	$PDF->Write(0, 'Stroke text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
	$PDF->Image('images/image_demo.jpg', 15, 75, 170, 10, '', '', '', true, 72);
	$PDF->StopTransform();

	$PDF->StartTransform();
	$PDF->setTextRenderingMode($stroke = 0.3, $fill = true, $clip = true);
	$PDF->Write(0, 'Fill, then stroke text and add to path for clipping', '', 0, '', true, 0, false, false, 0);
	$PDF->Image('images/image_demo.jpg', 15, 85, 170, 10, '', '', '', true, 72);
	$PDF->StopTransform();

	$PDF->StartTransform();
	$PDF->setTextRenderingMode($stroke = 0, $fill = false, $clip = true);
	$PDF->Write(0, 'Add text to path for clipping', '', 0, '', true, 0, false, false, 0);
	$PDF->Image('images/image_demo.jpg', 15, 95, 170, 10, '', '', '', true, 72);
	$PDF->StopTransform();

	// reset text rendering mode
	$PDF->setTextRenderingMode($stroke = 0, $fill = true, $clip = false);

	// * * * HTML MODE * * * * * * * * * * * * * * * * * * * * *
	

	// The following attributes were added to HTML:
	// stroke : stroke width
	// strokecolor : stroke color
	// fill : true (default) to fill the font, false otherwise
	

	// create some HTML content with text rendering modes
	$html = '<span stroke="0" fill="true">HTML Fill text</span><br />';
	$html.= '<span stroke="0.2" fill="false">HTML Stroke text</span><br />';
	$html.= '<span stroke="0.2" fill="true" strokecolor="#FF0000" color="#FFFF00">HTML Fill, then stroke text</span><br />';
	$html.= '<span stroke="0" fill="false">HTML Neither fill nor stroke text (invisible)</span><br />';

	// output the HTML content
	$PDF->writeHTML($html, true, 0, true, 0);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_026.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	