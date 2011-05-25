<?php

class Application_Model_Servers
{
	private $cassie;
	private $auth;
	
	function __construct()
	{
		$this->cassie = new ZendExt_Cassandra("SERVERS");
		$this->auth = Zend_Auth::getInstance();
	}
	
	public function add_server(array $data)
	{
		$uuid = ZendExt_CassandraUtil::uuid1();
		
		$this->cassie->insert($uuid, $data);
		
		$this->cassie->set_column_family('USER_SERVERS');
		
		$my_server_ids = $cassie->get($this->auth->getIdentity()->id);
		$my_server_ids = explode(',', $my_server_ids['server_ids']);
		$my_server_ids = array_merge($my_server_ids, array($uuid));
		
		$this->cassie->insert($this->auth->getIdentity()->id, array(
			'server_ids' => implode(',', $my_server_ids)
		));
	}
}