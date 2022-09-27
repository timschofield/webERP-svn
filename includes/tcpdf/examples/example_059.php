<?php
//============================================================+
// File name   : example_059.php
// Begin       : 2010-05-06
// Last Update : 2013-05-14
//
// Description : Example 059 for TCPDF class
//               Table Of Content using HTML templates.
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
 * @abstract TCPDF - Example: Table Of Content using HTML templates.
 * @author Nicola Asuni
 * @since 2010-05-06
 */

// Include the main TCPDF library (search for installation path).
require_once ('tcpdf_include.php');

/**
 * TCPDF class extension with custom header and footer for TOC page
 */
class TOC_TCPDF extends TCPDF {

	/**
	 * Overwrite Header() method.
	 * @public
	 */
	public function Header() {
		if ($this->tocpage) {
			// *** replace the following parent::Header() with your code for TOC page
			parent::Header();
		} else {
			// *** replace the following parent::Header() with your code for normal pages
			parent::Header();
		}
	}

	/**
	 * Overwrite Footer() method.
	 * @public
	 */
	public function Footer() {
		if ($this->tocpage) {
			// *** replace the following parent::Footer() with your code for TOC page
			parent::Footer();
		} else {
			// *** replace the following parent::Footer() with your code for normal pages
			parent::Footer();
		}
	}

	} // end of class
	

	// create new PDF document
	$PDF = new TOC_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	$PDF->SetCreator(PDF_CREATOR);
	$PDF->SetAuthor('Nicola Asuni');
	$PDF->SetTitle('TCPDF Example 059');
	$PDF->SetSubject('TCPDF Tutorial');
	$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

	// set default header data
	$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 059', PDF_HEADER_STRING);

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

	// set font
	$PDF->SetFont('helvetica', '', 10);

	// ---------------------------------------------------------
	

	// create some content ...
	

	// add a page
	$PDF->AddPage();

	// set a bookmark for the current position
	$PDF->Bookmark('Chapter 1', 0, 0, '', 'B', array(0, 64, 128));

	// print a line using Cell()
	$PDF->Cell(0, 10, 'Chapter 1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.1', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.2', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.2', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Sub-Paragraph 1.2.1', 2, 0, '', 'I', array(0, 128, 0));
	$PDF->Cell(0, 10, 'Sub-Paragraph 1.2.1', 0, 1, 'L');

	$PDF->AddPage();
	$PDF->Bookmark('Paragraph 1.3', 1, 0, '', '', array(128, 0, 0));
	$PDF->Cell(0, 10, 'Paragraph 1.3', 0, 1, 'L');

	// add some pages and bookmarks
	for ($i = 2;$i < 12;$i++) {
		$PDF->AddPage();
		$PDF->Bookmark('Chapter ' . $i, 0, 0, '', 'B', array(0, 64, 128));
		$PDF->Cell(0, 10, 'Chapter ' . $i, 0, 1, 'L');
	}

	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	// add a new page for TOC
	$PDF->addTOCPage();

	// write the TOC title and/or other elements on the TOC page
	$PDF->SetFont('times', 'B', 16);
	$PDF->MultiCell(0, 0, 'Table Of Content', 0, 'C', 0, 1, '', '', true, 0);
	$PDF->Ln();
	$PDF->SetFont('helvetica', '', 10);

	// define styles for various bookmark levels
	$bookmark_templates = array();

	/*
	 * The key of the $bookmark_templates array represent the bookmark level (from 0 to n).
	 * The following templates will be replaced with proper content:
	 *     #TOC_DESCRIPTION#    this will be replaced with the bookmark description;
	 *     #TOC_PAGE_NUMBER#    this will be replaced with page number.
	 *
	 * NOTES:
	 *     If you want to align the page number on the right you have to use a monospaced font like courier, otherwise you can left align using any font type.
	 *     The following is just an example, you can get various styles by combining various HTML elements.
	*/

	// A monospaced font for the page number is mandatory to get the right alignment
	$bookmark_templates[0] = '<table border="0" cellpadding="0" cellspacing="0" style="background-color:#EEFAFF"><tr><td width="155mm"><span style="font-family:times;font-weight:bold;font-size:12pt;color:black;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:12pt;color:black;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
	$bookmark_templates[1] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="5mm">&nbsp;</td><td width="150mm"><span style="font-family:times;font-size:11pt;color:green;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:11pt;color:green;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
	$bookmark_templates[2] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="10mm">&nbsp;</td><td width="145mm"><span style="font-family:times;font-size:10pt;color:#666666;"><i>#TOC_DESCRIPTION#</i></span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:10pt;color:#666666;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
	// add other bookmark level templates here ...
	

	// add table of content at page 1
	// (check the example n. 45 for a text-only TOC
	$PDF->addHTMLTOC(1, 'INDEX', $bookmark_templates, true, 'B', array(128, 0, 0));

	// end of TOC page
	$PDF->endTOCPage();

	// . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
	

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_059.pdf', 'D');

	//============================================================+
	// END OF FILE
	//============================================================+
	