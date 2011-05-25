<?php

class Application_Model_Provider_Amazon extends Application_Model_Provider
{
	
	private $ec2;
	
	function __construct(){
		parent::__construct();
		
		// $this->ec2 = new AmazonEC2()
	}
	
	public function list_images()
	{
		
	}
	
	public function launch_server(array $params)
	{
		
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