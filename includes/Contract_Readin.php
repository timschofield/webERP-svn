<?php

/*Contract_Readin.php is used by the modify existing Contract in Contracts.php and also by ContractCosting.php */

$ContractHeaderSQL = "SELECT contractdescription,
							contracts.debtorno,
							contracts.branchcode,
							contracts.loccode,
							contracts.customerref,
							status,
							categoryid,
							orderno,
							margin,
							wo,
							requireddate,
							drawing,
							exrate,
							debtorsmaster.name,
							custbranch.brname,
							debtorsmaster.currcode
						FROM contracts
						INNER JOIN debtorsmaster
							ON contracts.debtorno=debtorsmaster.debtorno
						INNER JOIN currencies
							ON debtorsmaster.currcode=currencies.currabrev
						INNER JOIN custbranch
							ON debtorsmaster.debtorno=custbranch.debtorno
							AND contracts.branchcode=custbranch.branchcode
						INNER JOIN locationusers
							ON locationusers.loccode=contracts.loccode
							AND locationusers.userid='" .  $_SESSION['UserID'] . "'
							AND locationusers.canupd=1
						WHERE contractref= '" . $ContractRef . "'";

$ErrMsg = _('The contract cannot be retrieved because');
$DbgMsg = _('The SQL statement that was used and failed was');
$ContractHdrResult = DB_query($ContractHeaderSQL, $ErrMsg, $DbgMsg);

if (DB_num_rows($ContractHdrResult) == 1 and !isset($_SESSION['Contract' . $Identifier]->ContractRef)) {

	$MyRow = DB_fetch_array($ContractHdrResult);
	$_SESSION['Contract' . $Identifier]->ContractRef = $ContractRef;
	$_SESSION['Contract' . $Identifier]->ContractDescription = $MyRow['contractdescription'];
	$_SESSION['Contract' . $Identifier]->DebtorNo = $MyRow['debtorno'];
	$_SESSION['Contract' . $Identifier]->BranchCode = $MyRow['branchcode'];
	$_SESSION['Contract' . $Identifier]->LocCode = $MyRow['loccode'];
	$_SESSION['Contract' . $Identifier]->CustomerRef = $MyRow['customerref'];
	$_SESSION['Contract' . $Identifier]->Status = $MyRow['status'];
	$_SESSION['Contract' . $Identifier]->CategoryID = $MyRow['categoryid'];
	$_SESSION['Contract' . $Identifier]->OrderNo = $MyRow['orderno'];
	$_SESSION['Contract' . $Identifier]->Margin = $MyRow['margin'];
	$_SESSION['Contract' . $Identifier]->WO = $MyRow['wo'];
	$_SESSION['Contract' . $Identifier]->RequiredDate = ConvertSQLDate($MyRow['requireddate']);
	$_SESSION['Contract' . $Identifier]->Drawing = $MyRow['drawing'];
	$_SESSION['Contract' . $Identifier]->ExRate = $MyRow['exrate'];
	$_SESSION['Contract' . $Identifier]->BranchName = $MyRow['brname'];
	$_SESSION['RequireCustomerSelection'] = 0;
	$_SESSION['Contract' . $Identifier]->CustomerName = $MyRow['name'];
	$_SESSION['Contract' . $Identifier]->CurrCode = $MyRow['currcode'];


	/*now populate the contract BOM array with the items required for the contract */

	$ContractBOMsql = "SELECT contractbom.stockid,
							stockmaster.description,
							contractbom.workcentreadded,
							contractbom.quantity,
							stockmaster.units,
							stockmaster.decimalplaces,
							materialcost+labourcost+overheadcost AS cost
						FROM contractbom
						INNER JOIN stockmaster
							ON contractbom.stockid=stockmaster.stockid
						WHERE contractref ='" . $ContractRef . "'";

	$ErrMsg = _('The bill of material cannot be retrieved because');
	$DbgMsg = _('The SQL statement that was used to retrieve the contract bill of material was');
	$ContractBOMResult = DB_query($ContractBOMsql, $ErrMsg, $DbgMsg);

	if (DB_num_rows($ContractBOMResult) > 0) {
		while ($MyRow = DB_fetch_array($ContractBOMResult)) {
			$_SESSION['Contract' . $Identifier]->Add_To_ContractBOM($MyRow['stockid'], $MyRow['description'], $MyRow['workcentreadded'], $MyRow['quantity'], $MyRow['cost'], $MyRow['units'], $MyRow['decimalplaces']);
		}
		/* add contract bill of materials BOM lines*/
	} //end is there was a contract BOM to add
	//Now add the contract requirments
	$ContractReqtsSQL = "SELECT requirement,
								quantity,
								costperunit,
								contractreqid
						FROM contractreqts
						WHERE contractref ='" . $ContractRef . "'
						ORDER BY contractreqid";

	$ErrMsg = _('The other contract requirementscannot be retrieved because');
	$DbgMsg = _('The SQL statement that was used to retrieve the other contract requirments was');
	$ContractReqtsResult = DB_query($ContractReqtsSQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($ContractReqtsResult) > 0) {
		while ($MyRow = DB_fetch_array($ContractReqtsResult)) {
			$_SESSION['Contract' . $Identifier]->Add_To_ContractRequirements($MyRow['requirement'], $MyRow['quantity'], $MyRow['costperunit'], $MyRow['contractreqid']);
		}
		/* add other contract requirments lines*/
	} //end is there are contract other contract requirments to add
} // end if there was a header for the contract
?>