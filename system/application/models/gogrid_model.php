<?php

class Gogrid_model extends Model {

	private $gogrid;

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
			header('Content-type: application/json');
			echo json_encode(array(
				'error'			=> true,
				'error_message'	=> $error_message
			));
			die; // how can you proceed if things failed? ;)
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
	
	public function get_images()
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
	
	public function get_instances()
	{
		$this->db->select('instance_name, provider_instance_id, public_ip')->from('user_instances')->where('account_id', $this->session->userdata('account_id'));
		$query = $this->db->get();
		$names = array();
		$empties = array();
		foreach($query->result() as $row)
		{
			$names []= $row->instance_name;
			if(is_null($row->provider_instance_id))
			{
				$empties[$row->public_ip] = $row->instance_name;
			}
		}
		if(empty($names)) return array();
		
		$response = $this->gogrid->call('grid.server.list', array(
			// 'name' => $names
		));
		$response = json_decode($response);
		if($response->status === 'success')
		{
			$out = array();
			foreach($response->list as $server)
			{
				$id = isset($server->id) ? $server->id : 'none';
				$ip = $server->ip->ip;
				
				if($id && isset($empties[$ip]))
				{
					$this->db->where(array(
						'public_ip'		=> $ip,
						'instance_name'	=> $empties[$ip]
					));
					$this->db->update('user_instances', array('provider_instance_id' => $id));
				}
				
				$out []= array(
					'name'				=> $server->name,
					'instance_id'		=> $id,
					'dns_name'			=> $ip,
					'ip_address'		=> $ip,
					'image_id'			=> $server->image->id,
					'state'				=> $server->state->name,
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
		print_r($response);die;
		$response = json_decode($response);
		$this->test_response($response);
		
		// write to db if things went fine
		$instance = $response->list[0];
		$this->db->insert('user_instances', array(
			'account_id'			=> $this->session->userdata('account_id'),
			// 'provider_instance_id'	=> $instance->id,
			'instance_name'			=> $instance->name,
			'provider'				=> 'GoGrid',
			'public_ip'				=> $instance->ip->ip
		));
		die;
		
		return true;
	}
	
	public function delete_instance($id)
	{
		// $this->db->select('provider_instance_id')->from('user_instances')->where('instance_id', $id);
		// $query = $this->db->get();
		// if(!$query->num_rows()) return false;
		
		// $instance_id = $query->row()->provider_instance_id;
		$response = $this->gogrid->call('grid.server.delete', array(
			// 'id' => $instance_id
			'id' => $id
		));
		$response = json_decode($response);
		$this->test_response($response);
		
		$success =  $response->status === 'success';
		if($success)
		{
			// remove from db
			$this->db->delete('user_instances', array(
				'instance_id' => $id
			));
		}
		return $success;
	}
	
	private function power_instance($instance_id, $action)
	{
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

	public function test()
	{
		$response = $this->gogrid->call('grid.server.get', array(
			'name' => 'instance_id_tester'
		));
		$response = json_decode($response);	
		print_r($response);
		echo PHP_EOL;die;
	}
}