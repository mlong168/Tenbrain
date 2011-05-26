<?php

/** DoDirectPayment NVP
 *
 *  Process a credit card payment. 
*/

class Paypal_DoDirectPayment
{
	private $price = '0.99';
	
	public function doDirectPayment(array $cc_info)
	{
		$cc_number = $cc_info['number'];
		$cc_type = $cc_info['type'];
		$cc_month = $cc_info['month'];
		$cc_year = $cc_info['year'];
		$cc_cvv2 = $cc_info['cvv2'];
		
		// Set request-specific fields.
		$paymentType = urlencode('Sale');				// 'Authorization' or 'Sale'
		$creditCardType = urlencode(strtoupper($cc_type));
		$creditCardNumber = urlencode($cc_number); //test num - 4834612755548993
		$cvv2Number = urlencode($cc_cvv2);
		$amount = urlencode($this->price);
		$currencyID = urlencode('USD');
		$expDateYear = urlencode($cc_year);
		$expDateMonth = $cc_month;

		// Month must be padded with leading zero
		$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));
		
		// Add request-specific fields to the request string.
		$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber".
					"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&CURRENCYCODE=$currencyID";
		
		// Execute the API operation; see the PPHttpPost function above.
		$httpPost = new Paypal_HttpPost();
		$this->details = $httpPost->httpPost('DoDirectPayment', $nvpStr);
		
		return $this->SaveToDB();
	}
	
	
	private function SaveToDB()
	{
		$table = new Application_Model_Paypal();
		return $table->db_save($this->details);
	}
	

}

