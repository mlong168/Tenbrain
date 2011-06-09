<?php
/**
 * PaypalModel
 * 
 * @author Kostik
 * @version 
 */
class Application_Model_Paypal extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'payment_details';
    
    /**
     * 
     * details of transaction
     * @var array
     */
    protected $details;
    
    public function db_save($data, $payment_type) 
    {
    	$this->details = $data;
    	$auth = Zend_Auth::getInstance();
    	$bind = array(
    		'userid' 		=> $auth->getIdentity()->id,
    		'ack' 			=> urldecode($data['ACK']),
    		'amount' 		=> urldecode($data['AMT']),
    		'timestamp' 	=> urldecode($data['TIMESTAMP']),
    		'transactionid'	=> $this->urldecode_save('TRANSACTIONID'),
    		'error_short' 	=> $this->urldecode_save('L_SHORTMESSAGE0'),
    		'error_long' 	=> $this->urldecode_save('L_LONGMESSAGE0'),
    		'details' 		=> urldecode(print_r($data, true)),
    	);
    	
    	
    	if($this->isPaymentSuccessful($bind['ack']))
    	{
    		$this->upgradeAccount($payment_type);
    	}
    	
    	
    	return $this->insert($bind);
    }
    
    private function urldecode_save($name, $saveAs = null)
    {
    	if (isset($this->details[$name]))
    	{
    		return urldecode($this->details[$name]);
    	}
   		return $saveAs;
    }
    
    public function db_load($id)
    {
		$result = $this->find($id);
		if (count($result) > 0)
		{
    		$this->details = $result->getRow(0)->toArray();
    		return $this->details;
		}
    	return false;
    }
    
    
    public function isPaymentSuccessful($ack)
    {
    	if (isset($ack))
	    	if(	"SUCCESS" == strtoupper($ack) || 
	    		"SUCCESSWITHWARNING" == strtoupper($ack)) 
			{
				return true;
			} 
		return false;
    }
    
    public function upgradeAccount($payment_type)
    {
    	//upgrage account accourding to payment type. get period in db
			$payment_type_role = new Application_Model_DbTable_PaymentTypeRole;
			$curr_payment_type = $payment_type_role->getPaymentType($payment_type);

			$paid_days = $curr_payment_type->time_period;
			$exp_date = date("Y-m-d", strtotime("+".$paid_days." days"));
			
			$account_role_exp = new Application_Model_AccountRoleExp();
			$select = $account_role_exp->select()->where("account_id = ?", Zend_Auth::getInstance()->getIdentity()->id);
			$existing_account = $account_role_exp->fetchRow($select);

			if(!empty($existing_account))
			{			
				$new_exp_date = date("Y-m-d", strtotime("+".$paid_days." days", strtotime($existing_account->expiration_date)));
				
				$data = array(
					"role_id" => $curr_payment_type->acl_role_id,
					"expiration_date" => $new_exp_date
				);
				$where = $account_role_exp->getAdapter()->quoteInto('id = ?', $existing_account->id);
				$account_role_exp->update($data, $where);
			}
			else
			{
				$bind = array(
					"account_id" => Zend_Auth::getInstance()->getIdentity()->id,
					"role_id" => $curr_payment_type->acl_role_id,
					"expiration_date" => $exp_date
				);
				
				$account_role_exp->insert($bind);
			}
			
    }
    
    
    
}
