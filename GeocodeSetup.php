<?php
include ('includes/session.php');
$Title = _('Geocode Maintenance');
include ('includes/header.php');

if (isset($_GET['SelectedParam'])) {
	$SelectedParam = $_GET['SelectedParam'];
} elseif (isset($_POST['SelectedParam'])) {
	$SelectedParam = $_POST['SelectedParam'];
}

$InputError = 0;

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$i = 1;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs are sensible
	$SQL = "SELECT count(geocodeid)
			FROM geocode_param WHERE geocodeid='" . $_POST['GeoCodeID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0] != 0 and !isset($SelectedParam)) {
		$InputError = 1;
		prnMsg(_('That geocode ID already exists in the database'), 'error');
	}

	$Msg = '';

	if (isset($SelectedParam) and $InputError != 1) {

		/*SelectedParam could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the delete code below*/

		if (isset($_POST['GeoCode_Key']) and isset($_POST['GeoCode_Key'])) {
			$SQL = "UPDATE geocode_param SET
					geocode_key='" . $_POST['GeoCode_Key'] . "',
					center_long='" . $_POST['Center_Long'] . "',
					center_lat='" . $_POST['Center_Lat'] . "',
					map_height='" . $_POST['Map_Height'] . "',
					map_width='" . $_POST['Map_Width'] . "',
					map_host='" . $_POST['Map_Host'] . "'
					WHERE geocodeid = '" . $SelectedParam . "'";
		}
		$Msg = _('The geocode status record has been updated');

	} else if ($InputError != 1) {

		/*Selected Param is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new status code form */

		if (isset($_POST['GeoCode_Key']) and $_POST['GeoCode_Key'] > 0) {

			$SQL = "INSERT INTO geocode_param (geocodeid,
												geocode_key,
												center_long,
												center_lat,
												map_height,
												map_width,
												map_host)
					VALUES ('',
							'" . $_POST['GeoCode_Key'] . "',
							'" . $_POST['Center_Long'] . "',
							'" . $_POST['Center_Lat'] . "',
							'" . $_POST['Map_Height'] . "',
							'" . $_POST['Map_Width'] . "',
							'" . $_POST['Map_Host'] . "')";
		} else {
			$SQL = "INSERT INTO geocode_param (geocodeid,
												geocode_key,
												center_long,
												center_lat,
												map_height,
												map_width,
												map_host)
								VALUES ('" . $_POST['GeoCodeID'] . "',
										'" . $_POST['GeoCode_Key'] . "',
										'" . $_POST['Center_Long'] . "',
										'" . $_POST['Center_Lat'] . "',
										'" . $_POST['Map_Height'] . "',
										'" . $_POST['Map_Width'] . "',
										'" . $_POST['Map_Host'] . "')";
		}

		$Msg = _('A new geocode status record has been inserted');
		unset($SelectedParam);
		unset($_POST['GeoCode_Key']);
	}
	//run the SQL from either of the above possibilites
	$Result = DB_query($SQL);
	if ($Msg != '') {
		prnMsg($Msg, 'success');
	}
} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$SQL = "DELETE FROM geocode_param WHERE geocodeid = '" . $_GET['delete'] . "' LIMIT 1";
	$Result = DB_query($SQL);
	prnMsg(_('Geocode deleted'), 'success');
	//end if status code used in customer or supplier accounts
	unset($_GET['delete']);
	unset($SelectedParam);

}

