<?php

class Instance_model extends Model {
		
	public $gogrid;
	
	function __construct(){
		parent::__construct();
	}
	
	function get_user_instances()
	{
		$sql = 'SELECT ui.instance_id as id, ui.provider, ui.provider_instance_id as pid,';
		$sql .= ' ui.instance_name as name, ui.public_ip as ip';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		$sql .= ' WHERE ui.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udi.instance_id IS NULL';
		
		$query = $this->db->query($sql);
		return $query->num_rows() ? $query->result() : array();
	}

	function get_updated_instance_id($vars)
	{
		$this->db->where(array(
			'public_ip'		=> $vars['public_ip'],
			'instance_name'	=> $vars['instance_name']
		));
		$this->db->update('user_instances', array('provider_instance_id' => $vars['id']));
		$this->db->select('instance_id');
		$query = $this->db->get_where('user_instances', array('provider_instance_id' => $vars['id']));
		return $query->row()->instance_id;
	}	
	
	function get_instances($account_id,$ids)
	{
		$sql = 'SELECT ui.provider, ui.provider_instance_id';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		$sql .= ' WHERE ui.account_id = ' . $account_id;
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= ' AND ui.instance_id IN (' . implode(',', $ids) . ')';
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			if(!array_key_exists($row->provider, $instances)) $instances[$row->provider] = array();
			$instances[$row->provider][] = $row->provider_instance_id;
		}
		return $instances;
		
	}
	
	function add_user_instance($account_id,$provider_instance_id = null,$instance_name,$provider,$public_ip = null)
	{
		$this->db->insert('user_instances', array(
			'account_id'			=> $account_id,
			'provider_instance_id'	=> $provider_instance_id,
			'instance_name'			=> $instance_name,
			'provider'				=> $provider,
			'public_ip'				=> $public_ip
		));
	}

	function terminate_instance($id,$account_id)
	{
		$this->db->insert('user_deleted_instances', array(
			'instance_id'	=> $id,
			'account_id'	=> $account_id
		));		
	}

	function add_user_deleted_instance($instance_id,$account_id)
	{
		$this->db->set('instance_id', "(SELECT instance_id FROM user_instances WHERE provider_instance_id = $instance_id)", false);
		$this->db->insert('user_deleted_instances', array(
			'account_id'	=> $account_id
		));
	}
	
	function get_instances_available_for_lb($provider,$account_id,$lb_id)
	{
		$sql = 'SELECT instance_id as id, provider_instance_id as p_id, instance_name as name, public_ip as ip';
		$sql .= ' FROM user_instances';
		$sql .= ' WHERE provider = ' . $provider;
		$sql .= ' AND account_id = ' . $account_id;
		$sql .= ' AND instance_id NOT IN (';
		$sql .= '  SELECT instance_id FROM load_balancer_instances where load_balancer_id = ' . $lb_id . ' and active = true';
		$sql .= ' )';
		$query = $this->db->query($sql);
		
		$instances = array();
		if($query->num_rows())
		{
			foreach($query->result() as $row)
			{
				$instances[] = array(
					'id'			=> $row->id,
					'instance_id'	=> $row->p_id,
					'name'			=> $row->name,
					'ip_address'	=> $row->ip
				);
			}
		}
		return $instances;
	}
	
	function get_register_instances_within_lb($lb,$instance_ids)
	{
		$sql = 'SELECT ui.instance_id as id, ui.public_ip as ip';
		$sql .= ' FROM load_balancer_instances lbi';
		$sql .= ' INNER JOIN user_instances ui USING(instance_id)';
		$sql .= ' WHERE lbi.load_balancer_id = ' . $this->db->escape($lb->id);
		$sql .= ' AND lbi.active = 1';
		$sql .= ' ';
		
		$query = $this->db->query($sql); $already_registered = array();
		if($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$already_registered[$row->id] = $row->ip;
			}
		}
		
		$this->db->delete('load_balancer_instances', array(
			'load_balancer_id' => $lb->id
		));
		
		$this->db->select('instance_id as id, public_ip as ip')->from('user_instances')->where_in('instance_id', $instance_ids);
		$query = $this->db->get(); $to_be_registered = array();
		if($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$to_be_registered[$row->id] = $row->ip;
			}
		}
		
		return $already_registered + $to_be_registered;
	}
	
	function deregister_instances_in_lb($load_balancer_id, $instance_id)
	{
		$this->db->where(array(
			'load_balancer_id'	=> $load_balancer_id,
			'instance_id'		=> $instance_id
		));
		$this->db->update('load_balancer_instances', array(
			'active' => false
		));
	}

	function get_instances_by_provider_name($name,$account_id,$instances)
	{
		$sql = 'SELECT ui.public_ip as ip';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		$sql .= ' WHERE ui.account_id = ' . $account_id;
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= " AND ui.provider='{$name}'";
		$sql .= ' AND ui.instance_id IN (' . implode(',', $instances) . ')';
		
		$real_ips = array(); $i = 0;
		$query = $this->db->query($sql);
		
		$result = $query->result();
		
		return $result;
	}
}
