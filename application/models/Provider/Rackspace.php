<?php

class Application_Model_Provider_Rackspace extends Application_Model_Provider
{
	private $rack;
	private $storage;
	
	private $user_id;
	
	function __construct(){
		parent::__construct();
		
		$this->name = 'Rackspace';
		$this->storage = new Application_Model_Servers();
		
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		
		$rack_credentials = new Application_Model_DbTable_Credentials_Rackspace();
		$credentials = $rack_credentials->get_credentials($this->user_id);
		$this->rack = new ZendExt_Rackspace();
	}
	
	public function list_flavors()
	{
		$flavors = $this->rack->GET_request('flavors/detail');
		return empty($flavors) ? false : $flavors->flavors;
	}
	
	public function get_available_server_types()
	{
		$flavors = $this->list_flavors();
		$types = array();
		$premium = false;
		
		foreach($flavors as $flavor)
		{
			$types []= array(
				'name'		=> $flavor->name,
				'value'		=> $flavor->id,
				'available'	=> true,
				'reason'	=> 'Not available in a free version'
			);
		}
		return $types;
	}
	
	private function get_flavor_details($flavor_id)
	{
		$details = $this->rack->GET_request('flavors/' . $flavor_id);
		return empty($details) ? false : $details->flavor;
	}
	
	public function launch_server(array $params)
	{
		$setup = array(
			'server' => array(
				'name'		=> $params['name'],
				'imageId'	=> (int) $params['image_id'],
				'flavorId'	=> (int) $params['flavor_id']
			)
		);
		
		$server = $this->rack->POST_request('servers', $setup);
		if(!$server) return false;
		$server = $server->server;
		
		$type = $this->get_flavor_details($server->flavorId);
		
		$this->storage->add_server(array(
			// common to all providers
			'name'				=> $server->name,
			'provider_server_id'=> $server->id,
			'provider'			=> $this->name,
			'type'				=> $type->name,
			'image_id'			=> $server->imageId,
			
			// rackspace-specific data:
			'host_id'			=> $server->hostId,
			'flavor_id'			=> $server->flavorId,
			'root_password'		=> $server->adminPass,
			'private_ip'		=> $server->addresses->private[0],
			'public_ip'			=> $server->addresses->public[0]
		));
		
		return true;
	}
	
	public function list_images()
	{
		$out = array();
		$images = $this->rack->GET_request('images/detail');
		if(!$images) return $out;
		$images = $images->images;
		foreach($images as $image)
		{
			$out []= array(
				'image_id'	=> $image->id,
				'name'		=> $image->name,
				'provider'	=> $this->name,
				'state'		=> $image->status
			);
		}
		
		return $out;
	}
	
	public function list_servers($ids = array(), $state = 'running')
	{
		// there is no ability to start/stop servers in Rackspace, and we take terminated from the db only,
		// therefore this method can  be useful only for listing running servers here
		if($state !== 'running') return array();
		
		$out = array();
		$flavors = $this->list_flavors();
		$types = array();
		foreach($flavors as $flavor)
		{
			$types[$flavor->id] = $flavor->name;
		}
		foreach($ids as $pid => $db_id)
		{
			$server = $this->rack->GET_request('servers/' . $pid);
			if(!$server) continue;
			$server = $server->server;
			$ip = $server->addresses->public[0];
			
			$out[] = array(
				'id'				=> $db_id,
				'name'				=> $server->name,
				'dns_name'			=> $ip,
				'ip_address'		=> $ip,
				'image_id'			=> $server->imageId,
				'state'				=> $server->status === 'ACTIVE' ? 'running' : 'pending',
				'type'				=> $types[$server->flavorId],
				'provider'			=> $this->name
				// ''				=> $server->, 
			);
		}
		return $out;
	}

	public function get_connection_info(array $params)
	{
		$root_password = $params['root_password'];
		return
			"Use password '{$root_password}' and username 'root' to connect to the server by ssh."
			. '<br />Alternatively, you can use our web console';
	}
	
	// no start or stop for rackspace
	public function start_servers(array $ids)
	{
		return false;
	}
	
	public function stop_servers(array $ids)
	{
		return false;
	}
	
	public function reboot_servers(array $ids)
	{
		$data = array(
			'reboot' => array(
				'type' => 'SOFT'
			)
		);
		foreach($ids as $id)
		{
			$this->rack->POST_request('servers/' . $id . '/action', $data);
		}
		return true;
	}
	
	public function terminate_servers(array $ids)
	{
		foreach($ids as $id)
		{
			$this->rack->DELETE_request('servers/' . $id);
		}
		
		$this->storage->remove_servers(array_keys($ids));
		return true;
	}
	
