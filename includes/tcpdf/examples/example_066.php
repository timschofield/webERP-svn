<?php
/**
 * Example 066 for TCPDF library
 *
 * @description Creates an example PDF/A-1b document using TCPDF
 * @author Nicola Asuni - Tecnick.com LTD <info@tecnick.com>
 * @license LGPL-3.0
 */

/**
 * Creates an example PDF/A-1b document using TCPDF
 *
 * @abstract TCPDF - Example: PDF/A-1b mode
 * @author Nicola Asuni
 * @since 2021-03-26
 */

// Load the autoloader, move one folder back from examples
require_once __DIR__ . '/../vendor/autoload.php';

// create new PDF document
$PDF = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);

// set document information
$PDF->SetCreator(PDF_CREATOR);
$PDF->SetAuthor('Nicola Asuni');
$PDF->SetTitle('TCPDF Example 066');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 066', PDF_HEADER_STRING);

// set header and footer fonts
$PDF->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$PDF->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

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
if (@file_exists(__DIR__ . '/lang/eng.php')) {
	require_once __DIR__ . '/lang/eng.php';

	$PDF->setLanguageArray($l);
	}

	// ---------------------------------------------------------
	

	// set default font subsetting mode
	$PDF->setFontSubsetting(true);

	// Set font
	$PDF->SetFont('helvetica', '', 14, '', true);

	// Add a page
	// This method has several options, check the source code documentation for more information.
	$PDF->AddPage();

	// Set some content to print
	$html = <<<HTML
<h1>Example of <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a> document in <span style="background-color:#99ccff;color:black;"> PDF/A-1b </span> mode.</h1>
<i>This document conforms to the standard <b>PDF/A-1b (ISO 19005-1:2005)</b>.</i>
<p>Please check the source code documentation and other examples for further information (<a href="http://www.tcpdf.org">http://www.tcpdf.org</a>).</p>
HTML;
	

	// Print text using writeHTMLCell()
	$PDF->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

	// ---------------------------------------------------------
	

	// Close and output PDF document
	// This method has several options, check the source code documentation for more information.
	$PDF->Output('example_066.pdf', 'I');
	