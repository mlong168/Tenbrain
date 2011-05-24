<?php
require_once 'ZendExt/Cassandra/columnfamily.php';

class ZendExt_Cassandra 
{
	private $column_family;
	
	function __construct($column_family) 
	{
		//Settings
		$servers = array("50.19.88.0:9160");
		$key_space = "Tenbrain_dev";
		
		$pool = new ConnectionPool($key_space, $servers);
		$this->column_family =  new ColumnFamily($pool, $column_family);
   }
   
   public function insert($key, array $row)
   {
   		$this->column_family->insert($key, $row);
   }
   
   public function batch_insert(array $rows)
   {
   		$this->column_family->batch_insert($rows);
   }
   
   public function get($key)
   {
   		return (array)$this->column_family->get($key);
   }
   
   public function multiget($keys)
   {
   		return (array)$this->column_family->multiget($keys);
   }
   
   public function get_range($key_start, $key_finish)
   {
   		return (array)$this->column_family->get_range($key_start, $key_finish);
   }
   
   public function remove($key, $columns)
   {
   		$this->column_family->remove($key, $columns);
   }
   
   public function get_count($row_key, $columns)
   {
   		return $this->column_family->get_count($key, $columns);
   }
}