<?php
//============================================================+
// File name   : example_042.php
// Begin       : 2008-12-23
// Last Update : 2013-05-14
//
// Description : Example 042 for TCPDF class
//               Test Image with alpha channel
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
 * @abstract TCPDF - Example: Test Image with alpha channel
 * @author Nicola Asuni
 * @since 2008-12-23
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 042');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 042', PDF_HEADER_STRING);

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
	

	// set JPEG quality
	//$PDF->setJPEGQuality(75);
	

	$PDF->SetFont('helvetica', '', 18);

	// add a page
	$PDF->AddPage();

	// create background text
	$background_text = str_repeat('TCPDF test PNG Alpha Channel ', 50);
	$PDF->MultiCell(0, 5, $background_text, 0, 'J', 0, 2, '', '', true, 0, false);

	// --- Method (A) ------------------------------------------
	// the Image() method recognizes the alpha channel embedded on the image:
	

	$PDF->Image('images/image_with_alpha.png', 50, 50, 100, '', '', 'http://www.tcpdf.org', '', false, 300);

	// --- Method (B) ------------------------------------------
	// provide image + separate 8-bit mask
	

	// first embed mask image (w, h, x and y will be ignored, the image will be scaled to the target image's size)
	$mask = $PDF->Image('images/alpha.png', 50, 140, 100, '', '', '', '', false, 300, '', true);

	// embed image, masked with previously embedded mask
	$PDF->Image('images/img.png', 50, 140, 100, '', '', 'http://www.tcpdf.org', '', false, 300, '', false, $mask);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_042.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	