<?php

class Application_Model_Balancer
{
	private $user_id;
	private $cassie;
	
	function __construct()
	{
		$this->user_id = Zend_Auth::getInstance()->getIdentity()->id;
		$this->cassie = new ZendExt_Cassandra();
	}
}