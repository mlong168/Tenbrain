<?php
require_once 'ZendExt/Cassandra/columnfamily.php';

class ZendExt_Cassandra 
{
	const KEY_SPACE = 'Tenbrain_dev';
	const SERVER = '50.19.88.0:9160';
	
	public function useColumnFamilies(array $families)
	{
		$pool = new ConnectionPool(self::KEY_SPACE, array(self::SERVER));
		foreach($families as $family)
		{
			$this->$family = new ColumnFamily($pool, $family);
		}
	}
}