<?php
require_once 'ZendExt/Cassandra/columnfamily.php';

class ZendExt_Cassandra 
{
	const KEY_SPACE = 'Tenbrain_dev';
	const SERVER = 'ec2-50-16-91-150.compute-1.amazonaws.com:9160';
	
	public function use_column_families(array $families)
	{
		$pool = new ConnectionPool(self::KEY_SPACE, array(self::SERVER));
		foreach($families as $family)
		{
			$this->$family = new ColumnFamily($pool, $family);
		}
	}
}