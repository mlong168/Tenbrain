<?php

class Control_panel extends Controller {
	
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
		if($this->authentication->is_signed_in())
		{
			$this->load->view('cp/main');
		}
		else
		{
			redirect('account/sign_in');
		}
	}
}

/* End of file control_panel.php */
/* Location: ./system/application/controllers/control_panel.php */