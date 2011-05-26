<?php

/** DoDirectPayment NVP
 *
 *  Process a credit card payment. 
*/

class Paypal_DoDirectPayment
{
	public function doDirectPayment()
	{
		// Set request-specific fields.
		$paymentType = urlencode('Sale');				// 'Authorization' or 'Sale'
//		$firstName = urlencode('Bob');
//		$lastName = urlencode('Marley');
		$creditCardType = urlencode('VISA');
		$creditCardNumber = urlencode('4834612755548993');
		$expDateMonth = '05';
		// Month must be padded with leading zero
		$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));
		$expDateYear = urlencode('2016');
		$cvv2Number = urlencode('164');
//		$address1 = urlencode('customer_address1');
//		$address2 = urlencode('customer_address2');
//		$city = urlencode('NY');
//		$state = urlencode('NY');
//		$zip = urlencode('10001');
		$country = urlencode('US');				// US or other valid country code
		$amount = urlencode('0.99');
		$currencyID = urlencode('USD');							// or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
		
		// Add request-specific fields to the request string.
		$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber".
					"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number".
					"&CURRENCYCODE=$currencyID";
//		$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber".
//					"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&FIRSTNAME=$firstName&LASTNAME=$lastName".
//					"&STREET=$address1&CITY=$city&STATE=$state&ZIP=$zip&COUNTRYCODE=$country&CURRENCYCODE=$currencyID";
		
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

