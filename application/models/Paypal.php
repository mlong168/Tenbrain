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
    
    public function __construct()
    {
    	$this->paypal = new Paypal_DoDirectPayment();
    }
    
    public function doDirectPayment() 
    {
    	$this->details = $this->paypal->doDirectPayment();
    	#TODO: save datails to db
		return $this->details;
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
}
