<?php
require_once 'ZendExt/Cassandra/columnfamily.php';

class ZendExt_Cassandra
{
	private $pool;
	
	function __construct() 
	{
		$servers = array("10.110.183.94:7000");
		$this->pool = new ConnectionPool("Tenbrain_dev", $servers);
		print_r($this->pool);
		die;
   }
   
   public function insert($key, array $data, $column_family)
   {
   		//$column = new ColumnFamily($this->pool, $column_family);
   		//$column->insert($key, $columns);
   }
}