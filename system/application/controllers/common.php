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
		$this->load->model('Rackspace_model', 'rackspace');

		$providers = array($this->amazon, $this->gogrid, $this->rackspace);
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
		$this->load->model('Instance_model', 'instance');
		
		$instances = $this->instance->get_user_instances();
		$out = $provider_instances = array();
		foreach($instances as &$row)
		{
			$id = $row->id;
			$pid = $row->pid;
			$provider = $row->provider;
			
			// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
			if(!$pid && $provider === 'GoGrid')
			{
				$pid = $this->gogrid->assign_instance_id($id);
				if(!$pid)
				{
					$out []= array(
						'id'			=> 0,
						'name'			=> $row->name,
						'provider'		=> 'GoGrid',
						'state'			=> 'pending',
						'dns_name'		=> $row->ip,
						'ip_address'	=> $row->ip
						// ''			=> $row->, 
					);
					continue;
				}
			}
			
			$provider_instances[$row->provider][$pid] = $id;
		}
		
		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $provider_instances))
			{
				$out = array_merge($out, $provider->list_instances($provider_instances[$provider->name]));
			}
		}
		

		$instances = array();
		
		if($state == 'terminated')
		{
			$terminated = $this->instance->get_user_terminated_instances();
				echo json_encode(array(
				'success'	=> true,
				'instances'	=> isset($terminated) ? $terminated : array()
			));
			return;
		}
		
		foreach($out as $instance)
		{
			if($instance['state'] != 'stopped')
				$instances['running'][] = $instance;
			else
				$instances['stopped'][] = $instance;
		}
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> isset($instances[$state]) ? $instances[$state] : array()
		));
	}
	
	function reboot_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->reboot_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function stop_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->stop_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function terminate_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		
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
		$this->load->model('Balancer_model', 'balancer');
		
		$instances = $this->balancer->get_user_load_balancers();
		$lbs = $load_balancers = array();

		if(count($instances) > 0)
		{
			foreach($instances as $row)
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
	
	function get_load_balanced_instances()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->get_load_balanced_instances($lb->id)
		));
	}
	
	function instances_available_for_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->instances_available_for_lb($lb->id)
		));
	}
	
	function register_instances_within_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->register_instances_within_lb($lb, $instance_ids)
		));
	}
	
	function deregister_instances_from_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->deregister_instances_from_lb($lb, $instance_ids)
		));
	}
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */