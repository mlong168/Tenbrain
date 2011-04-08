<?php

class Common extends Controller {

	private $providers;

	function __construct()
	{
		parent::Controller();
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// more models to be added:
		$this->load->model('Amazon_model', 'amazon');
		$this->load->model('Gogrid_model', 'gogrid');
		
		$providers = array($this->amazon, $this->gogrid);
		$this->providers = array();
		foreach($providers as $provider)
		{
			$this->providers[$provider->name] = $provider;
		}
		unset($providers);
		
		header('Content-type: application/json');	// only xhr responses from this controller
		
		if(!$this->authentication->is_signed_in())
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> 'you do not have the permission to access this page'
			));
			die();
		}
	}

	function index()
	{
		error_reporting(E_ALL);
		header('Content-type: text/plain');
		print_r($this->list_instances());
		die(PHP_EOL . 'voila! this is a common controller index function');
	}

	function available_images()
	{
		$images = array(); $i = 0;
		foreach($this->providers as $provider)
		{
			$imgs = $provider->list_images();
			foreach($imgs as $img)
			{
				$images []= array_merge(array('id' => $i), $img);
				++$i;
			}
		}
		
		echo json_encode(array(
			'success'	=> true,
			'images'	=> $images
		));
	}
	
	function list_instances($state = 'running')
	{
		$sql = 'SELECT ui.instance_id, ui.provider, ui.provider_instance_id, ui.instance_name, ui.public_ip';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		// $sql .= ' NATURAL JOIN user_deleted_instances udi';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			$instances[$row->provider][] = array(
				'id'			=> $row->instance_id,
				'name'			=> $row->instance_name,
				'instance_id'	=> isset($row->provider_instance_id) ? $row->provider_instance_id : false,
				'instance_ip'	=> isset($row->public_ip) ? $row->public_ip : false // a must-have for gogrid
			);
		}
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$instances = array_merge($instances, $provider->list_instances($instances[$provider->name]));
			unset($instances[$provider->name]);
		}
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function reboot_instances()
	{
		$ids = json_decode($this->input->post('instances'));
		
		$sql = 'SELECT ui.provider, ui.provider_instance_id';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		// $sql .= ' NATURAL JOIN user_deleted_instances udi';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= ' AND ui.instance_id IN (' . implode(',', $ids) . ')';
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			if(!array_key_exists($row->provider, $instances)) $instances[$row->provider] = array();
			$instances[$row->provider][] = $row->provider_instance_id;
		}
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->reboot_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function stop_instances()
	{
		$ids = json_decode($this->input->post('instances'));
		
		$sql = 'SELECT ui.provider, ui.provider_instance_id';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		// $sql .= ' NATURAL JOIN user_deleted_instances udi';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= ' AND ui.instance_id IN (' . implode(',', $ids) . ')';
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			if(!array_key_exists($row->provider, $instances)) $instances[$row->provider] = array();
			$instances[$row->provider][] = $row->provider_instance_id;
		}
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->stop_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function terminate_instances()
	{
		$ids = json_decode($this->input->post('instances'));
		
		$sql = 'SELECT ui.provider, ui.provider_instance_id';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		// $sql .= ' NATURAL JOIN user_deleted_instances udi';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= ' AND ui.instance_id IN (' . implode(',', $ids) . ')';
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			if(!array_key_exists($row->provider, $instances)) $instances[$row->provider] = array();
			$instances[$row->provider][] = $row->provider_instance_id;
		}
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->terminate_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	/*
	 *	Load Balancer listing
	 */
	function list_load_balancers()
	{
		$user_id = $this->session->userdata('account_id');
		$sql = 'SELECT lb.load_balancer_id as id, lb.provider_lb_id as p_id, lb.provider, lb.name';
		$sql .= ' FROM user_load_balancers lb';
		$sql .= ' LEFT JOIN deleted_load_balancers dlb USING(load_balancer_id)';
		$sql .= ' WHERE dlb.load_balancer_id IS NULL';
		$sql .= ' AND lb.account_id = ' . $this->db->escape($user_id);
		
		$lbs = $load_balancers = array();
		$query = $this->db->query($sql);
		if($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$id = $row->id;
				$lb_pid = $row->p_id;
				$provider = $row->provider;
				
				// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
				if(!$lb_pid && $provider === 'GoGrid')
				{
					$lb_pid = $this->gogrid->assign_lb_id($id);
					if(!$lb_pid)
					{
						$load_balancers []= array(
							'id'		=> 0,
							'name'		=> $row->name,
							'provider'	=> 'GoGrid',
							'state'		=> 'pending',
							// ''	=> $lb->,
						);
						continue;
					}
				}
				
				if(!array_key_exists($provider, $lbs)) $lbs[$provider] = array();
				$lbs[$row->provider][$lb_pid] = $id;
			}
			foreach($this->providers as $provider)
			{
				if(!array_key_exists($provider->name, $lbs)) continue;
				$load_balancers = array_merge($load_balancers, $provider->list_load_balancers($lbs[$provider->name]));
			}
		}
		
		// print_r($lbs);die;
		echo json_encode(array(
			'success'			=> true,
			'load_balancers'	=> $load_balancers
		));
	}
	
	private function get_load_balancer($lb_id)
	{
		$this->db->select('provider, load_balancer_id as id, provider_lb_id as pid');
		$query = $this->db->get_where('user_load_balancers', array('load_balancer_id' => $lb_id));		
		return $query->num_rows() ? $query->row() : false;
	}
	
	function get_load_balanced_instances()
	{
		$lb = $this->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->get_load_balanced_instances($lb->id)
		));
	}
	
	function instances_available_for_lb()
	{
		$lb = $this->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->instances_available_for_lb($lb->id)
		));
	}
	
	function register_instances_within_lb()
	{
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->register_instances_within_lb($lb, $instance_ids)
		));
	}
	
	function deregister_instances_from_lb()
	{
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->deregister_instances_from_lb($lb, $instance_ids)
		));
	}
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */