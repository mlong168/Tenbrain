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
	
	public function get_available_server_types()
	{
		$flavors = $this->list_flavors();
		$types = array();
		$premium = false;
		
		foreach($flavors as $flavor)
		{
			$types []= array(
				'name'		=> $flavor->name,
				'available'	=> true,
				'reason'	=> 'Not available in a free version'
			);
		}
		return $types;
	}
	
	private function get_flavor_details($flavor_id)
	{
		$details = $this->rack->GET_request('flavors/' . $flavor_id);
		return empty($details) ? false : $details->flavor;
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
		
		$type = $this->get_flavor_details($server->flavorId);
		
		$this->storage->add_server(array(
			// common to all providers
			'name'				=> $server->name,
			'provider_server_id'=> $server->id,
			'provider'			=> $this->name,
			'type'				=> $type->name,
			'image_id'			=> $server->imageId,
			
			// rackspace-specific data:
			'host_id'			=> $server->hostId,
			'flavor_id'			=> $server->flavorId,
			'root_password'		=> $server->adminPass,
			'private_ip'		=> $server->addresses->private[0],
			'public_ip'			=> $server->addresses->public[0]
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
	
	public function list_servers($ids = array(), $state = 'running')
	{
		// there is no ability to start/stop servers in Rackspace, and we take terminated from the db only,
		// therefore this method can  be useful only for listing running servers here
		if($state !== 'running') return array();
		
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

	public function get_connection_info(array $params)
	{
		$root_password = $params['root_password'];
		return
			"Use password '{$root_password}' and username 'root' to connect to the server by ssh."
			. '<br />Alternatively, you can use our web console';
	}
	
	// no start or stop for rackspace
	public function start_servers(array $ids)
	{
		return false;
	}
	
	public function stop_servers(array $ids)
	{
		return false;
	}
	
	public function reboot_servers(array $ids)
	{
		$data = array(
			'reboot' => array(
				'type' => 'SOFT'
			)
		);
		foreach($ids as $id)
		{
			$this->rack->POST_request('servers/' . $id . '/action', $data);
		}
		return true;
	}
	
	public function terminate_servers(array $ids)
	{
		foreach($ids as $id)
		{
			$this->rack->DELETE_request('servers/' . $id);
		}
		
		$this->storage->remove_servers(array_keys($ids));
		return true;
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
}