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
    	$paypal = new Paypal_DoDirectPayment();
    	$this->view->message = $paypal->doDirectPayment();
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
