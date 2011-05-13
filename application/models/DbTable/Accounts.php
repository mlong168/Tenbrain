<?php
/**
 * Accounts
 * 
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
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
