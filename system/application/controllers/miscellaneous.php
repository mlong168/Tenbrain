<?php

class Miscellaneous extends Controller {
	
	function __construct()
	{
		parent::Controller();
	}
	
	function index()
	{
	}
	
	function about()
	{
		$this->load->view('about');
	}
	
	function apology()
	{
		$this->load->view('apology');
	}
	
	function error()
	{
		$this->load->view('error');
	}
}

/* End of file miscellaneous.php */
/* Location: ./system/application/controllers/miscellaneous.php */