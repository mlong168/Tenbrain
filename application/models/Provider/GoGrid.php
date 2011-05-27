<?php

class Application_Model_Provider_GoGrid extends Application_Model_Provider
{
	private $gogrid;
	function __construct(){
		parent::__construct();
		$this->name = "GoGrid";
		$this->gogrid = new ZendExt_GoGridClient();
	}
	
	public function launch_server(array $params)
	{
		
	}
	
	public function list_images()
	{
		$response = $this->gogrid->call('grid.image.list', array(
			'isPublic'		=> 'true',
			'image.type'	=> 'Web Server',
			'state'			=> 'Available'
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $image)
			{
				if(strpos(strtolower($image->os->name), 'ubuntu') !== false) $out []= array(
					'image_id'		=> $image->id,
					'provider'		=> 'GoGrid',
					'name'			=> $image->friendlyName,
					'location'		=> $image->location,
					'description'	=> isset($image->description) ? $image->description : "Image",
					'state'			=> $image->state->name
					// ''	=> $image->,
				);
			}
			return $out;
		}
		else return false;
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