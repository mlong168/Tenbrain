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
	
	public function update_provider_id($server_id, $provider_server_id)
	{
		$this->cassie->use_column_families(array('SERVERS'));
		
		$server = $this->cassie->SERVERS->get($server_id);
		$server['provider_server_id'] = $provider_server_id;
		
		$server = $this->cassie->SERVERS->insert($server_id, $server);
	}
	
	public function get_user_server($server_id)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));

		$user_server = $this->cassie->USER_SERVERS->get($this->user_id, array($server_id));
		if($user_server)
			return $this->cassie->SERVERS->get($server_id);
	}
	
	public function get_user_server_by_provider_id($provider_server_id)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));

		$user_server_ids = $this->cassie->USER_SERVERS->get($this->user_id);
		$user_server_ids = array_keys($user_server_ids);
		
		if($user_server_ids)
		{
			$servers = $this->cassie->SERVERS->multiget($user_server_ids);
			foreach ($servers as $server) {
				if($server['provider_server_id'] == $provider_server_id)
					return $server;
			}
		}
	}

	public function get_user_servers($ids = null)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		$server_ids = $this->cassie->USER_SERVERS->get($this->user_id, empty($ids) ? null : $ids);
		$server_ids = array_keys($server_ids);
		
		return $this->cassie->SERVERS->multiget($server_ids);
	}
	
	/**
	*	@param $return_tb_ids - if true - returns tenbrain ids also
	*/
	public function get_user_server_provider_ids($server_ids, $return_tb_ids = false)
	{
		$servers = $this->get_user_servers($server_ids);
		$out = array();
		foreach($servers as $tb_id => $server)
		{
			if(!array_key_exists($server['provider'], $out)) $out[$server['provider']] = array();
			if($return_tb_ids)
			{
				$out[$server['provider']][$tb_id] = $server['provider_server_id'];
			}
			else
			{
				$out[$server['provider']][] = $server['provider_server_id'];
			}
		}
		
		return $out;
	}
	
	public function add_server(array $details)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		$uuid = uniqid('tb-');
		$this->cassie->SERVERS->insert($uuid, $details);
		$this->cassie->USER_SERVERS->insert($this->user_id, array($uuid => ''));
		
		return true;
	}

	public function add_servers (array $servers)
	{
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS'));
		
		foreach ($servers as $server) {
			$uuid = uniqid('tb-');
			$this->cassie->SERVERS->insert($uuid, $server);
			$this->cassie->USER_SERVERS->insert($this->user_id, 
			array($uuid => ''));
		}
	}
	
	public function change_server($server_id, $new_params)
	{
		$this->cassie->use_column_families(array('SERVERS'));
		$this->cassie->SERVERS->insert($server_id, $new_params);
		return true;
	}

	public function remove_server ($server_id)
	{
		$this->cassie->use_column_families(array('USER_SERVERS', 'USER_DELETED_SERVERS'));
		
		$this->cassie->USER_DELETED_SERVERS->insert($this->user_id, 
			array($server_id => ''));
			
		$this->cassie->USER_SERVERS->remove($this->user_id, array($server_id));
	}
	
	public function remove_servers (array $server_ids)
	{
		$this->cassie->use_column_families(array('USER_SERVERS', 'USER_DELETED_SERVERS'));
		
		foreach ($server_ids as $id)
			$this->cassie->USER_DELETED_SERVERS->insert($this->user_id, 
			array($id => ''));
		
		$this->cassie->USER_SERVERS->remove($this->user_id, $server_ids);
	}

	public function get_user_terminated_servers ()
	{
		$servers = array();
		$this->cassie->use_column_families(array('SERVERS', 'USER_DELETED_SERVERS'));

		$server_ids = $this->cassie->USER_DELETED_SERVERS->get($this->user_id);
		$server_ids = array_keys($server_ids);
		
		$terminated = $this->cassie->SERVERS->multiget($server_ids);
		$out = array();
		// foreach($terminated as $id => $term)
		foreach($terminated as $term)
		{
			// if(!isset($term['type']))
			// {
				// $term = array_merge($term, array('type' => '256 Server'));
				// $this->cassie->SERVERS->insert($id, $term);
			// }
			$out[] = array(
				'name'		=> $term['name'],
				'provider'	=> $term['provider'],
				'state'		=> 'terminated',
				'type'		=> $term['type']
			);
		}
		
		return $out;
	}

	public function get_servers_details ($server_ids, $fields = array('name'))
	{
		$servers = array();
		$possible_fields = array('provider_server_id', 'name', 'provider', 'ip', 'image_id');
		$fields_to_retrieve = array();
		if (! is_array($fields)) $fields = array($fields);
		foreach ($fields as $field) {
			if (in_array($field, $possible_fields)) $fields_to_retrieve[] = $field;
		}
		if ($fields === array('*')) $fields_to_retrieve = $possible_fields;
		$columns = $fields_to_retrieve;
		
		$this->cassie->use_column_families(array('SERVERS'));
		
		foreach ($server_ids as $id) {
			$servers[] = $this->cassie->SERVERS->get($id, $columns);
		}
		return $servers;
	}
	
	public function get_server_ids($provider_server_ids)
	{
		$server_ids = array();
		if(!is_array($provider_server_ids)) $provider_server_ids = array($provider_server_ids);
		$user_servers = $this->get_user_servers();
		
		foreach ($user_servers as $id => $server)
		{
			if(in_array($server['provider_server_id'], $provider_server_ids))
				$server_ids[] = $id;
		}
		return $server_ids;
	}
}