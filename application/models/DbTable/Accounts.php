<?php
/**
 * Accounts
 */

class Application_Model_DbTable_Accounts extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'accounts';
    
	public function isUnique($username) {
        $select = $this->_db->select()->from($this->_name)->where('username = ?', $username);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
	public function isUniqueEmail($email) {
        $select = $this->_db->select()->from($this->_name)->where('email = ?', $email);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
	public function getUserName($account_id) {
        $select = $this->_db->select()->from($this->_name)->where('account_id = ?', $account_id);
        $result = $this->getAdapter()->fetchOne($select);
        
        print_r($result);
        die();
        //return $result ? $
    }
}
