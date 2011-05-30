<?php
class Paypal_Validate_ExpirationDate
{
	const BADDATE = 'Expiration date is incorrect.';
	
	public static function isValid($month, $year)
	{
		$month = (int)$month;
		$year = (int)$year;
		$this_year = date('Y');
		$this_month = date('n');
		if($year <= $this_year )
		{
			if($month <= $this_month)
				return false;
			else 
				return true;
		}
		return true;
	}
}
?>