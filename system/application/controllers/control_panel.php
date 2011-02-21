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
	
	function menu()
	{
		$menu = array(
			array(
				'text'		=> 'Instances Management',
				'expanded'	=> true,
				'children'	=> array(
					array(
						'text'	=> 'Running Instances',
						'id'	=> 'running_instances',
						'leaf'	=> true
					),
					array(
						'text'	=> 'Stopped instances',
						'id'	=> 'stopped_instances',
						'leaf'	=> true
					),
					array(
						'text'	=> 'Terminated instances',
						'id'	=> 'terminated_instances',
						'leaf'	=> true
					)
				)
			),
			array(
				'text'		=> 'Available Images',
				'children'	=> array(
					array(
						'text'	=> 'Images available for deployment',
						'id'	=> 'available_images',
						'leaf'	=> true
					)
				)
			)
		);
		
		$profile_menu = array(
			'text'		=> 'Your Profile',
			'children'	=> array(
				array(
					'text'	=> 'Profile Information',
					'id'	=> 'account_profile',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Account Settings',
					'id'	=> 'account_settings',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Linked accounts',
					'id'	=> 'account_linked',
					'leaf'	=> true
				)
			)
		);
		
		if($this->account_model->get_by_id($this->session->userdata('account_id'))->password)
		{
			$profile_menu['children'] []= array(
				'text'	=> 'Password',
				'id'	=> 'account_password',
				'leaf'	=> true
			);
		}
		
		$menu []= $profile_menu;
		
		header('Content-type: application/json');
		echo json_encode($menu);
	}
}

/* End of file control_panel.php */
/* Location: ./system/application/controllers/control_panel.php */