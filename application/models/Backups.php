<?php

class Application_Model_Backups
{
	private $user_id;
	private $cassie;
	
	function __construct()
	{
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		$this->cassie = new ZendExt_Cassandra();
	}
	
	public function addBackups(array $backups)
	{
		$this->cassie->useColumnFamilies(array('BACKUPS', 'USER_BACKUPS'));
		
		foreach ($backups as $backup) {
			$uuid = ZendExt_CassandraUtil::uuid1();
			$data['server_id'] = $uuid;
			$this->cassie->BACKUPS->insert($uuid, $backup);
			$this->cassie->USER_BACKUPS->insert($this->user_id, 
			array($uuid => ''));
		}
	}
	
	public function removeBackups(array $backup_ids)
	{
		$this->cassie->useColumnFamilies('USER_BACKUPS', 'USER_DELETED_BACKUPS');
		
		foreach ($backup_ids as $id)
			$this->cassie->USER_DELETED_BACKUPS->insert($this->user_id, 
			array($id => ''));
		
		$this->cassie->USER_BACKUPS->remove($this->user_id, $server_ids);
	}
	
	public function getUserBackups ()
	{
		$backups = array();
		$this->cassie->useColumnFamilies(array('BACKUPS', 'USER_BACKUPS'));
		
		$backup_ids = $this->cassie->USER_BACKUPS->get($this->user_id);
		foreach ($backup_ids as $id) {
			$backups[] = $this->cassie->BACKUPS->get($id);
		}
		return $backups;
	}
	
	public function getUserBackupsById ($backup_ids)
	{
		$backups = array();
		$this->cassie->useColumnFamilies(array('BACKUPS'));
		
		foreach ($backup_ids as $id) {
			$backups[] = $this->cassie->BACKUPS->get($id);
		}
		return $backups;
	}
	
	public function getBackupByProviderId($provider_backup_id)
	{
		$this->cassie->useColumnFamilies('BACKUPS', 'USER_BACKUPS', 'USER_DELETED_BACKUPS');

		$user_deleted_backup_ids = $server_ids = $this->cassie->USER_DELETED_BACKUPS->get($this->user_id);
		$user_backup_ids = $server_ids = $this->cassie->USER_BACKUPS->get($this->user_id);
		$backups_ids = array_merge($user_backup_ids,$user_deleted_backup_ids);
		
		foreach ($backups_ids as $id) {
			$backup = $this->cassie->BACKUPS->get($id);
			if($backup['provider_backup_id'] == $provider_backup_id)
				return $backup;
		}
		return NULL;
	}
	
	public function getBackupById($backup_id)
	{
		$this->cassie->useColumnFamilies(array('USER_BACKUPS', 'BACKUPS'));
		$bk = $this->cassie->USER_BACKUPS->get($this->user_id, $backup_id); // Check if user have this Backup
		if($bk)
			return $this->cassie->BACKUPS->get($backup_id);
	}
	
	public function getAvailableBackups($provider = "ALL", $server_id = FALSE)
	{
		$this->cassie->useColumnFamilies(array('BACKUPS', 'USER_BACKUPS', 'USER_DELETED_BACKUPS'));
		$bk_deleted = $this->cassie->USER_DELETED_BACKUPS->get($this->user_id);
		$bk = $this->cassie->USER_BACKUPS->get($this->user_id);
		$backup_ids = array_diff($bk, $bk_deleted);
		if(!$backup_ids)
			return array();
		
		$user_backups = $this->getUserBackupsById($backup_ids);
		
		$backups = $server_backups = array();
		if($user_backups)
		{
			$user_provider_backups = array();
			if($provider != "ALL")
			{
				foreach ($user_backups as $user_backup)
					if($user_server['provider'] == $provider)
						$user_provider_backups[] = $user_backup;
				$user_backups = $user_provider_backups;
			}
			if($server_id)
			{
				foreach($user_backups as $user_backup)
				{
					$server_backups[] = array(
						'backup_id'				=> $user_backup['backup_id'],
						'provider_backup_id'	=> $user_backup['provider_backup_id'],
						'backup_name'			=> $user_backup['backup_name'],
						'server_id'				=> $user_backup['server_id']
					);
				}
				return $server_backups;
			}
			foreach($user_backups as $user_backup)
			{
				$backups[] = array(
					'backup_id'				=> $user_backup['backup_id'],
					'provider_backup_id'	=> $user_backup['provider_backup_id'],
					'backup_name'			=> $user_backup['backup_name'],
					'server_id'				=> $user_backup['server_id']
				);
			}
			return $backups;
		}
		return array();
	}
	
	public function getBackupDetails($backup_ids, $fields = array('backup_name'))
	{
		$backups = array();
		$possible_fields = array('backup_id', 'provider_backup_id', 'backup_name', 'description', 'server_id', 'provider', 'created_on');
		$fields_to_retrieve = array();
		if (! is_array($fields)) $fields = array($fields);
		foreach ($fields as $field) {
			if (in_array($field, $possible_fields)) $fields_to_retrieve[] = $field;
		}
		if ($fields === array('*')) $fields_to_retrieve = $possible_fields;
		$columns = implode(',', $fields_to_retrieve);
		
		$this->cassie->useColumnFamilies(array('BACKUPS'));
		
		foreach ($backup_ids as $id) {
			$backups[] = $this->cassie->BACKUPS->get($id, $columns);
		}
		return $backups;
	}
}