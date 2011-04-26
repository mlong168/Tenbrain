<?php

include_once('provider_model.php');

class Rackspace_model extends Provider_model {
	
	private $server_url = '';
	private $auth_token = '';

	public $name = 'Rackspace';
	
	const USERNAME = 'tenbrain';
	const API_KEY = '7e7bd2615abdfc2734e66ba3441674ae';
	const VERSION = 'v1.0';
	const AUTH_URL = 'https://auth.api.rackspacecloud.com';

	function __construct()
	{
		parent::__construct();
		
		$auth_params = $this->authenticate();
		$this->auth_token = $auth_params['auth_token'];
		$this->server_url = $auth_params['server_management_url'];
	}
	
	private function authenticate()
	{
		$curl_session = curl_init(self::AUTH_URL . '/' . self::VERSION);
		
		$headers = array(
			'X-Auth-User: ' . self::USERNAME,
			'X-Auth-Key: ' . self::API_KEY
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		
		if($response->getResponseCode() !== 204) return false;
		$response_headers = $response->getHeaders();
		
		return array(
			'auth_token'			=> $response_headers['X-Auth-Token'],
			'storage_url'			=> $response_headers['X-Storage-Url'],
			'storage_token'			=> $response_headers['X-Storage-Token'],
			'cdn_management_url'	=> $response_headers['X-Cdn-Management-Url'],
			'server_management_url'	=> $response_headers['X-Server-Management-Url']
			// ''	=> $response_headers[''],
		);
	}

	private function GET_request($action, $success_response_codes = array(200, 203))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);
		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token,
			'Accept: application/json'
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		//print_r($response);
		$response =  new HttpMessage($response);
		if(!in_array($response->getResponseCode(), $success_response_codes)) return false;
		
