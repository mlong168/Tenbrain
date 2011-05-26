<?php
/**
 * 
 * Send HTTP POST Request.
 * @author Kostik
 *
 */
class Paypal_HttpPost
{
	// Set up your API credentials, PayPal end point, and API version.
	private $environment = 'sandbox';	// or 'beta-sandbox' or 'live'

	private $API = array( 
			//Website Payments Pro account
			'UserName' => 'kkorni_1304690763_biz_api1.softjourn.com',
			'Password' => '1304690787',
			'Signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31A9poMo9ShDVamqD2WQCv-il9oeci',
			'Version' => '51.0',
	);
	
	public function __construct()
	{
		$this->massUrlencode($this->API);
		if("sandbox" === $this->environment || "beta-sandbox" === $this->environment) 
		{
			$this->API['Endpoint'] = "https://api-3t.".$this->environment.".paypal.com/nvp";
		}
		else 
		{
			$this->API['Endpoint'] = 'https://api-3t.paypal.com/nvp';
		}
	}
	
	private function massUrlencode($array)
	{
		foreach ($array as $key => $value) {
			$array[$key] = urlencode($value);
		}
	}
	
	/**
	 * Send HTTP POST Request
	 *
	 * @param	string	The API method name
	 * @param	string	The POST Message fields in &name=value pair format
	 * @return	array	Parsed HTTP Response body
	 */
	public function httpPost($methodName_, $nvpStr_) {
		
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
?>