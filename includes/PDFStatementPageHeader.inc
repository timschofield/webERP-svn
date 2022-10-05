<?php
/* PDFlib code to set up a new page */

$Perforation = $Page_Width - $Right_Margin - 160;

$YPos = $Page_Height - $Top_Margin;

// Company Logo:
/*$PDF->addJpegFromFile($_SESSION['LogoFile'], $Page_Width/2-130, $YPos-80, 0, 60);*/
$PDF->Image(
	$_SESSION['LogoFile'],// Name of the file containing the image.
	$Page_Width/2-130,// Abscissa from left border to the upper-left corner (LTR).
	$Page_Height -($YPos-80) -(60),// Ordinate from top border to the upper-left corner (LTR).
	0,// Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
	60,// Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
	''// Image format. If not specified, the type is inferred from the file extension.
);// Public function Image() in ~/includes/tcpdf/tcpdf.php

// Title
$FontSize =15;
$XPos = $Page_Width/2 - 110;
$PDF->addText($XPos, $YPos,$FontSize, _('Statement') );

$FontSize = 12;
$PDF->addText($XPos + 70, $YPos,$FontSize, ' '. _('as of').' ' . Date($_SESSION['DefaultDateFormat']));

// Remittance header

$YPosR = $YPos;
$FontSize = 10;
$LineHeight = 13;
$LineCountR = 0;
$Remit1 = $Perforation +2;

$PDF->addText($Remit1, $YPosR-$LineCountR*$LineHeight,$FontSize, _('Remittance Advice') );
$LineCountR += 1;
$PDF->addText($Remit1, $YPosR-$LineCountR*$LineHeight,$FontSize, _('Statement dated').' ' . Date($_SESSION['DefaultDateFormat']));
$LineCountR += 1;
$PDF->addText($Remit1, $YPosR-$LineCountR*$LineHeight,$FontSize, _('Page').': ' . $PageNumber);

/*Also show the page number on the main section */
$PDF->addText($Perforation-50, $YPos,$FontSize, _('Page').': ' . $PageNumber);

/*Now print out company info at the top left */

$XPos = $Left_Margin;
$YPos = $Page_Height - $Top_Margin - 20;

$FontSize = 10;
$LineHeight = 13;
$LineCount = 0;

$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $_SESSION['CompanyRecord']['coyname']);

$FontSize = 8;
$LineHeight = 10;

if ($_SESSION['CompanyRecord']['regoffice1'] <> '') {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
}
if ($_SESSION['CompanyRecord']['regoffice2'] <> '') {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
}
if (($_SESSION['CompanyRecord']['regoffice3'] <> '') OR ($_SESSION['CompanyRecord']['regoffice4'] <> '') OR ($_SESSION['CompanyRecord']['regoffice5'] <> '')) {
  $LineCount += 1;
  $PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);  // country in 6 not printed
}
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Phone') . ':' . $_SESSION['CompanyRecord']['telephone']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight,$FontSize, _('Fax') . ': ' . $_SESSION['CompanyRecord']['fax']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, _('Email') . ': ' . $_SESSION['CompanyRecord']['email']);

/*Now the remittance section just company and return postal address */

$FontSize = 10;
$LineHeight = 13;

$LineCountR += 2;
$PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight,$FontSize, _('Please send with remittance to').':');
$LineCountR += 1;
$PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight, $FontSize, $_SESSION['CompanyRecord']['coyname']);
if ($_SESSION['CompanyRecord']['regoffice1'] <> '') {
  $LineCountR += 1;
  $PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice1']);
}
if ($_SESSION['CompanyRecord']['regoffice2'] <> '') {
  $LineCountR += 1;
  $PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice2']);
}
if (($_SESSION['CompanyRecord']['regoffice3'] <> '') OR ($_SESSION['CompanyRecord']['regoffice4'] <> '') OR ($_SESSION['CompanyRecord']['regoffice5'] <> '')) {
  $LineCountR += 1;
  $PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight,$FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);  // country in 6 not printed
}

/*Now the customer details and statement address */

$XPos = $Left_Margin+20;
$YPos = $Page_Height - $Top_Margin - 120;

