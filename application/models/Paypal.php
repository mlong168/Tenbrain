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
    
    public function db_save($data) 
    {
    	$this->details = $data;
    	
    	$bind = array(
    		'userid' 		=> '0',
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
    	$select = $this->_db->select()->from($this->_name)->where('id = ?', $id);
        $result = $this->getAdapter()->fetchAll($select);
    	return $result;
    }
    
    
//    public function isPaymentSuccessful()
//    {
//    	if ($this->details)
//	    	if(	"SUCCESS" == strtoupper($this->details["ACK"]) || 
//	    		"SUCCESSWITHWARNING" == strtoupper($this->details["ACK"])) 
//			{
//				return true;
//			} 
//		return false;
//    }
    
    
    
}
