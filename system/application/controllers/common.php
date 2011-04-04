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
		
		$this->providers = array($this->amazon, $this->gogrid);
		
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
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */