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
    private $salt = 'salt@%)@&FHW@FW)R&@F*@';
    
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
	
	public function generate_forgot_token($email,$password,$date)
	{
		$key = $this->salt.$date.$email;
		return md5($this->salt.$date.$email);
	}
	
	public function change_new_password($username)
	{
		$data = array(
		    'password'	=> $this->generatePassword()
		);
		
		$where['username = ?'] = $username;
		$this->_db->update($this->_name, $data, $where);
		
		return $data["password"];
	}
	
	public function change_password($user_id, $password)
	{
		$data = array(
		    'password'	=> $password
		);
		
		$where = $this->_db->quoteInto("id = ?", $user_id);
		
		$test = $this->update($data, $where);
	}
	
	public function get_user($user_id)
	{
	    $select = $this->_db->select()->from($this->_name, array('id','username','password','email', 'resetsenton'))->where('id = ?', $user_id);
        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result;
        } else {
            return TRUE;
        }
	}
	
	public function get_user_by_email($email)
	{
	    $select = $this->_db->select()->from($this->_name, array('id','username','password','email'))->where('email = ?', $email);
        $result = $this->getAdapter()->fetchRow($select);
        if ($result) {
            return $result;
        } else {
            return TRUE;
        }
	}
	
	public function send_welcome_email($email,$username)
	{
    	$settings = new Application_Model_DbTable_Settings();
		$mail = $settings->getSetting('wellcome_email');
		$mail_subject = $settings->getSetting('wellcome_email_subject');
		
		$options = array(
	        'auth'     => 'login',
	        'username' => 'support@tenbrain.com',
	        'password' => 'sp234567',
	        'ssl'      => 'tls',
	        'port' => 587
	    );
	    $mailTransport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $options);
	    Zend_Mail::setDefaultTransport($mailTransport);
	    
	    $settings_model = new Application_Model_DbTable_Settings();
	    $wellcome_body = $settings_model->getSetting('wellcome_email');
		$wellcome_email_subject = $settings_model->getSetting('wellcome_email_subject');
		
	    $wellcome_body = str_replace('%USERNAEM%', $username, $wellcome_body);
	    $m = new Zend_Mail();
	    $m->addTo($email);
	    $m->setFrom('support@tenbrain.com', 'Tenbrain Support');
	    $m->setSubject($wellcome_email_subject);
	    $m->setBodyText($wellcome_body);
	    $m->send();
	}
	
	public function send_reset_message($email)
	{
		$date = date( 'Y-m-d H:i:s', time() );
		$user = $this->get_user_by_email($email);
		if(!$user)
			return false;
		$token = $this->generate_forgot_token($user['email'],$user['password'],$date);
		$empty_token = $this->generate_forgot_token('','',$date);

		if($token != $empty_token)
			$this->update_token($user['id'], $date, 'resetsenton');
		//Send email
		
		$options = array(
	        'auth'     => 'login',
	        'username' => 'support@tenbrain.com',
	        'password' => 'sp234567',
	        'ssl'      => 'tls',
	        'port' => 587
	    );
	    $mailTransport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $options);
	    Zend_Mail::setDefaultTransport($mailTransport);
	    
	    $settings_model = new Application_Model_DbTable_Settings();
	    $reset_body = $settings_model->getSetting('password_reset');
		$password_reset_subject = $settings_model->getSetting('password_reset_subject');
		
	    $reset_body = str_replace('%TOKEN%', $token, $reset_body);
	    $reset_body = str_replace('%USERID%', $user['id'], $reset_body);
	    $reset_body = str_replace('%USERNAME%', $user['username'], $reset_body);
	    
	    $m = new Zend_Mail();
	    $m->addTo($user['email']);
	    $m->setFrom('support@tenbrain.com', 'Tenbrain Support');
	    $m->setSubject($password_reset_subject);
	    $m->setBodyText($reset_body);
	    $m->send();
	}
	
	public function valid_token($user_id, $token, $token_type)
	{
		$user = $this->get_user($user_id);
		
		$key = $this->salt.$user[$token_type].$user['email'];
		$_token = md5($key);

		return $token == $_token;
	}
	
	private function update_token($user_id, $token, $type)
	{
		$data = array(
		    $type	=> $token
		);
		
		$where = $this->_db->quoteInto("id = ?", $user_id);
		
		$test = $this->update($data, $where);
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
