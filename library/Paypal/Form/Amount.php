<?php
class Paypal_Form_Amount extends Zend_Form
{
	public function init()
	{
		$time_amount = new Zend_Form_Element_Text('time_amount');
    	$time_amount->setValidators(array(new Zend_Validate_Digits()));
    	$time_amount->setDecorators(array('ViewHelper'));
			$time_amount->setAttrib("class", "control paypal");
			$time_amount->setAttrib("width", "30px");
    	$time_amount->setValue(10);

    	$money_amount = new Zend_Form_Element_Text('money_amount');
    	$money_amount->setValidators(array(new Zend_Validate_Digits()));
    	$money_amount->setDecorators(array('ViewHelper'));
			$money_amount->setAttrib("class", "control paypal");
			$money_amount->setAttrib("width", "30px");
    	$money_amount->setValue(10);
    	
    	$submit = new Zend_Form_Element_Submit('submit');
    	$submit->setAttrib('class', 'login_submit underlined_dash');
    	$submit->setLabel('Enter Credit Card Info');

    	$this->addElements(array(
    		$time_amount,
    		$money_amount,
    		$submit,
    	));
	}
}
?>