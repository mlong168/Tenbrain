<?php

/** DoDirectPayment NVP
 *
 *  Process a credit card payment. 
*/

class Paypal_DoDirectPayment
{

	// Set up your API credentials, PayPal end point, and API version.
	private $paypal_url = 'https://api-3t.paypal.com/nvp';
	private $environment = 'sandbox';	// or 'beta-sandbox' or 'live'

	private $API = array( 
			//Website Payments Pro account
			'UserName' => 'kkorni_1304690763_biz_api1.softjourn.com',
			'Password' => '1304690787',
			'Signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31A9poMo9ShDVamqD2WQCv-il9oeci',
			'Version' => '51.0',
	);
	
	private $fields;
	
	public function __construct()
	{
		$this->massUrlencode($this->API);
		if("sandbox" === $this->environment || "beta-sandbox" === $this->environment) {
				$this->API['Endpoint'] = "https://api-3t.".$this->environment.".paypal.com/nvp";
		}
		else {
			$this->API['Endpoint'] = $this->paypal_url;
		}
	}
	
	private function massUrlencode($array)
	{
		foreach ($array as $key => $value) {
			$array[$key] = urlencode($value);
		}
	}
	
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
		$httpParsedResponseAr = $this->httpPost('DoDirectPayment', $nvpStr);
		
//		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
//			return ('Direct Payment Completed Successfully: '.print_r($httpParsedResponseAr, true));
//		} else  {
//			return ('DoDirectPayment failed: ' . print_r($httpParsedResponseAr, true));
//		}
		return $httpParsedResponseAr;
	}
	
	/**
	 * Send HTTP POST Request
	 *
	 * @param	string	The API method name
	 * @param	string	The POST Message fields in &name=value pair format
	 * @return	array	Parsed HTTP Response body
	 */
	private function httpPost($methodName_, $nvpStr_) {
		
		$API_UserName = $this->API['UserName'];
		$API_Password = $this->API['Password'];
		$API_Signature = $this->API['Signature'];
		$API_Endpoint = $this->API['Endpoint'];
		$version = $this->API['Version'];
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) {
			return array(
				'ACK' => 'Failure',
				'L_LONGMESSAGE0' => "$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')'
			);
		}
	
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			return array(
				'ACK' => 'Failure',
				'L_LONGMESSAGE0' => "Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint."
			);
		}
	
		return $httpParsedResponseAr;
	}

}

//success:
//Array
//(
//    [TIMESTAMP] => 2011%2d05%2d24T08%3a40%3a41Z
//    [CORRELATIONID] => e904b5db76401
//    [ACK] => Success
//    [VERSION] => 51%2e0
//    [BUILD] => 1863577
//    [AMT] => 0%2e99
//    [CURRENCYCODE] => USD
//    [AVSCODE] => X
//    [CVV2MATCH] => M
//    [TRANSACTIONID] => 6XX64287PX509163E
//)

//Failure
//Array
//(
//    [TIMESTAMP] => 2011%2d05%2d24T08%3a42%3a48Z
//    [CORRELATIONID] => 34aad0572fdf
//    [ACK] => Failure
//    [VERSION] => 51%2e0
//    [BUILD] => 1863577
//    [L_ERRORCODE0] => 10561
//    [L_SHORTMESSAGE0] => Invalid%20Data
//    [L_LONGMESSAGE0] => There%27s%20an%20error%20with%20this%20transaction%2e%20Please%20enter%20a%20complete%20billing%20address%2e
//    [L_SEVERITYCODE0] => Error
//    [AMT] => 0%2e99
//    [CURRENCYCODE] => USD
//)