<?php
include ('includes/DefineContractClass.php');

include ('includes/session.php');
$Title = _('Contract Other Requirements');

$Identifier = $_GET['identifier'];

/* If a contract header doesn't exist, then go to
 * Contracts.php to create one
*/

if (!isset($_SESSION['Contract' . $Identifier])) {
	header('Location:' . $RootPath . '/Contracts.php');
	exit;
}
$ViewTopic = 'Contracts';
$BookMark = 'AddToContract';
include ('includes/header.php');

if (isset($_POST['UpdateLines']) or isset($_POST['BackToHeader'])) {
	if ($_SESSION['Contract' . $Identifier]->Status != 2) { //dont do anything if the customer has committed to the contract
		foreach ($_SESSION['Contract' . $Identifier]->ContractReqts as $ContractComponentID => $ContractRequirementItem) {

			if (filter_number_format($_POST['Qty' . $ContractComponentID]) == 0) {
				//this is the same as deleting the line - so delete it
				$_SESSION['Contract' . $Identifier]->Remove_ContractRequirement($ContractComponentID);
			} else {
				$_SESSION['Contract' . $Identifier]->ContractReqts[$ContractComponentID]->Quantity = filter_number_format($_POST['Qty' . $ContractComponentID]);
				$_SESSION['Contract' . $Identifier]->ContractReqts[$ContractComponentID]->CostPerUnit = filter_number_format($_POST['CostPerUnit' . $ContractComponentID]);
				$_SESSION['Contract' . $Identifier]->ContractReqts[$ContractComponentID]->Requirement = $_POST['Requirement' . $ContractComponentID];
			}
		} // end loop around the items on the contract requirements array
		
	} // end if the contract is not currently committed to by the customer
	
} // end if the user has hit the update lines or back to header buttons


if (isset($_POST['BackToHeader'])) {
	echo '<meta http-equiv="Refresh" content="0; url=', $RootPath, '/Contracts.php?identifier=', urlencode($Identifier), '" />';
	echo '<br />';
	prnMsg(_('You should automatically be forwarded to the Contract page. If this does not happen perhaps the browser does not support META Refresh') . '<a href="' . $RootPath . '/Contracts.php?identifier=' . urlencode($Identifier) . '">' . _('click here') . '</a> ' . _('to continue'), 'info');
	include ('includes/footer.php');
	exit;
}

if (isset($_GET['Delete'])) {
	if ($_SESSION['Contract' . $Identifier]->Status != 2) {
		$_SESSION['Contract' . $Identifier]->Remove_ContractRequirement($_GET['Delete']);
	} else {
		prnMsg(_('The other contract requirements cannot be altered because the customer has already placed the order'), 'warn');
	}
}
if (isset($_POST['EnterNewRequirement'])) {
	$InputError = false;
	if (!is_numeric(filter_number_format($_POST['Quantity']))) {
		prnMsg(_('The quantity of the new requirement is expected to be numeric'), 'error');
		$InputError = true;
	}
	if (!is_numeric(filter_number_format($_POST['CostPerUnit']))) {
		prnMsg(_('The cost per unit of the new requirement is expected to be numeric'), 'error');
		$InputError = true;
	}
	if (!$InputError) {
		$_SESSION['Contract' . $Identifier]->Add_To_ContractRequirements($_POST['RequirementDescription'], filter_number_format($_POST['Quantity']), filter_number_format($_POST['CostPerUnit']));
		unset($_POST['RequirementDescription']);
		unset($_POST['Quantity']);
		unset($_POST['CostPerUnit']);
	}
}

/* This is where the other requirement as entered/modified should be displayed reflecting any deletions or insertions*/

echo '<form name="ContractReqtsForm" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', urlencode($Identifier), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/contract.png" title="', _('Contract Other Requirements'), '" alt="" /> ', _('Contract Other Requirements'), ' - ', $_SESSION['Contract' . $Identifier]->CustomerName, '
	</p>';

