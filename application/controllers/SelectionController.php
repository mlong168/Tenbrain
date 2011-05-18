<?php

class SelectionController extends Zend_Controller_Action
{
	
	private $selections = array(
		'tenstack'		=> array(
			'business'	=> array(
				'type'		=> 'big',
				'disabled'	=> true,
				'text'		=> 'Business<br />Stack'
			),
			'enterprise'=>  array(
				'type'		=> 'big',
				'disabled'	=> true,
				'text'		=> 'Enterprise<br />Stack'
			),
			'web'		=>  array(
				'type'		=> 'big',
				'disabled'	=> false,
				'text'		=> 'UC<br />Stack'
			)
		),
		'deployment'	=> array(
			'desktop'	=>  array(
				'type'		=> 'big',
				'disabled'	=> true,
				'text'		=> 'Desktop<br />Deployment'
			),
			'enterprise'=> array(
				'type'		=> 'big',
				'disabled'	=> true,
				'text'		=> 'Enterprise<br />Deployment'
			),
			'cloud'		=> array(
				'type'		=> 'big',
				'disabled'	=> false,
				'text'		=> 'Cloud<br />Deployment'
			)
		),
		'os'	=> array(
			'linux'		=> array(
				'type'		=> 'small',
				'disabled'	=> false,
				'text'		=> 'Linux'
			),
			'windows'	=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'Windows'
			),
			'mac'		=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'Mac'
			)
		),
		'vm'			=> array(
			'vmware'	=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'Vmware'
			),
			'citrix'	=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'Citrix Xen'
			),
			'kvm'		=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'Kvm'
			)
		),
		'providers'		=> array(
			'amazon'	=> array(
				'type'		=> 'small',
				'disabled'	=> false,
				'text'		=> 'AWS'
			),
			'gogrid'	=> array(
				'type'		=> 'small',
				'disabled'	=> true,
				'text'		=> 'GoGrid'
			),
			'rackspace'	=> array(
				'type'		=> 'small',
				'disabled'	=> false,
				'text'		=> 'Rackspace'
			)
		)
	);
	
	private $deploy_aliases = array(
		'desktop'	=> 'os',
		'enterprise'=> 'vm',
		'cloud'		=> 'providers'
	);
	
	private $selected;

	public function init()
	{
		$this->view->headTitle()->prepend('Welcome');
		$this->selected = new Zend_Session_Namespace('selection');
	}
	
	public function indexAction()
	{
		$this->_forward('tenstack');
	}
	
	public function errorAction()
	{
		// just show an error view
	}
	
	public function tenstackAction()
	{
		unset($this->selected->tenstack);
		unset($this->selected->deployment);
		
		$type = 'tenstack';
		$this->view->assign(array(
			'selections'=> $this->selections[$type],
			'type'		=> $type,
			'next'		=> 'selection/deployment/tenstack'
		));
	}
	
	public function deploymentAction()
	{
		$tenstack = $this->getRequest()->getParam('tenstack');
		if(in_array($tenstack, array_keys($this->selections['tenstack'])))
		{
			$this->selected->tenstack = $tenstack;
			
			$type = 'deployment';
			$this->view->assign(array(
				'selections'=> $this->selections[$type],
				'type'		=> $type,
				'next'		=> 'selection/finals/deployment'
			));
		}
		else
		{
			$this->_forward('error');
		}
	}
	
	public function finalsAction()
	{
		$deployment = $this->getRequest()->getParam('deployment');
		if(in_array($deployment, array_keys($this->selections['deployment'])))
		{
			$this->selected->deployment = $deployment;
			
			$this->view->assign(array(
				'selections'=> $this->selections[$this->deploy_aliases[$deployment]],
				'type'		=> $this->deploy_aliases[$deployment],
				'next'		=> 'selection/results/finals'
			));
		}
		else
		{
			$this->_forward('error');
		}
	}
	
	public function resultsAction()
	{
		if(isset($this->selected->deployment))
		{
			$deployment = $this->selected->deployment;
			$deploy_aliases = array(
				'desktop'	=> 'os',
				'enterprise'=> 'vm',
				'cloud'		=> 'providers'
			);
			
			$dep = $deploy_aliases[$deployment];
			$finals = $this->getRequest()->getParam('finals');
			
			if(in_array($finals, array_keys($this->selections[$dep])) && isset($this->selected->tenstack))
			{
				$tenstack = $this->selected->tenstack;
				$this->selected->selection = array(
					'tenstack'		=> $tenstack,
					'deployment'	=> $deployment,
					$dep			=> $finals // ????????????
				);
				
				$this->view->assign(array(
					'results'	=> array(
						'tenstack ' . $tenstack		=> $this->selections['tenstack'][$tenstack]['text'],
						'deployment ' . $deployment	=> $this->selections['deployment'][$deployment]['text'],
						$dep . ' ' . $finals		=> $this->selections[$dep][$finals]['text']
					)
				));
				return true;
			}
		}
		
		// being here means an error has occurred, so we just say that:
		$this->_forward('error');
	}
	
	public function confirmAction()
	{
		unset($this->selected->tenstack);
		unset($this->selected->deployment);
		
		$auth = Zend_Auth::getInstance();
		
		if(!$auth->hasIdentity())
		{
			// $this->session->set_userdata('sign_in_redirect', '/selection/confirm');
			$this->_redirect('auth/login');
		}
		
		if(!isset($this->selected->selection))
		{
			// throw some kind of error and exit from here
		}
		
		//print_r($this->session->userdata('selection'));
		$user_name = $auth->getIdentity()->username;
		//die;
		switch($this->selected->selection['providers'])
		{
			case 'rackspace':
				$this->load->model('Rackspace_model', 'rackspace');
				$this->rackspace->launch_instance('TenBrain UC Stack for ' . $user_name, 49, 1 );
			break;	
			case 'amazon':
				$this->load->model('Amazon_model', 'amazon');
				$this->amazon->launch_instance('ami-326c9f5b', 't1.micro', 'TenBrain UC Stack for ' . $user_name);
			break;
			case 'gogrid':
				echo 'Started GoGrid Instance';
				die;
				//$this->load->model('GoGrid_model', 'gogrid');
				//$this->gogrid->launch_instance('512MB', '5825', 'TenBrain UC Stack for ' . $user_name);
			break;
			default:
				$this->load->model('Amazon_model', 'amazon');
				$this->amazon->launch_instance('ami-326c9f5b', 't1.micro', 'TenBrain UC Stack for ' . $user_name);
			break;
		}
		unset($this->selected->selection);			
		
		$this->_redirect('console');
	}


}