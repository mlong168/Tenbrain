<?php

class Rackspace extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
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
	
	function get_user_credentials()
	{
		echo json_encode(array(
			'success'	=> true,
			'credentials'		=> $this->rack->get_user_rackspace_credentials()
		));
	}
	
	function set_user_credentials()
	{
		$new_credentials = array();
		$new_credentials['key'] = $this->input->post('key');
		$new_credentials['username'] = $this->input->post('username');
		
		$credentials = $this->rack->get_user_rackspace_credentials();
		
		$result = $credentials 
			? $this->rack->update_user_rackspace_credentials($new_credentials)
			: $this->rack->set_user_rackspace_credentials($new_credentials);
		
		echo json_encode($result);
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
		$roleid = $this->acl->get_user_role_id();
		$allow_launch = $this->rack->allow_launch_instance($roleid);
		if(!$allow_launch)
		{
			echo json_encode(array(
				'success' => false
			));
			die;
		}
		
		echo json_encode(array(
			'success' => $this->rack->launch_instance(
				$this->input->post('name'),
				$this->input->post('image_id'),
				$this->input->post('flavor_id')
			)
		));
	}

	function get_instances_for_lb()
	{
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->rack->get_instances_for_lb()
		));
	}
	
	function create_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->rack->create_load_balancer(
				$this->input->post('name'),
				$this->input->post('instances')
			)
		));
	}
	
	function delete_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->rack->delete_load_balancer($this->input->post('id'))
		));
	}

	function create_backup()
	{
		echo json_encode(array(
			'success' => $this->rack->create_backup(
				$this->input->post('instance_id'),
				$this->input->post('name'),
				$this->input->post('description')
			)
		));
	}
	
	function created_backups()
	{
		echo json_encode(array(
			'success' => $this->rack->created_backups()
		));
	}
	
	function delete_backup()
	{
		echo json_encode(array(
			'success' => $this->rack->delete_backup($this->input->post('backup_id'))
		));
	}
	
	function backup_instance()
	{
		echo json_encode($this->rack->describe_backup_instance($this->input->post('backup_id')));
	}
	
	function restore_backup_to_corresponding_instance()
	{
		echo json_encode(array(
			'success' => $this->rack->restore_backup_to_corresponding_instance($this->input->post('backup_id'))
		));
	}
	
	function restore_backup_to_new_instance()
	{
		$instance = array(
			'backup_id'	=> $this->input->post('backup_id'),
			'name'	=>	$this->input->post('name'),
			'flavorId'	=>	$this->input->post('flavorId')
		);
		
		echo json_encode(array(
			'success' => $this->rack->restore_backup_to_new_instance($instance)
		));
	}
	
	function get_backup_status($id)
	{
		echo json_encode(array(
			'success' => $this->rack->get_backup_status($id)
		));
	}
}

/* End of file rackspace.php */
/* Location: ./system/application/controllers/rackspace.php */