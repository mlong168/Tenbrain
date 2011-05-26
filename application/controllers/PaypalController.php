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
    	$table = new Application_Model_Paypal();
    	$this->view->id = $this->getRequest()->getParam('id');
    	$this->view->details = $table->db_load($this->view->id);
    	if (empty($this->view->details))
    	{
    		$this->view->details = '';
    	}
    }
    
    private function isAutorized()
    {
    	$auth = Zend_Auth::getInstance();
  		return $auth->hasIdentity();
    }
}
