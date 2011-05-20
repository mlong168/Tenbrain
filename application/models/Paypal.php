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
    
    public function __construct()
    {
    	$this->paypal = new Paypal_Main();
    }
    
    public function BuyButton() {
    	$responce = "<img src='".$this->paypal->BuyButton()."'>";
    	return $responce;
    }
    
    public function CryptedBuyButton($page) {
    	$responce = $this->paypal->CryptedBuyButton($page);
    	return $responce;
    }
}
