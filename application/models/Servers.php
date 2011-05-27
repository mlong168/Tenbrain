<?php

class Application_Model_Servers
{
	private $user_id;
	private $cassie;

	function __construct ()
	{
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		$this->cassie = new ZendExt_Cassandra();
	}
	
	public function add_server(array $details)
	{
		$this->cassie->useColumnFamilies(array('SERVERS', 'USER_SERVERS'));
		
		$uuid = ZendExt_CassandraUtil::uuid1();
		$this->cassie->SERVERS->insert($uuid, $details);
		$this->cassie->USER_SERVERS->insert($this->user_id, array($uuid => ''));
		
		return true;
	}

	public function addServers (array $servers)
	{
		$this->cassie->useColumnFamilies(array('SERVERS', 'USER_SERVERS'));
		
		foreach ($servers as $server) {
			$uuid = ZendExt_CassandraUtil::uuid1();
			$data['server_id'] = $uuid;
			$this->cassie->SERVERS->insert($uuid, $server);
			$this->cassie->USER_SERVERS->insert($this->user_id, 
			array($uuid => ''));
		}
	}

	public function removeServers (array $server_ids)
	{
		$this->cassie->useColumnFamilies('USER_SERVERS', 'USER_DELETED_SERVERS');
		
		foreach ($server_ids as $id)
			$this->cassie->USER_DELETED_SERVERS->insert($this->user_id, 
			array($id => ''));
		
		$this->cassie->USER_SERVERS->remove($this->user_id, $server_ids);
	}

	public function get_user_servers()
	{
		$this->cassie->useColumnFamilies(array('SERVERS', 'USER_SERVERS'));
		
		$server_ids = $this->cassie->USER_SERVERS->get($this->user_id);
		$server_ids = array_keys($server_ids);
		
		return $this->cassie->SERVERS->multiget($server_ids);
	}

	public function getUserTerminatedServers ()
	{
		$servers = array();
		$this->cassie->useColumnFamilies(
		array('SERVERS', 'USER_DELETED_SERVERS'));
		
		$server_ids = $this->cassie->USER_DELETED_SERVERS->get($this->user_id);
		foreach ($server_ids as $id) {
			$servers[] = $this->cassie->SERVERS->get($id);
		}
		return $servers;
	}

	public function getServersDetails ($server_ids, $fields = array('instance_name'))
	{
		$servers = array();
		$possible_fields = array('server_id', 'provider_server_id', 'server_name', 'provider', 'public_ip', 'created_on');
		$fields_to_retrieve = array();
		if (! is_array($fields)) $fields = array($fields);
		foreach ($fields as $field) {
			if (in_array($field, $possible_fields)) $fields_to_retrieve[] = $field;
		}
		if ($fields === array('*')) $fields_to_retrieve = $possible_fields;
		$columns = implode(',', $fields_to_retrieve);
		
		$this->cassie->useColumnFamilies(array('SERVERS'));
		
		foreach ($server_ids as $id) {
			$servers[] = $this->cassie->SERVERS->get($id, $columns);
		}
		return $servers;
	}
	
	public function getServerIds($provider_server_ids)
	{
		$server_ids = array();
		if(!is_array($provider_instance_ids)) $provider_instance_ids = array($provider_instance_ids);
		$user_servers = $this->getUserServers();
		foreach ($servers as $server)
		{
			if(in_array($server['provider_server_id'], $provider_instance_ids))
				$server_ids[] = $server['server_id'];
		}
		return $server_ids;
	}
	
	public function removeServersInLb($load_balancer_id, $server_ids)
	{
		$this->cassie->useColumnFamilies(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->remove($load_balancer_id, $server_ids);
	}
	
	public function addServersInLb($load_balancer_id, $server_ids)
	{
		$this->cassie->useColumnFamilies(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->insert($load_balancer_id, $server_ids);
	}
	
	public function getServersAvailableForLb($provider = "ALL", $load_balancer_id)
	{
		$this->cassie->useColumnFamilies(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id);
		if(!$lb)
			return array();
		
		$lb_servers = (array) $this->cassie->USER_LOADBALANCER_SERVERS->get($load_balancer_id);
		$user_servers = $this->getUserServers();

		$user_provider_servers = array();
		if($provider != "ALL")
		{
			foreach ($user_servers as $user_server)
				if($user_server['provider'] == $provider)
					$user_provider_servers[] = $user_server;
			$user_servers = $user_provider_servers;
		}
		
		$servers = array();
		if($user_servers)
		{
			foreach($user_servers as $user_server)
			{
				if(!in_array($lb_servers, $user_servers))
					$servers[] = array(
						'server_id'				=> $user_server['server_id'],
						'provider_server_id'	=> $user_server['provider_server_id'],
						'server_name'			=> $user_server['server_name'],
						'ip_address'			=> $user_server['public_ip']
					);
			}
		}
		return $servers;
	}
}