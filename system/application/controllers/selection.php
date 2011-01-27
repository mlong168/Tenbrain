<?php

class Selection extends Controller {

	private $_variants;
	
	function __construct()
	{
		parent::Controller();
		$this->_variants = array(
			'tenstack'		=> array(
				'business'	=> array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'Business<br />Application'
				),
				'enterprise'=>  array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'Enterprise<br />Application'
				),
				'web'		=>  array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'Web<br />Application'
				)
			),
			'deployment'	=> array(
				'desktop'	=>  array(
					'type'		=> 'big',
					'disabled'	=> false,
					'text'		=> 'Desktop<br />Deployment'
				),
				'enterprise'=> array(
					'type'		=> 'big',
					'disabled'	=> false,
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
					'disabled'	=> false,
					'text'		=> 'Windows'
				),
				'mac'		=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'Mac'
				)
			),
			'vm'			=> array(
				'vmware'	=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'Vmware'
				),
				'citrix'	=> array(
					'type'		=> 'small',
					'disabled'	=> false,
					'text'		=> 'Citrix Xen'
				),
				'kvm'		=> array(
					'type'		=> 'small',
					'disabled'	=> false,
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
					'disabled'	=> true,
					'text'		=> 'Rackspace'
				)
			)
		);
	}
	
	function index()
	{
		$this->tenstack();
	}
	
	function tenstack()
	{
		$this->session->unset_userdata('tenstack');
		$this->session->unset_userdata('deployment');
		
		$type = 'tenstack';
		$this->load->view('selection', array(
			'type'			=> $type,
			'next'			=> 'selection/deployment',
			'selections'	=> $this->_variants[$type]
		));	
	}
	
	function deployment($tenstack)
	{
		if(in_array($tenstack, array_keys($this->_variants['tenstack'])))
		{
			$this->session->set_userdata('tenstack', $tenstack);
			
			$type = 'deployment';
			$this->load->view('selection', array(
				'type'			=> $type,
				'next'			=> 'selection/finals',
				'selections'	=> $this->_variants[$type]
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));			
		}
	}
	
	function finals($deployment)
	{
		if(in_array($deployment, array_keys($this->_variants['deployment'])))
		{
			$this->session->set_userdata('deployment', $deployment);
			
			$deploy_aliases = array(
				'desktop'	=> 'os',
				'enterprise'=> 'vm',
				'cloud'		=> 'providers'
			);
			
			$this->load->view('selection', array(
				'type'			=> $deploy_aliases[$deployment],
				'next'			=> 'selection/results',
				'selections'	=> $this->_variants[$deploy_aliases[$deployment]]
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));
		}
	}
	
	function results($finals)
	{
		$deploy_aliases = array(
			'desktop'	=> 'os',
			'enterprise'=> 'vm',
			'cloud'		=> 'providers'
		);
		$deployment = $this->session->userdata('deployment');
		$dep = $deploy_aliases[$deployment];
		if(in_array($finals, array_keys($this->_variants[$dep])))
		{
			$tenstack = $this->session->userdata('tenstack');			
			$this->load->view('results', array(
				'results'	=> array(
					'tenstack ' . $tenstack		=> $this->_variants['tenstack'][$tenstack]['text'],
					'deployment ' . $deployment	=> $this->_variants['deployment'][$deployment]['text'],
					$dep . ' ' . $finals		=> $this->_variants[$dep][$finals]['text']
				)
			));
		}
		else
		{
			$this->load->view('error', array(
				'message' => 'selection failed'
			));
		}
	}
}

/* End of file selection.php */
/* Location: ./system/application/controllers/selection.php */