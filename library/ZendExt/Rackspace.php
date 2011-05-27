<?php

class ZendExt_Rackspace
{	
	private $server_url = '';
	private $auth_token = '';
	
	private $premium = true;
	private $default_type = 1;	

	public $name = 'Rackspace';
	
	const USERNAME = 'tenbrain';
	const API_KEY = '7e7bd2615abdfc2734e66ba3441674ae';
	const VERSION = 'v1.0';
	const AUTH_URL = 'https://auth.api.rackspacecloud.com';

	function __construct($credentials = null)
	{		
		if($credentials)
			$auth_params = $this->authenticate($credentials['username'],$credentials['key']);
		else
			$auth_params = $this->authenticate(self::USERNAME, self::API_KEY);
		

		$this->auth_token = $auth_params['auth_token'];
		$this->server_url = $auth_params['server_management_url'];
	}
	
	private function authenticate($username = null, $key = null)
	{
		$curl_session = curl_init(self::AUTH_URL . '/' . self::VERSION);
		
		$headers = array(
			'X-Auth-User: ' . $username,
			'X-Auth-Key: ' . $key
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		
		if($response->getResponseCode() !== 204) return false;
		$response_headers = $response->getHeaders();
		
		return array(
			'auth_token'			=> $response_headers['X-Auth-Token'],
			'storage_url'			=> $response_headers['X-Storage-Url'],
			'storage_token'			=> $response_headers['X-Storage-Token'],
			'cdn_management_url'	=> $response_headers['X-Cdn-Management-Url'],
			'server_management_url'	=> $response_headers['X-Server-Management-Url']
			// ''	=> $response_headers[''],
		);
	}

	private function GET_request($action, $success_response_codes = array(200, 203))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);
		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token,
			'Accept: application/json'
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		//print_r($response);
		$response =  new HttpMessage($response);
		if(!in_array($response->getResponseCode(), $success_response_codes)) return false;
		
		return json_decode($response->getBody());
	}
	
	private function DELETE_request($action, $success_response_codes = array(202, 204))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		return in_array($response->getResponseCode(), $success_response_codes);	
	}
	
	private function POST_request($action, $data, $success_response_codes = array(202))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token,
			'Content-Type: application/json'
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_POST, true);
		curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);

		if(!in_array($response->getResponseCode(), $success_response_codes)) return false;
		$body = $response->getBody();
		return $body ? json_decode($body) : true;	
	}
	
	private function PUT_request($action, $data, $success_response_codes = array(204))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);		
		$headers = array(
			sprintf("%s: %s", 'X-Auth-Token', $this->auth_token),
			sprintf("%s: %s", 'Content-Type', 'application/json')
		);
		array_push($headers, json_encode($data));

		curl_setopt($curl_session, CURLOPT_HEADER, 0);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, 'PUT');

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		print_r($response);die;

		if(!in_array($response->getResponseCode(), $success_response_codes)) return false;
		$body = $response->getBody();
		return $body ? json_decode($body) : true;	
	}
	
	public function validate_credentials($new_credentials)
	{
		$curl_session = curl_init(self::AUTH_URL . '/' . self::VERSION);
		
		$headers = array(
			'X-Auth-User: ' . $new_credentials['username'],
			'X-Auth-Key: ' . $new_credentials['key']
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		
		if($response->getResponseCode() !== 204) 
			return false;
		
		return true;
	}
}