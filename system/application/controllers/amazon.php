<?php

class Amazon extends Controller {

	function __construct()
	{
		parent::Controller();
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// the model:
		$this->load->model('Amazon_model', 'amazon');
		
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
		print_r($this->amazon->created_snapshots('i-152b0e79'));
		// print_r($this->amazon->created_snapshots());
		die(PHP_EOL . 'voila! this is an amazon controller index function');
	}

	function show_instances($state)
	{		
		$states = array('running', 'terminated', 'stopped');
		
		if(empty($state) || !in_array($state, $states))
		{
			$state = 'running';
		}
		
		echo json_encode($this->amazon->describe_instances($state));
	}

	function available_images()
	{
		echo json_encode($this->amazon->describe_images());
	}

	function launch_instance()
	{
		$image_id = $this->input->post('image_id');
		
		$available_types = array('t1.micro', 'm1.small', 'm1.large', 'm1.xlarge', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge', 'c1.medium', 'c1.xlarge', 'cc1.4xlarge', 'cg1.4xlarge');
		$type = $this->input->post('instance_type');
		if(!in_array($type, $available_types))
		{
			$type = 't1.micro';
		}
		
		$name = $this->input->post('instance_name');

		echo json_encode(array(
			'success' => $this->amazon->launch_instance($image_id, $type, $name)
		));
	}

	function terminate_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->terminate_instance($this->input->post('instance_id'))
		));
	}

	function start_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->start_instance($this->input->post('instance_id'))
		));
	}

	function stop_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->stop_instance($this->input->post('instance_id'))
		));
	}

	function reboot_instance()
	{
		echo json_encode(array(
			'success' => $this->amazon->reboot_instance($this->input->post('instance_id'))
		));
	}

	function created_snapshots()
	{
		echo json_encode($this->amazon->created_snapshots($this->input->post('instance_id')));
	}
	
	function create_snapshot()
	{
		echo json_encode(array(
			'success' => $this->amazon->create_snapshot(
				$this->input->post('instance_id'),
				$this->input->post('name'),
				$this->input->post('description')
			)
		));
	}
}

/* End of file amazon.php */
/* Location: ./system/application/controllers/amazon.php */