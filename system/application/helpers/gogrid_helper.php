<?PHP
/*
 * This class provides functions for generating and submitting signed HTTP requests for the GoGrid API.
 * It can be instantiated with or without server, key and secret values.
 *
 */
class GoGridClient
{
	private $format			= 'json';
	private $server			= 'https://api.gogrid.com/api';
	private $api_key		= '1364ca1fd8a687d2';
	private $api_secret		= '956orc23';
	private $api_version	= '1.8';

	function __construct($server = null, $apikey = null, $secret = null, $format = null, $version = null)
	{
		if($server != null)
		{
			$this->server = $server;
		}
		if($version != null)
		{
			$this->api_version = $version;
		}
		if($format != null)
		{
			$this->format = $format;
		}
		if($apikey != null)
		{
			$this->api_key = $apikey;
		}
		if($secret != null)
		{
			$this->api_secret = $secret;
		}

		return true;
	}

	/*
	* Build a signed request url for the API
	*/
	private function getRequestURL($method, $alt_params = array())
	{
		$params['format'] = $this->format;
		$params['api_key'] = $this->api_key;
		$params['v'] = $this->api_version;

		// Start building the URL string with the server and method
		$method = str_replace('.', '/', $method);
		$requestUrl = "{$this->server}/$method?";

		// Copy the passed in parameters into the default parameter
		if(!empty($alt_params))
		{
			$params = array_merge($params, $alt_params);
		}

		// Generate the signature from the api_key and secret in the params
		$params['sig'] = $this->getSignature($this->api_key, $this->api_secret);

		// Append the param keys and alues to the URL string
		foreach($params as $key => $val)
		{
			$requestUrl .= "$key=" . urlencode($val) . "&";
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
		$output = $this->sendAPIRequest($url);
		
		return $output;
	}

}