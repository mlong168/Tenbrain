<?php

class Miscellaneous extends CI_Controller {
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
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