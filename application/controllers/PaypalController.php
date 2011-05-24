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

    }
    
    public function successAction()
    {
    	$paypal = new Application_Model_Paypal();
    	$details = $paypal->doDirectPayment();
    	
    	$this->view->form = $details;
    }
    
    public function cancelAction()
    {
    	$this->_redirect('paypal');
    }
    
    public function notifyAction()
    {
    	
    }
}
