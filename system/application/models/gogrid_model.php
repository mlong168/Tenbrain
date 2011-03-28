<?php

class Gogrid_model extends Model {

	private $gogrid;

	function __construct()
	{
		parent::Model();
		$this->load->helper('gogrid');	// lousy, refactor that!		
		$this->gogrid = new GoGridClient();
	}
	
	public function get_images()
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
					'description'	=> $image->description,
					'state'			=> $image->state->name
					// ''	=> $image->,
				);
			}
			return $out;
		}
		else return false;
	}

	public function test()
	{
		$response = $this->get_images();		
		print_r($response);
		echo PHP_EOL;die;
	}
}