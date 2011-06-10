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
	
	private function balancer_belongs_to_current_user($balancer_id)
	{
		if(!isset($this->cassie->USER_LOADBALANCERS))
		{
			$this->cassie->use_column_families(array('USER_LOADBALANCERS'));
		}
		
		$balancer = $this->cassie->USER_LOADBALANCERS->get($this->user_id, array($balancer_id));
		
		return !empty($balancer);
	}
	
	public function add_load_balancer(array $details)
	{
		$this->cassie->use_column_families(array('LOADBALANCERS', 'USER_LOADBALANCERS'));
		
		$uuid = uniqid('tlb-');
		$this->cassie->LOADBALANCERS->insert($uuid, $details);
		$this->cassie->USER_LOADBALANCERS->insert($this->user_id, array($uuid => ''));
		
		return $uuid;
	}

	public function delete_load_balancer($load_balancer_id)
	{
		$this->cassie->use_column_families(array('USER_DELETED_LOADBALANCERS', 'USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		
		$this->cassie->USER_DELETED_LOADBALANCERS->insert($this->user_id, array($load_balancer_id => ''));
		$this->cassie->USER_LOADBALANCERS->remove($this->user_id, array($load_balancer_id));
		$this->cassie->USER_LOADBALANCER_SERVERS->remove($load_balancer_id);
	}
	
	public function get_user_load_balancer($load_balancer_id)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'LOADBALANCERS'));
		
		return $this->balancer_belongs_to_current_user($load_balancer_id)
			? $this->cassie->LOADBALANCERS->get($load_balancer_id)
			: false;
	}
	
	public function get_user_load_balancers()
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'LOADBALANCERS'));
		
		$load_balancer_ids = $this->cassie->USER_LOADBALANCERS->get($this->user_id);
		$load_balancer_ids = array_keys($load_balancer_ids);
		
		return $this->cassie->LOADBALANCERS->multiget($load_balancer_ids);
	}
	
	public function remove_servers_from_lb($load_balancer_id, $server_ids)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->remove($load_balancer_id, $server_ids);
	}
	
	public function add_servers_to_lb($load_balancer_id, $server_ids)
	{
		$this->cassie->use_column_families(array('USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		$lb = $this->cassie->USER_LOADBALANCERS->get($this->user_id, $load_balancer_id); // Check if user have this LB
		if($lb)
			$this->cassie->USER_LOADBALANCER_SERVERS->insert($load_balancer_id, $server_ids);
	}

	public function get_servers_for_lb($provider)
	{
		$servers = array();
		$this->cassie->use_column_families(array('SERVERS', 'USER_SERVERS', 'USER_LOADBALANCERS', 'USER_LOADBALANCER_SERVERS'));
		
		$balanced_servers = array();
		$balancers = $this->cassie->USER_LOADBALANCERS->get($this->user_id);
		if(!empty($balancers))
		{
			$balancer_servers = $this->cassie->USER_LOADBALANCER_SERVERS->multiget(array_keys($balancers));
			foreach($balancer_servers as $bs) $balanced_servers = array_merge($balanced_servers, array_keys($bs));
		}
		
		$all_servers = $this->cassie->USER_SERVERS->get($this->user_id);
		$not_balanced_ids = array_diff(array_keys($all_servers), $balanced_servers);
		
		$to_register = $this->cassie->SERVERS->multiget($not_balanced_ids);
		foreach($to_register as $id => $server)
		{
			if($server['provider'] === $provider)
			{
				$servers []= array(
					'id'		=> $id,
					'name'		=> $server['name']
				);
			}
		}
		return $servers;
	}
	
	public function get_servers_available_for_lb($provider = "ALL", $load_balancer_id)
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