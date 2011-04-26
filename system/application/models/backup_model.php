<?php

class Backup_model extends Model {
	
	function __construct()
	{
		parent::__construct();
	}
	
	function add_backup(array $backup)
	{
		$this->db->insert('user_backups', array(
				'instance_id'	=> $backup['instance_id'],
				'account_id'	=> $this->session->userdata('account_id'),
				'provider_backup_id'	=> $backup['provider_backup_id'],
				'backup_name'	=>	$backup['backup_name'],
				'description'	=>	$backup['description'],
				'provider'	=>	$backup['provider']
			));
	}
	
	function remove_backup($provider_backup_id)
	{
		$this->db->set('backup_id', "(SELECT backup_id FROM user_backups WHERE provider_backup_id = '".$provider_backup_id."')", false);
		$this->db->insert('user_deleted_backups', array(
				'account_id'	=> $this->session->userdata('account_id')
			));
	}
	
	function get_backup_by_provider_id($provider_backup_id)
	{
		$sql = 'SELECT ub.backup_id, ub.backup_name, ub.description, ub.provider, ub.provider_backup_id, ub.instance_id, ub.account_id, ub.created_on, udb.backup_id as removed_backup_id';
		$sql .= ' FROM user_backups ub';
		$sql .= ' LEFT JOIN user_deleted_backups udb USING(backup_id)';
		$sql .= ' WHERE ub.account_id = '.$this->session->userdata('account_id');
		$sql .= ' AND ub.provider_backup_id = \''.$provider_backup_id.'\'';
		
		$result = array(); $query = $this->db->query($sql);

		if($query->num_rows())
		{
			$result = $query->result();
			$result = $result[0];
		}
		return $result;
	}
	
	function get_backup_by_id($backup_id)
	{
		$sql = 'SELECT ub.backup_id, ub.backup_name, ub.description, ub.provider, ub.provider_backup_id, ub.instance_id, ub.account_id, ub.created_on, udb.backup_id as removed_backup_id';
		$sql .= ' FROM user_backups ub';
		$sql .= ' LEFT JOIN user_deleted_backups udb USING(backup_id)';
		$sql .= ' WHERE ub.account_id = '.$this->session->userdata('account_id');
		$sql .= ' AND ub.backup_id = \''.$backup_id.'\'';
		
		$result = array(); $query = $this->db->query($sql);

		if($query->num_rows())
		{
			$result = $query->result();
			$result = $result[0];
		}
		return $result;
	}

	function get_available_backups($provider = "ALL")
	{
		$sql = 'SELECT ub.backup_id as id, ub.backup_name as name, ub.description, ub.provider, ub.account_id, ub.created_on, ub.provider_backup_id';
		// , udb.backup_id as removed_backup_id';
		$sql .= ' FROM user_backups ub';
		$sql .= ' LEFT JOIN user_deleted_backups udb USING(backup_id)';
		$sql .= ' WHERE ub.account_id = '.$this->session->userdata('account_id');
		if(isset($provider) && $provider != 'ALL')
			$sql .= ' AND ub.provider = \''.$provider.'\'';
		$sql .= ' AND udb.backup_id IS NULL ';

		$result = array(); $query = $this->db->query($sql);

		if($query->num_rows())
		{
			$result = $query->result();
		}
		return $result;
	}
	
	public function get_backup_details($backup_ids, $fields = array('backup_name'))
	{
		$possible_fields = array('backup_id', 'provider_backup_id', 'backup_name', 'provider', 'description', 'created_on', 'instance_id');
		$fields_to_retrieve = array();
		if(!is_array($fields)) $fields = array($fields);
		foreach($fields as $field)
		{
			if(in_array($field, $possible_fields)) $fields_to_retrieve []= $field;
		}
		if($fields === array('*')) $fields_to_retrieve = $possible_fields;	// select all
		foreach($fields_to_retrieve as &$field) $field = 'ub.' . $field;
		
		if(!is_array($backup_ids)) $backup_ids = array($backup_ids);
		foreach($backup_ids as &$bkps) $bkps = $this->db->escape($bkps);
		
		$sql = 'SELECT ' . implode(',', $fields_to_retrieve);
		$sql .= ' FROM user_backups ub';
		$sql .= ' LEFT JOIN user_deleted_backups udb USING(backup_id)';
		$sql .= ' WHERE ub.account_id = ' . $this->session->userdata('account_id');
		$sql .= ' AND udb.backup_id IS NULL';
		$sql .= ' AND ub.backup_id IN(' . implode(',', $backup_ids) . ')';

		$query = $this->db->query($sql);
		return $query->num_rows() ? $query->result() : array();
	}
}
