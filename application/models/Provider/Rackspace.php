<?php

class Application_Model_Provider_Rackspace extends Application_Model_Provider
{
	private $rack;
	private $storage;
	
	private $user_id;
	
	function __construct(){
		parent::__construct();
		
		$this->name = 'Rackspace';
		$this->storage = new Application_Model_Servers();
		
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$rack_credentials = new Application_Model_DbTable_Credentials_Rackspace();
		$credentials = $rack_credentials->get_credentials($this->user_id);
		$this->rack = new ZendExt_Rackspace();
	}
	
	public function list_flavors()
	{
		$flavors = $this->rack->GET_request('flavors/detail');
		return empty($flavors) ? false : $flavors->flavors;
	}
	
	public function launch_server(array $params)
	{
		$setup = array(
			'server' => array(
				'name'		=> $params['name'],
				'imageId'	=> (int) $params['image_id'],
				'flavorId'	=> (int) $params['flavor_id']
			)
		);
		
		$server = $this->rack->POST_request('servers', $setup);
		if(!$server) return false;
		
		$server = $server->server;
		$this->storage->add_server(array(
			'name'				=> $server->name,
			'provider_server_id'=> $server->id,
			'flavor_id'			=> $server->flavorId,
			'host_id'			=> $server->hostId,
			'root_password'		=> $server->adminPass,
			'private_ip'		=> $server->addresses->private[0],
			'public_ip'			=> $server->addresses->public[0],
			'provider'			=> $this->name
		));
		
		return true;
	}
	
	public function list_images()
	{
		$out = array();
		$images = $this->rack->GET_request('images/detail');
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
	
	public function list_servers($ids = array())
	{
		$out = array();
		// $av_types = $this->get_available_server_types();
		foreach($ids as $pid => $db_id)
		{
			$server = $this->rack->GET_request('servers/' . $pid);
			if(!$server) continue;
			$server = $server->server;
			$ip = $server->addresses->public[0];
			
			// foreach($av_types as &$t)
			// {
				// if($t['value'] == $server->flavorId)
					// $type = $t['name'];
			// }
			
			$out[] = array(
				'id'				=> $db_id,
				'name'				=> $server->name,
				'dns_name'			=> $ip,
				'ip_address'		=> $ip,
				'image_id'			=> $server->imageId,
				'state'				=> $server->status === 'ACTIVE' ? 'running' : 'pending',
				// 'type'				=> $type,
				'type'				=> $server->flavorId,
				'provider'			=> $this->name
				// ''				=> $server->, 
			);
		}
		return $out;
	}

	public function start_servers(array $ids)
	{
		
	}
	
	public function stop_servers(array $ids)
	{
		
	}
	
	public function reboot_servers(array $ids)
	{
		
	}
	
	public function terminate_servers(array $ids)
	{
		
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
}