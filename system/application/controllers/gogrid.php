<?php

class Gogrid extends Controller {

	function __construct()
	{
		parent::__construct();
		
		//authentication stuff:
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// the model:
		$this->load->model('Gogrid_model', 'gg');
		
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
		$this->gg->test();
		die(PHP_EOL . 'voila! this is an gogrid controller index function');
	}
	
	function lookup($lookup)
	{
		print_r($this->gg->lookup($lookup));
	}
	
	function get_free_addresses()
	{
		$addresses = $this->gg->get_free_addresses();
		echo json_encode(array(
			'success'	=> (bool) $addresses,
			'addresses'	=> $addresses
		));
	}
	
	function get_available_ram_sizes()
	{
		$rams = $this->gg->get_available_ram_sizes();
		echo json_encode(array(
			'success'	=> (bool) $rams,
			'sizes'		=> $rams
		));
	}
	
	function launch_instance()
	{
		$params = array(
			'image'			=> $this->input->post('image_id'),
			'name'			=> $this->input->post('name'),
			'ip'			=> $this->input->post('address'),
			'server.ram'	=> $this->input->post('ram')
		);
		
		echo json_encode(array(
			'success' => $this->gg->launch_instance($params)
		));
	}
	
	function get_instance_password()
	{
		$password = $this->gg->get_password($this->input->post('instance_id'));
		$success = !empty($password);
		
		echo json_encode(array(
			'success'		=> $success,
			'error_message'	=> $success ? '' : 'You are not authorised to do this',
			'username'		=> $success ? $password['username'] : '',
			'password'		=> $success ? $password['password'] : '',
		));
	}
	
	function get_instances_for_lb()
	{
		$instances = $this->gg->get_instances_for_lb();
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function create_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->gg->create_load_balancer(
				$this->input->post('name'),
				$this->input->post('address'),
				$this->input->post('instances')
			)
		));
	}
	
	function delete_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->gg->delete_load_balancer($this->input->post('id'))
		));
	}
	
	function create_backup($instance_id,$name,$description)
	{
		echo json_encode(array(
			'success' => $this->gg->create_backup(
				//$this->input->post('instance_id'),
				//$this->input->post('name'),
				//$this->input->post('description')
				$instance_id,
				$name,
				$description
			)
		));
	}
	
	function created_backups()
	{
		echo json_encode(array(
			'success' => $this->gg->created_backups()
		));
	}
	
	function delete_backup($backup_id)
	{
		echo json_encode(array(
			'success' => $this->gg->delete_backup($backup_id)
		));
	}
	
	function backup_instance($provider_backup_id)
	{
		echo json_encode($this->gg->describe_backup_instance($provider_backup_id));
	}
	
	function restore_backup_to_corresponding_instance($provider_backup_id)
	{
		echo json_encode(array(
			'success' => $this->gg->restore_backup_to_corresponding_instance($provider_backup_id)
		));
		//echo json_encode(array(
		//	'success' => $this->gg->restore_snapshot_to_corresponding_instance($this->input->post('provider_backup_id'))
		//));
	}
	
	function restore_backup_to_new_instance($provider_backup_id)
	{
		echo json_encode(array(
			'success' => $this->gg->restore_backup_to_new_instance($provider_backup_id)
		));
	}
}

/* End of file gogrid.php */
/* Location: ./system/application/controllers/gogrid.php */