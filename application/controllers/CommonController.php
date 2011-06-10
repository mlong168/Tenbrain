<?php

class CommonController extends Zend_Controller_Action
{
	
	// private $supported_providers = array('Amazon', 'Rackspace', 'GoGrid');
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
		return Zend_Json::encode(array_merge(array('success' => true), $return));
	}
	
	private function failure_response($message, $additional_params = array())
	{
		return Zend_Json::encode(array_merge(array(
			'success'	=> false,
			'message'	=> $message
		), $additional_params));
	}
	
	public function indexAction()
	{
		print_r($this->providers['Amazon']->list_servers());
	}
	
	function createBackupAction()
	{
		$server_id = $this->getRequest()->getParam('instance_id');
		$servers_model = new Application_Model_Servers();
		
		$server = $servers_model->get_servers_details(array($server_id), array('provider', 'provider_server_id'));
		$server = $server[0];
		
		$backup = $this->providers[$server['provider']]->create_backup($server_id,
			$this->getRequest()->getParam('name'),
			$this->getRequest()->getParam('description')
		);
		
		return $backup ? $this->successfull_response('Snapshot has been created successfully') : $this->failure_response('Problem'); 
	}
	
	function listBackupsAction()
	{
		$i = 0; $backups = array();

		foreach($this->providers as $provider)
		{
			$bkps = $provider->created_backups();
			foreach($bkps as $bkp)
			{
				$backups[] = array_merge(array('id' => $i), (Array)$bkp);
				++$i;
			}
		}
		
		echo json_encode(array(
			'success'	=> true,
			'backups'	=> $backups
		));
	}
	
	function deleteBackupsAction()
	{
		$backup_ids = $this->getRequest()->getParam('backup_ids');
		$backup_ids = json_decode($backup_ids);
		
		foreach($backup_ids as $id)
		{
			$this->remove_backup($id);
		}
		
		echo json_encode(array(
			'success'	=> true
		));
	}
	
	function backupInstanceAction()
	{
		$backup_id = $this->getRequest()->getParam('backup_id');
		$backup_model = new Application_Model_Backups();
		
		$backup = $backup_model->get_backup_details(array($backup_id), array('provider', 'provider_backup_id'));

		$backup = $backup[0];
		$instances = $this->providers[$backup['provider']]->get_backuped_server($backup_id);

		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function restoreBackupToCorrespondingInstanceAction()
	{
		$backup_id 		= $this->getRequest()->getParam('backup_id');
		$backup_model 	= new Application_Model_Backups();

		$_backup = $backup_model->get_backup_by_id($backup_id);
		if(!$_backup)
			return $this->failure_response('Problem'); 
		$backup = $this->providers[$_backup['provider']]->restore_backup_to_corresponding_server($backup_id);
		return $backup ? $this->successfull_response('Snapshot has been deleted successfully') : $this->failure_response('Problem'); 
	}
	
	function restoreBackupToNewInstanceAction()
	{
		$backup_id 		= $this->getRequest()->getParam('backup_id');
		$name 			= $this->getRequest()->getParam('name');
		$server_type 	= $this->getRequest()->getParam('server_type');
		$ip 			= $this->getRequest()->getParam('ip_address');
		
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		$settings = array('name' => $name, 'type' =>  $server_type, 'ip' => $ip);
		$result = $this->providers[$backup['provider']]->restore_backup_to_new_server($backup_id, $settings);
		
		return $result 
			? $this->successfull_response('Snapshot has been deleted successfully') 
			: $this->failure_response('Problem'); 
	}
	
	function deleteBackupAction()
	{
		$backup_id = $this->getRequest()->getParam('backup_id');
		
		echo json_encode(array(
			'success'	=> $this->remove_backup($backup_id)
		));
	}
	
	function remove_backup($backup_id = null)
	{
		$backup_model = new Application_Model_Backups();
		$_backup = $backup_model->get_backup_by_id($backup_id);
		if(!$_backup)
			return $this->failure_response('Problem'); 
		$backup = $this->providers[$_backup['provider']]->delete_backup($backup_id);
		
		return $backup ? true : false; 
	}
	
	function viewBackupsAction()
	{
		$server_id = $this->getRequest()->getParam('server_id');
		$server_model = new Application_Model_Servers();
		$server = $server_model->get_servers_details(array($server_id), array('provider', 'provider_server_id'));		
		$server = $server[0];
		
		$backups = $this->providers[$server['provider']]->get_backups($server['provider'], $server['provider_server_id']);
		
		echo json_encode(array(
			'success'	=> true,
			'backups'	=> $backups
		));
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
		$servers_model = new Application_Model_Servers();
		
		if($state === 'terminated')
		{
			$terminated = $servers_model->get_user_terminated_servers();
			$out = count($terminated) ? $terminated : array();
			echo $this->successfull_response(array('instances' => $out));
			return false;
		}
		
		$servers = $servers_model->get_user_servers();
		unset($servers_model);
		$out = $provider_servers = array();
		foreach($servers as $id => &$row)
		{
			$pid = $row['provider_server_id'];
			$provider = $row['provider'];
			
			// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
			if(!$pid && $provider === 'GoGrid')
			{
				$pid = $this->providers['GoGrid']->assign_server_id($id);
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
			
			$provider_servers[$provider][$pid] = $id;
		}
		
		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $provider_servers))
			{
				$out = array_merge($out, $provider->list_servers($provider_servers[$provider->name], $state));
			}
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
	
	public function connectionInfoAction()
	{
		$server_id = $this->getRequest()->getParam('server_id');
		$server_model = new Application_Model_Servers();
		$server = $server_model->get_user_server($server_id);
		if(!$server)
		{
			echo $this->failure_response('Your server was not found in our database');
			return false;
		}
		
		$provider = $server['provider'];
		$connection_message = '';
		if(array_key_exists($provider, $this->providers))
		{
			$connection_message = $this->providers[$provider]->get_connection_info($server);
		}
		else
		{
			$connection_message = 'An error has occurred';
		}
		
		echo $this->successfull_response(array(
			'connection_message' => $connection_message
		));
		return false;
	}
	
	public function connectionParamsAction()
	{
		$server_id = $this->getRequest()->getParam('server_id');
		$server_model = new Application_Model_Servers();
		$server = $server_model->get_user_server($server_id);
		if(!$server)
		{
			echo $this->failure_response('Your server was not found in our database');
			return false;
		}
		
		$provider = $server['provider'];
		$connection_params = array();
		if(array_key_exists($provider, $this->providers))
		{
			$connection_params = $this->providers[$provider]->get_connection_params($server);
		}
		
		echo $this->successfull_response(array(
			'connection_params' => $connection_params
		));
		return false;
	}
	
	public function getAvailableServerTypesAction()
	{
		$provider = $this->getRequest()->getParam('provider');
		$response = '';
		if(in_array($provider, $this->supported_providers))
		{
			$types = $this->providers[$provider]->get_available_server_types();
			$response = $this->successfull_response(array('types' => $types));
		}
		else
		{
			$response = $this->failure_response('An error has occurred');
		}
		echo $response;
		return false;
	}
	
	public function modifyServerAction()
	{
		$request = $this->getRequest();
		$server_id = $request->getParam('server_id');
		$new_type = $request->getParam('server_type');
		
		$server_model = new Application_Model_Servers();
		$server = $server_model->get_user_server($server_id);
		
		$success = false;
		$provider = $server['provider'];
		if(array_key_exists($provider, $this->providers))
		{
			$success = $this->providers[$provider]->modify_server($server['provider_server_id'], $new_type, $server_id, $server);
		}
		
		echo $success ? $this->successfull_response() : $this->failure_response('Server modificaion failed');
		return false;
	}
	
	public function listLoadBalancersAction()
	{
		$balancer_model = new Application_Model_Balancer();
		$balancers = $balancer_model->get_user_load_balancers();
		echo $this->successfull_response(array('balancers' => $balancers));
		return false;
	}
	
	public function getServersForLoadBalancingAction()
	{
		$provider = $this->getRequest()->getParam('provider');
		$servers = array();
		if(in_array($provider, $this->supported_providers))
		{
			$balancer_model = new Application_Model_Balancer();
			$servers = $balancer_model->get_servers_for_lb($provider);
		}
		
		echo $this->successfull_response(array('servers' => $servers));
		return false;
	}
	
	public function createLoadBalancerAction()
	{
		$request = $this->getRequest();
		$name = $request->getParam('name');
		$provider = $request->getParam('provider');
		$servers = $request->getParam('instances');
		
		$success = false;
		if(in_array($provider, $this->supported_providers))
		{
			$server_model = new Application_Model_Servers();
			$server_ids = $server_model->get_user_server_provider_ids($servers, true);
			
			$success = $this->providers[$provider]->create_load_balancer($name, $server_ids, null);
		}
		
		echo $success
			? $this->successfull_response()
			: $this->failure_response('We are sorry, an error has occurred');
		return false;
	}
	
}
