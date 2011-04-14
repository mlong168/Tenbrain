<?php

include_once('provider.php');

class Rackspace_model extends Provider {
	
	private $server_url = '';
	private $auth_token = '';

	public $name = 'Rackspace';
	
	const USERNAME = 'tenbrain';
	const API_KEY = '7e7bd2615abdfc2734e66ba3441674ae';
	const VERSION = 'v1.0';
	const AUTH_URL = 'https://auth.api.rackspacecloud.com';

	function __construct()
	{
		parent::__construct();
		
		$auth_params = $this->authenticate();
		$this->auth_token = $auth_params['auth_token'];
		$this->server_url = $auth_params['server_management_url'];
	}
	
	private function authenticate()
	{
		$curl_session = curl_init(self::AUTH_URL . '/' . self::VERSION);
		
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

	private function GET_request($action, $success_response_codes = array(200, 203))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);
		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
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
	
	public function list_images()
	{
		$out = array();
		$images = $this->GET_request('images/detail');
		if(!$images) return $out;
		$images = $images->images;
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
		$flavors = $this->GET_request('flavors/detail');
		return empty($flavors) ? false : $flavors->flavors;
	}

	public function launch_instance($name, $image_id, $flavor_id)
	{
		$setup = array(
			'server' => array(
				'name'		=> $name,
				'imageId'	=> (int) $image_id,
				'flavorId'	=> (int) $flavor_id,
			)
		);
		
		$instance = $this->POST_request('servers', $setup);
		if(!$instance) return false;
		
		$instance = $instance->server;		
		$this->db->insert('user_instances', array(
			'account_id'			=> $this->session->userdata('account_id'),
			'provider_instance_id'	=> $instance->id,
			'instance_name'			=> $instance->name,
			'provider'				=> 'Rackspace',
			'public_ip'				=> $instance->addresses->public[0]
		));
		
		return true;
	}
	
	public function list_instances($instances = null)
	{
		$out = array();
		foreach($instances as $instance_id => $db_id)
		{
			$server = $this->GET_request('servers/' . $instance_id);
			if(!$server) continue;
			$server = $server->server;
			$ip = $server->addresses->public[0];
			$out[] = array(
// 				'id'				=> $db_id,*/
				'id'				=> $server->id,
				'name'				=> $server->name,
				'dns_name'			=> $ip,
				'ip_address'		=> $ip,
				'image_id'			=> $server->imageId,
				'state'				=> $server->status === 'ACTIVE' ? 'running' : 'pending',
				'type'				=> $server->flavorId,
				'provider'			=> $this->name
				// ''				=> $server->, 
			);
		}
		return $out;
	}

	public function delete_instance($id)
	{
		return $this->DELETE_request('servers/' . $id);
	}
	
	// no start or stop for rackspace
	public function start_instance($id)
	{
		return false;
	}

	public function stop_instance($id)
	{
		return false;
	}

	public function restart_instance($id)
	{
		$data = array(
			'reboot' => array(
				'type' => 'SOFT'
			)
		);
		return $this->POST_request('servers/' . $id . '/action', $data);
	}
	
	public function test()
	{
		// $images = $this->launch_instance('tenbrain first', 4, 1);
		// print_r($images);
		echo PHP_EOL; die;
	}
}