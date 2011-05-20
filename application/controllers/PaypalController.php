<?php
/**
 * PaypalController
 * 
 * @author
 * @version 
 */
class PaypalController extends Zend_Controller_Action
{
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {
    	$paypal = new Application_Model_Paypal();
    	$page = $this->view->url();

    	$buttonReturn = $paypal->CryptedBuyButton($page);
    	if(!$buttonReturn["status"]) {
			$this->_redirect('error');
		}
    	$this->view->CryptedBuyButton = $buttonReturn["encryptedButton"];
    }
    
    public function successAction()
    {
    }
    
    public function cancelAction()
    {
    	$this->_redirect('paypal');
    }
    
    public function notifyAction()
    {
    	
    }
}
