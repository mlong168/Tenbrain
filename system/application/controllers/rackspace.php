<?php

class Rackspace extends Controller {

	function __construct()
	{
		parent::Controller();
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// the model:
		$this->load->model('Rackspace_model', 'rack');		
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
		$this->rack->test();
		die(PHP_EOL . 'voila! this is a rackspace controller index function');
	}
	
	function get_flavors()
	{
		echo json_encode(array(
			'success'	=> true,
			'flavors'	=> $this->rack->list_flavors()
		));
	}
	
	function launch_instance()
	{
		echo json_encode(array(
			'success' => $this->rack->launch_instance(
				$this->input->post('name'),
				$this->input->post('image_id'),
				$this->input->post('flavor_id')
			)
		));
	}
	
	function terminate_instance()
	{
		echo json_encode(array(
			'success' => $this->rack->delete_instance($this->input->post('instance_id'))
		));
	}
	
	function reboot_instance()
	{
		echo json_encode(array(
			'success' => $this->rack->restart_instance($this->input->post('instance_id'))
		));
	}
}

/* End of file rackspace.php */
/* Location: ./system/application/controllers/rackspace.php */