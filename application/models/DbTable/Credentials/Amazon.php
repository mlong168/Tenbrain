<?php
/**
 * Credentials_Amazon
 */

class Application_Model_DbTable_Credentials_Amazon extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'account_amazon_credentials';
	
	protected $_primary = array("account_id","user_id");
	
}