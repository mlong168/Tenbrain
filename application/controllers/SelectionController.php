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

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
       // action body
       $type = 'tenstack';
       $this->view->selections = $this->selections[$type];
	   $this->view->type = $type;
	   $this->view->page_title = 'Welcome';
    }


}