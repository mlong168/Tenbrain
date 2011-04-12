<?php

class Rackspace_model extends Model {
	
	private $server_url = '';
	private $auth_token = '';

	public $name = 'Rackspace';
	
	const USERNAME = 'tenbrain';
	const API_KEY = '7e7bd2615abdfc2734e66ba3441674ae';
	const VERSION = 'v1.0';
	const SERVER_HOST = 'api.rackspacecloud.com';

	function __construct()
	{
		parent::Model();
		
		$auth_params = $this->authenticate();
		$this->auth_token = $auth_params['auth_token'];
		$this->server_url = $auth_params['server_management_url'];
	}
	
	private function prepare_url($operation_type)
	{
		return 'https://' . $operation_type . '.' . self::SERVER_HOST . '/' . self::VERSION;
	}
	
	private function authenticate()
	{
		$url = $this->prepare_url('auth');
		$curl_session = curl_init($url);
		
		$headers = array(
			'X-Auth-User: ' . self::USERNAME,
			'X-Auth-Key: ' . self::API_KEY
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

	public function list_images()
	{
		$curl_session = curl_init($this->server_url . '/images/detail');
		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		if(!in_array($response->getResponseCode(), array(200, 203))) return false;
		
		$images = json_decode($response->getBody());
		$images = $images->images; $out = array();
		foreach($images as $image)
		{
			$out []= array(
				'image_id'	=> $image->id,
				'name'		=> $image->name,
				'provider'	=> $this->name,
				'state'		=> $image->status
			);
		}
		
		return $out;
	}
	
	public function list_flavors()
	{
		$curl_session = curl_init($this->server_url . '/flavors/detail');
		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		if(!in_array($response->getResponseCode(), array(200, 203))) return false;
		
		$flavors = json_decode($response->getBody());		
		return $flavors->flavors;
	}

	public function launch_instance()
	{
		
	}
	
	public function test()
	{
		$images = $this->list_flavors();
		print_r($images);
		echo PHP_EOL; die;
	}
}