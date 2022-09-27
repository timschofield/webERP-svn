<?php
//============================================================+
// File name   : example_014.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 014 for TCPDF class
//               Javascript Form and user rights (only works on Adobe Acrobat)
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
 * @abstract TCPDF - Example: Javascript Form and user rights (only works on Adobe Acrobat)
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
$PDF->SetTitle('TCPDF Example 014');
$PDF->SetSubject('TCPDF Tutorial');
$PDF->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$PDF->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 014', PDF_HEADER_STRING);

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
	

	// IMPORTANT: disable font subsetting to allow users editing the document
	$PDF->setFontSubsetting(false);

	// set font
	$PDF->SetFont('helvetica', '', 10, '', false);

	// add a page
	$PDF->AddPage();

	/*
	It is possible to create text fields, combo boxes, check boxes and buttons.
	Fields are created at the current position and are given a name.
	This name allows to manipulate them via JavaScript in order to perform some validation for instance.
	*/

	// set default form properties
	$PDF->setFormDefaultProp(array('lineWidth' => 1, 'borderStyle' => 'solid', 'fillColor' => array(255, 255, 200), 'strokeColor' => array(255, 128, 128)));

	$PDF->SetFont('helvetica', 'BI', 18);
	$PDF->Cell(0, 5, 'Example of Form', 0, 1, 'C');
	$PDF->Ln(10);

	$PDF->SetFont('helvetica', '', 12);

	// First name
	$PDF->Cell(35, 5, 'First name:');
	$PDF->TextField('firstname', 50, 5);
	$PDF->Ln(6);

	// Last name
	$PDF->Cell(35, 5, 'Last name:');
	$PDF->TextField('lastname', 50, 5);
	$PDF->Ln(6);

	// Gender
	$PDF->Cell(35, 5, 'Gender:');
	$PDF->ComboBox('gender', 30, 5, array(array('', '-'), array('M', 'Male'), array('F', 'Female')));
	$PDF->Ln(6);

	// Drink
	$PDF->Cell(35, 5, 'Drink:');
	//$PDF->RadioButton('drink', 5, array('readonly' => 'true'), array(), 'Water');
	$PDF->RadioButton('drink', 5, array(), array(), 'Water');
	$PDF->Cell(35, 5, 'Water');
	$PDF->Ln(6);
	$PDF->Cell(35, 5, '');
	$PDF->RadioButton('drink', 5, array(), array(), 'Beer', true);
	$PDF->Cell(35, 5, 'Beer');
	$PDF->Ln(6);
	$PDF->Cell(35, 5, '');
	$PDF->RadioButton('drink', 5, array(), array(), 'Wine');
	$PDF->Cell(35, 5, 'Wine');
	$PDF->Ln(6);
	$PDF->Cell(35, 5, '');
	$PDF->RadioButton('drink', 5, array(), array(), 'Milk');
	$PDF->Cell(35, 5, 'Milk');
	$PDF->Ln(10);

	// Newsletter
	$PDF->Cell(35, 5, 'Newsletter:');
	$PDF->CheckBox('newsletter', 5, true, array(), array(), 'OK');

	$PDF->Ln(10);
	// Address
	$PDF->Cell(35, 5, 'Address:');
	$PDF->TextField('address', 60, 18, array('multiline' => true, 'lineWidth' => 0, 'borderStyle' => 'none'), array('v' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'dv' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'));
	$PDF->Ln(19);

	// Listbox
	$PDF->Cell(35, 5, 'List:');
	$PDF->ListBox('listbox', 60, 15, array('', 'item1', 'item2', 'item3', 'item4', 'item5', 'item6', 'item7'), array('multipleSelection' => 'true'));
	$PDF->Ln(20);

	// E-mail
	$PDF->Cell(35, 5, 'E-mail:');
	$PDF->TextField('email', 50, 5);
	$PDF->Ln(6);

	// Date of the day
	$PDF->Cell(35, 5, 'Date:');
	$PDF->TextField('date', 30, 5, array(), array('v' => date('Y-m-d'), 'dv' => date('Y-m-d')));
	$PDF->Ln(10);

	$PDF->SetX(50);

	// Button to validate and print
	$PDF->Button('print', 30, 10, 'Print', 'Print()', array('lineWidth' => 2, 'borderStyle' => 'beveled', 'fillColor' => array(128, 196, 255), 'strokeColor' => array(64, 64, 64)));

	// Reset Button
	$PDF->Button('reset', 30, 10, 'Reset', array('S' => 'ResetForm'), array('lineWidth' => 2, 'borderStyle' => 'beveled', 'fillColor' => array(128, 196, 255), 'strokeColor' => array(64, 64, 64)));

	// Submit Button
	$PDF->Button('submit', 30, 10, 'Submit', array('S' => 'SubmitForm', 'F' => 'http://localhost/printvars.php', 'Flags' => array('ExportFormat')), array('lineWidth' => 2, 'borderStyle' => 'beveled', 'fillColor' => array(128, 196, 255), 'strokeColor' => array(64, 64, 64)));

	// Form validation functions
	$js = <<<EOD
function CheckField(name,message) {
	var f = getField(name);
	if(f.value == '') {
	    app.alert(message);
	    f.setFocus();
	    return false;
	}
	return true;
}
function Print() {
	if(!CheckField('firstname','First name is mandatory')) {return;}
	if(!CheckField('lastname','Last name is mandatory')) {return;}
	if(!CheckField('gender','Gender is mandatory')) {return;}
	if(!CheckField('address','Address is mandatory')) {return;}
	print();
}
EOD;
	

	// Add Javascript code
	$PDF->IncludeJS($js);

	// ---------------------------------------------------------
	

	//Close and output PDF document
	$PDF->Output('example_014.pdf', 'D');

	//============================================================+
	// END OF FILE
	//============================================================+
	