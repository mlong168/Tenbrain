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
    
    public function db_save($data) 
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
    	return $this->insert($bind);
    }
    
    private function urldecode_save($name, $saveAs = '')
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
    
    
    
}
