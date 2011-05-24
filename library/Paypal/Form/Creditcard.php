<?php
class Paypal_Form_Creditcard extends Zend_Form
{
	private $inputClassName = 'control paypal';
	private $cc_type = array(
							'Visa',
							'Master Card',
							'American Express'
	);
	
	public function init()
	{
		$this->setMethod('post');
		$this->setAttrib('id', 'add');
		
		$number = $this->createElement('text', 'number', array(
							'label' => 'Credit Card Number',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'validators' => array(
								new Zend_Validate_Digits(),
								new Zend_Validate_StringLength(array('max' => 16))
		)));
        
        $type = $this->createElement('text', 'type', array(
							'label' => 'Credit Card Type',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE
        ));
        
        $expMonth = $this->createElement('select', 'month', array(
							'label' => 'expiration month',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
        					'multioptions' => $this->genMonth()
        ));
        
        $expYear = $this->createElement('select', 'year', array(
							'label' => 'expiration year',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
        					'multioptions' => $this->genYears()
        ));
        
        $cvv = $this->createElement('text', 'cvv', array(
							'label' => 'CVV',
        					'class'	=>	$this->inputClassName,
                            'required' => TRUE,
							'validators' => array(new Zend_Validate_StringLength(array('max' => 4)))
        ));
		
        $signup = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))
        					->setLabel('Sign In');

        $elements = array(
                    $number,
                    $type,
                    $expMonth,
                    $expYear,
                    $cvv,
                    $signup,
        );
        
//        foreach ($elements as $element) {
//        	$element = $element->setDecorators(array('ViewHelper'));
//        }
        
        $this->addElements($elements);
	}
	
	private function genMonth()
	{
		$monthList = array();
		foreach (range(1, 12) as $value) {
			array_push($monthList, $value);
		}
		return $monthList;
	}
	
	private function genYears()
	{
		$currentYear = date('Y');
		$yearsList = array();
		foreach (range($currentYear, $currentYear + 10) as $value) {
			array_push($yearsList, $value);
		}
		return $yearsList;
	}
	
	
}
?>