	public function modify_server($server_id, $type, $tb_server_id, $all_params)
	{
		if(!is_numeric($type)) return false;
		
		$flavor_id = $type;
		$resize = array(
			'resize' => array(
				'flavorId'	=> (int) $flavor_id
			)
		);
		$response = $this->rack->POST_request('servers/' . $server_id . '/action' , $resize);
		
		$start_time = time();
		$timeout = 60 * 20;

		while($start_time + $timeout > time())
		{
			$response = $this->rack->GET_request('servers/' . $server_id);
			if($response->server->status == 'VERIFY_RESIZE')
			{
				$cofirm = array(
					'confirmResize' => NULL
				);
				
				$sucess_response = array(204);
				$response = $this->rack->POST_request('servers/'.$server_id.'/action' , $cofirm, $sucess_response);
				
				$type = $this->get_flavor_details($flavor_id);
				$all_params['flavor_id'] = $flavor_id;
				$all_params['type'] = $type->name;
				$this->storage->change_server($tb_server_id, $all_params);
			
				break;
			}
			else
			{
				sleep(15);
			}
		}
		return true;
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
	
	function create_backup($id, $name, $description = 'sample description')
	{
		$backup_model = new Application_Model_Backups();
		
		$server_id = $this->get_provider_server_id($id);
		if(!$server_id) return false;
		
		$server = $this->rack->GET_request('servers/' . $server_id);

		$status = $server->server->status;
		if(!$status == 'ACTIVE') $this->die_with_error('The snapshot could not be created from an instance yet');

		$setup = array(
			'image' => array(
				'serverId' => (int)$server_id,
				'name' => $name
			)
		);
		
		$response = $this->rack->POST_request('images',$setup);

		$backup_id = $response->image->id;
		$backup_model->add_backup(array(
			'provider_backup_id'	=>	(int)$backup_id, 
			'name' => $name,
			'description'	=> $description,
			'provider'	=> 'Rackspace',
		
			'server_id'	=>	(int)$server_id
		));
		return true;
	}
	
	private function start_backup_image($backup_image)
	{
		$flavorId = $backup_image['flavorId'];
		$provider_backup_id = $backup_image['imageId'];
		$name = $backup_image['name'];
		
		$server_model = new Application_Model_Servers();
		
		$setup = array(
			'server' => array(
				'name' => $name,
				'imageId' => (int)$provider_backup_id,
				'flavorId' => (int) $flavorId
			)
		);
		$response = $this->rack->POST_request('servers',$setup);
		// write to db if things went fine
		$server = $response->server;
		
		$backup_details = array(
			'name' => $server->name,
			'provider' => 'Rackspace',
			'provider_server_id' => $server->id,
		
			'ip' => $server->addresses->public[0]
		);
		//print_r($backup_details);
		$server_model->add_server($backup_details);
		return true;
	}
	
	public function restore_backup_to_corresponding_server($backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		if(!$backup)
			return false;
		$server = $this->rack->GET_request('servers/' . $backup['server_id']);
		if(isset($server->server) && $server->server->status == "ACTIVE")
		{
			$server_model = new Application_Model_Servers();
			$this->rack->DELETE_request('servers/' . $server->server->id);
			$server_ids = $server_model->get_server_ids($server->server->id);
			$server_model->remove_server($server_ids[0]);
		}
		else
			return false;
		$backup_image = array(
			'flavorId' => $server->server->flavorId,
			'imageId'	=> $backup['provider_backup_id'],
			'name'	=> $server->server->name
		);
		
		return $this->start_backup_image($backup_image);
	}
	
	public function restore_backup_to_new_server($backup_id, array $settings)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		if(!$backup)
			return false;
		
		$flavorId = $settings['type'];
		$name = $settings['name'];
		
		$backup_image = array(
			'flavorId' => $flavorId,
			'imageId'	=> $backup['provider_backup_id'],
			'name'	=> $name
		);
		return $this->start_backup_image($backup_image);
	}
	
	public function delete_backup($backup_id = false)
	{
		$backup_model = new Application_Model_Backups();
		
		$backup = $backup_model->get_backup_by_id($backup_id);
		
		if(!$backup) $this->die_with_error('No snapshot specified');

		$this->rack->DELETE_request('images/' . $backup['provider_backup_id']);	
		
		$backup_model->remove_backup($backup_id);
		return true;
	}
	
	public function get_backuped_server($backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);

		$server = $this->rack->GET_request('servers/' . $backup['server_id']);

		if(!$server)
			return array();
		$server = $server->server;
		
		$server_desrc = array(
			'id'				=> $server->id,
			'name'				=> (string) $server->name,
			'state'			=> (string) $server->status,
			'ip'				=> $server->addresses->public[0]
		);

		return $server_desrc;
	}
	
	function get_backups($provider, $instance_id)
	{
		return $this->view_backups($provider, $instance_id);
	}
	
	function get_backup_status($provider_backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_provider_id($provider_backup_id);
		if(!$backup)
			return false;
		$backup = $this->rack->GET_request('images/' . $backup->provider_backup_id);

		if(!isset($backup->image))
			return false;
			
		return $backup->image->status == "ACTIVE" ? 'completed' : $backup->image->status;
	}
	
	public function created_backups()
	{
		$backup_model = new Application_Model_Backups();
		$backups = $backup_model->get_available_backups("Rackspace");
		
		//foreach($backups as $i => $backup)
		//{
			//$backup['status'] = 'deleted';
			//$backup['status'] = $this->get_backup_status($backup->provider_backup_id);
		//	$backups[$i] = $backup;
		//}
		
		return $backups;
	}
}