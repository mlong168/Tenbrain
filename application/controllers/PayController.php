<?php
/**
 * PaypalController
 * 
 * @author
 * @version 
 */
class PayController extends Zend_Controller_Action
{
	protected $minMoneyAmount = 10;
	protected $monthlyPayments = array(
		'some_features',
		'more_features',
		'all_features'
	);
	
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
    	$payment_method = $this->getRequest()->getParam('type');
    	
    	/*if($payment_method != 'onetime')
    	{
    		$this->_helper->Redirector->gotoUrl('pay');
    	}*/
    	if($payment_method == 'onetime'){
			$form = new Paypal_Form_Amount();
			$this->view->form = $form;
			$this->view->monthly = false;
    	
			if ($this->getRequest()->isPost())
			{
				$params = $this->getRequest()->getParams();
				if($this->view->form->isValid($params))
				{
					//$amount = $params['money_amount'];
					if($params['payment_type'] == "ten_up")
					{
						$amount = $params['tenup_amount'];
					}
					else
					{
						$payment_type_role = new Application_Model_DbTable_PaymentTypeRole;
						$curr_payment_type = $payment_type_role->getPaymentType($params['payment_type']);

						$amount = $curr_payment_type->price;
					}
					$this->_helper->Redirector->gotoUrl('pay/creditcard/amount/'.$amount.'/paytype/'.$params['payment_type']);
				}
				else
				{
					$this->view->errorElements = $this->view->form->getMessages();
				}
			}
		}elseif($payment_method == 'monthly'){
			$form = new Paypal_Form_Monthly();
			$this->view->form = $form;
			$this->view->monthly = true;
			$payment_type_role = new Application_Model_DbTable_PaymentTypeRole;
			foreach($this->monthlyPayments as $monthlyPayment){
				$row = $payment_type_role->getPaymentType($monthlyPayment);
				$this->view->$monthlyPayment = $row['price'];
			}
			if($this->getRequest()->isPost()){
				$params = $this->getRequest()->getParams();

				if($this->view->form->isValid($params)){
					
					$curr_payment_type = $payment_type_role->getPaymentType($params['payment_type']);
					$amount = $curr_payment_type->price;


					$this->_redirect('pay/creditcard/amount/'.$amount.'/paytype/'.$params['payment_type']);
				}else
					$this->view->errorElements = $this->view->form->getMessages();
			}
		}
    }
    
    
    public function creditcardAction()
    {
    	$this->isAutorized();
    	
    	$amount = $this->getRequest()->getParam('amount');
			$payment_type = $this->getRequest()->getParam('paytype');
    	/*if( !(isset($amount) && $amount >= $this->minMoneyAmount) )
    	{
    		$this->_helper->Redirector->gotoUrl('pay');
    	}*/
    	
    	
		$form = new Paypal_Form_Creditcard($amount, $payment_type);
		$this->view->form = $form;
		
		if ($this->getRequest()->isPost())
		{
			$params = $this->getRequest()->getParams();
			
			if($this->view->form->isValid($params) & $this->isValidExpirationDate($params))
			{
				$paypal = new Paypal_DoDirectPayment();
    			$page = $paypal->doDirectPayment($params);
    			
				$this->_helper->Redirector->gotoUrl('pay/details/'.$page);
			}
		}
    }
    
    public function detailsAction()
    {
    	$this->isAutorized();
    	$payment = new Application_Model_Paypal();
    	if($this->getRequest()->getParam('id') != "")
			{
				$this->view->id = $this->getRequest()->getParam('id');
			}
			else
			{
				$this->view->id = substr($this->getRequest()->getRequestUri(), strrpos($this->getRequest()->getRequestUri(), "/")+1);
			}
    	$details = $payment->db_load($this->view->id);
			$this->view->details = $payment->isPaymentSuccessful($details['ack']);
    }
    
    private function isAutorized()
    {
    	$auth = Zend_Auth::getInstance();
  		if (!$auth->hasIdentity())
  		{
			$redirect = new Zend_Session_Namespace('sign_in_redirect');
			$redirect->url = 'http://payments.tenbrain.com';
  			$this->_helper->Redirector->gotoUrl('account/sign_in');
  		}
    }
    
    private function isValidExpirationDate($params)
    {
    	$month = $params['month'];
    	$year = $params['year'];
    	if(Paypal_Validate_ExpirationDate::isValid($month, $year))
    	{
    		return true;
    	}
    	$this->view->form->year->setErrors(array('isEmpty' => 'Expiration date is incorrect.'));
    	return false;
    }
}
