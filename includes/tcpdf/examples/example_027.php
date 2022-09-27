<?php
//============================================================+
// File name   : example_027.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 027 for TCPDF class
//               1D Barcodes
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
 * @abstract TCPDF - Example: 1D Barcodes.
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
$PDF->SetTitle('TCPDF Example 027');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 027', PDF_HEADER_STRING);

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
	

	// set a barcode on the page footer
	$PDF->setBarcode(date('Y-m-d H:i:s'));

	// set font
	$PDF->SetFont('helvetica', '', 11);

	// add a page
	$PDF->AddPage();

	// print a message
	$txt = "You can also export 1D barcodes in other formats (PNG, SVG, HTML). Check the examples inside the barcodes directory.\n";
	$PDF->MultiCell(70, 50, $txt, 0, 'J', false, 1, 125, 30, true, 0, false, true, 0, 'T', false);
	$PDF->SetY(30);

	// -----------------------------------------------------------------------------
	

	$PDF->SetFont('helvetica', '', 10);

	// define barcode style
	$style = array('position' => '', 'align' => 'C', 'stretch' => false, 'fitwidth' => true, 'cellfitalign' => '', 'border' => true, 'hpadding' => 'auto', 'vpadding' => 'auto', 'fgcolor' => array(0, 0, 0), 'bgcolor' => false, //array(255,255,255),
	'text' => true, 'font' => 'helvetica', 'fontsize' => 8, 'stretchtext' => 4);

	// PRINT VARIOUS 1D BARCODES
	

	// CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
	$PDF->Cell(0, 0, 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9', 0, 1);
	$PDF->write1DBarcode('CODE 39', 'C39', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 39 + CHECKSUM
	$PDF->Cell(0, 0, 'CODE 39 + CHECKSUM', 0, 1);
	$PDF->write1DBarcode('CODE 39 +', 'C39+', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 39 EXTENDED
	$PDF->Cell(0, 0, 'CODE 39 EXTENDED', 0, 1);
	$PDF->write1DBarcode('CODE 39 E', 'C39E', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 39 EXTENDED + CHECKSUM
	$PDF->Cell(0, 0, 'CODE 39 EXTENDED + CHECKSUM', 0, 1);
	$PDF->write1DBarcode('CODE 39 E+', 'C39E+', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 93 - USS-93
	$PDF->Cell(0, 0, 'CODE 93 - USS-93', 0, 1);
	$PDF->write1DBarcode('TEST93', 'C93', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// Standard 2 of 5
	$PDF->Cell(0, 0, 'Standard 2 of 5', 0, 1);
	$PDF->write1DBarcode('1234567', 'S25', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// Standard 2 of 5 + CHECKSUM
	$PDF->Cell(0, 0, 'Standard 2 of 5 + CHECKSUM', 0, 1);
	$PDF->write1DBarcode('1234567', 'S25+', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// Interleaved 2 of 5
	$PDF->Cell(0, 0, 'Interleaved 2 of 5', 0, 1);
	$PDF->write1DBarcode('1234567', 'I25', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// Interleaved 2 of 5 + CHECKSUM
	$PDF->Cell(0, 0, 'Interleaved 2 of 5 + CHECKSUM', 0, 1);
	$PDF->write1DBarcode('1234567', 'I25+', '', '', '', 18, 0.4, $style, 'N');

	// add a page ----------
	$PDF->AddPage();

	// CODE 128 AUTO
	$PDF->Cell(0, 0, 'CODE 128 AUTO', 0, 1);
	$PDF->write1DBarcode('CODE 128 AUTO', 'C128', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 128 A
	$PDF->Cell(0, 0, 'CODE 128 A', 0, 1);
	$PDF->write1DBarcode('CODE 128 A', 'C128A', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 128 B
	$PDF->Cell(0, 0, 'CODE 128 B', 0, 1);
	$PDF->write1DBarcode('CODE 128 B', 'C128B', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 128 C
	$PDF->Cell(0, 0, 'CODE 128 C', 0, 1);
	$PDF->write1DBarcode('0123456789', 'C128C', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// EAN 8
	$PDF->Cell(0, 0, 'EAN 8', 0, 1);
	$PDF->write1DBarcode('1234567', 'EAN8', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// EAN 13
	$PDF->Cell(0, 0, 'EAN 13', 0, 1);
	$PDF->write1DBarcode('1234567890128', 'EAN13', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// UPC-A
	$PDF->Cell(0, 0, 'UPC-A', 0, 1);
	$PDF->write1DBarcode('12345678901', 'UPCA', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// UPC-E
	$PDF->Cell(0, 0, 'UPC-E', 0, 1);
	$PDF->write1DBarcode('04210000526', 'UPCE', '', '', '', 18, 0.4, $style, 'N');

	// add a page ----------
	$PDF->AddPage();

	// 5-Digits UPC-Based Extension
	$PDF->Cell(0, 0, '5-Digits UPC-Based Extension', 0, 1);
	$PDF->write1DBarcode('51234', 'EAN5', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// 2-Digits UPC-Based Extension
	$PDF->Cell(0, 0, '2-Digits UPC-Based Extension', 0, 1);
	$PDF->write1DBarcode('34', 'EAN2', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// MSI
	$PDF->Cell(0, 0, 'MSI', 0, 1);
	$PDF->write1DBarcode('80523', 'MSI', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// MSI + CHECKSUM (module 11)
	$PDF->Cell(0, 0, 'MSI + CHECKSUM (module 11)', 0, 1);
	$PDF->write1DBarcode('80523', 'MSI+', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODABAR
	$PDF->Cell(0, 0, 'CODABAR', 0, 1);
	$PDF->write1DBarcode('123456789', 'CODABAR', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// CODE 11
	$PDF->Cell(0, 0, 'CODE 11', 0, 1);
	$PDF->write1DBarcode('123-456-789', 'CODE11', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// PHARMACODE
	$PDF->Cell(0, 0, 'PHARMACODE', 0, 1);
	$PDF->write1DBarcode('789', 'PHARMA', '', '', '', 18, 0.4, $style, 'N');

	$PDF->Ln();

	// PHARMACODE TWO-TRACKS
	$PDF->Cell(0, 0, 'PHARMACODE TWO-TRACKS', 0, 1);
	$PDF->write1DBarcode('105', 'PHARMA2T', '', '', '', 18, 2, $style, 'N');

	// add a page ----------
	$PDF->AddPage();

	// IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
	$PDF->Cell(0, 0, 'IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200', 0, 1);
	$PDF->write1DBarcode('01234567094987654321-01234567891', 'IMB', '', '', '', 15, 0.6, $style, 'N');

	$PDF->Ln();

	// POSTNET
	$PDF->Cell(0, 0, 'POSTNET', 0, 1);
	$PDF->write1DBarcode('98000', 'POSTNET', '', '', '', 15, 0.6, $style, 'N');

	$PDF->Ln();

	// PLANET
	$PDF->Cell(0, 0, 'PLANET', 0, 1);
	$PDF->write1DBarcode('98000', 'PLANET', '', '', '', 15, 0.6, $style, 'N');

	$PDF->Ln();

	// RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
	$PDF->Cell(0, 0, 'RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)', 0, 1);
	$PDF->write1DBarcode('SN34RD1A', 'RMS4CC', '', '', '', 15, 0.6, $style, 'N');

	$PDF->Ln();

	// KIX (Klant index - Customer index)
	$PDF->Cell(0, 0, 'KIX (Klant index - Customer index)', 0, 1);
	$PDF->write1DBarcode('SN34RDX1A', 'KIX', '', '', '', 15, 0.6, $style, 'N');

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// TEST BARCODE ALIGNMENTS
	

	// add a page
	$PDF->AddPage();

	// set a background color
	$style['bgcolor'] = array(255, 255, 240);
	$style['fgcolor'] = array(127, 0, 0);

	// Left position
	$style['position'] = 'L';
	$PDF->write1DBarcode('LEFT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Center position
	$style['position'] = 'C';
	$PDF->write1DBarcode('CENTER', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Right position
	$style['position'] = 'R';
	$PDF->write1DBarcode('RIGHT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);
	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	$style['fgcolor'] = array(0, 127, 0);
	$style['position'] = '';
	$style['stretch'] = false; // disable stretch
	$style['fitwidth'] = false; // disable fitwidth
	

	// Left alignment
	$style['align'] = 'L';
	$PDF->write1DBarcode('LEFT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Center alignment
	$style['align'] = 'C';
	$PDF->write1DBarcode('CENTER', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Right alignment
	$style['align'] = 'R';
	$PDF->write1DBarcode('RIGHT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);
	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	$style['fgcolor'] = array(0, 64, 127);
	$style['position'] = '';
	$style['stretch'] = false; // disable stretch
	$style['fitwidth'] = true; // disable fitwidth
	

	// Left alignment
	$style['cellfitalign'] = 'L';
	$PDF->write1DBarcode('LEFT', 'C128A', 105, '', 90, 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Center alignment
	$style['cellfitalign'] = 'C';
	$PDF->write1DBarcode('CENTER', 'C128A', 105, '', 90, 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Right alignment
	$style['cellfitalign'] = 'R';
	$PDF->write1DBarcode('RIGHT', 'C128A', 105, '', 90, 15, 0.4, $style, 'N');

	$PDF->Ln(2);
	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	$style['fgcolor'] = array(127, 0, 127);

	// Left alignment
	$style['position'] = 'L';
	$PDF->write1DBarcode('LEFT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Center alignment
	$style['position'] = 'C';
	$PDF->write1DBarcode('CENTER', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	$PDF->Ln(2);

	// Right alignment
	$style['position'] = 'R';
	$PDF->write1DBarcode('RIGHT', 'C128A', '', '', '', 15, 0.4, $style, 'N');

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// TEST BARCODE STYLE
	

	// define barcode style
	$style = array('position' => '', 'align' => '', 'stretch' => true, 'fitwidth' => false, 'cellfitalign' => '', 'border' => true, 'hpadding' => 'auto', 'vpadding' => 'auto', 'fgcolor' => array(0, 0, 128), 'bgcolor' => array(255, 255, 128), 'text' => true, 'label' => 'CUSTOM LABEL', 'font' => 'helvetica', 'fontsize' => 8, 'stretchtext' => 4);

	// CODE 39 EXTENDED + CHECKSUM
	$PDF->Cell(0, 0, 'CODE 39 EXTENDED + CHECKSUM', 0, 1);
	$PDF->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)));
	$PDF->write1DBarcode('CODE 39 E+', 'C39E+', '', '', 120, 25, 0.4, $style, 'N');

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_027.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	