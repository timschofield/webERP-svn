<?php
/* This script shows the trend in exchange rates as retrieved from ECB. */

include('includes/session.php');
$Title = _('View Currency Trend');
$ViewTopic= 'Currencies';
$BookMark = 'ExchangeRateTrend';
include('includes/header.php');

$FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];

if ( isset($_GET['CurrencyToShow']) ){
	$CurrencyToShow = $_GET['CurrencyToShow'];
} elseif ( isset($_POST['CurrencyToShow']) ) {
	$CurrencyToShow = $_POST['CurrencyToShow'];
}

// ************************
// SHOW OUR MAIN INPUT FORM
// ************************

echo '<form method="post" id="update" action="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
echo '<p class="page_title_text">
		<img alt="" src="', $RootPath, '/css/', $Theme, '/images/currency.png" title="', _('View Currency Trend'), '" /> ', _('View Currency Trend'), '
	</p>';// Page title.

echo '<fieldset>
		<legend>', _('Select the Currency'), '</legend>'; // First column

$SQL = "SELECT currabrev FROM currencies";
$Result=DB_query($SQL);
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.

// CurrencyToShow Currency Picker
echo '<field>
		<select name="CurrencyToShow" onchange="ReloadForm(update.submit)">';

DB_data_seek($Result, 0);
while ($MyRow=DB_fetch_array($Result)) {
	if ($MyRow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
		if ( $CurrencyToShow==$MyRow['currabrev'] )	{
			echo '<option selected="selected" value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], ' (', $MyRow['currabrev'], ')</option>';
		} else {
			echo '<option value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], ' (', $MyRow['currabrev'], ')</option>';
		}
	}
}
echo '</select>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="submit" value="', _('Accept'), '" />
	</div>
</form>';

// **************
// SHOW OUR GRAPH
// **************
$image = 'http://www.google.com/finance/getchart?q=' . $FunctionalCurrency . '-' . $CurrencyToShow . '&amp;x=CURRENCY&amp;p=3M&amp;i=86400';

echo '<table class="selection">
		<tr>
			<th>
				<div class="centre">
					<b>', $FunctionalCurrency, ' / ', $CurrencyToShow, '</b>
				</div>
			</th>
		</tr>
		<tr>
			<td><img src="', $image, '" alt="', _('Trend Currently Unavailable'), '" /></td>
		</tr>
	</table>';

include('includes/footer.php');
?>