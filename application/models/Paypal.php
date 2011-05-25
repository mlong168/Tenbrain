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
    protected $_name = 'paypal';
    protected $paypal;
    protected $details = false;
    protected $db_table = 'payment_details';
    
    public function __construct()
    {
    	$this->paypal = new Paypal_DoDirectPayment();
    }
    
    public function doDirectPayment() 
    {
    	$this->details = $this->paypal->doDirectPayment();
    	
    	#TODO: save datails to db
    	$bind = array(
    		'userid' 		=> 0,
    		'ack' 			=> urlencode($this->details['ACK']),
    		'amount' 		=> urlencode($this->details['AMT']),
    		'timestamp' 	=> urlencode($this->details['TIMESTAMP']),
    		'transactionid'	=> urlencode($this->details['TRANSACTIONID']),
    		'error_short' 	=> urlencode($this->details['L_SHORTMESSAGE0']),
    		'error_long' 	=> urlencode($this->details['L_LONGMESSAGE0']),
    		'details' 		=> urlencode(print_r($this->details, true)),
    	);
    	return $this->db_save($bind);
    }
    
    public function isPaymentSuccessful()
    {
    	if ($this->details)
	    	if("SUCCESS" == strtoupper($this->details["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($this->details["ACK"])) 
			{
				return true;
			} 
		return false;
    }
    
    private function db_save($bind)
    {    	
    	$sql = $this->_db->insert($this->db_table, $bind);
    	$result = $this->getAdapter()->fetchOne($sql);
    	return $this->_db->lastInsertId();
    }
    
    private function db_load()
    {
    	$select = $this->_db->select()->from($this->db_table)->where('username = ?', $username);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
