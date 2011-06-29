<?php
/**
 * AccountRoleExpModel
 * 
 * @author Yevgebn Vershynin
 * @version 
 */
class Application_Model_AccountRoleExp extends Zend_Db_Table_Abstract
{
	/**
	 * The table name 
	 */
	protected $_name = 'account_role_exp';
	
	public function isExpired($user_id, $role_id){
		$sql = $this->_db->select()
						->from($this->_name)
						->where('account_id = ?', $user_id)
						->where('role_id = ?', $role_id)
						->where('expiration_date >= CURDATE()');
		$result = $this->_db->query($sql)->fetch();
		if($result)
			return false;
		else
			return true;
	}		
}