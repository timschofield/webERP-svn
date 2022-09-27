<?php
//============================================================+
// File name   : example_047.php
// Begin       : 2009-03-19
// Last Update : 2013-05-14
//
// Description : Example 047 for TCPDF class
//               Transactions
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
 * @abstract TCPDF - Example: Transactions
 * @author Nicola Asuni
 * @since 2009-03-19
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 047');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 047', PDF_HEADER_STRING);

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
	$PDF->SetFont('helvetica', '', 16);

	// add a page
	$PDF->AddPage();

	$txt = 'Example of Transactions.
TCPDF allows you to undo some operations using the Transactions.
Check the source code for further information.';
	$PDF->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);

	$PDF->Ln(5);

	$PDF->SetFont('times', '', 12);

	// start transaction
	$PDF->startTransaction();

	$PDF->Write(0, "LINE 1\n");
	$PDF->Write(0, "LINE 2\n");

	// restarts transaction
	$PDF->startTransaction();

	$PDF->Write(0, "LINE 3\n");
	$PDF->Write(0, "LINE 4\n");

	// rolls back to the last (re)start
	$PDF = $PDF->rollbackTransaction();

	$PDF->Write(0, "LINE 5\n");
	$PDF->Write(0, "LINE 6\n");

	// start transaction
	$PDF->startTransaction();

	$PDF->Write(0, "LINE 7\n");

	// commit transaction (actually just frees memory)
	$PDF->commitTransaction();

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_047.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	