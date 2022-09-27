<?php
/* -----------------------------------------------------------------------------------------------
This class was an extension to the FPDF class to use the syntax of the R&OS pdf.php class,
the syntax that KwaMoja original reports were written in.
Due to limitation of R&OS class for foreign character support, this wrapper class was
written to allow the same code base to use the more functional fpdf.class by Olivier Plathey.

However, due to limitations of FPDF class for UTF-8 support, now this class inherits from
the TCPDF class by Nicola Asuni.

Work to move from FPDF to TCPDF by:
Javier de Lorenzo-Cáceres <info@civicom.eu>
----------------------------------------------------------------------------------------------- */
require_once (dirname(__FILE__) . '/tcpdf/config/lang/eng.php');
require_once (dirname(__FILE__) . '/tcpdf/tcpdf.php');

if (!class_exists('Cpdf', false)) {

	class Cpdf extends TCPDF {

		public function __construct($DocOrientation = 'P', $DocUnits = 'pt', $DocPaper = 'A4') {

			parent::__construct($DocOrientation, $DocUnits, $DocPaper, true, 'utf-8', false);

			$this->setuserpdffont();
		}

		protected function setuserpdffont() {

			if (session_id() == '') {
				session_start();
			}

			if (isset($_SESSION['PDFLanguage'])) {

				$UserPdfLang = $_SESSION['PDFLanguage'];

				switch ($UserPdfLang) {
					case 0:
						$UserPdfFont = 'times';
					break;
					case 1:
						$UserPdfFont = 'javierjp';
					break;
					case 2:
						$UserPdfFont = 'javiergb';
					break;
					case 3:
						$UserPdfFont = 'freeserif';
					break;
				}

			} else {
				$UserPdfFont = 'helvetica';
			}

			$this->SetFont($UserPdfFont, '', 11);
			//     SetFont($family, $style='', $size=0, $fontfile='')
			
		}

		function newPage() {
			/* Javier: 	$this->setPrintHeader(false);  This is not a removed call but added in. */
			$this->AddPage();
		}

		function line($x1, $y1, $x2, $y2, $style = array()) {
			// Javier	FPDF::line($x1, $this->h-$y1, $x2, $this->h-$y2);
			// Javier: width, color and style might be edited
			TCPDF::Line($x1, $this->h - $y1, $x2, $this->h - $y2, $style);
		}

		function addText($XPos, $YPos, $fontsize, $text /*,$angle=0,$wordSpaceAdjust=0*/
		) {
			// $XPos = cell horizontal coordinate from page left side to cell left side in dpi (72dpi = 25.4mm).
			// $YPos = cell vertical coordinate from page bottom side to cell top side in dpi (72dpi = 25.4mm).
			// $fontsize = font size in dpi (72dpi = 25.4mm).
			// Javier	$text = html_entity_decode($text);
			$this->SetFontSize($fontsize); // Public function SetFontSize() in ~/includes/tcpdf/tcpdf.php.
			$this->Text($XPos, $this->h - $YPos, $text); // Public function Text() in ~/includes/tcpdf/tcpdf.php.
			
		}

		function addTextWrap($XPos, $YPos, $Width, $Height, $Text, $Align = 'J', $border = 0, $fill = 0) {
			// R&OS version 0.12.2: "addTextWrap function is no more, use addText instead".
			// Adds text to the page and returns the balance of the string that could not fit in the width.
			// $XPos = cell horizontal coordinate from page left side to cell left side in dpi (72dpi = 25.4mm).
			// $YPos = cell vertical coordinate from page bottom side to cell bottom side in dpi (72dpi = 25.4mm).
			// $Width = Cell (line) width in dpi (72dpi = 25.4mm).
			// $Height = Font size in dpi (72dpi = 25.4mm).
			// $Text = Text to be split in portion to be add to the page and the remainder to be returned.
			// $Align = 'left', 'center', 'centre', 'full' or 'right'.
			//some special characters are html encoded
			//this code serves to make them appear human readable in pdf file
			$Text = html_entity_decode($Text, ENT_QUOTES, 'UTF-8'); // Convert all HTML entities to their applicable characters.
			

			$this->x = $XPos;
			$this->y = $this->h - $YPos - $Height; //RChacon: This -$Height is the difference in yPos between AddText() and AddTextWarp(). It is better "$this->y = $this->h-$YPos", but that requires to recode all the pdf generator scripts.
			

			switch ($Align) { // Translate from Pdf-Creator to TCPDF.
					
				case 'left':
					$Align = 'L';
				break;
				case 'right':
					$Align = 'R';
				break;
				case 'center':
					$Align = 'C';
				break;
				case 'centre':
					$Align = 'C';
				break;
				case 'full':
					$Align = 'J';
				break;
				default:
					$Align = 'L';
				break;
			}
			$this->SetFontSize($Height); // Public function SetFontSize() in ~/includes/tcpdf/tcpdf.php.
			

			if ($Width == 0) {
				$Width = $this->w - $this->rMargin - $this->x; // Line_width = Page_width - Right_margin - Cell_horizontal_coordinate($XPos).
				
			}
			$wmax = ($Width - 2 * $this->cMargin);
			$s = str_replace("\r", '', $Text);
			$s = str_replace("\n", ' ', $s);
			$s = trim($s) . ' ';
			$nb = mb_strlen($s, 'UTF-8');
			$b = 0;
			if ($border) {
				if ($border == 1) {
					$border = 'LTRB';
					$b = 'LRT';
					$b2 = 'LR';
				} else {
					$b2 = '';
					if (is_int(mb_strpos($border, 'L', 0, 'UTF-8'))) {
						$b2.= 'L';
					}
					if (is_int(mb_strpos($border, 'R', 0, 'UTF-8'))) {
						$b2.= 'R';
					}
					$b = is_int(mb_strpos($border, 'T', 0, 'UTF-8')) ? $b2 . 'T' : $b2;
				}
			}
			$sep = - 1;
			$i = 0;
			$l = $ls = 0;
			$ns = 0;
			$cw = $this->GetStringWidth($s, '', '', 0, true);
			while ($i < $nb) {
				/*$c=$s{$i};*/
				$c = mb_substr($s, $i, 1, 'UTF-8');
				if ($c === ' ' and $i > 0) {
					$sep = $i;
					$ls = $l;
					$ns++;
				}
				if (isset($cw[$i])) {
					$l+= $cw[$i];
				}
				if ($l > $wmax) {
					break;
				} else {
					$i++;
				}
			}
			if ($sep == - 1) {
				if ($i == 0) {
					$i++;
				}

				if (isset($this->ws) and $this->ws > 0) {
					$this->ws = 0;
					$this->_out('0 Tw');
				}
			} else {
				if ($Align == 'J') {
					$this->ws = ($ns > 1) ? ($wmax - $ls) / ($ns - 1) : 0;
					$this->_out(sprintf('%.3f Tw', $this->ws * $this->k));
				}
			}
			$sep = $i;

			$this->Cell($Width, $Height, mb_substr($s, 0, $sep, 'UTF-8'), $b, 2, $Align, $fill);
			$this->x = $this->lMargin;
			return mb_substr($s, $sep, $nb - $sep, 'UTF-8');
		} // End function addTextWrap.
		

		function addInfo($label, $Value) {
			if ($label == 'Creator') {

				$this->SetCreator(PDF_CREATOR);
			}
			if ($label == 'Author') {
				$this->SetAuthor($Value);
			}
			if ($label == 'Title') {
				$this->SetTitle($Value);
			}
			if ($label == 'Subject') {
				$this->SetSubject($Value);
			}
			if ($label == 'Keywords') {
				$this->SetKeywords($Value);
			}
		}

		function addJpegFromFile($file, $x, $YPos, $width, $height) {
			// Puts an image in the page.
			// $file (string) Name of the file containing the image.
			// $x (float) Abscissa from left border to the upper-left corner (LTR).
			// $this->h is the page height.
			// $YPos Ordinate of upper-left corner. WARNING: Measured from bottom left corner!
			// $width (float) Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
			// $height (float) Height of the image in the page.
			$this->Image($file, $x, $this->h - $YPos - $height, $width, $height); // Public function Image() in ~/includes/tcpdf/tcpdf.php.
			
		}

		/*
		 * Next Two functions are adopted from R&OS pdf class
		*/

		/**
		 * draw a part of an ellipse
		 */
		function partEllipse($x0, $y0, $astart, $afinish, $r1, $r2 = 0, $angle = 0, $nSeg = 8) {
			$this->ellipse($x0, $y0, $r1, $r2, $angle, $nSeg, $astart, $afinish, 0);
		}

		/**
		 * draw an ellipse
		 * note that the part and filled ellipse are just special cases of this function
		 *
		 * draws an ellipse in the current line style
		 * centered at $x0,$y0, radii $r1,$r2
		 * if $r2 is not set, then a circle is drawn
		 * nSeg is not allowed to be less than 2, as this will simply draw a line (and will even draw a
		 * pretty crappy shape at 2, as we are approximating with bezier curves.
		 */
		function ellipse($x0, $y0, $r1, $r2 = 0, $angle = 0, $nSeg = 8, $astart = 0, $afinish = 360, $close = 1, $fill = 0, $fill_color = array(), $nc = 8) {

			if ($r1 == 0) {
				return;
			}
			if ($r2 == 0) {
				$r2 = $r1;
			}
			if ($nSeg < 2) {
				$nSeg = 2;
			}

			$astart = deg2rad((float)$astart);
			$afinish = deg2rad((float)$afinish);
			$totalAngle = $afinish - $astart;

			$dt = $totalAngle / $nSeg;
			$dtm = $dt / 3;

			if ($angle != 0) {
				$a = - 1 * deg2rad((float)$angle);
				$tmp = "\n q ";
				$tmp.= sprintf('%.3f', cos($a)) . ' ' . sprintf('%.3f', (-1.0 * sin($a))) . ' ' . sprintf('%.3f', sin($a)) . ' ' . sprintf('%.3f', cos($a)) . ' ';
				$tmp.= sprintf('%.3f', $x0) . ' ' . sprintf('%.3f', $y0) . ' cm';
				$x0 = 0;
				$y0 = 0;
			} else {
				$tmp = '';
			}

			$t1 = $astart;
			$a0 = $x0 + $r1 * cos($t1);
			$b0 = $y0 + $r2 * sin($t1);
			$c0 = - $r1 * sin($t1);
			$d0 = $r2 * cos($t1);

			$tmp.= "\n" . sprintf('%.3f', $a0) . ' ' . sprintf('%.3f', $b0) . ' m ';
			for ($i = 1;$i <= $nSeg;$i++) {
				// draw this bit of the total curve
				$t1 = $i * $dt + $astart;
				$a1 = $x0 + $r1 * cos($t1);
				$b1 = $y0 + $r2 * sin($t1);
				$c1 = - $r1 * sin($t1);
				$d1 = $r2 * cos($t1);
				$tmp.= "\n" . sprintf('%.3f', ($a0 + $c0 * $dtm)) . ' ' . sprintf('%.3f', ($b0 + $d0 * $dtm));
				$tmp.= ' ' . sprintf('%.3f', ($a1 - $c1 * $dtm)) . ' ' . sprintf('%.3f', ($b1 - $d1 * $dtm)) . ' ' . sprintf('%.3f', $a1) . ' ' . sprintf('%.3f', $b1) . ' c';
				$a0 = $a1;
				$b0 = $b1;
				$c0 = $c1;
				$d0 = $d1;
			}
			if ($fill) {
				//$this->objects[$this->currentContents]['c']
				$tmp.= ' f';
			} else {
				if ($close) {
					$tmp.= ' s'; // small 's' signifies closing the path as well
					
				} else {
					$tmp.= ' S';
				}
			}
			if ($angle != 0) {
				$tmp.= ' Q';
			}
			$this->_out($tmp);
		}

		/* Javier:
		A file's name is needed if we don't want file extension to be .php
		TCPDF has a different behaviour than FPDF, the recursive scripts needs D.
		The admin/user may change I to D to force all pdf to be downloaded or open in a desktop app instead the browser plugin, but not vice-versa.
		The admin/user may change I and D to F to save all pdf in the server for Document Management.
		*/

		function OutputI($DocumentFilename = 'Document.pdf') {
			if (($DocumentFilename == null) or ($DocumentFilename == '')) {
				$DocumentFilename = _('Document.pdf');
			}
			$this->Output($DocumentFilename, 'I');
		}

		function OutputD($DocumentFilename = 'Document.pdf') {
			if (($DocumentFilename == null) or ($DocumentFilename == '')) {
				$DocumentFilename = _('Document.pdf');
			}
			$this->Output($DocumentFilename, 'D');
		}

		function Rectangle($x, $YPos, $width, $height) {
			// Draws a rectangle.
			// $x (float) Abscissa from left border to the upper-left corner (LTR).
			// $this->h is the page height.
			// $YPos Ordinate of upper-left corner. WARNING: Measured from bottom left corner!
			// $width (float) Rectangle width.
			// $height (float) Rectangle height.
			$this->Rect($x, $this->h - $YPos, $width, $height); // Public function Rect() in ~/includes/tcpdf/tcpdf.php.
			
		}

		function RoundRectangle($x, $YPos, $width, $height, $rx, $ry) {
			// Draws a rounded rectangle.
			// $x (float) Abscissa from left border to the upper-left corner (LTR).
			// $this->h is the page height.
			// $YPos Ordinate of upper-left corner. WARNING: Measured from bottom left corner!
			// $width (float) Rectangle width.
			// $height (float) Rectangle height.
			// $rx (float) the x-axis radius of the ellipse used to round off the corners of the rectangle.
			// $ry (float) the y-axis radius of the ellipse used to round off the corners of the rectangle.
			$this->RoundedRectXY($x, $this->h - $YPos, $width, $height, $rx, $ry); // Public function RoundedRectXY() in ~/includes/tcpdf/tcpdf.php.
			
		}

	} // end of class
	
	} //end if  Cpdf class exists already
	
?>