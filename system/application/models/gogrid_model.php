<?php

class Gogrid_model extends Model {

	public $gogrid;
	
	public $name = 'GoGrid';
	
	function __construct()
	{
		parent::Model();
		$this->load->helper('gogrid');
		$this->gogrid = new GoGridClient();
		
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
	
	private function die_with_error($error_message)
	{
		header('Content-type: application/json');
		echo json_encode(array(
			'error'			=> true,
			'error_message'	=> $error_message
		));
		die; // how can you proceed if things failed? ;)
	}
	
	public function lookup($lookup)
	{
		$response = $this->gogrid->call('common.lookup.list', array(
			'lookup' => $lookup
		));
		return json_decode($response);
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
					'description'	=> $image->description,
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
		// print_r($response);//die;
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
				'type'				=> $server->type->name,
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
		
		$pid = $response->list[0]->id;
		
		$this->db->where('instance_id', $id);
		$this->db->update('user_instances', array(
			'provider_instance_id' => $pid
		));
		
		return $pid;
	}
	
	public function get_instances()
	{
		$sql = 'SELECT ui.instance_id, ui.instance_name, ui.provider_instance_id, ui.public_ip';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		
		$query = $this->db->query($sql);
		$names = array();
		$empties = array();
		$ids = array();
		foreach($query->result() as $row)
		{
			$names []= $row->instance_name;
			if(is_null($row->provider_instance_id))
			{
				$empties[$row->public_ip] = $row->instance_name;
			}
			else
			{
				$ids[$row->provider_instance_id] = $row->instance_id;
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
					$this->db->where(array(
						'public_ip'		=> $ip,
						'instance_name'	=> $empties[$ip]
					));
					$this->db->update('user_instances', array('provider_instance_id' => $id));
					$this->db->select('instance_id');
					$query = $this->db->get_where('user_instances', array('provider_instance_id' => $id));
					$id = $query->row()->instance_id;
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
	
	private function retrieve_provider_instance_id($instance_id)
	{
		$this->db->select('provider_instance_id')->from('user_instances')->where(array(
			'instance_id'	=> $instance_id,
			'account_id'	=> $this->session->userdata('account_id')
		));
		$query = $this->db->get();
		
		return $query->num_rows === 1 ? (int) $query->row()->provider_instance_id : false;
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
					'size' => $ram->name
				);
			}
			return $out;
		}
		else return false;
	}
	
	public function launch_instance($params)
	{	
		$response = $this->gogrid->call('grid.server.add', $params);
		$response = json_decode($response);
		// print_r($response);die;
		$this->test_response($response);
		
		$this->load->model('Instance_model', 'instance');
		
		// write to db if things went fine
		$instance = $response->list[0];
		// print_r($instance);
		$this->instance->add_user_instance(
			$this->session->userdata('account_id'),
			null,
			$instance->name,
			'GoGrid',
			$instance->ip->ip
		);
		return true;
	}
	
	public function delete_instance($id)
	{
		$instance_id = $this->retrieve_provider_instance_id($id);
		if(!$instance_id) return false;
		
		$response = $this->gogrid->call('grid.server.delete', array(
			'id' => $instance_id
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$success =  $response->status === 'success';
		if($success)
		{
			$this->db->insert('user_deleted_instances', array(
				'instance_id'	=> $id,
				'account_id'	=> $this->session->userdata('account_id')
			));
		}
		return $success;
	}
	
	public function get_password($instance_id)
	{
		$instance_id = $this->retrieve_provider_instance_id($instance_id);
		$response = $this->gogrid->call('support.password.list');
		$response = json_decode($response);
		$this->test_response($response);
		
		foreach($response->list as $pass)
		{
			if($pass->server->id === $instance_id)
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
		// $instance_id = $this->retrieve_provider_instance_id($instance_id);
		$response = $this->gogrid->call('grid.server.power', array(
			'id'	=> $instance_id,
			'power'	=> $action
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		return true;
	}
	
	public function stop_instance($instance_id)
	{
		return $this->power_instance($instance_id, 'stop');
	}
	
	public function start_instance($instance_id)
	{
		return $this->power_instance($instance_id, 'start');
	}
	
	public function restart_instance($instance_id)
	{
		return $this->power_instance($instance_id, 'restart');
	}
	
	public function reboot_instances($instance_ids)
	{
		foreach($instance_ids as $instance_id)
		{
			$this->power_instance($instance_id, 'restart');
		}
		return true;
	}
	
	public function stop_instances($instance_ids)
	{
		foreach($instance_ids as $instance_id)
		{
			$this->power_instance($instance_id, 'stop');
		}
		return true;
	}
	
	public function terminate_instances($instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		
		foreach($instance_ids as $instance_id)
		{
			$response = $this->gogrid->call('grid.server.delete', array(
				'id' => $instance_id
			));
			$response = json_decode($response);
			$this->test_response($response);
			
			$instance_id = $this->db->escape($instance_id);

			$this->instance->add_user_deleted_instance(
				$instance_id,
				$this->session->userdata('account_id')
			);
		}
		return true;
	}
	
	public function get_instances_for_lb()
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$account_id = $this->session->userdata('account_id');
		
		$instances = $this->balancer->get_instances_for_lb($account_id,$this->name);
		
		return $instances;
	}
	
	public function create_load_balancer($name, $ip, $instances)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$account_id = $this->session->userdata('account_id');
		
		$rows = $this->balancer->get_instances_by_provider_name($this->name,$account_id,$instances);
		$real_ips = array(); $i = 0;
		
		foreach($rows as $row)
		{
			$real_ips['realiplist.' . $i . '.ip'] = $row->ip;
			$real_ips['realiplist.' . $i . '.port'] = 80;
		}
		
		$response = $this->gogrid->call('grid.loadbalancer.add', array_merge(array(
			'name'				=> $name,
			'virtualip.ip'		=> $ip,
			'virtualip.port'	=> 80
		), $real_ips));
		$response = json_decode($response);
		$this->test_response($response);
		
		$lb = $response->list[0];
		
		$this->db->insert('user_load_balancers', array(
			'account_id'	=> $this->session->userdata('account_id'),
			'name'			=> $lb->name,
			'provider'		=> $this->name,
			'ip_address'	=> $ip
		));
		$lb_id = $this->db->insert_id();
		
		// a bit unreliable, should more relay on $lb->realiplist than on $instances
		foreach($instances as $i_id)
		{
			$this->balancer->insert_load_balancer_instance($lb_id,$i_id);
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
		
		$this->balancer->update_user_load_balancer($id,$lb_id);
		
		return $lb_pid;
	}
	
	public function delete_load_balancer($id)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$user_id = $this->session->userdata('account_id');

		$lb_id = $this->balancer->get_delete_load_balancer_id($id,$user_id); // should be only one
		
		$response = $this->gogrid->call('grid.loadbalancer.delete', array('id' => $lb_id));
		$response = json_decode($response);		
		$this->test_response($response);
		
		$this->balancer->delete_load_balancer($id,$user_id);
		
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
	
	public function get_load_balanced_instances($lb_id)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$rows = $this->balancer->get_instances_for_load_balancer($lb_id);
		
		$names = array(); $lb_id = '';
		foreach($rows as $row)
		{
			$names[$row->ip] = array(
				'id'	=> $row->id,
				'name'	=> $row->name
			);
			if(!$lb_id) $lb_id = $row->lb_id;
		}
		$response = $this->gogrid->call('grid.loadbalancer.get', array(
			'id' => $lb_id
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$instances = array();
		foreach($response->list[0]->realiplist as $instance)
		{
			$ip = $instance->ip->ip;
			$instances []= array(
				'id'				=> $names[$ip]['id'],
				'name'				=> $names[$ip]['name'],
				'ip_address'		=> $ip,
				'healthy'			=> (int) $instance->ip->state->id === 2,
				'health_message'	=> $instance->ip->state->description
				// ''	=> $lb->,
			);
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
	
	function register_instances_within_lb($lb, $instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Balancer_model', 'balancer');
		
		$lb = $this->db->escape($lb->id);
		
		$instances = $this->instance->get_register_instances_within_lb($lb,$instance_ids);
		
		$response = $this->gogrid->call('grid.loadbalancer.edit', array_merge(array(
			'id' => $lb->pid
		), $this->form_realip_array(array_values($instances))));
		$response = json_decode($response);
		$this->test_response($response);
		
		// rewrite it to relay more on an realiplist from response, when will have free time
		foreach($instances as $id => $ip)
		{
			$this->balancer->add_load_balancer_instances($id,$lb->id);
		}
		
		return true;
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
	
	function deregister_instances_from_lb($lb, $instance_ids)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Balancer_model', 'balancer');
		
		$rows = $this->balancer->get_instances_for_lb_deregistering($lb_id);
		
		foreach($query->result() as $row)
		{
			if(in_array($row->id, $instance_ids))
			{
				$deregister[$row->id] = $row->ip;
			}
			else
			{
				$left[$row->id] = $row->ip;
			}
		}
		if(count($left) === 0) $this->die_with_error('Please leave at least one');
		
		$response = $this->gogrid->call('grid.loadbalancer.edit', array_merge(array(
			'id' => $lb->pid
		), $this->form_realip_array(array_values($left))));
		$response = json_decode($response);
		$this->test_response($response);
		
		// rewrite it to relay more on an realiplist from response, when will have free time
		foreach($deregister as $id => $ip)
		{
			$this->instance->deregister_instances_in_lb($lb->id,$id);
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
}