		return json_decode($response->getBody());
	}
	
	private function DELETE_request($action, $success_response_codes = array(202, 204))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);
		return in_array($response->getResponseCode(), $success_response_codes);	
	}
	
	private function POST_request($action, $data, $success_response_codes = array(202))
	{
		$curl_session = curl_init($this->server_url . '/' . $action);		
		$headers = array(
			'X-Auth-Token: ' . $this->auth_token,
			'Content-Type: application/json'
		);

		curl_setopt($curl_session, CURLOPT_HEADER, true);
		curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_session, CURLOPT_POST, true);
		curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($curl_session);
		curl_close($curl_session);
		
		$response =  new HttpMessage($response);

		if(!in_array($response->getResponseCode(), $success_response_codes)) return false;
		$body = $response->getBody();
		return $body ? json_decode($body) : true;	
	}
	
	public function list_images()
	{
		$out = array();
		$images = $this->GET_request('images/detail');
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
	
	public function list_flavors()
	{
		$flavors = $this->GET_request('flavors/detail');
		return empty($flavors) ? false : $flavors->flavors;
	}

	public function launch_instance($name, $image_id, $flavor_id)
	{
		$setup = array(
			'server' => array(
				'name'		=> $name,
				'imageId'	=> (int) $image_id,
				'flavorId'	=> (int) $flavor_id,
			)
		);
		
		$instance = $this->POST_request('servers', $setup);
		if(!$instance) return false;
		
		$instance = $instance->server;
		$this->db->insert('user_instances', array(
			'account_id'			=> $this->session->userdata('account_id'),
			'provider_instance_id'	=> $instance->id,
			'instance_name'			=> $instance->name,
			'provider'				=> 'Rackspace',
			'public_ip'				=> $instance->addresses->public[0]
		));
		
		return true;
	}
	
	public function list_instances($instances = null)
	{
		$out = array();
		foreach($instances as $instance_id => $db_id)
		{
			$server = $this->GET_request('servers/' . $instance_id);
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
				'type'				=> $server->flavorId,
				'provider'			=> $this->name
				// ''				=> $server->, 
			);
		}
		return $out;
	}

	public function terminate_instances(array $ids)
	{
		foreach($ids as $id)
		{
			$this->DELETE_request('servers/' . $id);
		}
		
		$this->load->model('Instance_model', 'instance');
		$this->instance->terminate_instances($ids, $this->session->userdata('account_id'));
		
		return true;
	}
	
	// no start or stop for rackspace
	public function start_instances(array $ids)
	{
		return false;
	}
	
	public function stop_instances(array $ids)
	{
		return false;
	}

	public function reboot_instances(array $ids)
	{
		$data = array(
			'reboot' => array(
				'type' => 'SOFT'
			)
		);
		foreach($ids as $id)
		{
			$this->POST_request('servers/' . $id . '/action', $data);
		}
		return true;
	}
	
	public function get_instances_for_lb()
	{
		$account_id = $this->session->userdata('account_id');
		$this->load->model('Balancer_model', 'balancer');
		
		return $this->balancer->get_instances_for_lb($account_id,$this->name);
	}
	
	public function create_load_balancer($name, array $instances, $gg_lb_address = false)
	{
		$this->load->model('Instance_model', 'instances');
		$nodes = array();
		foreach(array_keys($instances) as $id)
		{
			$instance = $this->GET_request('servers/' . $id);
			$nodes []= array(
				'address'	=> $instance->server->addresses->private[0],
				'port'		=> '80',
				'condition'	=> 'ENABLED'
			);
		}
		$setup = array(
			'loadBalancer' => array(
				'name'			=> $name,
				'port'			=> '80',
				'protocol'		=> 'HTTP',
				'virtualIps'	=> array(
					array('type' => 'PUBLIC')
				),
				'nodes'			=> $nodes
			)
		);
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		$lb = $this->POST_request('loadbalancers', $setup);
		if(!$lb) return false;
		$this->load->model('Balancer_model', 'balancer');
		$lb = $lb->loadBalancer;
		$lb_id = $this->balancer->create_load_balancer($lb->name, $this->name, $lb->id, 'PUBLIC');
		foreach($instances as $instance)
		{
			$this->balancer->add_load_balancer_instances($lb_id, $instance['instance_id']);
		}
		return true;
	}
	
	public function list_load_balancers($ids)
	{
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		$response = $this->GET_request('loadbalancers');
		if(!$response) return array();
		
		$lbs = array();
		foreach($response->loadBalancers as $lb)
		{
			$id = $lb->id;
			if(array_key_exists($id, $ids))
			{
				$lbs[] = array(
					'id'		=> $ids[$id],
					'name'		=> $lb->name,
					'provider'	=> $this->name,
					'dns_name'	=> $lb->virtualIps[0]->address,
					'state'		=> $lb->status === 'ACTIVE' ? 'On' : 'pending'
					// ''	=> $lb->,
				);
			}
		}
		return $lbs;
	}

	public function register_instances_within_load_balancer($lb, $instances)
	{
		$this->load->model('Instance_model', 'instances');
		$instance_ids = $this->instances->get_instances_details($instances, 'provider_instance_id');
		
		foreach($instance_ids as &$id)
		{
			$id = $id->provider_instance_id;
		}
		$nodes = array();
		foreach($instance_ids as $id)
		{
			$instance = $this->GET_request('servers/' . $id);
			$nodes []= array(
				'address'	=> $instance->server->addresses->private[0],
				'port'		=> '80',
				'condition'	=> 'ENABLED'
			);
		}
		$setup = array(
			'nodes' => $nodes
		);
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		$this->POST_request('loadbalancers/' . $lb->pid . '/nodes', $setup);
		foreach($instances as $id)
		{
			$this->balancer->add_load_balancer_instances($lb->id, $id);
		}
		return true;
	}

	public function deregister_instances_from_lb($lb, $instances)
	{
		$this->load->model('Instance_model', 'instances');
		$instance_ids = $this->instances->get_instances_details($instances, array('provider_instance_id', 'instance_id'));
		$instances = array();
		foreach($instance_ids as $inst)
		{
			$instances[$inst->provider_instance_id] = $inst->instance_id;
		}
		$nodes = array();
		foreach(array_keys($instances) as $id)
		{
			$instance = $this->GET_request('servers/' . $id);
			$nodes[$instance->server->addresses->private[0]]= $id;
		}
		
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);		
		$node_ids = $this->GET_request('loadbalancers/' . $lb->pid . '/nodes');
		$node_ids = $node_ids->nodes;
		foreach($node_ids as $node)
		{
			$address = $node->address;
			if(array_key_exists($address, $nodes))
			{
				if($this->DELETE_request('loadbalancers/' . $lb->pid . '/nodes/' . $node->id))
				{
					$this->balancer->deregister_instance_from_lb($instances[$nodes[$address]], $lb->id);
				}
			}
		}
		return true;
	}
	
	public function delete_load_balancer($id)
	{
		$user_id = $this->session->userdata('account_id');
		$this->load->model('Balancer_model', 'balancer');
		$lb_id = $this->balancer->get_delete_load_balancer_id($id, $user_id); // should be only one
		
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		$this->DELETE_request('loadbalancers/' . $lb_id);		
		
		$this->balancer->delete_load_balancer($id,$user_id);
		
		return true;
	}
	
	function create_backup($id,$name,$description = 'sample description')
	{
		$this->load->model("Backup_model","backup");
		
		$instance_id = $this->get_provider_instance_id($id);
		if(!$instance_id) return false;
		
		$instance = $this->GET_request('servers/' . $instance_id);

		$status = $instance->server->status;
		if(!$status == 'ACTIVE') $this->die_with_error('The snapshot could not be created from an instance yet');

		$setup = array(
			'image' => array(
				'serverId' => (int)$instance_id,
				'name' => $name
			)
		);
		
		$response = $this->POST_request('images',$setup);

		$backup_id = $response->image->id;
		$this->backup->add_backup(array(
			'instance_id'	=>	(int)$instance_id,
			'provider_backup_id'	=>	(int)$backup_id, 
			'backup_name' => $name,
			'description'	=> $description,
			'provider'	=> 'Rackspace'
		));
		return true;
	}
	
	public function delete_backup($backup_id = false)
	{
		$this->load->model("Backup_model","backup");
		
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		if(!$backup) $this->die_with_error('No snapshot specified');

		$this->DELETE_request('images/' . $backup->provider_backup_id);	
		
		$this->backup->remove_backup($backup->provider_backup_id);
		return true;
	}

	public function created_backups()
	{
		$this->load->model('Backup_model','backup');
		$backups = $this->backup->get_available_backups("Rackspace");
		
		foreach($backups as $i => $backup)
		{
			$backup->status = 'deleted';
			$backup->status = $this->get_backup_status($backup->provider_backup_id);
			$backups[$i] = $backup;
		}
		
		return $backups;
	}
	
	public function describe_backup_instance($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');
		
		$instances = $this->get_backup_by_id($backup_id);
		
		return array(
			'success'	=> true,
			'instances'	=> $instances
		);
	}
	
	private function get_backuped_instance($backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);

		$instance = $this->GET_request('servers/' . $backup->instance_id);

		$instance = $instance->server;
		//print_r($instance);
		$instance_desrc = array(
			'id'				=> $instance->id,
			'name'				=> (string) $instance->name,
			'snapshot_id'		=> (string) $provaider_backup_id,
			//'capacity'			=> (string) ($instance->diskSize/1024) . 'GB',
			//'description'		=> (string) $instance->description,
			'status'			=> (string) $instance->status,
			'progress'			=> (string) $instance->progress,
			//'started'			=> $instance->started
			// ''				=> (string) $instance->,
		);

		return $instance_desrc;
	}
	
	private function start_backup_image($backup_image)
	{
		$flavorId = $backup_image['flavorId'];
		$provider_backup_id = $backup_image['imageId'];
		$name = $backup_image['name'];
		
		$this->load->model('Instance_model', 'instance');
		
		$setup = array(
			'server' => array(
				'name' => $name,
				'imageId' => (int)$provider_backup_id,
				'flavorId' => (int) $flavorId
			)
		);

		$response = $this->POST_request('servers',$setup);
		// write to db if things went fine
		$instance = $response->server;
		$this->instance->add_user_instance(array(
			'account_id' => $this->session->userdata('account_id'),
			'instance_name' => $instance->name,
			'provider' => 'Rackspace',
			'provider_instance_id' => $instance->id,
			'public_ip' => $instance->addresses->public[0]
		));
		
		return true;
	}
	
	public function restore_backup_to_corresponding_instance($backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);
		if(!$backup)
			return false;
			
		$instance = $this->GET_request('servers/' . $backup->instance_id);

		if(isset($instance->server) && $instance->server->status == "ACTIVE")
		{
			$this->load->model("Instance_model","instance");
			$this->DELETE_request('servers/' . $instance->server->id);
			$this->instance->terminate_instance($instance->server->id, $this->session->userdata('account_id'));
		}
		else
			return false;
		
		$backup_image = array(
			'flavorId' => $instance->server->flavorId,
			'imageId'	=> $backup->provider_backup_id,
			'name'	=> $instance->server->name
		);
		
		return $this->start_backup_image($backup_image);
	}
	
	public function restore_backup_to_new_instance($instance)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($instance['backup_id']);
		if(!$backup)
			return false;
		
		$backup_image = array(
			'flavorId' => $instance['flavorId'],
			'imageId'	=> $backup->provider_backup_id,
			'name'	=> $instance['name']
		);
		return $this->start_backup_image($backup_image);
	}
	
	function get_backup_status($provider_backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_provider_id($provider_backup_id);
		if(!$backup)
			return false;
		$instance = $this->GET_request('images/' . $backup->provider_backup_id);

		if(!isset($instance->image))
			return false;
			
		return $instance->image->status == "ACTIVE" ? 'completed' : $instance->image->status;
	}
	
	public function test()
	{
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		print_r($this->GET_request('loadbalancers'));
		// $images = $this->launch_instance('tenbrain first', 4, 1);
		// print_r($images);
		echo PHP_EOL; die;
	}
	
	public function get_load_balanced_instances($lb_pid, $lb_dbid)
	{
		$db_instances = $this->balancer->get_load_balanced_instances($lb_dbid);
		$instances = array();
		foreach(array_keys($db_instances) as $id)
		{
			$instance = $this->GET_request('servers/' . $id);
			$instances[$instance->server->addresses->private[0]]= $db_instances[$instance->server->id];
		}
		
		$this->server_url = str_replace('servers', 'ord.loadbalancers', $this->server_url);
		$nodes = $this->GET_request('loadbalancers/' . $lb_pid . '/nodes');
		$nodes = $nodes->nodes;
		
		$out = array();
		foreach($nodes as $node)
		{
			$out []= array(
				'id'				=> $instances[$node->address]['id'],
				'name'				=> $instances[$node->address]['name'],
				'ip_address'		=> $node->address,
				'healthy'			=> $node->status === 'ONLINE',
				'health_message'	=> $node->condition
			);
		}
		return $out;
	}
}