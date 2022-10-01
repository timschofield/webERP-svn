<?php
// BOMIndented.php - Reverse Indented Bill of Materials - From lowest level component to top level
// assembly
include ('includes/session.php');

if (isset($_POST['PrintPDF'])) {

	$Result = DB_query("DROP TABLE IF EXISTS tempbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom2");
	$SQL = "CREATE TEMPORARY TABLE passbom (
				part char(20),
				sortpart text) DEFAULT CHARSET=utf8";

	$ErrMsg = _('The SQL to create passbom failed with the message');
	$Result = DB_query($SQL, $ErrMsg);

	$SQL = "CREATE TEMPORARY TABLE tempbom (
				parent char(20),
				component char(20),
				sortpart text,
				level int,
				workcentreadded char(5),
				loccode char(5),
				effectiveafter date,
				effectiveto date,
				quantity double) DEFAULT CHARSET=utf8";
	$Result = DB_query($SQL, _('Create of tempbom failed because'));
	// First, find first level of components below requested assembly
	// Put those first level parts in passbom, use COMPONENT in passbom
	// to link to PARENT in bom to find next lower level and accumulate
	// those parts into tempbom
	// This finds the top level
	$SQL = "INSERT INTO passbom (part, sortpart)
			   SELECT bom.parent AS part,
					  CONCAT(bom.component,bom.parent) AS sortpart
					  FROM bom
			  WHERE bom.component ='" . $_POST['Part'] . "'
			  AND bom.effectiveto > CURRENT_DATE
			  AND bom.effectiveafter <= CURRENT_DATE";
	$Result = DB_query($SQL);

	$LevelCounter = 2;
	// $LevelCounter is the level counter
	$SQL = "INSERT INTO tempbom (
				parent,
				component,
				sortpart,
				level,
				workcentreadded,
				loccode,
				effectiveafter,
				effectiveto,
				quantity)
			  SELECT bom.parent,
					 bom.component,
					 CONCAT(bom.component,bom.parent) AS sortpart,
					 " . $LevelCounter . " AS level,
					 bom.workcentreadded,
					 bom.loccode,
					 bom.effectiveafter,
					 bom.effectiveto,
					 bom.quantity
					 FROM bom
			  WHERE bom.component ='" . $_POST['Part'] . "'
			  AND bom.effectiveto > CURRENT_DATE
			  AND bom.effectiveafter <= CURRENT_DATE";
	$Result = DB_query($SQL);

	// This while routine finds the other levels as long as $ComponentCounter - the
	// component counter finds there are more components that are used as
	// assemblies at lower levels
	$ComponentCounter = 1;
	while ($ComponentCounter > 0) {
		$LevelCounter++;
		$SQL = "INSERT INTO tempbom (parent,
									component,
									sortpart,
									level,
									workcentreadded,
									loccode,
									effectiveafter,
									effectiveto,
									quantity)
				  SELECT bom.parent,
						 bom.component,
						 CONCAT(passbom.sortpart,bom.parent) AS sortpart,
						 " . $LevelCounter . " AS level,
						 bom.workcentreadded,
						 bom.loccode,
						 bom.effectiveafter,
						 bom.effectiveto,
						 bom.quantity
				FROM bom,passbom
				WHERE bom.component = passbom.part
				AND bom.effectiveto > CURRENT_DATE
				AND bom.effectiveafter <= CURRENT_DATE";
		$Result = DB_query($SQL);

		$Result = DB_query("DROP TABLE IF EXISTS passbom2");

		$Result = DB_query("ALTER TABLE passbom RENAME AS passbom2");
		$Result = DB_query("DROP TABLE IF EXISTS passbom");

		$SQL = "CREATE TEMPORARY TABLE passbom (
						part char(20),
						sortpart text) DEFAULT CHARSET=utf8";
		$Result = DB_query($SQL);

		$SQL = "INSERT INTO passbom (part, sortpart)
				   SELECT bom.parent AS part,
						  CONCAT(passbom2.sortpart,bom.parent) AS sortpart
				   FROM bom,passbom2
				   WHERE bom.component = passbom2.part
					AND bom.effectiveto > CURRENT_DATE
					AND bom.effectiveafter <= CURRENT_DATE";
		$Result = DB_query($SQL);
		$Result = DB_query("SELECT COUNT(*) FROM bom,passbom WHERE bom.component = passbom.part");

		$MyRow = DB_fetch_row($Result);
		$ComponentCounter = $MyRow[0];

	} // End of while $ComponentCounter > 0
	if (DB_error_no() != 0) {
		$Title = _('Indented BOM Listing') . ' - ' . _('Problem Report');
		include ('includes/header.php');
		prnMsg(_('The Indented BOM Listing could not be retrieved by the SQL because') . ' ' . DB_error_msg(), 'error');
		echo '<br />
			<a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($Debug == 1) {
			echo '<br />' . $SQL;
		}
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT stockmaster.stockid,
				   stockmaster.description
			  FROM stockmaster
			  WHERE stockid = '" . $_POST['Part'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$Assembly = $_POST['Part'];
	$AssemblyDesc = $MyRow['description'];

	//	PrintHeader($PDF, $YPos, $PageNumber, $Page_Height, $Top_Margin, $Left_Margin, $Page_Width, $Right_Margin, $AssemblyDesc);
	$Tot_Val = 0;

	$SQL = "SELECT tempbom.*,
				   stockmaster.description,
				   stockmaster.mbflag,
                   stockmaster.units
				FROM tempbom
				INNER JOIN stockmaster
					ON tempbom.parent = stockmaster.stockid
				INNER JOIN locationusers
					ON locationusers.loccode=tempbom.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canview=1
				ORDER BY sortpart";
	$Result = DB_query($SQL);

	$ListCount = DB_num_rows($Result);

	$Title = _('Indented BOM Listing');
	include ('includes/header.php');

	if ($_POST['Fill'] == 'yes') {
		$CSSClass = 'striped_row';
	} else {
		$CSSClass = '';
	}
	echo '<div class="toplink">
			<a class="noPrint" href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Select another BOM'), '</a>
		</div>';

	echo '<table>
			<thead>
				<tr class="noPrint">
					<th colspan="9"><h2>', $Title, '</h2>
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print this report'), '" alt="', _('Print'), '" onclick="window.print();" />
					</th>
				</tr>
				<tr>
					<td colspan="8"><h3>
						', $_SESSION['CompanyRecord']['coyname'], '<br />
						', _('Indented BOM Listing For Component'), ' ', mb_strtoupper($_POST['Part']), '<br />
					</td>
					<td style="float:right;vertical-align:top;text-align:right">
						', _('Printed On'), ' ', Date($_SESSION['DefaultDateFormat']) . '
					</td></h3>
				</tr>
				<tr>
					<th>', _('Part Number'), '</th>
					<th>', _('M/B'), '</th>
					<th>', _('Part Description'), '</th>
					<th>', _('Location'), '</th>
					<th>', _('Work Centre'), '</th>
					<th>', _('Quantity'), '</th>
					<th>', _('Units'), '</th>
					<th>', _('From Date'), '</th>
					<th>', _('To Date'), '</th>
				</tr>
			</thead>
			<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="', $CSSClass, '">
					<td>', indent($MyRow['level'] - 2), $MyRow['component'], '</td>
					<td>', indent($MyRow['level'] - 2), $MyRow['mbflag'], '</td>
					<td>', indent($MyRow['level'] - 2), $MyRow['description'], '</td>
					<td>', indent($MyRow['level'] - 2), $MyRow['loccode'], '</td>
					<td>', indent($MyRow['level'] - 2), $MyRow['workcentreadded'], '</td>
					<td class="number">', indent($MyRow['level'] - 2), locale_number_format($MyRow['quantity'], 'Variable'), '</td>
					<td>', indent($MyRow['level'] - 2), $MyRow['units'], '</td>
					<td>', indent($MyRow['level'] - 2), ConvertSQLDate($MyRow['effectiveafter']), '</td>
					<td>', indent($MyRow['level'] - 2), ConvertSQLDate($MyRow['effectiveto']), '</td>
				</tr>';
	}
	echo '</tbody>
	</table>';
	include ('includes/footer.php');

} else {
	/*The option to print PDF was not hit so display form */

	$Title = _('Reverse Indented BOM Listing');
	include ('includes/header.php');

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<legend>', _('Select Report Criteria'), '</legend>
			<field>
				<label for="Part">', _('Part'), ':</label>
				<input type ="text" name="Part" autofocus="autofocus" required="required" maxlength="20" size="20" />
				<fieldhelp>', _('The component part to look up'), '</fieldhelp>
			</field>
			<field>
				<label for="Fill">', _('Print Option'), ':</label>
				<select name="Fill">
					<option selected="selected" value="yes">', _('Print With Alternating Highlighted Lines'), '</option>
					<option value="no">', _('Plain Print'), '</option>
				</select>
				<fieldhelp>', _('Select the print options for the report'), '</fieldhelp>
			</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="PrintPDF" value="', _('View Report'), '" />
		</div>
	</form>';

	include ('includes/footer.php');

}
/*end of else not PrintPDF */

function indent($Level) {
	for ($i = 0;$i < $Level;$i++) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
}

?>