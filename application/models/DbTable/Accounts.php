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
        if ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
	
	public function isValidSecure($data)
	{
		$select = $this->_db->select()->from($this->_name)->where('email = ?', $data["email"])->where('secure_key = ?', $data["secure_key"]);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return TRUE;
        } else {
            return FALSE;
        }
	}
	
	public function generate_forgot_key($email)
	{
		$data = array(
		    'secure_key'      => md5(time()+"sol")
		);
		
		$where['email = ?'] = $mail;
		$this->_db->update($this->_name, $data, $where);
		
		return $data["secure_key"];
	}
	
	public function change_new_password($username)
	{
		$data = array(
		    'password'      => $this->generatePassword()
		);
		
		$where['username = ?'] = $username;
		$this->_db->update($this->_name, $data, $where);
		
		return $data["password"];
	}
	
	private function generatePassword ($length = 6)
	{
		$password = "";
		$possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
		$maxlength = strlen($possible);
		if ($length > $maxlength) {
		  $length = $maxlength;
		}
		$i = 0; 
		while ($i < $length) { 
		
			$char = substr($possible, mt_rand(0, $maxlength-1), 1);
			   
			if (!strstr($password, $char)) { 
			$password .= $char;
			    $i++;
			}
		
		}
		return $password;
	
	}
}
