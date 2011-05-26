<?php

/** DoDirectPayment NVP
 *
 *  Process a credit card payment. 
*/

class Paypal_DoDirectPayment
{
	public function doDirectPayment(array $cc_info)
	{
		$cc_number = $cc_info['number'];
		$cc_type = $cc_info['type'];
		$cc_month = $cc_info['month'];
		$cc_year = $cc_info['year'];
		$cc_cvv2 = $cc_info['cvv2'];
		
		// Set request-specific fields.
		$paymentType = urlencode('Sale');				// 'Authorization' or 'Sale'
//		$firstName = urlencode('Bob');
//		$lastName = urlencode('Marley');
		$creditCardType = urlencode(strtoupper($cc_type));
//		$creditCardType = urlencode('VISA');
		$creditCardNumber = urlencode($cc_number);
//		$creditCardNumber = urlencode('4834612755548993');
		$expDateMonth = $cc_month;
		// Month must be padded with leading zero
		$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));
		$expDateYear = urlencode($cc_year);
		$cvv2Number = urlencode($cc_cvv2);
//		$address1 = urlencode('customer_address1');
//		$address2 = urlencode('customer_address2');
//		$city = urlencode('NY');
//		$state = urlencode('NY');
//		$zip = urlencode('10001');
//		$country = urlencode('US');				// US or other valid country code
		$amount = urlencode('0.99');
		$currencyID = urlencode('USD');			// or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
		
		// Add request-specific fields to the request string.
		$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber".
					"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&CURRENCYCODE=$currencyID";
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

