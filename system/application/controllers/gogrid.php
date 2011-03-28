<?php

class Gogrid extends Controller {

	function __construct()
	{
		parent::Controller();
		
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
}

/* End of file gogrid.php */
/* Location: ./system/application/controllers/gogrid.php */