<?php
function GetCommissionPeriods($SalesPerson, $Period, $Term) {
	$ReturnPeriods = array();
	switch ($Term) {
		case 1:
			$ReturnPeriods[] = $Period;
		break;
		case 2:
			$From = FirstPeriodInFY($Period);
			$EndPeriod = FirstPeriodInFY($Period);
			while (($Period - $From) > 2) {
				$From = $From + 3;
			}
			$ReturnPeriods[] = $From;
			if (($From + 1) <= $Period) {
				$ReturnPeriods[] = $From + 1;
			}
			if (($From + 2) <= $Period) {
				$ReturnPeriods[] = $From + 2;
			}
		break;
		case 3:
			$From = FirstPeriodInFY($Period);
			while ($From !== ($Period + 1)) {
				$ReturnPeriods[] = $From;
				$From++;
			}
		break;
		default:
			$ReturnPeriods[] = $Period;
		break;
	}
	return $ReturnPeriods;
}

function StockCategoryCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period) {
	/* Does this sales person get commission */
	$SQL = "SELECT commissionperiod FROM salesman WHERE salesmancode='" . $SalesPerson . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['commissionperiod'] == 0) {
		/* If set to No Commission return zero */
		return 0;
	} else {
		$CommissionTerm = $MyRow['commissionperiod'];
	}

	/* Does a record exist for this sales person and currency */
	$SQL = "SELECT rate
				FROM salescommissionrates
				WHERE salespersoncode='" . $SalesPerson . "'
					AND currency='" . $Currency . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		/* There are no possible commission rates
		 * for this person/currency combination
		 * so exit with a vero commission
		*/
		return 0;
	}

	/* There is a record for this person/currency combination
	 * so now we need to check if there are specific records
	 * for this stock category
	*/
	$SQL = "SELECT rate,
					startfrom,
					salescommissionrates.categoryid
				FROM salescommissionrates
				INNER JOIN stockmaster
					ON salescommissionrates.categoryid=stockmaster.categoryid
				WHERE salespersoncode='" . $SalesPerson . "'
					AND currency='" . $Currency . "'
					AND stockmaster.stockid='" . $StockID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if (DB_num_rows($Result) == 1 and $MyRow['startfrom'] == 0) {
		/* If there is only one record returned for this
		 * salesperson/currency/category combination then
		 * there is no need to go further as the commission
		 * due is just the value of this transaction multiplied
		 * by the commission rate so just return that amount.
		*/
		return ($Value * $MyRow['rate'] / 100);
	} elseif (DB_num_rows($Result) == 1) {
		return 0;
	}
	if (DB_num_rows($Result) > 1) {
		/* Then at least two records exist for this specific stock category,
		 * so next we need to ascertain the commission period for this
		 * transaction.
		*/
		$CommissionPeriods = GetCommissionPeriods($SalesPerson, $Period, $CommissionTerm);
		/* Now we get the total value of relevant transactions for
		 * this commission period
		*/
		$PeriodTotal = 0;
		foreach ($CommissionPeriods as $CommissionPeriod) {
			/* for each financial period in the list of commission
			 * periods we obtain the total value of invoiced sales
			 * so far in this salesperson/currency/category group
			*/
			$InvoiceValueSQL = "SELECT sum(price*-qty) AS value
								FROM stockmoves
								INNER JOIN debtortrans
									ON stockmoves.type=debtortrans.type
									AND stockmoves.transno=debtortrans.transno
								INNER JOIN stockmaster
									ON stockmoves.stockid=stockmaster.stockid
								INNER JOIN debtorsmaster
									ON debtortrans.debtorno=debtorsmaster.debtorno
								WHERE stockmaster.categoryid='" . $MyRow['categoryid'] . "'
									AND debtortrans.salesperson='" . $SalesPerson . "'
									AND stockmoves.prd='" . $CommissionPeriod . "'
									AND debtorsmaster.currcode='" . $Currency . "'
									AND stockmoves.type=10";
			$InvoiceValueResult = DB_query($InvoiceValueSQL);
			$InvoiceValueRow = DB_fetch_array($InvoiceValueResult);
			$PeriodTotal+= $InvoiceValueRow['value'];
			/* and reduce by the total credit value
			*/
			$CreditValueSQL = "SELECT sum(price*qty) AS value
								FROM stockmoves
								INNER JOIN debtortrans
									ON stockmoves.type=debtortrans.type
									AND stockmoves.transno=debtortrans.transno
								INNER JOIN stockmaster
									ON stockmoves.stockid=stockmaster.stockid
								INNER JOIN debtorsmaster
									ON debtortrans.debtorno=debtorsmaster.debtorno
								WHERE stockmaster.categoryid='" . $MyRow['categoryid'] . "'
									AND debtortrans.salesperson='" . $SalesPerson . "'
									AND stockmoves.prd='" . $CommissionPeriod . "'
									AND debtorsmaster.currcode='" . $Currency . "'
									AND stockmoves.type=11";
			$CreditValueResult = DB_query($CreditValueSQL);
			$CreditValueRow = DB_fetch_array($CreditValueResult);
			$PeriodTotal-= $CreditValueRow['value'];
		}
		/* Now we cycle through the commission rates for this
		 * person/currency/category adding up the commission
		 * as we go
		*/
		DB_data_seek($Result, 0);
		/* Read the rates and quantity breaks into an array */
		$i = 0;
		while ($MyRow = DB_fetch_array($Result)) {
			$RatesArray[$i]['Rate'] = $MyRow['rate'];
			$RatesArray[$i]['StartFrom'] = $MyRow['startfrom'];
			++$i;
		}
		/* End the array with a very large number as next Start From
		 * field. This is a bit of a fudge so need to look for a better
		 * solution maybe?
		*/
		$RatesArray[$i]['StartFrom'] = 99999999999;
		for ($i = 0;$i < count($RatesArray);$i++) {
			/* Now we cycle through the Breakpoint/rates array calculating
			 * the commission along the way
			*/
			if ($PeriodTotal >= $RatesArray[$i]['StartFrom'] and $PeriodTotal < $RatesArray[$i + 1]['StartFrom']) {
				/* We have reached the band where the commission calculation starts */
				if (($PeriodTotal + $Value) < $RatesArray[$i + 1]['StartFrom']) {
					/* If it starts and ends in this band, calculate commission and return */
					return $Value * $RatesArray[$i]['Rate'] / 100;
				} else {
					/* Otherwise add the bands together */
					$Commission = ($RatesArray[$i + 1]['StartFrom'] - $PeriodTotal) * $RatesArray[$i]['Rate'] / 100;
					$Commission+= (($Value + $PeriodTotal) - $RatesArray[$i + 1]['StartFrom']) * $RatesArray[$i + 1]['Rate'] / 100;
					return $Commission;
				}
			}
		}
	}

	/* There is a record for this person/currency combination
	 * but not for this category.
	 * First off check if there is an all categories record
	*/
	$SQL = "SELECT rate,
					startfrom,
					categoryid
				FROM salescommissionrates
				WHERE salespersoncode='" . $SalesPerson . "'
					AND currency='" . $Currency . "'
					AND categoryid='ALL'";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {
		/* There are no records for this category, or for All
		 * categories so there cannot be commission to pay
		 * and so just return zero
		*/
		return 0;
	}

	$MyRow = DB_fetch_array($Result);
	if (DB_num_rows($Result) == 1 and $MyRow['startfrom'] == 0) {
		/* If there is only one record returned for this
		 * salesperson/currency combination then
		 * there is no need to go further as the commission
		 * due is just the value of this transaction multiplied
		 * by the commission rate so just return that amount.
		*/
		return ($Value * $MyRow['rate'] / 100);
	} elseif (DB_num_rows($Result) == 1) {
		return 0;
	}
	if (DB_num_rows($Result) > 1) {
		/* Then at least two records exist for all stock categories,
		 * so next we need to ascertain the commission period for this
		 * transaction.
		*/
		$CommissionPeriods = GetCommissionPeriods($SalesPerson, $Period, $CommissionTerm);
		/* Now we get the total value of relevant transactions for
		 * this commission period
		*/
		$PeriodTotal = 0;
		foreach ($CommissionPeriods as $CommissionPeriod) {
			/* for each financial period in the list of commission
			 * periods we obtain the total value of invoiced sales
			 * so far in this salesperson/currency group
			*/
			$InvoiceValueSQL = "SELECT sum(price*-qty) AS value
								FROM stockmoves
								INNER JOIN debtortrans
									ON stockmoves.type=debtortrans.type
									AND stockmoves.transno=debtortrans.transno
								INNER JOIN stockmaster
									ON stockmoves.stockid=stockmaster.stockid
								INNER JOIN debtorsmaster
									ON debtortrans.debtorno=debtorsmaster.debtorno
								WHERE debtortrans.salesperson='" . $SalesPerson . "'
									AND stockmoves.prd='" . $CommissionPeriod . "'
									AND debtorsmaster.currcode='" . $Currency . "'
									AND stockmoves.type=10";
			$InvoiceValueResult = DB_query($InvoiceValueSQL);
			$InvoiceValueRow = DB_fetch_array($InvoiceValueResult);
			$PeriodTotal+= $InvoiceValueRow['value'];
			/* and reduce by the total credit value
			*/
			$CreditValueSQL = "SELECT sum(price*qty) AS value
								FROM stockmoves
								INNER JOIN debtortrans
									ON stockmoves.type=debtortrans.type
									AND stockmoves.transno=debtortrans.transno
								INNER JOIN stockmaster
									ON stockmoves.stockid=stockmaster.stockid
								INNER JOIN debtorsmaster
									ON debtortrans.debtorno=debtorsmaster.debtorno
								WHERE debtortrans.salesperson='" . $SalesPerson . "'
									AND stockmoves.prd='" . $CommissionPeriod . "'
									AND debtorsmaster.currcode='" . $Currency . "'
									AND stockmoves.type=11";
			$CreditValueResult = DB_query($CreditValueSQL);
			$CreditValueRow = DB_fetch_array($CreditValueResult);
			$PeriodTotal-= $CreditValueRow['value'];
		}
		/* Now we cycle through the commission rates for this
		 * person/currency adding up the commission as we go
		*/
		DB_data_seek($Result, 0);
		/* Read the rates and quantity breaks into an array */
		$i = 0;
		while ($MyRow = DB_fetch_array($Result)) {
			$RatesArray[$i]['Rate'] = $MyRow['rate'];
			$RatesArray[$i]['StartFrom'] = $MyRow['startfrom'];
			++$i;
		}
		/* End the array with a very large number as next Start From
		 * field. This is a bit of a fudge so need to look for a better
		 * solution maybe?
		*/
		$RatesArray[$i]['StartFrom'] = 99999999999;
		for ($i = 0;$i < count($RatesArray);$i++) {
			/* Now we cycle through the Breakpoint/rates array calculating
			 * the commission along the way
			*/
			if ($PeriodTotal >= $RatesArray[$i]['StartFrom'] and $PeriodTotal < $RatesArray[$i + 1]['StartFrom']) {
				/* We have reached the band where the commission calculation starts */
				if (($PeriodTotal + $Value) < $RatesArray[$i + 1]['StartFrom']) {
					/* If it starts and ends in this band, calculate commission and return */
					return $Value * $RatesArray[$i]['Rate'] / 100;
				} else {
					/* Otherwise add the bands together */
					$Commission = ($RatesArray[$i + 1]['StartFrom'] - $PeriodTotal) * $RatesArray[$i]['Rate'] / 100;
					$Commission+= (($Value + $PeriodTotal) - $RatesArray[$i + 1]['StartFrom']) * $RatesArray[$i + 1]['Rate'] / 100;
					return $Commission;
				}
			}
		}
	}
}

function SalesAreaCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period) {
}

function TimeAsCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period) {
}

function CalculateCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period) {
	/* Get the commission calculation method for this sales person */
	$SQL = "SELECT commissiontypeid
				FROM salesman
				WHERE salesmancode='" . $SalesPerson . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$SalesCommissionType = $MyRow['commissiontypeid'];

	/* Select the calculation function based on this method */
	switch ($SalesCommissionType) {
		case 1:
			return StockCategoryCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period);
		break;
		case 2:
			return SalesAreaCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period);
		break;
		case 3:
			return TimeAsCommission($SalesPerson, $Debtor, $Branch, $StockID, $Currency, $Value, $Period);
		break;
		default:
			/* if there is no commission calculation method then return zero */
			return 0;
		break;
	}

}

?>