<?php

class Application_Model_Provider_Rackspace extends Application_Model_Provider
{
	private $rack;
	private $user_id;
	function __construct(){
		parent::__construct();
		
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$rack_credentials = new Application_Model_DbTable_Credentials_Rackspace();
		$credentials = $rack_credentials->get_credentials($this->user_id);
		$this->rack = new ZendExt_Rackspace();
	}
	
	public function launch_server(array $params)
	{
		
	}
	
	public function list_images()
	{
		$out = array();
		$images = $this->rack->GET_request('images/detail');
		if(!$images) return $out;
		$images = $images->images;
		foreach($images as $image)
		{
			$out []= array(
				'image_id'	=> $image->id,
				'name'		=> $image->name,
				'provider'	=> $this->name,
				'state'		=> $image->status
			);
		}
		
		return $out;
	}
	
	public function list_servers($ids)
	{
		
	}

	public function start_servers(array $ids)
	{
		
	}
	
	public function stop_servers(array $ids)
	{
		
	}
	
	public function reboot_servers(array $ids)
	{
		
	}
	
	public function terminate_servers(array $ids)
	{
		
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
}