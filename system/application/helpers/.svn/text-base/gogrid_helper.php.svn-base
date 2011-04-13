<?php
/*
 * This class provides functions for generating and submitting signed HTTP requests for the GoGrid API.
 * It can be instantiated with or without key and secret values.
 *
 */
class GoGridClient
{
	const FORMAT		= 'json';
	const SERVER		= 'https://api.gogrid.com/api';
	const API_VERSION	= '1.8';
	
	// const API_KEY		= '1364ca1fd8a687d2';
	const API_KEY		= 'c65c001f5fc9100f';
	// const API_SECRET	= '956orc12';
	const API_SECRET	= '956orc12';
	
	private $api_key;
	private $api_secret;

	function __construct($api_key = null, $api_secret = null)
	{
		$this->api_key = is_null($api_key) ? self::API_KEY : $api_key;
		$this->api_secret = is_null($api_secret) ? self::API_SECRET : $api_secret;
		
		return true;
	}

	/*
	* Build a signed request url for the API
	*/
	private function getRequestURL($method, $alt_params = array())
	{
		$params['format'] = self::FORMAT;
		$params['api_key'] = $this->api_key;
		$params['v'] = self::API_VERSION;

		// Start building the URL string with the server and method
		$method = str_replace('.', '/', $method);
		$requestUrl = self::SERVER . '/' . $method . '?';

		// Copy the passed in parameters into the default parameter
		if(!empty($alt_params))
		{
			$params = array_merge($params, $alt_params);
		}

		// Generate the signature from the api_key and secret in the params
		$params['sig'] = $this->getSignature($this->api_key, $this->api_secret);

		// Append the param keys and values to the URL string
		foreach($params as $key => $val)
		{
			if(is_array($val))	// support multiple values
			{
				foreach($val as $subval)
				{
					$requestUrl .= "$key=" . urlencode($subval) . "&";
				}
			}
			else
			{
				$requestUrl .= "$key=" . urlencode($val) . "&";
			}
		}
		return $requestUrl;
	}

	/*
	* Get a URL signature value for a particular key and secret
	*/
	private function getSignature($key, $secret)
	{
		$timestamp = time();
		$sig = md5($key . $secret . $timestamp);
		return $sig;
	}

	/*
	* Make the api request over HTTP
	*/
	private function sendAPIRequest($url)
	{
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		// grab URL and pass it to the browser
		$response = curl_exec($ch);

		// Handle any HTTP errors by simply returning the error message string
		if(curl_errno($ch))
		{
			$response = curl_error($ch);
		}
		else
		{
			curl_close($ch); // make sure the connection is closed
		}

		return $response;
	}
	
	public function call($method, $alt_params = array())
	{
		$url = $this->getRequestURL($method, $alt_params);
		// var_dump($url);die;
		$output = $this->sendAPIRequest($url);
		
		return $output;
	}

}