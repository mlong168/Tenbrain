<?php
/**
 * PaypalController
 * 
 * @author
 * @version 
 */
class PayController extends Zend_Controller_Action
{
	private $selections = array(
		'pay_cc'	=> 
			array(
					'onetime'	=> array(
						'type'		=> 'big',
						'disabled'	=> false,
						'text'		=> 'Onetime<br>Payment'
					),
					'monthly'=>  array(
						'type'		=> 'big',
						'disabled'	=> true,
						'text'		=> 'Monthly<br>Payment'
					)
				)
	);
	
	/**
     * The default action - show the home page
     */
	
    public function indexAction ()
    {
    	$type = 'pay_cc';
		$this->view->assign(array(
			'selections'=> $this->selections[$type],
			'type'		=> $type,
			'next'		=> 'pay/amount/type'
		));
    }
    
    public function amountAction()
    {
    	$this->isAutorized();
    	$payment_type = $this->getRequest()->getParam('type');
    	
    	if($payment_type != 'onetime')
    	{
    		$this->_helper->Redirector->gotoUrl('pay');
    	}
    	
    	$form = new Paypal_Form_Amount();
    	$this->view->form = $form;
    	
    	if ($this->getRequest()->isPost())
		{
			$params = $this->getRequest()->getParams();
			if($this->view->form->isValid($params))
			{
				#TODO: pass params
				$this->_helper->Redirector->gotoUrl('pay/creditcard/');
			}
			else
			{
				$this->view->errorElements = $this->view->form->getMessages();
			}
		}
    }
    
    
    public function creditcardAction()
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
    			$page = $paypal->doDirectPayment($params);
    			
				$this->_helper->Redirector->gotoUrl('pay/details/'.$page);
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
