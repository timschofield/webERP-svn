<?php
//============================================================+
// File name   : example_011.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 011 for TCPDF class
//               Colored Table (very simple table)
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
 * @abstract TCPDF - Example: Colored Table
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// extend TCPF with custom functions
class MYPDF extends TCPDF {

	// Load table data from file
	public function LoadData($file) {
		// Read file lines
		$lines = file($file);
		$data = array();
		foreach ($lines as $line) {
			$data[] = explode(';', chop($line));
		}
		return $data;
	}

	// Colored table
	public function ColoredTable($header, $data) {
		// Colors, line width and bold font
		$this->SetFillColor(255, 0, 0);
		$this->SetTextColor(255);
		$this->SetDrawColor(128, 0, 0);
		$this->SetLineWidth(0.3);
		$this->SetFont('', 'B');
		// Header
		$w = array(40, 35, 40, 45);
		$num_headers = count($header);
		for ($i = 0;$i < $num_headers;++$i) {
			$this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
		}
		$this->Ln();
		// Color and font restoration
		$this->SetFillColor(224, 235, 255);
		$this->SetTextColor(0);
		$this->SetFont('');
		// Data
		$fill = 0;
		foreach ($data as $row) {
			$this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
			$this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
			$this->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
			$this->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
			$this->Ln();
			$fill = !$fill;
		}
		$this->Cell(array_sum($w), 0, '', 'T');
	}
	}

	// create new PDF document
	$PDF = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	$PDF->SetCreator(PDF_CREATOR);
	$PDF->SetAuthor('Nicola Asuni');
	$PDF->SetTitle('TCPDF Example 011');
	$PDF->SetSubject('TCPDF Tutorial');
	$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

	// set default header data
	$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 011', PDF_HEADER_STRING);

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
	$PDF->SetFont('helvetica', '', 12);

	// add a page
	$PDF->AddPage();

	// column titles
	$header = array('Country', 'Capital', 'Area (sq km)', 'Pop. (thousands)');

	// data loading
	$data = $PDF->LoadData('data/table_data_demo.txt');

	// print colored table
	$PDF->ColoredTable($header, $data);

	// ---------------------------------------------------------
	

	// close and output PDF document
	$PDF->Output('example_011.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	