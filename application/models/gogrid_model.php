<?php

include_once('provider_model.php');

class Gogrid_model extends Provider_model {

	public $gogrid;
	
	private $premium = true;
	private $default_type = "512MB";
	
	public $name = 'GoGrid';
	
	function __construct()
	{
		parent::__construct();
		
		$credentials = $this->get_user_gogrid_credentials();

		$this->load->helper('gogrid');
		$this->gogrid = $credentials 
						? new GoGridClient($credentials['key'],$credentials['secret_key'])
						: new GoGridClient();
	}
	
	private function test_response($response)
	{
		if($response->status === 'failure')
		{
			$error_message = '';
			// just to make sure:
			foreach($response->list as $item)
			{
				if($item->object === 'error') $error_message = $item->message;
			}
			$this->die_with_error($error_message);
		}
		return $response->status === 'success';
	}
	
	public function lookup($lookup)
	{
		$response = $this->gogrid->call('common.lookup.list', array(
			'lookup' => $lookup
		));
		return json_decode($response);
	}
	
	public function get_user_gogrid_credentials()
	{
		$credentials = array();
		$this->db->select('key, secret_key')
			->from('account_gogrid_credentials')
			->where('account_id', $this->session->userdata('account_id'));

		$query = $this->db->get();
		if(!$this->db->count_all_results()) return false;
		foreach ($query->result() as $row)
		{
			$credentials = array(
				'key'			=> $row->key,
				'secret_key'	=> $row->secret_key
			);
		}

		return $credentials;
	}
	
	private function validate_credentials($new_credentials)
	{
		$client =  new GoGridClient($new_credentials['key'],$new_credentials['secret_key']);	
		$response = $client->call('grid.ip.list');
		$response = json_decode($response);

		if(!isset($response->status))
			return false;
			
		return true;
	}
	
	public function set_user_gogrid_credentials($new_credentials)
	{
		$valid = $this->validate_credentials($new_credentials);
		if(!$valid)
			return array(
					'success'	=> false,
					'error_message'	=> "The security credentials you've provided do not seem to be valid. Please try again."
				);
		
		$this->db->set('account_id', $this->session->userdata('account_id'));
		$this->db->set('key', $new_credentials['key']);
		$this->db->set('secret_key', $new_credentials['secret_key']);
		
		$this->db->insert('account_gogrid_credentials');

		return array(
					'success'	=> true
				);
	}

	public function update_user_gogrid_credentials($new_credentials)
	{
		$valid = $this->validate_credentials($new_credentials);
		if(!$valid)
			return array(
					'success'	=> false,
					'error_message'	=> "The security credentials you've provided do not seem to be valid.<br />Please try again"
				);
				
		$this->db->where('account_id', $this->session->userdata('account_id'));
		$this->db->update('account_gogrid_credentials', $new_credentials);

		return array(
					'success'	=> true
				);
	}

	public function get_account_type()
	{
		$credentials = $this->get_user_gogrid_credentials();
		if(!$credentials) return 'basic';
		return 'premium';
	}
	
	public function list_images()
	{
		$response = $this->gogrid->call('grid.image.list', array(
			'isPublic'		=> 'true',
			'image.type'	=> 'Web Server',
			'state'			=> 'Available'
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $image)
			{
				if(strpos(strtolower($image->os->name), 'ubuntu') !== false) $out []= array(
					'image_id'		=> $image->id,
					'provider'		=> 'GoGrid',
					'name'			=> $image->friendlyName,
					'location'		=> $image->location,
					'description'	=> isset($image->description) ? $image->description : "Image",
					'state'			=> $image->state->name
					// ''	=> $image->,
				);
			}
			return $out;
		}
		else return false;
	}
	
