<?php

class Application_Model_Balancer
{
	private $user_id;
	private $cassie;
	
	function __construct()
	{
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		$this->cassie = new ZendExt_Cassandra();
	}
	
	public function add_load_balancer(array $details)
	{
		$this->cassie->use_column_families(array('LOADBALANCERS', 'USER_LOADBALANCERS'));
		
		$uuid = uniqid('tb_server');
		$details['balancer_id'] = $uuid;
		$this->cassie->LOADBALANCERS->insert($uuid, $details);
		$this->cassie->USER_LOADBALANCERS->insert($this->user_id, 
		array($uuid => ''));
	}

	public function delete_load_balancer($load_balancer_id)
	{
		$this->cassie->use_column_families(array('USER_DELETED_LOADBALANCERS', 'USER_LOADBALANCERS'));
		
		$this->cassie->USER_DELETED_LOADBALANCERS->insert($this->user_id, 
			array($load_balancer_id => ''));
		$this->cassie->USER_LOADBALANCERS->remove($this->user_id, array($load_balancer_id));
	}
	public function get_user_load_balancer($load_balancer_id)
	{
		$this->cassie->use_column_families(array('LOADBALANCERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			return $this->cassie->LOADBALANCERS->get($load_balancer_id);
	}
	public function get_user_load_balancers()
	{
		$this->cassie->use_column_families(array('LOADBALANCERS'));
		
		$load_balancer_ids = $this->cassie->USER_LOADBALANCERS->get($this->user_id);
		$load_balancer_ids = array_keys($load_balancer_id);
		
		return $this->cassie->LOADBALANCERS->muntiget($load_balancer_ids);
	}
	
	public function remove_servers_in_lb($load_balancer_id, $server_ids)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->remove($load_balancer_id, $server_ids);
	}
	
	public function add_servers_in_lb($load_balancer_id, $server_ids)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->insert($load_balancer_id, $server_ids);
	}
	
	public function get_servers_available_fo_lb($provider = "ALL", $load_balancer_id)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id);
		if(!$lb)
			return array();
		
		$lb_servers = (array) $this->cassie->USER_LOADBALANCER_SERVERS->get($load_balancer_id);
		$user_servers = $this->get_user_servers();

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
	
	public function get_load_balanced_servers($load_balancer_id)
	{
		$this->cassie->use_column_families(array('LOADBALANCERS','USER_LOADBALANCER_SERVERS'));
		
		$server_ids = $this->cassie->USER_LOADBALANCER_SERVERS->get($load_balancer_id);
		$server_ids = array_keys($server_ids);
		
		return $this->cassie->LOADBALANCERS->multiget($server_ids);
	}
}