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
		print_r($this->providers);
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
	
	function instances($state = 'running')
	{
		$instances = array(); $i = 0;
		foreach($this->providers as $provider)
		{
			$insts = $provider->list_instances();
			foreach($insts as $inst)
			{
				$instances = array_merge(array('id' => $i), $inst);
				++$i;
			}
		}
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */