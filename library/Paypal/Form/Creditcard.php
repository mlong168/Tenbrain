<?php
class Paypal_Form_Creditcard extends Zend_Form
{
	private $inputClassName = 'control paypal';
	private $labelClassName = 'text paypal';
	private $cc_type = array(
							'Visa' => 'Visa',
							'Discover' => 'Discover',
							'MasterCard' => 'Master Card',
							'Amex' => 'American Express',
	);
	protected $amount;
	
	public function __construct($amount = null)
	{
		parent::__construct(); 
		$this->amount = round(($amount+0.3)/(1 - 0.029), 2);
		$this->init();
	}
	
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'payment');
		
		$cc_amount = $this->createElement('text', 'cc_amount', array(
							'label' => 'Money amount',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'maxlength' => 16,
        					'size' => 16,
        					'readonly' => 'true',
		));
		$cc_amount->setValue($this->amount);
		$this->setLabelDecorator($cc_amount);
		
		$number = $this->createElement('text', 'number', array(
							'label' => 'Credit Card Number',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'maxlength' => 16,
        					'size' => 16,
									'autocomplete' => 'OFF',
							'validators' => array(
								new Paypal_Validate_CreditCard(array(
										Paypal_Validate_CreditCard::VISA,
										Paypal_Validate_CreditCard::MASTERCARD,
										Paypal_Validate_CreditCard::DISCOVER,
										Paypal_Validate_CreditCard::AMERICAN_EXPRESS,
									)),
		)));
		$number->setAutoInsertNotEmptyValidator(false);
		$this->setLabelDecorator($number);
        
        $type = $this->createElement('select', 'type', array(
							'label' => 'Credit Card Type',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
        					'multioptions' => $this->cc_type
        ));
		$this->setLabelDecorator($type);
        
        $expMonth = $this->createElement('select', 'month', array(
							'label' => 'Expiration month/year',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
        					'multioptions' => $this->genMonth()
        ));
		$this->setLabelDecorator($expMonth);
        
        $expYear = $this->createElement('select', 'year', array(
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
        					'multioptions' => $this->genYears()
        ));
        
        $cvv2 = $this->createElement('text', 'cvv2', array(
							'label' => 'CVV2',
        					'maxlength' => 4,
        					'size' => 4,
        					'class'	=>	$this->inputClassName,
									'autocomplete' => 'OFF',
                            'required' => TRUE,
							'validators' => array(
        						new Paypal_Validate_CVV2()
        )));
        $cvv2->setAutoInsertNotEmptyValidator(false);
		$this->setLabelDecorator($cvv2);
		
        $signup = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash',
        ))
        					->setLabel('Make payment');


		$elements = array(
					$cc_amount,
                    $number,
                    $type,
                    $expMonth,
                    $expYear,
                    $cvv2,
                    $signup,
        );
        
        $this->addElements($elements);
        
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'dl',
                                   'class' => 'payment_form')),
            'Form',
        ));
        
	}
	
	private function genMonth()
	{
		$monthList = array();
		foreach (range(1, 12) as $value) {
			$monthList[$value]= $value;
		}
		return $monthList;
	}
	
	private function genYears()
	{
		$currentYear = date('Y');
		$yearsList = array();
		foreach (range($currentYear, $currentYear + 10) as $value) {
			$yearsList[$value]= $value;
		}
		return $yearsList;
	}
	
	private function setLabelDecorator($element, $class = null)
	{
		if($class == null)
			$class = $this->labelClassName;
		$element->getDecorator('Label')
				->setOptions(array('tag' => 'dt', 'class' => $class));
	}
	
	
}
?>