$LineCount = 0;

$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $StmtHeader['name']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $StmtHeader['address1']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $StmtHeader['address2']);
$LineCount += 1;
$PDF->addText($XPos, $YPos-$LineCount*$LineHeight, $FontSize, $StmtHeader['address3'] . ' ' . $StmtHeader['address4']  . ' ' . $StmtHeader['address5']  . ' ' . $StmtHeader['address6']);

$YPos = $Page_Height - $Top_Margin - 120;

/*Now note the customer code in the remittance section too */

$FontSize=10;
$LineCountR += 2;
$PDF->addText($Perforation+1, $YPosR-$LineCountR*$LineHeight,$FontSize, _('Customer Code').': ' . $StmtHeader['debtorno']);

$FontSize=8;
$XPos = $Page_Width/2 - 60;
$PDF->addText($XPos, $YPos,$FontSize, _('All amounts stated in') . ': ' . _($StmtHeader['currency']));
$YPos -= $line_height;
$PDF->addText($XPos, $YPos,$FontSize, $StmtHeader['terms']);

$YPos = $Page_Height - $Top_Margin - 180;
//$YPos -= $line_height;
$XPos = $Left_Margin;

// Draws a rounded rectangle around the statement details:
$PDF->RoundedRect(
	($Left_Margin),// Abscissa of upper-left corner from left border (LTR).
	$Page_Height -($YPos),// Ordinate of upper-left corner from top border (LTR).
	($Perforation) -($Left_Margin),// Width.
	($YPos) -($Bottom_Margin),// Height.
	10,// The radius of the circle used to round off the corners of the rectangle.
	'1111',// Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111").
	'',// Style of rendering. See the getPathPaintOperator() function for more information. Default value: empty ("").
	array(),// Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array).
	array()// Fill color. Format: array(GREY) or array(R,G,B) or array(C,M,Y,K) or array(C,M,Y,K,SpotColorName). Default value: default color (empty array).
);// Public function RoundedRect() in ~/includes/tcpdf/tcpdf.php

// Draws a rounded rectangle around the remittance advice section:
$PDF->RoundedRect(
	($Perforation+1),// Abscissa of upper-left corner from left border (LTR).
	$Page_Height -($YPos),// Ordinate of upper-left corner from top border (LTR).
	($Page_Width-$Right_Margin) -($Perforation+1),// Width.
	($YPos) -($Bottom_Margin),// Height.
	10,// The radius of the circle used to round off the corners of the rectangle.
	'1111',// Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111").
	'',// Style of rendering. See the getPathPaintOperator() function for more information. Default value: empty ("").
	array(),// Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array).
	array()// Fill color. Format: array(GREY) or array(R,G,B) or array(C,M,Y,K) or array(C,M,Y,K,SpotColorName). Default value: default color (empty array).
);// Public function RoundedRect() in ~/includes/tcpdf/tcpdf.php

$YPos -= $line_height;
$FontSize =10;
/*Set up headings */
$PDF->addText($Left_Margin+1, $YPos,$FontSize, _('Trans Type') );
$PDF->addText($Left_Margin+100, $YPos,$FontSize, _('Number') );
$PDF->addText($Left_Margin+210, $YPos,$FontSize, _('Date') );
$PDF->addText($Left_Margin+300, $YPos,$FontSize, _('Charges') );
$PDF->addText($Left_Margin+382, $YPos,$FontSize, _('Credits') );
$PDF->addText($Left_Margin+459, $YPos,$FontSize, _('Allocated') );
$PDF->addText($Left_Margin+536, $YPos,$FontSize, _('Outstanding') );

/*Set up remittance section headings */
$FontSize=8;
$PDF->addText($Perforation+10, $YPos,$FontSize, _('Trans') );
$PDF->addText($Perforation+55, $YPos,$FontSize, _('Number') );
$PDF->addText($Perforation+100, $YPos,$FontSize, _('Outstanding') );

$YPos -= $line_height;
/*draw a line */
$PDF->line($Page_Width-$Right_Margin, $YPos,$XPos, $YPos);

$YPos -= $line_height;
$XPos = $Left_Margin;

?>