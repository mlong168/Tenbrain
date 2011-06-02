<?php
/**
 * Credentials_Amazon
 */

class Application_Model_DbTable_Credentials_Amazon extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'account_aws_credentials';
	
	protected $_primary = array("account_id","user_id");

	public function get_credentials($user_id)
	{
		$select = $this->_db->select()
			->from($this->_name)
			->where('account_id = ?', $user_id);
			
		$result = $this->getAdapter()->fetchOne($select);
		
		return $result ? array(
			'user_id'		=> $result['user_id'],
			'key'			=> $result['key'],
			'secret_key'	=> $result['secret_key']
		) : false;
	}
	
	public function set_credentials($user_id, $credentials)
	{
		$this->_db->insert($this->_name, array(
			'account_id'	=> $user_id,
			'user_id'		=> $credentials['aws_user_id'],
			'key'			=> $credentials['key'],
			'secret_key'	=> $credentials['secret_key']
		));
		
		return true;
	}
	
}