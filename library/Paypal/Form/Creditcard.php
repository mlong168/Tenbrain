<?php
class Paypal_Form_Creditcard extends Zend_Form
{
	private $inputClassName = 'control paypal';
	private $labelClassName = 'text paypal';
	private $cc_type = array(
							'Visa' => 'Visa',
							'Master Card' => 'Master Card',
							'American Express' => 'American Express'
	);
	
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'payment');
		
		$number = $this->createElement('text', 'number', array(
							'label' => 'Credit Card Number',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'maxlength' => 16,
        					'size' => 16,
							'validators' => array(
								new Zend_Validate_CreditCard()
		)));
		$this->setLabelDecorator($number);
		$number->setErrorMessages(array('Bad credit card number.'));
        
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
        
        $cvv2 = $this->createElement('text', 'cvv', array(
							'label' => 'CVV2',
        					'maxlength' => 4,
        					'size' => 4,
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'validators' => array(new Zend_Validate_StringLength(array('max' => 4)))
        ));
		$this->setLabelDecorator($cvv2);
		
        $signup = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash',
        ))
        					->setLabel('Make payment');


		$elements = array(
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