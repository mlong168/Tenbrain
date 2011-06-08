<?php
/**
 * FacebookAccounts
 */

class Application_Model_DbTable_FacebookAccounts extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'facebook_accounts';
	
	protected $_primary = array("account_id","facebook_id");
	
    public function isUnique($id) {
        $select = $this->_db->select()->from($this->_name)->where('facebook_id = ?', (int)$id);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
	public function get_user($id) {
        $select = $this->_db->select()->from($this->_name, 'account_id')->where('facebook_id = ?', (string)$id);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return $result;
        } else {
            return FALSE;
        }
    }
}
