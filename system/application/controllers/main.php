<?php

class Main extends Controller {

	function __construct()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->load->view('main');
	}
}

/* End of file main.php */
/* Location: ./system/application/controllers/main.php */