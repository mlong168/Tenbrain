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
    	$this->isAutorized();
		$form = new Paypal_Form_Creditcard();
		$this->view->form = $form;
		
		if ($this->getRequest()->isPost())
		{
			$params = $this->getRequest()->getParams();
			if($this->view->form->isValid($params))
			{
				$paypal = new Paypal_DoDirectPayment();
    			$page = $paypal->doDirectPayment();
    			
				$this->_helper->Redirector->gotoUrl('paypal/details/'.$page);
			}
			else
			{
				$this->view->errorElements = $this->view->form->getMessages();
			}
		}
    }
    
    public function detailsAction()
    {
    	$this->isAutorized();
    	$payment = new Application_Model_Paypal();
    	$this->view->id = $this->getRequest()->getParam('id');
    	$details = $payment->db_load($this->view->id);
		$this->view->details = $payment->isPaymentSuccessful($details['ack']);
    }
    
    private function isAutorized()
    {
    	$auth = Zend_Auth::getInstance();
  		if (!$auth->hasIdentity())
  		{
  			$this->_helper->Redirector->gotoUrl('account/sign_in');
  		}
    }
}
