<?php

class Balancer_model extends Model {
	
	function __construct(){
		parent::Model();
	}
	
	public function create_load_balancer($name, $provider, $provider_lb_id, $ip_address)
	{
		$this->db->insert('user_load_balancers', array(
			'account_id'		=> $this->session->userdata('account_id'),
			'name'				=> $name,
			'provider'			=> $provider,
			'provider_lb_id'	=> $provider_lb_id,
			'ip_address'		=> $ip_address
		));
		return $this->db->insert_id();
	}
	
	function get_load_balancer($lb_id)
	{
		$this->db->select('provider, load_balancer_id as id, provider_lb_id as pid');
		$query = $this->db->get_where('user_load_balancers', array('load_balancer_id' => $lb_id));		
		return $query->num_rows() ? $query->row() : false;
	}
	
	function get_user_load_balancers()
	{
		$sql = 'SELECT lb.load_balancer_id as id, lb.provider_lb_id as p_id, lb.provider, lb.name';
		$sql .= ' FROM user_load_balancers lb';
		$sql .= ' LEFT JOIN deleted_load_balancers dlb USING(load_balancer_id)';
		$sql .= ' WHERE dlb.load_balancer_id IS NULL';
		$sql .= ' AND lb.account_id = ' . $this->session->userdata('account_id');
		
		$query = $this->db->query($sql);
		return $query->num_rows() ? $query->result() : array();
	}

	function get_delete_load_balancer_id($id,$user_id)
	{
		$sql = 'SELECT lb.provider_lb_id as id';
		$sql .= ' FROM user_load_balancers lb';
		$sql .= ' LEFT JOIN deleted_load_balancers dlb USING(load_balancer_id)';
		$sql .= ' WHERE dlb.load_balancer_id IS NULL';
		$sql .= ' AND lb.account_id = ' . $this->db->escape($user_id);
		$sql .= ' AND lb.load_balancer_id = ' . $this->db->escape($id);
		
		$query = $this->db->query($sql);
		if($query->num_rows === 0) $this->die_with_error('The load balancer you have requested was not found');
		$id = $query->row()->id; 
		
		return $id;
	}
	
	function delete_load_balancer($id,$user_id){
		$this->db->insert('deleted_load_balancers', array(
			'account_id'		=> $user_id,
			'load_balancer_id'	=> $id
		));
		
		$this->db->where('load_balancer_id', $id);
		$this->db->update('load_balancer_instances', array(
			'active' => false
		));
	}
	function add_load_balancer_instances($id,$lb_id)
	{
		$this->db->insert('load_balancer_instances', array(
			'load_balancer_id'	=> $id,
			'instance_id'		=> $lb_id,
			'active'			=> true
		));
	}

	function get_instances_for_lb($account_id, $provider_name)
	{
		$sql = 'SELECT ui.instance_id as id, ui.instance_name as name, ui.public_ip as address';
		$sql .= ' FROM user_instances ui';
		$sql .= ' LEFT JOIN user_deleted_instances udi USING(instance_id)';
		$sql .= ' WHERE ui.account_id = ' . $account_id;
		$sql .= ' AND udi.instance_id IS NULL';
		$sql .= " AND ui.provider='{$provider_name}'";
		
		$instances = array();
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			$instances[] = array(
				'id'		=> $row->id,
				'name'		=> $row->name . ' (' . $row->address . ')'
			);
		}

		return $instances;
	}

	function get_user_load_balancer($id)
	{
		$this->db->select('name, ip_address');
		$query = $this->db->get_where('user_load_balancers', array('load_balancer_id' => $id));
		$row = $query->row(); 
		return $row;
	}

	function update_user_load_balancer($id,$lb_pid)
	{
		$this->db->where('load_balancer_id', $id);
		$this->db->update('user_load_balancers', array(
			'provider_lb_id' => $lb_pid
		));
	}

	function get_instances_for_load_balancer($lb_id)
	{
		$sql = 'SELECT ui.instance_id as id, ui.instance_name as name, ui.public_ip as ip, lb.provider_lb_id as lb_id';
		$sql .= ' FROM load_balancer_instances lbi';
		$sql .= ' INNER JOIN user_load_balancers lb USING(load_balancer_id)';
		$sql .= ' INNER JOIN user_instances ui USING(instance_id)';
		$sql .= ' WHERE lbi.load_balancer_id = ' . $this->db->escape($lb_id);
		$sql .= ' AND lbi.active = 1';
		$sql .= ' ';
		
		$query = $this->db->query($sql);
		if(!$query->num_rows()) $this->die_with_error('No instances are registered within this load balancer');
		
		$resut = $query->result();
		
		return $resut;
	}
	
	function get_instances_for_lb_deregistering($lb_id)
	{
		$sql = 'SELECT ui.instance_id as id, ui.public_ip as ip';
		$sql .= ' FROM load_balancer_instances lbi';
		$sql .= ' INNER JOIN user_instances ui USING(instance_id)';
		$sql .= ' WHERE lbi.load_balancer_id = ' . $this->db->escape($lb->id);
		$sql .= ' AND lbi.active = 1';
		$sql .= ' ';
		
		$query = $this->db->query($sql); $deregister = $left = array();
		$num_registered = $query->num_rows();
		if(!$num_registered) $this->die_with_error('No instances are currently registered within this load balancer');
		
		$result = $query->result();
		
		return $result;
	}
}
