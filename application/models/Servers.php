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
	
	public function get_user_server($server_id)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));

		$user_server = $this->cassie->USER_SERVERS->get($this->user_id, array($server_id));
		if($user_server)
			return $this->cassie->SERVERS->get($server_id);
	}
	
	public function add_server(array $details)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		$uuid = uniqid('tb');
		$details['server_id'] = $uuid;
		$this->cassie->SERVERS->insert($uuid, $details);
		$this->cassie->USER_SERVERS->insert($this->user_id, array($uuid => ''));
		
		return true;
	}

	public function add_servers (array $servers)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		foreach ($servers as $server) {
			$uuid = uniqid('tb');
			$server['server_id'] = $uuid;
			$this->cassie->SERVERS->insert($uuid, $server);
			$this->cassie->USER_SERVERS->insert($this->user_id, 
			array($uuid => ''));
		}
	}

	public function remove_server ($server_id)
	{
		$this->cassie->use_column_families('USER_SERVERS', 'USER_DELETED_SERVERS');
		
		$this->cassie->USER_DELETED_SERVERS->insert($this->user_id, 
			array($server_id => ''));
		$this->cassie->USER_SERVERS->remove($this->user_id, array($server_id));
	}
	
	public function remove_servers (array $server_ids)
	{
		$this->cassie->use_column_families('USER_SERVERS', 'USER_DELETED_SERVERS');
		
		foreach ($server_ids as $id)
			$this->cassie->USER_DELETED_SERVERS->insert($this->user_id, 
			array($id => ''));
		
		$this->cassie->USER_SERVERS->remove($this->user_id, $server_ids);
	}

	public function get_user_servers()
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		$server_ids = $this->cassie->USER_SERVERS->get($this->user_id);
		$server_ids = array_keys($server_ids);
		
		return $this->cassie->SERVERS->multiget($server_ids);
	}

	public function get_user_terminated_servers ()
	{
		$servers = array();
		$this->cassie->use_column_families(
		array('SERVERS', 'USER_DELETED_SERVERS'));
		
		$server_ids = $this->cassie->USER_DELETED_SERVERS->get($this->user_id);
		$server_ids = array_keys($server_ids);
		return $this->cassie->SERVERS->get($server_ids);
	}

	public function get_servers_details ($server_ids, $fields = array('instance_name'))
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
		
		$this->cassie->use_column_families(array('SERVERS'));
		
		foreach ($server_ids as $id) {
			$servers[] = $this->cassie->SERVERS->get($id, $columns);
		}
		return $servers;
	}
	
	public function get_server_ids($provider_server_ids)
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
}