if (!isset($SelectedParam)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedParam will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of status codes will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT geocodeid,
					geocode_key,
					center_long,
					center_lat,
					map_height,
					map_width,
					map_host
			FROM geocode_param";
	$Result = DB_query($SQL);

	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Geocode Setup'), '" alt="" />', _('Setup configuration for Geocoding of Customers and Suppliers'), '
		</p>';

	echo '<div class="page_help_text">', _('Get a google API key at '), '<a href="http://code.google.com/apis/maps/signup.html" target="_blank"> http://code.google.com/apis/maps/signup.html</a></div>';

	echo '<div class="page_help_text">
			', _('Find the lat/long for your map center point at '), '<a href="http://www.batchgeocode.com/lookup/" target="_blank">http://www.batchgeocode.com/lookup/</a>
		</div>';

	prnMsg(_('Set the maps centre point using the Center Longitude and Center Latitude. Set the maps screen size using the height and width in pixels (px)'), 'info');

	echo '<table>';

	echo '<tr>
			<th>', _('Geocode ID'), '</th>
			<th>', _('Geocode Key'), '</th>
			<th>', _('Center Longitude'), '</th>
			<th>', _('Center Latitude'), '</th>
			<th>', _('Map height (px)'), '</th>
			<th>', _('Map width (px)'), '</th>
			<th>', _('Map host'), '</th>
			<th></th>
			<th></th>
		</tr>';

	while ($MyRow = DB_fetch_row($Result)) {

		echo '<tr class="striped_row">
				<td>', $MyRow[0], '</td>
				<td>', $MyRow[1], '</td>
				<td>', $MyRow[2], '</td>
				<td>', $MyRow[3], '</td>
				<td>', $MyRow[4], '</td>
				<td>', $MyRow[5], '</td>
				<td>', $MyRow[6], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedParam=', urlencode($MyRow[0]), '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedParam=', urlencode($MyRow[0]), '&delete=', urlencode($MyRow[0]), '">', _('Delete'), '</a></td>
			</tr>';

	} //END WHILE LIST LOOP
	echo '</table>';

} //end of ifs and buts!
if (isset($SelectedParam)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('Show Defined Geocode Param Codes'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedParam) and ($InputError != 1)) {
		//editing an existing status code
		$SQL = "SELECT geocodeid,
					geocode_key,
					center_long,
					center_lat,
					map_height,
					map_width,
					map_host
				FROM geocode_param
				WHERE geocodeid='" . $SelectedParam . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['GeoCodeID'] = $MyRow['geocodeid'];
		$_POST['GeoCode_Key'] = $MyRow['geocode_key'];
		$_POST['Center_Long'] = $MyRow['center_long'];
		$_POST['Center_Lat'] = $MyRow['center_lat'];
		$_POST['Map_Height'] = $MyRow['map_height'];
		$_POST['Map_Width'] = $MyRow['map_width'];
		$_POST['Map_Host'] = $MyRow['map_host'];

		echo '<input type="hidden" name="SelectedParam" value="' . $SelectedParam . '" />';
		echo '<input type="hidden" name="GeoCodeID" value="' . $_POST['GeoCodeID'] . '" />';
		echo '<p class="page_title_text" ><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/maintenance.png" title="' . _('Geocode Setup') . '" alt="" />' . _('Setup configuration for Geocoding of Customers and Suppliers') . '</p>';
		echo '<fieldset>
				<legend>', _('Edit Geocode Settings'), '</legend>
				<field>
					<label for="GeoCodeID">', _('Geocode Code'), ':</label>
					<div class="fieldtext">', $_POST['GeoCodeID'], '</div>
				</field>';

	} else { //end of if $SelectedParam only do the else when a new record is being entered
		$_POST['GeoCodeID'] = '';
		$_POST['GeoCode_Key'] = '';
		$_POST['Center_Long'] = '';
		$_POST['Center_Lat'] = '';
		$_POST['Map_Height'] = '';
		$_POST['Map_Width'] = '';
		$_POST['Map_Host'] = '';
		echo '<fieldset>
				<legend>', _('Create Geocode Settings'), '</legend>';
	}

	echo '<field>
			<label for="GeoCode_Key">', _('Geocode Key'), ':</label>
			<input type="text" name="GeoCode_Key" value="', $_POST['GeoCode_Key'], '" size="28" maxlength="300" />
		</field>';

	echo '<field>
			<label for="Center_Long">', _('Geocode Center Long'), ':</label>
			<input type="text" name="Center_Long" value="', $_POST['Center_Long'], '" size="28" maxlength="300" />
		</field>';

	echo '<field>
			<label for="Center_Lat">', _('Geocode Center Lat'), ':</label>
			<input type="text" name="Center_Lat" value="', $_POST['Center_Lat'], '" size="28" maxlength="300" />
		</field>';

	echo '<field>
			<label for="Map_Height">', _('Geocode Map Height'), ':</label>
			<input type="text" name="Map_Height" value="', $_POST['Map_Height'], '" size="28" maxlength="300" />
		</field>';

	echo '<field>
			<label for="Map_Width">', _('Geocode Map Width'), ':</label>
			<input type="text" name="Map_Width" value="', $_POST['Map_Width'], '" size="28" maxlength="300" />
		</field>';

	echo '<field>
			<label for="Map_Host">', _('Geocode Host'), ':</label>
			<input type="text" name="Map_Host" value="', $_POST['Map_Host'], '" size="20" maxlength="300" />
		</field>';

	echo '</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="submit" value="', _('Enter Information'), '" />
		</div>
	</form>';

	echo '<div class="page_help_text">
			', _('When ready, click on the link below to run the GeoCode process. This will Geocode all Branches and Suppliers. This may take some time. Errors will be returned to the screen.'), '<br />
			', _('Suppliers and Customer Branches are geocoded when being entered/updated. You can rerun the geocode process from this screen at any time.'), '
		</div>';

	echo '<div class="centre">
			<a href="', $RootPath, '/geocode.php">', _('Run GeoCode process (may take a long time)'), '</a><br />
			<a href="', $RootPath, '/geo_displaymap_customers.php">', _('Display Map of Customer Branches'), '</a><br />
			<a href="', $RootPath, '/geo_displaymap_suppliers.php">', _('Display Map of Suppliers'), '</a>
		</div>';
} //end if record deleted no point displaying form to add record
include ('includes/footer.php');

?>