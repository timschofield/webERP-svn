<?php
//============================================================+
// File name   : example_024.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 024 for TCPDF class
//               Object Visibility and Layers
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
 * @abstract TCPDF - Example: Object Visibility and Layers
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
$PDF->SetTitle('TCPDF Example 024');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 024', PDF_HEADER_STRING);

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
	$PDF->SetFont('times', '', 18);

	// add a page
	$PDF->AddPage();

	/*
	 * setVisibility() allows to restrict the rendering of some
	 * elements to screen or printout. This can be useful, for
	 * instance, to put a background image or color that will
	 * show on screen but won't print.
	*/

	$txt = 'You can limit the visibility of PDF objects to screen or printer by using the setVisibility() method.
Check the print preview of this document to display the alternative text.';

	$PDF->Write(0, $txt, '', 0, '', true, 0, false, false, 0);

	// change font size
	$PDF->SetFontSize(40);

	// change text color
	$PDF->SetTextColor(0, 63, 127);

	// set visibility only for screen
	$PDF->setVisibility('screen');

	// write something only for screen
	$PDF->Write(0, '[This line is for display]', '', 0, 'C', true, 0, false, false, 0);

	// set visibility only for print
	$PDF->setVisibility('print');

	// change text color
	$PDF->SetTextColor(127, 0, 0);

	// write something only for print
	$PDF->Write(0, '[This line is for printout]', '', 0, 'C', true, 0, false, false, 0);

	// restore visibility
	$PDF->setVisibility('all');

	// ---------------------------------------------------------
	

	// LAYERS
	

	// start a new layer
	$PDF->startLayer('layer1', true, true);

	// change font size
	$PDF->SetFontSize(18);

	// change text color
	$PDF->SetTextColor(0, 127, 0);

	$txt = 'Using the startLayer() method you can group PDF objects into layers.
This text is on "layer1".';

	// write something
	$PDF->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

	// close the current layer
	$PDF->endLayer();

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_024.pdf', 'D');

	//============================================================+
	// END OF FILE
	//============================================================+
	