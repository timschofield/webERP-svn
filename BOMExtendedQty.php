<?php
// BOMExtendedQty.php - Quantity Extended Bill of Materials
include ('includes/session.php');

if (isset($_POST['PrintPDF'])) {

	if (!$_POST['Quantity'] or !is_numeric(filter_number_format($_POST['Quantity']))) {
		$_POST['Quantity'] = 1;
	}

	$Result = DB_query("DROP TABLE IF EXISTS tempbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom2");
	$SQL = "CREATE TEMPORARY TABLE passbom (
				part char(20),
				extendedqpa double,
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
	$SQL = "INSERT INTO passbom (part, extendedqpa, sortpart)
			   SELECT bom.component AS part,
					  (" . filter_number_format($_POST['Quantity']) . " * bom.quantity) as extendedqpa,
					   CONCAT(bom.parent,bom.component) AS sortpart
					  FROM bom
			  WHERE bom.parent ='" . $_POST['Part'] . "'
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
					 CONCAT(bom.parent,bom.component) AS sortpart," . $LevelCounter . " as level,
					 bom.workcentreadded,
					 bom.loccode,
					 bom.effectiveafter,
					 bom.effectiveto,
					 (" . filter_number_format($_POST['Quantity']) . " * bom.quantity) as extendedqpa
			FROM bom
			WHERE bom.parent ='" . $_POST['Part'] . "'
			AND bom.effectiveto > CURRENT_DATE
			AND bom.effectiveafter <= CURRENT_DATE";
	$Result = DB_query($SQL);
	//echo "<br />sql is $SQL<br />";
	// This while routine finds the other levels as long as $ComponentCounter - the
	// component counter finds there are more components that are used as
	// assemblies at lower levels
	$ComponentCounter = 1;
	while ($ComponentCounter > 0) {
		$LevelCounter++;
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
					 CONCAT(passbom.sortpart,bom.component) AS sortpart,
					 " . $LevelCounter . " as level,
					 bom.workcentreadded,
					 bom.loccode,
					 bom.effectiveafter,
					 bom.effectiveto,
					 (bom.quantity * passbom.extendedqpa)
			 FROM bom,passbom
			 WHERE bom.parent = passbom.part
			  AND bom.effectiveto > CURRENT_DATE
			  AND bom.effectiveafter <= CURRENT_DATE";
		$Result = DB_query($SQL);

		$Result = DB_query("DROP TABLE IF EXISTS passbom2");
		$Result = DB_query("ALTER TABLE passbom RENAME AS passbom2");
		$Result = DB_query("DROP TABLE IF EXISTS passbom");

		$SQL = "CREATE TEMPORARY TABLE passbom (part char(20),
												extendedqpa decimal(10,3),
												sortpart text) DEFAULT CHARSET=utf8";
		$Result = DB_query($SQL);

		$SQL = "INSERT INTO passbom (part,
									extendedqpa,
									sortpart)
									SELECT bom.component AS part,
											(bom.quantity * passbom2.extendedqpa),
											CONCAT(passbom2.sortpart,bom.component) AS sortpart
									FROM bom
									INNER JOIN passbom2
									ON bom.parent = passbom2.part
									WHERE bom.effectiveto > CURRENT_DATE
										AND bom.effectiveafter <= CURRENT_DATE";
		$Result = DB_query($SQL);

		$SQL = "SELECT COUNT(bom.parent) AS components
					FROM bom
					INNER JOIN passbom
					ON bom.parent = passbom.part
					GROUP BY passbom.part";
		$Result = DB_query($SQL);

		$MyRow = DB_fetch_array($Result);
		$ComponentCounter = $MyRow['components'];

	} // End of while $ComponentCounter > 0
	if (DB_error_no() != 0) {
		$Title = _('Quantity Extended BOM Listing') . ' - ' . _('Problem Report');
		include ('includes/header.php');
		prnMsg(_('The Quantiy Extended BOM Listing could not be retrieved by the SQL because') . ' ' . DB_error_msg(), 'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($Debug == 1) {
			echo '<br />' . $SQL;
		}
		include ('includes/footer.php');
		exit;
	}

	$Tot_Val = 0;
	$SQL = "SELECT tempbom.component,
				   SUM(tempbom.quantity) as quantity,
				   stockmaster.description,
				   stockmaster.decimalplaces,
				   stockmaster.mbflag,
				   (SELECT
					  SUM(locstock.quantity) as invqty
					  FROM locstock
					  INNER JOIN locationusers
						ON locationusers.loccode=locstock.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview=1
					  WHERE locstock.stockid = tempbom.component
					  GROUP BY locstock.stockid) AS qoh,
				   (SELECT
					  SUM(purchorderdetails.quantityord - purchorderdetails.quantityrecd) as netqty
					  FROM purchorderdetails
					  INNER JOIN purchorders
						ON purchorderdetails.orderno=purchorders.orderno
					  INNER JOIN locationusers
						ON locationusers.loccode=purchorders.intostocklocation
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview=1
					  WHERE purchorderdetails.itemcode = tempbom.component
					  AND completed = 0
					  AND (purchorders.status = 'Authorised' OR purchorders.status='Printed')
					  GROUP BY purchorderdetails.itemcode) AS poqty,
				   (SELECT
					  SUM(woitems.qtyreqd - woitems.qtyrecd) as netwoqty
					  FROM woitems INNER JOIN workorders
						ON woitems.wo = workorders.wo
					  INNER JOIN locationusers
						ON locationusers.loccode=workorders.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview=1
					  WHERE woitems.stockid = tempbom.component
					  AND workorders.closed=0
					  GROUP BY woitems.stockid) AS woqty
			  FROM tempbom
			  INNER JOIN stockmaster
				ON tempbom.component = stockmaster.stockid
			  INNER JOIN locationusers
				ON locationusers.loccode=tempbom.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			  GROUP BY tempbom.component,
					   stockmaster.description,
					   stockmaster.decimalplaces,
					   stockmaster.mbflag";
	$Result = DB_query($SQL);
	$ListCount = DB_num_rows($Result);

	$Title = _('Quantity Extended BOM Listing');
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
					<th colspan="8"><h2>', $Title, '</h2>
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print this report'), '" alt="', _('Print'), '" onclick="window.print();" />
					</th>
				</tr>
				<tr>
					<td colspan="7"><h3>
						', $_SESSION['CompanyRecord']['coyname'], '<br />
						', _('Extended Quantity BOM Listing For '), mb_strtoupper($_POST['Part']), '<br />
						', _('Build Quantity:  '), locale_number_format($_POST['Quantity'], 'Variable'), '
					</td>
					<td style="float:right;vertical-align:top;text-align:right">
						', _('Printed On'), ' ', Date($_SESSION['DefaultDateFormat']) . '
					</td></h3>
				</tr>
				<tr>
					<th>', _('Part Number'), '</th>
					<th>', _('M/B'), '</th>
					<th>', _('Part Description'), '</th>
					<th>', _('Build Quantity'), '</th>
					<th>', _('On Hand Quantity'), '</th>
					<th>', _('P.O. Quantity'), '</th>
					<th>', _('W.O. Quantity'), '</th>
					<th>', _('Shortage'), '</th>
				</tr>
			</thead>
			<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		$Difference = $MyRow['quantity'] - ($MyRow['qoh'] + $MyRow['poqty'] + $MyRow['woqty']);
		echo '<tr class="', $CSSClass, '">
					<td>', $MyRow['component'], '</td>
					<td>', $MyRow['mbflag'], '</td>
					<td>', $MyRow['description'], '</td>
					<td class="number">', locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']), '</td>
					<td class="number">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
					<td class="number">', locale_number_format($MyRow['poqty'], $MyRow['decimalplaces']), '</td>
					<td class="number">', locale_number_format($MyRow['woqty'], $MyRow['decimalplaces']), '</td>
					<td class="number">', locale_number_format($Difference, $MyRow['decimalplaces']), '</td>
				</tr>';
	}
	echo '</tbody>
	</table>';
	include ('includes/footer.php');

} else {
	/*The option to print PDF was not hit so display form */

	$Title = _('Quantity Extended BOM Listing');
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
			</field>
			<field>
				<label for="Quantity">', _('Quantity'), ':</label>
				<input type="text" class="number" name="Quantity" required="required" maxlength="11" size="4" />
			</field>
			<field>
				<label for="Select">', _('Selection Option'), ':</label>
				<select name="Select">
					<option selected="selected" value="All">', _('Show All Parts'), '</option>
					<option value="Shortages">', _('Only Show Shortages'), '</option>
				</select>
			</field>
			<field>
				<label for="Fill">', _('Print Option'), ':</label>
				<select name="Fill">
					<option selected="selected" value="yes">', _('Print With Alternating Highlighted Lines'), '</option>
					<option value="no">', _('Plain Print'), '</option>
				</select>
			</field>
			</fieldset>
			<div class="centre">
				<input type="submit" name="PrintPDF" value="', _('View Report'), '" />
			</div>
		</form>';

	include ('includes/footer.php');

}
?>