<?php

class CommonController extends Zend_Controller_Action
{
	
	// private $supported_providers = array('Amazon', 'GoGrid', 'Rackspace');
	private $supported_providers = array('Amazon');
	private $providers;
	
	public function init()
	{
		foreach($this->supported_providers as $provider_name)
		{
			$model_name = 'Application_Model_Provider_' . $provider_name;
			$this->providers[$provider_name] = new $model_name();
		}
		
		// disable native views - only xhr responses here
		$helper = $this->_helper->getHelper('Layout');
		$this->layout =$helper->getLayoutInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		header('Content-type: application/json');
		
	}
	
	public function indexAction()
	{
		print_r($this->providers['Amazon']->list_servers());
	}
	
	public function availableImagesAction()
	{
		$images = array(); $i = 0;
		foreach($this->providers as $provider)
		{
			$imgs = $provider->list_images();
			foreach($imgs as $img)
			{
				$images []= array_merge(array('id' => $i), $img);
				++$i;
			}
		}
		
		echo Zend_Json_Encoder::encode(array(
			'success'	=> true,
			'images'	=> $images
		));
	}
	
	public function listInstancesAction()
	{
		if(0)
		{
			$state = $this->getRequest()->getParam('state');
			$servers = new Application_Model_Servers();
			
			if($state === 'terminated')
			{
				$terminated = $this->instance->get_user_terminated_instances();
				echo Zend_Json_Encoder::encode(array(
					'success'	=> true,
					'instances'	=> isset($terminated) ? $terminated : array()
				));
				return;
			}
			
			$instances = $servers->get_user_servers();
			$out = $provider_instances = array();
			foreach($instances as &$row)
			{
				$id = $row->id;
				$pid = $row->pid;
				$provider = $row->provider;
				
				// GoGrid-only exception - ids are not assigned immediatвely after creation, that sucks...
				if(!$pid && $provider === 'GoGrid')
				{
					$pid = $this->providers['GoGrid']->assign_instance_id($id);
					if(!$pid)
					{
						$out []= array(
							'id'			=> 0,
							'name'			=> $row->name,
							'provider'		=> 'GoGrid',
							'state'			=> 'pending',
							'dns_name'		=> $row->ip,
							'ip_address'	=> $row->ip
							// ''			=> $row->, 
						);
						continue;
					}
				}
				
				$provider_instances[$row->provider][$pid] = $id;
			}
			
			foreach($this->supported_providers as $provider)
			{
				if(array_key_exists($provider->name, $provider_instances))
				{
					$out = array_merge($out, $provider->list_instances($provider_instances[$provider->name]));
				}
			}
	
			$instances = array();
			
			foreach($out as $instance)
			{
				if($instance['state'] == 'terminated')
					continue;
				if($instance['state'] != 'stopped')
					$instances['running'][] = $instance;
				else
					$instances['stopped'][] = $instance;
			}

		}

		$out = array();
		foreach($this->providers as $provider)
		{
			$out = array_merge($out, $provider->list_servers());
		}
		
		echo Zend_Json_Encoder::encode(array(
			'success'	=> true,
			'instances'	=> $out
		));
	}
	
}
