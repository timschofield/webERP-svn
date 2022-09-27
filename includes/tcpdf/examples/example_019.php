<?php
//============================================================+
// File name   : example_019.php
// Begin       : 2008-03-07
// Last Update : 2013-05-14
//
// Description : Example 019 for TCPDF class
//               Non unicode with alternative config file
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
 * @abstract TCPDF - Example: Non unicode with alternative config file
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

// Set document information dictionary in unicode mode
$PDF->SetDocInfoUnicode(true);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni [€]');
$PDF->SetTitle('TCPDF Example 019');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 019', PDF_HEADER_STRING);

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

// set some language dependent data:
$lg = Array();
$lg['a_meta_charset'] = 'ISO-8859-1';
$lg['a_meta_dir'] = 'ltr';
$lg['a_meta_language'] = 'en';
$lg['w_page'] = 'page';

// set some language-dependent strings (optional)
$PDF->setLanguageArray($lg);

// ---------------------------------------------------------


// set font
$PDF->SetFont('helvetica', '', 12);

// add a page
$PDF->AddPage();

// set color for background
$PDF->SetFillColor(200, 255, 200);

$txt = 'An alternative configuration file is used on this example.
Check the definition of the K_TCPDF_EXTERNAL_CONFIG constant on the source code.';

// print some text
$PDF->MultiCell(0, 0, $txt . "\n", 1, 'J', 1, 1, '', '', true, 0, false, true, 0);

// ---------------------------------------------------------


//Close and output PDF document
$PDF->Output('example_019.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
