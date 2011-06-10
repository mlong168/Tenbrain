<?php

class Application_Model_Provider_GoGrid extends Application_Model_Provider
{
	public $gogrid;
	public $state = 'running';
	private $premium = true;
	private $default_type = "512MB";
	public $name = 'GoGrid';
	
	function __construct()
	{
		parent::__construct();
		
		$this->name = "GoGrid";
		$this->gogrid = new ZendExt_GoGridClient();
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
		$credentials = array(
			'key'			=> $row->key,
			'secret_key'	=> $row->secret_key
		);

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
		// TODO: Create new credentials
		$this->db->set('account_id', $this->session->userdata('account_id'));
		$this->db->set('key', $new_credentials['key']);
		$this->db->set('secret_key', $new_credentials['secret_key']);
		
		$this->db->insert('account_gogrid_credentials');
		//////////////////////
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
		// TODO: Update credentials		
		$this->db->where('account_id', $this->session->userdata('account_id'));
		$this->db->update('account_gogrid_credentials', $new_credentials);
		////////////////////////////////////////
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
	
	public function list_servers($ids, $state)
	{
		//if($state !== 'running') return array();
		
		$response = $this->gogrid->call('grid.server.get', array(
			'id' => array_keys($ids)
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$servers = array();
		foreach($response->list as $server)
		{
			$p_id = $server->id;
			$ip = $server->ip->ip;
			$servers []= array(
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
		
		return $servers;
	}
	
	public function assign_server_id($id)
	{
		$server_model = new Application_Model_Servers();
		$selected_server = $server_model->get_user_server($id);
		
		$ip = $selected_server['ip'];
		$response = $this->gogrid->call('grid.server.get', array('name' => $selected_server['name']));
		$response = json_decode($response);
		
		if($response->status !== 'success') return false;
		
		$pid = false;
		foreach($response->list as $server)
		{
			if($server->ip->ip === $ip)
			{
				$pid = $server->id;
				$server_model->update_provider_id($id,$pid);
			}
		}
		
		return $pid;
	}
	
	public function get_servers()
	{
		$server_model = new Application_Model_Servers();
		$serevers = $server_model->get_user_servers();
		$names = array();
		$empties = array();
		$ids = array();
		foreach($serevers as $server)
		{
			$names []= $server->name;
			if(is_null($server->pid))
			{
				$empties[$server->ip] = $server->name;
			}
			else
			{
				$ids[$server->pid] = $server->id;
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
					//TODO: create get_updated_instance_id
					$id = $server_model->get_updated_server_id(array(
						'public_ip' => $ip,
						'server_name' => $empties[$ip],
						'id' => $id
					));
				}
				
				if(isset($ids[$id])) $id = $ids[$id];
				
				$out []= array(
					'name'				=> $server->name,
					'server_id'			=> $id,
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
	
	public function allow_launch_server($roleid)
	{
		$loaded_servers_count = (int)$this->get_deployed_server_count();
		$available_server_count = (int)$this->account_server_count_limits[$this->name][$roleid];
		
		return ($available_server_count > $loaded_servers_count);
	}
	
	public function launch_server(array $params)
	{
		$response = $this->gogrid->call('grid.server.add', $params);
		$response = json_decode($response);

		$this->test_response($response);
		
		$server_model = new Application_Model_Servers();
		$server = $response->list[0];
		
		$server_model->add_server(array(
			// common to all providers
			'name'				=> $server->name,
			'provider_server_id'=> '',
			'provider'			=> $this->name,
			'type'				=> $server->ram->description,
			'image_id'			=> $server->image->id,
			
			// gogrid-specific data:
			'ram_id'	=> $server->ram->id,
			'ip'		=> $server->ip->ip
		));
		return true;
	}

	public function get_connection_info(array $params)
	{
		$server_id = $params['provider_server_id'];
		$auth = $this->get_password($server_id);
		return
			"Use password '{$auth['password']}' and username '{$auth['username']}' to connect to the server by ssh."
			. '<br />Alternatively, you can use our web console';
	}

	public function get_connection_params(array $params)
	{
		$server_id = $params['provider_server_id'];
		$auth = $this->get_password($server_id);
		
		return array(
			'hostname'	=> $params['ip'],
			'login_user'=> $auth['username'],
			'port'		=> 22,
			'provider'	=> strtolower($this->name)
		);
	}
	
	public function modify_server($server_id, $ram_size, $tb_server_id, $all_params)
	{
		$response = $this->gogrid->call('grid.server.edit', array(
			'id'			=> $server_id,
			'server.ram'	=> $ram_size 
		));
		$response = json_decode($response);
		$this->test_response($response);
			
		$all_params['type'] = $ram_size;
		$server_model = new Application_Model_Servers();
		$server_model->change_server($tb_server_id, $all_params);
		
		return true;
	}
	
	public function get_password($server_id)
	{
		$response = $this->gogrid->call('support.password.list');
		$response = json_decode($response);
		$this->test_response($response);
		
		foreach($response->list as $pass)
		{
			if(!isset($pass->server)) continue;
			
			if($pass->server->id === (int)$server_id)
			{
				return array(
					'username'	=> $pass->username,
					'password'	=> $pass->password,
				);
			}
		}
		
		return false;
	}
	
	private function power_server($server_id, $action)
	{
		$response = $this->gogrid->call('grid.server.power', array(
			'id'	=> $server_id,
			'power'	=> $action
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		return true;
	}
	
	public function reboot_servers(array $server_ids)
	{
		foreach($server_ids as $server_id)
		{
			$this->power_server($server_id, 'restart');
		}
		return true;
	}
	
	public function stop_servers(array $server_ids)
	{
		foreach($server_ids as $server_id)
		{
			$this->power_server($server_ids, 'stop');
		}
		return true;
	}
	
	public function start_servers(array $server_ids)
	{
		foreach($server_ids as $server_id)
		{
			$this->power_server($server_id, 'start');
		}
		return true;
	}
	
	
	
	public function terminate_servers(array $server_ids)
	{
		foreach($server_ids as $server_id)
		{
			$response = $this->gogrid->call('grid.server.delete', array(
				'id' => $server_id
			));
			$response = json_decode($response);
			$this->test_response($response);
		}
		
		$server_model = new Application_Model_Servers();
		$server_model->remove_servers(array_keys($server_ids));
		
		return true;
	}
	
	public function get_servers_for_lb()
	{
		$balancer_model = new Application_Model_Balancer();
		
		return $balancer_model->get_servers_available_fo_lb();
	}
	
	public function create_load_balancer($name, array $servers, $ip)
	{	
		$ips = array();
		foreach($servers as $ser)
		{
			$ips []= $serv['public_ip'];
		}

		$response = $this->gogrid->call('grid.loadbalancer.add', array_merge(array(
			'name'				=> $name,
			'virtualip.ip'		=> $ip,
			'virtualip.port'	=> 80
		), $this->form_realip_array($ips)));
		$response = json_decode($response);
		$this->test_response($response);
		
		$lb = $response->list[0];
		
		$balancer_model = new Application_Model_Balancer();
		$lb_id = $balancer_model->add_load_balancer(array($lb->name, $this->name, null, $ip));
		
		foreach($servers as $server)
		{
			$balancer_model->add_servers_in_lb($lb_id, array($server['server_id']));
		}
		return true;
	}

	public function assign_lb_id($id)
	{
		$balancer_model = new Application_Model_Balancer();
		
		$user_load_balancer = $balancer_model->get_user_load_balancer($id); 
		
		$name = $user_load_balancer->name; $ip = $user_load_balancer->ip_address;
		
		$response = $this->gogrid->call('grid.loadbalancer.get', array('name' => $name));
		$response = json_decode($response);
		
		if($response->status !== 'success') return false;
		
		$lb_pid = $response->list[0];
		$lb_pid = $lb_pid->id;
		// TODO: ADD update_user_load_balancer
		$balancer_model->update_user_load_balancer($id, $lb_pid);
		
		return $lb_pid;
	}
	
	public function delete_load_balancer($id, $tb_id)
	{
		$balancer_model = new Application_Model_Balancer();

		$lb = $balancer_model->get_user_load_balancer($id);
		$lb_id = $lb['provider_balancer_id'];
		if(!$lb_id) $this->die_with_error('The load balancer you have requested was not found or is not available to operate yet');
		
		$response = $this->gogrid->call('grid.loadbalancer.delete', array('id' => $lb_id));
		$response = json_decode($response);
		$this->test_response($response);
		
		$balancer_model->delete_load_balancer($id);
		
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
	
	public function get_load_balanced_servers($lb_pid, $lb_dbid)
	{
		$balancer_model = new Application_Model_Balancer();
		$rows = $balancer_model->get_load_balanced_servers($lb_dbid);
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
		
		$servers = array();
		foreach($response->list[0]->realiplist as $server)
		{
			$ip = $server->ip->ip;
			if(array_key_exists($ip, $names))
			{	
			$servers []= array(
				'id'				=> $names[$ip]['id'],
				'name'				=> $names[$ip]['name'],
				'ip_address'		=> $ip,
				'healthy'			=> (int) $server->ip->state->id === 2,
				'health_message'	=> $server->ip->state->description
				// ''	=> $lb->,
			);
			}
		}
		
		return $servers;
	}
	
	function servers_available_for_lb($lb_id)
	{
		$balancer_model = new Application_Model_Balancer();
		
		$provider = $this->db->escape($this->name);
		$account_id = $this->db->escape($this->session->userdata('account_id'));
		$lb_id = $this->db->escape($lb_id);
		
		$servers = $balancer_model->get_servers_available_for_lb($provider, $lb_id);
		
		return $servers;
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
	
	function register_servers_within_load_balancer($lb, $server_ids)
	{
		$server_model = new Application_Model_Servers();
		$balancer_model = new Application_Model_Balancer();
		
		$to_register = array();
		$servers = $server_model->get_server_details($server_ids, array('public_ip', 'server_id'));
		foreach($servers as $serv)
		{
			$to_register[$serv->public_ip] = $serv->server_id;
		}
		
		$already_registered = $balancer_model->get_load_balanced_servers($lb->id);
		$left = array();
		foreach($already_registered as $serv)
		{
			$ip = $serv['ip_address'];
			$to_register[$ip] = $left[$ip] = $serv['id'];
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
			$balancer_model->add_servers_in_lb($lb->id, array($id));
		}
		
		return true;
	}
	
	function deregister_servers_from_lb($lb, $server_ids)
	{
		$server_model = new Application_Model_Servers();
		$balancer_model = new Application_Model_Balancer();
		
		$left = array();
		$registered = $balancer_model->get_load_balanced_servers($lb->id);
		foreach($registered as $serv)
		{
			$left[$serv['ip_address']] = $serv['id'];
		}
		
		$to_deregister = array();
		$servers = $server_model->get_servers_details($server_ids, array('public_ip', 'server_id'));
		foreach($servers as $serv)
		{
			$to_deregister[$serv->public_ip] = $serv->server_id;
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
			$balancer_model->remove_servers_in_lb($id, array($lb->id));
		}
		
		return true;
	}
	
	function create_backup($id, $name, $description = 'sample description')
	{
		$backup_model = new Application_Model_Backups();
		
		$server_id = $this->get_provider_server_id($id);
		if(!$server_id) return false;
		
		$response = $this->gogrid->call('grid.image.get', array(
			'id' => $server_id
		));
		$response = json_decode($response);

		$status = $response->status;
		if(!$status == 'failure') $this->die_with_error('The snapshot could not be created from an server yet');
		
		$response = $this->gogrid->call('grid.image.save', array(
			'server' => $server_id,
			'description' => $description,
			'friendlyName'	=> $name
		));

		$response = json_decode($response);
	
		$this->test_response($response);
		
		$backup_id = $response->list[0]->id;
		$backup_model->add_backup(array(
			'name'	=>	$name,
			'provider_backup_id'	=>	$backup_id,
			'description'	=>	$description,
			'provider'	=>	'GoGrid',

			'server_id'	=>	$server_id
		));
		return true;
	}
	
	public function delete_backup($backup_id = false)
	{
		$backup_model = new Application_Model_Backups();
		
		$backup = $backup_model->get_backup_by_id($backup_id);
		
		if(!$backup) $this->die_with_error('No snapshot specified');
		$response = $this->gogrid->call('grid.image.delete', array(
			'id' => $backup['provider_backup_id']
		));
		
		$response = json_decode($response);
		$this->test_response($response);
		
		$backup_model->remove_backup($backup_id);
		return true;
	}
	
	public function created_backups()
	{
		$backup_model = new Application_Model_Backups();
		$backups = $backup_model->get_available_backups($this->name);
		//foreach($backups as $i => $backup)
		//{
		//	$backup['status'] = 'deleted';
		//	$backup['status'] = $this->get_backup_status($backup['provider_backup_id']);
		//	$backups[$i] = $backup;
		//}
		
		return $backups;
	}
	
	private function start_backup_image(array $backup)
	{
		$server_model = new Application_Model_Servers();
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
		
		// write to db if things went fine
		$server = $response->list[0];

		$server_model->add_server(array(
			'name' 		=> $server->name,
			'provider'	=> 'GoGrid',
			'ip' 		=> $server->ip->ip
		));
		
		return true;
	}
	
	public function restore_backup_to_corresponding_server($backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);

		if(!$backup)
			return false;

		$response = $this->gogrid->call('grid.server.get', array(
			'id' => $backup['server_id']
		));
		$_server = json_decode($response);
		
		if(isset($_server) && $_server->status == 'success')
		{			
			$serv_id = $_server->list[0]->id;
			$response = $this->gogrid->call('grid.server.delete', array(
				'id' => $serv_id
			));
			$server_model = new Application_Model_Servers();
			$server_ids = $server_model->get_server_ids($serv_id);
			$server_model->remove_server($server_ids[0]);
		}
		else
			return false;
		
		$name = $_server->list[0]->name;
		$ram = $_server->list[0]->ram->id;
		
		$backup_image = array(
			'backup_name'	=> $name,
			'ram'	=> isset($ram) ? $ram : 1,
			'provider_backup_id' => $backup['provider_backup_id']
		);
		return $this->start_backup_image($backup_image);
	}
	
	public function restore_backup_to_new_server($backup_id, array $settings)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		if(!$backup)
			return false;
			
		$name = $settings['name'];
		$ip = $settings['ip'];
		$ram = $settings['type'];
		
		$backup_image = array(
			'backup_name'=> $name,
			'ram'	=> $ram,
			'ip'	=>	$ip,
			'provider_backup_id' => $backup['provider_backup_id']
		);
		
		return $this->start_backup_image($backup_image);
	}
	
	public function describe_backup_server($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');
		
		$servers = $this->get_backuped_server($backup_id);
		
		return array(
			'success'	=> true,
			'servers'	=> $servers
		);
	}
	
	public function get_backuped_server($backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);

		$response = $this->gogrid->call('grid.server.get', array(
			'id' => $backup['server_id']
		));
		$server = json_decode($response);
		$server = $server->list[0];
		
		if($server->object == "error")
			$server_desrc = array();
		else
			$server_desrc = array(
				'id'				=> $server->id,
				'name'				=> (string) $server->name,
				'state'				=> (string) $server->state->name,
				'ip'				=> $server->ip->ip
				// ''				=> (string) $instance->,
			);

		return $server_desrc;
	}
	
	function get_backups($provider, $server_id)
	{
		return $this->view_backups($provider, $server_id);
	}
	
	function get_backup_status($provider_backup_id)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_provider_id($provider_backup_id);

		if(!$backup)
			return false;
			
		$response = $this->gogrid->call('grid.image.get', array(
			'id' => 7836
		));
		$_backup = json_decode($response);
		print_r($_backup);die;
		$_backup = $_backup->list[0];
		if(!isset($_backup->state))
			return false;

		return $_backup->state->name == "Available" ? 'completed' : $_backup->state->name;
	}

	public function test()
	{
		// $response = $this->gogrid->call('support.password.list');
		// $response = json_decode($response);	
		// print_r($response);
		print_r($this->list_load_balancers());
		echo PHP_EOL;die;
	}
}