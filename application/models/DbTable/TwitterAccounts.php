<?php
/**
 * TwitterAccounts
 */

class Application_Model_DbTable_TwitterAccounts extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'twitter_accounts';
    
	public function isUnique($id) {
        $select = $this->_db->select()->from($this->_name)->where('twitter_id = ?', (int)$id);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
	public function get_user($id) {
        $select = $this->_db->select()->from($this->_name)->where('twitter_id = ?', (string)$id);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return $result["account_id"];
        } else {
            return FALSE;
        }
    }
}
