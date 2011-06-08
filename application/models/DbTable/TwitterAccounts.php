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
	
    protected $_primary = array("account_id","twitter_id");
	
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
        $select = $this->_db->select('account_id')->from($this->_name)->where('twitter_id = ?', (string)$id);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return $result;
        } else {
            return FALSE;
        }
    }
}
