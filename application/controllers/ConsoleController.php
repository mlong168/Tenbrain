<?php

class ConsoleController extends Zend_Controller_Action
{
	
	private $layout;

	public function init()
	{
		$helper = $this->_helper->getHelper('Layout');
		$this->layout =$helper->getLayoutInstance(); 
	}
	
	public function indexAction()
	{
		/* set alternate layout */
		
		$this->layout->setLayout('control_panel');
		$this->view->headTitle()->prepend('Tenbrain Control Panel');
		
		$scripts = array(
			'extjs4/ext-all-debug',
			// 'cp/instances',
			// 'cp/images',
			// 'cp/snapshots',
			// 'cp/profile',
			// 'cp/transferer'
		);
		
		// if($this->account_type === 'premium') $scripts = array_merge($scripts, array('cp/load_balancers', 'cp/elastic_ips'));
		
		$scripts []= 'cp/cp';
		
		foreach($scripts as $script)
		{
			$this->view->headScript()->appendFile("/js/{$script}.js", 'text/javascript');
		}
	}
	
	public function menuAction()
	{
		// $profile_active = (bool) $this->session->userdata('active_menu_item');
		// $this->session->unset_userdata('active_menu_item');
		$profile_active = false;
		
		$menu = array();
		// Servers: 
		$menu []= array(
			'text'		=> 'Server Management',
			'expanded'	=> !$profile_active,
			'children'	=> array(
				array(
					'text'	=> 'Running Servers',
					'id'	=> 'running_instances',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Stopped Servers',
					'id'	=> 'stopped_instances',
					'leaf'	=> true
				),
				array(
					'text'	=> 'Terminated Servers',
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
			'text'		=> 'Backups',
			'children'	=> array(
				array(
					'text'	=> 'Created Backups',
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
		
		// elastic IP's
		$menu []= array(
			'text'		=> 'Elastic IPs',
			'children'	=> array(
				array(
					'text'	=> 'Elastic IPs',
					'id'	=> 'elastic_ips',
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
		
		// to be rewritten to zend_auth:
		// if($this->account_model->get_by_id($this->session->userdata('account_id'))->password)
		{
			$profile_menu['children'] []= array(
				'text'	=> 'Password',
				'id'	=> 'account_password',
				'leaf'	=> true
			);
		}
		
		$menu []= $profile_menu;
		
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		
		header('Content-type: application/json');
		echo Zend_Json_Encoder::encode($menu);
	}

}