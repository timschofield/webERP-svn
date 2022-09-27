<?php
//============================================================+
// File name   : example_003.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 003 for TCPDF class
//               Custom Header and Footer
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
 * @abstract TCPDF - Example: Custom Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {

	//Page header
	public function Header() {
		// Logo
		$image_file = K_PATH_IMAGES . 'logo_example.jpg';
		$this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		// Set font
		$this->SetFont('helvetica', 'B', 20);
		// Title
		$this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
	}

	// Page footer
	public function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-15);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}
	}

	// create new PDF document
	$PDF = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	$PDF->SetCreator(PDF_CREATOR);
	$PDF->SetAuthor('Nicola Asuni');
	$PDF->SetTitle('TCPDF Example 003');
	$PDF->SetSubject('TCPDF Tutorial');
	$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

	// set default header data
	$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

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
	$PDF->SetFont('times', 'BI', 12);

	// add a page
	$PDF->AddPage();

	// set some text to print
	$txt = <<<EOD
TCPDF Example 003

Custom page header and footer are defined by extending the TCPDF class and overriding the Header() and Footer() methods.
EOD;
	

	// print a block of text using Write()
	$PDF->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_003.pdf', 'I');

	//============================================================+
	// END OF FILE
	//============================================================+
	