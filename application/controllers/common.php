<?php

class Common extends CI_Controller {

	private $supported_providers = array('Amazon', 'GoGrid', 'Rackspace'); // possibly to be added to config
	private $providers;

	function __construct()
	{
		parent::__construct();
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		// more models to be added:
		$this->load->model('Amazon_model', 'amazon');
		$this->load->model('Gogrid_model', 'gogrid');
		$this->load->model('Rackspace_model', 'rackspace');

		$providers = array($this->amazon, $this->gogrid, $this->rackspace);
		$this->providers = array();
		foreach($providers as $provider)
		{
			$this->providers[$provider->name] = $provider;
		}
		unset($providers);
		
		header('Content-type: application/json');	// only xhr responses from this controller
		
		if(!$this->authentication->is_signed_in())
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> 'you do not have the permission to access this page'
			));
			die();
		}
	}
	
	private function successfull_response($out = '')
	{
		$return = is_array($out) ? $out : array('message' => (string) $out);
		echo json_encode(array_merge(array('success' => true), $return));
		
		return true;
	}
	
	private function failure_response($message, $additional_params = array())
	{
		echo json_encode(array_merge(array(
			'success'	=> false,
			'message'	=> $message
		), $additional_params));
		die;
	}

	function index()
	{
		error_reporting(E_ALL);
		header('Content-type: text/plain');
		print_r($this->list_instances());
		die(PHP_EOL . 'voila! this is a common controller index function');
	}

	function available_images()
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
		
		echo json_encode(array(
			'success'	=> true,
			'images'	=> $images
		));
	}
	
	function backup_instance()
	{
		$backup_id = $this->input->post('backup_id');
		$this->load->model('Backup_model', 'backup');
		
		$backup = $this->backup->get_backup_details($backup_id, array('provider', 'provider_instance_id'));
		//print_r($backup_id);
		$backup = $backup[0];

		$instances = $this->providers[$backup->provider]->get_backuped_instance($backup_id);
		
		//print_r($instances);
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function create_backup()
	{
		$instance_id = $this->input->post('instance_id');
		$this->load->model('Instance_model', 'instance');
		

		$instance = $this->instance->get_instances_details($instance_id, array('provider', 'provider_instance_id'));
		$instance = $instance[0];

		$backup = $this->providers[$instance->provider]->create_backup($instance_id,
			$this->input->post('name'),
			$this->input->post('description')
		);
		
		return $backup ? $this->successfull_response('Snapshot has been created successfully') : $this->failure_response('Problem'); 
	}
	
	function restore_backup_to_corresponding_instance()
	{
		$backup_id = $this->input->post('backup_id');
		$this->load->model('Backup_model', 'backup');

		$_backup = $this->backup->get_backup_by_id($backup_id);
		if(!$_backup)
			return $this->failure_response('Problem I'); 
			
		$backup = $this->providers[$_backup->provider]->restore_backup_to_corresponding_instance($backup_id);
		//print_r($backup);
		return $backup ? $this->successfull_response('Snapshot has been deleted successfully') : $this->failure_response('Problem'); 
	}
	
	function restore_backup_to_new_instance()
	{
		$backup_id = $this->input->post('backup_id');
		
		$this->load->model('Backup_model', 'backup');
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		$name = $this->input->post('name');
		$server_type = $this->input->post('server_type');
		$ip = $this->input->post('ip_address');
		
		$settings = array('name' => $name, 'type' =>  $server_type, 'ip' => $ip);
		
		$result = $this->providers[$backup->provider]->restore_backup_to_new_instance($backup_id, $settings);
		
		return $result ? $this->successfull_response('Snapshot has been deleted successfully') : $this->failure_response('Problem'); 
	}
	
	function get_available_server_types()
	{
		$provider = $this->input->post('provider');
		if(!in_array($provider, $this->supported_providers)) return $this->failure_response('Provider not found');
		$types = $this->providers[$provider]->get_available_server_types();
		
		echo json_encode(array(
			'success'	=> true,
			'types'		=> $types
		));
	}
	
	function delete_backups()
	{
		$backup_ids = $this->input->post('backup_ids');
		$backup_ids = json_decode($backup_ids);
		
		foreach($backup_ids as $id)
		{
			$this->remove_backup($id);
		}
		
		echo json_encode(array(
			'success'	=> true
		));
	}
	
	function delete_backup()
	{
		$backup_id = $this->input->post('backup_id');
		
		echo json_encode(array(
			'success'	=> $this->remove_backup($backup_id)
		));
	}
	
	function remove_backup($backup_id = null)
	{
		$this->load->model('Backup_model', 'backup');
		$_backup = $this->backup->get_backup_by_id($backup_id);
		if(!$_backup)
			return $this->failure_response('Problem1'); 
		
		$backup = $this->providers[$_backup->provider]->delete_backup($backup_id);
		
		return $backup ? true : false; 
	}
	
	function list_backups()
	{
		$i = 0; $backups = array();

		foreach($this->providers as $provider)
		{
			$_backups = $provider->created_backups();
			
			foreach($_backups as $_backup)
			{
				$backups[] = array_merge(array('id' => $i), (Array)$_backup);
				++$i;
			}
		}
		
		echo json_encode(array(
			'success'	=> true,
			'backups'	=> $backups
		));
	}
	
	function view_backups()
	{
		$instance_id = $this->input->post('server_id');
		$this->load->model('Instance_model', 'instance');
		$instance = $this->instance->get_instances_details($instance_id, array('provider', 'provider_instance_id'));		
		$instance = $instance[0];

		$backups = $this->providers[$instance->provider]->get_backups($instance->provider, $instance->provider_instance_id);
		
		echo json_encode(array(
			'success'	=> true,
			'backups'	=> $backups
		));
	}
	
	function list_instances($state = 'running')
	{
		$this->load->model('Instance_model', 'instance');
		
		if($state == 'terminated')
		{
			$terminated = $this->instance->get_user_terminated_instances();
				echo json_encode(array(
				'success'	=> true,
				'instances'	=> isset($terminated) ? $terminated : array()
			));
			return;
		}
		
		$instances = $this->instance->get_user_instances();
		$out = $provider_instances = array();
		//print_r($instances);
		foreach($instances as &$row)
		{
			$id = $row->id;
			$pid = $row->pid;
			$provider = $row->provider;
			
			// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
			if(!$pid && $provider === 'GoGrid')
			{
				$pid = $this->gogrid->assign_instance_id($id);
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
		
		foreach($this->providers as $provider)
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
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> isset($instances[$state]) ? $instances[$state] : array()
		));
	}
	
	function reboot_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		foreach($this->providers as $provider)
		{
			if(array_key_exists($provider->name, $instances))
			{
				$provider->reboot_instances($instances[$provider->name]);
			}
		}
		
		echo json_encode(array('success' => true));
	}

	function modify_instance()
	{
		$this->load->model('Instance_model', 'instance');
		
		$id = $this->input->post('instance_id');
		$type = $this->input->post('instance_type');
		
		$instances = $this->instance->get_instances_details($id, array('provider_instance_id', 'provider'));

		foreach($instances as $instance)
		{
			$this->providers[$instance->provider]->modify_instance($instance->provider_instance_id, $type);
			echo json_encode(array('success' => true));
		}
	}
	
	function start_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id, $ids);
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->start_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function stop_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->stop_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	function terminate_instances()
	{
		$this->load->model('Instance_model', 'instance');
		
		$ids = json_decode($this->input->post('instances'));
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->instance->get_instances($account_id,$ids);
		
		foreach($this->providers as $provider)
		{
			if(!array_key_exists($provider->name, $instances)) continue;
			$provider->terminate_instances($instances[$provider->name]);
		}
		
		echo json_encode(array('success' => true));
	}
	
	/*
	 *	Load Balancer listing
	 */
	function list_load_balancers()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$balancers = $this->balancer->get_user_load_balancers();
		$lbs = $load_balancers = array();
		
		if(count($balancers) > 0)
		{
			foreach($balancers as $row)
			{
				$id = $row->id;
				$lb_pid = $row->p_id;
				$provider = $row->provider;
				
				// GoGrid-only exception - ids are not assigned immediately after creation, that sucks...
				if(!$lb_pid && $provider === 'GoGrid')
				{
					$lb_pid = $this->gogrid->assign_lb_id($id);
					if(!$lb_pid)
					{
						$load_balancers []= array(
							'id'		=> $id,
							'name'		=> $row->name,
							'provider'	=> 'GoGrid',
							'state'		=> 'pending',
							// ''	=> $lb->,
						);
						continue;
					}
				}
				
				if(!array_key_exists($provider, $lbs)) $lbs[$provider] = array();
				$lbs[$row->provider][$lb_pid] = $id;
			}

			foreach($this->providers as $provider)
			{
				if(array_key_exists($provider->name, $lbs))
				{
					$load_balancers = array_merge($load_balancers, $provider->list_load_balancers($lbs[$provider->name]));
				}
			}
		}

		// print_r($lbs);die;
		echo json_encode(array(
			'success'			=> true,
			'load_balancers'	=> $load_balancers
		));
	}
	
	function get_load_balanced_instances()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->get_load_balanced_instances($lb->pid, $lb->id)
		));
	}
	
	function instances_available_for_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $this->providers[$lb->provider]->instances_available_for_lb($lb->id)
		));
	}
	
	function instances_for_load_balancing()
	{
		$provider = $this->input->post('provider');
		
		if(!in_array($provider, $this->supported_providers)) return false;
		
		$this->load->model('Balancer_model', 'balancer');
		$instances = $this->balancer->get_instances_for_lb($this->session->userdata('account_id'), $provider);
		
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function create_load_balancer()
	{
		$name = $this->input->post('name');
		$instances = $this->input->post('instances');
		$provider = $this->input->post('provider');
		$gogrid_lb_address = $this->input->post('address');

		if(!in_array($provider, $this->supported_providers)) return $this->failure_response('sometheing bad happened...');
	
		$this->load->model('Instance_model', 'instances');
		$instances = $this->instances->get_instances_details($instances, array('instance_id', 'provider_instance_id', 'provider', 'public_ip'));
		
		$to_register = array();
		foreach($instances as $instance)
		{
			if($instance->provider === $provider)
			{
				$to_register[$instance->provider_instance_id] = array(
					'instance_id'	=> $instance->instance_id,
					'public_ip'		=> $instance->public_ip ? $instance->public_ip : 'not set'
				);
			}
		}
		if(empty($to_register)) return $this->failure_response('sometheing bad happened...');
		
		$this->providers[$provider]->create_load_balancer($name, $to_register, $gogrid_lb_address);
		
		return $this->successfull_response('Load balancer was created successfully');
	}

	function instances_for_registering_within_lb()
	{
		$lb_id = $this->input->post('lb_id');
		
		$this->load->model('Balancer_model', 'balancer');
		$instances = $this->balancer->get_instances_for_registering_within_lb($lb_id);
		
		return $this->successfull_response(array('instances' => $instances));
	}
	
	function register_instances_within_load_balancer()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$instance_ids = $this->input->post('instances');
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->register_instances_within_load_balancer($lb, $instance_ids)
		));
	}
	
	function deregister_instances_from_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$instance_ids = json_decode($this->input->post('instances'));
		$lb = $this->balancer->get_load_balancer($this->input->post('lb_id'));
		
		echo json_encode(array(
			'success' => $this->providers[$lb->provider]->deregister_instances_from_lb($lb, $instance_ids)
		));
	}
}

/* End of file common.php */
/* Location: ./system/application/controllers/common.php */