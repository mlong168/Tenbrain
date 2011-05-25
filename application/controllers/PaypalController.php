<?php
/**
 * PaypalController
 * 
 * @author
 * @version 
 */
class PaypalController extends Zend_Controller_Action
{
//	public function preDispatch()
//	{
//		$this->session = new Zend_Session_Namespace('paypal');
//	}
	
	/**
     * The default action - show the home page
     */
    public function indexAction ()
    {
    	
    	
		$form = new Paypal_Form_Creditcard();
		$this->view->form = $form;
		
		if ($this->getRequest()->isPost())
		{
			$params = $this->getRequest()->getParams();
			if($this->view->form->isValid($params))
			{
				$paypal = new Application_Model_Paypal();
    			$details = $paypal->doDirectPayment();
    			
				$this->_helper->Redirector->gotoUrl('paypal/success/');
			}
			else
			{
				$this->view->errorElements = $this->view->form->getMessages();
			}
		}
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
    	//bad credit card info !
    }
    
    private function isAutorized()
    {
    	$auth = Zend_Auth::getInstance();
  		return $auth->hasIdentity();
    }
}
