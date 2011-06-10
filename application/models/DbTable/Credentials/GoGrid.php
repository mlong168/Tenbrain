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
	
	public function get_credentials($user_id)
	{
		$select = $this->_db->select()
			->from($this->_name)
			->where('account_id = ?', $user_id);
			
		$result = $this->getAdapter()->fetchOne($select);
		
		return $result ? array(
			'key'			=> $result['key'],
			'secret_key'	=> $result['secret_key']
		) : false;
	}
	
	public function set_credentials($user_id, $credentials)
	{
		$this->_db->insert($this->_name, array(
			'account_id'	=> $user_id,
			'key'			=> $credentials['key'],
			'secret_key'	=> $credentials['secret_key']
		));
		
		return true;
	}
}