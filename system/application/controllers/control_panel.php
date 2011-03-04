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
		$active_menu = 'running_instances';
		if($this->session->userdata('linked_accounts_active'))
		{
			$active_menu = 'account_linked';
		}
		else if($this->session->userdata('password_reseted'))
		{
			$active_menu = 'account_password';
		}
		
		if($this->authentication->is_signed_in())
		{
			$this->load->view('cp/main', array(
				'active_menu_item' => $active_menu
			));
		}
		else
		{
			redirect('account/sign_in');
		}
	}
	
	function menu()
	{
		$profile_active = $this->session->userdata('linked_accounts_active') || $this->session->userdata('password_reseted');
		$this->session->unset_userdata('linked_accounts_active');
		$this->session->unset_userdata('password_reseted');
		
		$menu = array();
		// instances: 
		$menu []= array(
			'text'		=> 'Instances Management',
			'expanded'	=> !$profile_active,
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
		);
		
		// images: 
		$menu []= array(
			'text'		=> 'Available Images',
			'children'	=> array(
				array(
					'text'	=> 'Images available for deployment',
					'id'	=> 'available_images',
					'leaf'	=> true
				)
			)
		);
		
		// snapshots:
		$menu []= array(
			'text'		=> 'Snapshots',
			'children'	=> array(
				array(
					'text'	=> 'Created Snapshots',
					'id'	=> 'snapshots',
					'leaf'	=> true
				)
			)
		);
		
		// load balancers:
		$menu []= array(
			'text'		=> 'Load Balancers',
			'children'	=> array(
				array(
					'text'	=> 'Load Balancers',
					'id'	=> 'load_balancers',
					'leaf'	=> true
				)
			)
		);
		
		$profile_menu = array(
			'text'		=> 'Your Profile',
			'expanded'	=> $profile_active,
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