	/*
	 * common for provider-specific models
	 * $input_ary is an indexed array that contains associative of the form:
	 * 'id'				- db id of the instance
	 * 'instance_id'	- provider-specific instance id
	 * 'name'	- instance name
	 * 'instance_ip'	- an IP address of the instance
	 */
	public function list_instances($ids, $state = 'running')
	{
		$response = $this->gogrid->call('grid.server.get', array(
			'id' => array_keys($ids)
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$instances = array();
		foreach($response->list as $server)
		{
			$p_id = $server->id;
			$ip = $server->ip->ip;
			$instances []= array(
				'id'				=> $ids[$p_id],
				'name'				=> $server->name,
				'dns_name'			=> $ip,
				'ip_address'		=> $ip,
				'image_id'			=> $server->image->id,
				'state'				=> $server->state->name === 'On' ? 'running' : 'stopped',
				'type'				=> $server->ram->description,
				'provider'			=> $this->name
				// ''				=> $server->, 
			);
		}
		
		return $instances;
	}
	
	public function assign_instance_id($id)
	{
		$this->db->select('instance_name as name, public_ip as ip');
		$query = $this->db->get_where('user_instances', array('instance_id' => $id));
		$row = $query->row(); $name = $row->name; $ip = $row->ip;
		
		$response = $this->gogrid->call('grid.server.get', array('name' => $name));
		$response = json_decode($response);
		
		if($response->status !== 'success') return false;
		
		$pid = false;
		foreach($response->list as $server)
		{
			if($server->ip->ip === $ip)
			{
				$pid = $server->id;
				$this->db->where('instance_id', $id);
				$this->db->update('user_instances', array(
					'provider_instance_id' => $pid
				));
			}
		}
		
		return $pid;
	}
	
	public function get_instances()
	{
		$this->load->model('Instance', 'instance');
		
		$instances = $this->instance->get_user_instances();
		$names = array();
		$empties = array();
		$ids = array();
		foreach($instances as $row)
		{
			$names []= $row->name;
			if(is_null($row->pid))
			{
				$empties[$row->ip] = $row->name;
			}
			else
			{
				$ids[$row->pid] = $row->id;
			}
		}
		if(empty($names)) return array();
		
		$response = $this->gogrid->call('grid.server.get', array(
			'name' => $names
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $server)
			{
				$id = isset($server->id) ? $server->id : false;
				$ip = $server->ip->ip;
				if($id)
				{
					$state = $server->state->name === 'On' ? 'running' : 'terminated';
				}
				else
				{
					$state = 'pending';
				}
				
				if($id && isset($empties[$ip]))
				{
					$id = $this->instance->get_updated_instance_id(array(
						'public_ip' => $ip,
						'instance_name' => $empties[$ip],
						'id' => $id
					));
				}
				
				if(isset($ids[$id])) $id = $ids[$id];
				
				$out []= array(
					'name'				=> $server->name,
					'instance_id'		=> $id,
					'dns_name'			=> $ip,
					'ip_address'		=> $ip,
					'image_id'			=> $server->image->id,
					'state'				=> $state,
					'type'				=> $server->type->name,
					'provider'			=> 'GoGrid'
					// ''				=> $server->, 
				);
			}
			return $out;
		}
		else return false;		
	}
	
	public function get_free_addresses()
	{
		$response = $this->gogrid->call('grid.ip.list', array(
			'ip.state'	=> 'Unassigned',
			'ip.type'	=> 'Public'
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $address)
			{
				$out []= array(
					'address' => $address->ip
				);
			}
			return $out;
		}
		else return false;
	}
	
	public function get_available_server_types()
	{
		$types = $this->get_available_ram_sizes();

		foreach($types as $i => &$type)
		{
			//$available = $this->premium ? true : $_type['size'] === $this->default_type; 
			$available = $this->premium;
			$type = array(
				'id'		=> $i,
				'value'		=> $type['size'],
				'name'		=> $type['description'],
				'available'	=> $available,
				'reason'	=> $available ? '' : 'Not available for basic account'
			);
		}
		
		return $types;
	}
	
	public function get_available_ram_sizes()
	{
		$response = $this->gogrid->call('common.lookup.list', array(
			'lookup' => 'server.ram'
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $ram)
			{
				$out []= array(
					'size' => $ram->name,
					'description' => $ram->description
				);
			}
			return $out;
		}
		else return false;
	}
	
	public function allow_launch_instance($roleid)
	{
		$loaded_servers_count = (int)$this->get_deployed_server_count();
		$available_server_count = (int)$this->account_server_count_limits[$this->name][$roleid];
		
		return ($available_server_count >= $loaded_servers_count);
	}
	
	public function launch_instance($params)
	{
		$response = $this->gogrid->call('grid.server.add', $params);
		$response = json_decode($response);

		$this->test_response($response);
		
		$this->load->model('Instance_model', 'instance');
		
		// write to db if things went fine
		$instance = $response->list[0];
		// print_r($instance);
		$this->instance->add_user_instance(array(
			'account_id' => $this->session->userdata('account_id'),
			'instance_name' => $instance->name,
			'provider' => 'GoGrid',
			'public_ip' => $instance->ip->ip
		));
		return true;
	}
	
	public function modify_instance($instance_id, $instance_type)
	{
		$ram_size = $instance_type;
		$response = $this->gogrid->call('grid.server.edit', array(
			'id'			=> $instance_id,
			'server.ram'	=> $ram_size 
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		return true;
	}
	
	public function get_password($instance_id)
	{
		$response = $this->gogrid->call('support.password.list');
		$response = json_decode($response);
		$this->test_response($response);
		
		$instance_id = $this->get_provider_instance_id($instance_id);
		foreach($response->list as $pass)
		{
			if(!isset($pass->server)) continue;
			
			if($pass->server->id === (int)$instance_id)
			{
				return array(
					'username'	=> $pass->username,
					'password'	=> $pass->password,
				);
			}
		}
		
		return false;
	}
	
	private function power_instance($instance_id, $action)
	{
		// $instance_id = $this->get_provider_instance_id($instance_id);
		$response = $this->gogrid->call('grid.server.power', array(
			'id'	=> $instance_id,
			'power'	=> $action
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		return true;
	}
	
	public function reboot_instances(array $instance_ids)
	{
		foreach($instance_ids as $instance_id)
		{
			$this->power_instance($instance_id, 'restart');
		}
		return true;
	}
	
	public function stop_instances(array $instance_ids)
	{
		foreach($instance_ids as $instance_id)
		{
			$this->power_instance($instance_id, 'stop');
		}
		return true;
	}
	
	public function start_instances(array $instance_ids)
	{
		foreach($instance_ids as $instance_id)
		{
			$this->power_instance($instance_id, 'start');
		}
		return true;
	}
	
	
	
	public function terminate_instances(array $instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		
		foreach($instance_ids as $instance_id)
		{
			$response = $this->gogrid->call('grid.server.delete', array(
				'id' => $instance_id
			));
			$response = json_decode($response);
			$this->test_response($response);
		}
		
		$this->load->model('Instance_model', 'instance');
		$this->instance->terminate_instances($instance_ids, $this->session->userdata('account_id'));
		
		return true;
	}
	
	public function get_instances_for_lb()
	{
		$account_id = $this->session->userdata('account_id');
		$this->load->model('Balancer_model', 'balancer');
		
		return $this->balancer->get_instances_for_lb($account_id, $this->name);
	}
	
	public function create_load_balancer($name, array $instances, $ip)
	{	
		$ips = array();
		foreach($instances as $inst)
		{
			$ips []= $inst['public_ip'];
		}

		$response = $this->gogrid->call('grid.loadbalancer.add', array_merge(array(
			'name'				=> $name,
			'virtualip.ip'		=> $ip,
			'virtualip.port'	=> 80
		), $this->form_realip_array($ips)));
		$response = json_decode($response);
		$this->test_response($response);
		
		$lb = $response->list[0];
		$this->load->model('Balancer_model', 'balancer');
		$lb_id = $this->balancer->create_load_balancer($lb->name, $this->name, null, $ip);
		
		// a bit unreliable, should more relay on $lb->realiplist than on $instances
		foreach($instances as $instance)
		{
			$this->balancer->add_load_balancer_instances($lb_id, $instance['instance_id']);
		}
		return true;
	}
	
	// used to assign id if not yet present; failes at first times though
	// it takes gogrid around 30 sec to give back a response with a valid id
	public function assign_lb_id($id)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$user_load_balancer = $this->balancer->get_user_load_balancer($id); 
		
		$name = $user_load_balancer->name; $ip = $user_load_balancer->ip_address;
		
		$response = $this->gogrid->call('grid.loadbalancer.get', array('name' => $name));
		$response = json_decode($response);
		
		if($response->status !== 'success') return false;
		
		$lb_pid = $response->list[0];
		$lb_pid = $lb_pid->id;
		
		$this->balancer->update_user_load_balancer($id, $lb_pid);
		
		return $lb_pid;
	}
	
	public function delete_load_balancer($id)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$user_id = $this->session->userdata('account_id');

		$lb_id = $this->balancer->get_delete_load_balancer_id($id, $user_id); // should be only one
		if(!$lb_id) $this->die_with_error('The load balancer you have requested was not found or is not available to operate yet');
		
		$response = $this->gogrid->call('grid.loadbalancer.delete', array('id' => $lb_id));
		$response = json_decode($response);
		$this->test_response($response);
		
		$this->balancer->delete_load_balancer($id, $user_id);
		
		return true;
	}
	
	public function list_load_balancers($ids)
	{
		$response = $this->gogrid->call('grid.loadbalancer.get', array(
			'id' => array_keys($ids)
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$lbs = array();
		foreach($response->list as $lb)
		{
			$lbs []= array(
				'id'		=> $ids[$lb->id],
				'name'		=> $lb->name,
				'provider'	=> $this->name,
				'dns_name'	=> $lb->virtualip->ip->ip,
				'state'		=> 'On'
				// ''	=> $lb->,
			);
		}
		
		return $lbs;
	}
	
	public function get_load_balanced_instances($lb_pid, $lb_dbid)
	{
		$rows = $this->balancer->get_load_balanced_instances($lb_dbid);
		$names = array();
		foreach($rows as $row)
		{
			$names[$row['ip_address']] = array(
				'id'	=> $row['id'],
				'name'	=> $row['name']
			);
		}
		$response = $this->gogrid->call('grid.loadbalancer.get', array(
			'id' => $lb_pid
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$instances = array();
		foreach($response->list[0]->realiplist as $instance)
		{
			$ip = $instance->ip->ip;
			if(array_key_exists($ip, $names))
			{	
			$instances []= array(
				'id'				=> $names[$ip]['id'],
				'name'				=> $names[$ip]['name'],
				'ip_address'		=> $ip,
				'healthy'			=> (int) $instance->ip->state->id === 2,
				'health_message'	=> $instance->ip->state->description
				// ''	=> $lb->,
			);
			}
		}
		
		return $instances;
	}
	
	function instances_available_for_lb($lb_id)
	{
		$this->load->model('Instance_model', 'instance');
		
		$provider = $this->db->escape($this->name);
		$account_id = $this->db->escape($this->session->userdata('account_id'));
		$lb_id = $this->db->escape($lb_id);
		
		$instances = $this->instance->get_instances_available_for_lb($provider,$account_id,$lb_id);
		
		return $instances;
	}
	
	private function form_realip_array($ips, $port = 80)
	{
		$real_ips = array();$i = 0;
		foreach($ips as $ip)
		{
			$real_ips['realiplist.' . $i . '.ip'] = $ip;
			$real_ips['realiplist.' . $i . '.port'] = $port;
			++$i;
		}
		return $real_ips;
	}
	
	function register_instances_within_load_balancer($lb, $instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Balancer_model', 'balancer');
		
		$to_register = array();
		$instances = $this->instance->get_instances_details($instance_ids, array('public_ip', 'instance_id'));
		foreach($instances as $inst)
		{
			$to_register[$inst->public_ip] = $inst->instance_id;
		}
		
		$already_registered = $this->balancer->get_load_balanced_instances($lb->id);
		$left = array();
		foreach($already_registered as $inst)
		{
			$ip = $inst['ip_address'];
			$to_register[$ip] = $left[$ip] = $inst['id'];
		}
		
		$response = $this->gogrid->call('grid.loadbalancer.edit', array_merge(array(
			'id' => $lb->pid
		), $this->form_realip_array(array_keys($to_register))));
		$response = json_decode($response);
		$this->test_response($response);
		
		// rewrite it to relay more on an realiplist from response, when will have free time
		$registered = array_diff($to_register, $left);
		foreach(array_values($registered) as $id)
		{
			$this->balancer->add_load_balancer_instances($lb->id, $id);
		}
		
		return true;
	}
	
	function deregister_instances_from_lb($lb, $instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Balancer_model', 'balancer');
		
		$left = array();
		$registered = $this->balancer->get_load_balanced_instances($lb->id);
		foreach($registered as $inst)
		{
			$left[$inst['ip_address']] = $inst['id'];
		}
		
		$to_deregister = array();
		$instances = $this->instance->get_instances_details($instance_ids, array('public_ip', 'instance_id'));
		foreach($instances as $inst)
		{
			$to_deregister[$inst->public_ip] = $inst->instance_id;
		}
		$left = array_diff($left, $to_deregister);

		if(!count($left))
		{
			$this->die_with_error('Please leave at least one');
		}
		
		$response = $this->gogrid->call('grid.loadbalancer.edit', array_merge(array(
			'id' => $lb->pid
		), $this->form_realip_array(array_keys($left))));
		$response = json_decode($response);
		$this->test_response($response);
		
		// rewrite it to relay more on an realiplist from response, when will have free time
		foreach(array_values($to_deregister) as $id)
		{
			$this->balancer->deregister_instance_from_lb($id, $lb->id);
		}
		
		return true;
	}

	public function test()
	{
		// $response = $this->gogrid->call('support.password.list');
		// $response = json_decode($response);	
		// print_r($response);
		print_r($this->list_load_balancers());
		echo PHP_EOL;die;
	}
	
	function create_backup($id,$name,$description = 'sample description')
	{
		$this->load->model("Backup_model","backup");
		
		$instance_id = $this->get_provider_instance_id($id);
		if(!$instance_id) return false;
		
		$response = $this->gogrid->call('grid.image.get', array(
			'id' => $instance_id
		));
		$response = json_decode($response);

		$status = $response->status;
		if(!$status == 'failure') $this->die_with_error('The snapshot could not be created from an instance yet');
		
		$response = $this->gogrid->call('grid.image.save', array(
			'server' => $instance_id,
			'description' => $description,
			'friendlyName'	=> $name
		));

		$response = json_decode($response);
	
		$this->test_response($response);
		
		$backup_id = $response->list[0]->id;
		$this->backup->add_backup(array(
			'instance_id'	=>	$instance_id,
			'provider_backup_id'	=>	$backup_id,
			'backup_name'	=>	$name,
			'description'	=>	$description,
			'provider'	=>	'GoGrid'
		));
		return true;
	}
	
	public function delete_backup($backup_id = false)
	{
		$this->load->model("Backup_model","backup");
		
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		if(!$backup) $this->die_with_error('No snapshot specified');
		$response = $this->gogrid->call('grid.image.delete', array(
			'id' => $backup->provider_backup_id
		));
		
		$response = json_decode($response);
		$this->test_response($response);
		
		$this->backup->remove_backup($backup->provider_backup_id);
		return true;
	}
	
	public function created_backups()
	{
		$this->load->model('Backup_model','backup');
		$backups = $this->backup->get_available_backups("GoGrid");
		
		foreach($backups as $i => $backup)
		{
			$backup->status = 'deleted';
			$backup->status = $this->get_backup_status($backup->provider_backup_id);
			$backups[$i] = $backup;
		}
		
		return $backups;
	}
	
	private function start_backup_image(array $backup)
	{
		$ips = $this->get_free_addresses();

		$ram = !isset($backup['ram']) ? $backup['ram'] : 1;
		$name = $backup['backup_name'];
		$provider_backup_id = $backup['provider_backup_id'];
		
		if(!count($ips)>0)
			return false;
		$ip = isset($backup['ip']) ? $backup['ip'] : $ips[0]['address'];
		
		$response= $this->gogrid->call('grid.server.add', array(
			'name' => $name,
			'image' => $provider_backup_id,
			'server.ram' => $ram,
			'ip' => $ip
		));

		$response = json_decode($response);
		$this->test_response($response);

		$this->load->model('Instance_model', 'instance');
		
		// write to db if things went fine
		$instance = $response->list[0];

		$this->instance->add_user_instance(array(
			'account_id' => $this->session->userdata('account_id'),
			'instance_name' => $instance->name,
			'provider' => 'GoGrid',
			'public_ip' => $instance->ip->ip
		));
		
		return true;
	}
	
	public function restore_backup_to_corresponding_instance($backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);

		if(!$backup)
			return false;

		$response = $this->gogrid->call('grid.server.get', array(
			'id' => $backup->instance_id
		));
		$_instance = json_decode($response);

		if(isset($_instance) && $_instance->status == 'success')
		{
			$this->load->model("Instance_model","instance");
			
			$inst_id = $_instance->list[0]->id;
			$response = $this->gogrid->call('grid.server.delete', array(
				'id' => $inst_id
			));
			$this->load->model("Instance_model","backup");
			$instance_ids = $this->instance->get_instance_ids($inst_id);
			$this->instance->terminate_instance($instance_ids[0]['instance_id'], $this->session->userdata('account_id'));
		}
		else
			return false;
		
		$name = $_instance->list[0]->name;
		$ram = $_instance->list[0]->ram->id;
		
		$backup_image = array(
			'backup_name'	=> $name,
			'ram'	=> isset($ram) ? $ram : 1,
			'provider_backup_id' => $backup->provider_backup_id
		);
		
		return $this->start_backup_image($backup_image);
	}
	
	public function restore_backup_to_new_instance($backup_id, array $settings)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);
		if(!$backup)
			return false;
			
		$name = $settings['name'];
		$ip = $settings['ip'];
		$ram = $settings['type'];
		
		$backup_image = array(
			'backup_name'=> $name,
			'ram'	=> $ram,
			'ip'	=>	$ip,
			'provider_backup_id' => $backup->provider_backup_id
		);
		
		return $this->start_backup_image($backup_image);
	}
	
	public function describe_backup_instance($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');
		
		$instances = $this->get_backuped_instance($backup_id);
		
		return array(
			'success'	=> true,
			'instances'	=> $instances
		);
	}
	
	public function get_backuped_instance($backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);

		$response = $this->gogrid->call('grid.server.get', array(
			'id' => $backup->instance_id
		));
		$instance = json_decode($response);
		$instance = $instance->list[0];
		
		if($instance->object == "error")
			$instance_desrc = array();
		else
			$instance_desrc = array(
				'id'				=> $instance->id,
				'name'				=> (string) $instance->name,
				'state'			=> (string) $instance->state->name,
				'ip'				=> $instance->ip->ip
				// ''				=> (string) $instance->,
			);

		return $instance_desrc;
	}
	
	function get_backups($provider, $instance_id)
	{
		return $this->view_backups($provider, $instance_id);
	}
	
	function get_backup_status($provider_backup_id)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_provider_id($provider_backup_id);
		if(!$backup)
			return false;
		$response = $this->gogrid->call('grid.image.get', array(
			'id' => $backup->provider_backup_id
		));
		
		$_backup = json_decode($response);
		
		$_backup = $_backup->list[0];
		if(!isset($_backup->state))
			return false;

		return $_backup->state->name == "Available" ? 'completed' : $_backup->state->name;
	}
	
}