if (count($_SESSION['Contract' . $Identifier]->ContractReqts) > 0) {

	echo '<table>';

	if (isset($_SESSION['Contract' . $Identifier]->ContractRef)) {
		echo '<tr>
				<th colspan="5">', _('Contract Reference'), ': ', $_SESSION['Contract' . $Identifier]->ContractRef, '</th>
			</tr>';
	}

	echo '<tr>
			<th>', _('Description'), '</th>
			<th>', _('Quantity'), '</th>
			<th>', _('Unit Cost'), '</th>
			<th>', _('Sub-total'), '</th>
		</tr>';

	$_SESSION['Contract' . $Identifier]->total = 0;

	$TotalCost = 0;
	foreach ($_SESSION['Contract' . $Identifier]->ContractReqts as $ContractReqtID => $ContractComponent) {

		$LineTotal = $ContractComponent->Quantity * $ContractComponent->CostPerUnit;
		$DisplayLineTotal = locale_number_format($LineTotal, $_SESSION['CompanyRecord']['decimalplaces']);

		echo '<tr class="striped_row">
				<td><textarea name="Requirement', $ContractReqtID, '" required="required" autofocus="autofocus" cols="30" rows="3">', $ContractComponent->Requirement, '</textarea></td>
				<td><input type="text" class="number" maxlength="11" required="required" name="Qty', $ContractReqtID, '" size="11" value="', locale_number_format($ContractComponent->Quantity, 'Variable'), '" /></td>
				<td><input type="text" class="number" maxlength="11" required="required" name="CostPerUnit', $ContractReqtID, '" size="11" value="', locale_number_format($ContractComponent->CostPerUnit, $_SESSION['CompanyRecord']['decimalplaces']), '" /></td>
				<td class="number">', $DisplayLineTotal, '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', urlencode($Identifier), '&amp;Delete=', urlencode($ContractReqtID), '" onclick="return MakeConfirm(\'', _('Are you sure you wish to delete this contract requirement?'), '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			  </tr>';
		$TotalCost+= $LineTotal;
	}

	$DisplayTotal = locale_number_format($TotalCost, $_SESSION['CompanyRecord']['decimalplaces']);
	echo '<tr>
			<td colspan="4" class="number">', _('Total Other Requirements Cost'), '</td>
			<td class="number"><b>', $DisplayTotal, '</b></td>
		</tr>
		</table>
		<div class="centre">
			<input type="submit" name="UpdateLines" value="', _('Update Other Requirements Lines'), '" />
			<input type="submit" name="BackToHeader" value="', _('Back To Contract Header'), '" />
		</div>';

}
/*Only display the contract other requirements lines if there are any !! */

/*Now show  form to add new requirements to the contract */
if (!isset($_POST['RequirementDescription'])) {
	$_POST['RequirementDescription'] = '';
	$_POST['Quantity'] = 0;
	$_POST['CostPerUnit'] = 0;
}
echo '<fieldset>
		<legend>', _('Enter New Requirements'), '</legend>
		<field>
			<label for="RequirementDescription">', _('Requirement Description'), '</label>
			<textarea name="RequirementDescription" autofocus="autofocus" cols="30" rows="3">', $_POST['RequirementDescription'], '</textarea>
		</field>
		<field>
			<label for="Quantity">', _('Quantity Required'), ':</label>
			<input type="text" class="number" name="Quantity" size="10" maxlength="10" value="', $_POST['Quantity'], '" />
		</field>
		<field>
			<label for="CostPerUnit">', _('Cost Per Unit'), ':</label>
			<input type="text" class="number" name="CostPerUnit" size="10" maxlength="10" value="', $_POST['CostPerUnit'], '" />
		</field>

	</fieldset>';

echo '<div class="centre">
		<input type="submit" name="EnterNewRequirement" value="', _('Enter New Contract Requirement'), '" />
	</div>
</form>';

include ('includes/footer.php');
?>