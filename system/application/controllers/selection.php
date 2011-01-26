<?php

class Selection extends Controller {

	function __construct()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->load->view('selection');
	}
}

/* End of file selection.php */
/* Location: ./system/application/controllers/selection.php */