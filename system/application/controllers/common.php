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

		$this->load->model('Instance_model', 'instance');
// 		$this->load->model('Balancer_model', 'balancer');*/

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
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_list_instances($account_id);
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
		$user_id = $this->session->userdata('account_id');
		
		$load_balancers = $this->balancer->get_list_load_balancers($user_id);
		
		// print_r($lbs);die;
		echo json_encode(array(
			'success'			=> true,
			'load_balancers'	=> $load_balancers
		));
	}
	
	function get_load_balanced_instances()
	{
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->get_load_balanced_instances($lb->id)
		));
	}
	
	function instances_available_for_lb()
	{
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->instances_available_for_lb($lb->id)
		));
	}
	
	function register_instances_within_lb()
	{
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->register_instances_within_lb($lb, $instance_ids)
		));
	}
	
	function deregister_instances_from_lb()
	{
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->deregister_instances_from_lb($lb, $instance_ids)
		));
	}
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */