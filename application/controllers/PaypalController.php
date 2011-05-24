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
			if(!$this->view->form->isValid($this->getRequest()->getParams()))
			{
				
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
    	
    }
}
