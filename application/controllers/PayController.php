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
    	$payment_type = $this->getRequest()->getParam('type');
    	
    	if($payment_type != 'onetime')
    	{
    		$this->_helper->Redirector->gotoUrl('pay');
    	}
    	
    	
    	$time_amount = new Zend_Form_Element_Text('time');
    	$time_amount->setValidators(array(new Zend_Validate_Digits()));
    	$time_amount->setDecorators(array('ViewHelper'));
    	$time_amount->setAttrib('value', '1');
    	$this->view->time_form = $time_amount;

    	$money_amount = new Zend_Form_Element_Text('money');
    	$money_amount->setValidators(array(new Zend_Validate_Digits()));
    	$money_amount->setDecorators(array('ViewHelper'));
    	$money_amount->setAttrib('value', '10');
    	$this->view->amount_form = $money_amount;
    	
    	$submit = new Zend_Form_Element_Submit('submit');
    	$submit->setAttrib('class', 'login_submit underlined_dash');
    	$submit->setLabel('Enter Credit Card Info');
    	$this->view->submit = $submit;
    }
    
//	public function deploymentAction()
//	{
//		$tenstack = $this->getRequest()->getParam('tenstack');
//		if(in_array($tenstack, array_keys($this->selections['tenstack'])))
//		{
//			$this->selected->tenstack = $tenstack;
//			
//			$type = 'deployment';
//			$this->view->assign(array(
//				'selections'=> $this->selections[$type],
//				'type'		=> $type,
//				'next'		=> 'selection/finals/deployment'
//			));
//		}
//		else
//		{
//			$this->_forward('error');
//		}
//	}
    
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
