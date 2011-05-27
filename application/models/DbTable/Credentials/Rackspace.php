<?php
/**
 * Credentials_Rackspace
 */

class Application_Model_DbTable_Credentials_Rackspace extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
	protected $_name = 'account_rackspace_credentials';
	
	protected $_primary = array("account_id", "username");

	public function get_credentials ($user_id)
	{
		$select = $this->_db->select()
			->from($this->_name)
			->where('account_id = ?', $user_id);
		$result = $this->getAdapter()->fetchOne($select);
		if ($result) {
			return $result;
		}
		else {
			return FALSE;
		}
	}
}