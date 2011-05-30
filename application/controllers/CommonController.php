<?php

class CommonController extends Zend_Controller_Action
{
	
	private $supported_providers = array('Amazon', 'Rackspace', 'GoGrid');
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
	
	private function successfull_response($out = '')
	{
		$return = is_array($out) ? $out : array('message' => (string) $out);
		return Zend_Json_Encoder::encode(array_merge(array('success' => true), $return));
	}
	
	private function failure_response($message, $additional_params = array())
	{
		return Zend_Json_Encoder::encode(array_merge(array(
			'success'	=> false,
			'message'	=> $message
		), $additional_params));
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
		
		echo $this->successfull_response(array('images' => $images));
	}
	
	public function listInstancesAction()
	{
		$state = $this->getRequest()->getParam('state');
		$servers = new Application_Model_Servers();
		
		if($state === 'terminated')
		{
			$terminated = $servers->get_user_terminated_servers();
			echo Zend_Json_Encoder::encode(array(
				'success'	=> true,
				'instances'	=> count($terminated) ? $terminated : array()
			));
			return;
		}
		
		$instances = $servers->get_user_servers();
		$out = $provider_instances = array();
		foreach($instances as $id => &$row)
		{
			$pid = $row['provider_server_id'];
			$provider = $row['provider'];
			
			// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
			if(!$pid && $provider === 'GoGrid')
			{
				$pid = $this->providers['GoGrid']->assign_instance_id($id);
				if(!$pid)
				{
					$out []= array(
						'id'			=> 0,
						'name'			=> $row['name'],
						'provider'		=> 'GoGrid',
						'state'			=> 'pending',
						'dns_name'		=> $row['ip'],
						'ip_address'	=> $row['ip']
						// ''			=> $row->, 
					);
					continue;
				}
			}
			
			$provider_instances[$provider][$pid] = $id;
		}
		
		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $provider_instances))
			{
				$out = array_merge($out, $provider->list_servers($provider_instances[$provider->name]));
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

		echo $this->successfull_response(array('instances'	=> $out));
	}

	public function rebootInstancesAction()
	{
		$instance_ids = $this->getRequest()->getParam('instances');
		$instance_ids = Zend_Json_Decoder::decode($instance_ids);
		
		$storage = new Application_Model_Servers();
		$servers = $storage->get_user_server_provider_ids($instance_ids);

		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $servers))
			{
				$provider->reboot_servers($servers[$provider->name]);
			}
		}

		echo $this->successfull_response();
	}

	public function stopInstancesAction()
	{
		$instance_ids = $this->getRequest()->getParam('instances');
		$instance_ids = Zend_Json_Decoder::decode($instance_ids);
		
		$storage = new Application_Model_Servers();
		$servers = $storage->get_user_server_provider_ids($instance_ids);

		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $servers))
			{
				$provider->stop_servers($servers[$provider->name]);
			}
		}

		echo $this->successfull_response();
	}

	public function startInstancesAction()
	{
		$instance_ids = $this->getRequest()->getParam('instances');
		$instance_ids = Zend_Json_Decoder::decode($instance_ids);
		
		$storage = new Application_Model_Servers();
		$servers = $storage->get_user_server_provider_ids($instance_ids);

		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $servers))
			{
				$provider->start_servers($servers[$provider->name]);
			}
		}

		echo $this->successfull_response();
	}

	public function terminateInstancesAction()
	{
		$instance_ids = $this->getRequest()->getParam('instances');
		$instance_ids = Zend_Json_Decoder::decode($instance_ids);
		
		$storage = new Application_Model_Servers();
		$servers = $storage->get_user_server_provider_ids($instance_ids, true);

		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $servers))
			{
				$provider->terminate_servers($servers[$provider->name]);
			}
		}

		echo $this->successfull_response();
	}
	
}
