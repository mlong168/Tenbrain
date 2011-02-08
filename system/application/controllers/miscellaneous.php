<?php

class Miscellaneous extends Controller {
	
	function __construct()
	{
		parent::Controller();
		
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