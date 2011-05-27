<?php
/**
 * Credentials_GoGrid
 */

class Application_Model_DbTable_Credentials_GoGrid extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'account_gogrid_credentials';
	
	protected $_primary = array("account_id","secret_key");
	
}